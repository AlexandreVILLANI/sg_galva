<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304084550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning_ligne ADD date_validation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD commentaire_atelier TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD ordre INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD qualite_conforme BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD qualite_fiche_nc VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD qualite_operations TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD affichage_case_ce VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD affichage_case_controleur VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD traitement_surface_conforme BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD bain_zinc_conforme BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD rebuts TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD final_conforme BOOLEAN DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD final_fiche_nc VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD valide_par_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planning_ligne ADD CONSTRAINT FK_C65C611C6AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_C65C611C6AF12ED9 ON planning_ligne (valide_par_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning_ligne DROP CONSTRAINT FK_C65C611C6AF12ED9');
        $this->addSql('DROP INDEX IDX_C65C611C6AF12ED9');
        $this->addSql('ALTER TABLE planning_ligne DROP date_validation');
        $this->addSql('ALTER TABLE planning_ligne DROP commentaire_atelier');
        $this->addSql('ALTER TABLE planning_ligne DROP ordre');
        $this->addSql('ALTER TABLE planning_ligne DROP qualite_conforme');
        $this->addSql('ALTER TABLE planning_ligne DROP qualite_fiche_nc');
        $this->addSql('ALTER TABLE planning_ligne DROP qualite_operations');
        $this->addSql('ALTER TABLE planning_ligne DROP affichage_case_ce');
        $this->addSql('ALTER TABLE planning_ligne DROP affichage_case_controleur');
        $this->addSql('ALTER TABLE planning_ligne DROP traitement_surface_conforme');
        $this->addSql('ALTER TABLE planning_ligne DROP bain_zinc_conforme');
        $this->addSql('ALTER TABLE planning_ligne DROP rebuts');
        $this->addSql('ALTER TABLE planning_ligne DROP final_conforme');
        $this->addSql('ALTER TABLE planning_ligne DROP final_fiche_nc');
        $this->addSql('ALTER TABLE planning_ligne DROP valide_par_id');
    }
}
