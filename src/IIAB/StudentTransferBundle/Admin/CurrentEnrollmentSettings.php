<?php
/**
 * Created by PhpStorm.
 * User: DerrickWales
 * Date: 4/24/15
 * Time: 11:12 AM
 */

namespace IIAB\StudentTransferBundle\Admin;


use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class CurrentEnrollmentSettings extends Admin {
	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_current_enrollment_settings';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'current-enrollment-settings';

	protected function configureFormFields( FormMapper $form ) {

		$form
			->add( 'schoolName' )
			->add( 'black' )
			->add( 'white' )
			->add( 'other' )
		;
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier( 'schoolName' , null , array( 'label' => 'School Name' ) )
			->add( 'black' )
			->add( 'white' )
			->add( 'other' )
		;
	}

	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clear();
		$collection
			->add( 'list' )
			->add( 'edit' )
			->add( 'create' );
	}
}