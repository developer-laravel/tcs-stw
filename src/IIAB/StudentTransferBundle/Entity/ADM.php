<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ADM
 *
 * @ORM\Table(name="adm")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\ADMRepository")
 */
class ADM
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
    private $schoolID = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="schoolName", type="string", length=255)
     */
    private $schoolName;

    /**
     * @var integer
     *
     * @ORM\Column(name="black", type="integer", options={"default"=0}, nullable=true)
     */
    private $black = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="white", type="integer", options={"default"=0}, nullable=true)
     */
    private $white = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="other", type="integer", options={"default"=0}, nullable=true)
     */
    private $other = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total", type="integer", options={"default"=0}, nullable=true)
     */
    private $total = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="blackPercent", type="decimal", precision=7, scale=5, nullable=true, options={"default"=0})
     */
    private $blackPercent = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="whitePercent", type="decimal", precision=7, scale=5, nullable=true, options={"default"=0})
     */
    private $whitePercent = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="otherPercent", type="decimal", precision=7, scale=5, nullable=true, options={"default"=0})
     */
    private $otherPercent = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="hsvCityName", type="string", length=255, nullable=true)
     */
    private $hsvCityName;

    /**
     * @var string
     *
     * @ORM\Column(name="grade", type="string", length=255, nullable=true)
     */
    private $grade;

    /**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
     */
    protected $enrollmentPeriod;

    /**
     * @var integer
     *
     * @ORM\ManyToOne( targetEntity="IIAB\StudentTransferBundle\Entity\SchoolGroup")
     * @ORM\JoinColumn( name="groupID", referencedColumnName="id")
     */
    private $groupID;

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
     * @return ADM
     */
    public function setSchoolID($schoolID)
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
     * Set schoolName
     *
     * @param string $schoolName
     * @return ADM
     */
    public function setSchoolName($schoolName)
    {
        $this->schoolName = $schoolName;

        return $this;
    }

    /**
     * Get schoolName
     *
     * @return string
     */
    public function getSchoolName()
    {
        return $this->schoolName;
    }

    /**
     * Set black
     *
     * @param integer $black
     * @return ADM
     */
    public function setBlack($black)
    {
        $this->black = $black;

        return $this;
    }

    /**
     * Set White
     *
     * @param integer $white
     * @return ADM
     */
    public function setWhite($white)
    {
        $this->white = $white;

        return $this;
    }

    /**
     * Get White
     *
     * @return integer
     */
    public function getWhite()
    {
        return $this->white;
    }

    /**
     * Get black
     *
     * @return integer
     */
    public function getBlack()
    {
        return $this->black;
    }

    /**
     * Set other
     *
     * @param integer $other
     * @return ADM
     */
    public function setOther($other)
    {
        $this->other = $other;

        return $this;
    }

    /**
     * Get other
     *
     * @return integer
     */
    public function getOther()
    {
        return $this->other;
    }

    /**
     * Set total
     *
     * @param integer $total
     * @return ADM
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get total
     *
     * @return integer
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Set blackPercent
     *
     * @param integer $blackPercent
     * @return ADM
     */
    public function setBlackPercent($blackPercent)
    {
        $this->blackPercent = $blackPercent;

        return $this;
    }

    /**
     * Set whitePercent
     *
     * @param integer $whitePercent
     * @return ADM
     */
    public function setWhitePercent($whitePercent)
    {
        $this->whitePercent = $whitePercent;

        return $this;
    }

    /**
     * Get blackPercent
     *
     * @return integer
     */
    public function getBlackPercent()
    {
        return $this->blackPercent;
    }

    /**
     * Get whitePercent
     *
     * @return integer
     */
    public function getWhitePercent()
    {
        return $this->whitePercent;
    }

    /**
     * Set otherPercent
     *
     * @param integer $otherPerfect
     * @return ADM
     */
    public function setOtherPercent($otherPerfect)
    {
        $this->otherPercent = $otherPerfect;

        return $this;
    }

    /**
     * Get otherPercent
     *
     * @return integer
     */
    public function getOtherPercent()
    {
        return $this->otherPercent;
    }

    /**
     * Set hsvCityName
     *
     * @param string $hsvCityName
     * @return ADM
     */
    public function setHsvCityName($hsvCityName)
    {
        $this->hsvCityName = $hsvCityName;

        return $this;
    }

    /**
     * Get hsvCityName
     *
     * @return string
     */
    public function getHsvCityName()
    {
        return $this->hsvCityName;
    }

    /**
     * Set grade
     *
     * @param string $grade
     * @return ADM
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

	public function formattedString() {
		$grade = '';
		if( $this->getGrade() == 99 ) {
			$grade = 'PreK';
		} elseif ( $this->getGrade() == 00 ) {
			$grade = 'K';
		} else {
			$grade = $this->getGrade();
		}
		return $this->getSchoolName() . ' - Grade: ' . $grade;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {

		/*$grade = '';
		if( $this->getGrade() == 99 ) {
			$grade = 'PreK';
		} elseif ( $this->getGrade() == 00 ) {
			$grade = 'K';
		} else {
			$grade = $this->getGrade();
		}*/
		$grade = $this->getGrade();
		return $this->getSchoolName() . ' - Grade: ' . $grade;
		/*
		switch( $grade ) {
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
			default:
				return ucwords( strtolower( $this->getHsvCityName() . ' ELEMENTARY SCHOOL' ) ) . ' - Grade: ' . $grade;
				break;

			case 6:
			case 7:
			case 8:
				return ucwords( strtolower( $this->getHsvCityName() . ' MIDDLE SCHOOL' ) ) . ' - Grade: ' . $grade;
				break;

			case 9:
			case 10:
			case 11:
			case 12:
				return ucwords( strtolower( $this->getHsvCityName() . ' HIGH SCHOOL' ) ) . ' - Grade: ' . $grade;
				break;
		}
		return $this->getHsvCityName() . ' - Grade: ' . $grade;
		*/
	}

    /**
     * Set groupID
     *
     * @param \IIAB\StudentTransferBundle\Entity\SchoolGroup $groupID
     * @return ADM
     */
    public function setGroupID(\IIAB\StudentTransferBundle\Entity\SchoolGroup $groupID = null)
    {
        $this->groupID = $groupID;

        return $this;
    }

    /**
     * Get groupID
     *
     * @return \IIAB\StudentTransferBundle\Entity\SchoolGroup
     */
    public function getGroupID()
    {
        return $this->groupID;
    }
}
