<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Compte;
use App\Repository\MouvementRepository;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Symfony\Component\HttpFoundation\Response;

class ExcelExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MouvementRepository $mouvementRepository,
        private CompteRepository $compteRepository
    ) {}

    /**
     * Export complet en Excel avec plusieurs feuilles
     */
    public function exportComplet(User $user, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): Response
    {
        // Définir les dates par défaut (derniers 30 jours)
        if (!$dateDebut) {
            $dateDebut = new \DateTime('-30 days');
        }
        if (!$dateFin) {
            $dateFin = new \DateTime();
        }

        $spreadsheet = new Spreadsheet();
        
        // Supprimer la feuille par défaut
        $spreadsheet->removeSheetByIndex(0);

        // 1. Feuille Résumé
        $this->createResumeSheet($spreadsheet, $user, $dateDebut, $dateFin);
        
        // 2. Feuille Mouvements
        $this->createMouvementsSheet($spreadsheet, $user, $dateDebut, $dateFin);
        
        // 3. Feuille Comptes
        $this->createComptesSheet($spreadsheet, $user);
        
        // 4. Feuille Contacts
        $this->createContactsSheet($spreadsheet, $user);
        
        // 5. Feuille Dépenses Prévues
        $this->createDepensesPrevuesSheet($spreadsheet, $user);
        
        // 6. Feuille Graphiques
        $this->createGraphiquesSheet($spreadsheet, $user, $dateDebut, $dateFin);

        return $this->generateExcelResponse($spreadsheet, 'rapport-complet-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export des mouvements en Excel
     */
    public function exportMouvements(User $user, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null): Response
    {
        if (!$dateDebut) {
            $dateDebut = new \DateTime('-30 days');
        }
        if (!$dateFin) {
            $dateFin = new \DateTime();
        }

        $spreadsheet = new Spreadsheet();
        $this->createMouvementsSheet($spreadsheet, $user, $dateDebut, $dateFin);

        return $this->generateExcelResponse($spreadsheet, 'mouvements-' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Crée la feuille Résumé
     */
    private function createResumeSheet(Spreadsheet $spreadsheet, User $user, \DateTime $dateDebut, \DateTime $dateFin): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Résumé');
        
        // En-tête
        $sheet->setCellValue('A1', 'RAPPORT FINANCIER - ' . strtoupper($user->getNom() . ' ' . $user->getPrenoms()));
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->setCellValue('A2', 'Période du ' . $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y'));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Récupérer les données
        $comptes = $this->compteRepository->findBy(['user' => $user, 'actif' => true]);
        $mouvements = $this->mouvementRepository->findByUserAndDateRange($user, $dateDebut, $dateFin);
        
        // Calculer les statistiques
        $totalPatrimoine = 0;
        $totalEntrees = 0;
        $totalSorties = 0;
        
        foreach ($comptes as $compte) {
            $totalPatrimoine += $compte->getSoldeActuel();
        }
        
        foreach ($mouvements as $mouvement) {
            if (in_array($mouvement->getType(), ['entree', 'don'])) {
                $totalEntrees += $mouvement->getMontant();
            } else {
                $totalSorties += $mouvement->getMontant();
            }
        }
        
        // Résumé financier
        $sheet->setCellValue('A4', 'RÉSUMÉ FINANCIER');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A5', 'Nombre de comptes actifs:');
        $sheet->setCellValue('B5', count($comptes));
        
        $sheet->setCellValue('A6', 'Nombre de mouvements:');
        $sheet->setCellValue('B6', count($mouvements));
        
        $sheet->setCellValue('A7', 'Patrimoine total:');
        $sheet->setCellValue('B7', number_format($totalPatrimoine, 2, ',', ' ') . ' XOF');
        
        $sheet->setCellValue('A8', 'Total entrées:');
        $sheet->setCellValue('B8', number_format($totalEntrees, 2, ',', ' ') . ' XOF');
        
        $sheet->setCellValue('A9', 'Total sorties:');
        $sheet->setCellValue('B9', number_format($totalSorties, 2, ',', ' ') . ' XOF');
        
        $sheet->setCellValue('A10', 'Solde net:');
        $sheet->setCellValue('B10', number_format($totalEntrees - $totalSorties, 2, ',', ' ') . ' XOF');
        
        // Style des cellules
        $this->applyHeaderStyle($sheet, 'A4:B10');
        $this->applyDataStyle($sheet, 'A5:B10');
        
        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(25);
    }

    /**
     * Crée la feuille Mouvements
     */
    private function createMouvementsSheet(Spreadsheet $spreadsheet, User $user, \DateTime $dateDebut, \DateTime $dateFin): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Mouvements');
        
        // En-têtes
        $headers = ['ID', 'Date', 'Type', 'Description', 'Montant', 'Compte', 'Catégorie', 'Dépense Prévue', 'Contact'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        // Style des en-têtes
        $this->applyHeaderStyle($sheet, 'A1:I1');
        
        // Données
        $mouvements = $this->mouvementRepository->findByUserAndDateRange($user, $dateDebut, $dateFin);
        $row = 2;
        
        foreach ($mouvements as $mouvement) {
            $sheet->setCellValue('A' . $row, $mouvement->getId());
            $sheet->setCellValue('B' . $row, $mouvement->getDate()->format('d/m/Y H:i'));
            $sheet->setCellValue('C' . $row, $mouvement->getTypeLabel());
            $sheet->setCellValue('D' . $row, $mouvement->getDescription() ?: '');
            $sheet->setCellValue('E' . $row, number_format($mouvement->getMontant(), 2, ',', ' '));
            $sheet->setCellValue('F' . $row, $mouvement->getCompte()->getNom());
            $sheet->setCellValue('G' . $row, $mouvement->getCategorie() ? $mouvement->getCategorie()->getNom() : '');
            $sheet->setCellValue('H' . $row, $mouvement->getDepensePrevue() ? $mouvement->getDepensePrevue()->getNom() : '');
            $sheet->setCellValue('I' . $row, $mouvement->getContact() ? $mouvement->getContact()->getNom() : '');
            $row++;
        }
        
        // Style des données
        $this->applyDataStyle($sheet, 'A2:I' . ($row - 1));
        
        // Ajuster les largeurs
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
    }

    /**
     * Crée la feuille Comptes
     */
    private function createComptesSheet(Spreadsheet $spreadsheet, User $user): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Comptes');
        
        // En-têtes
        $headers = ['ID', 'Nom', 'Type', 'Description', 'Solde Initial', 'Solde Actuel', 'Devise', 'Numéro', 'Institution', 'Date Création', 'Actif'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $this->applyHeaderStyle($sheet, 'A1:K1');
        
        // Données
        $comptes = $this->compteRepository->findBy(['user' => $user, 'actif' => true]);
        $row = 2;
        
        foreach ($comptes as $compte) {
            $sheet->setCellValue('A' . $row, $compte->getId());
            $sheet->setCellValue('B' . $row, $compte->getNom());
            $sheet->setCellValue('C' . $row, $compte->getTypeLabel());
            $sheet->setCellValue('D' . $row, $compte->getDescription() ?: '');
            $sheet->setCellValue('E' . $row, number_format($compte->getSoldeInitial(), 2, ',', ' '));
            $sheet->setCellValue('F' . $row, number_format($compte->getSoldeActuel(), 2, ',', ' '));
            $sheet->setCellValue('G' . $row, $compte->getDevise() ? $compte->getDevise()->getCode() : 'XOF');
            $sheet->setCellValue('H' . $row, $compte->getNumero() ?: '');
            $sheet->setCellValue('I' . $row, $compte->getInstitution() ?: '');
            $sheet->setCellValue('J' . $row, $compte->getDateCreation()->format('d/m/Y'));
            $sheet->setCellValue('K' . $row, $compte->isActif() ? 'Oui' : 'Non');
            $row++;
        }
        
        $this->applyDataStyle($sheet, 'A2:K' . ($row - 1));
        
        // Ajuster les largeurs
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
    }

    /**
     * Crée la feuille Contacts
     */
    private function createContactsSheet(Spreadsheet $spreadsheet, User $user): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Contacts');
        
        // En-têtes
        $headers = ['ID', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Type', 'Date Création', 'Total Transactions'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $this->applyHeaderStyle($sheet, 'A1:H1');
        
        // Données
        $contacts = $this->entityManager->getRepository(\App\Entity\Contact::class)
            ->findBy(['user' => $user]);
        $row = 2;
        
        foreach ($contacts as $contact) {
            $sheet->setCellValue('A' . $row, $contact->getId());
            $sheet->setCellValue('B' . $row, $contact->getNom());
            $sheet->setCellValue('C' . $row, $contact->getPrenom());
            $sheet->setCellValue('D' . $row, $contact->getEmail() ?: '');
            $sheet->setCellValue('E' . $row, $contact->getTelephone() ?: '');
            $sheet->setCellValue('F' . $row, $contact->getType());
            $sheet->setCellValue('G' . $row, $contact->getDateCreation()->format('d/m/Y'));
            $sheet->setCellValue('H' . $row, $contact->getMouvements()->count());
            $row++;
        }
        
        $this->applyDataStyle($sheet, 'A2:H' . ($row - 1));
        
        // Ajuster les largeurs
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
    }

    /**
     * Crée la feuille Dépenses Prévues
     */
    private function createDepensesPrevuesSheet(Spreadsheet $spreadsheet, User $user): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Dépenses Prévues');
        
        // En-têtes
        $headers = ['ID', 'Nom', 'Description', 'Montant Prévu', 'Montant Dépensé', 'Date Création', 'Statut', 'Nombre Mouvements'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        
        $this->applyHeaderStyle($sheet, 'A1:H1');
        
        // Données
        $depensesPrevues = $this->entityManager->getRepository(\App\Entity\DepensePrevue::class)
            ->findBy(['user' => $user]);
        $row = 2;
        
        foreach ($depensesPrevues as $depensePrevue) {
            $sheet->setCellValue('A' . $row, $depensePrevue->getId());
            $sheet->setCellValue('B' . $row, $depensePrevue->getNom());
            $sheet->setCellValue('C' . $row, $depensePrevue->getDescription() ?: '');
            $sheet->setCellValue('D' . $row, number_format($depensePrevue->getBudgetPrevu(), 2, ',', ' '));
            $sheet->setCellValue('E' . $row, number_format($depensePrevue->getMontantDepense(), 2, ',', ' '));
            $sheet->setCellValue('F' . $row, $depensePrevue->getDateCreation()->format('d/m/Y'));
            $sheet->setCellValue('G' . $row, $depensePrevue->getStatut());
            $sheet->setCellValue('H' . $row, $depensePrevue->getMouvements()->count());
            $row++;
        }
        
        $this->applyDataStyle($sheet, 'A2:H' . ($row - 1));
        
        // Ajuster les largeurs
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
    }

    /**
     * Crée la feuille Graphiques
     */
    private function createGraphiquesSheet(Spreadsheet $spreadsheet, User $user, \DateTime $dateDebut, \DateTime $dateFin): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Graphiques');
        
        // En-tête
        $sheet->setCellValue('A1', 'ANALYSE FINANCIÈRE');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Données pour les graphiques
        $mouvements = $this->mouvementRepository->findByUserAndDateRange($user, $dateDebut, $dateFin);
        
        // Analyse par type
        $types = [];
        foreach ($mouvements as $mouvement) {
            $type = $mouvement->getTypeLabel();
            if (!isset($types[$type])) {
                $types[$type] = 0;
            }
            $types[$type] += $mouvement->getMontant();
        }
        
        // Tableau des données
        $sheet->setCellValue('A3', 'Type de mouvement');
        $sheet->setCellValue('B3', 'Montant total');
        $this->applyHeaderStyle($sheet, 'A3:B3');
        
        $row = 4;
        foreach ($types as $type => $montant) {
            $sheet->setCellValue('A' . $row, $type);
            $sheet->setCellValue('B' . $row, number_format($montant, 2, ',', ' ') . ' XOF');
            $row++;
        }
        
        $this->applyDataStyle($sheet, 'A4:B' . ($row - 1));
        
        // Ajuster les largeurs
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(25);
    }

    /**
     * Applique le style d'en-tête
     */
    private function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E3F2FD');
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * Applique le style des données
     */
    private function applyDataStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($range)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    /**
     * Génère la réponse Excel
     */
    private function generateExcelResponse(Spreadsheet $spreadsheet, string $filename): Response
    {
        $writer = new Xlsx($spreadsheet);
        
        // Créer un fichier temporaire
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
        
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'public');
        
        return $response;
    }
}
