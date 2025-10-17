<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Compte;
use App\Service\PdfExportService;
use App\Service\CsvExportService;
use App\Service\ExcelExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/export', name: 'api_export_')]
class ExportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PdfExportService $pdfExportService,
        private CsvExportService $csvExportService,
        private ExcelExportService $excelExportService
    ) {}

    #[Route('/releve-compte/{compteId}', name: 'releve_compte', methods: ['GET'])]
    public function exportReleveCompte(int $compteId, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer le compte
        $compte = $this->entityManager->getRepository(Compte::class)->findOneBy([
            'id' => $compteId,
            'user' => $user,
            'actif' => true
        ]);

        if (!$compte) {
            return new JsonResponse([
                'error' => 'Compte non trouvé',
                'message' => 'Le compte spécifié n\'existe pas ou n\'appartient pas à cet utilisateur'
            ], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les paramètres de date
        $dateDebut = null;
        $dateFin = null;

        if ($request->query->has('date_debut')) {
            try {
                $dateDebut = new \DateTime($request->query->get('date_debut'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de début invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('date_fin')) {
            try {
                $dateFin = new \DateTime($request->query->get('date_fin'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de fin invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            return $this->pdfExportService->generateReleveCompte($user, $compte, $dateDebut, $dateFin);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => 'Une erreur est survenue lors de la génération du relevé'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rapport-financier', name: 'rapport_financier', methods: ['GET'])]
    public function exportRapportFinancier(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les paramètres de date
        $dateDebut = null;
        $dateFin = null;

        if ($request->query->has('date_debut')) {
            try {
                $dateDebut = new \DateTime($request->query->get('date_debut'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de début invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('date_fin')) {
            try {
                $dateFin = new \DateTime($request->query->get('date_fin'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de fin invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            return $this->pdfExportService->generateRapportFinancier($user, $dateDebut, $dateFin);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => 'Une erreur est survenue lors de la génération du rapport'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/formats', name: 'formats', methods: ['GET'])]
    public function getAvailableFormats(): JsonResponse
    {
        return new JsonResponse([
            'formats' => [
                [
                    'type' => 'releve_compte',
                    'name' => 'Relevé de Compte',
                    'description' => 'Export PDF détaillé d\'un compte spécifique avec tous ses mouvements',
                    'endpoint' => '/api/export/releve-compte/{compteId}',
                    'parameters' => [
                        'compteId' => 'ID du compte (obligatoire)',
                        'date_debut' => 'Date de début (optionnel, format YYYY-MM-DD)',
                        'date_fin' => 'Date de fin (optionnel, format YYYY-MM-DD)'
                    ]
                ],
                [
                    'type' => 'rapport_financier',
                    'name' => 'Rapport Financier Global',
                    'description' => 'Export PDF de l\'ensemble de la situation financière',
                    'endpoint' => '/api/export/rapport-financier',
                    'parameters' => [
                        'date_debut' => 'Date de début (optionnel, format YYYY-MM-DD)',
                        'date_fin' => 'Date de fin (optionnel, format YYYY-MM-DD)'
                    ]
                ],
                [
                    'type' => 'mouvements_csv',
                    'name' => 'Export Mouvements CSV',
                    'description' => 'Export CSV de tous les mouvements financiers',
                    'endpoint' => '/api/export/mouvements/csv',
                    'parameters' => [
                        'date_debut' => 'Date de début (optionnel, format YYYY-MM-DD)',
                        'date_fin' => 'Date de fin (optionnel, format YYYY-MM-DD)',
                        'account_ids' => 'IDs des comptes (optionnel, array)',
                        'movement_types' => 'Types de mouvements (optionnel, array)'
                    ]
                ],
                [
                    'type' => 'comptes_csv',
                    'name' => 'Export Comptes CSV',
                    'description' => 'Export CSV de tous les comptes',
                    'endpoint' => '/api/export/comptes/csv',
                    'parameters' => []
                ],
                [
                    'type' => 'contacts_csv',
                    'name' => 'Export Contacts CSV',
                    'description' => 'Export CSV de tous les contacts',
                    'endpoint' => '/api/export/contacts/csv',
                    'parameters' => []
                ],
                [
                    'type' => 'depenses_prevues_csv',
                    'name' => 'Export Dépenses Prévues CSV',
                    'description' => 'Export CSV de toutes les dépenses prévues',
                    'endpoint' => '/api/export/depenses-prevues/csv',
                    'parameters' => []
                ],
                [
                    'type' => 'rapport_complet_excel',
                    'name' => 'Rapport Complet Excel',
                    'description' => 'Export Excel multi-feuilles avec toutes les données',
                    'endpoint' => '/api/export/rapport-complet/excel',
                    'parameters' => [
                        'date_debut' => 'Date de début (optionnel, format YYYY-MM-DD)',
                        'date_fin' => 'Date de fin (optionnel, format YYYY-MM-DD)'
                    ]
                ],
                [
                    'type' => 'mouvements_excel',
                    'name' => 'Export Mouvements Excel',
                    'description' => 'Export Excel des mouvements avec formatage',
                    'endpoint' => '/api/export/mouvements/excel',
                    'parameters' => [
                        'date_debut' => 'Date de début (optionnel, format YYYY-MM-DD)',
                        'date_fin' => 'Date de fin (optionnel, format YYYY-MM-DD)'
                    ]
                ]
            ]
        ]);
    }

    #[Route('/mouvements/csv', name: 'mouvements_csv', methods: ['GET'])]
    public function exportMouvementsCsv(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les paramètres
        $dateDebut = null;
        $dateFin = null;
        $filters = [];

        if ($request->query->has('date_debut')) {
            try {
                $dateDebut = new \DateTime($request->query->get('date_debut'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de début invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('date_fin')) {
            try {
                $dateFin = new \DateTime($request->query->get('date_fin'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de fin invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('account_ids')) {
            $filters['account_ids'] = json_decode($request->query->get('account_ids'), true);
        }

        if ($request->query->has('movement_types')) {
            $filters['movement_types'] = json_decode($request->query->get('movement_types'), true);
        }

        try {
            return $this->csvExportService->exportMouvements($user, $dateDebut, $dateFin, $filters);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du CSV',
                'message' => 'Une erreur est survenue lors de l\'export des mouvements'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/comptes/csv', name: 'comptes_csv', methods: ['GET'])]
    public function exportComptesCsv(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->csvExportService->exportComptes($user);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du CSV',
                'message' => 'Une erreur est survenue lors de l\'export des comptes'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/contacts/csv', name: 'contacts_csv', methods: ['GET'])]
    public function exportContactsCsv(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->csvExportService->exportContacts($user);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du CSV',
                'message' => 'Une erreur est survenue lors de l\'export des contacts'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/depenses-prevues/csv', name: 'depenses_prevues_csv', methods: ['GET'])]
    public function exportDepensesPrevuesCsv(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            return $this->csvExportService->exportDepensesPrevues($user);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du CSV',
                'message' => 'Une erreur est survenue lors de l\'export des dépenses prévues'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rapport-complet/excel', name: 'rapport_complet_excel', methods: ['GET'])]
    public function exportRapportCompletExcel(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les paramètres de date
        $dateDebut = null;
        $dateFin = null;

        if ($request->query->has('date_debut')) {
            try {
                $dateDebut = new \DateTime($request->query->get('date_debut'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de début invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('date_fin')) {
            try {
                $dateFin = new \DateTime($request->query->get('date_fin'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de fin invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            return $this->excelExportService->exportComplet($user, $dateDebut, $dateFin);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du Excel',
                'message' => 'Une erreur est survenue lors de l\'export du rapport complet'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/mouvements/excel', name: 'mouvements_excel', methods: ['GET'])]
    public function exportMouvementsExcel(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les paramètres de date
        $dateDebut = null;
        $dateFin = null;

        if ($request->query->has('date_debut')) {
            try {
                $dateDebut = new \DateTime($request->query->get('date_debut'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de début invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        if ($request->query->has('date_fin')) {
            try {
                $dateFin = new \DateTime($request->query->get('date_fin'));
            } catch (\Exception $e) {
                return new JsonResponse([
                    'error' => 'Date de fin invalide',
                    'message' => 'Format de date invalide. Utilisez YYYY-MM-DD'
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            return $this->excelExportService->exportMouvements($user, $dateDebut, $dateFin);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du Excel',
                'message' => 'Une erreur est survenue lors de l\'export des mouvements'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
