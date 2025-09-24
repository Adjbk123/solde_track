<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250923231911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactorisation des catégories : simplification en ENTRÉE/SORTIE uniquement';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour les types de catégories existantes
        $this->addSql("
            UPDATE categorie 
            SET type = CASE 
                WHEN type = 'depense' THEN 'sortie'
                WHEN type = 'entree' THEN 'entree'
                WHEN type = 'dette' THEN 'sortie'
                WHEN type = 'don' THEN 'sortie'
                ELSE type
            END
        ");
    }

    public function down(Schema $schema): void
    {
        // Revenir aux anciens types (approximation)
        $this->addSql("
            UPDATE categorie 
            SET type = CASE 
                WHEN type = 'sortie' AND nom LIKE '%Don%' THEN 'don'
                WHEN type = 'sortie' AND nom LIKE '%Dette%' THEN 'dette'
                WHEN type = 'sortie' THEN 'depense'
                WHEN type = 'entree' THEN 'entree'
                ELSE type
            END
        ");
    }
}
