<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SchoolGroup
 *
 * @ORM\Table(name="schoolgroup")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\SchoolGroupRepository")
 */
class SchoolGroup {

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
	 * @ORM\Column(name="name", type="string", length=255)
	 */
	private $name;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(type="boolean", options={"default":0})
	 */
	private $spedAccess = false;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
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
	 * Set name
	 *
	 * @param string $name
	 *
	 * @return SchoolGroup
	 */
	public function setName( $name ) {

		$this->name = $name;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isSpedAccess() {

		return $this->spedAccess;
	}

	/**
	 * @param boolean $spedAccess
	 *
	 * @return SchoolGroup
	 */
	public function setSpedAccess( $spedAccess ) {

		$this->spedAccess = $spedAccess;

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {

		return $this->getName();
	}
}
