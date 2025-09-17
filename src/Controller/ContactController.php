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

    /**
     * Normalise la source pour assurer la compatibilité
     */
    private function normalizeSource(string $source): string
    {
        // Convertir 'manual' en 'manuel' pour la compatibilité avec l'app mobile
        if ($source === 'manual') {
            return 'manuel';
        }
        return $source;
    }

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
        $contact->setSource($this->normalizeSource($data['source']));

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

    #[Route('/batch', name: 'create_batch', methods: ['POST'])]
    public function createBatch(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['contacts']) || !is_array($data['contacts']) || empty($data['contacts'])) {
            return new JsonResponse([
                'error' => 'Données manquantes',
                'message' => 'Le tableau "contacts" est requis et ne peut pas être vide'
            ], Response::HTTP_BAD_REQUEST);
        }

        $results = [
            'created' => [],
            'errors' => [],
            'duplicates' => []
        ];

        $createdCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;

        foreach ($data['contacts'] as $index => $contactData) {
            try {
                // Validation des données requises
                if (!isset($contactData['nom']) || !isset($contactData['telephone']) || !isset($contactData['source'])) {
                    $results['errors'][] = [
                        'index' => $index,
                        'data' => $contactData,
                        'error' => 'Données manquantes',
                        'message' => 'Nom, téléphone et source sont requis'
                    ];
                    $errorCount++;
                    continue;
                }

                // Vérifier si le contact existe déjà
                $existingContact = $this->entityManager->getRepository(Contact::class)
                    ->findByTelephone($user, $contactData['telephone']);
                if ($existingContact) {
                    $results['duplicates'][] = [
                        'index' => $index,
                        'data' => $contactData,
                        'existing_contact' => [
                            'id' => $existingContact->getId(),
                            'nom' => $existingContact->getNom(),
                            'telephone' => $existingContact->getTelephone()
                        ],
                        'message' => 'Un contact avec ce numéro existe déjà'
                    ];
                    $duplicateCount++;
                    continue;
                }

                // Créer le nouveau contact
                $contact = new Contact();
                $contact->setUser($user);
                $contact->setNom($contactData['nom']);
                $contact->setTelephone($contactData['telephone']);
                $contact->setEmail($contactData['email'] ?? null);
                $contact->setSource($this->normalizeSource($contactData['source']));

                // Validation
                $errors = $this->validator->validate($contact);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getMessage();
                    }
                    $results['errors'][] = [
                        'index' => $index,
                        'data' => $contactData,
                        'error' => 'Données invalides',
                        'messages' => $errorMessages
                    ];
                    $errorCount++;
                    continue;
                }

                // Persister le contact
                $this->entityManager->persist($contact);
                
                $results['created'][] = [
                    'index' => $index,
                    'contact' => [
                        'nom' => $contact->getNom(),
                        'telephone' => $contact->getTelephone(),
                        'email' => $contact->getEmail(),
                        'source' => $contact->getSource(),
                        'sourceLabel' => $contact->getSourceLabel()
                    ]
                ];
                $createdCount++;

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'index' => $index,
                    'data' => $contactData,
                    'error' => 'Erreur système',
                    'message' => $e->getMessage()
                ];
                $errorCount++;
            }
        }

        // Sauvegarder tous les contacts valides en une seule transaction
        if ($createdCount > 0) {
            try {
                $this->entityManager->flush();
                
                // Récupérer les IDs des contacts créés
                foreach ($results['created'] as &$createdContact) {
                    $contact = $this->entityManager->getRepository(Contact::class)
                        ->findByTelephone($user, $createdContact['contact']['telephone']);
                    if ($contact) {
                        $createdContact['contact']['id'] = $contact->getId();
                        $createdContact['contact']['dateCreation'] = $contact->getDateCreation()->format('Y-m-d H:i:s');
                    }
                }
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Erreur lors de la sauvegarde',
                    'message' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $totalProcessed = count($data['contacts']);
        $response = [
            'message' => 'Traitement en lot terminé',
            'summary' => [
                'total_processed' => $totalProcessed,
                'created' => $createdCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount
            ],
            'results' => $results
        ];

        // Déterminer le code de statut approprié
        if ($createdCount > 0 && $errorCount === 0 && $duplicateCount === 0) {
            $statusCode = Response::HTTP_CREATED; // 201 - Tous créés avec succès
        } elseif ($createdCount > 0) {
            $statusCode = Response::HTTP_MULTI_STATUS; // 207 - Succès partiel
        } else {
            $statusCode = Response::HTTP_BAD_REQUEST; // 400 - Aucun contact créé
        }

        return new JsonResponse($response, $statusCode);
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
            $contact->setSource($this->normalizeSource($data['source']));
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
