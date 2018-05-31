<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use IIAB\StudentTransferBundle\Command\FindCorrectZonedSchoolCommand;
use IIAB\StudentTransferBundle\Command\FindSubmittedUserCommand;

/**
 * Submission
 *
 * @ORM\Table(name="submission")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\SubmissionRepository")
 */
class Submission {

	/**
	 * @ORM\ManyToOne( targetEntity="ADM" )
	 * @ORM\JoinColumn( name="firstChoice", referencedColumnName="id", nullable=true )
	 */
	protected $firstChoice;

	/**
	 * @ORM\ManyToOne( targetEntity="ADM")
	 * @ORM\JoinColumn( name="secondChoice", referencedColumnName="id", nullable=true )
	 */
	protected $secondChoice;

	/**
	 * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
	 * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
	 */
	protected $enrollmentPeriod;

	/**
	 * @ORM\ManyToOne( targetEntity="SubmissionStatus" )
	 * @ORM\JoinColumn( name="submissionStatus", referencedColumnName="id" )
	 */
	protected $submissionStatus;

	/**
	 *
	 * @ORM\ManyToOne( targetEntity="ADM" )
	 * @ORM\JoinColumn( name="awardedSchoolID", referencedColumnName="id", nullable=true )
	 */
	protected $awardedSchoolID;

	/**
	 * @var array
	 *
	 * @ORM\OneToMany( targetEntity="SubmissionData", mappedBy="submission" )
	 */
	protected $submissionData;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="submissionID", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @ORM\ManyToOne( targetEntity="Form" )
	 * @ORM\JoinColumn( name="formID", referencedColumnName="formID" )
	 */
	private $formID;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="submissionDateTime", type="datetime")
	 */
	private $submissionDateTime;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="lotteryNumber", type="bigint")
	 */
	private $lotteryNumber;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="studentID", type="string", length=255, nullable=true)
	 */
	private $studentID;

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
	 * @ORM\Column(name="dob", type="string", length=255)
	 */
	private $dob;

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
	 * @var integer
	 *
	 * @ORM\Column(name="grade", type="string", length=255)
	 */
	private $grade;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="confirmationNumber", type="string", length=255, nullable=true)
	 */
	private $confirmationNumber;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="controlNumber", type="string", length=255, nullable=true)
	 */
	private $controlNumber;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="hsvZonedSchools", type="array", nullable=true)
	 */
	private $hsvZonedSchools;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="url", type="string", length=255, nullable=true)
	 */
	private $url;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="roundExpires", type="integer", nullable=true)
	 */
	private $roundExpires;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="manualAwardDate", type="datetime", nullable=true)
     */
    private $manualAwardDate;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="employeeID" , type="string", length=255, nullable=true)
	 */
	private $employeeID;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="employeeFirstName" , type="string", length=255, nullable=true)
	 */
	private $employeeFirstName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="employeeLastName" , type="string", length=255, nullable=true)
	 */
	private $employeeLastName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="employeeLocation" , type="string", length=255, nullable=true)
	 */
	private $employeeLocation;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="currentSchool" , type="string", length=255, nullable=true)
	 */
	private $currentSchool;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\Race")
	 * @ORM\JoinColumn(name="race", referencedColumnName="id", nullable=true)
	 */
	protected $race;

	/**
	 * @var bool
	 *
	 * @ORM\Column(type="boolean", options={"default":false})
	 */
	private $afterLotterySubmission = false;

	/**
	 * @var ArrayCollection
	 * @ORM\OneToMany(targetEntity="IIAB\StudentTransferBundle\Entity\WaitList", mappedBy="submission")
	 */
	protected $waitList;

	public function __construct() {

		$this->submissionData = new ArrayCollection();
		$this->waitList = new ArrayCollection();
	}

	/**
	 * @return ADM
	 */
	public function getAwardedSchoolID() {

		return $this->awardedSchoolID;
	}

	/**
	 * @param ADM $awardedSchoolID
	 */
	public function setAwardedSchoolID( $awardedSchoolID ) {

		$this->awardedSchoolID = $awardedSchoolID;
	}

	/**
	 * @return string
	 */
	public function getUrl() {

		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl( $url ) {

		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getControlNumber() {

		return $this->controlNumber;
	}

	/**
	 * @param string $controlNumber
	 */
	public function setControlNumber( $controlNumber ) {

		$this->controlNumber = $controlNumber;
	}

	/**
	 * @return int
	 */
	public function getLotteryNumber() {

		return $this->lotteryNumber;
	}

	/**
	 * @param int $lotteryNumber
	 */
	public function setLotteryNumber( $lotteryNumber ) {

		$this->lotteryNumber = $lotteryNumber;
	}

	/**
	 * Get formID
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\Form
	 */
	public function getFormID() {

		return $this->formID;
	}

	/**
	 * Set formID
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\Form $formID
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\Form
	 */
	public function setFormID( Form $formID ) {

		$this->formID = $formID;

		return $this;
	}

	/**
	 * Get submissionDateTime
	 *
	 * @return \DateTime
	 */
	public function getSubmissionDateTime() {

		return $this->submissionDateTime;
	}

	/**
	 * Set submissionDateTime
	 *
	 * @param \DateTime $submissionDateTime
	 *
	 * @return Submission
	 */
	public function setSubmissionDateTime( $submissionDateTime ) {

		$this->submissionDateTime = $submissionDateTime;

		return $this;
	}

	/**
	 * Get enrollmentPeriod
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
	 */
	public function getEnrollmentPeriod() {

		return $this->enrollmentPeriod;
	}

	/**
	 * Set enrollmentPeriod
	 *
	 * @param OpenEnrollment $enrollmentPeriod
	 *
	 * @return Submission
	 */
	public function setEnrollmentPeriod( OpenEnrollment $enrollmentPeriod = null ) {

		$this->enrollmentPeriod = $enrollmentPeriod;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAddress() {

		return $this->address;
	}

	/**
	 * @param string $address
	 */
	public function setAddress( $address ) {

		$this->address = $address;
	}

	/**
	 * @return string
	 */
	public function getCity() {

		return $this->city;
	}

	/**
	 * @param string $city
	 */
	public function setCity( $city ) {

		$this->city = $city;
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
	 * @return string
	 */
	public function getPrimTelephone() {
		return $this->primTelephone;
	}

	/**
	 * @param string $primTelephone
	 */
	public function setPrimTelephone( $primTelephone ) {

		$this->primTelephone = $primTelephone;
	}

	/**
	 * @return string
	 */
	public function getSecTelephone() {

		return $this->secTelephone;
	}

	/**
	 * @param string $secTelephone
	 */
	public function setSecTelephone( $secTelephone ) {

		$this->secTelephone = $secTelephone;
	}

	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail( $email ) {

		$this->email = $email;
	}

	/**
	 * @return ADM
	 */
	public function getFirstChoice() {

		return $this->firstChoice;
	}

	/**
	 * @param \IIAB\StudentTransferBundle\Entity\ADM $firstChoice
	 */
	public function setFirstChoice( ADM $firstChoice = null ) {

		$this->firstChoice = $firstChoice;
	}

	/**
	 * @return int
	 */
	public function getGrade() {

		return $this->grade;
	}

	/**
	 * @param int $grade
	 */
	public function setGrade( $grade ) {

		$this->grade = $grade;
	}

	/**
	 * @return ADM
	 */
	public function getSecondChoice() {

		return $this->secondChoice;
	}

	/**
	 * @param ADM $secondChoice
	 */
	public function setSecondChoice( ADM $secondChoice = null) {

		$this->secondChoice = $secondChoice;
	}

	/**
	 * @return SubmissionStatus
	 */
	public function getSubmissionStatus() {

		return $this->submissionStatus;
	}

	/**
	 * @param SubmissionStatus $submissionStatus
	 */
	public function setSubmissionStatus( SubmissionStatus $submissionStatus ) {

		$this->submissionStatus = $submissionStatus;
	}

	/**
	 * @return string
	 */
	public function getZip() {

		return $this->zip;
	}

	/**
	 * @param string $zip
	 */
	public function setZip( $zip ) {

		$this->zip = $zip;
	}

	/**
	 * @return string
	 */
	public function __toString() {

		return $this->getId() ? '' . $this->getConfirmationNumber() : 'Create';
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
	 * @return string
	 */
	public function getConfirmationNumber() {

		return $this->confirmationNumber;
	}

	/**
	 * @param string $confirmationNumber
	 */
	public function setConfirmationNumber( $confirmationNumber ) {

		$this->confirmationNumber = $confirmationNumber;
	}

	public function getName() {

		if ( !empty( $this->lastName ) && !empty( $this->firstName ) ) {
			return $this->getLastName() . ', ' . $this->getFirstName();
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	public function getLastName() {

		return $this->lastName;
	}

	/**
	 * @param string $lastName
	 */
	public function setLastName( $lastName ) {

		$this->lastName = $lastName;
	}

	/**
	 * @return string
	 */
	public function getFirstName() {

		return $this->firstName;
	}

	/**
	 * @param string $firstName
	 */
	public function setFirstName( $firstName ) {

		$this->firstName = $firstName;
	}

	public function getSubmitter() {

		return '';

		//$findSubmitteduser = new FindSubmittedUserCommand();
		//$user = $findSubmitteduser->findSubmittedUser( $this->getId() , $this->getStudentID() );

		return $user;
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
	 * @return integer
	 */
	public function getRoundExpires() {

		return $this->roundExpires;
	}

	/**
	 * @param integer $urlExpires
	 */
	public function setRoundExpires( $urlExpires ) {

		$this->roundExpires = $urlExpires;
	}

    /**
     * Get manualAwardDate
     *
     * @return \DateTime
     */
    public function getManualAwardDate() {

        return $this->manualAwardDate;
    }

    /**
     * Set manualAwardDate
     *
     * @param \DateTime $manualAwardDate
     *
     * @return Submission
     */
    public function setmanualAwardDate( $manualAwardDate ) {

        $this->manualAwardDate = $manualAwardDate;

        return $this;
    }

	public function getHsvZonedSchoolsString() {

		if ( $this->getFirstChoice() != null ) {

			$findCorrectZonedSchool = new FindCorrectZonedSchoolCommand();
			$school = $findCorrectZonedSchool->findCorrectZonedSchool( $this->hsvZonedSchools , $this->getFirstChoice()->getGrade() , $this->getEnrollmentPeriod()->getId() );

			if ( !empty( $school ) ) {
				return $school;
			}

			switch ( $this->getFirstChoice()->getGrade() ) {
				case 0:
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
				default:
					if ( isset( $this->hsvZonedSchools["ELEMENTARY SCHOOL"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["ELEMENTARY SCHOOL"] . ' ELEMENTARY SCHOOL' ) );
					} else if ( isset( $this->hsvZonedSchools["Elementary"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["Elementary"] . ' ELEMENTARY SCHOOL' ) );
					} else {
						return '';
					}
					break;

				case 6:
				case 7:
				case 8:
					if ( isset( $this->hsvZonedSchools["MIDDLE SCHOOL"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["MIDDLE SCHOOL"] . ' MIDDLE SCHOOL' ) );
					} else if ( isset( $this->hsvZonedSchools["Middle"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["Middle"] . ' MIDDLE SCHOOL' ) );
					} else {
						return '';
					}
					break;

				case 9:
				case 10:
				case 11:
				case 12:
					if ( isset( $this->hsvZonedSchools["HIGH SCHOOL"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["HIGH SCHOOL"] . ' HIGH SCHOOL' ) );
					} else if ( isset( $this->hsvZonedSchools["High"] ) ) {
						return ucwords( strtolower( $this->hsvZonedSchools["High"] . ' HIGH SCHOOL' ) );
					} else {
						return '';
					}
					break;
			}
		} else {
			return '';
		}
	}

	/**
	 * @return array
	 */
	public function getHsvZonedSchools() {

		return $this->hsvZonedSchools;
	}

	/**
	 * @param array $hsvZonedSchools
	 */
	public function setHsvZonedSchools( $hsvZonedSchools ) {

		$this->hsvZonedSchools = $hsvZonedSchools;
	}

	/**
	 * @return string
	 */
	public function getEmployeeFirstName() {

		return $this->employeeFirstName;
	}

	/**
	 * @param string $employeeFirstName
	 */
	public function setEmployeeFirstName( $employeeFirstName ) {

		$this->employeeFirstName = $employeeFirstName;
	}

	/**
	 * @return string
	 */
	public function getEmployeeID() {

		return $this->employeeID;
	}

	/**
	 * @param string $employeeID
	 */
	public function setEmployeeID( $employeeID ) {

		$this->employeeID = $employeeID;
	}

	/**
	 * @return string
	 */
	public function getEmployeeLastName() {

		return $this->employeeLastName;
	}

	/**
	 * @param string $employeeLastName
	 */
	public function setEmployeeLastName( $employeeLastName ) {

		$this->employeeLastName = $employeeLastName;
	}

	/**
	 * @return string
	 */
	public function getEmployeeLocation() {

		return $this->employeeLocation;
	}

	/**
	 * @param string $employeeLocation
	 */
	public function setEmployeeLocation( $employeeLocation ) {

		$this->employeeLocation = $employeeLocation;
	}

	/**
	 * @param bool $format
	 * @return string
	 */
	public function getCurrentSchool( $format = true ) {

		if ( $format ) {
			if ( !empty( $this->currentSchool ) && ( preg_match( '/(\bp8\b|\bp-8\b|\bp6\b|\bp-6\b|\bNon TCS Student\b|\bN\/A\b|\bMiddle\b|\bElementary\b|\bHigh\b|\bSchool\b|\bAcademy\b)/i' , $this->currentSchool ) == 0 ) ) {
				switch ( $this->getGrade() ) {
					case 0:
					case 1:
					case 2:
					case 3:
					case 4:
					case 5:
					default:
						return ucwords( strtolower( $this->currentSchool . ' ELEMENTARY SCHOOL' ) );
						break;

					case 6:
					case 7:
					case 8:
						return ucwords( strtolower( $this->currentSchool . ' MIDDLE SCHOOL' ) );
						break;

					case 9:
					case 10:
					case 11:
					case 12:
						return ucwords( strtolower( $this->currentSchool . ' HIGH SCHOOL' ) );
						break;

				}
			}
		}
		return $this->currentSchool;
	}

	/**
	 * @param string $currentSchool
	 */
	public function setCurrentSchool( $currentSchool ) {

		$this->currentSchool = $currentSchool;
	}

	/**
	 * Get race
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\Race
	 */
	public function getRace() {

		return $this->race;
	}

	/**
	 * Set race
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\Race $race
	 *
	 * @return Submission
	 */
	public function setRace( Race $race = null ) {

		$this->race = $race;

		return $this;
	}

	/**
	 * Gets the Race in a specific format.
	 * This should be used all the time for requesting the Race
	 */
	public function getRaceFormatted() {

		$race = $this->getRace()->getShortName();
	}

	/**
	 * Add submissionData
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\SubmissionData $submissionData
	 *
	 * @return Submission
	 */
	public function addSubmissionDatum( \IIAB\StudentTransferBundle\Entity\SubmissionData $submissionData ) {

		$this->submissionData[] = $submissionData;

		return $this;
	}

	/**
	 * Remove submissionData
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\SubmissionData $submissionData
	 */
	public function removeSubmissionDatum( \IIAB\StudentTransferBundle\Entity\SubmissionData $submissionData ) {

		$this->submissionData->removeElement( $submissionData );
	}

	/**
	 * Get submissionData
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSubmissionData() {

		return $this->submissionData;
	}

	public function getSubmissionDataString() {

		$returnString = array();
		foreach ( $this->submissionData as $submissionData ) {
			$returnString[] = $submissionData->getMetaKey() . ': ' . $submissionData->getMetaValue();
		}
		if ( $this->getEmployeeID() ) {
			$returnString[] = 'Employee ID: ' . addslashes( $this->getEmployeeID() );
			$returnString[] = 'Employee First Name: ' . addslashes( $this->getEmployeeFirstName() );
			$returnString[] = 'Employee Last Name: ' . addslashes( $this->getEmployeeLastName() );
			$returnString[] = 'Employee Location: ' . addslashes( $this->getEmployeeLocation() );
		}
		return implode( ' | ' , $returnString );
	}

	public function getSubmissionDataByKey( $key, $single = true ){
		$hash = $this->getSubmissionDataHash();

		if( !isset( $hash[$key] ) ){
			return null;
		}

		if( $single ){
			$max_id = max( array_keys( $hash[$key] ) );
			return $hash[$key][$max_id];
		}

		return $hash[$key];
	}

	public function getSubmissionDataHash() {
		$submisison_data_hash = [];
		foreach( $this->getSubmissionData() as $datum ){
			$submisison_data_hash[ $datum->getMetaKey()][ $datum->getId() ] = $datum;
		}
		return $submisison_data_hash;
	}

	/**
	 * Gets the AFter Lottery Submission Boolean value.
	 *
	 * @return bool
	 */
	public function isAfterLotterySubmission() {

		return $this->afterLotterySubmission;
	}

	/**
	 * @return boolean
	 */
	public function getAfterLotterySubmissionReportStyle() {

		if ( $this->afterLotterySubmission ) {
			return 'After Lottery Window';
		} else {
			return 'During Lottery Window';
		}
	}

	/**
	 * @param boolean $afterLotterySubmission
	 */
	public function setAfterLotterySubmission( $afterLotterySubmission ) {

		$this->afterLotterySubmission = $afterLotterySubmission;
	}

	/**
	 * Get afterLotterySubmission
	 *
	 * @return boolean
	 */
	public function getAfterLotterySubmission() {
		return $this->afterLotterySubmission;
	}

	/**
	 * Add waitList
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\WaitList $waitList
	 * @return Submission
	 */
	public function addWaitList( \IIAB\StudentTransferBundle\Entity\WaitList $waitList ) {

		$this->waitList[] = $waitList;
		$waitList->setSubmission( $this );

		return $this;
	}

	/**
	 * Remove waitList
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\WaitList $waitList
	 */
	public function removeWaitList( \IIAB\StudentTransferBundle\Entity\WaitList $waitList ) {

		$waitList->setSubmission( null );
		$this->waitList->removeElement( $waitList );
	}

	/**
	 * Get waitList
	 *
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getWaitList() {
		return $this->waitList;
	}

	public function getIsRenewal(){
		$datum = $this->getSubmissionDataByKey('is_renewal');

		if( $datum == null ){
			return 'Initial';
		}
		return ( $datum->getMetaValue() )
			? 'Renewal' : 'Initial';
	}
}
