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
    description: 'Importe les clients avec recherche intelligente des colonnes',
)]
class ImportClientsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    // Nettoyage standard des valeurs
    private function clean(?string $text, int $limit = 255): ?string
    {
        if ($text === null || $text === '') return null;
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        $clean = trim($clean);
        return mb_substr($clean, 0, $limit, 'UTF-8');
    }

    /**
     * Cherche une valeur dans la ligne en utilisant des mots-clés.
     * Ex: Si on cherche ['phono', 'teleph'], il trouvera la colonne "Téléphone"
     */
    private function findValue(array $record, array $keywords): ?string
    {
        // On parcourt toutes les colonnes du fichier CSV
        foreach ($record as $key => $value) {
            // On convertit le nom de la colonne en minuscule sans accents pour comparer
            // Ex: "N° Siret" devient "n siret", "Téléphone" devient "telephone"
            $normalizedKey = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $key));
            
            foreach ($keywords as $keyword) {
                // Si le nom de la colonne contient le mot clé...
                if (str_contains($normalizedKey, $keyword)) {
                    return $value; // ... on retourne la valeur !
                }
            }
        }
        return null;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $csvPath = $this->kernel->getProjectDir() . '/public/uploads/import_clients.csv';

        if (!file_exists($csvPath)) {
            $io->error("Fichier introuvable : $csvPath");
            return Command::FAILURE;
        }

        // 1. Lecture et conversion FORCEE en UTF-8
        $content = file_get_contents($csvPath);
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');

        // 2. Création du lecteur CSV
        $csv = Reader::createFromString($content);
        $csv->setDelimiter(','); 
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $repo = $this->em->getRepository(Client::class);
        
        $io->title("Importation des clients (Mode Recherche)...");
        $io->progressStart(iterator_count($records));

        $count = 0;

        foreach ($records as $record) {
            
            // Recherche du NOM (Cherche une colonne contenant 'Intitul' ou 'Nom')
            $nom = $this->clean($this->findValue($record, ['intitul', 'nom']));
            
            if (empty($nom)) continue;

            $client = $repo->findOneBy(['nom' => $nom]);
            if (!$client) {
                $client = new Client();
                $client->setNom($nom);
            }

            // --- MAPPING PAR MOTS-CLÉS (Plus robuste) ---

            // Numéro / Ref Interne (Cherche 'numer' ou 'code')
            $ref = $this->findValue($record, ['numer', 'code']);
            if ($ref) $client->setRefInterne($this->clean($ref, 50));

            // Abrégé (Cherche 'abrege')
            $abrege = $this->findValue($record, ['abreg']);
            if ($abrege) $client->setAbrege($this->clean($abrege, 50));

            // Adresse
            $adr = $this->findValue($record, ['adress', 'rue']);
            if ($adr) {
                $adrClean = $this->clean($adr, 255);
                $client->setAdresseFacturation($adrClean);
                if (!$client->getAdresseLivraison()) $client->setAdresseLivraison($adrClean);
            }

            // Code Postal (Cherche 'postal' ou 'cp')
            $cp = $this->findValue($record, ['postal', 'cp']);
            if ($cp) $client->setCodePostal($this->clean($cp, 10));

            // Ville
            $ville = $this->findValue($record, ['ville']);
            if ($ville) $client->setVille($this->clean($ville, 150));

            // Pays
            $pays = $this->findValue($record, ['pays']);
            if ($pays) $client->setPays($this->clean($pays, 100));

            // TÉLÉCOPIE (FAX) -> Attention, chercher 'copie' ou 'fax' AVANT téléphone
            $fax = $this->findValue($record, ['copie', 'fax']);
            if ($fax) $client->setFax($this->clean($fax, 50));

            // TÉLÉPHONE -> Cherche 'phon' ou 'tel' (mais on exclut fax car on l'a déjà géré ?)
            // On utilise findValue qui prendra la colonne "Téléphone" car elle contient "phon"
            $tel = $this->findValue($record, ['phon', 'tel']);
            // Petite sécurité : si on a pris le fax par erreur (rare car 'copie' vs 'phon')
            if ($tel && $tel !== $fax) {
                 $client->setTelephone($this->clean($tel, 50));
            }

            // Email (Cherche 'mail')
            $email = $this->findValue($record, ['mail']);
            if ($email) $client->setEmail($this->clean($email, 255));

            // Contact
            $contact = $this->findValue($record, ['contact']);
            if ($contact) $client->setContact($this->clean($contact, 255));

            // SIRET (Cherche 'siret')
            $siret = $this->findValue($record, ['siret']);
            if ($siret) $client->setSiret($this->clean($siret, 50));

            // TVA Intra (Cherche 'identifiant' ou 'tva')
            $tva = $this->findValue($record, ['identifiant', 'tva']);
            if ($tva) $client->setTvaIntra($this->clean($tva, 50));

            // Message Alerte (Cherche 'alerte' ou 'message')
            $msg = $this->findValue($record, ['alert', 'message']);
            if ($msg) $client->setMessageAlerte($this->clean($msg, 2000));

            // Catégorie comptable (Cherche 'comptable' ou 'categorie')
            $cat = $this->findValue($record, ['comptable', 'categ']);
            if ($cat) $client->setCategorieComptable($this->clean($cat, 50));

            // Encours (Cherche 'encours')
            $encoursRaw = $this->findValue($record, ['encours']);
            if ($encoursRaw) {
                $enc = str_replace(',', '.', $encoursRaw);
                $enc = preg_replace('/[^0-9.]/', '', $enc);
                $client->setEncoursAutorise(substr($enc, 0, 12));
            }
            
            $this->em->persist($client);
            $count++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();
        $io->success("$count clients traités !");

        return Command::SUCCESS;
    }
}