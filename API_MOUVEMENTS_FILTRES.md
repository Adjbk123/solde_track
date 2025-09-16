# 📊 API Mouvements - Filtres par Période

## 🎯 Endpoints disponibles

### 1. **Mouvements du jour**
```
GET /api/mouvements/today
```

**Paramètres optionnels :**
- `type` : `depense`, `entree`, `dette`, `don`

**Exemple :**
```
GET /api/mouvements/today
GET /api/mouvements/today?type=depense
```

**Réponse :**
```json
{
  "date": "2025-01-16",
  "mouvements": [...],
  "total": 5
}
```

---

### 2. **Mouvements d'une période personnalisée**
```
GET /api/mouvements/period
```

**Paramètres requis :**
- `start_date` : Date de début (format: YYYY-MM-DD)
- `end_date` : Date de fin (format: YYYY-MM-DD)

**Paramètres optionnels :**
- `type` : `depense`, `entree`, `dette`, `don`

**Exemple :**
```
GET /api/mouvements/period?start_date=2025-01-01&end_date=2025-01-31
GET /api/mouvements/period?start_date=2025-01-01&end_date=2025-01-31&type=depense
```

**Réponse :**
```json
{
  "start_date": "2025-01-01",
  "end_date": "2025-01-31",
  "mouvements": [...],
  "total": 25
}
```

---

### 3. **Mouvements de la semaine**
```
GET /api/mouvements/week
```

**Paramètres optionnels :**
- `type` : `depense`, `entree`, `dette`, `don`
- `week_offset` : Décalage de semaine (0 = cette semaine, -1 = semaine dernière, 1 = semaine prochaine)

**Exemple :**
```
GET /api/mouvements/week
GET /api/mouvements/week?type=depense
GET /api/mouvements/week?week_offset=-1
```

**Réponse :**
```json
{
  "week_start": "2025-01-13",
  "week_end": "2025-01-19",
  "week_offset": 0,
  "mouvements": [...],
  "total": 12
}
```

---

### 4. **Mouvements du mois**
```
GET /api/mouvements/month
```

**Paramètres optionnels :**
- `type` : `depense`, `entree`, `dette`, `don`
- `month_offset` : Décalage de mois (0 = ce mois, -1 = mois dernier, 1 = mois prochain)

**Exemple :**
```
GET /api/mouvements/month
GET /api/mouvements/month?type=entree
GET /api/mouvements/month?month_offset=-1
```

**Réponse :**
```json
{
  "month": "2025-01",
  "month_start": "2025-01-01",
  "month_end": "2025-01-31",
  "month_offset": 0,
  "mouvements": [...],
  "total": 45
}
```

---

### 5. **Dépenses du jour (spécialisé)**
```
GET /api/mouvements/depenses/today
```

**Réponse :**
```json
{
  "date": "2025-01-16",
  "depenses": [...],
  "total_count": 3,
  "total_amount": 15000,
  "total_amount_formatted": "15 000 XOF"
}
```

---

### 6. **Revenus du jour (spécialisé)**
```
GET /api/mouvements/entrees/today
```

**Réponse :**
```json
{
  "date": "2025-01-16",
  "entrees": [...],
  "total_count": 2,
  "total_amount": 50000,
  "total_amount_formatted": "50 000 XOF"
}
```

---

## 🔧 Types de mouvements disponibles

- **`depense`** : Dépenses
- **`entree`** : Revenus/Entrées
- **`dette`** : Dettes
- **`don`** : Dons

---

## 📱 Exemples d'utilisation Frontend

### Flutter/Dart
```dart
// Dépenses d'aujourd'hui
final response = await http.get(
  Uri.parse('$baseUrl/api/mouvements/depenses/today'),
  headers: {'Authorization': 'Bearer $token'},
);

// Mouvements de cette semaine
final response = await http.get(
  Uri.parse('$baseUrl/api/mouvements/week'),
  headers: {'Authorization': 'Bearer $token'},
);

// Dépenses du mois dernier
final response = await http.get(
  Uri.parse('$baseUrl/api/mouvements/month?type=depense&month_offset=-1'),
  headers: {'Authorization': 'Bearer $token'},
);

// Période personnalisée
final response = await http.get(
  Uri.parse('$baseUrl/api/mouvements/period?start_date=2025-01-01&end_date=2025-01-31&type=entree'),
  headers: {'Authorization': 'Bearer $token'},
);
```

---

## 🎨 Cas d'usage Frontend

### 1. **Dashboard du jour**
- Utiliser `/depenses/today` et `/entrees/today`
- Afficher le solde du jour (revenus - dépenses)

### 2. **Vue hebdomadaire**
- Utiliser `/week` avec `week_offset` pour naviguer
- Graphiques par jour de la semaine

### 3. **Vue mensuelle**
- Utiliser `/month` avec `month_offset` pour naviguer
- Statistiques mensuelles

### 4. **Période personnalisée**
- Utiliser `/period` avec sélecteur de dates
- Rapports personnalisés

### 5. **Filtres par type**
- Ajouter `?type=depense` pour ne voir que les dépenses
- Filtres combinés : `/week?type=entree`

---

## ⚡ Performance

- Tous les endpoints utilisent des requêtes optimisées
- Pagination disponible sur l'endpoint principal `/api/mouvements`
- Filtres appliqués au niveau base de données
- Tri par date décroissante (plus récent en premier)

---

## 🔐 Authentification

Tous les endpoints nécessitent :
```
Authorization: Bearer <JWT_TOKEN>
```

---

## 📊 Format des mouvements

Chaque mouvement retourné contient :
```json
{
  "id": 1,
  "type": "depense",
  "typeLabel": "Dépense",
  "montantTotal": "15000",
  "montantEffectif": "15000.00",
  "montantRestant": "0.00",
  "statut": "paye",
  "statutLabel": "Payé",
  "date": "2025-01-16 14:30:00",
  "description": "Achat alimentaire",
  "montantTotalFormatted": "15 000 XOF",
  "montantEffectifFormatted": "15 000 XOF",
  "montantRestantFormatted": "0 XOF",
  "categorie": {
    "id": 5,
    "nom": "Alimentation",
    "type": "depense",
    "typeLabel": "Dépense"
  },
  "compte": {
    "id": 1,
    "nom": "Compte Principal",
    "soldeActuel": "85000.00",
    "soldeActuelFormatted": "85 000 XOF",
    "devise": "XOF"
  }
}
```
