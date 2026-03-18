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
    description: 'Importe les clients avec recherche intelligente et agressive des colonnes',
)]
class ImportClientsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private KernelInterface $kernel
    ) {
        parent::__construct();
    }

    // Nettoyage standard (sans iconv qui bug sur certains serveurs)
    private function clean(?string $text, int $limit = 255): ?string
    {
        if ($text === null || $text === '') return null;
        $clean = trim($text);
        return mb_substr($clean, 0, $limit, 'UTF-8');
    }

    /**
     * Recherche "Agressive" : Écrase les accents, supprime les espaces, compare le texte pur.
     */
    private function findValue(array $record, array $keywords): ?string
    {
        foreach ($record as $key => $value) {
            $normalizedKey = mb_strtolower($key, 'UTF-8');
            $accents = ['é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e', 'à'=>'a', 'â'=>'a', 'ç'=>'c', 'î'=>'i', 'ï'=>'i', 'ô'=>'o', 'ö'=>'o', 'ù'=>'u', 'û'=>'u', 'ã©'=>'e'];
            $normalizedKey = strtr($normalizedKey, $accents);
            $normalizedKey = preg_replace('/[^a-z0-9]/', '', $normalizedKey);

            foreach ($keywords as $keyword) {
                $cleanKeyword = preg_replace('/[^a-z0-9]/', '', strtolower($keyword));
                if (str_contains($normalizedKey, $cleanKeyword)) {
                    return trim($value); 
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

        $content = file_get_contents($csvPath);
        
        if (mb_detect_encoding($content, 'UTF-8', true) === false) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $csv = Reader::createFromString($content);
        
        $delimiter = strpos($content, ';') !== false ? ';' : ',';
        $csv->setDelimiter($delimiter); 
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $repo = $this->em->getRepository(Client::class);
        
        $io->title("Importation des clients (Correction PostgreSQL)...");
        $io->progressStart(iterator_count($records));

        $count = 0;

        foreach ($records as $record) {
            
            // 1. On récupère le Nom et la Référence
            $nom = $this->clean($this->findValue($record, ['intitul', 'nom']));
            $ref = $this->clean($this->findValue($record, ['numer', 'code']), 50);
            
            if (empty($nom)) continue;

            $client = null;

            // 2. On cherche le client par sa Référence Interne d'abord
            if ($ref !== null && $ref !== '') {
                $client = $repo->findOneBy(['refInterne' => $ref]);
            }
            
            // 3. Sinon, on essaie de le trouver par son Nom
            if (!$client) {
                $client = $repo->findOneBy(['nom' => $nom]);
            }

            // 4. S'il n'existe toujours pas, on le crée
            if (!$client) {
                $client = new Client();
            }

            // --- ON REMPLIT LES INFOS ---
            
            $client->setNom($nom);
            if ($ref !== null && $ref !== '') $client->setRefInterne($ref);

            $abrege = $this->findValue($record, ['abreg']);
            if ($abrege !== null && $abrege !== '') $client->setAbrege($this->clean($abrege, 50));

            $adr = $this->findValue($record, ['adress', 'rue']);
            if ($adr !== null && $adr !== '') $client->setAdresseLivraison($this->clean($adr, 255));

            $cp = $this->findValue($record, ['postal', 'cp']);
            if ($cp !== null && $cp !== '') $client->setCodePostal($this->clean($cp, 10));

            $ville = $this->findValue($record, ['ville']);
            if ($ville !== null && $ville !== '') $client->setVille($this->clean($ville, 150));

            $pays = $this->findValue($record, ['pays']);
            if ($pays !== null && $pays !== '') $client->setPays($this->clean($pays, 100));

            $fax = $this->findValue($record, ['copie', 'fax']);
            if ($fax !== null && $fax !== '') $client->setFax($this->clean($fax, 50));

            $tel = $this->findValue($record, ['phon', 'tel']);
            if ($tel !== null && $tel !== '' && $tel !== $fax) $client->setTelephone($this->clean($tel, 50));

            $email = $this->findValue($record, ['mail']);
            if ($email !== null && $email !== '') $client->setEmail($this->clean($email, 255));

            $contact = $this->findValue($record, ['contact']);
            if ($contact !== null && $contact !== '') $client->setContact($this->clean($contact, 255));

            $siret = $this->findValue($record, ['siret']);
            if ($siret !== null && $siret !== '') $client->setSiret($this->clean($siret, 50));

            $tva = $this->findValue($record, ['identifiant', 'tva']);
            if ($tva !== null && $tva !== '') $client->setTvaIntra($this->clean($tva, 50));

            $msg = $this->findValue($record, ['alert', 'message']);
            if ($msg !== null && $msg !== '') $client->setMessageAlerte($this->clean($msg, 2000));

            $cat = $this->findValue($record, ['comptable', 'categ']);
            if ($cat !== null && $cat !== '') $client->setCategorieComptable($this->clean($cat, 50));

            $encoursRaw = $this->findValue($record, ['encours']);
            if ($encoursRaw !== null && $encoursRaw !== '') {
                $enc = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $encoursRaw));
                if ($enc !== '') $client->setEncoursAutorise(substr($enc, 0, 12));
            }

            // Portefeuille BL et FA
            $portefeuille = $this->findValue($record, ['portefeuille']);
            if ($portefeuille !== null && $portefeuille !== '') {
                $client->setPortefeuilleBlFa($this->clean($portefeuille, 50));
            }

            // Payeur
            $payeur = $this->findValue($record, ['payeur']);
            if ($payeur !== null && $payeur !== '') {
                $client->setPayeur($this->clean($payeur, 50));
            }

            // Assurance Crédit
            $assuCreditRaw = $this->findValue($record, ['assurance', 'credit']);
            if ($assuCreditRaw !== null && $assuCreditRaw !== '') {
                $assu = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $assuCreditRaw));
                if ($assu !== '') $client->setAssuranceCredit(substr($assu, 0, 12));
            }
            
            $this->em->persist($client);
            $count++;
            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();
        $io->success("$count clients importés et mis à jour avec succès !");

        return Command::SUCCESS;
    }
}
//php bin/console app:import-clients                                                   
