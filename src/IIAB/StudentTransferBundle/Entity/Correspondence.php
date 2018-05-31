<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Correspondence
 *
 * @ORM\Table(name="correspondence")
 * @ORM\Entity
 */
class Correspondence {
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
     * @ORM\Column(name="template", type="text")
     */
    private $template;

    /**
     * @var string
     *
     * @ORM\Column(name="template_type", type="string", length=255)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="template_name", type="string", length=255)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="active", type="smallint")
     */
    private $active;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastUpdateDateTime", type="datetime")
     */
    private $lastUpdateDateTime;

    public function __construct() {

        $this->lastUpdateDateTime = new \DateTime();
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
     * Set template
     *
     * @param string $template
     * @return Correspondence
     */
    public function setTemplate($template)
    {
        $template = str_replace('&#39;', "'", $template);
        $template = str_replace('&quot;', '"', $template);

        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return string 
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set active
     *
     * @param integer $active
     * @return Correspondence
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return integer 
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set lastUpdateDateTime
     *
     * @param \DateTime $lastUpdateDateTime
     * @return Correspondence
     */
    public function setLastUpdateDateTime($lastUpdateDateTime)
    {
        $this->lastUpdateDateTime = $lastUpdateDateTime;

        return $this;
    }

    /**
     * Get lastUpdateDateTime
     *
     * @return \DateTime 
     */
    public function getLastUpdateDateTime()
    {
        return $this->lastUpdateDateTime;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Correspondence
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Correspondence
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
}
