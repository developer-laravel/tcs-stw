<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Lottery
 *
 * @ORM\Table(name="lottery")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\LotteryRepository")
 */
class Lottery {

	/**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
	 */
	protected $enrollmentPeriod;

	/**
	 * @ORM\ManyToOne( targetEntity="LotteryStatus" )
	 * @ORM\JoinColumn( name="lotteryStatus", referencedColumnName="id" )
	 */
	protected $lotteryStatus;

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
	 * @ORM\Column(name="FirstRoundDate", type="datetime" , nullable=true)
	 */
	private $firstRoundDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="SecondRoundDate", type="datetime" , nullable=true)
	 */
	private $secondRoundDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="MailFirstRoundDate", type="datetime" , nullable=true)
	 */
	private $mailFirstRoundDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="MailSecondRoundDate", type="datetime" , nullable=true)
	 */
	private $mailSecondRoundDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="LastFirstRoundProcess", type="datetime" , nullable=true)
	 */
	private $lastFirstRoundProcess;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="LastSecondRoundProcess", type="datetime" , nullable=true)
	 */
	private $lastSecondRoundProcess;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="LastLateLotteryProcess", type="datetime" , nullable=true)
	 */
	private $lastLateLotteryProcess;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="time", nullable=true)
	 */
	private $offlineCloseTime;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $registrationNewDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $registrationCurrentDate;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $firstRoundAcceptanceWindow;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $secondRoundAcceptanceWindow;

	/**
	 * Get enrollmentPeriod
	 *
	 * @return OpenEnrollment
	 */
	public function getEnrollmentPeriod() {

		return $this->enrollmentPeriod;
	}

	/**
	 * Set enrollmentPeriod
	 *
	 * @param OpenEnrollment $enrollmentPeriod
	 *
	 * @return Lottery
	 */
	public function setEnrollmentPeriod( OpenEnrollment $enrollmentPeriod ) {

		$this->enrollmentPeriod = $enrollmentPeriod;

		return $this;
	}

	/**
	 * Get firstRoundDate
	 *
	 * @return \DateTime
	 */
	public function getFirstRoundDate() {

		return $this->firstRoundDate;
	}

	/**
	 * Set firstRoundDate
	 *
	 * @param \DateTime $firstRoundDate
	 *
	 * @return Lottery
	 */
	public function setFirstRoundDate( $firstRoundDate ) {

		$this->firstRoundDate = $firstRoundDate;

		return $this;
	}

	/**
	 * Get secondRoundDate
	 *
	 * @return \DateTime
	 */
	public function getSecondRoundDate() {

		return $this->secondRoundDate;
	}

	/**
	 * Set secondRoundDate
	 *
	 * @param \DateTime $secondRoundDate
	 *
	 * @return Lottery
	 */
	public function setSecondRoundDate( $secondRoundDate ) {

		$this->secondRoundDate = $secondRoundDate;

		return $this;
	}

	/**
	 * Get lotteryStatus
	 *
	 * @return LotteryStatus
	 */
	public function getLotteryStatus() {

		return $this->lotteryStatus;
	}

	/**
	 * Set lotteryStatus
	 *
	 * @param LotteryStatus $lotteryStatus
	 *
	 * @return Lottery
	 */
	public function setLotteryStatus( LotteryStatus $lotteryStatus ) {

		$this->lotteryStatus = $lotteryStatus;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getMailFirstRoundDate() {

		return $this->mailFirstRoundDate;
	}

	/**
	 * @param \DateTime $mailFirstRoundDate
	 */
	public function setMailFirstRoundDate( $mailFirstRoundDate ) {

		$this->mailFirstRoundDate = $mailFirstRoundDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getMailSecondRoundDate() {

		return $this->mailSecondRoundDate;
	}

	/**
	 * @param \DateTime $mailSecondRoundDate
	 */
	public function setMailSecondRoundDate( $mailSecondRoundDate ) {

		$this->mailSecondRoundDate = $mailSecondRoundDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastFirstRoundProcess() {

		return $this->lastFirstRoundProcess;
	}

	/**
	 * @param \DateTime $lastFirstRoundProcess
	 */
	public function setLastFirstRoundProcess( $lastFirstRoundProcess ) {

		$this->lastFirstRoundProcess = $lastFirstRoundProcess;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastSecondRoundProcess() {

		return $this->lastSecondRoundProcess;
	}

	/**
	 * @param \DateTime $lastSecondRoundProcess
	 */
	public function setLastSecondRoundProcess( $lastSecondRoundProcess ) {

		$this->lastSecondRoundProcess = $lastSecondRoundProcess;
	}

	/**
	 * @return string
	 */
	public function __toString() {

		return $this->getId() ? '' . $this->getId() : 'Create';
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
	 * @return \DateTime
	 */
	public function getOfflineCloseTime() {

		return $this->offlineCloseTime;
	}

	/**
	 * @param \DateTime $offlineCloseTime
	 */
	public function setOfflineCloseTime( $offlineCloseTime ) {

		$this->offlineCloseTime = $offlineCloseTime;
	}

	/**
	 * @return \DateTime
	 */
	public function getRegistrationNewDate() {

		return $this->registrationNewDate;
	}

	/**
	 * @param \DateTime $registrationNewDate
	 */
	public function setRegistrationNewDate( $registrationNewDate ) {

		$this->registrationNewDate = $registrationNewDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getRegistrationCurrentDate() {

		return $this->registrationCurrentDate;
	}

	/**
	 * @param \DateTime $registrationCurrentDate
	 */
	public function setRegistrationCurrentDate( $registrationCurrentDate ) {

		$this->registrationCurrentDate = $registrationCurrentDate;
	}

    /**
     * Set lastLateLotteryProcess
     *
     * @param \DateTime $lastLateLotteryProcess
     * @return Lottery
     */
    public function setLastLateLotteryProcess($lastLateLotteryProcess)
    {
        $this->lastLateLotteryProcess = $lastLateLotteryProcess;

        return $this;
    }

    /**
     * Get lastLateLotteryProcess
     *
     * @return \DateTime
     */
    public function getLastLateLotteryProcess()
    {
        return $this->lastLateLotteryProcess;
    }

    /**
	 * Get firstRoundAcceptanceWindow
	 *
	 * @return integer
	 */
	public function getFirstRoundAcceptanceWindow() {

		return $this->firstRoundAcceptanceWindow;
	}

	/**
	 * Set firstRoundAcceptanceWindow
	 *
	 * @param integer $firstRoundAcceptanceWindow
	 *
	 * @return Lottery
	 */
	public function setFirstRoundAcceptanceWindow( $firstRoundAcceptanceWindow ) {

		$this->firstRoundAcceptanceWindow = $firstRoundAcceptanceWindow;

		return $this;
	}

	/**
	 * Get secondRoundAcceptanceWindow
	 *
	 * @return integer
	 */
	public function getSecondRoundAcceptanceWindow() {

		return $this->secondRoundAcceptanceWindow;
	}

	/**
	 * Set secondRoundAcceptanceWindow
	 *
	 * @param integer $secondRoundAcceptanceWindow
	 *
	 * @return Lottery
	 */
	public function setSecondRoundAcceptanceWindow( $secondRoundAcceptanceWindow ) {

		$this->secondRoundAcceptanceWindow = $secondRoundAcceptanceWindow;

		return $this;
	}

}
