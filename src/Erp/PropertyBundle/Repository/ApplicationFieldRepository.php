<?php

namespace Erp\PropertyBundle\Repository;

use Erp\PropertyBundle\Entity\ApplicationSection;
use Doctrine\ORM\EntityRepository;

/**
 * ApplicationFieldRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ApplicationFieldRepository extends EntityRepository
{
    /**
     * Return sort number
     *
     * @param ApplicationSection $section
     *
     * @return int|mixed
     */
    public function getSortNumber(ApplicationSection $section)
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('af.sort')
            ->from($this->_entityName, 'af')
            ->where('af.applicationSection=:applicationSection')
            ->setParameter('applicationSection', $section)
            ->orderBy('af.sort', 'DESC')
            ->setMaxResults(1)
        ;

        $result = $qb->getQuery()->getOneOrNullResult();
        $result = !$result ? 1 : $result['sort'] + 1;

        return $result;
    }
}
