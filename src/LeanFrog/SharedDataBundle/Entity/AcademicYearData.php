<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcademicYearData
 *
 * @ORM\Table(name="academic_year_data")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\AcademicYearDataRepository")
 */
class AcademicYearData
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
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\AcademicYear", inversedBy="additionalData")
     * @ORM\JoinColumn(name="academicYear", referencedColumnName="id")
     */
    protected $academicYear;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_key", type="string", length=255, nullable=true)
     */
    private $metaKey;

    /**
     * @var string
     *
     * @ORM\Column(name="meta_value", type="text", nullable=true)
     */
    private $metaValue;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set metaKey
     *
     * @param string $metaKey
     *
     * @return AcademicYearData
     */
    public function setMetaKey($metaKey)
    {
        $this->metaKey = $metaKey;

        return $this;
    }

    /**
     * Get metaKey
     *
     * @return string
     */
    public function getMetaKey()
    {
        return $this->metaKey;
    }

    /**
     * Set metaValue
     *
     * @param string $metaValue
     *
     * @return AcademicYearData
     */
    public function setMetaValue($metaValue)
    {
        $this->metaValue = $metaValue;

        return $this;
    }

    /**
     * Get metaValue
     *
     * @return string
     */
    public function getMetaValue()
    {
        return $this->metaValue;
    }

    /**
     * Set academicYear
     *
     * @param \LeanFrog\SharedDataBundle\Entity\AcademicYear $academicYear
     *
     * @return AcademicYearData
     */
    public function setAcademicYear(\LeanFrog\SharedDataBundle\Entity\AcademicYear $academicYear = null)
    {
        $this->academicYear = $academicYear;

        return $this;
    }

    /**
     * Get academicYear
     *
     * @return \LeanFrog\SharedDataBundle\Entity\AcademicYear
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }
}

