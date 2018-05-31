<?php
/**
 * Company: Image In A Box
 * Date: 7/19/14
 * Time: 2:25 PM
 * Copyright: 2014
 */

namespace IIAB\StudentTransferBundle\Admin;


use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;

class OpenEnrollmentHasFormAdmin extends Admin {


	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_openEnrollmenthasform';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'open-enrollment-forms';

	protected function configureFormFields( FormMapper $form ) {

		$form
			->add('active' , null , array( 'label' => 'Publicly Available' ) )
			->add('form')
		;

	}


} 