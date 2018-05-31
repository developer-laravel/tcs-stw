<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Race
 *
 * @ORM\Table(name="race")
 * @ORM\Entity
 */
class Race {
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
	 * @ORM\Column(name="race", type="string", length=255)
	 */
	private $race;

    /**
     * @var string
     *
     * @ORM\Column(name="short_name", type="string", length=255)
     */
	private $shortName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="report_as_other", type="boolean", options={"default":0})
     */
	private $reportAsOther;

    /**
     * @var boolean
     *
     * @ORM\Column(name="report_as_no_answer", type="boolean", options={"default":0})
     */
	private $reportAsNoAnswer;

	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

    /**
     * Set Id
     *
     * @param string $id
     *
     * @return Race
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

	/**
	 * Set race
	 *
	 * @param string $race
	 *
	 * @return Race
	 */
	public function setRace( $race ) {
		$this->race = $race;

		return $this;
	}

	/**
	 * Get race
	 *
	 * @return string
	 */
	public function getRace() {
		return $this->race;
	}

	public function __toString() {

		return $this->getRace();
	}

    /**
     * Set shortName
     *
     * @param string $shortName
     * @return Race
     */
    public function setShortName($shortName)
    {
        $this->shortName = $shortName;

        return $this;
    }

    /**
     * Get shortName
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * Set reportAsOther
     *
     * @param boolean $reportAsOther
     * @return Race
     */
    public function setReportAsOther($reportAsOther)
    {
        $this->reportAsOther = $reportAsOther;

        return $this;
    }

    /**
     * Get reportAsOther
     *
     * @return boolean
     */
    public function getReportAsOther()
    {
        return $this->reportAsOther;
    }

    /**
     * Set reportAsNoAnswer
     *
     * @param boolean $reportAsNoAnswer
     * @return Race
     */
    public function setReportAsNoAnswer($reportAsNoAnswer)
    {
        $this->reportAsNoAnswer = $reportAsNoAnswer;

        return $this;
    }

    /**
     * Get reportAsNoAnswer
     *
     * @return boolean
     */
    public function getReportAsNoAnswer()
    {
        return $this->reportAsNoAnswer;
    }
}
