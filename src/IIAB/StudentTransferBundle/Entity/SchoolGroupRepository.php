<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 5/10/15
 * Time: 4:51 PM
 */

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class SchoolGroupRepository extends EntityRepository {

	/**
	 * Get the current Groups with a specific open Enrollment
	 *
	 * @param OpenEnrollment $openEnrollment
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	public function getEnrollmentSchools( OpenEnrollment $openEnrollment ) {

		return $this->createQueryBuilder('s')
			->leftJoin('\IIAB\StudentTransferBundle\Entity\ADM' , 'adm' , Join::WITH , 'adm.groupID = s.id')
			->where('adm.enrollmentPeriod = :enrollment')
			->setParameter('enrollment' , $openEnrollment )
			->orderBy('s.name','ASC')
			->getQuery()
			->getResult();
	}
}