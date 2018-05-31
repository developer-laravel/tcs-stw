<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class UserAdmin extends AbstractAdmin {

	protected $baseRouteName = 'admin_users';

	protected $baseRoutePattern = 'users';

	protected function configureFormFields( FormMapper $form ) {

		$school_groups = $this->getConfigurationPool()->getContainer()
			->get( 'doctrine' )
			->getRepository( 'IIABStudentTransferBundle:SchoolGroup' )
			->findAll();

		$schools = array(
			'' => 'Choose an option'
		);

		foreach( $school_groups as $school ) {
			$schools[$school->getId()] = $school->getName();
		}

		$form_objects = $this->getConfigurationPool()->getContainer()
			->get( 'doctrine' )
			->getRepository( 'IIABStudentTransferBundle:Form' )
			->findAll();

		$forms = array(
			'' => 'Choose an option'
		);

		foreach( $form_objects as $form_object ) {
			$forms[$form_object->getId()] = $form_object->getFormName();
		}

		$form
			->with( 'Profile' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'Account' , array( 'class' => 'col-md-6' ) )->end()
			->with( 'Form Access' , array( 'class' => 'col-md-12' , 'description' => 'Restrict a user to specific forms. If this is empty, they will have access to all forms.' ) )->end()
			->with( 'School Access' , array( 'class' => 'col-md-12' , 'description' => 'Restrict a user to specific schools. If this is empty, they will have access to all school.' ) )->end();
		$form
			->with( 'Profile' )
			->add( 'firstName' )
			->add( 'lastName' )
			->add( 'email' )
			->add( 'username' , null , array( 'label' => 'Confirm Email' ) )
			->add( 'plainPassword' , 'text' , array(
				'required' => ( !$this->getSubject() || is_null( $this->getSubject()->getId() ) ) ,
				'sonata_help' => 'To update a user\'s password, provide one here.'
			) )
			->end()
			->with( 'Account' )
			->add( 'enabled' , null , array(
				'required' => false ,
			) )
			// ->add( 'locked' , null , array(
			// 	'required' => false ,
			// ) )
			->add( 'roles' , 'collection' , array(
				'entry_type' => 'choice' ,
				'allow_delete' => true ,
				'allow_add' => true ,
				'delete_empty' => true ,
				'prototype_name' => 'Role' ,
				'label' => 'Roles' ,
				'entry_options' => array(
					'label' => 'Role' ,
					'choices' => array_flip( array(
						'' => 'Choose an option' ,
						'ROLE_ADMIN' => 'Any User' ,
						'ROLE_SUPER_ADMIN' => 'Super Admin',
						'ROLE_USER' => 'Deactivated User'
					))
				) ,
			) )
			->end()
			->with( 'School Access' )
			->add( 'schools' , 'collection' , array(
				'entry_type' => 'choice' ,
				'allow_delete' => true ,
				'allow_add' => true ,
				'delete_empty' => true ,
				'prototype_name' => 'School' ,
				'label' => 'Schools' ,
				'entry_options' => array(
					'label' => 'School' ,
					'choices' => array_flip( $schools )
				) ,
			) )
			->end()
			->with( 'Form Access' )
			->add( 'forms' , 'collection' , array(
				'entry_type' => 'choice' ,
				'allow_delete' => true ,
				'allow_add' => true ,
				'delete_empty' => true ,
				'prototype_name' => 'Form' ,
				'label' => 'Form' ,
				'entry_options' => array(
					'label' => 'Form' ,
					'choices' => array_flip( $forms )
				) ,
			) )
			->end();
	}

	protected function configureListFields( ListMapper $list ) {

		$list
			->addIdentifier( 'name' )
			->addIdentifier( 'email' )
			->add( 'enabled' , null , array( 'editable' => true ) )
			//->add( 'locked' , null , array( 'editable' => true ) )
			;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {

		$filter
			->add( 'id' )
			->add( 'email' )
			//->add( 'locked' )
			;
	}

	public function preUpdate( $object ) {

        $uniqid = $this->getRequest()->query->get( 'uniqid' );
        $formData = $this->getRequest()->request->get( $uniqid );

        if( isset( $formData['schools'] ) ){
            $object->setSchools( $formData['schools'] );
        }

		$userManager = $this->configurationPool->getContainer()->get('fos_user.user_manager');
		$userManager->updateUser( $object, false );
	}


}