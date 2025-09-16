# 🔥 Configuration Firebase Cloud Messaging v1 API

## 📋 Étapes de configuration

### 1. Créer un projet Firebase
1. Aller sur [Firebase Console](https://console.firebase.google.com/)
2. Cliquer sur "Créer un projet"
3. Suivre les étapes de configuration
4. Noter l'**ID du projet** (ex: `soldetrack`) - Utiliser l'ID de la Firebase Console, pas celui de Google Cloud

### 2. Activer Cloud Messaging
1. Dans le projet Firebase, aller dans **"Messaging"**
2. Cliquer sur **"Commencer"**
3. Activer les notifications

### 3. Générer un token d'accès (Access Token)

#### Option A : Via Google Cloud Console (Recommandé)
1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Sélectionner votre projet Firebase (utiliser l'ID `soldetrack`)
3. Aller dans **"IAM & Admin" > "Service Accounts"**
4. Cliquer sur **"Create Service Account"**
5. Donner un nom (ex: `fcm-service`)
6. Rôle : **"Firebase Cloud Messaging Admin"**
7. Cliquer sur **"Create Key"** > **"JSON"**
8. Télécharger le fichier JSON

#### Option B : Via Firebase CLI
```bash
# Installer Firebase CLI
npm install -g firebase-tools

# Se connecter
firebase login

# Générer un token
firebase projects:list
firebase use your-project-id
```

### 4. Configuration Symfony

#### Variables d'environnement
```bash
# .env
FCM_ACCESS_TOKEN=ya29.ElqKBGN2Ri_Uz...HnS_uNreA
FCM_PROJECT_ID=soldetrack
```

#### Récupérer le token d'accès
```bash
# Via gcloud CLI
gcloud auth application-default print-access-token

# Ou via le fichier JSON téléchargé
# Le token est dans le champ "access_token"
```

### 5. Structure de l'URL FCM v1
```
https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
```

Exemple avec votre projet :
```
https://fcm.googleapis.com/v1/projects/soldetrack/messages:send
```

## 🚀 Test de la configuration

### Test avec cURL
```bash
curl -X POST https://fcm.googleapis.com/v1/projects/soldetrack/messages:send \
  -H "Authorization: Bearer ya29.ElqKBGN2Ri_Uz...HnS_uNreA" \
  -H "Content-Type: application/json" \
  -d '{
    "message": {
      "token": "DEVICE_TOKEN_HERE",
      "notification": {
        "title": "Test SoldeTrack",
        "body": "Notification de test !"
      },
      "data": {
        "type": "TEST"
      }
    }
  }'
```

### Test via l'API SoldeTrack
```bash
curl -X POST https://your-api.com/api/notifications/test \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{"type": "FUN_MOTIVATION"}'
```

## 🔧 Avantages de l'API FCM v1

### ✅ Fonctionnalités avancées
- **Support multi-plateforme** : Android, iOS, Web
- **Démarrage direct Android** : `direct_boot_ok: true`
- **Canaux de notification** : `channel_id: 'solde_track_notifications'`
- **Priorité haute** : `priority: 'high'`
- **Badges et sons** : Configuration complète

### ✅ Sécurité améliorée
- **Tokens d'accès** : Plus sécurisé que les clés serveur
- **Expiration automatique** : Tokens renouvelés automatiquement
- **Audit trail** : Traçabilité des envois

### ✅ Performance
- **API moderne** : Plus rapide et fiable
- **Retry automatique** : Gestion des erreurs améliorée
- **Analytics** : Métriques détaillées

## 📱 Configuration Flutter

### 1. Installation
```yaml
# pubspec.yaml
dependencies:
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
```

### 2. Configuration
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

### 3. Enregistrer le token
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

## 🔍 Dépannage

### Erreurs courantes

#### 401 Unauthorized
```json
{
  "error": {
    "code": 401,
    "message": "Request had invalid authentication credentials."
  }
}
```
**Solution** : Vérifier le token d'accès et le project ID

#### 403 Forbidden
```json
{
  "error": {
    "code": 403,
    "message": "The caller does not have permission."
  }
}
```
**Solution** : Vérifier les permissions du service account

#### 400 Bad Request
```json
{
  "error": {
    "code": 400,
    "message": "The registration token is not a valid FCM registration token."
  }
}
```
**Solution** : Vérifier que le token FCM est valide et récent

### Logs de débogage
```bash
# Voir les logs Symfony
tail -f var/log/dev.log | grep "FCM"

# Voir les logs Firebase
# Dans Firebase Console > Messaging > Reports
```

## 📊 Monitoring

### Métriques disponibles
- **Taux de livraison** : Pourcentage de notifications livrées
- **Taux d'ouverture** : Pourcentage de notifications ouvertes
- **Erreurs** : Types d'erreurs et fréquences
- **Plateformes** : Répartition Android/iOS/Web

### Dashboard Firebase
1. Aller dans **Firebase Console**
2. **Messaging** > **Reports**
3. Voir les statistiques détaillées

## 🚀 Déploiement

### Variables d'environnement de production
```bash
# .env.prod
FCM_ACCESS_TOKEN=ya29.production_token_here
FCM_PROJECT_ID=soldetrack
```

### Cron Job
```bash
# Vérifier les notifications toutes les heures
0 * * * * cd /path/to/project && php bin/console app:notification:check
```

### Health Check
```bash
# Vérifier la configuration
php bin/console app:notification:check
```
