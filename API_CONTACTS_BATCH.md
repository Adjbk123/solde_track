# API Contacts - Enregistrement en Lot

## 📋 Nouveau Endpoint : Création Multiple de Contacts

### Endpoint
```
POST /api/contacts/batch
Authorization: Bearer {token}
Content-Type: application/json
```

### Description
Permet d'enregistrer plusieurs contacts à la fois en une seule requête. Cette fonctionnalité est particulièrement utile pour :
- Importer des contacts depuis un carnet d'adresses
- Synchroniser des contacts depuis une source externe
- Créer plusieurs contacts manuellement de manière efficace

## 📝 Format de Requête

### Structure JSON
```json
{
    "contacts": [
        {
            "nom": "Jean Dupont",
            "telephone": "+225123456789",
            "email": "jean@example.com",
            "source": "import_telephone"
        },
        {
            "nom": "Marie Martin",
            "telephone": "+225987654321",
            "email": "marie@example.com",
            "source": "import_telephone"
        },
        {
            "nom": "Paul Durand",
            "telephone": "+225555666777",
            "source": "manuel"
        }
    ]
}
```

### Champs Requis par Contact
- **nom** (string) : Nom du contact
- **telephone** (string) : Numéro de téléphone (doit être unique par utilisateur)
- **source** (string) : Source du contact (`manuel` ou `import_telephone`)

### Champs Optionnels
- **email** (string) : Adresse email du contact

## 📤 Réponses

### 🎯 Succès Total (201 Created)
Tous les contacts ont été créés avec succès :

```json
{
    "message": "Traitement en lot terminé",
    "summary": {
        "total_processed": 3,
        "created": 3,
        "duplicates": 0,
        "errors": 0
    },
    "results": {
        "created": [
            {
                "index": 0,
                "contact": {
                    "id": 15,
                    "nom": "Jean Dupont",
                    "telephone": "+225123456789",
                    "email": "jean@example.com",
                    "source": "import_telephone",
                    "sourceLabel": "Import Téléphone",
                    "dateCreation": "2024-01-15 14:30:25"
                }
            },
            {
                "index": 1,
                "contact": {
                    "id": 16,
                    "nom": "Marie Martin",
                    "telephone": "+225987654321",
                    "email": "marie@example.com",
                    "source": "import_telephone",
                    "sourceLabel": "Import Téléphone",
                    "dateCreation": "2024-01-15 14:30:25"
                }
            },
            {
                "index": 2,
                "contact": {
                    "id": 17,
                    "nom": "Paul Durand",
                    "telephone": "+225555666777",
                    "email": null,
                    "source": "manuel",
                    "sourceLabel": "Manuel",
                    "dateCreation": "2024-01-15 14:30:25"
                }
            }
        ],
        "errors": [],
        "duplicates": []
    }
}
```

### ⚠️ Succès Partiel (207 Multi-Status)
Certains contacts ont été créés, d'autres ont des erreurs :

```json
{
    "message": "Traitement en lot terminé",
    "summary": {
        "total_processed": 4,
        "created": 2,
        "duplicates": 1,
        "errors": 1
    },
    "results": {
        "created": [
            {
                "index": 0,
                "contact": {
                    "id": 18,
                    "nom": "Alice Bernard",
                    "telephone": "+225111222333",
                    "email": "alice@example.com",
                    "source": "manuel",
                    "sourceLabel": "Manuel",
                    "dateCreation": "2024-01-15 14:35:12"
                }
            },
            {
                "index": 3,
                "contact": {
                    "id": 19,
                    "nom": "Bob Moreau",
                    "telephone": "+225444555666",
                    "email": null,
                    "source": "import_telephone",
                    "sourceLabel": "Import Téléphone",
                    "dateCreation": "2024-01-15 14:35:12"
                }
            }
        ],
        "duplicates": [
            {
                "index": 1,
                "data": {
                    "nom": "Jean Dupont",
                    "telephone": "+225123456789",
                    "email": "jean@example.com",
                    "source": "import_telephone"
                },
                "existing_contact": {
                    "id": 15,
                    "nom": "Jean Dupont",
                    "telephone": "+225123456789"
                },
                "message": "Un contact avec ce numéro existe déjà"
            }
        ],
        "errors": [
            {
                "index": 2,
                "data": {
                    "telephone": "+225777888999",
                    "email": "incomplete@example.com",
                    "source": "manuel"
                },
                "error": "Données manquantes",
                "message": "Nom, téléphone et source sont requis"
            }
        ]
    }
}
```

### ❌ Échec Total (400 Bad Request)
Aucun contact n'a pu être créé :

```json
{
    "message": "Traitement en lot terminé",
    "summary": {
        "total_processed": 2,
        "created": 0,
        "duplicates": 1,
        "errors": 1
    },
    "results": {
        "created": [],
        "duplicates": [
            {
                "index": 0,
                "data": {
                    "nom": "Jean Dupont",
                    "telephone": "+225123456789",
                    "email": "jean@example.com",
                    "source": "import_telephone"
                },
                "existing_contact": {
                    "id": 15,
                    "nom": "Jean Dupont",
                    "telephone": "+225123456789"
                },
                "message": "Un contact avec ce numéro existe déjà"
            }
        ],
        "errors": [
            {
                "index": 1,
                "data": {
                    "telephone": "+225777888999",
                    "source": "manuel"
                },
                "error": "Données manquantes",
                "message": "Nom, téléphone et source sont requis"
            }
        ]
    }
}
```

## 🚫 Erreurs Possibles

### 401 Unauthorized
```json
{
    "error": "Non authentifié"
}
```

### 400 Bad Request - Données manquantes
```json
{
    "error": "Données manquantes",
    "message": "Le tableau \"contacts\" est requis et ne peut pas être vide"
}
```

### 500 Internal Server Error - Erreur de sauvegarde
```json
{
    "error": "Erreur lors de la sauvegarde",
    "message": "Message d'erreur détaillé"
}
```

## 📊 Gestion des Erreurs Individuelles

Le système traite chaque contact individuellement et continue le traitement même si certains contacts échouent :

### Types d'Erreurs par Contact :
1. **Données manquantes** : Nom, téléphone ou source manquants
2. **Contact existant** : Un contact avec le même numéro existe déjà
3. **Données invalides** : Erreurs de validation (format email, etc.)
4. **Erreur système** : Erreurs inattendues lors du traitement

### Avantages :
- ✅ **Transaction unique** : Tous les contacts valides sont sauvegardés en une seule fois
- ✅ **Gestion des doublons** : Les contacts existants sont identifiés sans arrêter le processus
- ✅ **Rapport détaillé** : Chaque erreur est documentée avec son index et ses données
- ✅ **Performance optimisée** : Une seule requête pour créer tous les contacts valides

## 🎯 Cas d'Usage

### Import depuis un carnet d'adresses
```bash
curl -X POST /api/contacts/batch \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contacts": [
      {
        "nom": "Contact 1",
        "telephone": "+225111111111",
        "email": "contact1@example.com",
        "source": "import_telephone"
      },
      {
        "nom": "Contact 2",
        "telephone": "+225222222222",
        "source": "import_telephone"
      }
    ]
  }'
```

### Création manuelle multiple
```bash
curl -X POST /api/contacts/batch \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "contacts": [
      {
        "nom": "Nouveau Client",
        "telephone": "+225333333333",
        "email": "client@example.com",
        "source": "manuel"
      }
    ]
  }'
```

## 💡 Bonnes Pratiques

1. **Limite recommandée** : Maximum 100 contacts par requête pour des performances optimales
2. **Gestion des doublons** : Vérifiez les numéros existants avant l'import si nécessaire
3. **Validation côté client** : Validez les données avant l'envoi pour réduire les erreurs
4. **Traitement des réponses** : Gérez les trois types de statuts (201, 207, 400)
5. **Retry logic** : Implémentez une logique de retry pour les erreurs système (500)
