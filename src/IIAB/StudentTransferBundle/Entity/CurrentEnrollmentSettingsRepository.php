<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CurrentEnrollmentSettingsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CurrentEnrollmentSettingsRepository extends EntityRepository {

    public function schoolGroupCurrentEnrollmentJoin( ) {
        return $this->createQueryBuilder('c')
            ->Join('c.groupId' , 's' , 'WITH' , 's.id' )
            ->getQuery()
            ->getResult();
    }
}
