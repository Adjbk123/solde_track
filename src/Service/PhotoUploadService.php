<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class PhotoUploadService
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Upload une photo de profil
     */
    public function upload(UploadedFile $file, int $userId): string
    {
        // Générer un nom de fichier unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $userId . '_' . $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            // Créer le dossier s'il n'existe pas
            if (!is_dir($this->targetDirectory)) {
                mkdir($this->targetDirectory, 0755, true);
            }

            // Déplacer le fichier
            $file->move($this->targetDirectory, $fileName);
        } catch (FileException $e) {
            throw new \Exception('Erreur lors de l\'upload de la photo: ' . $e->getMessage());
        }

        return $fileName;
    }

    /**
     * Supprime une photo de profil
     */
    public function delete(string $fileName): bool
    {
        $filePath = $this->targetDirectory . '/' . $fileName;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }

    /**
     * Valide le fichier uploadé
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        
        // Vérifier la taille (max 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            $errors[] = 'La photo ne doit pas dépasser 5MB';
        }
        
        // Vérifier le type MIME
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Seuls les formats JPEG, PNG, GIF et WebP sont autorisés';
        }
        
        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Extension de fichier non autorisée';
        }
        
        return $errors;
    }

    /**
     * Redimensionne une image
     */
    public function resizeImage(string $filePath, int $maxWidth = 300, int $maxHeight = 300): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }

        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Calculer les nouvelles dimensions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = intval($originalWidth * $ratio);
        $newHeight = intval($originalHeight * $ratio);

        // Créer l'image source selon le type
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($filePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($filePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($filePath);
                break;
            default:
                return false;
        }

        if (!$sourceImage) {
            return false;
        }

        // Créer l'image redimensionnée
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Préserver la transparence pour PNG et GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Redimensionner
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Sauvegarder selon le type
        $result = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($resizedImage, $filePath, 90);
                break;
            case 'image/png':
                $result = imagepng($resizedImage, $filePath, 9);
                break;
            case 'image/gif':
                $result = imagegif($resizedImage, $filePath);
                break;
            case 'image/webp':
                $result = imagewebp($resizedImage, $filePath, 90);
                break;
        }

        // Libérer la mémoire
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);

        return $result;
    }

    /**
     * Génère l'URL publique de la photo
     */
    public function getPublicUrl(string $fileName): string
    {
        return '/uploads/profils/' . $fileName;
    }

    /**
     * Génère le chemin complet du fichier
     */
    public function getFilePath(string $fileName): string
    {
        return $this->targetDirectory . '/' . $fileName;
    }

    /**
     * Sauvegarde une image base64 comme fichier
     */
    public function saveBase64Image(string $base64Data, int $userId): string
    {
        // Vérifier si c'est une image base64 valide
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            throw new \InvalidArgumentException('Format base64 invalide');
        }

        $extension = $matches[1];
        $imageData = substr($base64Data, strpos($base64Data, ',') + 1);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            throw new \InvalidArgumentException('Données base64 invalides');
        }

        // Générer un nom de fichier unique
        $fileName = 'user_' . $userId . '_photo_' . uniqid() . '.' . $extension;
        $filePath = $this->getFilePath($fileName);

        // Sauvegarder le fichier
        if (file_put_contents($filePath, $imageData) === false) {
            throw new \RuntimeException('Impossible de sauvegarder le fichier');
        }

        // Redimensionner l'image
        $this->resizeImage($filePath, 300, 300);

        return $fileName;
    }

    /**
     * Vérifie si une chaîne est une image base64
     */
    public function isBase64Image(string $data): bool
    {
        return preg_match('/^data:image\/(\w+);base64,/', $data) === 1;
    }

    /**
     * Nettoie une chaîne base64 stockée en base de données
     */
    public function cleanBase64FromDatabase(string $base64Data): ?string
    {
        if ($this->isBase64Image($base64Data)) {
            // Extraire le nom de fichier ou générer un nouveau nom
            $userId = 1; // Vous devrez passer l'ID utilisateur
            try {
                return $this->saveBase64Image($base64Data, $userId);
            } catch (\Exception $e) {
                error_log('Erreur lors de la conversion base64: ' . $e->getMessage());
                return null;
            }
        }
        
        return $base64Data; // Retourner tel quel si ce n'est pas du base64
    }
}
