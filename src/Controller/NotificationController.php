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
     * Tester l'envoi d'une notification
     */
    #[Route('/test', name: 'test', methods: ['POST'])]
    public function testNotification(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $type = $data['type'] ?? 'FUN_MOTIVATION';
        $testData = $data['data'] ?? [];

        // Données de test par défaut
        if (empty($testData)) {
            switch ($type) {
                case 'DEBT_REMINDER':
                    $testData = [
                        'amount' => 50000,
                        'name' => 'Rodrigue',
                        'due_date' => '2025-09-20',
                        'days_left' => -2
                    ];
                    break;
                case 'EXPENSE_REMINDER':
                    $testData = [
                        'amount' => 15000,
                        'days' => 1
                    ];
                    break;
                case 'INCOME_ALERT':
                    $testData = [
                        'amount' => 100000,
                        'source' => 'Salaire'
                    ];
                    break;
                case 'PROJECT_ALERT':
                    $testData = [
                        'project_name' => 'Projet Volaille',
                        'percentage' => 15,
                        'amount' => 25000
                    ];
                    break;
                case 'FUN_MOTIVATION':
                    $testData = [
                        'streak' => 7,
                        'total_saved' => 75000,
                        'category' => 'général'
                    ];
                    break;
                case 'BALANCE_ALERT':
                    $testData = [
                        'balance' => 5000,
                        'account_name' => 'Compte Principal'
                    ];
                    break;
            }
        }

        $success = $this->notificationService->sendNotification($user, $type, $testData);

        if ($success) {
            return new JsonResponse([
                'message' => 'Notification de test envoyée avec succès',
                'type' => $type,
                'data' => $testData
            ]);
        } else {
            return new JsonResponse([
                'error' => 'Échec de l\'envoi de la notification de test'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
