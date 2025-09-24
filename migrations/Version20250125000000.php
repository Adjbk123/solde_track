<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour remplacer 'depense' par 'sortie' dans les types de mouvement
 */
final class Version20250125000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace depense type with sortie type in mouvement table';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour le type 'depense' vers 'sortie' dans la table mouvement
        $this->addSql("UPDATE mouvement SET type = 'sortie' WHERE type = 'depense'");
        
        // Mettre à jour le discriminator 'depense' vers 'sortie' dans la table mouvement
        $this->addSql("UPDATE mouvement SET discr = 'sortie' WHERE discr = 'depense'");
    }

    public function down(Schema $schema): void
    {
        // Revenir en arrière : 'sortie' vers 'depense'
        $this->addSql("UPDATE mouvement SET type = 'depense' WHERE type = 'sortie'");
        $this->addSql("UPDATE mouvement SET discr = 'depense' WHERE discr = 'sortie'");
    }
}
