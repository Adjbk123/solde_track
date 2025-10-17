<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Compte;
use App\Repository\MouvementRepository;
use App\Repository\CompteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class CsvExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MouvementRepository $mouvementRepository,
        private CompteRepository $compteRepository
    ) {}

    /**
     * Export des mouvements en CSV
     */
    public function exportMouvements(User $user, ?\DateTime $dateDebut = null, ?\DateTime $dateFin = null, array $filters = []): Response
    {
        // Définir les dates par défaut (derniers 30 jours)
        if (!$dateDebut) {
            $dateDebut = new \DateTime('-30 days');
        }
        if (!$dateFin) {
            $dateFin = new \DateTime();
        }

        // Récupérer les mouvements avec filtres
        $mouvements = $this->mouvementRepository->findByUserAndDateRange($user, $dateDebut, $dateFin);

        // Appliquer les filtres supplémentaires
        if (!empty($filters['account_ids'])) {
            $mouvements = array_filter($mouvements, function($mouvement) use ($filters) {
                return in_array($mouvement->getCompte()->getId(), $filters['account_ids']);
            });
        }

        if (!empty($filters['movement_types'])) {
            $mouvements = array_filter($mouvements, function($mouvement) use ($filters) {
                return in_array($mouvement->getType(), $filters['movement_types']);
            });
        }

        // Générer le CSV
        $csvContent = $this->generateMouvementsCsv($mouvements, $user);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="mouvements-' . date('Y-m-d') . '.csv"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');

        return $response;
    }

    /**
     * Export des comptes en CSV
     */
    public function exportComptes(User $user): Response
    {
        $comptes = $this->compteRepository->findBy(['user' => $user, 'actif' => true]);
        
        $csvContent = $this->generateComptesCsv($comptes);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="comptes-' . date('Y-m-d') . '.csv"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');

        return $response;
    }

    /**
     * Export des contacts en CSV
     */
    public function exportContacts(User $user): Response
    {
        $contacts = $this->entityManager->getRepository(\App\Entity\Contact::class)
            ->findBy(['user' => $user]);

        $csvContent = $this->generateContactsCsv($contacts);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="contacts-' . date('Y-m-d') . '.csv"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');

        return $response;
    }

    /**
     * Export des dépenses prévues en CSV
     */
    public function exportDepensesPrevues(User $user): Response
    {
        $depensesPrevues = $this->entityManager->getRepository(\App\Entity\DepensePrevue::class)
            ->findBy(['user' => $user]);

        $csvContent = $this->generateDepensesPrevuesCsv($depensesPrevues);

        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="depenses-prevues-' . date('Y-m-d') . '.csv"');
        $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate');

        return $response;
    }

    /**
     * Génère le CSV des mouvements
     */
    private function generateMouvementsCsv(array $mouvements, User $user): string
    {
        $csv = "ID,Date,Type,Description,Montant,Compte,Catégorie,Dépense Prévue,Contact,Créé le\n";

        foreach ($mouvements as $mouvement) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $mouvement->getId(),
                $mouvement->getDate()->format('Y-m-d H:i:s'),
                $mouvement->getTypeLabel(),
                $this->escapeCsv($mouvement->getDescription() ?: ''),
                number_format($mouvement->getMontant(), 2, ',', ' '),
                $this->escapeCsv($mouvement->getCompte()->getNom()),
                $this->escapeCsv($mouvement->getCategorie() ? $mouvement->getCategorie()->getNom() : ''),
                $this->escapeCsv($mouvement->getDepensePrevue() ? $mouvement->getDepensePrevue()->getNom() : ''),
                $this->escapeCsv($mouvement->getContact() ? $mouvement->getContact()->getNom() : ''),
                $mouvement->getDateCreation()->format('Y-m-d H:i:s')
            );
        }

        return $csv;
    }

    /**
     * Génère le CSV des comptes
     */
    private function generateComptesCsv(array $comptes): string
    {
        $csv = "ID,Nom,Type,Description,Solde Initial,Solde Actuel,Devise,Numéro,Institution,Date Création,Actif\n";

        foreach ($comptes as $compte) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                $compte->getId(),
                $this->escapeCsv($compte->getNom()),
                $this->escapeCsv($compte->getTypeLabel()),
                $this->escapeCsv($compte->getDescription() ?: ''),
                number_format($compte->getSoldeInitial(), 2, ',', ' '),
                number_format($compte->getSoldeActuel(), 2, ',', ' '),
                $compte->getDevise() ? $compte->getDevise()->getCode() : 'XOF',
                $this->escapeCsv($compte->getNumero() ?: ''),
                $this->escapeCsv($compte->getInstitution() ?: ''),
                $compte->getDateCreation()->format('Y-m-d H:i:s'),
                $compte->isActif() ? 'Oui' : 'Non'
            );
        }

        return $csv;
    }

    /**
     * Génère le CSV des contacts
     */
    private function generateContactsCsv(array $contacts): string
    {
        $csv = "ID,Nom,Prénom,Email,Téléphone,Type,Date Création,Total Transactions\n";

        foreach ($contacts as $contact) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%d\n",
                $contact->getId(),
                $this->escapeCsv($contact->getNom()),
                $this->escapeCsv($contact->getPrenom()),
                $this->escapeCsv($contact->getEmail() ?: ''),
                $this->escapeCsv($contact->getTelephone() ?: ''),
                $this->escapeCsv($contact->getType()),
                $contact->getDateCreation()->format('Y-m-d H:i:s'),
                $contact->getMouvements()->count()
            );
        }

        return $csv;
    }

    /**
     * Génère le CSV des dépenses prévues
     */
    private function generateDepensesPrevuesCsv(array $depensesPrevues): string
    {
        $csv = "ID,Nom,Description,Montant Prévu,Montant Dépensé,Date Création,Statut,Nombre Mouvements\n";

        foreach ($depensesPrevues as $depensePrevue) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%d\n",
                $depensePrevue->getId(),
                $this->escapeCsv($depensePrevue->getNom()),
                $this->escapeCsv($depensePrevue->getDescription() ?: ''),
                number_format($depensePrevue->getBudgetPrevu(), 2, ',', ' '),
                number_format($depensePrevue->getMontantDepense(), 2, ',', ' '),
                $depensePrevue->getDateCreation()->format('Y-m-d H:i:s'),
                $this->escapeCsv($depensePrevue->getStatut()),
                $depensePrevue->getMouvements()->count()
            );
        }

        return $csv;
    }

    /**
     * Échappe les caractères spéciaux pour CSV
     */
    private function escapeCsv(string $value): string
    {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}
