<?php

namespace Etu\Core\UserBundle\Sync\Iterator\Element;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Etu\Core\UserBundle\Ldap\Model\User as LdapUser;
use Etu\Core\UserBundle\Entity\User as DbUser;
use Etu\Core\UserBundle\Model\BadgesManager;

/**
 * Element to update in database
 */
class ElementToUpdate
{
	/**
	 * @var DbUser
	 */
	protected $database;

	/**
	 * @var LdapUser
	 */
	protected $ldap;

	/**
	 * @var Registry
	 */
	protected $doctrine;

	/**
	 * @param Registry $doctrine
	 * @param array $elements
	 * @throws \RuntimeException
	 */
	public function __construct(Registry $doctrine, array $elements)
	{
		if (! $elements['ldap'] instanceof LdapUser) {
			if (is_object($elements['ldap'])) {
				$given = get_class($elements['ldap']);
			} else {
				$given = gettype($elements['ldap']);
			}

			throw new \RuntimeException(sprintf(
				'EtuUTT synchonizer can only update User objects (ldap: %s given)', $given
			));
		}

		if (! $elements['database'] instanceof DbUser) {
			if (is_object($elements['database'])) {
				$given = get_class($elements['database']);
			} else {
				$given = gettype($elements['database']);
			}

			throw new \RuntimeException(sprintf(
				'EtuUTT synchonizer can only update User objects (database: %s given)', $given
			));
		}

		$this->database = $elements['database'];
		$this->ldap = $elements['ldap'];
		$this->doctrine = $doctrine;
	}

	/**
	 * Update the element in the database
	 *
	 * @return DbUser
	 */
	public function update()
	{
		/*
		 * Update:
		 *      - formation
		 *      - niveau
		 *      - filiere
		 *      - uvs
		 *      - semesters history
		 */

		$persist = false;

		$user = $this->database;
		$history = $user->addCureentSemesterToHistory();

		$niveau = null;
		$branch = $this->ldap->getNiveau();

		preg_match('/^[^0-9]+/i', $this->ldap->getNiveau(), $match);

		if (isset($match[0])) {
			$branch = $match[0];
			$niveau = str_replace($branch, '', $this->ldap->getNiveau());
		}


		// Updates
		if (ucfirst(strtolower($this->ldap->getFormation())) != $this->database->getFormation()) {
			$persist = true;
			$user->setFormation(ucfirst(strtolower($this->ldap->getFormation())));
		}

		if ($niveau != $this->database->getNiveau()) {
			$persist = true;
			$user->setNiveau($niveau);
		}

		if ($branch != $this->database->getBranch()) {
			$persist = true;
			$user->setBranch($branch);
		}

		if ($this->ldap->getFiliere() != $this->database->getFiliere()) {
			$persist = true;
			$user->setFiliere($this->ldap->getFiliere());
		}

		if (implode('|', $this->ldap->getUvs()) != $this->database->getUvs()) {
			$persist = true;
			$user->setUvs(implode('|', $this->ldap->getUvs()));
		}

		/*
		 * Add badges
		 */
		if (substr($history['niveau'], 0, 2) == 'TC' && substr($user->getNiveau(), 0, 2) != 'TC') {
			BadgesManager::userAddBadge($user, 'tc_survivor');
			BadgesManager::userPersistBadges($user);
		}

		if ($persist) {
			$this->doctrine->getManager()->persist($user);
		}

		return $persist;
	}

	/**
	 * @return \Etu\Core\UserBundle\Ldap\Model\User
	 */
	public function getLdapUser()
	{
		return $this->ldap;
	}

	/**
	 * @return \Etu\Core\UserBundle\Entity\User
	 */
	public function getDatabaseUser()
	{
		return $this->database;
	}
}
