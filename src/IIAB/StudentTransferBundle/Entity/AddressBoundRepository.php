<?php
/**
 * Created by PhpStorm.
 * User: michellegivens
 * Date: 12/26/14
 * Time: 2:28 PM
 */

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\EntityRepository;

class AddressBoundRepository extends EntityRepository {

	/**
	 * Find all the address like the parameter passed in.
	 *
	 * @param $address
	 * @param $zip
	 *
	 * @return array
	 */
	public function findAddressLike( $address , $zip ) {

		return $this->createQueryBuilder( 'a' )
			->where( 'a.address_fu LIKE :address' )
			->andWhere( 'a.zipcode = :zip' )
			->setParameter( 'address' , $address . '%' )
			->setParameter( 'zip' , $zip )
			->getQuery()
			->getResult();
	}

	/**
	 * Find a specific address without an wildcard parameters
	 *
	 * @param $address
	 * @param $zip
	 *
	 * @return array
	 */
	public function findSpecificAddress( $address , $zip ) {

		return $this->createQueryBuilder( 'a' )
			->where( 'TRIM(LOWER( a.address_fu )) LIKE :address' )
			->andWhere( 'a.zipcode = :zip' )
			->setParameter( 'address' , trim( strtolower( $address ) ) )
			->setParameter( 'zip' , $zip )
			->getQuery()->getResult();
			var_dump( $test ); die;
	}
}