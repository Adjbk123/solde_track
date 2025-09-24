<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES COMPTES
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des comptes bancaires et financiers.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Comptes',
    'description' => 'Gestion des comptes bancaires et financiers de l\'utilisateur',
    'version' => '1.0',
    'base_url' => '/api/comptes',
    
    'endpoints' => [
        [
            'method' => 'GET',
            'url' => '/api/comptes',
            'name' => 'Liste des comptes',
            'description' => 'Récupère la liste de tous les comptes de l\'utilisateur',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'page' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 1,
                    'description' => 'Numéro de page pour la pagination'
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
                        'comptes' => [
                            [
                                'id' => 'integer',
                                'nom' => 'string',
                                'type' => 'string',
                                'solde' => 'float',
                                'soldeFormatted' => 'string',
                                'devise' => 'string',
                                'dateCreation' => 'datetime',
                                'nombreMouvements' => 'integer'
                            ]
                        ],
                        'pagination' => [
                            'page' => 'integer',
                            'limit' => 'integer',
                            'total' => 'integer',
                            'pages' => 'integer'
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
            'url' => '/api/comptes',
            'name' => 'Créer un compte',
            'description' => 'Crée un nouveau compte pour l\'utilisateur',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'nom' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Nom du compte'
                ],
                'type' => [
                    'type' => 'string',
                    'required' => true,
                    'enum' => ['compte_courant', 'epargne', 'investissement', 'autre'],
                    'description' => 'Type de compte'
                ],
                'solde_initial' => [
                    'type' => 'float',
                    'required' => false,
                    'default' => 0,
                    'description' => 'Solde initial du compte'
                ],
                'devise_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'ID de la devise (utilise la devise par défaut si non fourni)'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 201,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'solde' => 'float',
                        'soldeFormatted' => 'string',
                        'devise' => 'string',
                        'dateCreation' => 'datetime'
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
            'url' => '/api/comptes/{id}',
            'name' => 'Détails d\'un compte',
            'description' => 'Récupère les détails d\'un compte spécifique',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du compte'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'solde' => 'float',
                        'soldeFormatted' => 'string',
                        'devise' => 'string',
                        'dateCreation' => 'datetime',
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
                    'message' => 'Compte non trouvé'
                ]
            ]
        ],
        
        [
            'method' => 'PUT',
            'url' => '/api/comptes/{id}',
            'name' => 'Modifier un compte',
            'description' => 'Modifie les informations d\'un compte existant',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du compte'
                ],
                'nom' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Nouveau nom du compte'
                ],
                'type' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['compte_courant', 'epargne', 'investissement', 'autre'],
                    'description' => 'Nouveau type de compte'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'nom' => 'string',
                        'type' => 'string',
                        'solde' => 'float',
                        'soldeFormatted' => 'string',
                        'devise' => 'string',
                        'dateModification' => 'datetime'
                    ]
                ],
                'error' => [
                    'status' => 404,
                    'message' => 'Compte non trouvé'
                ]
            ]
        ],
        
        [
            'method' => 'DELETE',
            'url' => '/api/comptes/{id}',
            'name' => 'Supprimer un compte',
            'description' => 'Supprime un compte (seulement si aucun mouvement associé)',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'description' => 'ID du compte'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'message' => 'Compte supprimé avec succès'
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Impossible de supprimer le compte (mouvements associés)'
                ]
            ]
        ]
    ],
    
    'types_comptes' => [
        'compte_courant' => 'Compte Courant',
        'epargne' => 'Épargne',
        'investissement' => 'Investissement',
        'autre' => 'Autre'
    ],
    
    'exemples_utilisation' => [
        'lister_comptes' => 'GET /api/comptes?page=1&limit=10',
        'creer_compte' => 'POST /api/comptes {"nom": "Mon Compte", "type": "compte_courant", "solde_initial": 1000}',
        'details_compte' => 'GET /api/comptes/1',
        'modifier_compte' => 'PUT /api/comptes/1 {"nom": "Nouveau Nom"}',
        'supprimer_compte' => 'DELETE /api/comptes/1'
    ],
    
    'codes_erreur' => [
        200 => 'Succès',
        201 => 'Créé avec succès',
        400 => 'Données invalides',
        401 => 'Non authentifié',
        404 => 'Compte non trouvé',
        500 => 'Erreur serveur'
    ]
];
