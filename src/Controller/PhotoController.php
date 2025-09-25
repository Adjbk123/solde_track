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
                $oldPhotoPath = $user->getPhoto();
                $oldFileName = basename($oldPhotoPath); // Extraire juste le nom du fichier
                $this->photoUploadService->delete($oldFileName);
            }

            // Uploader la nouvelle photo
            $fileName = $this->photoUploadService->upload($uploadedFile, $user->getId());
            
            // Redimensionner l'image
            $filePath = $this->photoUploadService->getFilePath($fileName);
            $this->photoUploadService->resizeImage($filePath, 300, 300);

            // Mettre à jour l'utilisateur avec le chemin complet
            $fullPath = '/uploads/profils/' . $fileName;
            $user->setPhoto($fullPath);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo uploadée avec succès',
                'photo' => [
                    'filename' => $fileName,
                    'url' => $fullPath,
                    'full_path' => $fullPath
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
            // Extraire le nom de fichier du chemin complet
            $photoPath = $user->getPhoto();
            $fileName = basename($photoPath); // Extraire juste le nom du fichier
            
            // Supprimer le fichier
            $deleted = $this->photoUploadService->delete($fileName);
            
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
                    'url' => $user->getPhoto(),
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

    #[Route('/upload-base64', name: 'upload_base64', methods: ['POST'])]
    public function uploadBase64(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $base64Data = $data['photo'] ?? null;

        if (!$base64Data) {
            return new JsonResponse([
                'error' => 'Aucune image fournie',
                'message' => 'Veuillez fournir une image en base64'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Supprimer l'ancienne photo si elle existe
            if ($user->getPhoto()) {
                $oldPhotoPath = $user->getPhoto();
                $oldFileName = basename($oldPhotoPath); // Extraire juste le nom du fichier
                $this->photoUploadService->delete($oldFileName);
            }

            // Sauvegarder l'image base64 comme fichier
            $fileName = $this->photoUploadService->saveBase64Image($base64Data, $user->getId());

            // Mettre à jour l'utilisateur avec le chemin complet
            $fullPath = '/uploads/profils/' . $fileName;
            $user->setPhoto($fullPath);
            $this->entityManager->flush();

            return new JsonResponse([
                'message' => 'Photo uploadée avec succès',
                'photo' => [
                    'filename' => $fileName,
                    'url' => $fullPath,
                    'full_path' => $fullPath
                ]
            ]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'error' => 'Format d\'image invalide',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'upload',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/clean-base64', name: 'clean_base64', methods: ['POST'])]
    public function cleanBase64Data(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->getPhoto()) {
            return new JsonResponse([
                'error' => 'Aucune photo',
                'message' => 'Aucune photo à nettoyer'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Vérifier si la photo est stockée en base64
            if ($this->photoUploadService->isBase64Image($user->getPhoto())) {
                // Convertir le base64 en fichier
                $fileName = $this->photoUploadService->saveBase64Image($user->getPhoto(), $user->getId());
                
                // Mettre à jour l'utilisateur avec le nom du fichier
                $user->setPhoto($fileName);
                $this->entityManager->flush();

                return new JsonResponse([
                    'message' => 'Photo base64 convertie en fichier avec succès',
                    'photo' => [
                        'filename' => $fileName,
                        'url' => $fileName
                    ]
                ]);
            } else {
                return new JsonResponse([
                    'message' => 'La photo est déjà stockée comme fichier',
                    'photo' => [
                        'filename' => $user->getPhoto(),
                        'url' => $user->getPhoto()
                    ]
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors du nettoyage',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
