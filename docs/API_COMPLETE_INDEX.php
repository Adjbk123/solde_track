<?php

/**
 * ========================================
 * API DOCUMENTATION - INDEX COMPLET
 * ========================================
 * 
 * Ce fichier contient l'index complet de toute la documentation
 * de l'API Solde Track, organisée par modules.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'api' => [
        'nom' => 'Solde Track API',
        'version' => '1.0',
        'description' => 'API complète pour la gestion financière personnelle',
        'base_url' => 'https://api.solde-track.com',
        'authentication' => 'Bearer Token (JWT)',
        'format' => 'JSON'
    ],
    
    'modules' => [
        'gestion_comptes' => [
            'nom' => 'Gestion des Comptes',
            'description' => 'Gestion des comptes bancaires et financiers',
            'fichier' => 'API_GESTION_COMPTES.php',
            'endpoints' => [
                'GET /api/comptes' => 'Liste des comptes',
                'POST /api/comptes' => 'Créer un compte',
                'GET /api/comptes/{id}' => 'Détails d\'un compte',
                'PUT /api/comptes/{id}' => 'Modifier un compte',
                'DELETE /api/comptes/{id}' => 'Supprimer un compte'
            ]
        ],
        
        'gestion_users' => [
            'nom' => 'Gestion des Utilisateurs',
            'description' => 'Authentification et gestion des profils utilisateurs',
            'fichier' => 'API_GESTION_USERS.php',
            'endpoints' => [
                'POST /api/auth/register' => 'Inscription',
                'POST /api/auth/login' => 'Connexion',
                'POST /api/auth/logout' => 'Déconnexion',
                'GET /api/auth/me' => 'Profil utilisateur',
                'PUT /api/auth/me' => 'Modifier le profil',
                'POST /api/auth/change-password' => 'Changer le mot de passe',
                'GET /api/profil' => 'Profil complet',
                'POST /api/profil/photo' => 'Upload photo de profil'
            ]
        ],
        
        'gestion_mouvements' => [
            'nom' => 'Gestion des Mouvements',
            'description' => 'Gestion des mouvements financiers (entrées, dépenses, dettes, dons)',
            'fichier' => 'API_GESTION_MOUVEMENTS.php',
            'endpoints' => [
                'GET /api/mouvements' => 'Liste des mouvements',
                'POST /api/mouvements' => 'Créer un mouvement',
                'GET /api/mouvements/{id}' => 'Détails d\'un mouvement',
                'PUT /api/mouvements/{id}' => 'Modifier un mouvement',
                'DELETE /api/mouvements/{id}' => 'Supprimer un mouvement',
                'GET /api/mouvements/debt-categories/{type}' => 'Catégories pour dettes',
                'GET /api/mouvements/debt-balance' => 'Solde des dettes'
            ]
        ],
        
        'gestion_dettes' => [
            'nom' => 'Gestion des Dettes et Prêts',
            'description' => 'Gestion des dettes à payer, dettes à recevoir et prêts',
            'fichier' => 'API_GESTION_DETTES.php',
            'endpoints' => [
                'GET /api/mouvements/debt-categories/{type}' => 'Catégories pour dettes',
                'GET /api/mouvements/debt-balance' => 'Solde des dettes',
                'POST /api/mouvements' => 'Créer une dette',
                'GET /api/mouvements' => 'Liste des dettes',
                'POST /api/paiements' => 'Enregistrer un paiement',
                'GET /api/paiements' => 'Liste des paiements'
            ]
        ],
        
        'gestion_statistiques' => [
            'nom' => 'Gestion des Statistiques',
            'description' => 'Statistiques financières, analyses et rapports',
            'fichier' => 'API_GESTION_STATISTIQUES.php',
            'endpoints' => [
                'GET /api/statistiques/resume' => 'Résumé des statistiques',
                'GET /api/statistiques/entrees' => 'Entrées par période',
                'GET /api/statistiques/sorties' => 'Sorties par période',
                'GET /api/statistiques/entrees-sorties' => 'Entrées et sorties par période',
                'GET /api/statistiques/tendances' => 'Tendances',
                'GET /api/statistiques/comparaison-periodes' => 'Comparaison de périodes',
                'GET /api/dashboard/statistiques' => 'Statistiques dashboard'
            ]
        ],
        
        'gestion_categories' => [
            'nom' => 'Gestion des Catégories',
            'description' => 'Gestion des catégories financières (entrées et sorties)',
            'fichier' => 'API_GESTION_CATEGORIES.php',
            'endpoints' => [
                'GET /api/categories' => 'Liste des catégories',
                'POST /api/categories' => 'Créer une catégorie',
                'GET /api/categories/{id}' => 'Détails d\'une catégorie',
                'PUT /api/categories/{id}' => 'Modifier une catégorie',
                'DELETE /api/categories/{id}' => 'Supprimer une catégorie'
            ]
        ]
    ],
    
    'structure_globale' => [
        'types_categories' => [
            'entree' => 'Entrée',
            'sortie' => 'Sortie'
        ],
        'types_mouvements' => [
            'entree' => 'Entrée',
            'depense' => 'Dépense',
            'dette_a_payer' => 'Dette à payer',
            'dette_a_recevoir' => 'Dette à recevoir',
            'don' => 'Don'
        ],
        'logique_dettes' => [
            'dette_a_recevoir' => 'Catégorie ENTRÉE (argent qu\'on va recevoir)',
            'dette_a_payer' => 'Catégorie SORTIE (argent qu\'on va payer)'
        ]
    ],
    
    'endpoints_par_module' => [
        'comptes' => [
            'base_url' => '/api/comptes',
            'total_endpoints' => 5
        ],
        'auth' => [
            'base_url' => '/api/auth',
            'total_endpoints' => 6
        ],
        'mouvements' => [
            'base_url' => '/api/mouvements',
            'total_endpoints' => 7
        ],
        'statistiques' => [
            'base_url' => '/api/statistiques',
            'total_endpoints' => 7
        ],
        'categories' => [
            'base_url' => '/api/categories',
            'total_endpoints' => 5
        ],
        'paiements' => [
            'base_url' => '/api/paiements',
            'total_endpoints' => 2
        ]
    ],
    
    'statistiques_api' => [
        'total_modules' => 6,
        'total_endpoints' => 32,
        'types_categories' => 2,
        'types_mouvements' => 5,
        'version' => '1.0'
    ],
    
    'utilisation' => [
        'authentication' => 'Tous les endpoints (sauf auth/register et auth/login) nécessitent un Bearer Token',
        'format_reponse' => 'JSON',
        'codes_erreur' => [
            200 => 'Succès',
            201 => 'Créé avec succès',
            400 => 'Données invalides',
            401 => 'Non authentifié',
            404 => 'Ressource non trouvée',
            500 => 'Erreur serveur'
        ],
        'pagination' => 'Support de la pagination avec page et limit',
        'filtres' => 'Support des filtres par type, date, statut, etc.',
        'validation' => 'Validation complète des données d\'entrée'
    ]
];
