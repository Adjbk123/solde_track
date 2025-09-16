<?php

namespace App\Service;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;
use Psr\Log\LoggerInterface;

class PushNotificationService
{
    private $messaging;
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        // Chemin vers le fichier de configuration Firebase
        $firebaseConfigPath = dirname(__DIR__, 2) . '/config/firebase/firebase-service-account.json';
        
        try {
            $factory = (new Factory)->withServiceAccount($firebaseConfigPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            $this->logger->error('Erreur initialisation Firebase: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Envoie une notification push
     */
    public function sendNotification(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification);

            // Ajouter les données personnalisées si elles existent
            if (!empty($data)) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);
            
            $this->logger->info('Notification envoyée avec succès', [
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
                'title' => $title
            ]);
            
            return true;
            
        } catch (MessagingException $e) {
            $this->logger->error('Erreur envoi notification FCM: ' . $e->getMessage(), [
                'fcm_token' => substr($fcmToken, 0, 20) . '...',
                'title' => $title,
                'error_code' => $e->getCode()
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Erreur générale envoi notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification de motivation
     */
    public function sendMotivationNotification(string $fcmToken, string $userName, string $currency = 'XOF'): bool
    {
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

        $title = "💪 Motivation SoldeTrack";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'motivation',
            'user_name' => $userName,
            'currency' => $currency,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Envoie une notification de revenu
     */
    public function sendIncomeNotification(string $fcmToken, string $userName, float $amount, string $currency = 'XOF'): bool
    {
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

        $title = "💰 Nouveau Revenu";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'income',
            'user_name' => $userName,
            'amount' => $amount,
            'currency' => $currency,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Envoie une notification de rappel de dette
     */
    public function sendDebtReminderNotification(string $fcmToken, string $userName, string $debtName, float $amount, string $dueDate, string $currency = 'XOF'): bool
    {
        $messages = [
            "😅 Hé boss, {$debtName} attend encore " . number_format($amount) . " {$currency}. Échéance le {$dueDate} !",
            "⚠️ Oups ! " . number_format($amount) . " {$currency} à rembourser à {$debtName}. Dépêche-toi !",
            "💸 Ton budget te dit de régler " . number_format($amount) . " {$currency} à {$debtName} avant le {$dueDate}.",
            "🔥 Rappel amical : {$debtName} attend " . number_format($amount) . " {$currency}. Gère ça !",
            "🕒 Tic-tac… " . number_format($amount) . " {$currency} à {$debtName} à payer avant {$dueDate} !",
            "😎 N'oublie pas ta dette de " . number_format($amount) . " {$currency} pour {$debtName}.",
            "💪 Allez, c'est le moment de rembourser " . number_format($amount) . " {$currency} à {$debtName} !",
            "📌 Petit rappel : " . number_format($amount) . " {$currency} à {$debtName}, reste concentré !",
            "🤑 " . number_format($amount) . " {$currency} à {$debtName} → on ne traîne pas !",
            "👀 Hey, {$debtName} attend " . number_format($amount) . " {$currency}. À toi de jouer !"
        ];

        $title = "⚠️ Rappel de Dette";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'debt_reminder',
            'user_name' => $userName,
            'debt_name' => $debtName,
            'amount' => $amount,
            'due_date' => $dueDate,
            'currency' => $currency,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Envoie une notification de rappel de dépense
     */
    public function sendExpenseReminderNotification(string $fcmToken, string $userName): bool
    {
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

        $title = "💸 Rappel Dépense";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'expense_reminder',
            'user_name' => $userName,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Envoie une notification d'alerte de projet
     */
    public function sendProjectAlertNotification(string $fcmToken, string $userName, string $projectName, float $amount, string $currency = 'XOF'): bool
    {
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

        $title = "📊 Alerte Projet";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'project_alert',
            'user_name' => $userName,
            'project_name' => $projectName,
            'amount' => $amount,
            'currency' => $currency,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Envoie une notification d'alerte de solde
     */
    public function sendBalanceAlertNotification(string $fcmToken, string $userName, float $balance, string $currency = 'XOF'): bool
    {
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

        $title = "⚠️ Alerte Solde";
        $body = $messages[array_rand($messages)];

        $data = [
            'type' => 'balance_alert',
            'user_name' => $userName,
            'balance' => $balance,
            'currency' => $currency,
            'timestamp' => time()
        ];

        return $this->sendNotification($fcmToken, $title, $body, $data);
    }

    /**
     * Teste la connexion Firebase
     */
    public function testConnection(): array
    {
        try {
            // Test simple de connexion
            $factory = (new Factory)->withServiceAccount(dirname(__DIR__, 2) . '/config/firebase/firebase-service-account.json');
            $messaging = $factory->createMessaging();
            
            return [
                'status' => 'success',
                'message' => 'Connexion Firebase réussie',
                'project_id' => 'soldetrack'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur connexion Firebase: ' . $e->getMessage()
            ];
        }
    }
}
