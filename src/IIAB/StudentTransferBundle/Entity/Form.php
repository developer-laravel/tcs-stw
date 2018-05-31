<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Form
 *
 * @ORM\Table(name="form")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\FormRepository")
 */
class Form {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="formID", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="form_name", type="string", length=255)
	 */
	private $formName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="form_description", type="text")
	 */
	private $formDescription;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="form_confirmation", type="string", length=255)
	 */
	private $formConfirmation;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="route", type="string", length=255, nullable=true)
	 */
	private $route;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="public", type="boolean", nullable=true)
	 */
	private $public = false;

	/**
	 * @ORM\OneToMany(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm", mappedBy="form")
	 */
	private $openEnrollments;

	/**
	 * @var integer
	 *
	 * @ORM\Column(type="integer", nullable=true)
	 */
	private $acceptanceWindow;

	/**
	 * @return string
	 */
	public function getFormConfirmation() {

		return $this->formConfirmation;
	}

	/**
	 * @param string $formConfirmation
	 */
	public function setFormConfirmation( $formConfirmation ) {

		$this->formConfirmation = $formConfirmation;
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
	 * Get formDescription
	 *
	 * @return string
	 */
	public function getFormDescription() {

		return $this->formDescription;
	}

	/**
	 * Set formDescription
	 *
	 * @param string $formDescription
	 *
	 * @return Form
	 */
	public function setFormDescription( $formDescription ) {

		$this->formDescription = $formDescription;

		return $this;
	}

	/**
	 * Get route
	 *
	 * @return string
	 */
	public function getRoute() {

		return $this->route;
	}

	/**
	 * Set route
	 *
	 * @param string $route
	 *
	 * @return Form
	 */
	public function setRoute( $route ) {

		$this->route = $route;

		return $this;
	}

	/**
	 * Get public
	 *
	 * @return boolean
	 */
	public function getPublic() {

		return $this->public;
	}

	/**
	 * Set public
	 *
	 * @param boolean $public
	 *
	 * @return Form
	 */
	public function setPublic( $public ) {

		$this->public = $public;

		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString() {

		return $this->getFormName() ? : 'Create';
	}

	/**
	 * Get formName
	 *
	 * @return string
	 */
	public function getFormName() {

		return $this->formName;
	}

	/**
	 * Set formName
	 *
	 * @param string $formName
	 *
	 * @return Form
	 */
	public function setFormName( $formName ) {

		$this->formName = $formName;

		return $this;
	}

	/**
	 * Get acceptanceWindow
	 *
	 * @return string
	 */
	public function getAcceptanceWindow() {

		return $this->acceptanceWindow;
	}

	/**
	 * Set acceptanceWindow
	 *
	 * @param string $acceptanceWindow
	 *
	 * @return Form
	 */
	public function setAcceptanceWindow( $acceptanceWindow ) {

		$this->acceptanceWindow = $acceptanceWindow;

		return $this;
	}




    /**
     * Constructor
     */
    public function __construct()
    {
        $this->openEnrollments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add openEnrollments
     *
     * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $openEnrollments
     * @return Form
     */
    public function addOpenEnrollment(\IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $openEnrollments)
    {
        $this->openEnrollments[] = $openEnrollments;

        return $this;
    }

    /**
     * Remove openEnrollments
     *
     * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $openEnrollments
     */
    public function removeOpenEnrollment(\IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $openEnrollments)
    {
        $this->openEnrollments->removeElement($openEnrollments);
    }

    /**
     * Get openEnrollments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOpenEnrollments()
    {
        return $this->openEnrollments;
    }
}
