<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ADM
 *
 * @ORM\Table(name="expiring")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\ExpirationRepository")
 */
class Expiration {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="studentID", type="integer")
	 */
	private $studentID;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="studentFirstName", type="string")
	 */
	private $firstName;


	/**
	 * @var string
	 *
	 * @ORM\Column(name="studentLastName", type="string")
	 */
	private $lastName;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="expiring", type="boolean")
	 */
	private $expiring;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="feederSchool", type="string")
	 */
	private $feederSchool;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollment")
	 * @ORM\JoinColumn(referencedColumnName="id" )
	 */
	protected $openEnrollment;

	/**
	 * @return int
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId( $id ) {

		$this->id = $id;
	}

	/**
	 * Get Student ID
	 *
	 * @return integer
	 */
	public function getStudentID() {

		return $this->studentID;
	}

	/**
	 * Set Student ID
	 *
	 * @param integer $studentID
	 *
	 * @return Expiration
	 */
	public function setStudentID( $studentID ) {

		$this->studentID = $studentID;
		return $this;
	}

	/**
	 * Get Expiring
	 *
	 * @return integer
	 */
	public function getExpiring() {

		return $this->expiring;
	}

	/**
	 * Set Expiring
	 *
	 * @param integer $expiring
	 *
	 * @return Expiration
	 */
	public function setExpiring( $expiring ) {

		$this->expiring = $expiring;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFirstName() {

		return $this->firstName;
	}

	/**
	 * @param string $firstName
	 */
	public function setFirstName( $firstName ) {

		$this->firstName = $firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName() {

		return $this->lastName;
	}

	/**
	 * @param string $lastName
	 */
	public function setLastName( $lastName ) {

		$this->lastName = $lastName;
	}

	/**
	 * @return string
	 */
	public function getFeederSchool() {

		return $this->feederSchool;
	}

	/**
	 * @param string $feederSchool
	 */
	public function setFeederSchool( $feederSchool ) {

		$this->feederSchool = $feederSchool;
	}

	/**
	 * @return string
	 */
	public function getCurrentGrade() {

		return $this->currentGrade;
	}

	/**
	 * @param string $currentGrade
	 */
	public function setCurrentGrade( $currentGrade ) {

		$this->currentGrade = $currentGrade;
	}

	/**
	 * @return string
	 */
	public function getNextGrade() {

		return $this->nextGrade;
	}

	/**
	 * @param string $nextGrade
	 */
	public function setNextGrade( $nextGrade ) {

		$this->nextGrade = $nextGrade;
	}

	/**
	 * @return mixed
	 */
	public function getOpenEnrollment() {

		return $this->openEnrollment;
	}

	/**
	 * @param mixed $openEnrollment
	 */
	public function setOpenEnrollment( $openEnrollment ) {

		$this->openEnrollment = $openEnrollment;
	}

}
