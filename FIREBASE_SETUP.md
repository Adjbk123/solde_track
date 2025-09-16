# 🔥 Configuration Firebase Cloud Messaging

## 📋 Étapes de configuration

### 1. Créer un projet Firebase
1. Aller sur [Firebase Console](https://console.firebase.google.com/)
2. Cliquer sur "Créer un projet"
3. Suivre les étapes de configuration

### 2. Activer Cloud Messaging
1. Dans le projet Firebase, aller dans "Messaging"
2. Cliquer sur "Commencer"
3. Noter la clé serveur

### 3. Configuration Symfony
```bash
# .env
FCM_ACCESS_TOKEN=your_firebase_access_token_here
FCM_PROJECT_ID=your_firebase_project_id_here
```

### 4. Tester les notifications
```bash
# Envoyer une notification de test
curl -X POST https://your-api.com/api/notifications/test \
  -H "Authorization: Bearer your_jwt_token" \
  -H "Content-Type: application/json" \
  -d '{"type": "FUN_MOTIVATION"}'
```

## 🚀 Déploiement

### Variables d'environnement
- `FCM_ACCESS_TOKEN` : Token d'accès Firebase (Bearer token)
- `FCM_PROJECT_ID` : ID du projet Firebase
- `FCM_URL` : URL FCM v1 (https://fcm.googleapis.com/v1/projects/{project_id}/messages:send)

### Cron Job
```bash
# Vérifier les notifications toutes les heures
0 * * * * cd /path/to/project && php bin/console app:notification:check
```
