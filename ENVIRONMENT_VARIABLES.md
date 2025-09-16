# Variables d'environnement requises

## Configuration de base

Créez un fichier `.env` à la racine du projet avec les variables suivantes :

```bash
# Database Configuration
DATABASE_URL="mysql://root:@127.0.0.1:3306/solde_track?serverVersion=8.0.32&charset=utf8mb4"

# JWT Configuration
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_jwt_passphrase_here

# Firebase Cloud Messaging v1 API
FCM_ACCESS_TOKEN=your_fcm_access_token_here
FCM_PROJECT_ID=soldetrack

# Application Configuration
APP_ENV=dev
APP_SECRET=your_app_secret_here

# Mailer Configuration (optional)
MAILER_DSN=null://null

# CORS Configuration
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## Configuration Firebase

1. **Obtenez votre token FCM** :
   - Allez sur [Firebase Console](https://console.firebase.google.com/)
   - Sélectionnez votre projet `soldetrack`
   - Générez une clé de service account
   - Utilisez le script pour générer le token d'accès

2. **Placez le fichier de clé** :
   - Copiez `firebase-service-account.json` dans `config/firebase/`
   - Ce fichier est automatiquement ignoré par Git

## Sécurité

- ✅ Le fichier `.env` est ignoré par Git
- ✅ Les clés Firebase sont ignorées par Git
- ✅ Les clés JWT sont ignorées par Git
- ✅ Ne jamais commiter de données sensibles

## Déploiement

Sur votre serveur de production (LWS), créez le fichier `.env` avec vos vraies valeurs :

```bash
# Production
FCM_ACCESS_TOKEN=votre_vrai_token_fcm
FCM_PROJECT_ID=soldetrack
DATABASE_URL=mysql://user:password@localhost:3306/solde_track_prod
APP_ENV=prod
APP_SECRET=votre_secret_production
```
