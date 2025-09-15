# API Mouvements - SoldeTrack

## 💰 Gestion des Mouvements Financiers

L'API des mouvements permet de gérer tous les types de transactions financières : dépenses, entrées, dettes et dons.

## 📋 Types de mouvements

- **Dépense** : Sortie d'argent (achats, factures, etc.)
- **Entrée** : Entrée d'argent (salaire, vente, etc.)
- **Dette** : Argent dû (prêt, crédit, etc.)
- **Don** : Don d'argent (cadeau, charité, etc.)

## 🎯 Endpoints disponibles

### 1. Lister les mouvements (`GET /api/mouvements`)

#### **Requête :**
```http
GET /api/mouvements?type=depense&projet_id=1&categorie_id=2&statut=effectue&page=1&limit=20
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `type` (optionnel) : Type de mouvement (depense, entree, dette, don)
- `projet_id` (optionnel) : ID du projet
- `categorie_id` (optionnel) : ID de la catégorie
- `statut` (optionnel) : Statut (en_attente, effectue, annule)
- `page` (optionnel) : Numéro de page (défaut: 1)
- `limit` (optionnel) : Nombre d'éléments par page (défaut: 20)

#### **Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 1,
            "type": "depense",
            "typeLabel": "Dépense",
            "montantTotal": "50000.00",
            "montantTotalFormatted": "50 000,00 XOF",
            "montantEffectif": "50000.00",
            "montantEffectifFormatted": "50 000,00 XOF",
            "montantRestant": "0.00",
            "montantRestantFormatted": "0,00 XOF",
            "statut": "effectue",
            "statutLabel": "Effectué",
            "date": "2024-01-20 14:30:00",
            "description": "Achat de matériaux",
            "categorie": {
                "id": 1,
                "nom": "Matériaux",
                "type": "depense"
            },
            "projet": {
                "id": 1,
                "nom": "Achat Maison"
            },
            "compte": {
                "id": 1,
                "nom": "Compte Principal",
                "type": "compte_principal"
            },
            "lieu": "Magasin Brico",
            "methodePaiement": "carte",
            "methodePaiementLabel": "Carte bancaire",
            "recu": "REC-2024-001"
        }
    ],
    "pagination": {
        "page": 1,
        "limit": 20,
        "total": 1
    }
}
```

### 2. Voir un mouvement (`GET /api/mouvements/{id}`)

#### **Requête :**
```http
GET /api/mouvements/1
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "mouvement": {
        "id": 1,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "50000.00",
        "montantTotalFormatted": "50 000,00 XOF",
        "montantEffectif": "50000.00",
        "montantEffectifFormatted": "50 000,00 XOF",
        "montantRestant": "0.00",
        "montantRestantFormatted": "0,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-20 14:30:00",
        "description": "Achat de matériaux",
        "categorie": {
            "id": 1,
            "nom": "Matériaux",
            "type": "depense"
        },
        "projet": {
            "id": 1,
            "nom": "Achat Maison"
        },
        "compte": {
            "id": 1,
            "nom": "Compte Principal",
            "type": "compte_principal"
        },
        "lieu": "Magasin Brico",
        "methodePaiement": "carte",
        "methodePaiementLabel": "Carte bancaire",
        "recu": "REC-2024-001"
    }
}
```

### 3. Créer une dépense (`POST /api/mouvements/depenses`)

#### **Requête :**
```http
POST /api/mouvements/depenses
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "25000.00",
    "categorie_id": 1,
    "description": "Achat de nourriture",
    "lieu": "Supermarché",
    "methodePaiement": "especes",
    "recu": "REC-2024-002",
    "projet_id": 1,
    "contact_id": 1,
    "compte_id": 1,
    "date": "2024-01-20"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Dépense créée avec succès",
    "mouvement": {
        "id": 2,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "25000.00",
        "montantTotalFormatted": "25 000,00 XOF",
        "montantEffectif": "25000.00",
        "montantEffectifFormatted": "25 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-20 00:00:00",
        "description": "Achat de nourriture",
        "categorie": {
            "id": 1,
            "nom": "Alimentation",
            "type": "depense"
        },
        "lieu": "Supermarché",
        "methodePaiement": "especes",
        "methodePaiementLabel": "Espèces",
        "recu": "REC-2024-002"
    }
}
```

### 4. Créer une entrée (`POST /api/mouvements/entrees`)

#### **Requête :**
```http
POST /api/mouvements/entrees
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "150000.00",
    "categorie_id": 2,
    "description": "Salaire mensuel",
    "source": "Employeur",
    "methode": "virement",
    "projet_id": 1,
    "compte_id": 1,
    "date": "2024-01-15"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Entrée créée avec succès",
    "mouvement": {
        "id": 3,
        "type": "entree",
        "typeLabel": "Entrée",
        "montantTotal": "150000.00",
        "montantTotalFormatted": "150 000,00 XOF",
        "montantEffectif": "150000.00",
        "montantEffectifFormatted": "150 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-15 00:00:00",
        "description": "Salaire mensuel",
        "categorie": {
            "id": 2,
            "nom": "Salaire",
            "type": "entree"
        },
        "source": "Employeur",
        "methode": "virement",
        "methodeLabel": "Virement bancaire"
    }
}
```

### 5. Créer une dette (`POST /api/mouvements/dettes`)

#### **Requête :**
```http
POST /api/mouvements/dettes
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "500000.00",
    "categorie_id": 3,
    "description": "Prêt bancaire",
    "echeance": "2024-12-31",
    "taux": "5.5",
    "projet_id": 1,
    "contact_id": 2,
    "compte_id": 1,
    "date": "2024-01-10"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Dette créée avec succès",
    "mouvement": {
        "id": 4,
        "type": "dette",
        "typeLabel": "Dette",
        "montantTotal": "500000.00",
        "montantTotalFormatted": "500 000,00 XOF",
        "montantEffectif": "500000.00",
        "montantEffectifFormatted": "500 000,00 XOF",
        "montantRestant": "500000.00",
        "montantRestantFormatted": "500 000,00 XOF",
        "statut": "en_attente",
        "statutLabel": "En attente",
        "date": "2024-01-10 00:00:00",
        "description": "Prêt bancaire",
        "categorie": {
            "id": 3,
            "nom": "Prêt",
            "type": "dette"
        },
        "echeance": "2024-12-31",
        "taux": "5.5",
        "montantRest": "500000.00",
        "montantRestFormatted": "500 000,00 XOF",
        "montantInterets": "27500.00",
        "montantInteretsFormatted": "27 500,00 XOF",
        "enRetard": false
    }
}
```

### 6. Créer un don (`POST /api/mouvements/dons`)

#### **Requête :**
```http
POST /api/mouvements/dons
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "10000.00",
    "categorie_id": 4,
    "description": "Don pour l'éducation",
    "occasion": "Anniversaire",
    "contact_id": 3,
    "compte_id": 1,
    "date": "2024-01-18"
}
```

#### **Réponse (201 Created) :**
```json
{
    "message": "Don créé avec succès",
    "mouvement": {
        "id": 5,
        "type": "don",
        "typeLabel": "Don",
        "montantTotal": "10000.00",
        "montantTotalFormatted": "10 000,00 XOF",
        "montantEffectif": "10000.00",
        "montantEffectifFormatted": "10 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-18 00:00:00",
        "description": "Don pour l'éducation",
        "categorie": {
            "id": 4,
            "nom": "Charité",
            "type": "don"
        },
        "occasion": "Anniversaire"
    }
}
```

### 7. Modifier un mouvement (`PUT /api/mouvements/{id}`)

#### **Requête :**
```http
PUT /api/mouvements/1
Authorization: Bearer {token}
Content-Type: application/json

{
    "montantTotal": "55000.00",
    "description": "Achat de matériaux - mis à jour",
    "date": "2024-01-21"
}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Mouvement mis à jour avec succès",
    "mouvement": {
        "id": 1,
        "type": "depense",
        "typeLabel": "Dépense",
        "montantTotal": "55000.00",
        "montantTotalFormatted": "55 000,00 XOF",
        "montantEffectif": "55000.00",
        "montantEffectifFormatted": "55 000,00 XOF",
        "statut": "effectue",
        "statutLabel": "Effectué",
        "date": "2024-01-21 00:00:00",
        "description": "Achat de matériaux - mis à jour"
    }
}
```

### 8. Supprimer un mouvement (`DELETE /api/mouvements/{id}`)

#### **Requête :**
```http
DELETE /api/mouvements/1
Authorization: Bearer {token}
```

#### **Réponse (200 OK) :**
```json
{
    "message": "Mouvement supprimé avec succès"
}
```

### 9. Statistiques des mouvements (`GET /api/mouvements/statistiques`)

#### **Requête :**
```http
GET /api/mouvements/statistiques?debut=2024-01-01&fin=2024-01-31&type=depense
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `debut` (optionnel) : Date de début (YYYY-MM-DD)
- `fin` (optionnel) : Date de fin (YYYY-MM-DD)
- `type` (optionnel) : Type de mouvement

#### **Réponse (200 OK) :**
```json
{
    "statistiques": {
        "totalDepenses": 75000.00,
        "totalDepensesFormatted": "75 000,00 XOF",
        "totalEntrees": 150000.00,
        "totalEntreesFormatted": "150 000,00 XOF",
        "totalDettes": 500000.00,
        "totalDettesFormatted": "500 000,00 XOF",
        "totalDons": 10000.00,
        "totalDonsFormatted": "10 000,00 XOF",
        "soldeNet": 75000.00,
        "soldeNetFormatted": "75 000,00 XOF",
        "nombreTotal": 4
    },
    "parType": {
        "depense": {
            "count": 2,
            "total": 75000.00
        },
        "entree": {
            "count": 1,
            "total": 150000.00
        },
        "dette": {
            "count": 1,
            "total": 500000.00
        },
        "don": {
            "count": 1,
            "total": 10000.00
        }
    },
    "parCategorie": {
        "Matériaux": {
            "count": 1,
            "total": 50000.00
        },
        "Alimentation": {
            "count": 1,
            "total": 25000.00
        },
        "Salaire": {
            "count": 1,
            "total": 150000.00
        },
        "Prêt": {
            "count": 1,
            "total": 500000.00
        },
        "Charité": {
            "count": 1,
            "total": 10000.00
        }
    }
}
```

### 10. Mouvements récents (`GET /api/mouvements/recents`)

#### **Requête :**
```http
GET /api/mouvements/recents?limit=5&type=depense
Authorization: Bearer {token}
```

#### **Paramètres de requête :**
- `limit` (optionnel) : Nombre de mouvements (défaut: 10)
- `type` (optionnel) : Type de mouvement

#### **Réponse (200 OK) :**
```json
{
    "mouvements": [
        {
            "id": 2,
            "type": "depense",
            "typeLabel": "Dépense",
            "montantTotal": "25000.00",
            "montantTotalFormatted": "25 000,00 XOF",
            "montantEffectif": "25000.00",
            "montantEffectifFormatted": "25 000,00 XOF",
            "statut": "effectue",
            "statutLabel": "Effectué",
            "date": "2024-01-20 00:00:00",
            "description": "Achat de nourriture",
            "categorie": {
                "id": 1,
                "nom": "Alimentation",
                "type": "depense"
            }
        }
    ]
}
```

## 📱 Utilisation avec Flutter

### Exemple de service Flutter :

```dart
class MouvementService {
  static const String baseUrl = 'http://localhost:8000/api';
  
  // Créer une dépense
  static Future<Mouvement> createDepense({
    required String montantTotal,
    required int categorieId,
    String? description,
    String? lieu,
    String? methodePaiement,
    String? recu,
    int? projetId,
    int? contactId,
    int? compteId,
    String? date,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/mouvements/depenses'),
      headers: await _getAuthHeaders(),
      body: jsonEncode({
        'montantTotal': montantTotal,
        'categorie_id': categorieId,
        if (description != null) 'description': description,
        if (lieu != null) 'lieu': lieu,
        if (methodePaiement != null) 'methodePaiement': methodePaiement,
        if (recu != null) 'recu': recu,
        if (projetId != null) 'projet_id': projetId,
        if (contactId != null) 'contact_id': contactId,
        if (compteId != null) 'compte_id': compteId,
        if (date != null) 'date': date,
      }),
    );
    
    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      return Mouvement.fromJson(data['mouvement']);
    } else {
      throw Exception('Erreur lors de la création de la dépense');
    }
  }
  
  // Créer une entrée
  static Future<Mouvement> createEntree({
    required String montantTotal,
    required int categorieId,
    String? description,
    String? source,
    String? methode,
    int? projetId,
    int? contactId,
    int? compteId,
    String? date,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/mouvements/entrees'),
      headers: await _getAuthHeaders(),
      body: jsonEncode({
        'montantTotal': montantTotal,
        'categorie_id': categorieId,
        if (description != null) 'description': description,
        if (source != null) 'source': source,
        if (methode != null) 'methode': methode,
        if (projetId != null) 'projet_id': projetId,
        if (contactId != null) 'contact_id': contactId,
        if (compteId != null) 'compte_id': compteId,
        if (date != null) 'date': date,
      }),
    );
    
    if (response.statusCode == 201) {
      final data = jsonDecode(response.body);
      return Mouvement.fromJson(data['mouvement']);
    } else {
      throw Exception('Erreur lors de la création de l\'entrée');
    }
  }
  
  // Récupérer les mouvements
  static Future<List<Mouvement>> getMouvements({
    String? type,
    int? projetId,
    int? categorieId,
    String? statut,
    int page = 1,
    int limit = 20,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
      'limit': limit.toString(),
    };
    
    if (type != null) queryParams['type'] = type;
    if (projetId != null) queryParams['projet_id'] = projetId.toString();
    if (categorieId != null) queryParams['categorie_id'] = categorieId.toString();
    if (statut != null) queryParams['statut'] = statut;
    
    final uri = Uri.parse('$baseUrl/mouvements').replace(
      queryParameters: queryParams,
    );
    
    final response = await http.get(
      uri,
      headers: await _getAuthHeaders(),
    );
    
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return (data['mouvements'] as List)
          .map((json) => Mouvement.fromJson(json))
          .toList();
    } else {
      throw Exception('Erreur lors de la récupération des mouvements');
    }
  }
  
  // Récupérer les statistiques
  static Future<Map<String, dynamic>> getStatistiques({
    String? debut,
    String? fin,
    String? type,
  }) async {
    final queryParams = <String, String>{};
    
    if (debut != null) queryParams['debut'] = debut;
    if (fin != null) queryParams['fin'] = fin;
    if (type != null) queryParams['type'] = type;
    
    final uri = Uri.parse('$baseUrl/mouvements/statistiques').replace(
      queryParameters: queryParams,
    );
    
    final response = await http.get(
      uri,
      headers: await _getAuthHeaders(),
    );
    
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Erreur lors de la récupération des statistiques');
    }
  }
  
  static Future<Map<String, String>> _getAuthHeaders() async {
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

### Modèle Dart pour Mouvement :

```dart
class Mouvement {
  final int id;
  final String type;
  final String typeLabel;
  final String montantTotal;
  final String? montantTotalFormatted;
  final String montantEffectif;
  final String? montantEffectifFormatted;
  final String montantRestant;
  final String? montantRestantFormatted;
  final String statut;
  final String statutLabel;
  final DateTime date;
  final String? description;
  final Categorie categorie;
  final Projet? projet;
  final Contact? contact;
  final Compte? compte;
  
  // Champs spécifiques aux dépenses
  final String? lieu;
  final String? methodePaiement;
  final String? methodePaiementLabel;
  final String? recu;
  
  // Champs spécifiques aux entrées
  final String? source;
  final String? methode;
  final String? methodeLabel;
  
  // Champs spécifiques aux dettes
  final DateTime? echeance;
  final String? taux;
  final String? montantRest;
  final String? montantRestFormatted;
  final String? montantInterets;
  final String? montantInteretsFormatted;
  final bool? enRetard;
  
  // Champs spécifiques aux dons
  final String? occasion;

  Mouvement({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.montantTotal,
    this.montantTotalFormatted,
    required this.montantEffectif,
    this.montantEffectifFormatted,
    required this.montantRestant,
    this.montantRestantFormatted,
    required this.statut,
    required this.statutLabel,
    required this.date,
    this.description,
    required this.categorie,
    this.projet,
    this.contact,
    this.compte,
    this.lieu,
    this.methodePaiement,
    this.methodePaiementLabel,
    this.recu,
    this.source,
    this.methode,
    this.methodeLabel,
    this.echeance,
    this.taux,
    this.montantRest,
    this.montantRestFormatted,
    this.montantInterets,
    this.montantInteretsFormatted,
    this.enRetard,
    this.occasion,
  });

  factory Mouvement.fromJson(Map<String, dynamic> json) {
    return Mouvement(
      id: json['id'],
      type: json['type'],
      typeLabel: json['typeLabel'],
      montantTotal: json['montantTotal'],
      montantTotalFormatted: json['montantTotalFormatted'],
      montantEffectif: json['montantEffectif'],
      montantEffectifFormatted: json['montantEffectifFormatted'],
      montantRestant: json['montantRestant'],
      montantRestantFormatted: json['montantRestantFormatted'],
      statut: json['statut'],
      statutLabel: json['statutLabel'],
      date: DateTime.parse(json['date']),
      description: json['description'],
      categorie: Categorie.fromJson(json['categorie']),
      projet: json['projet'] != null ? Projet.fromJson(json['projet']) : null,
      contact: json['contact'] != null ? Contact.fromJson(json['contact']) : null,
      compte: json['compte'] != null ? Compte.fromJson(json['compte']) : null,
      lieu: json['lieu'],
      methodePaiement: json['methodePaiement'],
      methodePaiementLabel: json['methodePaiementLabel'],
      recu: json['recu'],
      source: json['source'],
      methode: json['methode'],
      methodeLabel: json['methodeLabel'],
      echeance: json['echeance'] != null ? DateTime.parse(json['echeance']) : null,
      taux: json['taux'],
      montantRest: json['montantRest'],
      montantRestFormatted: json['montantRestFormatted'],
      montantInterets: json['montantInterets'],
      montantInteretsFormatted: json['montantInteretsFormatted'],
      enRetard: json['enRetard'],
      occasion: json['occasion'],
    );
  }
}
```

## ⚠️ Codes d'erreur

### Erreurs générales :
- **401** : Non authentifié
- **403** : Accès refusé
- **404** : Mouvement non trouvé
- **500** : Erreur serveur

### Erreurs de validation :
- **400** : Données manquantes ou invalides
- **404** : Catégorie, projet ou contact non trouvé

## 🎯 Fonctionnalités clés

1. **✅ CRUD complet** : Créer, lire, modifier, supprimer
2. **✅ 4 types de mouvements** : Dépenses, Entrées, Dettes, Dons
3. **✅ Filtrage avancé** : Par type, projet, catégorie, statut
4. **✅ Pagination** : Liste paginée des mouvements
5. **✅ Statistiques** : Analyses détaillées par type et catégorie
6. **✅ Formatage** : Montants formatés avec la devise de l'utilisateur
7. **✅ Relations** : Liens avec projets, catégories, contacts, comptes
8. **✅ Sécurité** : Chaque utilisateur ne voit que ses mouvements
