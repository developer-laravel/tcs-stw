<?php

namespace IIAB\TranslationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * LanguageToken
 *
 * @ORM\Table(name="languagetoken")
 * @ORM\Entity()
 */
class LanguageToken {

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
	 * @ORM\Column(name="token", type="string", length=255, unique=true)
	 */
	private $token;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="description", type="string", length=255, nullable=true)
	 */
	private $description;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="type", type="string", length=255)
	 */
	private $type;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="available_variables", type="array", nullable=true)
	 */
	private $availableVariables = [ ];

	/**
	 * @var ArrayCollection
	 *
	 * @ORM\OneToMany(targetEntity="IIAB\TranslationBundle\Entity\LanguageTranslation", mappedBy="languageToken", cascade={"ALL"})
	 */
	private $translations;

	/**
	 * LanguageToken constructor.
	 */
	public function __construct() {

		$this->translations = new ArrayCollection();
	}


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Set token
	 *
	 * @param string $token
	 *
	 * @return LanguageToken
	 */
	public function setToken( $token ) {

		$this->token = $token;

		return $this;
	}

	/**
	 * Get token
	 *
	 * @return string
	 */
	public function getToken() {

		return $this->token;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return LanguageToken
	 */
	public function setDescription( $description = null ) {

		$this->description = $description;

		return $this;
	}

	/**
	 * Get Description
	 *
	 * @return string
	 */
	public function getDescription() {

		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getType() {

		return $this->type;
	}

	/**
	 * @param string $type
	 *
	 * @return LanguageToken
	 */
	public function setType( $type ) {

		$this->type = $type;

		return $this;
	}

	/**
	 * Set Available Variables
	 * @return array
	 */
	public function getAvailableVariables() {

		return $this->availableVariables;
	}

	/**
	 * Gets Available Variables
	 *
	 * @param array $availableVariables
	 *
	 * @return LanguageToken
	 */
	public function setAvailableVariables( $availableVariables ) {

		$this->availableVariables = $availableVariables;

		return $this;
	}

	/**
	 * Add translations
	 *
	 * @param \IIAB\TranslationBundle\Entity\LanguageTranslation $translation
	 *
	 * @return LanguageToken
	 */
	public function addTranslation( \IIAB\TranslationBundle\Entity\LanguageTranslation $translation ) {

		$this->translations[] = $translation;
		$translation->setLanguageToken( $this );

		return $this;
	}

	/**
	 * Remove translations
	 *
	 * @param \IIAB\TranslationBundle\Entity\LanguageTranslation $translation
	 */
	public function removeTranslation( \IIAB\TranslationBundle\Entity\LanguageTranslation $translation ) {

		$this->translations->removeElement( $translation );
		$translation->setLanguageToken( null );

	}

	/**
	 * Get translations
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getTranslations() {

		return $this->translations;
	}

	/**
	 * @param bool|false $full
	 *
	 * @return string
	 */
	public function getEnglishTranslation( $full = false ) {

		return $this->getSpecificTranslation( 1 , $full );
	}

	/**
	 * @param bool|false $full
	 *
	 * @return string
	 */
	public function getSpanishTranslation( $full = false ) {

		return $this->getSpecificTranslation( 2 , $full );
	}

	/**
	 * @return string
	 */
	public function getEnglishTranslationFull() {

		return $this->getSpecificTranslation( 1 , true );
	}

	/**
	 * @return string
	 */
	public function getSpanishTranslationFull() {

		return $this->getSpecificTranslation( 2 , true );
	}

	/**
	 * @param int        $id
	 * @param bool|false $full
	 *
	 * @return string
	 */
	public function getSpecificTranslation( $id = 0 , $full = false ) {

		if( $id == 0 ) {
			return '';
		}

		/** @var \IIAB\TranslationBundle\Entity\LanguageTranslation $translation */
		foreach( $this->getTranslations() as $translation ) {
			if( $translation != null ) {
				if( $translation->getLanguage()->getId() == $id ) { //Getting the English
					if( $full ) {
						return html_entity_decode( $translation->getTranslation() );
					} else {
						$strippedContent = strip_tags( $translation->getTranslation() , 'p,a' );
						$strippedContent = preg_split( "/ /" , $strippedContent );
						if( count( $strippedContent ) > 20 ) {
							array_splice( $strippedContent , 20 );
							$strippedContent = implode( ' ' , $strippedContent ) . '...';
						} else {
							$strippedContent = implode( ' ' , $strippedContent );
						}
					}

					return html_entity_decode( $strippedContent );
				}
			}
		}
	}

	/**
	 * The __toString method allows a class to decide how it will react when it is converted to a string.
	 *
	 * @return string
	 */
	function __toString() {

		return ( $this->getId() ) ? 'Edit ' . $this->getDescription() : 'New Translation';
	}


}
