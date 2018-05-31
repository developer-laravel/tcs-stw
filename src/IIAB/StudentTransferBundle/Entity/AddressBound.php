<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AddressBound
 *
 * @ORM\Table(name="addressbound")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\AddressBoundRepository")
 */
class AddressBound {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="address", type="integer", nullable=true)
	 */
	private $address;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="unit", type="string", length=255, nullable=true)
	 */
	private $unit;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="street_nam", type="string", length=255)
	 */
	private $street_nam;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="label_angl", type="string", length=255, nullable=true)
	 */
	private $label_angl;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="label_text", type="string", length=255, nullable=true)
	 */
	private $label_text;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="addr_type", type="string", length=255, nullable=true)
	 */
	private $addr_type;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="mail_count", type="string", length=255, nullable=true)
	 */
	private $mail_count;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="mail_city", type="string", length=255)
	 */
	private $mail_city;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="zipcode", type="integer")
	 */
	private $zipcode;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="city", type="string", length=255)
	 */
	private $city;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="address_fu", type="string", length=255)
	 */
	private $address_fu;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="ESBND", type="string", length=255)
	 */
	private $ESBND;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="MSBND", type="string", length=255)
	 */
	private $MSBND;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="HSBND", type="string", length=255)
	 */
	private $HSBND;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="ARSENAL", type="string", length=1, nullable=true)
	 */
	private $ARSENAL;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getAddress() {

		return $this->address;
	}

	/**
	 * @param int $address
	 */
	public function setAddress( $address ) {

		$this->address = $address;
	}

	/**
	 * @return string
	 */
	public function getUnit() {

		return $this->unit;
	}

	/**
	 * @param string $unit
	 */
	public function setUnit( $unit ) {

		$this->unit = $unit;
	}

	/**
	 * @return string
	 */
	public function getStreetNam() {

		return $this->street_nam;
	}

	/**
	 * @param string $street_nam
	 */
	public function setStreetNam( $street_nam ) {

		$this->street_nam = $street_nam;
	}

	/**
	 * @return string
	 */
	public function getLabelAngl() {

		return $this->label_angl;
	}

	/**
	 * @param string $label_angl
	 */
	public function setLabelAngl( $label_angl ) {

		$this->label_angl = $label_angl;
	}

	/**
	 * @return string
	 */
	public function getLabelText() {

		return $this->label_text;
	}

	/**
	 * @param string $label_text
	 */
	public function setLabelText( $label_text ) {

		$this->label_text = $label_text;
	}

	/**
	 * @return string
	 */
	public function getMailCity() {

		return $this->mail_city;
	}

	/**
	 * @param string $mail_city
	 */
	public function setMailCity( $mail_city ) {

		$this->mail_city = $mail_city;
	}

	/**
	 * @return int
	 */
	public function getZipcode() {

		return $this->zipcode;
	}

	/**
	 * @param int $zipcode
	 */
	public function setZipcode( $zipcode ) {

		$this->zipcode = $zipcode;
	}

	/**
	 * @return string
	 */
	public function getCity() {

		return $this->city;
	}

	/**
	 * @param string $city
	 */
	public function setCity( $city ) {

		$this->city = $city;
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
	public function getARSENAL() {

		return $this->ARSENAL;
	}

	/**
	 * @param string $ARSENAL
	 */
	public function setARSENAL( $ARSENAL ) {

		$this->ARSENAL = $ARSENAL;
	}

	/**
	 * @return string
	 */
	public function getAddrType() {

		return $this->addr_type;
	}

	/**
	 * @param string $addr_type
	 */
	public function setAddrType( $addr_type ) {

		$this->addr_type = $addr_type;
	}

	/**
	 * @return string
	 */
	public function getMailCount() {

		return $this->mail_count;
	}

	/**
	 * @param string $mail_count
	 */
	public function setMailCount( $mail_count ) {

		$this->mail_count = $mail_count;
	}


	/**
	 * @return string
	 */
	public function __toString() {

		return "{$this->address_fu} - {$this->ESBND} - {$this->MSBND} - {$this->HSBND}";
	}


}
