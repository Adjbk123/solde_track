<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:notification:check',
    description: 'Vérifie et envoie les notifications automatiques (dettes, projets, etc.)'
)]
class NotificationCheckCommand extends Command
{
    public function __construct(
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔔 Vérification des notifications automatiques');

        // Vérifier les dettes en retard
        $io->section('💸 Vérification des dettes en retard');
        $debtNotifications = $this->notificationService->checkAndSendDebtReminders();
        $io->success("Notifications de dettes envoyées : {$debtNotifications}");

        // Vérifier les projets en dépassement
        $io->section('⚠️ Vérification des projets en dépassement');
        $projectNotifications = $this->notificationService->checkAndSendProjectAlerts();
        $io->success("Notifications de projets envoyées : {$projectNotifications}");

        $totalNotifications = $debtNotifications + $projectNotifications;
        
        if ($totalNotifications > 0) {
            $io->success("✅ Vérification terminée ! {$totalNotifications} notifications envoyées au total.");
        } else {
            $io->info("ℹ️ Aucune notification à envoyer pour le moment.");
        }

        return Command::SUCCESS;
    }
}
