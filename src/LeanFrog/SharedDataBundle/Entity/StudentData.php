<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StudentData
 *
 * @ORM\Table(name="student_data")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\StudentDataRepository")
 */
class StudentData
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
     * @ORM\ManyToOne(targetEntity="LeanFrog\SharedDataBundle\Entity\Student", inversedBy="additionalData")
     * @ORM\JoinColumn(name="student", referencedColumnName="id")
     */
    protected $student;

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
     * @return StudentData
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
     * @return StudentData
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
     * Set student
     *
     * @param \LeanFrog\SharedDataBundle\Entity\Student $student
     *
     * @return StudentData
     */
    public function setStudent(\LeanFrog\SharedDataBundle\Entity\Student $student = null)
    {
        $this->student = $student;

        return $this;
    }

    /**
     * Get student
     *
     * @return \LeanFrog\SharedDataBundle\Entity\Student
     */
    public function getStudent()
    {
        return $this->student;
    }
}
