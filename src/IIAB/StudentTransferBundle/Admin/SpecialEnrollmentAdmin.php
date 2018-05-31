<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Finder\Finder;

class SpecialEnrollmentAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_special_enrollment';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'specialenrollment';


	protected function configureFormFields( FormMapper $form ) {

		$form
			->with( 'Special Enrollment Dates' , array( 'class' => 'col-md-12' ) )
			->with( 'Mailed Date' , array( 'class' => 'col-md-12' ) )
		;

		$form
			->with( 'Special Enrollment Dates' )
			->add('title', null, array( 'label' => 'Period Name' ))
			->add('enrollmentPeriod')
			->add('form', 'entity', array(
				'placeholder' => 'Choose an Form Type',
				'class' => 'IIAB\StudentTransferBundle\Entity\Form',
				'query_builder' => function ($er) {
					return $er->createQueryBuilder('se')
						->where('se.id IN (:form_types)')
						->setParameter('form_types', [2, 3]);
				}
			) )
			->add('beginningDate' , 'datetime' , array(
				'label' => 'Starting Date'
			) )
			->add('endingDate' , 'datetime' , array(
				'label' => 'Ending Date'
			) )
			->end()
			->with( 'Mailed Date' )
			->add('mailDate' , 'date' , array(
				'label' => 'Mailed Date'
			) )
			->end()
		;
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier('id')
			->add('title', null, array( 'label' => 'Period Name' ) )
			->add('enrollmentPeriod')
			->add('form')
			->add('beginningDate' , null , array( 'label' => 'Start Date' ) )
			->add('endingDate' , null , array( 'label' => 'End Date' ) )
			->add('mailDate' , null , array( 'label' => 'Mailed Date' ) )
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('enrollmentPeriod')
			->add('form')
		;
	}

}