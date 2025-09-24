<?php

/**
 * ========================================
 * API DOCUMENTATION - GESTION DES UTILISATEURS
 * ========================================
 * 
 * Ce fichier contient la documentation complète de l'API
 * pour la gestion des utilisateurs et profils.
 * 
 * @author Solde Track API
 * @version 1.0
 * @date 2024
 */

return [
    'module' => 'Gestion des Utilisateurs',
    'description' => 'Gestion des utilisateurs, authentification et profils',
    'version' => '1.0',
    'base_url' => '/api/auth',
    
    'endpoints' => [
        [
            'method' => 'POST',
            'url' => '/api/auth/register',
            'name' => 'Inscription',
            'description' => 'Crée un nouveau compte utilisateur',
            'authentication' => 'Aucune',
            'parameters' => [
                'email' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'email',
                    'description' => 'Adresse email de l\'utilisateur'
                ],
                'password' => [
                    'type' => 'string',
                    'required' => true,
                    'min_length' => 6,
                    'description' => 'Mot de passe (minimum 6 caractères)'
                ],
                'nom' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Nom de l\'utilisateur'
                ],
                'prenom' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Prénom de l\'utilisateur'
                ],
                'devise_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'default' => 1,
                    'description' => 'ID de la devise par défaut'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 201,
                    'data' => [
                        'user' => [
                            'id' => 'integer',
                            'email' => 'string',
                            'nom' => 'string',
                            'prenom' => 'string',
                            'devise' => 'string',
                            'dateCreation' => 'datetime'
                        ],
                        'token' => 'string',
                        'expires_in' => 'integer'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Email déjà utilisé ou données invalides'
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/auth/login',
            'name' => 'Connexion',
            'description' => 'Authentifie un utilisateur et retourne un token',
            'authentication' => 'Aucune',
            'parameters' => [
                'email' => [
                    'type' => 'string',
                    'required' => true,
                    'format' => 'email',
                    'description' => 'Adresse email de l\'utilisateur'
                ],
                'password' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Mot de passe de l\'utilisateur'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'user' => [
                            'id' => 'integer',
                            'email' => 'string',
                            'nom' => 'string',
                            'prenom' => 'string',
                            'devise' => 'string'
                        ],
                        'token' => 'string',
                        'expires_in' => 'integer'
                    ]
                ],
                'error' => [
                    'status' => 401,
                    'message' => 'Identifiants invalides'
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/auth/logout',
            'name' => 'Déconnexion',
            'description' => 'Déconnecte l\'utilisateur et invalide le token',
            'authentication' => 'Bearer Token requis',
            'parameters' => [],
            'response' => [
                'success' => [
                    'status' => 200,
                    'message' => 'Déconnexion réussie'
                ],
                'error' => [
                    'status' => 401,
                    'message' => 'Token invalide'
                ]
            ]
        ],
        
        [
            'method' => 'GET',
            'url' => '/api/auth/me',
            'name' => 'Profil utilisateur',
            'description' => 'Récupère les informations du profil utilisateur connecté',
            'authentication' => 'Bearer Token requis',
            'parameters' => [],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'email' => 'string',
                        'nom' => 'string',
                        'prenom' => 'string',
                        'devise' => 'string',
                        'dateCreation' => 'datetime',
                        'derniereConnexion' => 'datetime'
                    ]
                ],
                'error' => [
                    'status' => 401,
                    'message' => 'Non authentifié'
                ]
            ]
        ],
        
        [
            'method' => 'PUT',
            'url' => '/api/auth/me',
            'name' => 'Modifier le profil',
            'description' => 'Modifie les informations du profil utilisateur',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'nom' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Nouveau nom'
                ],
                'prenom' => [
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Nouveau prénom'
                ],
                'devise_id' => [
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'Nouvelle devise par défaut'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'id' => 'integer',
                        'email' => 'string',
                        'nom' => 'string',
                        'prenom' => 'string',
                        'devise' => 'string',
                        'dateModification' => 'datetime'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Données invalides'
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/auth/change-password',
            'name' => 'Changer le mot de passe',
            'description' => 'Change le mot de passe de l\'utilisateur connecté',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'current_password' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Mot de passe actuel'
                ],
                'new_password' => [
                    'type' => 'string',
                    'required' => true,
                    'min_length' => 6,
                    'description' => 'Nouveau mot de passe'
                ],
                'confirm_password' => [
                    'type' => 'string',
                    'required' => true,
                    'description' => 'Confirmation du nouveau mot de passe'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'message' => 'Mot de passe modifié avec succès'
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Mot de passe actuel incorrect ou données invalides'
                ]
            ]
        ]
    ],
    
    'endpoints_profil' => [
        [
            'method' => 'GET',
            'url' => '/api/profil',
            'name' => 'Profil complet',
            'description' => 'Récupère le profil complet avec statistiques',
            'authentication' => 'Bearer Token requis',
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'user' => [
                            'id' => 'integer',
                            'email' => 'string',
                            'nom' => 'string',
                            'prenom' => 'string',
                            'devise' => 'string',
                            'dateCreation' => 'datetime'
                        ],
                        'statistiques' => [
                            'totalComptes' => 'integer',
                            'totalMouvements' => 'integer',
                            'soldeTotal' => 'float',
                            'derniereActivite' => 'datetime'
                        ]
                    ]
                ]
            ]
        ],
        
        [
            'method' => 'POST',
            'url' => '/api/profil/photo',
            'name' => 'Upload photo de profil',
            'description' => 'Upload une photo de profil pour l\'utilisateur',
            'authentication' => 'Bearer Token requis',
            'parameters' => [
                'photo' => [
                    'type' => 'file',
                    'required' => true,
                    'format' => 'image/jpeg, image/png',
                    'max_size' => '2MB',
                    'description' => 'Fichier image de profil'
                ]
            ],
            'response' => [
                'success' => [
                    'status' => 200,
                    'data' => [
                        'photo_url' => 'string',
                        'message' => 'Photo uploadée avec succès'
                    ]
                ],
                'error' => [
                    'status' => 400,
                    'message' => 'Format de fichier non supporté ou taille trop importante'
                ]
            ]
        ]
    ],
    
    'exemples_utilisation' => [
        'inscription' => 'POST /api/auth/register {"email": "user@example.com", "password": "password123", "nom": "Doe", "prenom": "John"}',
        'connexion' => 'POST /api/auth/login {"email": "user@example.com", "password": "password123"}',
        'profil' => 'GET /api/auth/me',
        'modifier_profil' => 'PUT /api/auth/me {"nom": "Nouveau Nom"}',
        'changer_mot_de_passe' => 'POST /api/auth/change-password {"current_password": "old", "new_password": "new123", "confirm_password": "new123"}'
    ],
    
    'codes_erreur' => [
        200 => 'Succès',
        201 => 'Créé avec succès',
        400 => 'Données invalides',
        401 => 'Non authentifié',
        403 => 'Accès refusé',
        404 => 'Utilisateur non trouvé',
        500 => 'Erreur serveur'
    ]
];
