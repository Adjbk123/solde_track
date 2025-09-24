# API Exports Excel - SoldeTrack

## 📋 Vue d'ensemble

Système d'export Excel multi-feuilles pour SoldeTrack avec formatage professionnel et données complètes.

## 🔗 Endpoints

### **Rapport Complet Excel**
```http
GET /api/export/rapport-complet/excel
```

**Paramètres :**
- `date_debut` (optionnel) : Date de début (YYYY-MM-DD)
- `date_fin` (optionnel) : Date de fin (YYYY-MM-DD)

**Réponse :** Fichier Excel multi-feuilles

### **Export Mouvements Excel**
```http
GET /api/export/mouvements/excel
```

**Paramètres :**
- `date_debut` (optionnel) : Date de début (YYYY-MM-DD)
- `date_fin` (optionnel) : Date de fin (YYYY-MM-DD)

**Réponse :** Fichier Excel des mouvements

## 📊 Contenu Excel

### **Rapport Complet (6 feuilles) :**
1. **Résumé** - Statistiques financières
2. **Mouvements** - Transactions détaillées
3. **Comptes** - Soldes et informations
4. **Contacts** - Liste des contacts
5. **Projets** - Budgets et dépenses
6. **Graphiques** - Analyses et tendances

### **Export Mouvements :**
- Colonnes : ID, Date, Type, Description, Montant, Compte, Catégorie, Projet, Contact
- Formatage professionnel avec en-têtes colorés
- Bordures et alignement optimisés

## 🔐 Authentification

```http
Authorization: Bearer {token}
```

## 📱 Exemples d'utilisation

### **Flutter :**
```dart
// Rapport complet
var response = await http.get(
  Uri.parse('$baseUrl/api/export/rapport-complet/excel'),
  headers: {'Authorization': 'Bearer $token'},
);
await saveExcelFile(response.bodyBytes, 'rapport.xlsx');

// Mouvements
var response = await http.get(
  Uri.parse('$baseUrl/api/export/mouvements/excel?date_debut=2025-01-01'),
  headers: {'Authorization': 'Bearer $token'},
);
await saveExcelFile(response.bodyBytes, 'mouvements.xlsx');
```

### **cURL :**
```bash
# Rapport complet
curl -H "Authorization: Bearer TOKEN" \
  "https://api.example.com/api/export/rapport-complet/excel" \
  --output rapport.xlsx

# Mouvements
curl -H "Authorization: Bearer TOKEN" \
  "https://api.example.com/api/export/mouvements/excel" \
  --output mouvements.xlsx
```

## ✅ Fonctionnalités

- **Multi-feuilles** : Organisation claire des données
- **Formatage professionnel** : En-têtes colorés, bordures
- **Styles cohérents** : Police, couleurs, alignement
- **Données formatées** : Nombres avec séparateurs français
- **Dates lisibles** : Format DD/MM/YYYY HH:MM
- **Largeurs optimisées** : Colonnes ajustées automatiquement

## 🚀 Installation

PhpSpreadsheet est déjà installé et configuré dans le projet.

## 📄 Formats de sortie

- **Fichier** : `.xlsx` (Excel 2007+)
- **MIME Type** : `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- **Encodage** : UTF-8
- **Séparateurs** : Virgules pour les nombres français
