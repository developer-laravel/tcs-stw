<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Settings
 *
 * @ORM\Table(name="setting")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\SettingsRepository")
 */
class Settings {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="settingID", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	private $id;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="setting_name", type="string", length=255)
	 */
	private $settingName;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="setting_value_en", type="text", nullable=true)
	 */
	private $settingValue;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="setting_value_es", type="text", nullable=true)
	 */
	private $settingValue_es;

	/**
	 * @param string $settingValue_es
	 */
	public function setSettingValueEs( $settingValue_es ) {

		$this->settingValue_es = $settingValue_es;
	}

	/**
	 * @return string
	 */
	public function getSettingValueEs() {

		return $this->settingValue_es;
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
	 * Set settingName
	 *
	 * @param string $settingName
	 *
	 * @return Settings
	 */
	public function setSettingName( $settingName ) {

		$this->settingName = $settingName;

		return $this;
	}

	/**
	 * Get settingName
	 *
	 * @return string
	 */
	public function getSettingName() {

		return $this->settingName;
	}

	/**
	 * Set settingValue
	 *
	 * @param string $settingValue
	 *
	 * @return Settings
	 */
	public function setSettingValue( $settingValue ) {

		$this->settingValue = $settingValue;

		return $this;
	}

	/**
	 * Get settingValue
	 *
	 * @return string
	 */
	public function getSettingValue() {

		return $this->settingValue;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getId() ? '' . $this->getId() : 'Create';
	}
}
