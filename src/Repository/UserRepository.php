<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // ─── Méthodes admin ───────────────────────────────────────────────────────

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBanned(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.isBanned = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countNew(int $days = 30): int
    {
        $since = new \DateTimeImmutable("-{$days} days");
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Liste tous les utilisateurs pour l'admin, triés par inscription récente.
     *
     * @return User[]
     */
    public function findAllForAdmin(): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche rapide par email ou username (pour la barre de recherche admin).
     *
     * @return User[]
     */
    public function searchAdmin(string $q): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email LIKE :q OR u.username LIKE :q')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    // ─── Exploration / Profil public ─────────────────────────────────────────

    /**
     * Recherche de créateurs par username/prénom/nom, avec comptage de projets publics.
     * Retourne des tableaux mixtes [0 => User, 'project_count' => int].
     *
     * @return array<array{0: User, project_count: string}>
     */
    /**
     * Cherche des créateurs avec leur nombre de projets publics.
     * Si $q est vide → retourne tous les créateurs actifs ayant au moins 1 projet public.
     *
     * @param string $sort  'az' | 'za' | 'count'
     */
    public function searchCreatorsWithProjectCount(
        string $q      = '',
        int    $limit  = 24,
        int    $offset = 0,
        string $sort   = 'az',
    ): array {
        $qb = $this->createQueryBuilder('u')
            ->select('u', 'COUNT(p.id) AS project_count')
            ->innerJoin('u.projects', 'p', 'WITH', "p.visibility = 'public' AND p.moderationStatus != :blocked")
            ->setParameter('blocked', 'blocked')
            ->andWhere('u.isBanned = false')
            ->groupBy('u.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($q !== '') {
            $qb->andWhere('LOWER(u.username) LIKE :q OR LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        match ($sort) {
            'za'    => $qb->orderBy('u.username', 'DESC'),
            'count' => $qb->orderBy('project_count', 'DESC')->addOrderBy('u.username', 'ASC'),
            default => $qb->orderBy('u.username', 'ASC'), // 'az'
        };

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les créateurs pour la pagination.
     * Si $q est vide → compte tous les créateurs avec au moins 1 projet public.
     */
    public function countCreatorsForSearch(string $q = ''): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->innerJoin('u.projects', 'p', 'WITH', "p.visibility = 'public' AND p.moderationStatus != :blocked")
            ->setParameter('blocked', 'blocked')
            ->andWhere('u.isBanned = false');

        if ($q !== '') {
            $qb->andWhere('LOWER(u.username) LIKE :q OR LOWER(u.firstName) LIKE :q OR LOWER(u.lastName) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Top créateurs (pour sidebar exploration sans recherche).
     * Retourne des tableaux mixtes [0 => User, 'project_count' => int].
     *
     * @return array<array{0: User, project_count: string}>
     */
    public function findTopCreatorsWithCount(int $limit = 8): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'COUNT(p.id) AS project_count')
            ->innerJoin('u.projects', 'p', 'WITH', "p.visibility = 'public' AND p.moderationStatus != :blocked")
            ->setParameter('blocked', 'blocked')
            ->andWhere('u.isBanned = false')
            ->groupBy('u.id')
            ->orderBy('project_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
