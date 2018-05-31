<?php

namespace IIAB\StudentTransferBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class User extends BaseUser {

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var string
	 * @ORM\Column(name="firstName", type="string", length=255, nullable=true)
	 */
	private $firstName;

	/**
	 * @var string
	 * @ORM\Column(name="lastName", type="string", length=255, nullable=true)
	 */
	private $lastName;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="schools", type="array", nullable=true)
	 */
	private $schools;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="forms", type="array", nullable=true)
	 */
	private $forms;

	public function __construct() {

		parent::__construct();

		$this->schools = array();
		$this->forms = array();
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

	public function getName() {

		return $this->getLastName() . ', ' . $this->getFirstName();
	}

	/**
	 * @param $school
	 *
	 * @return $this
	 */
	public function addSchool( $school ) {

		$school = strtoupper( $school );

		if( empty( $this->schools) ){
			$this->schools = [];
		}

		if( !in_array( $school , $this->schools , true ) ) {
			$this->schools[] = $school;
		}
		return $this;
	}

	public function removeSchool( $school ) {
		if (false !== $key = array_search(strtoupper($school), $this->schools, true)) {
			unset($this->schools[$key]);
			$this->schools = array_values($this->schools);
		}

		return $this;
	}

	/**
	 * Returns the user schools
	 *
	 * @return array The Schools
	 */
	public function getSchools() {

		$schools = $this->schools;

		if( empty( $schools ) ) {
			return array();
		}

		return array_unique( $schools );
	}

	/**
	 * User has access to a specific school.
	 *
	 * @param string $school
	 *
	 * @return boolean
	 */
	public function hasSchool( $school ) {

		if( method_exists($school, 'getGroupID' ) ){
			return in_array( strtoupper( $school->getGroupId()->getId() ) , $this->getSchools() , true );
		}
		return in_array( strtoupper( $school->getId() ) , $this->getSchools() , true );
	}

	/**
     * Set schools
     *
     * @param array $schools
     * @return User
     */
    public function setSchools($schools)
    {
        $this->schools = $schools;

        return $this;
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
	 * @param $form
	 *
	 * @return $this
	 */
	public function addForm( $form ) {

		$form = strtoupper( $form );

		if( empty( $this->forms) ){
			$this->forms = [];
		}

		if( !in_array( $form , $this->forms , true ) ) {
			$this->forms[] = $form;
		}
		return $this;
	}

	public function removeForm( $form ) {
		if (false !== $key = array_search(strtoupper($form), $this->forms, true)) {
			unset($this->forms[$key]);
			$this->forms = array_values($this->forms);
		}

		return $this;
	}

	/**
	 * Returns the user forms
	 *
	 * @return array The Forms
	 */
	public function getForms() {

		$forms = $this->forms;

		if( empty( $forms ) ) {
			return array();
		}

		return array_unique( $forms );
	}

	/**
	 * User has access to a specific form.
	 *
	 * @param string $form
	 *
	 * @return boolean
	 */
	public function hasForm( $form ) {

		return in_array( strtoupper( $form->getId() ) , $this->getForms() , true );
	}

	/**
     * Set forms
     *
     * @param array $forms
     * @return User
     */
    public function setForms($forms)
    {
        $this->forms = $forms;

        return $this;
    }
}
