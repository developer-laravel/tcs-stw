<?php

namespace LeanFrog\SharedDataBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProgramSchool
 *
 * @ORM\Table(name="program_school")
 * @ORM\Entity(repositoryClass="LeanFrog\SharedDataBundle\Repository\ProgramSchoolRepository")
 */
class ProgramSchool
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="gradeLevel", type="integer", nullable=true)
     */
    private $gradeLevel;

    /**
     * @ORM\ManyToOne(targetEntity="ProgramSchool", inversedBy="children")
     * @ORM\JoinColumn(name="parent", referencedColumnName="id")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="ProgramSchool", mappedBy="parent")
     */
    protected $children;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="LeanFrog\SharedDataBundle\Entity\ProgramSchoolData", mappedBy="programSchool", cascade={"all"})
     */
    private $additionalData;

    /**
     * @var string
     *
     * @ORM\Column(name="linkedEntity", type="string", length=255, nullable=true)
     */
    private $linkedEntity;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->additionalData = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set name
     *
     * @param string $name
     *
     * @return ProgramSchool
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set gradeLevel
     *
     * @param integer $gradeLevel
     *
     * @return ProgramSchool
     */
    public function setGradeLevel($gradeLevel)
    {
        $this->gradeLevel = $gradeLevel;

        return $this;
    }

    /**
     * Get gradeLevel
     *
     * @return integer
     */
    public function getGradeLevel()
    {
        return $this->gradeLevel;
    }

    /**
     * Get gradeLevelString
     *
     * @return integer
     */
    public function getGradeLevelString()
    {
        $non_numeric = [
            97 => 'PreK 97',
            98 => 'PreK 98',
            99 => 'PreK',
            0 => 'K',
        ];

        return ( isset( $non_numeric[$this->gradeLevel] ) )
            ? 'Grade '. $non_numeric[$this->gradeLevel]
            : 'Grade '. $this->gradeLevel;
    }

    /**
     * Set parent
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $parent
     *
     * @return ProgramSchool
     */
    public function setParent(\LeanFrog\SharedDataBundle\Entity\ProgramSchool $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \LeanFrog\SharedDataBundle\Entity\ProgramSchool
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $child
     *
     * @return ProgramSchool
     */
    public function addChild(\LeanFrog\SharedDataBundle\Entity\ProgramSchool $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchool $child
     */
    public function removeChild(\LeanFrog\SharedDataBundle\Entity\ProgramSchool $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add additionalDatum
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchoolData $additionalDatum
     *
     * @return ProgramSchool
     */
    public function addAdditionalDatum(\LeanFrog\SharedDataBundle\Entity\ProgramSchoolData $additionalDatum)
    {
        $this->additionalData[] = $additionalDatum;

        return $this;
    }

    /**
     * Remove additionalDatum
     *
     * @param \LeanFrog\SharedDataBundle\Entity\ProgramSchoolData $additionalDatum
     */
    public function removeAdditionalDatum(\LeanFrog\SharedDataBundle\Entity\ProgramSchoolData $additionalDatum)
    {
        $this->additionalData->removeElement($additionalDatum);
    }

    /**
     * Get additionalData
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * Set linkedEntity
     *
     * @param string $entity
     *
     * @return AcademicYear
     */
    public function setLinkedEntity($entity, $value = null)
    {
        if( $value != null ){
            $key = $entity;
            $entity = $this->getLinkedEntity();

            $entity[$key] = $value;
        }

        if( !is_array( $entity ) ){

            $test = json_decode( $entity );

            if (json_last_error() !== JSON_ERROR_NONE
                || !is_array( $test )
            ) {
                throw new Exception('Attempted to Set invalid value');
            }
        }

        $this->linkedEntity = $entity;
        return $this;
    }

    /**
     * Get linkedEntity
     *
     * @param string $key
     *
     * @return string
     */
    public function getLinkedEntity( $key = null )
    {
        $entities = json_decode( $this->linkedEntity, true );
        if (json_last_error() !== JSON_ERROR_NONE) {
            $entities = [];
        }

        return ( $key == null ) ? $entities : $entities[ $key ];
    }

    /**
     * Remove linkedEntity
     *
     * @param string $key
     *
     * @return string
     */
    public function removeLinkedEntity( $key )
    {
        $entities = json_decode( $this->linkedEntity, true );
        if (json_last_error() !== JSON_ERROR_NONE) {
            $entities = [];
        }

        unset( $entities[ $key ] );

        $this->setLinkedEntity( $entities );

        return $this;
    }

    public function __toString(){
        return $this->name .' '. $this->getGradeLevelString();
    }
}
