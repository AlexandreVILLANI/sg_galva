<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210123017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD ref_interne VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD abrege VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD siret VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD pays VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD tva_intra VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD encours_autorise NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD message_alerte TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD categorie_comptable VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP ref_interne');
        $this->addSql('ALTER TABLE client DROP abrege');
        $this->addSql('ALTER TABLE client DROP contact');
        $this->addSql('ALTER TABLE client DROP siret');
        $this->addSql('ALTER TABLE client DROP pays');
        $this->addSql('ALTER TABLE client DROP email');
        $this->addSql('ALTER TABLE client DROP tva_intra');
        $this->addSql('ALTER TABLE client DROP encours_autorise');
        $this->addSql('ALTER TABLE client DROP message_alerte');
        $this->addSql('ALTER TABLE client DROP categorie_comptable');
    }
}
