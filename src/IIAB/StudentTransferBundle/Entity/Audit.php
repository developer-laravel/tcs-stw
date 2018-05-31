<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Audit
 *
 * @ORM\Table(name="audit")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\AuditRepository")
 */
class Audit {

	/**
	 * @ORM\ManyToOne( targetEntity="AuditCode" )
	 * @ORM\JoinColumn( name="auditCodeID", referencedColumnName="auditCodeID", nullable=false )
	 */
	protected $auditCodeID;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="auditID", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="submissionID", type="integer", nullable=true)
	 */
	private $submissionID = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="userID", type="integer", nullable=true)
	 */
	private $userID;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="ipaddress", type="string", length=255, nullable=true)
	 */
	private $ipaddress;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="timestamp", type="datetime" )
	 */
	private $timestamp;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="studentID", type="string", length=255, nullable=true)
	 */
	private $studentID = 0;

	/**
	 * @return string
	 */
	public function getStudentID() {

		return $this->studentID;
	}

	/**
	 * @param string $studentID
	 */
	public function setStudentID( $studentID ) {

		$this->studentID = $studentID;
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
	 * Get auditCodeID
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\AuditCode
	 */
	public function getAuditCodeID() {

		return $this->auditCodeID;
	}

	/**
	 * Set auditCodeID
	 *
	 * @param $auditCodeID
	 *
	 * @return Audit
	 */
	public function setAuditCodeID( $auditCodeID ) {

		$this->auditCodeID = $auditCodeID;

		return $this;
	}

	/**
	 * Get submissionID
	 *
	 * @return integer
	 */
	public function getSubmissionID() {

		return $this->submissionID;
	}

	/**
	 * Set submissionID
	 *
	 * @param integer $submissionID
	 *
	 * @return Audit
	 */
	public function setSubmissionID( $submissionID = 0 ) {

		$this->submissionID = $submissionID;

		return $this;
	}

	/**
	 * Get userID
	 *
	 * @return integer
	 */
	public function getUserID() {

		return $this->userID;
	}

	/**
	 * Set userID
	 *
	 * @param integer $userID
	 *
	 * @return Audit
	 */
	public function setUserID( $userID ) {

		$this->userID = $userID;

		return $this;
	}

	/**
	 * Get ipaddress
	 *
	 * @return string
	 */
	public function getIpaddress() {

		return $this->ipaddress;
	}

	/**
	 * Set ipaddress
	 *
	 * @param string $ipaddress
	 *
	 * @return Audit
	 */
	public function setIpaddress( $ipaddress ) {

		$this->ipaddress = $ipaddress;
	}

	/**
	 * Get timestamp
	 *
	 * @return \DateTime
	 */
	public function getTimestamp() {

		return $this->timestamp;
	}

	/**
	 * Set timestamp
	 *
	 * @param \DateTime $timestamp
	 *
	 * @return Audit
	 */
	public function setTimestamp( $timestamp ) {

		$this->timestamp = $timestamp;

		return $this;
	}

	public function getUsername() {
		return 'test';
	}
}
