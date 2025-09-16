# 🔔 API Notifications Push - SoldeTrack

## 📋 Vue d'ensemble

L'API de notifications push permet d'envoyer des notifications contextuelles et motivantes aux utilisateurs via Firebase Cloud Messaging (FCM).

## 🎯 Types de notifications

### 1. 💸 Rappel de dette (DEBT_REMINDER)
- **Déclencheur** : Dette en retard de remboursement
- **Exemple** : "😅 Hé boss, Rodrigue attend ses 50 000 XOF avant le 20/09. On s'active ?"

### 2. 📝 Rappel de dépense (EXPENSE_REMINDER)
- **Déclencheur** : Dépense non notée depuis plusieurs jours
- **Exemple** : "👀 Hé, ton portefeuille est triste… note ta dépense d'hier !"

### 3. 💰 Alerte de revenu (INCOME_ALERT)
- **Déclencheur** : Nouveau revenu enregistré
- **Exemple** : "💰 Nouveau revenu ! 100 000 XOF de Salaire !"

### 4. ⚠️ Alerte de projet (PROJECT_ALERT)
- **Déclencheur** : Budget de projet dépassé
- **Exemple** : "🐔 Projet Volaille → attention, budget dépassé de 15% !"

### 5. 🎉 Motivation (FUN_MOTIVATION)
- **Déclencheur** : Streak de suivi, économies, etc.
- **Exemple** : "🔥 Bravo ! Tu as suivi tes dépenses 7 jours de suite !"

### 6. 💰 Alerte de solde (BALANCE_ALERT)
- **Déclencheur** : Solde de compte bas
- **Exemple** : "⚠️ Solde Compte Principal : 5 000 XOF. On fait attention ?"

## 🚀 Endpoints

### Enregistrer le token FCM
```http
POST /api/notifications/register-token
Authorization: Bearer {token}
Content-Type: application/json

{
  "fcm_token": "fcm_token_here"
}
```

**Réponse :**
```json
{
  "message": "Token FCM enregistré avec succès",
  "fcm_token": "fcm_token_here"
}
```

### Tester une notification
```http
POST /api/notifications/test
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "FUN_MOTIVATION",
  "data": {
    "streak": 7,
    "total_saved": 75000,
    "category": "général"
  }
}
```

**Réponse :**
```json
{
  "message": "Notification de test envoyée avec succès",
  "type": "FUN_MOTIVATION",
  "data": {
    "streak": 7,
    "total_saved": 75000,
    "category": "général"
  }
}
```

### Vérifier les dettes en retard
```http
POST /api/notifications/check-debts
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "message": "Vérification des dettes terminée",
  "notifications_sent": 3
}
```

### Vérifier les projets en dépassement
```http
POST /api/notifications/check-projects
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "message": "Vérification des projets terminée",
  "notifications_sent": 1
}
```

### Envoyer une notification de motivation
```http
POST /api/notifications/motivation
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "message": "Notification de motivation envoyée avec succès"
}
```

### Obtenir les types de notifications
```http
GET /api/notifications/types
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "types": {
    "DEBT_REMINDER": {
      "name": "Rappel de dette",
      "description": "Notifications pour les dettes en retard",
      "icon": "💸"
    },
    "EXPENSE_REMINDER": {
      "name": "Rappel de dépense",
      "description": "Notifications pour les dépenses non notées",
      "icon": "📝"
    },
    "INCOME_ALERT": {
      "name": "Alerte de revenu",
      "description": "Notifications pour les nouveaux revenus",
      "icon": "💰"
    },
    "PROJECT_ALERT": {
      "name": "Alerte de projet",
      "description": "Notifications pour les projets en dépassement",
      "icon": "⚠️"
    },
    "FUN_MOTIVATION": {
      "name": "Motivation",
      "description": "Notifications de motivation et encouragement",
      "icon": "🎉"
    },
    "BALANCE_ALERT": {
      "name": "Alerte de solde",
      "description": "Notifications pour les soldes de comptes",
      "icon": "💰"
    }
  }
}
```

## 🔧 Configuration

### Variables d'environnement
```bash
# .env
FCM_SERVER_KEY=your_firebase_server_key_here
```

### Firebase Cloud Messaging
1. Créer un projet Firebase
2. Activer Cloud Messaging
3. Générer une clé serveur
4. Configurer la clé dans `.env`

## 🤖 Automatisation

### Commande Symfony
```bash
# Vérifier et envoyer les notifications automatiques
php bin/console app:notification:check
```

### Cron Job (recommandé)
```bash
# Vérifier toutes les heures
0 * * * * cd /path/to/project && php bin/console app:notification:check

# Vérifier tous les jours à 9h
0 9 * * * cd /path/to/project && php bin/console app:notification:check
```

## 📱 Intégration Flutter

### 1. Configuration Firebase
```dart
// main.dart
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  
  // Demander la permission
  FirebaseMessaging messaging = FirebaseMessaging.instance;
  NotificationSettings settings = await messaging.requestPermission(
    alert: true,
    badge: true,
    sound: true,
  );
  
  runApp(MyApp());
}
```

### 2. Enregistrer le token FCM
```dart
// services/notification_service.dart
class NotificationService {
  static Future<void> registerFCMToken() async {
    FirebaseMessaging messaging = FirebaseMessaging.instance;
    String? token = await messaging.getToken();
    
    if (token != null) {
      // Envoyer le token au backend
      await ApiService.post('/api/notifications/register-token', {
        'fcm_token': token,
      });
    }
  }
}
```

### 3. Gérer les notifications reçues
```dart
// services/notification_service.dart
class NotificationService {
  static void setupNotificationHandlers() {
    FirebaseMessaging.onMessage.listen((RemoteMessage message) {
      // Notification reçue en foreground
      _showNotification(message);
    });
    
    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      // Notification cliquée (app en background)
      _handleNotificationTap(message);
    });
  }
  
  static void _handleNotificationTap(RemoteMessage message) {
    String type = message.data['type'] ?? '';
    
    switch (type) {
      case 'DEBT_REMINDER':
        // Ouvrir la page des dettes
        Get.toNamed('/debts');
        break;
      case 'PROJECT_ALERT':
        // Ouvrir la page du projet
        String projectId = message.data['project_id'] ?? '';
        Get.toNamed('/projects/$projectId');
        break;
      case 'INCOME_ALERT':
        // Ouvrir la page des mouvements
        Get.toNamed('/movements');
        break;
      default:
        // Ouvrir le dashboard
        Get.toNamed('/dashboard');
    }
  }
}
```

## 🎨 Personnalisation des messages

### Templates de messages
Les messages sont générés dynamiquement avec :
- **Emojis** : Pour rendre les notifications plus attrayantes
- **Humor local** : Style génération Z, expressions familières
- **Variations** : Plusieurs versions pour éviter la répétition
- **Contexte** : Informations spécifiques (montants, noms, dates)

### Exemples de messages
```php
// Dette en retard (devise de l'utilisateur)
"😅 Hé boss, Rodrigue attend encore 50 000 USD. Échéance le 20/09 !"
"⚠️ Oups ! 50 000 USD à rembourser à Rodrigue. Dépêche-toi !"

// Rappel de dépense
"💸 N'oublie pas de noter ta dépense d'hier !"
"👀 Hé, ton portefeuille est triste… note ta dernière dépense !"

// Nouveau revenu (devise de l'utilisateur)
"💵 Félicitations ! Tu as reçu 100 000 EUR."
"🤑 Nouveau revenu : 100 000 EUR ajouté à ton solde !"

// Projet en dépassement (devise de l'utilisateur)
"🐔 Projet Volaille : attention, budget dépassé !"
"⚠️ Dépense sur Projet Volaille → surveille ton solde !"

// Motivation
"🔥 Bravo ! 5 jours de suivi parfait, tu gères !"
"😎 Tu es un vrai boss du cash 💰 !"

// Alerte de solde (devise de l'utilisateur)
"⚠️ Attention, ton solde est bas : 5 000 USD restant !"
"💸 Solde actuel : 5 000 USD. Prudence sur tes dépenses !"
```

### 🌍 Support multi-devises
Les notifications respectent automatiquement la devise de l'utilisateur :
- **XOF** : Franc CFA (par défaut)
- **USD** : Dollar américain
- **EUR** : Euro
- **CAD** : Dollar canadien
- **GBP** : Livre sterling
- **XAF** : Franc CFA BEAC

## 🔍 Monitoring et logs

### Logs Symfony
```bash
# Voir les logs de notifications
tail -f var/log/dev.log | grep "Notification"
```

### Métriques
- Nombre de notifications envoyées
- Taux de succès d'envoi
- Types de notifications les plus utilisées
- Réactivité des utilisateurs

## 🚨 Gestion des erreurs

### Erreurs courantes
1. **Token FCM invalide** : L'utilisateur doit se reconnecter
2. **Clé serveur incorrecte** : Vérifier la configuration Firebase
3. **Quota dépassé** : Limiter le nombre de notifications par utilisateur

### Fallback
Si FCM échoue, les notifications peuvent être stockées en base et affichées dans l'app.

## 📊 Statistiques

### Dashboard de notifications
- Notifications envoyées par jour/semaine/mois
- Taux d'ouverture par type
- Utilisateurs les plus actifs
- Dettes les plus en retard

## 🔐 Sécurité

### Bonnes pratiques
- Valider les tokens FCM
- Limiter le nombre de notifications par utilisateur
- Chiffrer les données sensibles
- Respecter les préférences utilisateur

### RGPD
- Consentement explicite pour les notifications
- Possibilité de désactiver les notifications
- Suppression des tokens lors de la suppression du compte
