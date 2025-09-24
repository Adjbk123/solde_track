<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES STATISTIQUES
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des statistiques et analyses financières.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Statistiques',
    'description' => 'Statistiques financières, analyses et rapports',
    'version' => '1.0',
    'base_url' => '/api/statistiques',
    
    'logique_statistiques' => [
        'entrees' => [
            'description' => 'Toutes les entrées d\'argent',
            'types_mouvements' => [
                'TYPE_ENTREE' => 'Entrées normales',
                'TYPE_DETTE_A_RECEVOIR' => 'Dettes à recevoir'
            ],
            'categorie_type' => 'entree'
        ],
        'sorties' => [
            'description' => 'Toutes les sorties d\'argent',
            'types_mouvements' => [
                'TYPE_DEPENSE' => 'Dépenses',
                'TYPE_DETTE_A_PAYER' => 'Dettes à payer'
            ],
            'categorie_type' => 'sortie'
        ]
    ],
    
    'endpoints' => [
        [
            'method' => 'GET',
            'url' => '/api/statistiques/resume',
            'name' => 'Résumé des statistiques',
            'description' => 'Récupère un résumé des statistiques financières',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'periode' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['semaine', 'mois', 'trimestre', 'annee'],
                    'default' => 'semaine',
                    'description' => 'Période d\'analyse'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'periode' => 'string',
                        'entrees' => [
                            'montant' => 'string',
                            'montantFormatted' => 'string',
                            'variation' => 'float',
                            'variationFormatted' => 'string'
                        ],
                        'sorties' => [
                            'montant' => 'string',
                            'montantFormatted' => 'string',
                            'variation' => 'float',
                            'variationFormatted' => 'string'
                        ],
                        'soldeTotal' => [
                            'montant' => 'string',
                            'montantFormatted' => 'string'
                        ],
                        'detail' => [
                            'entrees' => [
                                'entrees_normales' => 'string',
                                'dettes_a_recevoir' => 'string'
                            ],
                            'sorties' => [
                                'depenses' => 'string',
                                'dettes_a_payer' => 'string'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/statistiques/entrees',
            'name' => 'Entrées par période',
            'description' => 'Récupère les entrées pour une période donnée',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'debut' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de début'
                ],
                'fin' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de fin'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'entrees' => [
                            'periode' => [
                                'debut' => 'string',
                                'fin' => 'string'
                            ],
                            'total' => 'float',
                            'detail' => [
                                'entrees_normales' => 'float',
                                'dettes_a_recevoir' => 'float'
                            ],
                            'formatted' => [
                                'total' => 'string',
                                'entrees_normales' => 'string',
                                'dettes_a_recevoir' => 'string'
                            ]
                        ],
                        'devise' => 'string'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Paramètres debut et fin requis'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/statistiques/sorties',
            'name' => 'Sorties par période',
            'description' => 'Récupère les sorties pour une période donnée',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'debut' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de début'
                ],
                'fin' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de fin'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'sorties' => [
                            'periode' => [
                                'debut' => 'string',
                                'fin' => 'string'
                            ],
                            'total' => 'float',
                            'detail' => [
                                'depenses' => 'float',
                                'dettes_a_payer' => 'float'
                            ],
                            'formatted' => [
                                'total' => 'string',
                                'depenses' => 'string',
                                'dettes_a_payer' => 'string'
                            ]
                        ],
                        'devise' => 'string'
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/statistiques/entrees-sorties',
            'name' => 'Entrées et sorties par période',
            'description' => 'Récupère les entrées et sorties pour une période donnée',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'debut' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de début'
                ],
                'fin' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'YYYY-MM-DD',
                    'description' => 'Date de fin'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'periode' => [
                            'debut' => 'string',
                            'fin' => 'string'
                        ],
                        'entrees' => 'object',
                        'sorties' => 'object',
                        'solde_net' => 'float',
                        'solde_net_formatted' => 'string',
                        'devise' => 'string'
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/statistiques/tendances',
            'name' => 'Tendances',
            'description' => 'Récupère les tendances financières',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'periode' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['semaine', 'mois', 'trimestre'],
                    'default' => 'mois',
                    'description' => 'Période d\'analyse'
                ],
                'nb_periodes' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 6,
                    'description' => 'Nombre de périodes à analyser'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'periode' => 'string',
                        'nbPeriodes' => 'integer',
                        'tendances' => [
                            'entrees' => [
                                'evolution' => 'float',
                                'tendance' => 'string'
                            ],
                            'sorties' => [
                                'evolution' => 'float',
                                'tendance' => 'string'
                            ],
                            'solde' => [
                                'evolution' => 'float',
                                'tendance' => 'string'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/statistiques/comparaison-periodes',
            'name' => 'Comparaison de périodes',
            'description' => 'Compare les statistiques entre deux périodes',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'periode' => [
                    'type' => 'string',
                    'required' => false,
                    'enum' => ['semaine', 'mois', 'trimestre'],
                    'default' => 'mois',
                    'description' => 'Période d\'analyse'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'periode' => 'string',
                        'actuelle' => [
                            'debut' => 'string',
                            'fin' => 'string',
                            'entrees' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ],
                            'sorties' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ],
                            'solde' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ]
                        ],
                        'precedente' => [
                            'debut' => 'string',
                            'fin' => 'string',
                            'entrees' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ],
                            'sorties' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ],
                            'solde' => [
                                'montant' => 'string',
                                'montantFormatted' => 'string'
                            ]
                        ],
                        'variations' => [
                            'entrees' => 'float',
                            'sorties' => 'float',
                            'solde' => 'float'
                        ]
                    ]
                ]
            ]
        ]
    ],
    
    'endpoints_dashboard' => [
        [
            'method' => 'GET',
            'url' => '/api/dashboard/statistiques',
            'name' => 'Statistiques dashboard',
            'description' => 'Récupère les statistiques pour le dashboard',
            'authentication' => 'Bearer Token requis',
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'periode' => 'string',
                        'entrees' => [
                            'total' => 'float',
                            'totalFormatted' => 'string',
                            'detail' => [
                                'entrees_normales' => 'float',
                                'dettes_a_recevoir' => 'float'
                            ]
                        ],
                        'sorties' => [
                            'total' => 'float',
                            'totalFormatted' => 'string',
                            'detail' => [
                                'depenses' => 'float',
                                'dettes_a_payer' => 'float'
                            ]
                        ],
                        'solde_net' => 'float',
                        'solde_net_formatted' => 'string',
                        'devise' => 'string'
                    ]
                ]
            ]
        ]
    ],
    
    'exemples_utilisation' => [
        'resume_semaine' => 'GET /api/statistiques/resume?periode=semaine',
        'entrees_mois' => 'GET /api/statistiques/entrees?debut=2024-01-01&fin=2024-01-31',
        'sorties_mois' => 'GET /api/statistiques/sorties?debut=2024-01-01&fin=2024-01-31',
        'entrees_sorties' => 'GET /api/statistiques/entrees-sorties?debut=2024-01-01&fin=2024-01-31',
        'tendances' => 'GET /api/statistiques/tendances?periode=mois&nb_periodes=6',
        'comparaison' => 'GET /api/statistiques/comparaison-periodes?periode=mois',
        'dashboard' => 'GET /api/dashboard/statistiques'
    ],
    
    'codes_erreur' => [
        200 => 'Succès',
        400 => 'Données invalides',
        401 => 'Non authentifié',
        500 => 'Erreur serveur'
    ]
];
