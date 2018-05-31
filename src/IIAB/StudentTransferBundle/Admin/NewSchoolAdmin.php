<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class NewSchoolAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_newschool';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'new-school';

	protected function configureFormFields( FormMapper $form ) {

		$form
			->add('currentSchool')
			->add('currentSchoolID')
			->add('currentGrade')
			->add('newSchool')
			->add('newSchoolID')
			->add('newGrade')
			->add('enrollmentPeriod')
		;
	}

	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clearExcept( array( 'list' , 'show' , 'export' ) );
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier('currentSchool')
			->add('currentSchoolID')
			->add('currentGrade')
			->add('newSchool')
			->add('newSchoolID')
			->add('newGrade')
			->add('enrollmentPeriod')
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('currentSchool')
			->add('currentSchoolID')
			->add('currentGrade')
			->add('newSchool')
			->add('newSchoolID')
			->add('newGrade')
			->add('enrollmentPeriod')
		;
	}

}