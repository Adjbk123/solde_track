<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES DETTES ET PRÊTS
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des dettes et prêts.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Dettes et Prêts',
    'description' => 'Gestion des dettes à payer, dettes à recevoir et prêts',
    'version' => '1.0',
    'base_url' => '/api/mouvements',
    
    'types_dettes' => [
        'dette_a_payer' => 'Dette à payer',
        'dette_a_recevoir' => 'Dette à recevoir'
    ],
    
    'types_categories' => [
        'entree' => 'Entrée (pour dettes à recevoir)',
        'sortie' => 'Sortie (pour dettes à payer)'
    ],
    
    'logique_dettes' => [
        'dette_a_recevoir' => [
            'categorie_type' => 'entree',
            'logique' => 'Argent qu\'on va recevoir',
            'impact_solde' => 'positif'
        ],
        'dette_a_payer' => [
            'categorie_type' => 'sortie',
            'logique' => 'Argent qu\'on va payer',
            'impact_solde' => 'negatif'
        ]
    ],
    
    'endpoints' => [
        [
            'method' => 'GET',
            'url' => '/api/mouvements/debt-categories/{type}',
            'name' => 'Catégories pour dettes',
            'description' => 'Récupère les catégories compatibles pour un type de dette',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['dette_a_payer', 'dette_a_recevoir'],
                    'description' => 'Type de dette'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'movementType' => 'string',
                        'categories' => [
                            [
                                'id' => 'integer',
                                'nom' => 'string',
                                'type' => 'string',
                                'typeLabel' => 'string',
                                'nombreMouvements' => 'integer'
                            ]
                        ],
                        'suggestedCategories' => [
                            'string'
                        ]
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Type de mouvement de dette invalide'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/mouvements/debt-balance',
            'name' => 'Solde des dettes',
            'description' => 'Récupère le solde des dettes de l\'utilisateur',
            'authentication' => 'Bearer Token requis',
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'dettes_a_payer' => [
                            'total' => 'float',
                            'totalFormatted' => 'string',
                            'nombre' => 'integer'
                        ],
                        'dettes_a_recevoir' => [
                            'total' => 'float',
                            'totalFormatted' => 'string',
                            'nombre' => 'integer'
                        ],
                        'solde_dettes' => 'float',
                        'solde_dettes_formatted' => 'string',
                        'net_positive' => 'boolean'
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/mouvements',
            'name' => 'Créer une dette',
            'description' => 'Crée une nouvelle dette (à payer ou à recevoir)',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['dette_a_payer', 'dette_a_recevoir'],
                    'description' => 'Type de dette'
                ],
                'montant' => [
                    'type' => 'float',
                    'required' => true,
                    'min' => 0.01,
                    'description' => 'Montant de la dette'
                ],
                'description' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Description de la dette'
                ],
                'date' => [
                    'type' => 'string',
                    'required' => false,
                    'format' => 'YYYY-MM-DD',
                    'default' => 'Date actuelle',
                    'description' => 'Date de la dette'
                ],
                'categorie_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID de la catégorie (doit être compatible avec le type)'
                ],
                'compte_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du compte'
                ],
                'contact_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du contact (créancier ou débiteur)'
                ],
                'date_echeance' => [
                    'type' => 'string',
                    'required' => false,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date d\'échéance de la dette'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 201,
                    'data' => [
                        'id' => 'integer',
                        'type' => 'string',
                        'montant' => 'float',
                        'montantFormatted' => 'string',
                        'description' => 'string',
                        'date' => 'datetime',
                        'dateEcheance' => 'datetime',
                        'statut' => 'string',
                        'categorie' => 'object',
                        'compte' => 'object',
                        'contact' => 'object'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Données invalides ou catégorie incompatible'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/mouvements',
            'name' => 'Liste des dettes',
            'description' => 'Récupère la liste des dettes avec filtres',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['dette_a_payer', 'dette_a_recevoir'],
                    'description' => 'Filtrer par type de dette'
                ],
                'contact_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Filtrer par contact'
                ],
                'statut' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['en_attente', 'confirme', 'annule'],
                    'description' => 'Filtrer par statut'
                ],
                'page' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 1,
                    'description' => 'Numéro de page'
                ],
                'limit' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 20,
                    'description' => 'Nombre d\'éléments par page'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'mouvements' => [
                            [
                                'id' => 'integer',
                                'type' => 'string',
                                'montant' => 'float',
                                'montantFormatted' => 'string',
                                'description' => 'string',
                                'date' => 'datetime',
                                'dateEcheance' => 'datetime',
                                'statut' => 'string',
                                'categorie' => 'object',
                                'compte' => 'object',
                                'contact' => 'object'
                            ]
                        ],
                        'pagination' => [
                            'page' => 'integer',
                            'limit' => 'integer',
                            'total' => 'integer',
                            'pages' => 'integer'
                        ]
                    ]
                ]
            ]
        ]
    ],
    
    'endpoints_paiements' => [
        [
            'method' => 'POST',
            'url' => '/api/paiements',
            'name' => 'Enregistrer un paiement',
            'description' => 'Enregistre un paiement pour une dette',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'mouvement_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du mouvement de dette'
                ],
                'montant' => [
                    'type' => 'float',
                    'required' => true,
                    'min' => 0.01,
                    'description' => 'Montant du paiement'
                ],
                'date' => [
                    'type' => 'string',
                    'required' => false,
                    'format' => 'YYYY-MM-DD',
                    'default' => 'Date actuelle',
                    'description' => 'Date du paiement'
                ],
                'description' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Description du paiement'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 201,
                    'data' => [
                        'id' => 'integer',
                        'montant' => 'float',
                        'montantFormatted' => 'string',
                        'date' => 'datetime',
                        'description' => 'string',
                        'mouvement' => 'object',
                        'solde_restant' => 'float',
                        'solde_restant_formatted' => 'string'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Montant supérieur au solde restant'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/paiements',
            'name' => 'Liste des paiements',
            'description' => 'Récupère la liste des paiements',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'mouvement_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Filtrer par mouvement'
                ],
                'page' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 1,
                    'description' => 'Numéro de page'
                ],
                'limit' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 20,
                    'description' => 'Nombre d\'éléments par page'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'paiements' => [
                            [
                                'id' => 'integer',
                                'montant' => 'float',
                                'montantFormatted' => 'string',
                                'date' => 'datetime',
                                'description' => 'string',
                                'mouvement' => 'object'
                            ]
                        ],
                        'pagination' => [
                            'page' => 'integer',
                            'limit' => 'integer',
                            'total' => 'integer',
                            'pages' => 'integer'
                        ]
                    ]
                ]
            ]
        ]
    ],
    
    'exemples_utilisation' => [
        'creer_dette_a_payer' => 'POST /api/mouvements {"type": "dette_a_payer", "montant": 500, "description": "Prêt banque", "categorie_id": 3, "compte_id": 1, "contact_id": 1}',
        'creer_dette_a_recevoir' => 'POST /api/mouvements {"type": "dette_a_recevoir", "montant": 300, "description": "Prêt à un ami", "categorie_id": 2, "compte_id": 1, "contact_id": 2}',
        'lister_dettes' => 'GET /api/mouvements?type=dette_a_payer',
        'solde_dettes' => 'GET /api/mouvements/debt-balance',
        'enregistrer_paiement' => 'POST /api/paiements {"mouvement_id": 1, "montant": 100, "description": "Premier paiement"}',
        'lister_paiements' => 'GET /api/paiements?mouvement_id=1'
    ],
    
    'codes_erreur' => [
        200 => 'Succès',
        201 => 'Créé avec succès',
        400 => 'Données invalides',
        401 => 'Non authentifié',
        404 => 'Mouvement non trouvé',
        500 => 'Erreur serveur'
    ]
];
