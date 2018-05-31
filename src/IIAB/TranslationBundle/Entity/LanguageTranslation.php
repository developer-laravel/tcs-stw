<?php

namespace IIAB\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageTranslation
 *
 * @ORM\Table(name="languagetranslation")
 * @ORM\Entity(repositoryClass="IIAB\TranslationBundle\Entity\LanguageTranslationRepository")
 */
class LanguageTranslation {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="catalogue", type="string", length=255, options={"default":"messages"})
	 */
	private $catalogue = 'messages';

	/**
	 * @var string
	 *
	 * @ORM\Column(name="translation", type="text", nullable=true)
	 */
	private $translation;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\TranslationBundle\Entity\Language")
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	protected $language;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\TranslationBundle\Entity\LanguageToken", inversedBy="translations")
	 * @ORM\JoinColumn(referencedColumnName="id")
	 */
	protected $languageToken;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Set catalogue
	 *
	 * @param string $catalogue
	 *
	 * @return LanguageTranslation
	 */
	public function setCatalogue( $catalogue ) {

		$this->catalogue = $catalogue;

		return $this;
	}

	/**
	 * Get catalogue
	 *
	 * @return string
	 */
	public function getCatalogue() {

		return $this->catalogue;
	}

	/**
	 * Set translation
	 *
	 * @param string $translation
	 *
	 * @return LanguageTranslation
	 */
	public function setTranslation( $translation ) {

		$this->translation = $translation;

		return $this;
	}

	/**
	 * Get translation
	 *
	 * @return string
	 */
	public function getTranslation() {

		return $this->translation;
	}

	/**
	 * Set language
	 *
	 * @param \IIAB\TranslationBundle\Entity\Language $language
	 *
	 * @return LanguageTranslation
	 */
	public function setLanguage( \IIAB\TranslationBundle\Entity\Language $language = null ) {

		$this->language = $language;

		return $this;
	}

	/**
	 * Get language
	 *
	 * @return \IIAB\TranslationBundle\Entity\Language
	 */
	public function getLanguage() {

		return $this->language;
	}

	/**
	 * Set languageToken
	 *
	 * @param \IIAB\TranslationBundle\Entity\LanguageToken $languageToken
	 *
	 * @return LanguageTranslation
	 */
	public function setLanguageToken( \IIAB\TranslationBundle\Entity\LanguageToken $languageToken = null ) {

		$this->languageToken = $languageToken;

		return $this;
	}

	/**
	 * Get languageToken
	 *
	 * @return \IIAB\TranslationBundle\Entity\LanguageToken
	 */
	public function getLanguageToken() {

		return $this->languageToken;
	}
}
