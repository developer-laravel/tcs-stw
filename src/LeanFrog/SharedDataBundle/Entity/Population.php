<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Population
 *
 * @ORM\Table(name="population")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\PopulationRepository")
 */
class Population
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\ProgramSchool", inversedBy="programSchools")
     * @ORM\JoinColumn(name="programSchool", referencedColumnName="id")
     */
    protected $programSchool;

    /**
     * @var string
     *
     * @ORM\Column(name="trackingColumn", type="string", length=255)
     */
    private $trackingColumn;

    /**
     * @var string
     *
     * @ORM\Column(name="trackingValue", type="string", length=255)
     */
    private $trackingValue;

    /**
     * @var int
     *
     * @ORM\Column(name="count", type="integer")
     */
    private $count;

    /**
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\AcademicYear", inversedBy="populations")
     * @ORM\JoinColumn(name="academicYear", referencedColumnName="id")
     */
    protected $academicYear;

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
     * Set trackingColumn
     *
     * @param string $trackingColumn
     *
     * @return Population
     */
    public function setTrackingColumn($trackingColumn)
    {
        $this->trackingColumn = $trackingColumn;

        return $this;
    }

    /**
     * Get trackingColumn
     *
     * @return string
     */
    public function getTrackingColumn()
    {
        return $this->trackingColumn;
    }

    /**
     * Set trackingValue
     *
     * @param string $trackingValue
     *
     * @return Population
     */
    public function setTrackingValue($trackingValue)
    {
        $this->trackingValue = $trackingValue;

        return $this;
    }

    /**
     * Get trackingValue
     *
     * @return string
     */
    public function getTrackingValue()
    {
        return $this->trackingValue;
    }

    /**
     * Set count
     *
     * @param integer $count
     *
     * @return Population
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="update_type", type="string", length=255)
     */
    private $updateType;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updateDateTime", type="datetime")
     */
    private $updateDateTime;

    /**
     * Set programSchool
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $programSchool
     *
     * @return Population
     */
    public function setProgramSchool(\LeanFrog\SharedDataBundle\Entity\ProgramSchool $programSchool = null)
    {
        $this->programSchool = $programSchool;

        return $this;
    }

    /**
     * Get programSchool
     *
     * @return \LeanFrog\SharedDataBundle\Entity\ProgramSchool
     */
    public function getProgramSchool()
    {
        return $this->programSchool;
    }

    /**
     * Set academicYear
     *
     * @param \LeanFrog\SharedDataBundle\Entity\AcademicYear $academicYear
     *
     * @return Population
     */
    public function setAcademicYear(\LeanFrog\SharedDataBundle\Entity\AcademicYear $academicYear = null)
    {
        $this->academicYear = $academicYear;

        return $this;
    }

    /**
     * Get academicYear
     *
     * @return \LeanFrog\SharedDataBundle\Entity\AcademicYear
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    /**
     * Set updateDateTime
     *
     * @param \DateTime $updateDateTime
     *
     * @return Population
     */
    public function setUpdateDateTime($updateDateTime)
    {
        $this->updateDateTime = $updateDateTime;

        return $this;
    }

    /**
     * Get updateDateTime
     *
     * @return \DateTime
     */
    public function getUpdateDateTime()
    {
        return $this->updateDateTime;
    }

    /**
     * Set updateType
     *
     * @param string $updateType
     *
     * @return Population
     */
    public function setUpdateType($updateType)
    {
        $this->updateType = $updateType;

        return $this;
    }

    /**
     * Get updateType
     *
     * @return string
     */
    public function getUpdateType()
    {
        return $this->updateType;
    }
}
