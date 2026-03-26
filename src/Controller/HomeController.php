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
use App\Repository\BonLivraisonRepository;

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
        if ($this->isGranted('ROLE_PESEE')) return $this->redirectToRoute('app_pesee_home'); // <--- NOUVEAU
        
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
        
        if ($this->isGranted('ROLE_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_ORDONNANCEMENT')) {
            return $this->redirectToRoute('app_reception_ordonnancement_home');
        }
        
        if ($this->isGranted('ROLE_RECEPTION_TERRAIN')) {
            return $this->redirectToRoute('app_reception_terrain_home');
        }

        if ($this->isGranted('ROLE_PESEE')) {
            return $this->redirectToRoute('app_pesee_home');
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
        BonTravailRepository $btRepository,
        BonLivraisonRepository $blRepo
    ): Response {
        $bons = $bcRepository->findBy([], ['date' => 'DESC']);
        $bons_travail = $btRepository->findBy([], ['dateCreation' => 'DESC']);
        $bonsLivraison = $blRepo->findBy([], ['id' => 'DESC']);

        return $this->render('home/reception_ordonnancement.html.twig', [
            'bons' => $bons,
            'bons_travail' => $bons_travail,
            'bons_livraison' => $bonsLivraison,
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
     * Espace ÉQUIPE PESÉE
     */
    #[Route('/pesee', name: 'app_pesee_home')]
    #[IsGranted('ROLE_PESEE')]
    public function peseeIndex(PlanningRepository $planningRepository): Response
    {
        // On récupère les plannings récents (exactement comme pour le Chef d'Équipe)
        $plannings = $planningRepository->findBy([], ['datePlanning' => 'DESC'], 15);

        return $this->render('home/pesee.html.twig', [
            'plannings' => $plannings,
        ]);
    }
    
    /**
     * Page de consultation d'un planning pour l'équipe PESÉE
     */
    #[Route('/pesee/planning/{id}', name: 'app_planning_pesee_edit')]
    #[IsGranted('ROLE_PESEE')] // Sécurité : uniquement pour ce rôle
    public function peseeEdit(\App\Entity\Planning $planning): Response
    {
        // On affiche la vue qu'on vient de créer, en lui passant le planning sélectionné
        return $this->render('planning/show.html.twig', [
            'planning' => $planning,
        ]);
    }
    

    // --- NOUVELLE ROUTE : IMPORT CSV DEPUIS L'ADMIN (ALGORITHME AGRESSIF) ---
    #[Route('/admin/import-clients', name: 'app_admin_import_clients_csv', methods: ['POST'])]
    public function importClientsCsv(Request $request, EntityManagerInterface $em): Response
    {
        $file = $request->files->get('csv_file');

        if (!$file) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
        }

        // 1. Lecture et conversion intelligente
        $content = file_get_contents($file->getPathname());
        if (mb_detect_encoding($content, 'UTF-8', true) === false) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // 2. Création du lecteur CSV avec détection du délimiteur
        $csv = \League\Csv\Reader::createFromString($content);
        $delimiter = strpos($content, ';') !== false ? ';' : ',';
        $csv->setDelimiter($delimiter); 
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $repo = $em->getRepository(Client::class);
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

            // Adresse mise dans Livraison
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
            
            $em->persist($client);
            $count++;
        }

        $em->flush();
        
        $this->addFlash('success', "$count clients ont été importés et mis à jour avec succès (Mode web) !");
        
        return $this->redirectToRoute('app_admin_home', ['section' => 'admin-cl']);
    }

   // --- HELPER METHODS (ALGORITHME AGRESSIF) ---
    private function clean(?string $text, int $limit = 255): ?string
    {
        if ($text === null || $text === '') return null;
        $clean = trim($text);
        return mb_substr($clean, 0, $limit, 'UTF-8');
    }

    private function findValue(array $record, array $keywords): ?string
    {
        foreach ($record as $key => $value) {
            // 1. Minuscules
            $normalizedKey = mb_strtolower($key, 'UTF-8');
            
            // 2. On écrase manuellement tous les accents (y compris les bugs d'encodage Excel)
            $accents = [
                'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e', 
                'à'=>'a', 'â'=>'a', 'ç'=>'c', 'î'=>'i', 
                'ï'=>'i', 'ô'=>'o', 'ö'=>'o', 'ù'=>'u', 
                'û'=>'u', 'ã©'=>'e', 'ã'=>'a'
            ];
            $normalizedKey = strtr($normalizedKey, $accents);
            
            // 3. On supprime tout ce qui n'est pas une lettre ou un chiffre
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
}