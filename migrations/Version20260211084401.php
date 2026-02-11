<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211084401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_dechargement ADD poids DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_dechargement ADD u VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_dechargement ADD reference VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_dechargement ADD travaux_annexes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_dechargement ADD observations TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_dechargement DROP poids');
        $this->addSql('ALTER TABLE ligne_dechargement DROP u');
        $this->addSql('ALTER TABLE ligne_dechargement DROP reference');
        $this->addSql('ALTER TABLE ligne_dechargement DROP travaux_annexes');
        $this->addSql('ALTER TABLE ligne_dechargement DROP observations');
    }
}
