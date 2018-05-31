<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * $this
 *
 * @ORM\Table(name="current_enrollment_settings")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\CurrentEnrollmentSettingsRepository")
 *
 */
class CurrentEnrollmentSettings {

	/**
	 * @var integer
	 * @ORM\Id
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\ManyToOne( targetEntity="IIAB\StudentTransferBundle\Entity\SchoolGroup")
	 * @ORM\JoinColumn( name="groupId" , referencedColumnName="id")
	 */
	private $groupId;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", options={"default"=0})
	 */
	private $maxCapacity = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="black", type="integer", options={"default"=0})
	 */
	private $black = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="white", type="integer", options={"default"=0})
	 */
	private $white = 0;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="other", type="integer", options={"default"=0})
	 */
	private $other = 0;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime")
	 */
	private $addedDateTime;

	/**
	 * @var string
	 *
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollment")
	 * @ORM\JoinColumn(name="enrollmentPeriod" , referencedColumnName="id")
	 */
	private $enrollmentPeriod;


	function __construct() {
		$this->addedDateTime = new \DateTime();
	}

	/**
	 * Get getId
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * setId
	 *
	 * @param integer $id
	 *
	 * @return $this
	 */
	public function setId( $id ) {

		$this->id = $id;

		return $this;
	}

	/**
	 * Get black
	 *
	 * @return integer
	 */
	public function getBlack() {

		return $this->black;
	}

	/**
	 * Set black
	 *
	 * @param integer $black
	 *
	 * @return $this
	 */
	public function setBlack( $black ) {

		$this->black = $black;

		return $this;
	}

	/**
	 * Get White
	 *
	 * @return integer
	 */
	public function getWhite() {

		return $this->white;
	}

	/**
	 * Set White
	 *
	 * @param integer $white
	 *
	 * @return $this
	 */
	public function setWhite( $white ) {

		$this->white = $white;

		return $this;
	}

	/**
	 * Get other
	 *
	 * @return integer
	 */
	public function getOther() {

		return $this->other;
	}

	/**
	 * Set other
	 *
	 * @param integer $other
	 *
	 * @return $this
	 */
	public function setOther( $other ) {

		$this->other = $other;

		return $this;
	}

	/**
	 * Get the sum of the individual races.
	 *
	 * @return int
	 */
	public function getSum() {
		return $this->other + $this->white + $this->black;
	}

	/**
	 * Get enrollmentPeriod
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
	 */
	public function getEnrollmentPeriod() {

		return $this->enrollmentPeriod;
	}

	/**
	 * Set enrollmentPeriod
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod
	 *
	 * @return CurrentEnrollmentSettings
	 */
	public function setEnrollmentPeriod( \IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod = null ) {

		$this->enrollmentPeriod = $enrollmentPeriod;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getMaxCapacity() {

		return $this->maxCapacity;
	}

	/**
	 * @param int $maxCapacity
	 */
	public function setMaxCapacity( $maxCapacity ) {

		$this->maxCapacity = $maxCapacity;
	}

	/**
	 * Get groupId
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\SchoolGroup
	 */
	public function getGroupId() {

		return $this->groupId;
	}

	/**
	 * Set groupId
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\SchoolGroup $groupId
	 *
	 * @return CurrentEnrollmentSettings
	 */
	public function setGroupId( \IIAB\StudentTransferBundle\Entity\SchoolGroup $groupId = null ) {

		$this->groupId = $groupId;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getAddedDateTime() {
		return $this->addedDateTime;
	}

	/**
	 * @param \DateTime $addedDateTime
	 */
	public function setAddedDateTime( $addedDateTime ) {
		$this->addedDateTime = $addedDateTime;
	}
}
