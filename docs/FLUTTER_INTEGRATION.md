# Intégration Flutter

## 1. Configuration de base

- **Base URL dev** : `https://votre-domaine.test/api/v1` ou URL locale tunnel.
- **Headers** : `Accept: application/json`, `Content-Type: application/json`.
- **Auth** : `Authorization: Bearer TOKEN` après login/register.
- **Stockage token** : utiliser `flutter_secure_storage`, pas `SharedPreferences` pour un token sensible.

## 2. Format de réponse standard

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

```json
{
  "success": false,
  "message": "...",
  "errors": {}
}
```

Lecture côté Flutter :

- `success == true` : lire `data`.
- `success == false` : afficher `message`, puis les erreurs champ par champ si `errors` est non vide.
- Ne pas dépendre d’un texte exact de `message` pour la logique métier ; utiliser plutôt le code HTTP.

## 3. Auth flow Flutter

1. `POST /auth/register` ou `POST /auth/login`.
2. Lire `data.token`.
3. Sauvegarder le token dans un stockage sécurisé.
4. Instancier le client API avec Bearer token.
5. Appeler `GET /me`.
6. Au logout : `POST /auth/logout`, puis supprimer le token local.

## 4. Game flow Flutter

1. `GET /game/map` pour afficher régions/villes/niveaux.
2. `POST /levels/{level}/start` pour démarrer.
3. Afficher les questions retournées.
4. À chaque réponse : `POST /game-sessions/{session}/answer` avec `question_id`, `answer_id`, `response_time`.
5. Afficher le résultat de réponse renvoyé par Laravel.
6. Après toutes les questions : `POST /game-sessions/{session}/finish`.
7. Afficher le résumé final.
8. Rafraîchir `/lives`, `/streak`, `/league/current`, `/me`, et selon les unlocks `/chests`, `/collection`, `/passport`.

## 5. Gestion des erreurs

| HTTP | Signification | Action Flutter |
| --- | --- | --- |
| `401` | Token absent/expiré/invalide | Supprimer token, rediriger login. |
| `403` | Accès interdit, niveau verrouillé, ressource non propriétaire | Afficher message et resynchroniser l’état. |
| `404` | Ressource introuvable | Revenir à l’écran précédent ou refresh map. |
| `422` | Validation | Afficher erreurs de formulaire. |
| `429` | Rate limit | Désactiver bouton et retenter plus tard. |
| `500` | Erreur serveur | Message générique, logging Crashlytics/Sentry. |

## 6. Exemple Dart avec Dio

### ApiResponse générique

```dart
class ApiResponse<T> {
  final bool success;
  final String message;
  final T? data;
  final Map<String, dynamic>? errors;

  ApiResponse({
    required this.success,
    required this.message,
    this.data,
    this.errors,
  });

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic raw)? parseData,
  ) {
    return ApiResponse<T>(
      success: json['success'] == true,
      message: json['message']?.toString() ?? '',
      data: parseData != null && json.containsKey('data')
          ? parseData(json['data'])
          : null,
      errors: json['errors'] is Map<String, dynamic>
          ? json['errors'] as Map<String, dynamic>
          : null,
    );
  }
}
```

### ApiException

```dart
class ApiException implements Exception {
  final int? statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException(this.message, {this.statusCode, this.errors});

  @override
  String toString() => 'ApiException($statusCode): $message';
}
```

### ApiClient

```dart
import 'package:dio/dio.dart';

class ApiClient {
  final Dio dio;

  ApiClient(String baseUrl, String? token)
      : dio = Dio(BaseOptions(baseUrl: baseUrl)) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          options.headers['Accept'] = 'application/json';
          options.headers['Content-Type'] = 'application/json';
          handler.next(options);
        },
        onError: (error, handler) {
          final response = error.response;
          final body = response?.data;
          if (body is Map<String, dynamic>) {
            throw ApiException(
              body['message']?.toString() ?? 'Erreur API',
              statusCode: response?.statusCode,
              errors: body['errors'] is Map<String, dynamic>
                  ? body['errors'] as Map<String, dynamic>
                  : null,
            );
          }
          throw ApiException(
            'Erreur réseau ou serveur',
            statusCode: response?.statusCode,
          );
        },
      ),
    );
  }
}
```

### AuthApi

```dart
class AuthApi {
  final Dio _dio;

  AuthApi(ApiClient client) : _dio = client.dio;

  Future<ApiResponse<Map<String, dynamic>>> login({
    required String email,
    required String password,
  }) async {
    final response = await _dio.post('/auth/login', data: {
      'email': email,
      'password': password,
    });

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
  }

  Future<ApiResponse<Map<String, dynamic>>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) async {
    final response = await _dio.post('/auth/register', data: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
    });

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
  }

  Future<void> logout() async {
    await _dio.post('/auth/logout');
  }
}
```

### GameApi

```dart
class GameApi {
  final Dio _dio;

  GameApi(ApiClient client) : _dio = client.dio;

  Future<Map<String, dynamic>> getMap() async {
    final response = await _dio.get('/game/map');
    final api = ApiResponse<Map<String, dynamic>>.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
    return api.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> startSession(int levelId) async {
    final response = await _dio.post('/levels/$levelId/start');
    final api = ApiResponse<Map<String, dynamic>>.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
    return api.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> submitAnswer({
    required int sessionId,
    required int questionId,
    required int answerId,
    required int responseTime,
  }) async {
    final response = await _dio.post('/game-sessions/$sessionId/answer', data: {
      'question_id': questionId,
      'answer_id': answerId,
      'response_time': responseTime,
    });

    final api = ApiResponse<Map<String, dynamic>>.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
    return api.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> finishSession(int sessionId) async {
    final response = await _dio.post('/game-sessions/$sessionId/finish');
    final api = ApiResponse<Map<String, dynamic>>.fromJson(
      response.data as Map<String, dynamic>,
      (raw) => raw as Map<String, dynamic>,
    );
    return api.data ?? <String, dynamic>{};
  }
}
```

### Lecture des erreurs dans l’UI

```dart
try {
  final result = await gameApi.submitAnswer(
    sessionId: 50,
    questionId: 100,
    answerId: 501,
    responseTime: 8,
  );
  final isCorrect = result['is_correct'] == true;
  // Afficher correction serveur.
} on ApiException catch (e) {
  if (e.statusCode == 401) {
    // Rediriger login.
  } else if (e.statusCode == 422) {
    // Afficher e.errors.
  } else {
    // SnackBar(e.message).
  }
}
```

## 7. Conseils Flutter

- Ne jamais calculer le score côté Flutter.
- Ne jamais envoyer XP, coins, gems, score ou `is_correct`.
- Ne jamais stocker les bonnes réponses localement.
- Ne pas exposer `is_correct` avant la réponse serveur.
- Utiliser un cache local seulement pour améliorer l’affichage.
- Toujours resynchroniser après fin de session.
- Désactiver les boutons après tap pour éviter les doubles soumissions.
- Gérer le mode hors-ligne plus tard, pas en V1.
