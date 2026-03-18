<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260318082524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client ADD portefeuille_bl_fa VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD payeur VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD assurance_credit NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE client DROP portefeuille_bl_fa');
        $this->addSql('ALTER TABLE client DROP payeur');
        $this->addSql('ALTER TABLE client DROP assurance_credit');
    }
}
