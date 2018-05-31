<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WaitList
 *
 * @ORM\Table(name="waitlist")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\WaitListRepository")
 */
class WaitList {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="waitListDateTime", type="datetime")
	 */
	private $waitListDateTime;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\Submission", inversedBy="waitList")
	 * @ORM\JoinColumn(referencedColumnName="submissionID", name="submission")
	 */
	protected $submission;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\ADM")
	 * @ORM\JoinColumn(referencedColumnName="id", name="choiceSchool")
	 */
	protected $choiceSchool;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollment")
	 * @ORM\JoinColumn(referencedColumnName="id", name="openEnrollment")
	 */
	protected $openEnrollment;

	function __construct() {

		$this->waitListDateTime = new \DateTime();
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
	 * Set waitListDateTime
	 *
	 * @param \DateTime $waitListDateTime
	 *
	 * @return WaitList
	 */
	public function setWaitListDateTime( $waitListDateTime ) {

		$this->waitListDateTime = $waitListDateTime;

		return $this;
	}

	/**
	 * Get waitListDateTime
	 *
	 * @return \DateTime
	 */
	public function getWaitListDateTime() {

		return $this->waitListDateTime;
	}

	/**
	 * Set submission
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\Submission $submission
	 *
	 * @return WaitList
	 */
	public function setSubmission( \IIAB\StudentTransferBundle\Entity\Submission $submission = null ) {

		$this->submission = $submission;

		return $this;
	}

	/**
	 * Get submission
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\Submission
	 */
	public function getSubmission() {

		return $this->submission;
	}

	/**
	 * Set choiceSchool
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\ADM $choiceSchool
	 *
	 * @return WaitList
	 */
	public function setChoiceSchool( \IIAB\StudentTransferBundle\Entity\ADM $choiceSchool = null ) {

		$this->choiceSchool = $choiceSchool;

		return $this;
	}

	/**
	 * Get choiceSchool
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\ADM
	 */
	public function getChoiceSchool() {

		return $this->choiceSchool;
	}

	/**
	 * Set openEnrollment
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollment $openEnrollment
	 *
	 * @return WaitList
	 */
	public function setOpenEnrollment( \IIAB\StudentTransferBundle\Entity\OpenEnrollment $openEnrollment = null ) {

		$this->openEnrollment = $openEnrollment;

		return $this;
	}

	/**
	 * Get openEnrollment
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
	 */
	public function getOpenEnrollment() {

		return $this->openEnrollment;
	}
}
