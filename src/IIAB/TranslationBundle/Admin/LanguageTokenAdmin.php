<?php
/**
 * Company: Image In A Box
 * Date: 8/7/15
 * Time: 1:42 PM
 * Copyright: 2015
 */

namespace IIAB\TranslationBundle\Admin;

use IIAB\TranslationBundle\Entity\LanguageToken;
use IIAB\TranslationBundle\Entity\LanguageTranslation;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Finder\Finder;

class LanguageTokenAdmin extends Admin {

	/**
	 * @var string
	 */
	protected $baseRouteName = 'admin_language';

	/**
	 * @var string
	 */
	protected $baseRoutePattern = 'translation';

	/**
	 * {@inheritdoc}
	 */
	protected function configureListFields( ListMapper $list ) {

		$list
			->addIdentifier( 'description' )
			->add( 'englishTranslation' , null , [ 'label' => 'English' ] )
			->add( 'spanishTranslation' , null , [ 'label' => 'Spanish' ] );
	}

	/**
	 * @return array
	 */
	public function getExportFields() {

		return array(
			'Description' => 'description',
			'English Translation' => 'englishTranslationFull' ,
			'Spanish Translation' => 'spanishTranslationFull' ,
		);
	}


	/**
	 * {@inheritdoc}
	 */
	protected function configureFormFields( FormMapper $form ) {

		/** @var \IIAB\TranslationBundle\Entity\LanguageToken $entity */
		$entity = $this->getSubject();
		if( count( $entity->getTranslations() ) != 2 ) {
			$entity = $this->alwaysMakeSureTokenHasBothTranslations( $entity );
		}

		$form
			->add( 'translations' , 'sonata_type_collection' , [
				'btn_add' => false ,
				'type_options' => [
					// Prevents the "Delete" option from being displayed
					'delete' => false ,
				]
			] , [
				'edit' => 'inline' ,
				'inline' => 'language' ,
			] );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function configureRoutes( RouteCollection $collection ) {

		$collection->clearExcept( [ 'list' , 'edit' , 'export' ] );
	}

	/**
	 * {@inheritdoc}
	 */
	public function getExportFormats() {

		return array(
			'csv', 'xls'
		);
	}


	/**
	 * {@inheritdoc}
	 */
	public function postUpdate( $object ) {

		$cacheDir = $this->getConfigurationPool()->getContainer()->get( 'kernel' )->getCacheDir();
		$finder = new Finder();
		$finder->in( array( $cacheDir . "/../*/translations" ) )->files();

		foreach( $finder as $file ) {
			unlink( $file->getRealpath() );
		}
	}




	/**
	 *
	 * @param \IIAB\TranslationBundle\Entity\LanguageToken $entity
	 *
	 * @return LanguageToken
	 */
	private function alwaysMakeSureTokenHasBothTranslations( LanguageToken $entity ) {

		$DM = $this->getConfigurationPool()->getContainer()->get( 'doctrine' )->getManager();
		$english = $DM->getRepository( 'IIABTranslationBundle:Language' )->findOneByLocale( 'en' );
		$spanish = $DM->getRepository( 'IIABTranslationBundle:Language' )->findOneByLocale( 'es' );

		if( count( $entity->getTranslations() ) == 0 ) {
			$translationEN = new LanguageTranslation();
			$translationEN->setLanguageToken( $entity );
			$translationEN->setLanguage( $english );
			$translationEN->setCatalogue( 'messages' );
			$entity->addTranslation( $translationEN );
			$DM->persist( $translationEN );

			$translationES = new LanguageTranslation();
			$translationES->setLanguageToken( $entity );
			$translationES->setLanguage( $spanish );
			$translationES->setCatalogue( 'messages' );
			$entity->addTranslation( $translationES );
			$DM->persist( $translationES );
		}

		if( count( $entity->getTranslations() ) == 1 ) {
			/** @var \IIAB\TranslationBundle\Entity\LanguageTranslation $translation */
			$languageToAdd = '';
			foreach( $entity->getTranslations() as $translation ) {
				if( $translation->getLanguage() == $english ) {
					$languageToAdd = $spanish;
					break;
				}
				if( $translation->getLanguage() == $spanish ) {
					$languageToAdd = $english;
					break;
				}
			}

			if( $languageToAdd != '' ) {
				$translationNew = new LanguageTranslation();
				$translationNew->setLanguageToken( $entity );
				$translationNew->setLanguage( $languageToAdd );
				$translationNew->setCatalogue( 'messages' );
				$entity->addTranslation( $translationNew );
				$DM->persist( $translationNew );
			} else {
				throw new Exception( 'Try to balance out translations but did not find which one.' , 5000 );
			}
		}
		$DM->flush();

		return $entity;
	}

}