<?php

namespace App\MessageHandler;

use App\Message\CleanPhotosMessage;
use App\Entity\PhotoBonCommande;
use App\Entity\PhotoDechargement;
use App\Repository\ConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CleanPhotosMessageHandler
{
    private EntityManagerInterface $em;
    private ConfigurationRepository $configRepo;
    private string $projectDir;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        ConfigurationRepository $configRepo,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->configRepo = $configRepo;
        $this->projectDir = $projectDir;
        $this->logger = $logger;
    }

    public function __invoke(CleanPhotosMessage $message): void
    {
        $this->logger->info('Démarrage de la tâche de nettoyage automatique des photos.');
        
        $fs = new Filesystem();

        // Récupérer le délai configuré (par défaut 30 jours si introuvable)
        $config = $this->configRepo->findOneBy(['cle' => 'retention_photos_jours']);
        $joursRetention = $config ? (int) $config->getValeur() : 30;
        
        // Empêcher de tout supprimer par erreur (ex: si valeur <= 0)
        if ($joursRetention <= 0) {
            $joursRetention = 30;
        }

        $retentionSeconds = $joursRetention * 24 * 60 * 60; 
        $limitTime = time() - $retentionSeconds;

        $photosBon = $this->em->getRepository(PhotoBonCommande::class)->findAll();
        $photosFiche = $this->em->getRepository(PhotoDechargement::class)->findAll();

        $deletedCount = 0;

        // 1. Nettoyage des photos de Bons de Commande
        foreach ($photosBon as $photo) {
            $filePath = $this->projectDir . '/public/uploads/bons/' . $photo->getNomFichier();
            
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

        $this->em->flush();

        $this->logger->info(sprintf('Tâche terminée : %d photo(s) ont été supprimées avec succès (plus vielles que %d jours).', $deletedCount, $joursRetention));
    }
}
