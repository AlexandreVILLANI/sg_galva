<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203125531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD adresse VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD code_postal VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD ville VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD telephone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD fax VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP adresse');
        $this->addSql('ALTER TABLE client DROP code_postal');
        $this->addSql('ALTER TABLE client DROP ville');
        $this->addSql('ALTER TABLE client DROP telephone');
        $this->addSql('ALTER TABLE client DROP fax');
    }
}
