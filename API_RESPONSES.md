# Réponses de l'API SoldeTrack

## 🔐 Authentification

### 1. Inscription (`POST /api/auth/register`)

#### **Requête :**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "nom": "Dupont",
    "prenoms": "Jean",
    "devise_id": 1,
    "dateNaissance": "1990-01-15"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Inscription réussie et connexion automatique",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "nom": "Dupont",
        "prenoms": "Jean",
        "photo": null,
        "dateNaissance": "1990-01-15",
        "dateCreation": "2024-01-15 10:30:00",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA Ouest-Africain"
        }
    },
    "setup": {
        "categories_created": true,
        "comptes_created": true,
        "message": "Catégories et comptes par défaut créés automatiquement"
    }
}
```

### 2. Connexion (`POST /api/auth/login`)

#### **Requête :**
```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Connexion réussie",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "nom": "Dupont",
        "prenoms": "Jean",
        "photo": "http://localhost:8000/uploads/profils/1_photo-abc123.jpg",
        "dateNaissance": "1990-01-15",
        "dateCreation": "2024-01-15 10:30:00",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA Ouest-Africain"
        }
    }
}
```

### 3. Profil utilisateur (`GET /api/auth/me`)

#### **Réponse (200 OK) :**
```json
{
    "user": {
        "id": 1,
        "email": "user@example.com",
        "nom": "Dupont",
        "prenoms": "Jean",
        "photo": "http://localhost:8000/uploads/profils/1_photo-abc123.jpg",
        "dateNaissance": "1990-01-15",
        "dateCreation": "2024-01-15 10:30:00",
        "devise": {
            "id": 1,
            "code": "XOF",
            "nom": "Franc CFA Ouest-Africain"
        }
    }
}
```

## 💰 Comptes

### 1. Liste des comptes (`GET /api/comptes`)

#### **Réponse (200 OK) :**
```json
{
    "comptes": [
        {
            "id": 1,
            "nom": "Compte Principal",
            "description": "Compte principal pour les transactions quotidiennes",
            "type": "compte_principal",
            "typeLabel": "Compte Principal",
            "devise": {
                "id": 1,
                "code": "XOF",
                "nom": "Franc CFA Ouest-Africain"
            },
            "soldeInitial": "0.00",
            "soldeActuel": "150000.00",
            "numero": null,
            "institution": null,
            "actif": true,
            "dateCreation": "2024-01-15 10:30:00",
            "dateModification": "2024-01-15 15:45:00"
        }
    ]
}
```

## 🔄 Transferts

### 1. Créer un transfert (`POST /api/transferts`)

#### **Requête :**
```json
{
    "compte_source_id": 1,
    "compte_destination_id": 2,
    "montant": "5000.00",
    "note": "Transfert vers épargne"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Transfert effectué avec succès",
    "transfert": {
        "id": 1,
        "montant": "5000.00",
        "montantFormatted": "5 000,00 XOF",
        "compteSource": {
            "id": 1,
            "nom": "Compte Principal",
            "nouveauSolde": "145000.00"
        },
        "compteDestination": {
            "id": 2,
            "nom": "Épargne",
            "nouveauSolde": "5000.00"
        },
        "description": "Transfert de 5 000,00 XOF de Compte Principal vers Épargne"
    }
}
```

## 📊 Dashboard

### 1. Statistiques (`GET /api/dashboard`)

#### **Réponse (200 OK) :**
```json
{
    "soldeTotal": "150000.00",
    "soldeTotalFormatted": "150 000,00 XOF",
    "statistiques": {
        "totalDepenses": "25000.00",
        "totalEntrees": "175000.00",
        "totalDettes": "5000.00",
        "totalDons": "2000.00",
        "nombreMouvements": 15,
        "mouvementsRecents": [
            {
                "id": 1,
                "type": "entree",
                "montant": "50000.00",
                "description": "Salaire",
                "date": "2024-01-15 09:00:00"
            }
        ]
    },
    "dettesEnRetard": {
        "count": 2,
        "total": "3000.00",
        "totalFormatted": "3 000,00 XOF"
    },
    "devise": {
        "code": "XOF",
        "nom": "Franc CFA Ouest-Africain"
    }
}
```

## 🏷️ Catégories

### 1. Liste des catégories (`GET /api/categories`)

#### **Réponse (200 OK) :**
```json
{
    "categories": [
        {
            "id": 1,
            "nom": "Alimentation",
            "type": "depense",
            "dateCreation": "2024-01-15 10:30:00"
        },
        {
            "id": 2,
            "nom": "Transport",
            "type": "depense",
            "dateCreation": "2024-01-15 10:30:00"
        }
    ]
}
```

## 📱 Utilisation avec Flutter

### Exemple de gestion de l'inscription :

```dart
class AuthService {
  static const String baseUrl = 'http://localhost:8000/api';
  
  static Future<Map<String, dynamic>> register({
    required String email,
    required String password,
    required String nom,
    required String prenoms,
    required int deviseId,
    String? dateNaissance,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/register'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'email': email,
        'password': password,
        'nom': nom,
        'prenoms': prenoms,
        'devise_id': deviseId,
        if (dateNaissance != null) 'dateNaissance': dateNaissance,
      }),
    );
    
    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      
      // Sauvegarder le token
      await _saveToken(data['token']);
      
      // Retourner les données utilisateur
      return {
        'user': data['user'],
        'setup': data['setup'],
        'token': data['token'],
      };
    } else {
      throw Exception('Erreur d\'inscription: ${response.body}');
    }
  }
  
  static Future<void> _saveToken(String token) async {
    // Sauvegarder le token dans le stockage local
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }
}
```

## ⚠️ Codes d'erreur

### Erreurs d'inscription :
- **400** : Données manquantes ou invalides
- **409** : Utilisateur déjà existant
- **422** : Erreurs de validation

### Erreurs de connexion :
- **400** : Données manquantes
- **401** : Identifiants invalides

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Ressource non trouvée
- **500** : Erreur serveur

## 🎯 Points importants

1. **Token JWT** : Inclus dans toutes les réponses d'authentification
2. **Connexion automatique** : L'inscription connecte automatiquement l'utilisateur
3. **Setup automatique** : Catégories et comptes créés automatiquement
4. **Formatage des montants** : Montants formatés avec la devise de l'utilisateur
5. **URLs des photos** : URLs complètes pour les photos de profil
