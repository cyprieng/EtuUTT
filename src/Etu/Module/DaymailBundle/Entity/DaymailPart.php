<?php

namespace Etu\Module\DaymailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Etu\Core\UserBundle\Entity\Organization;

/**
 * Daymail part
 *
 * @ORM\Table(name="etu_daymail_parts")
 * @ORM\Entity
 */
class DaymailPart
{
	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @var Organization $orga
	 *
	 * @ORM\ManyToOne(targetEntity="\Etu\Core\UserBundle\Entity\Organization")
	 * @ORM\JoinColumn()
	 */
	protected $orga;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="title", type="string", length=100)
	 */
	protected $title;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="body", type="text")
	 */
	protected $body;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="date", type="date")
	 */
	protected $date;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="day", type="string", length=10)
	 */
	protected $day;


	/**
	 * @param Organization $orga
	 * @param \DateTime    $date
	 */
	public function __construct(Organization $orga, \DateTime $date)
	{
		$this->orga = $orga;
		$this->setDate($date);
	}

	/**
	 * Create the available list of days which can be used as publish day
	 *
	 * @return array
	 */
	public static function createFutureAvailableDays()
	{
		$available = array();

		for ($i = 1; $i <= 7; $i++) {
			$day = new \DateTime();
			$day->add(new \DateInterval('P'.$i.'D'));

			$available[$day->format('d-m-Y')] = $day;
		}

		return $available;
	}

	/**
	 * @param string $body
	 * @return DaymailPart
	 */
	public function setBody($body)
	{
		$this->body = $body;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param \DateTime $date
	 * @return DaymailPart
	 */
	public function setDate($date)
	{
		$this->date = $date;
		$this->day = $date->format('d-m-Y');

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param int $id
	 * @return DaymailPart
	 */
	public function setId($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param \Etu\Core\UserBundle\Entity\Organization $orga
	 * @return DaymailPart
	 */
	public function setOrga($orga)
	{
		$this->orga = $orga;

		return $this;
	}

	/**
	 * @return \Etu\Core\UserBundle\Entity\Organization
	 */
	public function getOrga()
	{
		return $this->orga;
	}

	/**
	 * @param string $title
	 * @return DaymailPart
	 */
	public function setTitle($title)
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}
}
