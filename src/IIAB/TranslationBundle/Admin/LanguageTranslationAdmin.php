<?php
/**
 * Company: Image In A Box
 * Date: 8/7/15
 * Time: 1:54 PM
 * Copyright: 2015
 */

namespace IIAB\TranslationBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;

class LanguageTranslationAdmin extends Admin {

	/**
	 * {@inheritdoc}
	 */
	protected function configureFormFields( FormMapper $form ) {

		/** @var \IIAB\TranslationBundle\Entity\LanguageTranslation $entity */
		$entity = $this->getSubject();
		if( $entity == null ) {
			$language = 'Language';
			$type = 'text';
			$options = [];
		} else {
			$language = $entity->getLanguage();
			$type = $entity->getLanguageToken()->getType();
			$options = $entity->getLanguageToken()->getAvailableVariables();
			if( !empty ( $options ) ) {
				$options = [ 'data-dynamic' => json_encode( $options ) ];
			} else {
				$options = [];
			}
		}

		$form
			->add( 'translation' , $type , [ 'label' => $language , 'required' => false , 'attr' => array_merge( $options , [ 'class' => 'form-control' ] ) ] );
	}


}