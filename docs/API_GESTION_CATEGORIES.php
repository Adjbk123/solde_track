<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES CATÉGORIES
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des catégories financières.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Catégories',
    'description' => 'Gestion des catégories financières (entrées et sorties)',
    'version' => '1.0',
    'base_url' => '/api/categories',
    
    'types_categories' => [
        'entree' => 'Entrée',
        'sortie' => 'Sortie'
    ],
    
    'clarification_types' => [
        'types_categories' => 'Types de catégories (entree/sortie)',
        'types_mouvements' => 'Types de mouvements (TYPE_ENTREE, TYPE_DEPENSE, etc.)',
        'relation' => 'Les mouvements utilisent des catégories compatibles avec leur type'
    ],
    
    'logique_categories' => [
        'entree' => [
            'description' => 'Catégories pour les entrées d\'argent',
            'mouvements_compatibles' => [
                'TYPE_ENTREE' => 'Entrées normales',
                'TYPE_DETTE_A_RECEVOIR' => 'Dettes à recevoir'
            ],
            'exemples' => [
                'Salaire',
                'Vente',
                'Prêt reçu'
            ]
        ],
        'sortie' => [
            'description' => 'Catégories pour les sorties d\'argent',
            'mouvements_compatibles' => [
                'TYPE_DEPENSE' => 'Dépenses',
                'TYPE_DETTE_A_PAYER' => 'Dettes à payer'
            ],
            'exemples' => [
                'Alimentation',
                'Transport',
                'Prêt donné'
            ]
        ]
    ],
    
    'endpoints' => [
        [
            'method' => 'GET',
            'url' => '/api/categories',
            'name' => 'Liste des catégories',
            'description' => 'Récupère la liste des catégories de l\'utilisateur',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['entree', 'sortie'],
                    'description' => 'Filtrer par type de catégorie'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'categories' => [
                            [
                                'id' => 'integer',
                                'nom' => 'string',
                                'type' => 'string',
                                'typeLabel' => 'string',
                                'dateCreation' => 'datetime',
                                'nombreMouvements' => 'integer'
                            ]
                        ]
                    ]
                ],
                'error' => [
                    'status' => 401,
                    'message' => 'Non authentifié'
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/categories',
            'name' => 'Créer une catégorie',
            'description' => 'Crée une nouvelle catégorie',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'nom' => [
                    'type' => 'string',
                    'required' => true,
                    'min_length' => 2,
                    'max_length' => 100,
                    'description' => 'Nom de la catégorie'
                ],
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['entree', 'sortie'],
                    'description' => 'Type de catégorie'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 201,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'typeLabel' => 'string',
                        'dateCreation' => 'datetime',
                        'nombreMouvements' => 0
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Données invalides ou nom déjà utilisé'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/categories/{id}',
            'name' => 'Détails d\'une catégorie',
            'description' => 'Récupère les détails d\'une catégorie spécifique',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID de la catégorie'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'typeLabel' => 'string',
                        'dateCreation' => 'datetime',
                        'nombreMouvements' => 'integer',
                        'mouvements' => [
                            [
                                'id' => 'integer',
                                'type' => 'string',
                                'montant' => 'float',
                                'description' => 'string',
                                'date' => 'datetime'
                            ]
                        ]
                    ]
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Catégorie non trouvée'
                ]
            ]
        ],
        
        [
            'method' => 'PUT',
            'url' => '/api/categories/{id}',
            'name' => 'Modifier une catégorie',
            'description' => 'Modifie une catégorie existante',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID de la catégorie'
                ],
                'nom' => [
                    'type' => 'string',
                    'required' => false,
                    'min_length' => 2,
                    'max_length' => 100,
                    'description' => 'Nouveau nom de la catégorie'
                ],
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['entree', 'sortie'],
                    'description' => 'Nouveau type de catégorie'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'typeLabel' => 'string',
                        'dateModification' => 'datetime',
                        'nombreMouvements' => 'integer'
                    ]
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Catégorie non trouvée'
                ]
            ]
        ],
        
        [
            'method' => 'DELETE',
            'url' => '/api/categories/{id}',
            'name' => 'Supprimer une catégorie',
            'description' => 'Supprime une catégorie (seulement si aucun mouvement associé)',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID de la catégorie'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'message' => 'Catégorie supprimée avec succès'
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Impossible de supprimer la catégorie (mouvements associés)'
                ]
            ]
        ]
    ],
    
    'endpoints_compatibilite' => [
        [
            'method' => 'GET',
            'url' => '/api/mouvements/debt-categories/{type}',
            'name' => 'Catégories compatibles pour dettes',
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
        ]
    ],
    
    'categories_par_defaut' => [
        'entree' => [
            'Salaire',
            'Vente',
            'Prêt reçu',
            'Don reçu',
            'Autre entrée'
        ],
        'sortie' => [
            'Alimentation',
            'Transport',
            'Logement',
            'Santé',
            'Éducation',
            'Loisirs',
            'Prêt donné',
            'Don donné',
            'Autre sortie'
        ]
    ],
    
    'exemples_utilisation' => [
        'lister_categories' => 'GET /api/categories',
        'lister_categories_entree' => 'GET /api/categories?type=entree',
        'lister_categories_sortie' => 'GET /api/categories?type=sortie',
        'creer_categorie' => 'POST /api/categories {"nom": "Salaire", "type": "entree"}',
        'details_categorie' => 'GET /api/categories/1',
        'modifier_categorie' => 'PUT /api/categories/1 {"nom": "Nouveau Nom"}',
        'supprimer_categorie' => 'DELETE /api/categories/1',
        'categories_dettes' => 'GET /api/mouvements/debt-categories/dette_a_payer'
    ],
    
    'codes_erreur' => [
        200 => 'Succès',
        201 => 'Créé avec succès',
        400 => 'Données invalides',
        401 => 'Non authentifié',
        404 => 'Catégorie non trouvée',
        500 => 'Erreur serveur'
    ]
];
