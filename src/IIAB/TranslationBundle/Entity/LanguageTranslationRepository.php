<?php

namespace IIAB\TranslationBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * LanguageTranslationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LanguageTranslationRepository extends EntityRepository {

	/**
	 * Gets the Translations
	 *
	 * @param int    $language
	 * @param string $catalogue
	 *
	 * @return array
	 */
	public function getTranslations( $language = 0 , $catalogue = 'messages' ) {

		$query = $this->getEntityManager()->createQuery( "SELECT t FROM IIABTranslationBundle:LanguageTranslation t WHERE t.language = :language AND t.catalogue = :catalogue" );
		$query->setParameter( 'language' , $language );
		$query->setParameter( 'catalogue' , $catalogue );

		return $query->getResult();
	}
}
