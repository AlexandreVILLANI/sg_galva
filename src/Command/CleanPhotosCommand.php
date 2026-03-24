<?php

namespace App\Command;

use App\Entity\PhotoBonCommande;
use App\Entity\PhotoDechargement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:clean-photos',
    description: 'Supprime les vieilles photos du serveur et de la base de données pour libérer de l\'espace.',
)]
class CleanPhotosCommand extends Command
{
    private EntityManagerInterface $em;
    private string $projectDir;

    public function __construct(
        EntityManagerInterface $em, 
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ) {
        parent::__construct();
        $this->em = $em;
        $this->projectDir = $projectDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();

        $retentionSeconds = 30 * 24 * 60 * 60; 
        $limitTime = time() - $retentionSeconds;

        $photosBon = $this->em->getRepository(PhotoBonCommande::class)->findAll();
        $photosFiche = $this->em->getRepository(PhotoDechargement::class)->findAll();

        $deletedCount = 0;

        // 1. Nettoyage des photos de Bons de Commande
        foreach ($photosBon as $photo) {
            $filePath = $this->projectDir . '/public/uploads/bons/' . $photo->getNomFichier();
            
            if ($fs->exists($filePath)) {
                // Si la date de modification du fichier est plus vieille que la limite
                if (filemtime($filePath) < $limitTime) {
                    $fs->remove($filePath); // Supprime le fichier physique
                    $this->em->remove($photo); // Supprime l'entrée en BDD
                    $deletedCount++;
                }
            } else {
                // Si le fichier physique a déjà disparu par erreur, on nettoie quand même la BDD
                $this->em->remove($photo);
            }
        }

        // 2. Nettoyage des photos de Fiches de Déchargement
        foreach ($photosFiche as $photo) {
            $filePath = $this->projectDir . '/public/uploads/fiches/' . $photo->getNomFichier();
            
            if ($fs->exists($filePath)) {
                if (filemtime($filePath) < $limitTime) {
                    $fs->remove($filePath);
                    $this->em->remove($photo);
                    $deletedCount++;
                }
            } else {
                $this->em->remove($photo);
            }
        }

        // On valide toutes les suppressions dans la base de données en une seule fois
        $this->em->flush();

        $io->success("$deletedCount photo(s) ont été supprimées avec succès !");

        return Command::SUCCESS;
    }
}

// cela n'est pas encore automatique