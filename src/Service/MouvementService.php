<?php

namespace App\Service;

use App\Entity\Mouvement;
use App\Entity\User;
use App\Entity\Compte;
use Doctrine\ORM\EntityManagerInterface;

class MouvementService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère les mouvements d'un compte spécifique
     */
    public function getMouvementsByCompte(User $user, int $compteId, ?string $type = null, ?int $page = 1, ?int $limit = 20): array
    {
        // Vérifier que le compte appartient à l'utilisateur
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        if (!$compte || $compte->getUser() !== $user) {
            throw new \InvalidArgumentException('Compte non trouvé ou non autorisé');
        }

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.compte = :compte')
            ->setParameter('user', $user)
            ->setParameter('compte', $compteId)
            ->orderBy('m.date', 'DESC');

        if ($type) {
            $qb->andWhere('m.type = :type')->setParameter('type', $type);
        }

        // Pagination
        if ($page && $limit) {
            $qb->setFirstResult(($page - 1) * $limit)
               ->setMaxResults($limit);
        }

        $mouvements = $qb->getQuery()->getResult();

        return [
            'mouvements' => $mouvements,
            'compte' => [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $compte->getSoldeActuelFormatted()
            ],
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($mouvements)
            ]
        ];
    }

    /**
     * Récupère les statistiques des mouvements d'un compte
     */
    public function getStatistiquesByCompte(User $user, int $compteId, ?\DateTime $debut = null, ?\DateTime $fin = null): array
    {
        // Vérifier que le compte appartient à l'utilisateur
        $compte = $this->entityManager->getRepository(Compte::class)->find($compteId);
        if (!$compte || $compte->getUser() !== $user) {
            throw new \InvalidArgumentException('Compte non trouvé ou non autorisé');
        }

        $qb = $this->entityManager->getRepository(Mouvement::class)
            ->createQueryBuilder('m')
            ->where('m.user = :user')
            ->andWhere('m.compte = :compte')
            ->setParameter('user', $user)
            ->setParameter('compte', $compteId);

        if ($debut) {
            $qb->andWhere('m.date >= :debut')->setParameter('debut', $debut);
        }
        if ($fin) {
            $qb->andWhere('m.date <= :fin')->setParameter('fin', $fin);
        }

        $mouvements = $qb->getQuery()->getResult();

        $totalEntrees = 0;
        $totalSorties = 0;
        $totalDettes = 0;
        $totalDons = 0;
        $mouvementsParType = [
            'sortie' => 0,
            'entree' => 0,
            'emprunt' => 0,
            'pret' => 0,
            'creance' => 0,
            'don' => 0
        ];
        $mouvementsParCategorie = [];

        foreach ($mouvements as $mouvement) {
            $montant = (float) $mouvement->getMontantEffectif();
            $typeMouvement = $mouvement->getType();
            $categorieNom = $mouvement->getCategorie()->getNom();

            switch ($typeMouvement) {
                case 'sortie':
                    $totalSorties += $montant;
                    break;
                case 'entree':
                    $totalEntrees += $montant;
                    break;
                case 'emprunt':
                case 'pret':
                case 'creance':
                    $totalDettes += $montant;
                    break;
                case 'don':
                    $totalDons += $montant;
                    break;
            }

            $mouvementsParType[$typeMouvement] = ($mouvementsParType[$typeMouvement] ?? 0) + $montant;
            $mouvementsParCategorie[$categorieNom] = ($mouvementsParCategorie[$categorieNom] ?? 0) + $montant;
        }

        $soldeNet = $totalEntrees - $totalSorties - $totalDettes - $totalDons;

        return [
            'compte' => [
                'id' => $compte->getId(),
                'nom' => $compte->getNom(),
                'soldeActuel' => $compte->getSoldeActuel(),
                'soldeActuelFormatted' => $compte->getSoldeActuelFormatted()
            ],
            'totalSorties' => number_format($totalSorties, 2, '.', ''),
            'totalEntrees' => number_format($totalEntrees, 2, '.', ''),
            'totalDettes' => number_format($totalDettes, 2, '.', ''),
            'totalDons' => number_format($totalDons, 2, '.', ''),
            'soldeNet' => number_format($soldeNet, 2, '.', ''),
            'parType' => $mouvementsParType,
            'parCategorie' => $mouvementsParCategorie,
            'periode' => [
                'debut' => $debut ? $debut->format('Y-m-d') : null,
                'fin' => $fin ? $fin->format('Y-m-d') : null
            ]
        ];
    }
}
