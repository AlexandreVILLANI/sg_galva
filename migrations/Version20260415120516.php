<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415120516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_livraison ADD cariste_valide BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE bon_livraison ADD signature_valide BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE bon_livraison ADD cariste_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bon_livraison ADD CONSTRAINT FK_31A531A47605FF4A FOREIGN KEY (cariste_id) REFERENCES "user" (id)');
        $this->addSql('CREATE INDEX IDX_31A531A47605FF4A ON bon_livraison (cariste_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_livraison DROP CONSTRAINT FK_31A531A47605FF4A');
        $this->addSql('DROP INDEX IDX_31A531A47605FF4A');
        $this->addSql('ALTER TABLE bon_livraison DROP cariste_valide');
        $this->addSql('ALTER TABLE bon_livraison DROP signature_valide');
        $this->addSql('ALTER TABLE bon_livraison DROP cariste_id');
    }
}
