<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Student
 *
 * @ORM\Table(name="student", indexes={@ORM\Index(name="stateId_idx", columns={"stateID"})})
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\StudentRepository")
 */
class Student
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
     * @var string
     *
     * @ORM\Column(name="stateID", type="string", length=255, nullable=true)
     */
    private $stateID;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=255)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=255)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="race", type="string", length=255)
     */
    protected $race;

    /**
     * @var int
     *
     * @ORM\Column(name="IsHispanic", type="integer", length=1, options={"default":0})
     */
    private $IsHispanic = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=255)
     */
    private $gender;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="birthday", type="date")
     */
    private $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=2)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="zip", type="string", length=255)
     */
    private $zip;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="gradeLevel", type="integer")
     */
    private $gradeLevel;

    /**
     * @var string
     *
     * @ORM\Column(name="currentSchool", type="string", length=255)
     */
    private $currentSchool;

    /**
     * @var string
     *
     * @ORM\Column(name="nextSchool", type="string", length=255, nullable=true)
     */
    private $nextSchool;

    /**
     * @var string
     *
     * @ORM\Column(name="zonedElementary", type="string", length=255, nullable=true)
     */
    private $zonedElementary;

    /**
     * @var string
     *
     * @ORM\Column(name="zonedMiddle", type="string", length=255, nullable=true)
     */
    private $zonedMiddle;

    /**
     * @var string
     *
     * @ORM\Column(name="zonedHigh", type="string", length=255, nullable=true)
     */
    private $zonedHigh;

    /**
     * @var integer
     *
     * @ORM\Column(name="isDistrictStudent", type="integer", length=1, options={"default":1})
     */
    private $isDistrictStudent = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="dborId", type="integer")
     */
    private $dborId;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="LeanFrog\SharedDataBundle\Entity\StudentData", mappedBy="student", cascade={"all"})
     */
    private $additionalData;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="LeanFrog\SharedDataBundle\Entity\StudentGrade", mappedBy="student", cascade={"all"})
     */
    private $grades;

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
     * Set stateID
     *
     * @param string $stateID
     *
     * @return Student
     */
    public function setStateID($stateID)
    {
        $this->stateID = $stateID;

        return $this;
    }

    /**
     * Get stateID
     *
     * @return string
     */
    public function getStateID()
    {
        return $this->stateID;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Student
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Student
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set race
     *
     * @param string $race
     *
     * @return Student
     */
    public function setRace($race)
    {
        $this->race = $race;

        return $this;
    }

    /**
     * Get race
     *
     * @return string
     */
    public function getRace()
    {
        return $this->race;
    }

    /**
     * Set isHispanic
     *
     * @param integer $isHispanic
     *
     * @return Student
     */
    public function setIsHispanic($isHispanic)
    {
        $this->IsHispanic = $isHispanic;

        return $this;
    }

    /**
     * Get isHispanic
     *
     * @return integer
     */
    public function getIsHispanic()
    {
        return $this->IsHispanic;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Student
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set birthday
     *
     * @param \DateTime $birthday
     *
     * @return Student
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return \DateTime
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Student
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Student
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Student
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set zip
     *
     * @param string $zip
     *
     * @return Student
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Student
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set gradeLevel
     *
     * @param integer $gradeLevel
     *
     * @return Student
     */
    public function setGradeLevel($gradeLevel)
    {
        $this->gradeLevel = $gradeLevel;

        return $this;
    }

    /**
     * Get gradeLevel
     *
     * @return integer
     */
    public function getGradeLevel()
    {
        return $this->gradeLevel;
    }

    /**
     * Set currentSchool
     *
     * @param string $currentSchool
     *
     * @return Student
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
     * Set nextSchool
     *
     * @param string $nextSchool
     *
     * @return Student
     */
    public function setNextSchool($nextSchool)
    {
        $this->nextSchool = $nextSchool;

        return $this;
    }

    /**
     * Get nextSchool
     *
     * @return string
     */
    public function getNextSchool()
    {
        return $this->nextSchool;
    }

    /**
     * Set isDistrictStudent
     *
     * @param integer $isDistrictStudent
     *
     * @return Student
     */
    public function setIsDistrictStudent($isDistrictStudent)
    {
        $this->isDistrictStudent = $isDistrictStudent;

        return $this;
    }

    /**
     * Get isDistrictStudent
     *
     * @return integer
     */
    public function getIsDistrictStudent()
    {
        return $this->isDistrictStudent;
    }

    /**
     * Set dborId
     *
     * @param integer $dborId
     *
     * @return Student
     */
    public function setDborId($dborId)
    {
        $this->dborId = $dborId;

        return $this;
    }

    /**
     * Get dborId
     *
     * @return integer
     */
    public function getDborId()
    {
        return $this->dborId;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->additionalData = new \Doctrine\Common\Collections\ArrayCollection();
        $this->grades = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add additionalDatum
     *
     * @param \LeanFrog\SharedDataBundle\Entity\StudentData $additionalDatum
     *
     * @return Student
     */
    public function addAdditionalDatum(\LeanFrog\SharedDataBundle\Entity\StudentData $additionalDatum)
    {
        $this->additionalData[] = $additionalDatum;

        return $this;
    }

    /**
     * Remove additionalDatum
     *
     * @param \LeanFrog\SharedDataBundle\Entity\StudentData $additionalDatum
     */
    public function removeAdditionalDatum(\LeanFrog\SharedDataBundle\Entity\StudentData $additionalDatum)
    {
        $this->additionalData->removeElement($additionalDatum);
    }

    /**
     * Get additionalData
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }






    /**
     * Add Grade
     *
     * @param \LeanFrog\SharedDataBundle\Entity\StudentGrade $studentGrade
     *
     * @return Student
     */
    public function addGrade(\LeanFrog\SharedDataBundle\Entity\StudentGrade $studentGrade)
    {
        $this->grades[] = $studentGrade;

        return $this;
    }

    /**
     * Remove grade
     *
     * @param \LeanFrog\SharedDataBundle\Entity\StudentGrade $studentGrade
     */
    public function removeGrade(\LeanFrog\SharedDataBundle\Entity\StudentGrade $studentGrade)
    {
        $this->grades->removeElement($studentGrade);
    }

    /**
     * Get grades
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGrades()
    {
        return $this->grades;
    }

    /**
     * Set zonedElementary
     *
     * @param string $zonedElementary
     *
     * @return Student
     */
    public function setZonedElementary($zonedElementary)
    {
        $this->zonedElementary = $zonedElementary;

        return $this;
    }

    /**
     * Get zonedElementary
     *
     * @return string
     */
    public function getZonedElementary()
    {
        return $this->zonedElementary;
    }

    /**
     * Set zonedMiddle
     *
     * @param string $zonedMiddle
     *
     * @return Student
     */
    public function setZonedMiddle($zonedMiddle)
    {
        $this->zonedMiddle = $zonedMiddle;

        return $this;
    }

    /**
     * Get zonedMiddle
     *
     * @return string
     */
    public function getZonedMiddle()
    {
        return $this->zonedMiddle;
    }

    /**
     * Set zonedHigh
     *
     * @param string $zonedHigh
     *
     * @return Student
     */
    public function setZonedHigh($zonedHigh)
    {
        $this->zonedHigh = $zonedHigh;

        return $this;
    }

    /**
     * Get zonedHigh
     *
     * @return string
     */
    public function getZonedHigh()
    {
        return $this->zonedHigh;
    }
}
