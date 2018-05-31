<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpenEnrollment
 *
 * @ORM\Table(name="openenrollment")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\OpenEnrollmentRepository")
 */
class OpenEnrollment {

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
	 * @ORM\Column(name="year", type="string", length=255)
	 */
	private $year;

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
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $afterLotteryBeginningDate;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="datetime", nullable=true)
	 *
	 */
	private $afterLotteryEndingDate;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="confirmationStyle", type="string", length=255)
	 */
	private $confirmationStyle;

	/**
	 * @ORM\OneToMany(targetEntity="IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm", mappedBy="openEnrollment", cascade={"persist"})
	 */
	private $forms;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="mailDate", type="datetime", nullable=true)
	 */
	private $mailDate;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="targetAcademicYear", type="string", nullable=true)
	 */
	private $targetAcademicYear;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="targetTransferYear", type="string", nullable=true)
	 */
	private $targetTransferYear;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(type="date", nullable=true)
	 */
	private $waitListExpireDate;


	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $m2mPDFInfo;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $m2mPDFInfoES;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $m2mPDFFAQs;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $m2mPDFFAQsES;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $personnelPDF;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $personnelPDFES;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $infoPDF;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $infoPDFES;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $spedPDFInfo;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $spedPDFInfoES;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $schoolChoicePDFInfo;

	/**
	 * @var string
	 * @ORM\Column(type="string", nullable=true)
	 */
	private $schoolChoicePDFInfoES;

	protected $fileName;

	protected $file;

	/**
	 * Kindergarten Birthday Date Cut Off
	 *
	 * @var \DateTime
	 *
	 * @ORM\Column(name="kindergartenDateCutOff", type="date", nullable=true)
	 */
	private $kindergartenDateCutOff;

	/**
	 * First Grade Birthday Date Cut Off
	 *
	 * @var \DateTime
	 *
	 * @ORM\Column(name="firstGradeDateCutOff", type="date", nullable=true)
	 */
	private $firstGradeDateCutOff;


	/**
	 * Constructor
	 */
	public function __construct() {

		$this->forms = new \Doctrine\Common\Collections\ArrayCollection();
	}

	/**
	 * @return string
	 */
	public function getConfirmationStyle() {

		return $this->confirmationStyle;
	}

	/**
	 * @param string $confirmationStyle
	 */
	public function setConfirmationStyle( $confirmationStyle ) {

		$this->confirmationStyle = $confirmationStyle;
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
	 * Get beginningDate
	 *
	 * @return \DateTime
	 */
	public function getBeginningDate() {

		return $this->beginningDate;
	}

	/**
	 * Set beginningDate
	 *
	 * @param \DateTime $beginningDate
	 *
	 * @return OpenEnrollment
	 */
	public function setBeginningDate( $beginningDate ) {

		$this->beginningDate = $beginningDate;

		return $this;
	}

	/**
	 * Get endingDate
	 *
	 * @return \DateTime
	 */
	public function getEndingDate() {

		return $this->endingDate;
	}

	/**
	 * Set endingDate
	 *
	 * @param \DateTime $endingDate
	 *
	 * @return OpenEnrollment
	 */
	public function setEndingDate( $endingDate ) {

		$this->endingDate = $endingDate;

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {

		return $this->getYear() ?: 'Create';
	}

	/**
	 * Get year
	 *
	 * @return string
	 */
	public function getYear() {

		return $this->year;
	}

	/**
	 * Set year
	 *
	 * @param string $year
	 *
	 * @return OpenEnrollment
	 */
	public function setYear( $year ) {

		$this->year = $year;

		return $this;
	}

	/**
	 * Add forms
	 *
	 * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $forms
	 *
	 * @return OpenEnrollment
	 */
	public function addForm( \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $forms ) {

		$this->forms[] = $forms;
		$forms->setOpenEnrollment( $this );

        return $this;
    }

    /**
     * Remove forms
     *
     * @param \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $forms
     */
    public function removeForm(\IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $forms)
    {
        $this->forms->removeElement($forms);
    }

    /**
     * Get forms
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getForms()
    {
        return $this->forms;
    }

	/**
	 * @return \DateTime
	 */
	public function getMailDate()
	{
		return $this->mailDate;
	}

	/**
	 * Get the mail date
	 *
	 * @param $maildate
	 */
	public function setMailDate( $maildate )
	{
		$this->mailDate = $maildate;
	}

	/**
	 * Get the target Academic Year for denied letters/emails.
	 *
	 * @return string
	 */
	public function getTargetAcademicYear() {
		return $this->targetAcademicYear;
	}

	/**
	 * Set the target Academic Year for denied letters/emails.
	 *
	 * @param $targetAcademicYear
	 */
	public function setTargetAcademicYear( $targetAcademicYear ) {
		$this->targetAcademicYear = $targetAcademicYear;
	}

	/**
	 * Get the target Transfer Year for denied letters/emails.
	 *
	 * @return string
	 */
	public function getTargetTransferYear() {
		return $this->targetTransferYear;
	}

	/**
	 * Set the target Transfer Year for denied letters/emails.
	 *
	 * @param $targetTransferYear
	 *
	 * @return OpenEnrollment
	 */
	public function setTargetTransferYear( $targetTransferYear ) {
		$this->targetTransferYear = $targetTransferYear;
		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getAfterLotteryBeginningDate() {

		return $this->afterLotteryBeginningDate;
	}

	/**
	 * @param \DateTime $afterLotteryBeginningDate
	 */
	public function setAfterLotteryBeginningDate( $afterLotteryBeginningDate ) {

		$this->afterLotteryBeginningDate = $afterLotteryBeginningDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getAfterLotteryEndingDate() {

		return $this->afterLotteryEndingDate;
	}

	/**
	 * @param \DateTime $afterLotteryEndingDate
	 */
	public function setAfterLotteryEndingDate( $afterLotteryEndingDate ) {

		$this->afterLotteryEndingDate = $afterLotteryEndingDate;
	}

	/**
	 * @return \DateTime
	 */
	public function getWaitListExpireDate() {

		return $this->waitListExpireDate;
	}

	/**
	 * @param \DateTime $waitListExpireDate
	 */
	public function setWaitListExpireDate( $waitListExpireDate = null ) {

		$this->waitListExpireDate = $waitListExpireDate;
	}


	/**
	 * Is the form available within the OpenEnrollment
	 *
	 * @param int $formID
	 *
	 * @return bool
	 */
	public function isFormAvailable( $formID = 0 ) {

		$forms = $this->getForms();

		$formFoundAndActive = false;

		/** @var \IIAB\StudentTransferBundle\Entity\OpenEnrollmentHasForm $enrollmentHasForm */
		foreach( $forms as $enrollmentHasForm ) {
			$form = $enrollmentHasForm->getForm();
			if( $form->getId() == $formID && $enrollmentHasForm->getActive() ) {
				$formFoundAndActive = true;
				break;
			}
		}
		return $formFoundAndActive;
	}

	/**
	 * @return string
	 */
	public function getM2mPDFInfo() {
		return $this->m2mPDFInfo;
	}

	/**
	 * @param string $m2mPDFInfo
	 */
	public function setM2mPDFInfo($m2mPDFInfo) {
		$this->m2mPDFInfo = $m2mPDFInfo;
	}

	/**
	 * @return string
	 */
	public function getM2mPDFInfoES() {
		return $this->m2mPDFInfoES;
	}

	/**
	 * @param string $m2mPDFInfoES
	 */
	public function setM2mPDFInfoES($m2mPDFInfoES) {
		$this->m2mPDFInfoES = $m2mPDFInfoES;
	}

	/**
	 * @return string
	 */
	public function getM2mPDFFAQs() {
		return $this->m2mPDFFAQs;
	}

	/**
	 * @param string $m2mPDFFAQs
	 */
	public function setM2mPDFFAQs($m2mPDFFAQs) {
		$this->m2mPDFFAQs = $m2mPDFFAQs;
	}

	/**
	 * @return string
	 */
	public function getM2mPDFFAQsES() {
		return $this->m2mPDFFAQsES;
	}

	/**
	 * @param string $m2mPDFFAQsES
	 */
	public function setM2mPDFFAQsES($m2mPDFFAQsES) {
		$this->m2mPDFFAQsES = $m2mPDFFAQsES;
	}

	/**
	 * @return string
	 */
	public function getPersonnelPDF() {
		return $this->personnelPDF;
	}

	/**
	 * @param string $personnelPDF
	 */
	public function setPersonnelPDF($personnelPDF) {
		$this->personnelPDF = $personnelPDF;
	}

	/**
	 * @return string
	 */
	public function getPersonnelPDFES() {
		return $this->personnelPDFES;
	}

	/**
	 * @param string $personnelPDFES
	 */
	public function setPersonnelPDFES($personnelPDFES) {
		$this->personnelPDFES = $personnelPDFES;
	}

	/**
	 * @return string
	 */
	public function getInfoPDF() {
		return $this->infoPDF;
	}

	/**
	 * @param string $infoPDF
	 */
	public function setInfoPDF($infoPDF) {
		$this->infoPDF = $infoPDF;
	}

	/**
	 * @return string
	 */
	public function getInfoPDFES() {
		return $this->infoPDFES;
	}

	/**
	 * @param string $infoPDFES
	 */
	public function setInfoPDFES($infoPDFES) {
		$this->infoPDFES = $infoPDFES;
	}

	/**
	 * @return string
	 */
	public function getSpedPDFInfo() {
		return $this->spedPDFInfo;
	}

	/**
	 * @param string $spedPDFInfo
	 */
	public function setSpedPDFInfo($spedPDFInfo) {
		$this->spedPDFInfo = $spedPDFInfo;
	}

	/**
	 * @return string
	 */
	public function getSpedPDFInfoES() {
		return $this->spedPDFInfoES;
	}

	/**
	 * @param string $spedPDFInfoES
	 */
	public function setSpedPDFInfoES($spedPDFInfoES) {
		$this->spedPDFInfoES = $spedPDFInfoES;
	}

	/**
	 * @return \DateTime
	 */
	public function getKindergartenDateCutOff() {

		return $this->kindergartenDateCutOff;
	}

	/**
	 * @param \DateTime $kindergartenDateCutOff
	 */
	public function setKindergartenDateCutOff( $kindergartenDateCutOff ) {

		$this->kindergartenDateCutOff = $kindergartenDateCutOff;
	}

	/**
	 * @return \DateTime
	 */
	public function getFirstGradeDateCutOff() {

		return $this->firstGradeDateCutOff;
	}

	/**
	 * @param \DateTime $firstGradeDateCutOff
	 */
	public function setFirstGradeDateCutOff( $firstGradeDateCutOff ) {

		$this->firstGradeDateCutOff = $firstGradeDateCutOff;
	}


	/**
	 * @return mixed
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * @param mixed $file
	 */
	public function setFile($file) {
		$this->file = $file;
	}

	/**
	 * @return mixed
	 */
	public function getFileName() {
		return $this->fileName;
	}

	/**
	 * @param mixed $fileName
	 */
	public function setFileName($fileName) {
		$this->fileName = $fileName;
	}

	public function getAbsolutePath() {
		return null === $this->fileName ? null : $this->getUploadRootDir() . '/' . $this->fileName;
	}

	public function getWebPath() {
		return null === $this->fileName ? null : $this->getUploadDir() . '/' . $this->fileName;
	}

	protected function getUploadRootDir($basepath) {
		// the absolute directory path where uploaded documents should be saved
		return $basepath . $this->getUploadDir();
	}

	protected function getUploadDir() {
		// get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image in the view.
		return 'uploads/pdfs';
	}

	public function upload($basepath) {
		// the file property can be empty if the field is not required
		if (null === $this->file) {
			return;
		}

		if (null === $basepath) {
			return;
		}

		// we use the original file name here but you should
		// sanitize it at least to avoid any security issues

		// move takes the target directory and then the target filename to move to
		$this->file->move($this->getUploadRootDir($basepath), $this->file->getClientOriginalName());

		// set the path property to the filename where you'ved saved the file
		$this->setFileName($this->file->getClientOriginalName());

		// clean up the file property as you won't need it anymore
		$this->file = null;
	}

    /**
     * Set schoolChoicePDFInfo
     *
     * @param string $schoolChoicePDFInfo
     *
     * @return OpenEnrollment
     */
    public function setSchoolChoicePDFInfo($schoolChoicePDFInfo)
    {
        $this->schoolChoicePDFInfo = $schoolChoicePDFInfo;

        return $this;
    }

    /**
     * Get schoolChoicePDFInfo
     *
     * @return string
     */
    public function getSchoolChoicePDFInfo()
    {
        return $this->schoolChoicePDFInfo;
    }

    /**
     * Set schoolChoicePDFInfoES
     *
     * @param string $schoolChoicePDFInfoES
     *
     * @return OpenEnrollment
     */
    public function setSchoolChoicePDFInfoES($schoolChoicePDFInfoES)
    {
        $this->schoolChoicePDFInfoES = $schoolChoicePDFInfoES;

        return $this;
    }

    /**
     * Get schoolChoicePDFInfoES
     *
     * @return string
     */
    public function getSchoolChoicePDFInfoES()
    {
        return $this->schoolChoicePDFInfoES;
    }
}
