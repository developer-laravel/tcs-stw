<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuditCode
 *
 * @ORM\Table(name="auditcode")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\AuditCodeRepository")
 */
class AuditCode {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="auditCodeID", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="description", type="text")
	 */
	private $description;

	public function __construct() {

		$this->audits = new ArrayCollection();
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
	 * Get description
	 *
	 * @return string
	 */
	public function getDescription() {

		return $this->description;
	}

	/**
	 * Set description
	 *
	 * @param string $description
	 *
	 * @return AuditCode
	 */
	public function setDescription( $description ) {

		$this->description = $description;

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {
		return 'AuditCode : ' . $this->getId() . ' - ' . ucwords( $this->getDescription() );
	}
}
