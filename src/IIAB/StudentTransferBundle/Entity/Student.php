<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Student
 *
 * @ORM\Table(name="inow")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\StudentRepository")
 * Last name, First name, Student#, Race, DoB, Street#, Street name, City, Zip, School, Grade)
 */
class Student {

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
	 * @ORM\Column(name="first_name", type="string", length=255)
	 */
	private $firstName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="last_name", type="string", length=255)
	 */
	private $lastName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="studentID", type="string", length=255, unique=false, nullable=true)
	 */
	private $studentID;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="race", type="string", length=255)
	 */
	private $race;

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
	 * @ORM\Column(name="zip", type="string", length=255)
	 */
	private $zip;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="primTelephone", type="string", length=255)
	 */
	private $primTelephone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="secTelephone", type="string", length=255, nullable=true)
	 */
	private $secTelephone;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="email", type="string", length=255, nullable=true)
	 */
	private $email;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="school", type="string", length=255, nullable=true)
	 */
	private $school;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="dob", type="string", length=255)
	 */
	private $dob;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="grade", type="integer")
	 */
	private $grade;

	/**
	 * @var int
	 *
	 * @ORM\Column(name="IsHispanic", type="integer", length=1, options={"default":0})
	 */
	private $IsHispanic = 0;

	/**
	 * @return string
	 */
	public function getSchool() {

		return $this->school;
	}

	/**
	 * @param string $school
	 */
	public function setSchool( $school ) {

		$this->school = $school;
	}

	/**
	 * @return string
	 */
	public function getRace() {

		return $this->race;
	}

	/**
	 * @param string $race
	 */
	public function setRace( $race ) {

		$this->race = $race;
	}

	/**
	 * @return string
	 */
	public function getStudentID() {

		return $this->studentID;
	}

	/**
	 * @param string $studentID
	 */
	public function setStudentID( $studentID ) {

		$this->studentID = $studentID;
	}

	/**
	 * @return string
	 */
	public function getDob() {

		return $this->dob;
	}

	/**
	 * @param string $dob
	 */
	public function setDob( $dob ) {

		$this->dob = $dob;
	}

	/**
	 * @return int
	 */
	public function getGrade() {
		if( $this->grade > 95 )
			return 99;

		return $this->grade;
	}

	/**
	 * @param int $grade
	 */
	public function setGrade( $grade ) {

		$this->grade = $grade;
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
	 * Get firstName
	 *
	 * @return string
	 */
	public function getFirstName() {

		return $this->firstName;
	}

	/**
	 * Set firstName
	 *
	 * @param string $firstName
	 *
	 * @return Student
	 */
	public function setFirstName( $firstName ) {

		$this->firstName = $firstName;

		return $this;
	}

	/**
	 * Get lastName
	 *
	 * @return string
	 */
	public function getLastName() {

		return $this->lastName;
	}

	/**
	 * Set lastName
	 *
	 * @param string $lastName
	 *
	 * @return Student
	 */
	public function setLastName( $lastName ) {

		$this->lastName = $lastName;

		return $this;
	}

	/**
	 * Get address
	 *
	 * @return string
	 */
	public function getAddress() {

		return $this->address;
	}

	/**
	 * Set address
	 *
	 * @param string $address
	 *
	 * @return Student
	 */
	public function setAddress( $address ) {

		$this->address = $address;

		return $this;
	}

	/**
	 * Get city
	 *
	 * @return string
	 */
	public function getCity() {

		return $this->city;
	}

	/**
	 * Set city
	 *
	 * @param string $city
	 *
	 * @return Student
	 */
	public function setCity( $city ) {

		$this->city = $city;

		return $this;
	}

	/**
	 * Get zip
	 *
	 * @return string
	 */
	public function getZip() {

		return $this->zip;
	}

	/**
	 * Set zip
	 *
	 * @param string $zip
	 *
	 * @return Student
	 */
	public function setZip( $zip ) {

		$this->zip = $zip;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getPrimTelephone() {
		return $this->primTelephone;
	}

	/**
	 * @param  $primTelephone
	 *
	 * @return Student
	 */
	public function setPrimTelephone( $primTelephone ) {

		$this->primTelephone = $primTelephone;

		return $this;
	}

	/**
	 * @return integer
	 */
	public function getSecTelephone() {

		return $this->secTelephone;
	}

	/**
	 * @param int $secTelephone
	 *
	 * @return Student
	 */
	public function setSecTelephone( $secTelephone ) {

		$this->secTelephone = $secTelephone;

		return $this;
	}
	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @return int
	 */
	public function getIsHispanic() {

		return $this->IsHispanic;
	}

	/**
	 * @param int $IsHispanic
	 */
	public function setIsHispanic( $IsHispanic ) {

		$this->IsHispanic = $IsHispanic;
	}

	/**
	 * @param string $email
	 */
	public function setEmail( $email ) {

		$this->email = $email;
	}
}
