<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203130929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD adresse_facturation VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client RENAME COLUMN adresse TO adresse_livraison');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD adresse VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client DROP adresse_livraison');
        $this->addSql('ALTER TABLE client DROP adresse_facturation');
    }
}
