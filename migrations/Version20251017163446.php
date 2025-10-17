<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017163446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EC18272');
        $this->addSql('CREATE TABLE depense_prevue (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, budget_prevu NUMERIC(10, 2) DEFAULT NULL, type_budget VARCHAR(50) DEFAULT NULL, montant_depense NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, date_debut_prevue DATETIME DEFAULT NULL, date_fin_prevue DATETIME DEFAULT NULL, date_debut_reelle DATETIME DEFAULT NULL, date_fin_reelle DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, INDEX IDX_A26DE2BDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement_dette (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, dette_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, montant_principal NUMERIC(10, 2) DEFAULT NULL, montant_interet NUMERIC(10, 2) DEFAULT NULL, date_paiement DATE NOT NULL, type_paiement VARCHAR(50) NOT NULL, statut_paiement VARCHAR(50) NOT NULL, commentaire LONGTEXT DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, date_modification DATETIME NOT NULL, INDEX IDX_2B832D17FB88E14F (utilisateur_id), INDEX IDX_2B832D17E11400A1 (dette_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE depense_prevue ADD CONSTRAINT FK_A26DE2BDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiement_dette ADD CONSTRAINT FK_2B832D17FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE paiement_dette ADD CONSTRAINT FK_2B832D17E11400A1 FOREIGN KEY (dette_id) REFERENCES dette (id)');
        $this->addSql('ALTER TABLE projet DROP FOREIGN KEY FK_50159CA9A76ED395');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1EECD1C222');
        $this->addSql('DROP TABLE projet');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('ALTER TABLE dette ADD montant_principal NUMERIC(10, 2) NOT NULL, ADD date_dernier_paiement DATE DEFAULT NULL, ADD type_dette VARCHAR(50) NOT NULL, ADD statut_dette VARCHAR(50) NOT NULL, ADD type_calcul_interet VARCHAR(50) DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL, ADD notifications_activees TINYINT(1) NOT NULL, ADD jours_alerte_echeance INT DEFAULT NULL, CHANGE taux taux_interet NUMERIC(5, 2) DEFAULT NULL, CHANGE echeance date_echeance DATE DEFAULT NULL, CHANGE montant_rest montant_interets NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_5B51FC3EC18272 ON mouvement');
        $this->addSql('ALTER TABLE mouvement CHANGE projet_id depense_prevue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EF48C245A FOREIGN KEY (depense_prevue_id) REFERENCES depense_prevue (id)');
        $this->addSql('CREATE INDEX IDX_5B51FC3EF48C245A ON mouvement (depense_prevue_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mouvement DROP FOREIGN KEY FK_5B51FC3EF48C245A');
        $this->addSql('CREATE TABLE projet (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, nom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, budget_prevu NUMERIC(10, 2) DEFAULT NULL, date_creation DATETIME NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_50159CA9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, mouvement_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, date DATETIME NOT NULL, commentaire LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, statut VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B1DC7A1EECD1C222 (mouvement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE projet ADD CONSTRAINT FK_50159CA9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1EECD1C222 FOREIGN KEY (mouvement_id) REFERENCES mouvement (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE depense_prevue DROP FOREIGN KEY FK_A26DE2BDA76ED395');
        $this->addSql('ALTER TABLE paiement_dette DROP FOREIGN KEY FK_2B832D17FB88E14F');
        $this->addSql('ALTER TABLE paiement_dette DROP FOREIGN KEY FK_2B832D17E11400A1');
        $this->addSql('DROP TABLE depense_prevue');
        $this->addSql('DROP TABLE paiement_dette');
        $this->addSql('ALTER TABLE dette ADD echeance DATE DEFAULT NULL, DROP montant_principal, DROP date_echeance, DROP date_dernier_paiement, DROP type_dette, DROP statut_dette, DROP type_calcul_interet, DROP notes, DROP notifications_activees, DROP jours_alerte_echeance, CHANGE taux_interet taux NUMERIC(5, 2) DEFAULT NULL, CHANGE montant_interets montant_rest NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_5B51FC3EF48C245A ON mouvement');
        $this->addSql('ALTER TABLE mouvement CHANGE depense_prevue_id projet_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE mouvement ADD CONSTRAINT FK_5B51FC3EC18272 FOREIGN KEY (projet_id) REFERENCES projet (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5B51FC3EC18272 ON mouvement (projet_id)');
    }
}
