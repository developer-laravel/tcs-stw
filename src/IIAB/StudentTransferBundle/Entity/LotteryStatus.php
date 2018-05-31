<?php

namespace IIAB\StudentTransferBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LotteryStatus
 *
 * @ORM\Table(name="lotterystatus")
 * @ORM\Entity(repositoryClass="IIAB\StudentTransferBundle\Entity\LotteryStatusRepository")
 */
class LotteryStatus {

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
	 * @ORM\Column(name="status", type="string", length=255)
	 */
	private $status;


	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {

		return $this->id;
	}

	/**
	 * Get status
	 *
	 * @return string
	 */
	public function getStatus() {

		return $this->status;
	}

	/**
	 * Set status
	 *
	 * @param string $status
	 *
	 * @return LotteryStatus
	 */
	public function setStatus( $status ) {

		$this->status = $status;

		return $this;
	}

	public function __toString() {
		return ucwords( strtolower( $this->getStatus() ) );
	}

}
