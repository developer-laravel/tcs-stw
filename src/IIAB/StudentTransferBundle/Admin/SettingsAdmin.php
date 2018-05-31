<?php

namespace IIAB\StudentTransferBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use IIAB\StudentTransferBundle\Admin\SettingsSchoolListType;

class SettingsAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'stw_admin_setting';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'settings';

	protected function configureFormFields( FormMapper $form ) {

		$object = $this->getSubject();

		$form
			->add('settingName' , null , array(
				'attr' => [ 'readonly' => 'readonly' ]
			) );

		$school_list_fields = [
			'Failing and Former Failing Schools',
			'Schools Accepting Acountability Act Transfers',
			'School Choice Schools',
			'Success Prep Schools'
		];

		if( in_array( $object->getSettingName(), $school_list_fields )
			|| strrpos($object->getSettingName(), 'Work Site -') !== false
		){
			$entity_manager = $this->getConfigurationPool()->getContainer()->get('doctrine')->getEntityManager();

			$schoolGroups = $entity_manager->getRepository('IIABStudentTransferBundle:SchoolGroup')
				->findAll();

			$choices = [];
			foreach( $schoolGroups as $schoolGroup ){
				$choices[ $schoolGroup->getName() ] = $schoolGroup->getId();
			}

			$data = $object->getSettingValue();

			$data = ( $data ) ? json_decode($data, true) : null;

			if( $data != null ){
				$new_data = [];
				foreach( $data as $datum ){

					if( !is_array( $datum ) ){
						$new_data[] = [
							'school' => $datum,
							'renewalOnly' => '0',
						];
					} else {
						$new_data[] = $datum;
					}
				}
				$data = $new_data;
			}

			$form
				->add( 'schools' , 'collection' , array(
					'entry_type' => SettingsSchoolListType::class ,
					'allow_delete' => true ,
					'allow_add' => true ,
					'delete_empty' => true ,
					'prototype_name' => 'School' ,
					'label' => 'Schools' ,
					'entry_options' => array(
						'choices' => $choices,
					) ,
					'data' => $data,
					'mapped' => false,
				) );

		} else {
			$form
				->add('settingValue' , null , array(
					'label' => 'English Version' ,
					'attr' => array( 'style' => 'width: 100%; height: 150px;')
				) )
				->add('settingValue_es' , null , array( 'label' => 'Spanish Version' , 'attr' => array( 'style' => 'width: 100%; height: 150px;') ) );
		}
	}

	protected function configureListFields( ListMapper $list ) {
		$list
			->addIdentifier('settingName')
			->add('settingValue' , null , array(
				'label' => 'English Version',
				'template' => 'IIABStudentTransferBundle:Admin:settings_list.html.twig'
			) )
			->add('settingValue_es' , null , array( 'label' => 'Spanish Version' ) )
		;
	}

	protected function configureDatagridFilters( DatagridMapper $filter ) {
		$filter
			->add('settingName')
		;
	}

	public function preUpdate( $object ) {

		$uniqid = $this->getRequest()->query->get( 'uniqid' );
		$formData = $this->getRequest()->request->get( $uniqid );

		if( isset($formData['schools'])) {
			$entity_manager = $this->getConfigurationPool()->getContainer()->get('doctrine')->getEntityManager();


			$object->setSettingValue( json_encode( $formData['schools'] ) );
			$entity_manager->persist( $object );
		}
	}
}