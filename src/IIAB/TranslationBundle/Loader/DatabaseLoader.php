<?php
/**
 * Company: Image In A Box
 * Date: 8/5/15
 * Time: 3:17 PM
 * Copyright: 2015
 */

namespace IIAB\TranslationBundle\Loader;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class DatabaseLoader implements LoaderInterface {

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	private $translationRepository;

	/**
	 * @var \Doctrine\ORM\EntityRepository
	 */
	private $languageRepository;

	/**
	 * Constructor
	 *
	 * @param EntityManager $entityManager
	 */
	public function __construct( EntityManager $entityManager ) {

		$this->translationRepository = $entityManager->getRepository( 'IIABTranslationBundle:LanguageTranslation' );
		$this->languageRepository = $entityManager->getRepository( 'IIABTranslationBundle:Language' );
	}

	/**
	 * Loads a locale.
	 *
	 * @param mixed  $resource A resource
	 * @param string $locale A locale
	 * @param string $domain The domain
	 *
	 * @return MessageCatalogue A MessageCatalogue instance
	 *
	 * @api
	 *
	 * @throws NotFoundResourceException when the resource cannot be found
	 * @throws InvalidResourceException  when the resource cannot be loaded
	 */
	public function load( $resource , $locale , $domain = 'messages' ) {

		$language = $this->languageRepository->findOneByLocale( $locale );

		$translations = $this->translationRepository->getTranslations( $language , 'messages' );

		$catalogue = new MessageCatalogue( $locale );

		/** @var \IIAB\TranslationBundle\Entity\LanguageTranslation $translation */
		foreach( $translations as $translation ) {
			$catalogue->set( $translation->getLanguageToken()->getToken() , $translation->getTranslation() , 'messages' );
			$catalogue->set( $translation->getLanguageToken()->getToken() , $translation->getTranslation() , 'IIABStudentTransferBundle' );
		}

		$translations = null;
		$translations = $this->translationRepository->getTranslations( $language , 'validators' );

		foreach( $translations as $translation ) {
			$catalogue->set( $translation->getLanguageToken()->getToken() , $translation->getTranslation() , 'validators' );
		}
		$translations = null;

		return $catalogue;
	}

	/**
	 * Need to clear the cache upon updating a translation.
	 * TODO: Move this to the Admin Function.
	 */
	private function clearLanguageCache() {

		$cacheDir = __DIR__ . "/../../../../app/cache";

		$finder = new Finder();

		//TODO quick hack...
		$finder->in( array( $cacheDir . "/dev/translations" , $cacheDir . "/prod/translations" ) )->files();

		foreach( $finder as $file ) {
			unlink( $file->getRealpath() );
		}
	}
}