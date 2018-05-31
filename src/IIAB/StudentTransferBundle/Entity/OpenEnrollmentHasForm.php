<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpenEnrollmentHasForm
 *
 * @ORM\Table(name="openerollmentforms")
 * @ORM\Entity
 */
class OpenEnrollmentHasForm {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var boolean
	 *
	 * @ORM\Column(name="active", type="boolean")
	 */
	private $active;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\Form", inversedBy="openEnrollments")
	 * @ORM\JoinColumn(name="form", referencedColumnName="formID")
	 */
	private $form;

	/**
	 * @ORM\ManyToOne(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollment", inversedBy="forms")
	 * @ORM\JoinColumn(name="openEnrollment", referencedColumnName="id")
	 */
	private $openEnrollment;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Get active
	 *
	 * @return boolean
	 */
	public function getActive() {

		return $this->active;
	}

	/**
	 * Set active
	 *
	 * @param boolean $active
	 *
	 * @return OpenEnrollmentHasForm
	 */
	public function setActive( $active ) {

		$this->active = $active;

		return $this;
	}

	/**
	 * Get form
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\Form
	 */
	public function getForm() {

		return $this->form;
	}

	/**
	 * Set form
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\Form $form
	 *
	 * @return OpenEnrollmentHasForm
	 */
	public function setForm( \IIAB\StudentTransferBundle\Entity\Form $form = null ) {

		$this->form = $form;

		return $this;
	}

	/**
	 * Get openEnrollment
	 *
	 * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
	 */
	public function getOpenEnrollment() {

		return $this->openEnrollment;
	}

	/**
	 * Set openEnrollment
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollment $openEnrollment
	 *
	 * @return OpenEnrollmentHasForm
	 */
	public function setOpenEnrollment( \IIAB\StudentTransferBundle\Entity\OpenEnrollment $openEnrollment = null ) {

		$this->openEnrollment = $openEnrollment;

		return $this;
	}

	public function __toString() {

		if( $this->getForm() != null )
			return $this->getForm()->__toString();
		else
			return '';

	}
}
