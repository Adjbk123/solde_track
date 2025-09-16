<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\NotificationService;
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
        private NotificationService $notificationService
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
            // Test avec un message simple
            $testMessage = [
                'title' => '🧪 Test SoldeTrack',
                'body' => 'Notification de test envoyée avec succès !',
                'data' => [
                    'type' => 'TEST',
                    'timestamp' => time()
                ]
            ];

            $success = $this->notificationService->sendTestNotification($fcmToken, $testMessage);

            if ($success) {
                return new JsonResponse([
                    'message' => 'Notification de test envoyée avec succès',
                    'fcm_token' => substr($fcmToken, 0, 20) . '...'
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
            $success = $this->notificationService->sendMotivationNotification($user);

            if ($success) {
                return new JsonResponse([
                    'message' => 'Notification de motivation envoyée avec succès'
                ]);
            } else {
                // Vérifier si c'est à cause du token FCM manquant
                if (!$user->getFcmToken()) {
                    return new JsonResponse([
                        'message' => 'Token FCM non enregistré. Veuillez enregistrer votre token FCM d\'abord.',
                        'requires_fcm_token' => true
                    ], Response::HTTP_BAD_REQUEST);
                } else {
                    return new JsonResponse([
                        'error' => 'Échec de l\'envoi de la notification de motivation'
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
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

        $diagnostic = [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'fcm_token_registered' => !empty($user->getFcmToken()),
            'fcm_token_length' => $user->getFcmToken() ? strlen($user->getFcmToken()) : 0,
            'fcm_token_preview' => $user->getFcmToken() ? substr($user->getFcmToken(), 0, 20) . '...' : null,
            'user_currency' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF',
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
}
