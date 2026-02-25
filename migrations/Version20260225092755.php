<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260225092755 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. On supprime l'ancienne colonne capricieuse
        $this->addSql('ALTER TABLE planning_ligne DROP COLUMN heure_mise_adisposition');
        
        // 2. On en recrée une toute neuve directement au format DATETIME (Timestamp)
        $this->addSql('ALTER TABLE planning_ligne ADD heure_mise_adisposition TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning_ligne ALTER heure_mise_adisposition TYPE TIME(0) WITHOUT TIME ZONE');
    }
}
