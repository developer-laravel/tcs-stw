<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class SubmissionDataAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_submissionData';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'submission-data';

	protected function configureFormFields( FormMapper $list ) {
		$list
			->add( 'metaKey' , null , array( 'label' => 'Key' ) )
			->add( 'metaValue' , null , array( 'label' => 'Value' )  )
		;
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->add( 'submission' , null , array( 'label' => 'Submission ID' ) )
			->add( 'metaKey' )
			->add( 'metaValue' )
		;
	}

	public function getExportFields() {

		return array(
			'metaKey',
			'metaValue'
		);
	}


	protected function configureRoutes( RouteCollection $collection ) {

		//$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
		$collection->clear();
	}
	/*
	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add( 'schoolID' , null , array( 'label' => 'School ID' ) )
			->add( 'grade' )
			->add( 'availableSlots' )
		;
	}
	*/

}