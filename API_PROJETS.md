# API Projets - SoldeTrack

## 🎯 Gestion des Projets

L'API des projets permet de gérer les projets financiers personnels avec suivi des budgets et des mouvements associés.

## 📋 Endpoints disponibles

### 1. Lister les projets (`GET /api/projets`)

#### **Requête :**
```http
GET /api/projets?page=1&limit=20&search=maison
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)
- `search` (optionnel) : Recherche dans le nom et la description

#### **Réponse (200 OK) :**
```json
{
    "projets": [
        {
            "id": 1,
            "nom": "Achat Maison",
            "description": "Projet d'achat d'une maison familiale",
            "budgetPrevu": "50000000.00",
            "budgetPrevuFormatted": "50 000 000,00 XOF",
            "dateCreation": "2024-01-15 10:30:00",
            "nombreMouvements": 15
        },
        {
            "id": 2,
            "nom": "Voyage Europe",
            "description": "Voyage de 2 semaines en Europe",
            "budgetPrevu": "2000000.00",
            "budgetPrevuFormatted": "2 000 000,00 XOF",
            "dateCreation": "2024-01-10 14:20:00",
            "nombreMouvements": 8
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 2,
        "pages": 1
    }
}
```

### 2. Voir un projet (`GET /api/projets/{id}`)

#### **Requête :**
```http
GET /api/projets/1
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "projet": {
        "id": 1,
        "nom": "Achat Maison",
        "description": "Projet d'achat d'une maison familiale",
        "budgetPrevu": "50000000.00",
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "dateCreation": "2024-01-15 10:30:00",
        "nombreMouvements": 15,
        "statistiques": {
            "totalDepenses": "5000000.00",
            "totalDepensesFormatted": "5 000 000,00 XOF",
            "totalEntrees": "8000000.00",
            "totalEntreesFormatted": "8 000 000,00 XOF",
            "soldeActuel": "3000000.00",
            "soldeActuelFormatted": "3 000 000,00 XOF"
        }
    }
}
```

### 3. Créer un projet (`POST /api/projets`)

#### **Requête :**
```http
POST /api/projets
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Achat Voiture",
    "description": "Achat d'une voiture d'occasion",
    "budgetPrevu": "3000000.00"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Projet créé avec succès",
    "projet": {
        "id": 3,
        "nom": "Achat Voiture",
        "description": "Achat d'une voiture d'occasion",
        "budgetPrevu": "3000000.00",
        "budgetPrevuFormatted": "3 000 000,00 XOF",
        "dateCreation": "2024-01-20 16:45:00"
    }
}
```

### 4. Modifier un projet (`PUT /api/projets/{id}`)

#### **Requête :**
```http
PUT /api/projets/3
Authorization: Bearer {token}
Content-Type: application/json

{
    "nom": "Achat Voiture Neuve",
    "description": "Achat d'une voiture neuve",
    "budgetPrevu": "5000000.00"
}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Projet mis à jour avec succès",
    "projet": {
        "id": 3,
        "nom": "Achat Voiture Neuve",
        "description": "Achat d'une voiture neuve",
        "budgetPrevu": "5000000.00",
        "budgetPrevuFormatted": "5 000 000,00 XOF",
        "dateCreation": "2024-01-20 16:45:00"
    }
}
```

### 5. Supprimer un projet (`DELETE /api/projets/{id}`)

#### **Requête :**
```http
DELETE /api/projets/3
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Projet supprimé avec succès"
}
```

#### **Erreur si des mouvements existent (400 Bad Request) :**
```json
{
    "error": "Impossible de supprimer",
    "message": "Ce projet contient des mouvements"
}
```

### 6. Statistiques d'un projet (`GET /api/projets/{id}/statistiques`)

#### **Requête :**
```http
GET /api/projets/1/statistiques
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "projet": {
        "id": 1,
        "nom": "Achat Maison",
        "description": "Projet d'achat d'une maison familiale",
        "budgetPrevu": "50000000.00",
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "dateCreation": "2024-01-15 10:30:00",
        "nombreMouvements": 15
    },
    "statistiques": {
        "budgetPrevu": 50000000.00,
        "budgetPrevuFormatted": "50 000 000,00 XOF",
        "totalDepenses": 5000000.00,
        "totalDepensesFormatted": "5 000 000,00 XOF",
        "totalEntrees": 8000000.00,
        "totalEntreesFormatted": "8 000 000,00 XOF",
        "totalDettes": 2000000.00,
        "totalDettesFormatted": "2 000 000,00 XOF",
        "totalDons": 500000.00,
        "totalDonsFormatted": "500 000,00 XOF",
        "soldeActuel": 3000000.00,
        "soldeActuelFormatted": "3 000 000,00 XOF",
        "pourcentageUtilise": 10.0,
        "nombreMouvements": 15,
        "derniereActivite": "2024-01-20 14:30:00"
    }
}
```

### 7. Mouvements d'un projet (`GET /api/projets/{id}/mouvements`)

#### **Requête :**
```http
GET /api/projets/1/mouvements?page=1&limit=10&type=depense
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)
- `type` (optionnel) : Filtrer par type (depense, entree, dette, don)

#### **Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 1,
            "type": "depense",
            "montant": "500000.00",
            "montantFormatted": "500 000,00 XOF",
            "description": "Achat de matériaux",
            "date": "2024-01-20 14:30:00",
            "statut": "effectue",
            "categorie": {
                "id": 1,
                "nom": "Matériaux"
            }
        },
        {
            "id": 2,
            "type": "entree",
            "montant": "1000000.00",
            "montantFormatted": "1 000 000,00 XOF",
            "description": "Épargne mensuelle",
            "date": "2024-01-15 09:00:00",
            "statut": "effectue",
            "categorie": {
                "id": 2,
                "nom": "Épargne"
            }
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 10,
        "total": 15,
        "pages": 2
    }
}
```

## 📱 Utilisation avec Flutter

### Exemple de service Flutter :

```dart
class ProjetService {
  static const String baseUrl = 'http://localhost:8000/api';
  
  static Future<List<Projet>> getProjets({
    int page = 1,
    int limit = 20,
    String? search,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
      'limit': limit.toString(),
    };
    
    if (search != null && search.isNotEmpty) {
      queryParams['search'] = search;
    }
    
    final uri = Uri.parse('$baseUrl/projets').replace(
      queryParameters: queryParams,
    );
    
    final response = await http.get(
      uri,
      headers: await _getAuthHeaders(),
    );
    
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return (data['projets'] as List)
          .map((json) => Projet.fromJson(json))
          .toList();
    } else {
      throw Exception('Erreur lors de la récupération des projets');
    }
  }
  
  static Future<Projet> createProjet({
    required String nom,
    String? description,
    String? budgetPrevu,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/projets'),
      headers: await _getAuthHeaders(),
      body: jsonEncode({
        'nom': nom,
        if (description != null) 'description': description,
        if (budgetPrevu != null) 'budgetPrevu': budgetPrevu,
      }),
    );
    
    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      return Projet.fromJson(data['projet']);
    } else {
      throw Exception('Erreur lors de la création du projet');
    }
  }
  
  static Future<Map<String, dynamic>> getStatistiques(int projetId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/projets/$projetId/statistiques'),
      headers: await _getAuthHeaders(),
    );
    
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Erreur lors de la récupération des statistiques');
    }
  }
  
  static Future<Map<String, dynamic>> _getAuthHeaders() async {
    final token = await _getStoredToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'Authorization': 'Bearer $token',
    };
  }
  
  static Future<String> _getStoredToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token') ?? '';
  }
}
```

### Modèle Dart pour Projet :

```dart
class Projet {
  final int id;
  final String nom;
  final String? description;
  final String? budgetPrevu;
  final String? budgetPrevuFormatted;
  final DateTime dateCreation;
  final int nombreMouvements;
  final Map<String, dynamic>? statistiques;

  Projet({
    required this.id,
    required this.nom,
    this.description,
    this.budgetPrevu,
    this.budgetPrevuFormatted,
    required this.dateCreation,
    required this.nombreMouvements,
    this.statistiques,
  });

  factory Projet.fromJson(Map<String, dynamic> json) {
    return Projet(
      id: json['id'],
      nom: json['nom'],
      description: json['description'],
      budgetPrevu: json['budgetPrevu'],
      budgetPrevuFormatted: json['budgetPrevuFormatted'],
      dateCreation: DateTime.parse(json['dateCreation']),
      nombreMouvements: json['nombreMouvements'],
      statistiques: json['statistiques'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nom': nom,
      'description': description,
      'budgetPrevu': budgetPrevu,
      'budgetPrevuFormatted': budgetPrevuFormatted,
      'dateCreation': dateCreation.toIso8601String(),
      'nombreMouvements': nombreMouvements,
      'statistiques': statistiques,
    };
  }
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Projet non trouvé
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **400** : Impossible de supprimer (mouvements associés)

## 🎯 Fonctionnalités clés

1. **✅ CRUD complet** : Créer, lire, modifier, supprimer
2. **✅ Recherche** : Recherche dans le nom et la description
3. **✅ Pagination** : Liste paginée des projets
4. **✅ Statistiques** : Calculs automatiques des budgets et soldes
5. **✅ Mouvements** : Liste des mouvements associés au projet
6. **✅ Formatage** : Montants formatés avec la devise de l'utilisateur
7. **✅ Sécurité** : Chaque utilisateur ne voit que ses projets
