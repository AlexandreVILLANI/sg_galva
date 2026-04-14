<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414070948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_travail ADD is_termine BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE bon_travail ADD is_forfait BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE bon_travail ADD nom_forfait VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE bon_travail ADD prix_forfait DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_travail DROP is_termine');
        $this->addSql('ALTER TABLE bon_travail DROP is_forfait');
        $this->addSql('ALTER TABLE bon_travail DROP nom_forfait');
        $this->addSql('ALTER TABLE bon_travail DROP prix_forfait');
    }
}
