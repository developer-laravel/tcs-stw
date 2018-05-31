<?php

namespace IIAB\TranslationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Language
 *
 * @ORM\Table(name="language")
 * @ORM\Entity()
 */
class Language {

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
	 * @ORM\Column(name="locale", type="string", length=255)
	 */
	private $locale;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	private $name;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Set locale
	 *
	 * @param string $locale
	 *
	 * @return Language
	 */
	public function setLocale( $locale ) {

		$this->locale = $locale;

		return $this;
	}

	/**
	 * Get locale
	 *
	 * @return string
	 */
	public function getLocale() {

		return $this->locale;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return Language
	 */
	public function setName( $name ) {

		$this->name = $name;

		return $this;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function getName() {

		return $this->name;
	}

	/**
	 * The __toString method allows a class to decide how it will react when it is converted to a string.
	 *
	 * @return string
	 */
	function __toString() {
		return $this->getName();
	}


}
