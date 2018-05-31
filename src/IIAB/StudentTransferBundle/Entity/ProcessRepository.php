<?php
/**
 * Company: Image In A Box
 * Date: 3/14/15
 * Time: 1:01 AM
 * Copyright: 2015
 */

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ProcessRepository extends EntityRepository {

	public function findlastFiveMinuteProcess() {

		return $this->createQueryBuilder( 'p' )
			->where( 'p.completedDateTime >= :fiveminutes' )
			->orWhere( 'p.completed = 0' )
			->setParameter( 'fiveminutes' , new \DateTime( '-5 mins' ) )
			->getQuery()
			->getResult();
	}
}