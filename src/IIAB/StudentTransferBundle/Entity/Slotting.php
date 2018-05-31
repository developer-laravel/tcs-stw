<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Slotting
 *
 * @ORM\Table(name="slotting")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\SlottingRepository")
 */
class Slotting
{
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
     * @ORM\Column(name="schoolID", type="integer")
     */
    private $schoolID;

	/**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
	 */
    protected $enrollmentPeriod;

    /**
     * @var string
     *
     * @ORM\Column(name="grade", type="string", length=255)
     */
    private $grade;

    /**
     * @var integer
     *
     * @ORM\Column(name="availableSlots", type="integer")
     */
    private $availableSlots;

    /**
     * @var integer
     *
     * @ORM\Column(name="priority", type="integer")
     */
    private $priority;


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
     * Set schoolID
     *
     * @param integer $schoolID
     *
     * @return Slotting
     */
    public function setSchoolID( $schoolID)
    {
        $this->schoolID = $schoolID;

        return $this;
    }

    /**
     * Get schoolID
     *
     * @return integer
     */
    public function getSchoolID()
    {
        return $this->schoolID;
    }

    /**
     * Set enrollmentPeriod
     *
     * @param integer $enrollmentPeriod
     * @return Slotting
     */
    public function setEnrollmentPeriod($enrollmentPeriod)
    {
        $this->enrollmentPeriod = $enrollmentPeriod;

        return $this;
    }

    /**
     * Get enrollmentPeriod
     *
     * @return integer
     */
    public function getEnrollmentPeriod()
    {
        return $this->enrollmentPeriod;
    }

    /**
     * Set grade
     *
     * @param string $grade
     * @return Slotting
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;

        return $this;
    }

    /**
     * Get grade
     *
     * @return string
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * Set availableSlots
     *
     * @param integer $availableSlots
     * @return Slotting
     */
    public function setAvailableSlots($availableSlots)
    {
        $this->availableSlots = $availableSlots;

        return $this;
    }

    /**
     * Get availableSlots
     *
     * @return integer
     */
    public function getAvailableSlots()
    {
        return $this->availableSlots;
    }

    /**
     * Set priority
     *
     * @param integer $priority
     * @return Slotting
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
