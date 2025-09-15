<?php

namespace App\Controller;

use App\Entity\Devise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/devises', name: 'api_devises_')]
class DeviseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $devises = $this->entityManager->getRepository(Devise::class)->findAll();
        
        $data = [];
        foreach ($devises as $devise) {
            $data[] = [
                'id' => $devise->getId(),
                'code' => $devise->getCode(),
                'nom' => $devise->getNom()
            ];
        }

        return new JsonResponse(['devises' => $data]);
    }

    #[Route('/{code}', name: 'show', methods: ['GET'])]
    public function show(string $code): JsonResponse
    {
        $devise = $this->entityManager->getRepository(Devise::class)->findByCode($code);
        
        if (!$devise) {
            return new JsonResponse(['error' => 'Devise non trouvée'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'devise' => [
                'id' => $devise->getId(),
                'code' => $devise->getCode(),
                'nom' => $devise->getNom()
            ]
        ]);
    }
}
