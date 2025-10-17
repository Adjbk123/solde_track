<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour mettre à jour les anciens types de dettes vers les nouveaux types
 */
final class Version20250127000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Mise à jour des anciens types de dettes vers les nouveaux types';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour les types de mouvements
        $this->addSql("UPDATE mouvement SET type = 'emprunt' WHERE type = 'dette_a_payer'");
        $this->addSql("UPDATE mouvement SET type = 'pret' WHERE type = 'dette_a_recevoir'");
        
        // Mettre à jour les types de dettes
        $this->addSql("UPDATE dette SET type_dette = 'emprunt' WHERE type_dette = 'dette_a_payer'");
        $this->addSql("UPDATE dette SET type_dette = 'pret' WHERE type_dette = 'dette_a_recevoir'");
    }

    public function down(Schema $schema): void
    {
        // Revenir aux anciens types
        $this->addSql("UPDATE mouvement SET type = 'dette_a_payer' WHERE type = 'emprunt'");
        $this->addSql("UPDATE mouvement SET type = 'dette_a_recevoir' WHERE type = 'pret'");
        
        $this->addSql("UPDATE dette SET type_dette = 'dette_a_payer' WHERE type_dette = 'emprunt'");
        $this->addSql("UPDATE dette SET type_dette = 'dette_a_recevoir' WHERE type_dette = 'pret'");
    }
}
