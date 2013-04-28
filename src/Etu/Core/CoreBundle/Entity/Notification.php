<?php

namespace Etu\Core\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Etu\Core\UserBundle\Entity\User;

/**
 * @ORM\Table(name="etu_notifications")
 * @ORM\Entity
 */
class Notification
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
	 * @var integer
	 *
	 * @ORM\Column(name="authorId", type="integer")
	 */
	protected $authorId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="entityType", type="string", length=50)
	 */
	protected $entityType;

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="entityId", type="integer")
	 */
	protected $entityId;

	/**
	 * Template helper: class loaded to display the notification
	 *
	 * @var string
	 *
	 * @ORM\Column(name="helper", type="string", length=100)
	 */
	protected $helper;

	/**
	 * List of entities in the notification (given to the
	 *
	 * @var array
	 *
	 * @ORM\Column(name="entities", type="array")
	 */
	protected $entities;

	/**
	 * Source module
	 *
	 * @var string
	 *
	 * @ORM\Column(name="module", type="string", length=100)
	 */
	protected $module;

	/**
	 * Create or last update date
	 *
	 * @var \DateTime
	 *
	 * @ORM\Column(name="date", type="datetime")
	 */
	protected $date;

	/**
	 * Is a super-notification ?
	 *
	 * @var boolean
	 *
	 * @ORM\Column(name="isSuper", type="boolean")
	 */
	protected $isSuper;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="expiration", type="datetime")
	 */
	protected $expiration;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->date = new \DateTime();
		$this->expiration = new \DateTime();
		$this->isSuper = false;
		$this->authorId = 0;
	}

	/**
	 * @param int $authorId
	 * @return Notification
	 */
	public function setAuthorId($authorId)
	{
		$this->authorId = $authorId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAuthorId()
	{
		return $this->authorId;
	}

	/**
	 * @param \DateTime $date
	 * @return Notification
	 */
	public function setDate($date)
	{
		$this->date = $date;

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
	 * @param \DateTime $lastVisitHome
	 * @return bool
	 */
	public function isNew(\DateTime $lastVisitHome)
	{
		return $lastVisitHome < $this->date;
	}

	/**
	 * @param array $entities
	 * @return Notification
	 */
	public function setEntities(array $entities)
	{
		$this->entities = $entities;

		return $this;
	}

	/**
	 * @param object $entity
	 * @return Notification
	 */
	public function addEntity($entity)
	{
		$this->entities[] = $entity;

		return $this;
	}

	/**
	 * @param object $entity
	 * @return Notification
	 */
	public function removeEntity($entity)
	{
		if ($key = array_search($entity, $this->entities)) {
			unset($this->entities[$key]);
		}

		return $this;
	}

	/**
	 * @return integer
	 */
	public function countEntities()
	{
		return count($this->entities);
	}

	/**
	 * @return object
	 */
	public function getFirstEntity()
	{
		return $this->getEntity(1);
	}

	/**
	 * @param integer $number
	 * @return object
	 */
	public function getEntity($number)
	{
		return (isset($this->entities[$number - 1])) ? $this->entities[$number - 1] : false;
	}

	/**
	 * @return array
	 */
	public function getEntities()
	{
		return $this->entities;
	}

	/**
	 * @param int $entityId
	 * @return Notification
	 */
	public function setEntityId($entityId)
	{
		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * @param string $entityType
	 * @return Notification
	 */
	public function setEntityType($entityType)
	{
		$this->entityType = $entityType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->entityType;
	}

	/**
	 * @param \DateTime $expiration
	 * @return Notification
	 */
	public function setExpiration($expiration)
	{
		$this->expiration = $expiration;

		return $this;
	}

	/**
	 * @return \DateTime
	 */
	public function getExpiration()
	{
		return $this->expiration;
	}

	/**
	 * @param string $helper
	 * @return Notification
	 */
	public function setHelper($helper)
	{
		$this->helper = $helper;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHelper()
	{
		return $this->helper;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param boolean $isSuper
	 * @return Notification
	 */
	public function setIsSuper($isSuper)
	{
		$this->isSuper = $isSuper;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getIsSuper()
	{
		return $this->isSuper;
	}

	/**
	 * @param string $module
	 * @return Notification
	 */
	public function setModule($module)
	{
		$this->module = $module;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getModule()
	{
		return $this->module;
	}
}

