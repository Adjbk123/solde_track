# Configuration CORS - SoldeTrack API

## ✅ CORS installé et configuré !

Le bundle **NelmioCorsBundle** a été installé et configuré pour permettre les requêtes cross-origin depuis Flutter.

## 🔧 Configuration actuelle

### Fichier : `config/packages/nelmio_cors.yaml`

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        # Autoriser toutes les origines en développement
        allow_origin: ['^https?://(localhost|127\.0\.0\.1|0\.0\.0\.0)(:[0-9]+)?$']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin']
        expose_headers: ['Link']
        max_age: 3600
        allow_credentials: true
    paths:
        '^/api/': ~
```

## 🌐 Origines autorisées (Développement)

- ✅ `http://localhost:*` (tous les ports)
- ✅ `https://localhost:*` (tous les ports)
- ✅ `http://127.0.0.1:*` (tous les ports)
- ✅ `https://127.0.0.1:*` (tous les ports)
- ✅ `http://0.0.0.0:*` (tous les ports)
- ✅ `https://0.0.0.0:*` (tous les ports)

## 📱 Méthodes HTTP autorisées

- ✅ `GET` - Récupérer des données
- ✅ `POST` - Créer des ressources
- ✅ `PUT` - Mettre à jour des ressources
- ✅ `PATCH` - Mise à jour partielle
- ✅ `DELETE` - Supprimer des ressources
- ✅ `OPTIONS` - Pré-vérification CORS

## 🔑 Headers autorisés

- ✅ `Content-Type` - Type de contenu
- ✅ `Authorization` - Token JWT
- ✅ `X-Requested-With` - Requête AJAX
- ✅ `Accept` - Types acceptés
- ✅ `Origin` - Origine de la requête

## 🚀 Utilisation avec Flutter

### Exemple de requête Flutter :

```dart
import 'package:http/http.dart' as http;

class ApiService {
  static const String baseUrl = 'http://localhost:8000/api';
  
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );
    
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Erreur de connexion');
    }
  }
  
  static Future<Map<String, dynamic>> getProfile(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/profile'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      throw Exception('Erreur de récupération du profil');
    }
  }
}
```

## 🔒 Configuration pour la production

Pour la production, modifiez le fichier `config/packages/nelmio_cors.yaml` :

```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        # Spécifiez vos domaines de production
        allow_origin: ['^https?://(votre-domaine\.com|app\.votre-domaine\.com)$']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin']
        expose_headers: ['Link']
        max_age: 3600
        allow_credentials: true
    paths:
        '^/api/': ~
```

## 🧪 Test de la configuration CORS

### Test avec curl :

```bash
# Test de pré-vérification CORS
curl -X OPTIONS \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  http://localhost:8000/api/auth/login

# Test de requête réelle
curl -X POST \
  -H "Origin: http://localhost:3000" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  http://localhost:8000/api/auth/login
```

### Test avec Flutter :

```dart
// Test simple de connexion
void testCors() async {
  try {
    final response = await http.get(
      Uri.parse('http://localhost:8000/api/auth/devises'),
      headers: {
        'Accept': 'application/json',
      },
    );
    
    print('Status: ${response.statusCode}');
    print('Headers: ${response.headers}');
    print('Body: ${response.body}');
  } catch (e) {
    print('Erreur CORS: $e');
  }
}
```

## ⚠️ Notes importantes

1. **Développement** : Configuration permissive pour faciliter le développement
2. **Production** : Restreindre aux domaines autorisés uniquement
3. **Credentials** : `allow_credentials: true` permet l'envoi de cookies/auth
4. **Cache** : `max_age: 3600` cache les pré-vérifications CORS pendant 1h

## 🎯 Prochaines étapes

1. ✅ CORS installé et configuré
2. ✅ Configuration adaptée pour Flutter
3. ✅ Headers et méthodes autorisés
4. ✅ Test avec votre application Flutter

**Votre API est maintenant prête pour Flutter !** 🚀📱
