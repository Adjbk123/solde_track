<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour nettoyer les photos base64 stockées en base de données
 */
final class Version20250125000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean base64 photos stored in database and convert them to files';
    }

    public function up(Schema $schema): void
    {
        // Cette migration sera exécutée via la commande Symfony
        // car elle nécessite des services complexes
        $this->addSql('-- Migration pour nettoyer les photos base64');
        $this->addSql('-- Exécuter: php bin/console app:clean-base64-photos');
    }

    public function down(Schema $schema): void
    {
        // Pas de rollback possible pour cette migration
        $this->addSql('-- Pas de rollback possible pour le nettoyage des photos base64');
    }
}
