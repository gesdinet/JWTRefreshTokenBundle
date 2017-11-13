<?php

namespace Gesdinet\JWTRefreshTokenBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * RefreshTokenRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RefreshTokenRepository extends EntityRepository
{
    public function findInvalid(\DateTime $datetime = null): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.valid < :datetime')
            ->setParameter(':datetime', $datetime ?? new \DateTime())
            ->getQuery()
            ->getResult();
    }
}
