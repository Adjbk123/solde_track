# API Gestion des Exports

## 📋 Vue d'ensemble

Le système d'export permet de générer des documents financiers professionnels au format PDF, CSV et Excel pour les utilisateurs de SoldeTrack. Plusieurs types d'exports sont disponibles : relevés de compte, rapports financiers, exports de données et rapports Excel multi-feuilles.

## 🔗 Endpoints disponibles

### **📄 Exports PDF**
- `GET /api/export/releve-compte/{compteId}` - Relevé de compte
- `GET /api/export/rapport-financier` - Rapport financier global

### **📊 Exports CSV**
- `GET /api/export/mouvements/csv` - Export mouvements
- `GET /api/export/comptes/csv` - Export comptes
- `GET /api/export/contacts/csv` - Export contacts
- `GET /api/export/projets/csv` - Export projets

### **📈 Exports Excel**
- `GET /api/export/rapport-complet/excel` - Rapport complet multi-feuilles
- `GET /api/export/mouvements/excel` - Export mouvements Excel

### **ℹ️ Formats disponibles**
- `GET /api/export/formats` - Liste des formats disponibles

## 🔐 Authentification

- **Type** : Bearer Token (JWT)
- **Obligatoire** : Oui
- **Header** : `Authorization: Bearer {token}`

## 📄 1. EXPORTS PDF

### **1.1 RELEVÉ DE COMPTE

### **Description**
Génère un relevé de compte professionnel au format PDF pour un compte spécifique, incluant tous les mouvements financiers sur une période donnée.

### **Paramètres**

#### **Path Parameters**
| Paramètre | Type | Obligatoire | Description |
|-----------|------|--------------|-------------|
| `compteId` | integer | ✅ Oui | ID du compte à exporter |

#### **Query Parameters**
| Paramètre | Type | Obligatoire | Description | Format |
|-----------|------|--------------|-------------|---------|
| `date_debut` | string | ❌ Non | Date de début de la période | YYYY-MM-DD |
| `date_fin` | string | ❌ Non | Date de fin de la période | YYYY-MM-DD |

### **Exemple de requête**
```bash
GET /api/export/releve-compte/1?date_debut=2025-01-01&date_fin=2025-01-31
```

### **Contenu du PDF**

#### **En-tête**
- Logo SoldeTrack
- "Gestion de Finances Personnelles"
- Numéro de page
- "RELEVÉ DE COMPTE"

#### **Informations du compte**
- Nom et prénom du titulaire
- Email du titulaire
- Nom du compte
- Numéro de compte (si disponible)

#### **Période et solde**
- Période couverte
- Solde d'ouverture

#### **Tableau des mouvements**
| Colonne | Description |
|---------|-------------|
| Date | Date de la transaction (DD/MM/YYYY) |
| Type | Type de mouvement (Entrée, Sortie, Dette, Don, Transfert) |
| Description | Description de la transaction |
| Crédit | Montant crédité (entrées) |
| Débit | Montant débité (sorties) |

#### **Résumé financier**
- Total Entrées
- Total Sorties
- Solde final

### **Réponses**

#### **✅ Succès (200 OK)**
- **Content-Type** : `application/pdf`
- **Content-Disposition** : `attachment; filename="releve-compte-{compteId}.pdf"`
- **Body** : Fichier PDF binaire

#### **❌ Erreurs**

**401 - Non authentifié**
```json
{
  "error": "Non authentifié"
}
```

**404 - Compte non trouvé**
```json
{
  "error": "Compte non trouvé",
  "message": "Le compte spécifié n'existe pas ou n'appartient pas à cet utilisateur"
}
```

**400 - Date invalide**
```json
{
  "error": "Date de début invalide",
  "message": "Format de date invalide. Utilisez YYYY-MM-DD"
}
```

**500 - Erreur serveur**
```json
{
  "error": "Erreur lors de la génération du PDF",
  "message": "Une erreur est survenue lors de la génération du relevé"
}
```

## 📊 2. RAPPORT FINANCIER GLOBAL

### **Description**
Génère un rapport financier complet au format PDF incluant tous les comptes et mouvements de l'utilisateur sur une période donnée.

### **Paramètres**

#### **Query Parameters**
| Paramètre | Type | Obligatoire | Description | Format |
|-----------|------|--------------|-------------|---------|
| `date_debut` | string | ❌ Non | Date de début de la période | YYYY-MM-DD |
| `date_fin` | string | ❌ Non | Date de fin de la période | YYYY-MM-DD |

### **Exemple de requête**
```bash
GET /api/export/rapport-financier?date_debut=2025-01-01&date_fin=2025-01-31
```

### **Contenu du PDF**

#### **En-tête**
- Logo SoldeTrack
- "Gestion de Finances Personnelles"
- "RAPPORT FINANCIER"
- Nom de l'utilisateur
- Période couverte

#### **Vue d'ensemble**
- Nombre de comptes actifs
- Nombre total de mouvements
- Patrimoine total

#### **Détail des comptes**
Pour chaque compte :
- Nom du compte
- Solde actuel
- Type de compte
- Devise
- Solde initial
- Date de création
- Description (si disponible)

#### **Statistiques des mouvements**
- Total des entrées
- Total des sorties

### **Réponses**

#### **✅ Succès (200 OK)**
- **Content-Type** : `application/pdf`
- **Content-Disposition** : `attachment; filename="rapport-financier-{userId}.pdf"`
- **Body** : Fichier PDF binaire

#### **❌ Erreurs**

**401 - Non authentifié**
```json
{
  "error": "Non authentifié"
}
```

**400 - Date invalide**
```json
{
  "error": "Date de début invalide",
  "message": "Format de date invalide. Utilisez YYYY-MM-DD"
}
```

**500 - Erreur serveur**
```json
{
  "error": "Erreur lors de la génération du PDF",
  "message": "Une erreur est survenue lors de la génération du rapport"
}
```

## 📋 3. FORMATS DISPONIBLES

### **Description**
Retourne la liste des formats d'export disponibles avec leurs paramètres.

### **Exemple de requête**
```bash
GET /api/export/formats
```

### **Réponse (200 OK)**
```json
{
  "formats": [
    {
      "type": "releve_compte",
      "name": "Relevé de Compte",
      "description": "Export PDF détaillé d'un compte spécifique avec tous ses mouvements",
      "endpoint": "/api/export/releve-compte/{compteId}",
      "parameters": [
        "compteId: ID du compte (obligatoire)",
        "date_debut: Date de début (optionnel, format YYYY-MM-DD)",
        "date_fin: Date de fin (optionnel, format YYYY-MM-DD)"
      ]
    },
    {
      "type": "rapport_financier",
      "name": "Rapport Financier Global",
      "description": "Export PDF de l'ensemble de la situation financière",
      "endpoint": "/api/export/rapport-financier",
      "parameters": [
        "date_debut: Date de début (optionnel, format YYYY-MM-DD)",
        "date_fin: Date de fin (optionnel, format YYYY-MM-DD)"
      ]
    }
  ]
}
```

## 🎨 Design et Style

### **Caractéristiques du design**
- **Style professionnel** : Noir et blanc, sans couleurs
- **Logo SoldeTrack** : En italique, en haut à gauche
- **Typographie** : Arial, lisible et professionnelle
- **Layout** : Inspiré des relevés bancaires traditionnels
- **Bordures** : Lignes noires nettes
- **Tableaux** : Alternance de couleurs grises pour la lisibilité

### **Éléments visuels**
- **En-tête** : Logo, titre, informations de contact
- **Informations client** : Nom, email, détails du compte
- **Période** : Dates de début et fin, solde d'ouverture
- **Tableau** : Mouvements avec colonnes Crédit/Débit
- **Totaux** : Résumé financier en bas
- **Footer** : Copyright et mentions légales

## 📱 Exemples d'utilisation

### **JavaScript/Fetch**

#### **Export relevé de compte**
```javascript
async function exportReleveCompte(compteId, dateDebut = null, dateFin = null) {
  try {
    let url = `/api/export/releve-compte/${compteId}`;
    const params = new URLSearchParams();
    
    if (dateDebut) params.append('date_debut', dateDebut);
    if (dateFin) params.append('date_fin', dateFin);
    
    if (params.toString()) {
      url += `?${params.toString()}`;
    }

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `releve-compte-${compteId}.pdf`;
      a.click();
      window.URL.revokeObjectURL(url);
    } else {
      const error = await response.json();
      console.error('Erreur:', error.error);
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
  }
}
```

#### **Export rapport financier**
```javascript
async function exportRapportFinancier(dateDebut = null, dateFin = null) {
  try {
    let url = '/api/export/rapport-financier';
    const params = new URLSearchParams();
    
    if (dateDebut) params.append('date_debut', dateDebut);
    if (dateFin) params.append('date_fin', dateFin);
    
    if (params.toString()) {
      url += `?${params.toString()}`;
    }

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (response.ok) {
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'rapport-financier.pdf';
      a.click();
      window.URL.revokeObjectURL(url);
    } else {
      const error = await response.json();
      console.error('Erreur:', error.error);
    }
  } catch (error) {
    console.error('Erreur réseau:', error);
  }
}
```

### **Flutter/Dart**

#### **Export relevé de compte**
```dart
Future<void> exportReleveCompte(int compteId, {String? dateDebut, String? dateFin}) async {
  try {
    String url = '/api/export/releve-compte/$compteId';
    List<String> params = [];
    
    if (dateDebut != null) params.add('date_debut=$dateDebut');
    if (dateFin != null) params.add('date_fin=$dateFin');
    
    if (params.isNotEmpty) {
      url += '?${params.join('&')}';
    }

    var response = await http.get(
      Uri.parse('$baseUrl$url'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      await savePdfFile(response.bodyBytes, 'releve-compte-$compteId.pdf');
    } else {
      var error = json.decode(response.body);
      print('Erreur: ${error['error']}');
    }
  } catch (e) {
    print('Erreur réseau: $e');
  }
}
```

#### **Export rapport financier**
```dart
Future<void> exportRapportFinancier({String? dateDebut, String? dateFin}) async {
  try {
    String url = '/api/export/rapport-financier';
    List<String> params = [];
    
    if (dateDebut != null) params.add('date_debut=$dateDebut');
    if (dateFin != null) params.add('date_fin=$dateFin');
    
    if (params.isNotEmpty) {
      url += '?${params.join('&')}';
    }

    var response = await http.get(
      Uri.parse('$baseUrl$url'),
      headers: {
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      await savePdfFile(response.bodyBytes, 'rapport-financier.pdf');
    } else {
      var error = json.decode(response.body);
      print('Erreur: ${error['error']}');
    }
  } catch (e) {
    print('Erreur réseau: $e');
  }
}
```

### **cURL**

#### **Export relevé de compte**
```bash
# Relevé de compte avec dates
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/export/releve-compte/1?date_debut=2025-01-01&date_fin=2025-01-31" \
  --output releve-compte-1.pdf

# Relevé de compte sans dates (30 derniers jours)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/export/releve-compte/1" \
  --output releve-compte-1.pdf
```

#### **Export rapport financier**
```bash
# Rapport financier avec dates
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/export/rapport-financier?date_debut=2025-01-01&date_fin=2025-01-31" \
  --output rapport-financier.pdf

# Rapport financier sans dates (30 derniers jours)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  "https://api.example.com/api/export/rapport-financier" \
  --output rapport-financier.pdf
```

## ⚙️ Configuration technique

### **Technologies utilisées**
- **DomPDF** : Génération de PDF à partir de HTML
- **Twig** : Moteur de templates pour le rendu HTML
- **Symfony** : Framework PHP
- **CSS3** : Styles pour le rendu PDF

### **Options DomPDF optimisées**
```php
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isJavascriptEnabled', false);
$options->set('debugKeepTemp', false);
$options->set('debugCss', false);
$options->set('debugLayout', false);
$options->set('fontCache', sys_get_temp_dir());
$options->set('tempDir', sys_get_temp_dir());
$options->set('chroot', realpath(__DIR__ . '/../../public'));
$options->set('defaultMediaType', 'print');
$options->set('defaultPaperSize', 'A4');
$options->set('defaultPaperOrientation', 'portrait');
$options->set('dpi', 96);
$options->set('fontHeightRatio', 1.1);
```

### **Templates disponibles**
- `templates/pdf/releve_compte.html.twig` : Template du relevé de compte
- `templates/pdf/rapport_financier.html.twig` : Template du rapport financier

## 📊 Codes de statut HTTP

| Code | Signification | Description |
|------|---------------|-------------|
| 200 | OK | PDF généré avec succès |
| 400 | Bad Request | Paramètres invalides |
| 401 | Unauthorized | Non authentifié |
| 404 | Not Found | Ressource non trouvée |
| 500 | Internal Server Error | Erreur lors de la génération |

## 🔧 Maintenance et développement

### **Ajout de nouveaux formats**
1. Créer un nouveau template Twig dans `templates/pdf/`
2. Ajouter une méthode dans `PdfExportService`
3. Créer un endpoint dans `ExportController`
4. Mettre à jour la liste des formats disponibles

### **Personnalisation du design**
- Modifier les templates Twig dans `templates/pdf/`
- Ajuster les styles CSS dans les templates
- Tester avec DomPDF pour vérifier le rendu

### **Optimisation des performances**
- Cache des templates Twig
- Optimisation des requêtes de base de données
- Compression des PDF générés

---

**Version** : 1.0  
**Dernière mise à jour** : 25 Janvier 2025  
**Auteur** : Équipe SoldeTrack API
