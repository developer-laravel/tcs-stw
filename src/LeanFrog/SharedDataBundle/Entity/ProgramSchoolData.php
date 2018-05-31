<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProgramSchoolData
 *
 * @ORM\Table(name="program_school_data")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\ProgramSchoolDataRepository")
 */
class ProgramSchoolData
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
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\ProgramSchool", inversedBy="additionalData")
     * @ORM\JoinColumn(name="programSchool", referencedColumnName="id")
     */
    protected $programSchool;

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
     * @return integer
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
     * @return ProgramSchoolData
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
     * @return ProgramSchoolData
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
     * Set programSchool
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $programSchool
     *
     * @return ProgramSchoolData
     */
    public function setProgramSchool(\LeanFrog\SharedDataBundle\Entity\ProgramSchool $programSchool = null)
    {
        $this->programSchool = $programSchool;

        return $this;
    }

    /**
     * Get programSchool
     *
     * @return \LeanFrog\SharedDataBundle\Entity\ProgramSchool
     */
    public function getProgramSchool()
    {
        return $this->programSchool;
    }
}
