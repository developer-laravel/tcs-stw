<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class SlottingAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_slotting';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'slotting';

	protected function configureListFields( ListMapper $list ) {
		$list
			->add( 'schoolID' , null , array( 'label' => 'School ID' ) )
			->add( 'grade' )
			->add( 'availableSlots' )
			->add( 'enrollmentPeriod' )
		;
	}

	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add( 'schoolID' , null , array( 'label' => 'School ID' ) )
			->add( 'grade' )
			->add( 'availableSlots' )
			->add( 'enrollmentPeriod' )
		;
	}

}