<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LotteryLog
 *
 * @ORM\Table(name="lottery_log")
 * @ORM\Entity
 */
class LotteryLog {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;


    /**
     * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
     * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
     */
    protected $enrollmentPeriod;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime" , nullable=true)
     */
    private $timestamp;

    /**
     * @ORM\ManyToOne( targetEntity="LotteryStatus" )
     * @ORM\JoinColumn( name="lotteryStatus", referencedColumnName="id" )
     */
    protected $lotteryStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="mailDate", type="datetime" , nullable=true)
     */
    private $mailDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="time", nullable=true)
     */
    private $offlineCloseTime;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return LotteryLog
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set mailDate
     *
     * @param \DateTime $mailDate
     * @return LotteryLog
     */
    public function setMailDate($mailDate)
    {
        $this->mailDate = $mailDate;

        return $this;
    }

    /**
     * Get mailDate
     *
     * @return \DateTime
     */
    public function getMailDate()
    {
        return $this->mailDate;
    }

    /**
     * Set offlineCloseTime
     *
     * @param \DateTime $offlineCloseTime
     * @return LotteryLog
     */
    public function setOfflineCloseTime($offlineCloseTime)
    {
        $this->offlineCloseTime = $offlineCloseTime;

        return $this;
    }

    /**
     * Get offlineCloseTime
     *
     * @return \DateTime
     */
    public function getOfflineCloseTime()
    {
        return $this->offlineCloseTime;
    }

    /**
     * Set enrollmentPeriod
     *
     * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod
     * @return LotteryLog
     */
    public function setEnrollmentPeriod(\IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod = null)
    {
        $this->enrollmentPeriod = $enrollmentPeriod;

        return $this;
    }

    /**
     * Get enrollmentPeriod
     *
     * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
     */
    public function getEnrollmentPeriod()
    {
        return $this->enrollmentPeriod;
    }

    /**
     * Set lotteryStatus
     *
     * @param \IIAB\StudentTransferBundle\Entity\LotteryStatus $lotteryStatus
     * @return LotteryLog
     */
    public function setLotteryStatus(\IIAB\StudentTransferBundle\Entity\LotteryStatus $lotteryStatus = null)
    {
        $this->lotteryStatus = $lotteryStatus;

        return $this;
    }

    /**
     * Get lotteryStatus
     *
     * @return \IIAB\StudentTransferBundle\Entity\LotteryStatus
     */
    public function getLotteryStatus()
    {
        return $this->lotteryStatus;
    }
}
