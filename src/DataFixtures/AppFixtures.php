<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Client;
use App\Entity\Emplacement;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création des Rôles (Référence)
        $adminRole = new Role();
        $adminRole->setNom('Administrateur');
        $manager->persist($adminRole);

        $caristeRole = new Role();
        $caristeRole->setNom('Cariste');
        $manager->persist($caristeRole);

        $receptionTerrainRole = new Role();
        $receptionTerrainRole->setNom('Réception Terrain');
        $manager->persist($receptionTerrainRole);

        $receptionOrdoRole = new Role();
        $receptionOrdoRole->setNom('Réception Ordonnancement');
        $manager->persist($receptionOrdoRole);

        $ordoRole = new Role();
        $ordoRole->setNom('Ordonnancement Planning');
        $manager->persist($ordoRole);

        // NOUVEAU : Rôle pour le Chef d'Équipe
        $chefEquipeRole = new Role();
        $chefEquipeRole->setNom("Chef d'Équipe");
        $manager->persist($chefEquipeRole);

        // 2. Création de ton compte Admin (Alex)
        $admin = new User();
        $admin->setUsername('alex');
        $admin->setPrenom('Alex');
        $admin->setTypeAcces('MDP');
        $admin->setRoles(['ROLE_ADMIN']); 
        $admin->setUserRole($adminRole);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);

        // 3. Création du compte RÉCEPTION TERRAIN (Thibaut)
        $reception1 = new User();
        $reception1->setUsername('thibaut');
        $reception1->setPrenom('Thibaut');
        $reception1->setTypeAcces('MDP');
        $reception1->setRoles(['ROLE_RECEPTION_TERRAIN']);
        $reception1->setUserRole($receptionTerrainRole);
        $reception1->setPassword($this->hasher->hashPassword($reception1, 'reception'));
        $manager->persist($reception1);

        // 4. Création du compte RÉCEPTION ORDONNANCEMENT (Dali)
        $reception2 = new User();
        $reception2->setUsername('dali');
        $reception2->setPrenom('Dali');
        $reception2->setTypeAcces('MDP');
        $reception2->setRoles(['ROLE_RECEPTION_ORDONNANCEMENT']);
        $reception2->setUserRole($receptionOrdoRole);
        $reception2->setPassword($this->hasher->hashPassword($reception2, 'reception'));
        $manager->persist($reception2);

        // 5. Création compte Ordonnancement (Gérard)
        $userOrdo = new User();
        $userOrdo->setUsername('gerard'); 
        $userOrdo->setPrenom('Gérard');
        $userOrdo->setTypeAcces('MDP');
        $userOrdo->setRoles(['ROLE_ORDONNANCEMENT']);
        $userOrdo->setUserRole($ordoRole);
        $userOrdo->setPassword($this->hasher->hashPassword($userOrdo, 'ordonnancement'));
        $manager->persist($userOrdo);

        // 6. NOUVEAU : Création du compte CHEF D'ÉQUIPE (Patrick)
        $userChef = new User();
        $userChef->setUsername('chef'); 
        $userChef->setPrenom('Jawed');
        $userChef->setTypeAcces('MDP');
        $userChef->setRoles(['ROLE_CHEF_EQUIPE']);
        $userChef->setUserRole($chefEquipeRole);
        $userChef->setPassword($this->hasher->hashPassword($userChef, 'chef'));
        $manager->persist($userChef);

        // 7. Création d'un Cariste (Jean)
        $cariste = new User();
        $cariste->setUsername('cariste_nord');
        $cariste->setPrenom('Jean Cariste');
        $cariste->setTypeAcces('LIEN');
        $cariste->setRoles(['ROLE_CARISTE']);
        $cariste->setUserRole($caristeRole);
        $cariste->setToken('cariste123');
        $cariste->setPassword($this->hasher->hashPassword($cariste, 'cariste'));
        $manager->persist($cariste);

        // 8. Emplacements et Clients
        $zones = ['Zone A1', 'Zone A2', 'Quai Nord', 'Quai Sud', 'Stockage Extérieur'];
        foreach ($zones as $nomZone) {
            $emplacement = new Emplacement();
            $emplacement->setNom($nomZone);
            $manager->persist($emplacement);
        }

        $nomsClients = ['ArcelorMittal', 'Eiffage', 'Bouygues Construction', 'Vinci', 'À définir'];
        foreach ($nomsClients as $nom) {
            $client = new Client();
            $client->setNom($nom);
            $client->setAdresseFacturation('12 Rue de l\'Acier');
            $client->setAdresseLivraison('12 Rue de l\'Acier');
            $client->setCodePostal('57000');
            $client->setVille('Metz');
            $client->setTelephone('03 87 00 00 00');
            $client->setFax('418 643-3210');
            $manager->persist($client);
        }

        $manager->flush();
    }
}