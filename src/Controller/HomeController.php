<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

use App\Repository\FicheDechargementRepository;
use App\Repository\BonDeCommandeRepository; 
use App\Repository\BonTravailRepository;
use App\Repository\PlanningRepository; 
use App\Repository\ClientRepository;
use App\Entity\Client;

use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader; 

class HomeController extends AbstractController
{
    /**
     * Route racine "/" : L'aiguilleur central
     */
    #[Route('/', name: 'app_root')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');
        
        if ($this->isGranted('ROLE_ADMIN')) return $this->redirectToRoute('app_admin_home');
        if ($this->isGranted('ROLE_CHEF_EQUIPE')) return $this->redirectToRoute('app_chef_equipe_home');
        if ($this->isGranted('ROLE_ORDONNANCEMENT')) return $this->redirectToRoute('app_ordonnancement_home');
        if ($this->isGranted('ROLE_RECEPTION_ORDONNANCEMENT')) return $this->redirectToRoute('app_reception_ordonnancement_home');
        if ($this->isGranted('ROLE_RECEPTION_TERRAIN')) return $this->redirectToRoute('app_reception_terrain_home');
        
        return $this->redirectToRoute('app_home');
    }

    /**
     * Espace CARISTE (ROLE_USER / ROLE_CARISTE)
     */
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function home(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin_home');
        }
        
        // --- NOUVEAU : Sécurité pour Ordonnancement ---
        if ($this->isGranted('ROLE_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_reception_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_TERRAIN')) {
            return $this->redirectToRoute('app_reception_terrain_home');
        }

        return $this->render('home/index.html.twig', [
            'controller_name' => 'Espace Cariste',
        ]);
    }

    /**
     * Espace RÉCEPTION TERRAIN (Thibaut)
     */
    #[Route('/reception-terrain', name: 'app_reception_terrain_home')]
    #[IsGranted('ROLE_RECEPTION_TERRAIN')]
    public function receptionIndex(
        Request $request, 
        FicheDechargementRepository $ficheRepository,
        BonDeCommandeRepository $bcRepository 
    ): Response {
        $client = $request->query->get('client');
        $cariste = $request->query->get('cariste');
        $date = $request->query->get('date');
        $section = $request->query->get('section');

        $fiches = $ficheRepository->findWithFilters($client, $cariste, $date);
        $bons = $bcRepository->findBy([], ['date' => 'DESC'], 10);

        $queryForfaits = $bcRepository->createQueryBuilder('b')
            ->select('DISTINCT b.forfait AS name')
            ->where('b.forfait IS NOT NULL')
            ->orderBy('b.forfait', 'ASC')
            ->getQuery()
            ->getScalarResult();
        
        $uniqueForfaits = array_filter(array_column($queryForfaits, 'name'));

        $queryCaristes = $ficheRepository->createQueryBuilder('f')
            ->select('DISTINCT u.prenom AS name')
            ->join('f.cariste', 'u')
            ->orderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getScalarResult();
            
        $uniqueCaristes = array_filter(array_column($queryCaristes, 'name'));

        return $this->render('home/reception_terrain.html.twig', [
            'fiches' => $fiches,
            'bons' => $bons,
            'uniqueForfaits' => $uniqueForfaits,
            'uniqueCaristes' => $uniqueCaristes,
            'search_client' => $client,
            'search_cariste' => $cariste,
            'search_date' => $date,
            'active_section' => $section, 
        ]);
    }

    /**
     * Espace RÉCEPTION ORDONNANCEMENT (Dali)
     */
    #[Route('/reception-ordonnancement', name: 'app_reception_ordonnancement_home')]
    #[IsGranted('ROLE_RECEPTION_ORDONNANCEMENT')]
    public function receptionOrdonnancementIndex(
        BonDeCommandeRepository $bcRepository,
        BonTravailRepository $btRepository
    ): Response {
        $bons = $bcRepository->findBy([], ['date' => 'DESC']);
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC']);

        return $this->render('home/reception_ordonnancement.html.twig', [
            'bons' => $bons,
            'bons_travail' => $bons_travail,
        ]);
    }

    /**
     * Espace CHEF D'ÉQUIPE
     */
    #[Route('/chef-equipe', name: 'app_chef_equipe_home')]
    #[IsGranted('ROLE_CHEF_EQUIPE')]
    public function chefEquipeIndex(PlanningRepository $planningRepository): Response
    {
        // On récupère les plannings récents (ceux d'aujourd'hui et les derniers créés)
        // pour qu'il puisse pointer l'avancement.
        $plannings = $planningRepository->findBy([], ['datePlanning' => 'DESC'], 10);

        return $this->render('home/chef_equipe.html.twig', [
            'plannings' => $plannings,
        ]);
    }
    
    /**
     * Espace ORDONNANCEMENT (Gestion des Plannings GB/PB)
     */
    #[Route('/ordonnancement', name: 'app_ordonnancement_home')]
    #[IsGranted('ROLE_ORDONNANCEMENT')]
    public function ordonnancementIndex(
        BonTravailRepository $btRepository,
        PlanningRepository $planningRepository // <--- INJECTION ICI
    ): Response {
        
        // 1. On récupère les Bons de Travail pour l'onglet "À planifier"
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC']);
        
        // 2. On récupère les Plannings pour l'onglet "Plannings du Jour"
        $plannings = $planningRepository->findBy([], ['datePlanning' => 'DESC']);
        
        return $this->render('home/ordonnancement.html.twig', [
            'bons_travail' => $bons_travail,
            'plannings' => $plannings,
        ]);
    }

    /**
     * Espace ADMIN
     */
    #[Route('/admin', name: 'app_admin_home')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(BonDeCommandeRepository $bcRepository, ClientRepository $clientRepo): Response // <-- Ajout de l'injection
    {
        $bons = $bcRepository->findBy([], ['date' => 'DESC']);
        $clients = $clientRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('home/admin.html.twig', [
            'bons' => $bons,
            'clients' => $clients,
        ]);
    }

    // --- NOUVELLE ROUTE : IMPORT CSV DEPUIS L'ADMIN ---
    #[Route('/admin/import-clients', name: 'app_admin_import_clients_csv', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function importClientsCsv(Request $request, EntityManagerInterface $em): Response
    {
        $file = $request->files->get('csv_file');

        if (!$file) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
        }

        // 1. Lecture et conversion en UTF-8 (Comme dans ta commande)
        $content = file_get_contents($file->getPathname());
        $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');

        // 2. Création du lecteur CSV
        $csv = Reader::createFromString($content);
        $csv->setDelimiter(','); // Ajuste si ton CSV utilise des points-virgules ';'
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $repo = $em->getRepository(Client::class);
        $count = 0;

        foreach ($records as $record) {
            $nom = $this->clean($this->findValue($record, ['intitul', 'nom']));
            
            if (empty($nom)) continue;

            $client = $repo->findOneBy(['nom' => $nom]);
            if (!$client) {
                $client = new Client();
                $client->setNom($nom);
            }

            // --- REPRISE EXACTE DE TON MAPPING ---
            $ref = $this->findValue($record, ['numer', 'code']);
            if ($ref) $client->setRefInterne($this->clean($ref, 50));

            $abrege = $this->findValue($record, ['abreg']);
            if ($abrege) $client->setAbrege($this->clean($abrege, 50));

            $adr = $this->findValue($record, ['adress', 'rue']);
            if ($adr) {
                $client->setAdresseFacturation($this->clean($adr, 255));
            }

            $cp = $this->findValue($record, ['postal', 'cp']);
            if ($cp) $client->setCodePostal($this->clean($cp, 10));

            $ville = $this->findValue($record, ['ville']);
            if ($ville) $client->setVille($this->clean($ville, 150));

            $pays = $this->findValue($record, ['pays']);
            if ($pays) $client->setPays($this->clean($pays, 100));

            $fax = $this->findValue($record, ['copie', 'fax']);
            if ($fax) $client->setFax($this->clean($fax, 50));

            $tel = $this->findValue($record, ['phon', 'tel']);
            if ($tel && $tel !== $fax) {
                 $client->setTelephone($this->clean($tel, 50));
            }

            $email = $this->findValue($record, ['mail']);
            if ($email) $client->setEmail($this->clean($email, 255));

            $contact = $this->findValue($record, ['contact']);
            if ($contact) $client->setContact($this->clean($contact, 255));

            $siret = $this->findValue($record, ['siret']);
            if ($siret) $client->setSiret($this->clean($siret, 50));

            $tva = $this->findValue($record, ['identifiant', 'tva']);
            if ($tva) $client->setTvaIntra($this->clean($tva, 50));

            $msg = $this->findValue($record, ['alert', 'message']);
            if ($msg) $client->setMessageAlerte($this->clean($msg, 2000));

            $cat = $this->findValue($record, ['comptable', 'categ']);
            if ($cat) $client->setCategorieComptable($this->clean($cat, 50));

            $encoursRaw = $this->findValue($record, ['encours']);
            if ($encoursRaw) {
                $enc = str_replace(',', '.', $encoursRaw);
                $enc = preg_replace('/[^0-9.]/', '', $enc);
                $client->setEncoursAutorise(substr($enc, 0, 12));
            }
            
            $em->persist($client);
            $count++;
        }

        $em->flush();
        
        $this->addFlash('success', "$count clients ont été importés ou mis à jour avec succès !");
        
        // On redirige vers l'onglet des clients
        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
    }

    // --- HELPER METHODS REPRISES DE TA COMMANDE ---
    private function clean(?string $text, int $limit = 255): ?string
    {
        if ($text === null || $text === '') return null;
        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $text);
        $clean = trim($clean);
        return mb_substr($clean, 0, $limit, 'UTF-8');
    }

    private function findValue(array $record, array $keywords): ?string
    {
        foreach ($record as $key => $value) {
            $normalizedKey = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $key));
            foreach ($keywords as $keyword) {
                if (str_contains($normalizedKey, $keyword)) {
                    return $value;
                }
            }
        }
        return null;
    }
}