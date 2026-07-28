<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260727114826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_livraison DROP CONSTRAINT fk_31a531a4b2dabd03');
        $this->addSql('ALTER TABLE bon_livraison ADD CONSTRAINT FK_31A531A4B2DABD03 FOREIGN KEY (bon_travail_id) REFERENCES bon_travail (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_livraison DROP CONSTRAINT FK_31A531A4B2DABD03');
        $this->addSql('ALTER TABLE bon_livraison ADD CONSTRAINT fk_31a531a4b2dabd03 FOREIGN KEY (bon_travail_id) REFERENCES bon_travail (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
