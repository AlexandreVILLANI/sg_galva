<?php

namespace App\Command;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-clients',
    description: 'Importe les clients depuis le CSV avec nettoyage forcé UTF-8',
)]
class ImportClientsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    /**
     * Nettoie, coupe et force l'encodage UTF-8 d'une chaîne
     */
    private function clean(?string $text, int $limit = 255): ?string
    {
        if ($text === null || $text === '') return null;

        // 1. Force UTF-8 en ignorant les caractères illégaux
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);

        // 2. Trim des espaces (y compris les espaces insécables bizarres 0xA0)
        $clean = trim($clean, " \t\n\r\0\x0B\xC2\xA0");

        // 3. Coupe à la longueur demandée
        return mb_substr($clean, 0, $limit, 'UTF-8');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectDir = $this->kernel->getProjectDir();
        $csvPath = $projectDir . '/public/uploads/import_clients.csv';

        if (!file_exists($csvPath)) {
            $io->error("Fichier introuvable : $csvPath");
            return Command::FAILURE;
        }

        try {
            // LECTURE ET NETTOYAGE BRUT DU FICHIER
            $content = file_get_contents($csvPath);

            // Conversion Windows-1252 vers UTF-8 avec translittération
            $content = iconv('Windows-1252', 'UTF-8//TRANSLIT//IGNORE', $content);

            // Création du lecteur CSV
            $csv = Reader::createFromString($content);
            $csv->setDelimiter(','); 
            $csv->setHeaderOffset(0); 
            
        } catch (\Exception $e) {
            $io->error("Erreur technique : " . $e->getMessage());
            return Command::FAILURE;
        }

        $records = $csv->getRecords();
        $repo = $this->em->getRepository(Client::class);
        
        $count = 0;
        $updates = 0;
        $creates = 0;

        $io->title('Importation...');
        $io->progressStart(iterator_count($records));

        // J'ai supprimé la ligne setSQLLogger qui posait problème

        foreach ($records as $record) {
            // Sécurité pour la clé du Nom
            $nomKey = array_key_exists('Intitulé', $record) ? 'Intitulé' : (array_keys($record)[1] ?? null);
            
            $rawNom = $record[$nomKey] ?? '';
            $nom = $this->clean($rawNom);

            if (empty($nom)) continue;

            $client = $repo->findOneBy(['nom' => $nom]);

            if (!$client) {
                $client = new Client();
                $client->setNom($nom);
                $creates++;
            } else {
                $updates++;
            }

            // --- MAPPING ---

            if (!empty($record['Numéro'])) $client->setRefInterne($this->clean($record['Numéro'], 50));
            if (!empty($record['Abrégé'])) $client->setAbrege($this->clean($record['Abrégé'], 50));
            
            // Adresse
            if (!empty($record['Adresse'])) {
                $adr = $this->clean($record['Adresse'], 255);
                $client->setAdresseFacturation($adr);
                if (!$client->getAdresseLivraison()) {
                    $client->setAdresseLivraison($adr);
                }
            }

            // CP / Ville / Pays
            if (!empty($record['Code postal'])) $client->setCodePostal($this->clean($record['Code postal'], 10));
            if (!empty($record['Ville']))       $client->setVille($this->clean($record['Ville'], 150));
            if (!empty($record['Pays']))        $client->setPays($this->clean($record['Pays'], 100));

            // Contacts
            if (!empty($record['Contact']))     $client->setContact($this->clean($record['Contact'], 255));
            if (!empty($record['Téléphone']))   $client->setTelephone($this->clean($record['Téléphone'], 20));
            if (!empty($record['Télécopie']))   $client->setFax($this->clean($record['Télécopie'], 20));
            if (!empty($record['E-mail']))      $client->setEmail($this->clean($record['E-mail'], 255));

            // Infos Légales
            if (!empty($record['N° Siret']))       $client->setSiret($this->clean($record['N° Siret'], 50));
            if (!empty($record['N° identifiant'])) $client->setTvaIntra($this->clean($record['N° identifiant'], 50));
            if (!empty($record['Catégorie comptable'])) $client->setCategorieComptable($this->clean($record['Catégorie comptable'], 50));
            
            // Message Alerte
            if (!empty($record['Message Alerte'])) $client->setMessageAlerte($this->clean($record['Message Alerte'], 2000));

            // Encours
            if (!empty($record['Encours autorisé'])) {
                $encoursRaw = $record['Encours autorisé'];
                $encoursRaw = iconv('UTF-8', 'UTF-8//IGNORE', $encoursRaw);
                $encours = str_replace(',', '.', $encoursRaw);
                $encours = preg_replace('/[^0-9.]/', '', $encours); 
                $client->setEncoursAutorise(substr($encours, 0, 12));
            }

            $this->em->persist($client);
            $count++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();
        
        $io->success("Import réussi ! Créés: $creates | Mis à jour: $updates");

        return Command::SUCCESS;
    }
}