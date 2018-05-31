<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SpecialEnrollment
 *
 * @ORM\Table(name="special_enrollment")
 * @ORM\Entity
 */
class SpecialEnrollment
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
     * @ORM\ManyToOne( targetEntity="OpenEnrollment" )
     * @ORM\JoinColumn( name="enrollmentPeriod", referencedColumnName="id" )
     */
    protected $enrollmentPeriod;

    /**
     * @ORM\ManyToOne( targetEntity="IIAB\StudentTransferBundle\Entity\Form" )
     * @ORM\JoinColumn( name="form", referencedColumnName="formID" )
     */
    protected $form;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="beginningDate", type="datetime")
     */
    private $beginningDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endingDate", type="datetime")
     */
    private $endingDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="mailDate", type="datetime", nullable=true)
     */
    private $mailDate;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

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
     * Set beginningDate
     *
     * @param \DateTime $beginningDate
     * @return SpecialEnrollment
     */
    public function setBeginningDate($beginningDate)
    {
        $this->beginningDate = $beginningDate;

        return $this;
    }

    /**
     * Get beginningDate
     *
     * @return \DateTime
     */
    public function getBeginningDate()
    {
        return $this->beginningDate;
    }

    /**
     * Set endingDate
     *
     * @param \DateTime $endingDate
     * @return SpecialEnrollment
     */
    public function setEndingDate($endingDate)
    {
        $this->endingDate = $endingDate;

        return $this;
    }

    /**
     * Get endingDate
     *
     * @return \DateTime
     */
    public function getEndingDate()
    {
        return $this->endingDate;
    }

    /**
     * Set mailDate
     *
     * @param \DateTime $mailDate
     * @return SpecialEnrollment
     */
    public function setMailDate($mailDate)
    {
        $this->mailDate = $mailDate;

        return $this;
    }

    /**
     * Get mailDate
     *
     * @return \DateTime
     */
    public function getMailDate()
    {
        return $this->mailDate;
    }

    /**
     * Set enrollmentPeriod
     *
     * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod
     * @return SpecialEnrollment
     */
    public function setEnrollmentPeriod(\IIAB\StudentTransferBundle\Entity\OpenEnrollment $enrollmentPeriod = null)
    {
        $this->enrollmentPeriod = $enrollmentPeriod;

        return $this;
    }

    /**
     * Get enrollmentPeriod
     *
     * @return \IIAB\StudentTransferBundle\Entity\OpenEnrollment
     */
    public function getEnrollmentPeriod()
    {
        return $this->enrollmentPeriod;
    }

    /**
     * Set form
     *
     * @param \IIAB\StudentTransferBundle\Entity\Form $form
     * @return SpecialEnrollment
     */
    public function setForm(\IIAB\StudentTransferBundle\Entity\Form $form = null)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get form
     *
     * @return \IIAB\StudentTransferBundle\Entity\Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return SpecialEnrollment
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
