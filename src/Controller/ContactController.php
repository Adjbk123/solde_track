<?php

namespace App\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/contacts', name: 'api_contacts_')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $source = $request->query->get('source');
        
        if ($source) {
            $contacts = $this->entityManager->getRepository(Contact::class)
                ->findByUserAndSource($user, $source);
        } else {
            $contacts = $user->getContacts();
        }

        $data = [];
        foreach ($contacts as $contact) {
            $data[] = [
                'id' => $contact->getId(),
                'nom' => $contact->getNom(),
                'telephone' => $contact->getTelephone(),
                'email' => $contact->getEmail(),
                'source' => $contact->getSource(),
                'sourceLabel' => $contact->getSourceLabel(),
                'dateCreation' => $contact->getDateCreation()->format('Y-m-d H:i:s'),
                'nombreMouvements' => $contact->getMouvements()->count()
            ];
        }

        return new JsonResponse(['contacts' => $data]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || !isset($data['telephone']) || !isset($data['source'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Nom, téléphone et source sont requis'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le contact existe déjà
        $existingContact = $this->entityManager->getRepository(Contact::class)
            ->findByTelephone($user, $data['telephone']);
        if ($existingContact) {
            return new JsonResponse([
                'error' => 'Contact existant',
                'message' => 'Un contact avec ce numéro existe déjà'
            ], Response::HTTP_CONFLICT);
        }

        $contact = new Contact();
        $contact->setUser($user);
        $contact->setNom($data['nom']);
        $contact->setTelephone($data['telephone']);
        $contact->setEmail($data['email'] ?? null);
        $contact->setSource($data['source']);

        $errors = $this->validator->validate($contact);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Données invalides',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Contact créé avec succès',
            'contact' => [
                'id' => $contact->getId(),
                'nom' => $contact->getNom(),
                'telephone' => $contact->getTelephone(),
                'email' => $contact->getEmail(),
                'source' => $contact->getSource(),
                'sourceLabel' => $contact->getSourceLabel(),
                'dateCreation' => $contact->getDateCreation()->format('Y-m-d H:i:s')
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $contactId = (int) $id;
        $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);
        if (!$contact || $contact->getUser() !== $user) {
            return new JsonResponse(['error' => 'Contact non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['nom'])) {
            $contact->setNom($data['nom']);
        }
        if (isset($data['telephone'])) {
            $contact->setTelephone($data['telephone']);
        }
        if (isset($data['email'])) {
            $contact->setEmail($data['email']);
        }
        if (isset($data['source'])) {
            $contact->setSource($data['source']);
        }

        $errors = $this->validator->validate($contact);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Données invalides',
                'messages' => $errorMessages
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Contact mis à jour avec succès',
            'contact' => [
                'id' => $contact->getId(),
                'nom' => $contact->getNom(),
                'telephone' => $contact->getTelephone(),
                'email' => $contact->getEmail(),
                'source' => $contact->getSource(),
                'sourceLabel' => $contact->getSourceLabel(),
                'dateCreation' => $contact->getDateCreation()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $contactId = (int) $id;
        $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);
        if (!$contact || $contact->getUser() !== $user) {
            return new JsonResponse(['error' => 'Contact non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier s'il y a des mouvements associés
        if ($contact->getMouvements()->count() > 0) {
            return new JsonResponse([
                'error' => 'Impossible de supprimer',
                'message' => 'Ce contact est associé à des mouvements'
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->remove($contact);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Contact supprimé avec succès'
        ]);
    }
}
