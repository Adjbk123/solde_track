<?php

namespace App\Entity;

/**
 * Classe utilitaire pour gérer les statuts des mouvements
 */
class StatutsMouvement
{
    // Statuts génériques pour tous les mouvements
    public const STATUT_ACTIF = 'actif';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_ARCHIVE = 'archive';

    public const STATUTS_GENERIQUES = [
        self::STATUT_ACTIF => 'Actif',
        self::STATUT_ANNULE => 'Annulé',
        self::STATUT_ARCHIVE => 'Archivé',
    ];

    // Statuts spécifiques aux dettes
    public const STATUT_DETTE_NON_PAYE = 'non_paye';
    public const STATUT_DETTE_PARTIELLEMENT_PAYE = 'partiellement_paye';
    public const STATUT_DETTE_PAYE = 'paye';
    public const STATUT_DETTE_EN_RETARD = 'en_retard';

    public const STATUTS_DETTE = [
        self::STATUT_DETTE_NON_PAYE => 'Non payé',
        self::STATUT_DETTE_PARTIELLEMENT_PAYE => 'Partiellement payé',
        self::STATUT_DETTE_PAYE => 'Payé',
        self::STATUT_DETTE_EN_RETARD => 'En retard',
    ];

    // Statuts spécifiques aux entrées (mappés vers les statuts génériques)
    public const STATUT_ENTREE_EN_ATTENTE = 'non_paye';
    public const STATUT_ENTREE_CONFIRME = 'paye';
    public const STATUT_ENTREE_REJETE = 'annule';

    public const STATUTS_ENTREE = [
        self::STATUT_ENTREE_EN_ATTENTE => 'En attente',
        self::STATUT_ENTREE_CONFIRME => 'Confirmé',
        self::STATUT_ENTREE_REJETE => 'Rejeté',
    ];

    // Statuts spécifiques aux dons (mappés vers les statuts génériques)
    public const STATUT_DON_PLANIFIE = 'non_paye';
    public const STATUT_DON_EFFECTUE = 'paye';
    public const STATUT_DON_ANNULE = 'annule';

    public const STATUTS_DON = [
        self::STATUT_DON_PLANIFIE => 'Planifié',
        self::STATUT_DON_EFFECTUE => 'Effectué',
        self::STATUT_DON_ANNULE => 'Annulé',
    ];

    /**
     * Retourne les statuts disponibles pour un type de mouvement
     */
    public static function getStatutsForType(string $type): array
    {
        return match($type) {
            'dette' => array_merge(self::STATUTS_GENERIQUES, self::STATUTS_DETTE),
            'entree' => array_merge(self::STATUTS_GENERIQUES, self::STATUTS_ENTREE),
            'don' => array_merge(self::STATUTS_GENERIQUES, self::STATUTS_DON),
            'sortie' => self::STATUTS_GENERIQUES,
            default => self::STATUTS_GENERIQUES,
        };
    }

    /**
     * Vérifie si un statut est valide pour un type de mouvement
     */
    public static function isStatutValide(string $type, string $statut): bool
    {
        $statutsDisponibles = self::getStatutsForType($type);
        return array_key_exists($statut, $statutsDisponibles);
    }

    /**
     * Retourne le statut par défaut pour un type de mouvement
     */
    public static function getStatutDefaut(string $type): string
    {
        return match($type) {
            'dette' => self::STATUT_DETTE_NON_PAYE,
            'entree' => self::STATUT_ENTREE_CONFIRME, // 'paye'
            'don' => self::STATUT_DON_EFFECTUE, // 'paye'
            'sortie' => self::STATUT_ACTIF,
            default => self::STATUT_ACTIF,
        };
    }
}
