<?php
/**
 * Company: Image In A Box
 * Date: 6/16/15
 * Time: 11:26 AM
 * Copyright: 2015
 */

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\EntityRepository;

class WaitListRepository extends EntityRepository {

	/**
	 * Find by SchoolGroup
	 *
	 * @param SchoolGroup $schoolGroup
	 * @param OpenEnrollment $openEnrollment
	 *
	 * @return array
	 */
	function findBySchoolGroup( SchoolGroup $schoolGroup , OpenEnrollment $openEnrollment ) {

		return $this->createQueryBuilder( 'wait' )
			->leftJoin( 'wait.choiceSchool' , 'adm' )
			->where( 'adm.groupID = :group' )
			->setParameter( 'group' , $schoolGroup )
			->andWhere( 'wait.openEnrollment = :enrollment' )
			->setParameter( 'enrollment' , $openEnrollment )
			->getQuery()
			->getResult();
	}
}