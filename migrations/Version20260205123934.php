<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260205123934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_de_commande ADD is_galvanisation BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE bon_de_commande ADD is_cataphorese BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE bon_de_commande ADD stockage VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE bon_de_commande ADD type_galva VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bon_de_commande DROP is_galvanisation');
        $this->addSql('ALTER TABLE bon_de_commande DROP is_cataphorese');
        $this->addSql('ALTER TABLE bon_de_commande DROP stockage');
        $this->addSql('ALTER TABLE bon_de_commande DROP type_galva');
    }
}
