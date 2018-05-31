<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StudentGrade
 *
 * @ORM\Table(name="student_grade")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\StudentGradeRepository")
 */
class StudentGrade
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
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\Student", inversedBy="grades")
     * @ORM\JoinColumn(name="student", referencedColumnName="id")
     */
    protected $student;

    /**
     * @var integer
     *
     * @ORM\Column(name="academicYear", type="integer", nullable=true)
     */
    private $academicYear;

    /**
     * @var string
     *
     * @ORM\Column(name="academicTerm", type="string", length=255, nullable=true)
     */
    private $academicTerm;

    /**
     * @var string
     *
     * @ORM\Column(name="courseTypeID", type="string", length=255, nullable=true)
     */
    private $courseTypeID;

    /**
     * @var string
     *
     * @ORM\Column(name="courseType", type="string", length=255, nullable=true)
     */
    private $courseType;

    /**
     * @var string
     *
     * @ORM\Column(name="courseName", type="string", length=255, nullable=true)
     */
    private $courseName;

    /**
     * @var string
     *
     * @ORM\Column(name="sectionNumber", type="string", length=255, nullable=true)
     */
    private $sectionNumber;

    /**
     * @var float
     *
     * @ORM\Column(name="numericGrade", type="float", scale=4, nullable=true)
     */
    private $numericGrade;

    /**
     * @var string
     *
     * @ORM\Column(name="alphaGrade", type="string", nullable=true)
     */
    private $alphaGrade;

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
     * Set academicYear
     *
     * @param integer $academicYear
     *
     * @return StudentGrade
     */
    public function setAcademicYear($academicYear)
    {
        $this->academicYear = $academicYear;

        return $this;
    }

    /**
     * Get academicYear
     *
     * @return integer
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    /**
     * Set academicTerm
     *
     * @param string $academicTerm
     *
     * @return StudentGrade
     */
    public function setAcademicTerm($academicTerm)
    {
        $this->academicTerm = $academicTerm;

        return $this;
    }

    /**
     * Get academicTerm
     *
     * @return string
     */
    public function getAcademicTerm()
    {
        return $this->academicTerm;
    }

    /**
     * Set courseTypeID
     *
     * @param string $courseTypeID
     *
     * @return StudentGrade
     */
    public function setCourseTypeID($courseTypeID)
    {
        $this->courseTypeID = $courseTypeID;

        return $this;
    }

    /**
     * Get courseTypeID
     *
     * @return string
     */
    public function getCourseTypeID()
    {
        return $this->courseTypeID;
    }

    /**
     * Set courseType
     *
     * @param string $courseType
     *
     * @return StudentGrade
     */
    public function setCourseType($courseType)
    {
        $this->courseType = $courseType;

        return $this;
    }

    /**
     * Get courseType
     *
     * @return string
     */
    public function getCourseType()
    {
        return $this->courseType;
    }

    /**
     * Set courseName
     *
     * @param string $courseName
     *
     * @return StudentGrade
     */
    public function setCourseName($courseName)
    {
        $this->courseName = $courseName;

        return $this;
    }

    /**
     * Get courseName
     *
     * @return string
     */
    public function getCourseName()
    {
        return $this->courseName;
    }

    /**
     * Set sectionNumber
     *
     * @param string $sectionNumber
     *
     * @return StudentGrade
     */
    public function setSectionNumber($sectionNumber)
    {
        $this->sectionNumber = $sectionNumber;

        return $this;
    }

    /**
     * Get sectionNumber
     *
     * @return string
     */
    public function getSectionNumber()
    {
        return $this->sectionNumber;
    }

    /**
     * Set numericGrade
     *
     * @param float $numericGrade
     *
     * @return StudentGrade
     */
    public function setNumericGrade($numericGrade)
    {
        $this->numericGrade = $numericGrade;

        return $this;
    }

    /**
     * Get numericGrade
     *
     * @return float
     */
    public function getNumericGrade()
    {
        return $this->numericGrade;
    }

    /**
     * Set alphaGrade
     *
     * @param float $alphaGrade
     *
     * @return StudentGrade
     */
    public function setAlphaGrade($alphaGrade)
    {
        $this->alphaGrade = $alphaGrade;

        return $this;
    }

    /**
     * Get alphaGrade
     *
     * @return float
     */
    public function getAlphaGrade()
    {
        return $this->alphaGrade;
    }

    /**
     * Set student
     *
     * @param \LeanFrog\SharedDataBundle\Entity\Student $student
     *
     * @return StudentGrade
     */
    public function setStudent(\LeanFrog\SharedDataBundle\Entity\Student $student = null)
    {
        $this->student = $student;

        return $this;
    }

    /**
     * Get student
     *
     * @return \LeanFrog\SharedDataBundle\Entity\Student
     */
    public function getStudent()
    {
        return $this->student;
    }
}
