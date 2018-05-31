<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SlottingReport
 *
 * @ORM\Table( name="slottingreport" )
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\SlottingReportRepository")
 */
class SlottingReport {

	/**
	 * @ORM\ManyToOne( targetEntity="ADM" )
	 * @ORM\JoinColumn( name="schoolID" , referencedColumnName="id" )
	 */
	protected $schoolID;

	/**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
	 */
	protected $enrollmentPeriod;

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
	 * @ORM\Column(name="round", type="integer")
	 */
	private $round;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="status", type="string", length=255)
	 */
	private $status;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="currentSchool", type="string", length=255, nullable=true)
	 */
	private $currentSchool;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Get schoolID
	 *
	 * @return ADM
	 */
	public function getSchoolID() {

		return $this->schoolID;
	}

	/**
	 * Set schoolID
	 *
	 * @param ADM $schoolID
	 *
	 * @return SlottingReport
	 */
	public function setSchoolID( $schoolID ) {

		$this->schoolID = $schoolID;

		return $this;
	}

	/**
	 * Get round
	 *
	 * @return integer
	 */
	public function getRound() {

		return $this->round;
	}

	/**
	 * Set round
	 *
	 * @param integer $round
	 *
	 * @return SlottingReport
	 */
	public function setRound( $round ) {

		$this->round = $round;

		return $this;
	}

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function getStatus() {

		return $this->status;
	}

	/**
	 * Set status
	 *
	 * @param string $status
	 *
	 * @return SlottingReport
	 */
	public function setStatus( $status ) {

		$this->status = $status;

		return $this;
	}

	/**
	 * @return OpenEnrollment
	 */
	public function getEnrollmentPeriod() {

		return $this->enrollmentPeriod;
	}

	/**
	 * @param OpenEnrollment $enrollmentPeriod
	 */
	public function setEnrollmentPeriod( $enrollmentPeriod ) {

		$this->enrollmentPeriod = $enrollmentPeriod;
	}

	/**
	 * @param string $currentSchool
	 */
	public function setCurrentSchool( $currentSchool ) {

		$this->currentSchool = $currentSchool;
	}

	/**
	 * @return string
	 */
	public function getCurrentSchool() {

		return $this->currentSchool;
	}
}
