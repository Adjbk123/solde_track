<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PhotoUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/photo', name: 'api_photo_')]
class PhotoController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PhotoUploadService $photoUploadService
    ) {}

    #[Route('/upload', name: 'upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $uploadedFile = $request->files->get('photo');
        
        if (!$uploadedFile) {
            return new JsonResponse([
                'error' => 'Aucun fichier fourni',
                'message' => 'Veuillez sélectionner une photo'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Valider le fichier
        $errors = $this->photoUploadService->validateFile($uploadedFile);
        if (!empty($errors)) {
            return new JsonResponse([
                'error' => 'Fichier invalide',
                'messages' => $errors
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer l'ancienne photo si elle existe
            if ($user->getPhoto()) {
                $this->photoUploadService->delete($user->getPhoto());
            }

            // Uploader la nouvelle photo
            $fileName = $this->photoUploadService->upload($uploadedFile, $user->getId());
            
            // Redimensionner l'image
            $filePath = $this->photoUploadService->getFilePath($fileName);
            $this->photoUploadService->resizeImage($filePath, 300, 300);

            // Mettre à jour l'utilisateur
            $user->setPhoto($fileName);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo uploadée avec succès',
                'photo' => [
                    'filename' => $fileName,
                    'url' => $this->photoUploadService->getPublicUrl($fileName)
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'upload',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function delete(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getPhoto()) {
            return new JsonResponse([
                'error' => 'Aucune photo',
                'message' => 'Aucune photo de profil à supprimer'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer le fichier
            $deleted = $this->photoUploadService->delete($user->getPhoto());
            
            if ($deleted) {
                // Mettre à jour l'utilisateur
                $user->setPhoto(null);
                $this->entityManager->flush();

                return new JsonResponse([
                    'message' => 'Photo supprimée avec succès'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Erreur lors de la suppression',
                    'message' => 'Impossible de supprimer le fichier'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/info', name: 'info', methods: ['GET'])]
    public function getInfo(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $photoInfo = null;
        if ($user->getPhoto()) {
            $filePath = $this->photoUploadService->getFilePath($user->getPhoto());
            if (file_exists($filePath)) {
                $photoInfo = [
                    'filename' => $user->getPhoto(),
                    'url' => $this->photoUploadService->getPublicUrl($user->getPhoto()),
                    'size' => filesize($filePath),
                    'lastModified' => filemtime($filePath)
                ];
            }
        }

        return new JsonResponse([
            'hasPhoto' => !is_null($user->getPhoto()),
            'photo' => $photoInfo
        ]);
    }
}
