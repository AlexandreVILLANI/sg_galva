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
        // On injecte le service de hashage de mot de passe
        $this->hasher = $hasher;
    }

    // src/DataFixtures/AppFixtures.php

    public function load(ObjectManager $manager): void
    {
        // 1. Création des Rôles (pour ta table Role)
        $adminRole = new Role();
        $adminRole->setNom('Administrateur');
        $manager->persist($adminRole);

        $caristeRole = new Role();
        $caristeRole->setNom('Cariste');
        $manager->persist($caristeRole);

        // NOUVEAU : Rôle Réception
        $receptionRole = new Role();
        $receptionRole->setNom('Réception');
        $manager->persist($receptionRole);

        // 2. Création de ton compte Admin
        $admin = new User();
        $admin->setUsername('alex');
        $admin->setPrenom('Alex');
        $admin->setTypeAcces('MDP');
        $admin->setRoles(['ROLE_ADMIN']); 
        $admin->setUserRole($adminRole);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin')); // ou 'SgGalva2026!'
        $manager->persist($admin);

        // 3. Création du compte RÉCEPTION 1
        $reception1 = new User();
        $reception1->setUsername('thibaut');
        $reception1->setPrenom('Thibaut');
        $reception1->setTypeAcces('MDP');
        $reception1->setRoles(['ROLE_RECEPTION_TERRAIN']); // Le badge technique
        $reception1->setUserRole($receptionRole); // Le lien vers l'entité
        $reception1->setPassword($this->hasher->hashPassword($reception1, 'reception'));
        $manager->persist($reception1);

        // 4. Création d'un Cariste
        $cariste = new User();
        $cariste->setUsername('cariste_nord');
        $cariste->setPrenom('Jean Cariste');
        $cariste->setTypeAcces('LIEN');
        $cariste->setRoles(['ROLE_CARISTE']);
        $cariste->setUserRole($caristeRole);
        $cariste->setToken('cariste123');
        $cariste->setPassword($this->hasher->hashPassword($cariste, 'cariste'));
        $manager->persist($cariste);

        $zones = ['Zone A1', 'Zone A2', 'Quai Nord', 'Quai Sud', 'Stockage Extérieur'];
        foreach ($zones as $nomZone) {
            $emplacement = new Emplacement();
            $emplacement->setNom($nomZone);
            $manager->persist($emplacement);
        }

        // 5. Clients (inchangé)
        $nomsClients = ['ArcelorMittal', 'Eiffage', 'Bouygues Construction', 'Vinci','À définir'];
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