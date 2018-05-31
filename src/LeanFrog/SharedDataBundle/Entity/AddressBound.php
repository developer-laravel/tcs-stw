<?php

namespace LeanFrog\SharedDataBundle\Entity;

/*********************
 *
 * NON DATABASE ENTITY
 *
 *********************
 */

/**
 * AddressBound
 *
 */
class AddressBound {

    /**
     * @var string
     *
     */
    private $ESBND;

    /**
     * @var string
     *
     */
    private $MSBND;

    /**
     * @var string
     *
     */
    private $HSBND;

    /**
     * @var string
     *
     * @ORM\Column(name="address_fu", type="string", length=255)
     */
    private $address_fu;


    /**
     * @return string
     */
    public function getESBND() {

        return $this->ESBND;
    }

    /**
     * @param string $ESBND
     */
    public function setESBND( $ESBND ) {

        $this->ESBND = $ESBND;
    }

    /**
     * @return string
     */
    public function getMSBND() {

        return $this->MSBND;
    }

    /**
     * @param string $MSBND
     */
    public function setMSBND( $MSBND ) {

        $this->MSBND = $MSBND;
    }

    /**
     * @return string
     */
    public function getHSBND() {

        return $this->HSBND;
    }

    /**
     * @param string $HSBND
     */
    public function setHSBND( $HSBND ) {

        $this->HSBND = $HSBND;
    }

    /**
     * @return string
     */
    public function getAddressFu() {

        return $this->address_fu;
    }

    /**
     * @param string $address_fu
     */
    public function setAddressFu( $address_fu ) {

        $this->address_fu = $address_fu;
    }


    /**
     * @return string
     */
    public function __toString() {

        return "{$this->address_fu} - {$this->ESBND} - {$this->MSBND} - {$this->HSBND}";
    }


}
