<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class FormAdmin extends AbstractAdmin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_form';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'forms';

	protected function configureFormFields( FormMapper $form ) {

		$form
			->add('formName' )
			->add('formDescription')
			->add('formConfirmation')
			->add('acceptanceWindow', null, ['label' => 'Number of Days to Accept Offers'])
			//->add('public')
		;
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier( 'formName' )
			->add('formDescription')
			->add('formConfirmation' , null , array( 'label' => 'Confirmation Style' ) )
			//->add('public')
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('formName')
			->add('formDescription')
			//->add('public')
		;
	}

}