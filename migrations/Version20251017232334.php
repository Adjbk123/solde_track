<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017232334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_497DD634A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE compte (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, devise_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(500) DEFAULT NULL, solde_initial NUMERIC(10, 2) NOT NULL, solde_actuel NUMERIC(10, 2) NOT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, actif TINYINT(1) NOT NULL, type VARCHAR(50) DEFAULT NULL, numero VARCHAR(100) DEFAULT NULL, institution VARCHAR(255) DEFAULT NULL, INDEX IDX_CFF65260A76ED395 (user_id), INDEX IDX_CFF65260F4445056 (devise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nom VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, email VARCHAR(255) DEFAULT NULL, source VARCHAR(50) NOT NULL, date_creation DATETIME NOT NULL, INDEX IDX_4C62E638A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE depense (id INT NOT NULL, lieu VARCHAR(255) DEFAULT NULL, methode_paiement VARCHAR(50) DEFAULT NULL, recu VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE depense_prevue (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, budget_prevu NUMERIC(10, 2) DEFAULT NULL, type_budget VARCHAR(50) DEFAULT NULL, montant_depense NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, date_debut_prevue DATETIME DEFAULT NULL, date_fin_prevue DATETIME DEFAULT NULL, date_debut_reelle DATETIME DEFAULT NULL, date_fin_reelle DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_A26DE2BDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dette (id INT NOT NULL, montant_principal NUMERIC(10, 2) NOT NULL, taux_interet NUMERIC(5, 2) DEFAULT NULL, date_echeance DATE DEFAULT NULL, date_dernier_paiement DATE DEFAULT NULL, type_dette VARCHAR(50) NOT NULL, statut_dette VARCHAR(50) NOT NULL, type_calcul_interet VARCHAR(50) DEFAULT NULL, montant_interets NUMERIC(10, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, notifications_activees TINYINT(1) NOT NULL, jours_alerte_echeance INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE devise (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(3) NOT NULL, nom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_43EDA4DF77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE don (id INT NOT NULL, occasion VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE entree (id INT NOT NULL, source VARCHAR(255) DEFAULT NULL, methode VARCHAR(50) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mouvement (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, categorie_id INT NOT NULL, depense_prevue_id INT DEFAULT NULL, contact_id INT DEFAULT NULL, compte_id INT NOT NULL, type VARCHAR(50) NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, montant_effectif NUMERIC(10, 2) DEFAULT NULL, statut VARCHAR(50) NOT NULL, date DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, description LONGTEXT DEFAULT NULL, discr VARCHAR(255) NOT NULL, INDEX IDX_5B51FC3EA76ED395 (user_id), INDEX IDX_5B51FC3EBCF5E72D (categorie_id), INDEX IDX_5B51FC3EF48C245A (depense_prevue_id), INDEX IDX_5B51FC3EE7A1254A (contact_id), INDEX IDX_5B51FC3EF2C56620 (compte_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement_dette (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, dette_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, montant_principal NUMERIC(10, 2) DEFAULT NULL, montant_interet NUMERIC(10, 2) DEFAULT NULL, date_paiement DATE NOT NULL, type_paiement VARCHAR(50) NOT NULL, statut_paiement VARCHAR(50) NOT NULL, commentaire LONGTEXT DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME NOT NULL, INDEX IDX_2B832D17FB88E14F (utilisateur_id), INDEX IDX_2B832D17E11400A1 (dette_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transfert (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, compte_source_id INT NOT NULL, compte_destination_id INT NOT NULL, devise_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, date DATETIME NOT NULL, note LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, annule TINYINT(1) NOT NULL, INDEX IDX_1E4EACBBA76ED395 (user_id), INDEX IDX_1E4EACBB56B22253 (compte_source_id), INDEX IDX_1E4EACBB4105B733 (compte_destination_id), INDEX IDX_1E4EACBBF4445056 (devise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, devise_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenoms VARCHAR(100) NOT NULL, photo VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, date_creation DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, fcm_token VARCHAR(255) DEFAULT NULL, INDEX IDX_8D93D649F4445056 (devise_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categorie ADD CONSTRAINT FK_497DD634A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE compte ADD CONSTRAINT FK_CFF65260A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE compte ADD CONSTRAINT FK_CFF65260F4445056 FOREIGN KEY (devise_id) REFERENCES devise (id)');
        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E638A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE depense ADD CONSTRAINT FK_34059757BF396750 FOREIGN KEY (id) REFERENCES mouvement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE depense_prevue ADD CONSTRAINT FK_A26DE2BDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE dette ADD CONSTRAINT FK_831BC808BF396750 FOREIGN KEY (id) REFERENCES mouvement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE don ADD CONSTRAINT FK_F8F081D9BF396750 FOREIGN KEY (id) REFERENCES mouvement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE entree ADD CONSTRAINT FK_598377A6BF396750 FOREIGN KEY (id) REFERENCES mouvement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EF48C245A FOREIGN KEY (depense_prevue_id) REFERENCES depense_prevue (id)');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EF2C56620 FOREIGN KEY (compte_id) REFERENCES compte (id)');
        $this->addSql('ALTER TABLE paiement_dette ADD CONSTRAINT FK_2B832D17FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiement_dette ADD CONSTRAINT FK_2B832D17E11400A1 FOREIGN KEY (dette_id) REFERENCES dette (id)');
        $this->addSql('ALTER TABLE transfert ADD CONSTRAINT FK_1E4EACBBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE transfert ADD CONSTRAINT FK_1E4EACBB56B22253 FOREIGN KEY (compte_source_id) REFERENCES compte (id)');
        $this->addSql('ALTER TABLE transfert ADD CONSTRAINT FK_1E4EACBB4105B733 FOREIGN KEY (compte_destination_id) REFERENCES compte (id)');
        $this->addSql('ALTER TABLE transfert ADD CONSTRAINT FK_1E4EACBBF4445056 FOREIGN KEY (devise_id) REFERENCES devise (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649F4445056 FOREIGN KEY (devise_id) REFERENCES devise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie DROP FOREIGN KEY FK_497DD634A76ED395');
        $this->addSql('ALTER TABLE compte DROP FOREIGN KEY FK_CFF65260A76ED395');
        $this->addSql('ALTER TABLE compte DROP FOREIGN KEY FK_CFF65260F4445056');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E638A76ED395');
        $this->addSql('ALTER TABLE depense DROP FOREIGN KEY FK_34059757BF396750');
        $this->addSql('ALTER TABLE depense_prevue DROP FOREIGN KEY FK_A26DE2BDA76ED395');
        $this->addSql('ALTER TABLE dette DROP FOREIGN KEY FK_831BC808BF396750');
        $this->addSql('ALTER TABLE don DROP FOREIGN KEY FK_F8F081D9BF396750');
        $this->addSql('ALTER TABLE entree DROP FOREIGN KEY FK_598377A6BF396750');
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EA76ED395');
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EBCF5E72D');
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EF48C245A');
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EE7A1254A');
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EF2C56620');
        $this->addSql('ALTER TABLE paiement_dette DROP FOREIGN KEY FK_2B832D17FB88E14F');
        $this->addSql('ALTER TABLE paiement_dette DROP FOREIGN KEY FK_2B832D17E11400A1');
        $this->addSql('ALTER TABLE transfert DROP FOREIGN KEY FK_1E4EACBBA76ED395');
        $this->addSql('ALTER TABLE transfert DROP FOREIGN KEY FK_1E4EACBB56B22253');
        $this->addSql('ALTER TABLE transfert DROP FOREIGN KEY FK_1E4EACBB4105B733');
        $this->addSql('ALTER TABLE transfert DROP FOREIGN KEY FK_1E4EACBBF4445056');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649F4445056');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE compte');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE depense');
        $this->addSql('DROP TABLE depense_prevue');
        $this->addSql('DROP TABLE dette');
        $this->addSql('DROP TABLE devise');
        $this->addSql('DROP TABLE don');
        $this->addSql('DROP TABLE entree');
        $this->addSql('DROP TABLE mouvement');
        $this->addSql('DROP TABLE paiement_dette');
        $this->addSql('DROP TABLE transfert');
        $this->addSql('DROP TABLE `user`');
    }
}
