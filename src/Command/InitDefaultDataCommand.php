<?php

namespace App\Command;

use App\Service\DefaultDevisesService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-default-data',
    description: 'Initialise les données par défaut (devises)',
)]
class InitDefaultDataCommand extends Command
{
    public function __construct(
        private DefaultDevisesService $defaultDevisesService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Initialisation des données par défaut');

        // Vérifier si les devises existent déjà
        if ($this->defaultDevisesService->devisesExist()) {
            $io->warning('Les devises par défaut existent déjà dans la base de données.');
            return Command::SUCCESS;
        }

        // Créer les devises par défaut
        $io->section('Création des devises par défaut...');
        $this->defaultDevisesService->createDefaultDevises();

        $io->success('Données par défaut initialisées avec succès !');
        $io->note('Les devises suivantes ont été créées :');
        
        $devises = $this->defaultDevisesService->getDefaultDevises();
        foreach ($devises as $devise) {
            $io->text(sprintf('- %s (%s)', $devise['nom'], $devise['code']));
        }

        return Command::SUCCESS;
    }
}
