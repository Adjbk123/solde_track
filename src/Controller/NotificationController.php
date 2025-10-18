<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use App\Service\PushNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications', name: 'notifications_')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private PushNotificationService $pushNotificationService,
        private NotificationRepository $notificationRepository
    ) {}

    /**
     * Enregistrer le token FCM de l'utilisateur
     */
    #[Route('/register-token', name: 'register_token', methods: ['POST'])]
    public function registerToken(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $fcmToken = $data['fcm_token'] ?? null;

        if (!$fcmToken) {
            return new JsonResponse([
                'error' => 'Token FCM requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->setFcmToken($fcmToken);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Token FCM enregistré avec succès',
            'fcm_token' => $fcmToken
        ]);
    }


    /**
     * Vérifier et envoyer les rappels de dettes
     */
    #[Route('/check-debts', name: 'check_debts', methods: ['POST'])]
    public function checkDebtReminders(): JsonResponse
    {
        $sentCount = $this->notificationService->checkAndSendDebtReminders();

        return new JsonResponse([
            'message' => "Vérification des dettes terminée",
            'notifications_sent' => $sentCount
        ]);
    }

    /**
     * Vérifier et envoyer les alertes de projets
     */
    #[Route('/check-projects', name: 'check_projects', methods: ['POST'])]
    public function checkProjectAlerts(): JsonResponse
    {
        $sentCount = $this->notificationService->checkAndSendProjectAlerts();

        return new JsonResponse([
            'message' => "Vérification des projets terminée",
            'notifications_sent' => $sentCount
        ]);
    }

    /**
     * Tester l'envoi de notification avec un token FCM
     */
    #[Route('/test', name: 'test', methods: ['POST'])]
    public function testNotification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $fcmToken = $data['fcm_token'] ?? null;

        if (!$fcmToken) {
            return new JsonResponse([
                'error' => 'Token FCM requis pour le test'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Test avec le nouveau service Firebase
            $success = $this->pushNotificationService->sendNotification(
                $fcmToken, 
                '🧪 Test SoldeTrack', 
                'Notification de test envoyée avec succès !',
                [
                    'type' => 'TEST',
                    'timestamp' => time()
                ]
            );

            if ($success) {
                return new JsonResponse([
                    'message' => 'Notification de test envoyée avec succès (Firebase)',
                    'fcm_token' => substr($fcmToken, 0, 20) . '...',
                    'service' => 'kreait/firebase-php'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Échec de l\'envoi de la notification de test'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors du test: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Envoyer une notification de motivation
     */
    #[Route('/motivation', name: 'motivation', methods: ['POST'])]
    public function sendMotivationNotification(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Vérifier si le token FCM est disponible
            if (!$user->getFcmToken()) {
                return new JsonResponse([
                    'message' => 'Token FCM non enregistré. Veuillez enregistrer votre token FCM d\'abord.',
                    'requires_fcm_token' => true
                ], Response::HTTP_BAD_REQUEST);
            }

            // Utiliser le nouveau service Firebase
            $currency = $user->getDevise() ? $user->getDevise()->getCode() : 'XOF';
            $success = $this->pushNotificationService->sendMotivationNotification(
                $user->getFcmToken(),
                $user->getNom() ?? $user->getEmail(),
                $currency
            );

            if ($success) {
                return new JsonResponse([
                    'message' => 'Notification de motivation envoyée avec succès',
                    'service' => 'kreait/firebase-php'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Échec de l\'envoi de la notification de motivation'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'envoi de la notification: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Diagnostic de la configuration FCM
     */
    #[Route('/diagnostic', name: 'diagnostic', methods: ['GET'])]
    public function diagnostic(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        // Test de connexion Firebase
        $firebaseTest = $this->pushNotificationService->testConnection();
        
        $diagnostic = [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'fcm_token_registered' => !empty($user->getFcmToken()),
            'fcm_token_length' => $user->getFcmToken() ? strlen($user->getFcmToken()) : 0,
            'fcm_token_preview' => $user->getFcmToken() ? substr($user->getFcmToken(), 0, 20) . '...' : null,
            'user_currency' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF',
            'firebase_connection' => $firebaseTest,
            'configuration' => [
                'fcm_project_id' => $this->notificationService->getProjectId(),
                'fcm_url' => $this->notificationService->getFcmUrl(),
                'access_token_configured' => $this->notificationService->isAccessTokenConfigured()
            ]
        ];

        return new JsonResponse($diagnostic);
    }

    /**
     * Obtenir les types de notifications disponibles
     */
    #[Route('/types', name: 'types', methods: ['GET'])]
    public function getNotificationTypes(): JsonResponse
    {
        $types = [
            'DEBT_REMINDER' => [
                'name' => 'Rappel de dette',
                'description' => 'Notifications pour les dettes en retard',
                'icon' => '💸'
            ],
            'EXPENSE_REMINDER' => [
                'name' => 'Rappel de dépense',
                'description' => 'Notifications pour les dépenses non notées',
                'icon' => '📝'
            ],
            'INCOME_ALERT' => [
                'name' => 'Alerte de revenu',
                'description' => 'Notifications pour les nouveaux revenus',
                'icon' => '💰'
            ],
            'PROJECT_ALERT' => [
                'name' => 'Alerte de projet',
                'description' => 'Notifications pour les projets en dépassement',
                'icon' => '⚠️'
            ],
            'FUN_MOTIVATION' => [
                'name' => 'Motivation',
                'description' => 'Notifications de motivation et encouragement',
                'icon' => '🎉'
            ],
            'BALANCE_ALERT' => [
                'name' => 'Alerte de solde',
                'description' => 'Notifications pour les soldes de comptes',
                'icon' => '💰'
            ]
        ];

        return new JsonResponse([
            'types' => $types
        ]);
    }

    /**
     * Récupérer les notifications de l'utilisateur avec pagination
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function getNotifications(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);
        $isRead = $request->query->get('is_read');
        
        // Convertir is_read en booléen si fourni
        $isReadFilter = null;
        if ($isRead !== null) {
            $isReadFilter = filter_var($isRead, FILTER_VALIDATE_BOOLEAN);
        }

        $notifications = $this->notificationRepository->findByUser($user, $page, $limit, $isReadFilter);
        
        $formattedNotifications = array_map(function (Notification $notification) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'data' => $notification->getData(),
                'is_read' => $notification->isIsRead(),
                'created_at' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'read_at' => $notification->getReadAt()?->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return new JsonResponse([
            'notifications' => $formattedNotifications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($formattedNotifications)
            ]
        ]);
    }

    /**
     * Récupérer le nombre de notifications non lues
     */
    #[Route('/unread-count', name: 'unread_count', methods: ['GET'])]
    public function getUnreadCount(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->notificationRepository->countUnreadByUser($user);

        return new JsonResponse([
            'count' => $count
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    #[Route('/{id}/read', name: 'mark_read', methods: ['PUT'])]
    public function markAsRead(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $notification->markAsRead();
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Notification marquée comme lue',
            'notification' => [
                'id' => $notification->getId(),
                'is_read' => $notification->isIsRead(),
                'read_at' => $notification->getReadAt()?->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    #[Route('/mark-all-read', name: 'mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $updatedCount = $this->notificationRepository->markAllAsReadByUser($user);

        return new JsonResponse([
            'message' => 'Toutes les notifications marquées comme lues',
            'updated_count' => $updatedCount
        ]);
    }

    /**
     * Supprimer une notification
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteNotification(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $notification = $this->notificationRepository->find($id);
        
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification non trouvée'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que la notification appartient à l'utilisateur
        if ($notification->getUser() !== $user) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Notification supprimée avec succès'
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     */
    #[Route('/delete-read', name: 'delete_read', methods: ['DELETE'])]
    public function deleteReadNotifications(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $deletedCount = $this->notificationRepository->deleteReadByUser($user);

        return new JsonResponse([
            'message' => 'Notifications lues supprimées',
            'deleted_count' => $deletedCount
        ]);
    }

    /**
     * Créer une notification de test
     */
    #[Route('/test', name: 'create_test', methods: ['POST'])]
    public function createTestNotification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? Notification::TYPE_SYSTEM;
        $title = $data['title'] ?? 'Test de notification';
        $message = $data['message'] ?? 'Ceci est une notification de test';
        $notificationData = $data['data'] ?? null;

        // Valider le type
        if (!in_array($type, array_keys(Notification::TYPES))) {
            $type = Notification::TYPE_SYSTEM;
        }

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setData($notificationData);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Notification de test créée avec succès',
            'notification' => [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'created_at' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
