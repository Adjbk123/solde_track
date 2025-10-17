<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ResponseService
{
    /**
     * Crée une réponse de succès
     */
    public static function success(array $data = [], string $message = '', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        return new JsonResponse($response, $statusCode);
    }

    /**
     * Crée une réponse d'erreur
     */
    public static function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, array $details = []): JsonResponse
    {
        $response = [
            'success' => false,
            'erreur' => $message
        ];
        
        if (!empty($details)) {
            $response['details'] = $details;
        }
        
        return new JsonResponse($response, $statusCode);
    }

    /**
     * Crée une réponse d'erreur de validation
     */
    public static function validationError(array $errors): JsonResponse
    {
        return self::error('Données invalides', Response::HTTP_BAD_REQUEST, ['erreurs' => $errors]);
    }

    /**
     * Crée une réponse d'erreur d'authentification
     */
    public static function unauthorized(string $message = 'Non authentifié'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Crée une réponse d'erreur de ressource non trouvée
     */
    public static function notFound(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Crée une réponse d'erreur serveur
     */
    public static function serverError(string $message = 'Erreur serveur interne'): JsonResponse
    {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Crée une réponse de création réussie
     */
    public static function created(array $data = [], string $message = 'Créé avec succès'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Crée une réponse de mise à jour réussie
     */
    public static function updated(array $data = [], string $message = 'Mis à jour avec succès'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_OK);
    }

    /**
     * Crée une réponse de suppression réussie
     */
    public static function deleted(string $message = 'Supprimé avec succès'): JsonResponse
    {
        return self::success([], $message, Response::HTTP_OK);
    }

    /**
     * Crée une réponse avec pagination
     */
    public static function paginated(array $items, int $page, int $limit, int $total, string $itemName = 'items'): JsonResponse
    {
        return self::success([
            $itemName => $items,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}
