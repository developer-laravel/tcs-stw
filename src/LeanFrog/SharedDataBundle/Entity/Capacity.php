<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Capacity
 *
 * @ORM\Table(name="capacity")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\CapacityRepository")
 */
class Capacity
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
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\AcademicYear", inversedBy="academicYears")
     * @ORM\JoinColumn(name="academicYear", referencedColumnName="id")
     */
    protected $academicYear;

    /**
     * @var int
     *
     * @ORM\Column(name="max", type="integer")
     */
    private $max;


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
     * Set max
     *
     * @param integer $max
     *
     * @return Capacity
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return integer
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set programSchool
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $programSchool
     *
     * @return Capacity
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
     * @return Capacity
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
}
