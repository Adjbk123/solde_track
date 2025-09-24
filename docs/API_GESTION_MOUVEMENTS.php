<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES MOUVEMENTS
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des mouvements financiers.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Mouvements',
    'description' => 'Gestion des mouvements financiers (entrées, dépenses, dettes, dons)',
    'version' => '1.0',
    'base_url' => '/api/mouvements',
    
    'types_mouvements' => [
        'entree' => 'Entrée',
        'depense' => 'Dépense',
        'dette_a_payer' => 'Dette à payer',
        'dette_a_recevoir' => 'Dette à recevoir',
        'don' => 'Don'
    ],
    
    'types_categories' => [
        'entree' => 'Entrée',
        'sortie' => 'Sortie'
    ],
    
    'endpoints' => [
        [
            'method' => 'GET',
            'url' => '/api/mouvements',
            'name' => 'Liste des mouvements',
            'description' => 'Récupère la liste des mouvements avec filtres',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['entree', 'depense', 'dette_a_payer', 'dette_a_recevoir', 'don'],
                    'description' => 'Filtrer par type de mouvement'
                ],
                'projet_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Filtrer par projet'
                ],
                'categorie_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Filtrer par catégorie'
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
                                'statut' => 'string',
                                'categorie' => [
                                    'id' => 'integer',
                                    'nom' => 'string',
                                    'type' => 'string'
                                ],
                                'compte' => [
                                    'id' => 'integer',
                                    'nom' => 'string'
                                ],
                                'projet' => [
                                    'id' => 'integer',
                                    'nom' => 'string'
                                ]
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
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/mouvements',
            'name' => 'Créer un mouvement',
            'description' => 'Crée un nouveau mouvement financier',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['entree', 'depense', 'dette_a_payer', 'dette_a_recevoir', 'don'],
                    'description' => 'Type de mouvement'
                ],
                'montant' => [
                    'type' => 'float',
                    'required' => true,
                    'min' => 0.01,
                    'description' => 'Montant du mouvement'
                ],
                'description' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Description du mouvement'
                ],
                'date' => [
                    'type' => 'string',
                    'required' => false,
                    'format' => 'YYYY-MM-DD',
                    'default' => 'Date actuelle',
                    'description' => 'Date du mouvement'
                ],
                'categorie_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID de la catégorie'
                ],
                'compte_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du compte'
                ],
                'projet_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID du projet (optionnel)'
                ],
                'contact_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID du contact (pour dettes)'
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
                        'statut' => 'string',
                        'categorie' => 'object',
                        'compte' => 'object',
                        'projet' => 'object',
                        'contact' => 'object'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Données invalides'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/mouvements/{id}',
            'name' => 'Détails d\'un mouvement',
            'description' => 'Récupère les détails d\'un mouvement spécifique',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du mouvement'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'type' => 'string',
                        'montant' => 'float',
                        'montantFormatted' => 'string',
                        'description' => 'string',
                        'date' => 'datetime',
                        'statut' => 'string',
                        'categorie' => 'object',
                        'compte' => 'object',
                        'projet' => 'object',
                        'contact' => 'object',
                        'dateCreation' => 'datetime',
                        'dateModification' => 'datetime'
                    ]
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Mouvement non trouvé'
                ]
            ]
        ],
        
        [
            'method' => 'PUT',
            'url' => '/api/mouvements/{id}',
            'name' => 'Modifier un mouvement',
            'description' => 'Modifie un mouvement existant',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du mouvement'
                ],
                'montant' => [
                    'type' => 'float',
                    'required' => false,
                    'min' => 0.01,
                    'description' => 'Nouveau montant'
                ],
                'description' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Nouvelle description'
                ],
                'date' => [
                    'type' => 'string',
                    'required' => false,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Nouvelle date'
                ],
                'categorie_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Nouvelle catégorie'
                ],
                'statut' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['en_attente', 'confirme', 'annule'],
                    'description' => 'Nouveau statut'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'type' => 'string',
                        'montant' => 'float',
                        'montantFormatted' => 'string',
                        'description' => 'string',
                        'date' => 'datetime',
                        'statut' => 'string',
                        'dateModification' => 'datetime'
                    ]
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Mouvement non trouvé'
                ]
            ]
        ],
        
        [
            'method' => 'DELETE',
            'url' => '/api/mouvements/{id}',
            'name' => 'Supprimer un mouvement',
            'description' => 'Supprime un mouvement',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du mouvement'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'message' => 'Mouvement supprimé avec succès'
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Mouvement non trouvé'
                ]
            ]
        ]
    ],
    
    'endpoints_dettes' => [
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
        ]
    ],
    
    'exemples_utilisation' => [
        'lister_mouvements' => 'GET /api/mouvements?type=depense&page=1&limit=10',
        'creer_entree' => 'POST /api/mouvements {"type": "entree", "montant": 1000, "description": "Salaire", "categorie_id": 1, "compte_id": 1}',
        'creer_depense' => 'POST /api/mouvements {"type": "depense", "montant": 50, "description": "Courses", "categorie_id": 2, "compte_id": 1}',
        'creer_dette' => 'POST /api/mouvements {"type": "dette_a_payer", "montant": 200, "description": "Prêt", "categorie_id": 3, "compte_id": 1, "contact_id": 1}',
        'modifier_mouvement' => 'PUT /api/mouvements/1 {"montant": 150, "description": "Nouvelle description"}',
        'supprimer_mouvement' => 'DELETE /api/mouvements/1'
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
