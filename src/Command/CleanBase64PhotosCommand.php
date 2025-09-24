<?php

namespace App\Command;

use App\Entity\User;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-base64-photos',
    description: 'Nettoie les photos base64 stockées en base de données et les convertit en fichiers',
)]
class CleanBase64PhotosCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PhotoUploadService $photoUploadService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Nettoyage des photos base64');

        // Récupérer tous les utilisateurs avec des photos
        $users = $this->entityManager->getRepository(User::class)->findBy(['photo' => null], [], 0, 0);
        $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.photo IS NOT NULL')
            ->getQuery()
            ->getResult();

        $io->info(sprintf('Trouvé %d utilisateurs avec des photos', count($users)));

        $converted = 0;
        $errors = 0;

        foreach ($users as $user) {
            $photo = $user->getPhoto();
            
            if (!$photo) {
                continue;
            }

            // Vérifier si c'est une image base64
            if ($this->photoUploadService->isBase64Image($photo)) {
                $io->writeln(sprintf('Conversion de la photo de l\'utilisateur %d...', $user->getId()));
                
                try {
                    // Convertir le base64 en fichier
                    $fileName = $this->photoUploadService->saveBase64Image($photo, $user->getId());
                    
                    // Mettre à jour l'utilisateur
                    $user->setPhoto($fileName);
                    $this->entityManager->flush();
                    
                    $converted++;
                    $io->writeln(sprintf('✅ Converti: %s', $fileName));
                    
                } catch (\Exception $e) {
                    $errors++;
                    $io->error(sprintf('❌ Erreur pour l\'utilisateur %d: %s', $user->getId(), $e->getMessage()));
                }
            } else {
                $io->writeln(sprintf('⏭️  L\'utilisateur %d a déjà un fichier: %s', $user->getId(), $photo));
            }
        }

        $io->success(sprintf('Nettoyage terminé: %d photos converties, %d erreurs', $converted, $errors));

        return Command::SUCCESS;
    }
}
