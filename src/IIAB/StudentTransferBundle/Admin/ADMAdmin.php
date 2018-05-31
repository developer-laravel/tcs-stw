<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class ADMAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_adm';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'adm';

	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
	}

	/**
	 * @param string $context
	 *
	 * @return \Sonata\AdminBundle\Datagrid\ProxyQueryInterface
	 */
	public function createQuery( $context = 'list' ) {

		$query = parent::createQuery( $context );

		$this->user = $this->getConfigurationPool()->getContainer()->get( 'security.token_storage' )->getToken()->getUser();
		$schools = $this->user->getSchools();

		if(!empty( $schools ) ) {

			$query->orWhere(
				$query->expr()->in( $query->getRootAlias() . '.groupID' , ':schools' )
			);
			$query->setParameter( 'schools' , $schools );
		}

		return $query;
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier( 'schoolID' , null , array( 'label' => 'School ID' ) )
			->add( '__toString' , null , array( 'label' => 'School Name' ) )
			->add( 'blackPercent' )
			->add( 'whitePercent' )
			->add( 'otherPercent' )
			->add( 'grade' )
			->add( 'enrollmentPeriod' )
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add( 'schoolID' )
			->add( 'schoolName' , null , array( 'label' => 'School Name' ) )
			->add( 'blackPercent' )
			->add( 'whitePercent' )
			->add( 'otherPercent' )
			->add( 'grade' )
			->add( 'enrollmentPeriod' )
		;
	}

}