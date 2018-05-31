<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * NewSchool
 *
 * @ORM\Table(name="newschool")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\NewSchoolRepository")
 */
class NewSchool
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
     * @var string
     *
     * @ORM\Column(name="currentSchool", type="string", length=255)
     */
    private $currentSchool;

    /**
     * @var integer
     *
     * @ORM\Column(name="currentSchoolID", type="integer")
     */
    private $currentSchoolID;

    /**
     * @var string
     *
     * @ORM\Column(name="currentGrade", type="string", length=255)
     */
    private $currentGrade;

    /**
     * @var string
     *
     * @ORM\Column(name="newSchool", type="string", length=255)
     */
    private $newSchool;

    /**
     * @var integer
     *
     * @ORM\Column(name="newSchoolID", type="integer")
     */
    private $newSchoolID;

    /**
     * @var string
     *
     * @ORM\Column(name="newGrade", type="string", length=255)
     */
    private $newGrade;

	/**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
	 */
	protected $enrollmentPeriod;


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
     * Set currentSchool
     *
     * @param string $currentSchool
     * @return NewSchool
     */
    public function setCurrentSchool($currentSchool)
    {
        $this->currentSchool = $currentSchool;

        return $this;
    }

    /**
     * Get currentSchool
     *
     * @return string
     */
    public function getCurrentSchool()
    {
        return $this->currentSchool;
    }

    /**
     * Set currentSchoolID
     *
     * @param integer $currentSchoolID
     * @return NewSchool
     */
    public function setCurrentSchoolID($currentSchoolID)
    {
        $this->currentSchoolID = $currentSchoolID;

        return $this;
    }

    /**
     * Get currentSchoolID
     *
     * @return integer
     */
    public function getCurrentSchoolID()
    {
        return $this->currentSchoolID;
    }

    /**
     * Set currentGrade
     *
     * @param string $grade
     * @return NewSchool
     */
    public function setCurrentGrade($grade)
    {
        $this->currentGrade = $grade;

        return $this;
    }

    /**
     * Get currentGrade
     *
     * @return string
     */
    public function getCurrentGrade()
    {
        return $this->currentGrade;
    }

    /**
     * Set newSchool
     *
     * @param string $newSchool
     * @return NewSchool
     */
    public function setNewSchool($newSchool)
    {
        $this->newSchool = $newSchool;

        return $this;
    }

    /**
     * Get newSchool
     *
     * @return string
     */
    public function getNewSchool()
    {
        return $this->newSchool;
    }

    /**
     * Set newSchoolID
     *
     * @param integer $newSchoolID
     * @return NewSchool
     */
    public function setNewSchoolID($newSchoolID)
    {
        $this->newSchoolID = $newSchoolID;

        return $this;
    }

    /**
     * Get newSchoolID
     *
     * @return integer
     */
    public function getNewSchoolID()
    {
        return $this->newSchoolID;
    }

    /**
     * Set newGrade
     *
     * @param string $newGrade
     * @return NewSchool
     */
    public function setNewGrade($newGrade)
    {
        $this->newGrade = $newGrade;

        return $this;
    }

    /**
     * Get newGrade
     *
     * @return string
     */
    public function getNewGrade()
    {
        return $this->newGrade;
    }

	/**
	 * Set enrollmentPeriod
	 *
	 * @param OpenEnrollment $enrollmentPeriod
	 * @return ADM
	 */
	public function setEnrollmentPeriod($enrollmentPeriod)
	{
		$this->enrollmentPeriod = $enrollmentPeriod;

		return $this;
	}

	/**
	 * Get enrollmentPeriod
	 *
	 * @return OpenEnrollment
	 */
	public function getEnrollmentPeriod()
	{
		return $this->enrollmentPeriod;
	}
}
