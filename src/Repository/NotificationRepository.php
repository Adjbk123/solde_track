<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Récupère les notifications d'un utilisateur avec pagination
     */
    public function findByUser(User $user, int $page = 1, int $limit = 20, ?bool $isRead = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($isRead !== null) {
            $qb->andWhere('n.isRead = :isRead')
               ->setParameter('isRead', $isRead);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de notifications non lues pour un utilisateur
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsReadByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->set('n.readAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime toutes les notifications lues d'un utilisateur
     */
    public function deleteReadByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.user = :user')
            ->andWhere('n.isRead = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les notifications par type pour un utilisateur
     */
    public function findByUserAndType(User $user, string $type, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les notifications récentes d'un utilisateur
     */
    public function findRecentByUser(User $user, int $days = 7): array
    {
        $dateLimit = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.createdAt >= :dateLimit')
            ->setParameter('user', $user)
            ->setParameter('dateLimit', $dateLimit)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime les anciennes notifications (plus de X jours)
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        $dateLimit = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :dateLimit')
            ->setParameter('dateLimit', $dateLimit)
            ->getQuery()
            ->execute();
    }
}
