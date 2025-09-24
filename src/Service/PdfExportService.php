<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Mouvement;
use App\Repository\MouvementRepository;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MouvementRepository $mouvementRepository,
        private CompteRepository $compteRepository,
        private Environment $twig
    ) {}

    /**
     * Génère un relevé de compte PDF
     */
    public function generateReleveCompte(User $user, Compte $compte, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): Response
    {
        // Définir les dates par défaut (derniers 30 jours)
        if (!$dateDebut) {
            $dateDebut = new \DateTime('-30 days');
        }
        if (!$dateFin) {
            $dateFin = new \DateTime();
        }

        // Récupérer les mouvements du compte
        $mouvements = $this->mouvementRepository->findByCompteAndDateRange(
            $compte,
            $dateDebut,
            $dateFin
        );

        // Calculer les totaux
        $totalEntrees = 0;
        $totalSorties = 0;
        $soldeInitial = $compte->getSoldeInitial();
        $soldeActuel = $compte->getSoldeActuel();

        foreach ($mouvements as $mouvement) {
            if (in_array($mouvement->getType(), [Mouvement::TYPE_ENTREE, Mouvement::TYPE_DON])) {
                $totalEntrees += $mouvement->getMontant();
            } else {
                $totalSorties += $mouvement->getMontant();
            }
        }

        // Générer le HTML avec Twig
        $html = $this->twig->render('pdf/releve_compte.html.twig', [
            'user' => $user,
            'compte' => $compte,
            'mouvements' => $mouvements,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'totalEntrees' => $totalEntrees,
            'totalSorties' => $totalSorties,
            'soldeInitial' => $soldeInitial,
            'soldeActuel' => $soldeActuel
        ]);

        return $this->generatePdfResponse($html, "releve-compte-{$compte->getId()}.pdf");
    }

    /**
     * Génère un rapport financier global PDF
     */
    public function generateRapportFinancier(User $user, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): Response
    {
        // Définir les dates par défaut (derniers 30 jours)
        if (!$dateDebut) {
            $dateDebut = new \DateTime('-30 days');
        }
        if (!$dateFin) {
            $dateFin = new \DateTime();
        }

        // Récupérer tous les comptes de l'utilisateur
        $comptes = $this->compteRepository->findBy(['user' => $user, 'actif' => true]);

        // Récupérer tous les mouvements
        $mouvements = $this->mouvementRepository->findByUserAndDateRange($user, $dateDebut, $dateFin);

        // Calculer les statistiques globales
        $stats = $this->calculateGlobalStats($mouvements, $comptes);

        // Générer le HTML avec Twig
        $html = $this->twig->render('pdf/rapport_financier.html.twig', [
            'user' => $user,
            'comptes' => $comptes,
            'mouvements' => $mouvements,
            'stats' => $stats,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin
        ]);

        return $this->generatePdfResponse($html, "rapport-financier-{$user->getId()}.pdf");
    }


    /**
     * Calcule les statistiques globales
     */
    private function calculateGlobalStats(array $mouvements, array $comptes): array
    {
        $totalPatrimoine = 0;
        foreach ($comptes as $compte) {
            $totalPatrimoine += $compte->getSoldeActuel();
        }

        return [
            'totalPatrimoine' => $totalPatrimoine,
            'nombreComptes' => count($comptes),
            'nombreMouvements' => count($mouvements)
        ];
    }

    /**
     * Génère la réponse PDF
     */
    private function generatePdfResponse(string $html, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isJavascriptEnabled', false);
        $options->set('debugKeepTemp', false);
        $options->set('debugCss', false);
        $options->set('debugLayout', false);
        $options->set('debugLayoutLines', false);
        $options->set('debugLayoutBlocks', false);
        $options->set('debugLayoutInline', false);
        $options->set('debugLayoutPaddingBox', false);
        $options->set('fontCache', sys_get_temp_dir());
        $options->set('tempDir', sys_get_temp_dir());
        $options->set('chroot', realpath(__DIR__ . '/../../public'));
        $options->set('logOutputFile', null);
        $options->set('defaultMediaType', 'print');
        $options->set('defaultPaperSize', 'A4');
        $options->set('defaultPaperOrientation', 'portrait');
        $options->set('dpi', 96);
        $options->set('fontHeightRatio', 1.1);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();

        $response = new Response($output);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');

        return $response;
    }
}
