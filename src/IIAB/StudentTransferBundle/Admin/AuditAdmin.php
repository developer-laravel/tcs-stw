<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class AuditAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_audit';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'audit-tracking';

	/**
	 * Default Datagrid values
	 *
	 * @var array
	 */
	protected $datagridValues = array(
		'_page' => 1,            // display the first page (default = 1)
		'_sort_order' => 'DESC', // reverse order (default = 'ASC')
		'_sort_by' => 'timestamp'  // name of the ordered field
		// (default = the model's id field, if any)

		// the '_sort_by' key can be of the form 'mySubModel.mySubSubModel.myField'.
	);


	/*
	protected function configureFormFields( FormMapper $form ) {

		$form
			->add('timestamp' )
			->add('submissionID')
			->add('userID')
			->add('studentID')
			->add('ipaddress')
			->add('auditCodeID')
		;
	}
	*/

	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier('timestamp' , null , array( 'label' => 'Date/Time' , 'format' => 'm/d/y H:i' ) )
			->add('submissionID' , null , array( 'label' => 'Submission #' ))
			->add('userID' , null , array( 'label' => 'User ID' ) )
			->add('studentID' , null , array( 'label' => 'Student #' ))
			->add('ipaddress' , null , array( 'label' => 'IP Address' ))
			->add('auditCodeID' , null , array( 'label' => 'Audit Message' ))
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('timestamp')
			->add('submissionID')
			->add('userID')
			->add('studentID')
			->add('ipaddress')
			->add('auditCodeID')
		;
	}


}