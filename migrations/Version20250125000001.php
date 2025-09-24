<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour forcer la mise à jour du discriminator depense vers sortie
 */
final class Version20250125000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Force update discriminator depense to sortie in mouvement table';
    }

    public function up(Schema $schema): void
    {
        // Forcer la mise à jour du discriminator 'depense' vers 'sortie'
        $this->addSql("UPDATE mouvement SET discr = 'sortie' WHERE discr = 'depense'");
        
        // Forcer la mise à jour du type 'depense' vers 'sortie'
        $this->addSql("UPDATE mouvement SET type = 'sortie' WHERE type = 'depense'");
        
        // Vérifier s'il y a des enregistrements à mettre à jour
        $this->addSql("SELECT COUNT(*) as count FROM mouvement WHERE discr = 'depense' OR type = 'depense'");
    }

    public function down(Schema $schema): void
    {
        // Revenir en arrière
        $this->addSql("UPDATE mouvement SET discr = 'depense' WHERE discr = 'sortie'");
        $this->addSql("UPDATE mouvement SET type = 'depense' WHERE type = 'sortie'");
    }
}
