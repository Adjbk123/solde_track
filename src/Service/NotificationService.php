<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Mouvement;
use App\Entity\Dette;
use App\Entity\Depense;
use App\Entity\Projet;
use App\Entity\Compte;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private LoggerInterface $logger;
    private string $fcmAccessToken;
    private string $fcmProjectId;
    private string $fcmUrl = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->logger = $logger;
        $this->fcmAccessToken = $this->params->get('app.fcm_access_token');
        $this->fcmProjectId = $this->params->get('app.fcm_project_id');
    }

    /**
     * Envoie une notification push à un utilisateur
     */
    public function sendNotification(User $user, string $type, array $data = []): bool
    {
        if (!$user->getFcmToken()) {
            $this->logger->warning('Utilisateur sans token FCM', ['user_id' => $user->getId()]);
            return false;
        }

        $message = $this->generateMessage($type, $data);
        if (!$message) {
            return false;
        }

        return $this->sendFCMNotification($user->getFcmToken(), $message);
    }

    /**
     * Génère un message personnalisé selon le type
     */
    private function generateMessage(string $type, array $data): ?array
    {
        switch ($type) {
            case 'DEBT_REMINDER':
                return $this->generateDebtReminderMessage($data);
            case 'EXPENSE_REMINDER':
                return $this->generateExpenseReminderMessage($data);
            case 'INCOME_ALERT':
                return $this->generateIncomeAlertMessage($data);
            case 'PROJECT_ALERT':
                return $this->generateProjectAlertMessage($data);
            case 'FUN_MOTIVATION':
                return $this->generateMotivationMessage($data);
            case 'BALANCE_ALERT':
                return $this->generateBalanceAlertMessage($data);
            default:
                return null;
        }
    }

    /**
     * Message de rappel de dette
     */
    private function generateDebtReminderMessage(array $data): array
    {
        $amount = $data['amount'] ?? 0;
        $name = $data['name'] ?? 'quelqu\'un';
        $dueDate = $data['due_date'] ?? null;
        $daysLeft = $data['days_left'] ?? 0;
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "😅 Hé boss, {$name} attend encore " . number_format($amount) . " {$currency}. Échéance le {$dueDate} !",
            "⚠️ Oups ! " . number_format($amount) . " {$currency} à rembourser à {$name}. Dépêche-toi !",
            "💸 Ton budget te dit de régler " . number_format($amount) . " {$currency} à {$name} avant le {$dueDate}.",
            "🔥 Rappel amical : {$name} attend " . number_format($amount) . " {$currency}. Gère ça !",
            "🕒 Tic-tac… " . number_format($amount) . " {$currency} à {$name} à payer avant {$dueDate} !",
            "😎 N'oublie pas ta dette de " . number_format($amount) . " {$currency} pour {$name}.",
            "💪 Allez, c'est le moment de rembourser " . number_format($amount) . " {$currency} à {$name} !",
            "📌 Petit rappel : " . number_format($amount) . " {$currency} à {$name}, reste concentré !",
            "🤑 " . number_format($amount) . " {$currency} à {$name} → on ne traîne pas !",
            "👀 Hey, {$name} attend " . number_format($amount) . " {$currency}. À toi de jouer !"
        ];

        if ($daysLeft <= 0) {
            $messages = [
                "🚨 URGENT ! {$name} attend ses " . number_format($amount) . " {$currency} depuis " . abs($daysLeft) . " jours !",
                "😱 {$name} est en colère ! Tu lui dois " . number_format($amount) . " {$currency} depuis " . abs($daysLeft) . " jours !"
            ];
        }

        $title = "💸 Rappel de dette";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'DEBT_REMINDER',
                'debt_id' => $data['debt_id'] ?? null,
                'amount' => $amount,
                'name' => $name
            ]
        ];
    }

    /**
     * Message de rappel de dépense
     */
    private function generateExpenseReminderMessage(array $data): array
    {
        $days = $data['days'] ?? 1;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "💸 N'oublie pas de noter ta dépense d'hier !",
            "👀 Hé, ton portefeuille est triste… note ta dernière dépense !",
            "📋 Petit rappel : ajoute ta dépense pour garder le contrôle.",
            "🔥 Ton suivi cash t'attend, note tes dépenses !",
            "🕒 Dépense oubliée ? Mets-la vite dans SoldeTrack !",
            "😅 On reste sérieux : note ta dépense maintenant !",
            "💪 Suivi parfait = budget au top. Ajoute tes dépenses !",
            "⚡ Petit rappel rapide : ta dépense n'est pas enregistrée !",
            "💰 Chaque dépense compte. Note-la !",
            "📌 Action rapide : ta dépense attend dans l'app !"
        ];

        $title = "📝 Rappel de dépense";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'EXPENSE_REMINDER',
                'amount' => $amount,
                'days' => $days
            ]
        ];
    }

    /**
     * Message d'alerte de revenu
     */
    private function generateIncomeAlertMessage(array $data): array
    {
        $amount = $data['amount'] ?? 0;
        $source = $data['source'] ?? 'une source';
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "💵 Félicitations ! Tu as reçu " . number_format($amount) . " {$currency}.",
            "🤑 Nouveau revenu : " . number_format($amount) . " {$currency} ajouté à ton solde !",
            "💸 Argent entré ! Vérifie ton solde pour " . number_format($amount) . " {$currency}.",
            "🔥 Solde boosté : " . number_format($amount) . " {$currency} ajouté !",
            "💰 Jackpot ! " . number_format($amount) . " {$currency} disponible maintenant.",
            "😎 Revenu reçu : " . number_format($amount) . " {$currency} → gère-le bien !",
            "📈 Ton argent travaille pour toi : " . number_format($amount) . " {$currency} ajouté.",
            "⚡ Nouveau cash : " . number_format($amount) . " {$currency}, tu peux le suivre.",
            "💳 Revenu enregistré : " . number_format($amount) . " {$currency}.",
            "📌 Heads up ! " . number_format($amount) . " {$currency} a été crédité sur ton compte."
        ];

        $title = "💰 Nouveau revenu";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'INCOME_ALERT',
                'amount' => $amount,
                'source' => $source
            ]
        ];
    }

    /**
     * Message d'alerte de projet
     */
    private function generateProjectAlertMessage(array $data): array
    {
        $projectName = $data['project_name'] ?? 'ton projet';
        $percentage = $data['percentage'] ?? 0;
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "🐔 Projet {$projectName} : attention, budget dépassé !",
            "⚠️ Dépense sur {$projectName} → surveille ton solde !",
            "💡 Petit rappel pour {$projectName} : contrôle ton budget !",
            "🔥 Projet {$projectName} : nouvelle dépense enregistrée.",
            "💸 " . number_format($amount) . " {$currency} dépensé pour {$projectName}.",
            "📌 Suivi projet : {$projectName} a une dépense inattendue !",
            "💪 Objectif projet : garde {$projectName} sous contrôle !",
            "😎 Projet {$projectName} → attention à ton budget !",
            "⚡ Alerte : {$projectName} approche limite budget.",
            "📋 Nouveau mouvement dans {$projectName} : vérifie !"
        ];

        $title = "⚠️ Alerte projet";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'PROJECT_ALERT',
                'project_id' => $data['project_id'] ?? null,
                'project_name' => $projectName,
                'percentage' => $percentage,
                'amount' => $amount
            ]
        ];
    }

    /**
     * Message de motivation
     */
    private function generateMotivationMessage(array $data): array
    {
        $streak = $data['streak'] ?? 0;
        $totalSaved = $data['total_saved'] ?? 0;
        $category = $data['category'] ?? 'général';
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "🔥 Bravo ! 5 jours de suivi parfait, tu gères !",
            "💪 Keep going ! Ton budget est sous contrôle !",
            "😎 Tu es un vrai boss du cash 💰 !",
            "🎉 Petite victoire : toutes tes dépenses sont à jour !",
            "💡 Astuce : noter chaque dépense = pouvoir 💪",
            "⚡ Motivation du jour : reste régulier ! ✨",
            "🚀 Suivi actif = objectifs atteints !",
            "🥳 Félicitations ! Tu respectes ton plan budget !",
            "💰 Chaque action compte… continue comme ça !",
            "👀 Garder le contrôle = succès assuré !"
        ];

        $title = "🎉 Motivation";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'FUN_MOTIVATION',
                'streak' => $streak,
                'total_saved' => $totalSaved,
                'category' => $category
            ]
        ];
    }

    /**
     * Message d'alerte de solde
     */
    private function generateBalanceAlertMessage(array $data): array
    {
        $balance = $data['balance'] ?? 0;
        $accountName = $data['account_name'] ?? 'ton compte';
        $currency = $data['currency'] ?? 'XOF';

        $messages = [
            "⚠️ Attention, ton solde est bas : " . number_format($balance) . " {$currency} restant !",
            "💸 Solde actuel : " . number_format($balance) . " {$currency}. Prudence sur tes dépenses !",
            "🔥 Rappel : " . number_format($balance) . " {$currency} restant → planifie bien !",
            "😅 Solde presque vide : " . number_format($balance) . " {$currency} disponible.",
            "💪 Petit check : " . number_format($balance) . " {$currency} dans le compte, gère bien !",
            "📌 Heads up ! Ton solde = " . number_format($balance) . " {$currency}.",
            "🕒 Temps de surveiller ton argent : " . number_format($balance) . " {$currency} restant.",
            "💳 Solde critique : " . number_format($balance) . " {$currency} → attention au budget !",
            "⚡ Balance Alert : " . number_format($balance) . " {$currency} → reste stratégique !",
            "👀 Solde = " . number_format($balance) . " {$currency}, planifie tes dépenses !"
        ];

        $title = "💰 Alerte solde";
        $body = $messages[array_rand($messages)];

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'type' => 'BALANCE_ALERT',
                'balance' => $balance,
                'account_name' => $accountName
            ]
        ];
    }

    /**
     * Envoie la notification via FCM v1 API
     */
    private function sendFCMNotification(string $fcmToken, array $message): bool
    {
        try {
            $httpClient = HttpClient::create();
            
            // Construire l'URL avec le project ID
            $url = str_replace('{project_id}', $this->fcmProjectId, $this->fcmUrl);
            
            // Payload pour l'API FCM v1
            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $message['title'],
                        'body' => $message['body']
                    ],
                    'data' => $message['data'],
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'badge' => 1,
                            'channel_id' => 'solde_track_notifications',
                            'priority' => 'high',
                            'default_sound' => true,
                            'default_vibrate_timings' => true,
                            'default_light_settings' => true
                        ],
                        'direct_boot_ok' => true // Support du démarrage direct Android
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                                'alert' => [
                                    'title' => $message['title'],
                                    'body' => $message['body']
                                ]
                            ]
                        ]
                    ],
                    'webpush' => [
                        'notification' => [
                            'title' => $message['title'],
                            'body' => $message['body'],
                            'icon' => '/icon-192x192.png',
                            'badge' => '/badge-72x72.png',
                            'require_interaction' => false,
                            'silent' => false
                        ]
                    ]
                ]
            ];

            $response = $httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->fcmAccessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            if ($response->getStatusCode() === 200) {
                $this->logger->info('Notification FCM v1 envoyée avec succès', [
                    'fcm_token' => substr($fcmToken, 0, 20) . '...',
                    'type' => $message['data']['type'] ?? 'unknown',
                    'project_id' => $this->fcmProjectId
                ]);
                return true;
            }

            $this->logger->error('Erreur FCM v1', [
                'status_code' => $response->getStatusCode(),
                'response' => $response->getContent(false),
                'project_id' => $this->fcmProjectId
            ]);

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'envoi FCM v1', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'project_id' => $this->fcmProjectId,
                'fcm_token_length' => strlen($fcmToken),
                'access_token_length' => strlen($this->fcmAccessToken),
                'url' => $url
            ]);
            return false;
        }
    }

    /**
     * Vérifie et envoie les notifications de dettes en retard
     */
    public function checkAndSendDebtReminders(): int
    {
        $today = new \DateTime();
        $debtRepository = $this->entityManager->getRepository(Dette::class);
        
        $overdueDebts = $debtRepository->createQueryBuilder('d')
            ->where('d.statut = :statut')
            ->andWhere('d.echeance < :today')
            ->setParameter('statut', 'en_attente')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $sentCount = 0;
        foreach ($overdueDebts as $debt) {
            $daysOverdue = $today->diff($debt->getDateEcheance())->days;
            
            $data = [
                'debt_id' => $debt->getId(),
                'amount' => $debt->getMontantRestant(),
                'name' => $debt->getContact() ? $debt->getContact()->getNom() : 'quelqu\'un',
                'due_date' => $debt->getDateEcheance()->format('d/m/Y'),
                'days_left' => -$daysOverdue,
                'currency' => $debt->getUser()->getDevise() ? $debt->getUser()->getDevise()->getCode() : 'XOF'
            ];

            if ($this->sendNotification($debt->getUser(), 'DEBT_REMINDER', $data)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Vérifie et envoie les notifications de projets en dépassement
     */
    public function checkAndSendProjectAlerts(): int
    {
        $projectRepository = $this->entityManager->getRepository(Projet::class);
        
        $projects = $projectRepository->createQueryBuilder('p')
            ->getQuery()
            ->getResult();

        $sentCount = 0;
        foreach ($projects as $project) {
            $totalSpent = $this->calculateProjectTotalSpent($project);
            $budget = $project->getBudgetPrevu();
            
            if ($budget && $totalSpent > (float) $budget) {
                $budgetFloat = (float) $budget;
                $percentage = round((($totalSpent - $budgetFloat) / $budgetFloat) * 100, 1);
                $excessAmount = $totalSpent - $budgetFloat;
                
                $data = [
                    'project_id' => $project->getId(),
                    'project_name' => $project->getNom(),
                    'percentage' => $percentage,
                    'amount' => $excessAmount,
                    'currency' => $project->getUser()->getDevise() ? $project->getUser()->getDevise()->getCode() : 'XOF'
                ];

                if ($this->sendNotification($project->getUser(), 'PROJECT_ALERT', $data)) {
                    $sentCount++;
                }
            }
        }

        return $sentCount;
    }

    /**
     * Calcule le total des dépenses d'un projet
     */
    private function calculateProjectTotalSpent(Projet $project): float
    {
        $total = 0;
        foreach ($project->getMouvements() as $mouvement) {
            if ($mouvement instanceof Depense) {
                $total += (float) $mouvement->getMontantTotal();
            }
        }
        return $total;
    }

    /**
     * Envoie une notification de test
     */
    public function sendTestNotification(string $fcmToken, array $message): bool
    {
        return $this->sendFCMNotification($fcmToken, $message);
    }

    /**
     * Obtient l'ID du projet FCM
     */
    public function getProjectId(): string
    {
        return $this->fcmProjectId;
    }

    /**
     * Obtient l'URL FCM
     */
    public function getFcmUrl(): string
    {
        return $this->fcmUrl;
    }

    /**
     * Vérifie si le token d'accès est configuré
     */
    public function isAccessTokenConfigured(): bool
    {
        return !empty($this->fcmAccessToken);
    }

    /**
     * Envoie une notification de motivation basée sur les statistiques
     */
    public function sendMotivationNotification(User $user): bool
    {
        // Calculer le streak de suivi des dépenses
        $streak = $this->calculateExpenseTrackingStreak($user);
        
        // Calculer les économies du mois
        $totalSaved = $this->calculateMonthlySavings($user);
        
        $data = [
            'streak' => $streak,
            'total_saved' => $totalSaved,
            'category' => 'général',
            'currency' => $user->getDevise() ? $user->getDevise()->getCode() : 'XOF'
        ];

        return $this->sendNotification($user, 'FUN_MOTIVATION', $data);
    }

    /**
     * Calcule le streak de suivi des dépenses
     */
    private function calculateExpenseTrackingStreak(User $user): int
    {
        $mouvementRepository = $this->entityManager->getRepository(Mouvement::class);
        
        $today = new \DateTime();
        $streak = 0;
        
        for ($i = 0; $i < 30; $i++) {
            $date = clone $today;
            $date->modify("-{$i} days");
            
            $expenses = $mouvementRepository->createQueryBuilder('m')
                ->where('m.user = :user')
                ->andWhere('m.type = :type')
                ->andWhere('DATE(m.date) = :date')
                ->setParameter('user', $user)
                ->setParameter('type', 'sortie')
                ->setParameter('date', $date->format('Y-m-d'))
                ->getQuery()
                ->getResult();
            
            if (empty($expenses)) {
                break;
            }
            
            $streak++;
        }
        
        return $streak;
    }

    /**
     * Calcule les économies du mois
     */
    private function calculateMonthlySavings(User $user): float
    {
        $mouvementRepository = $this->entityManager->getRepository(Mouvement::class);
        
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        
        $incomes = $mouvementRepository->createQueryBuilder('m')
            ->select('SUM(m.montantTotal)')
            ->where('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.date BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('type', 'entree')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        $expenses = $mouvementRepository->createQueryBuilder('m')
            ->select('SUM(m.montantTotal)')
            ->where('m.user = :user')
            ->andWhere('m.type = :type')
            ->andWhere('m.date BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('type', 'depense')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        
        return max(0, $incomes - $expenses);
    }
}
