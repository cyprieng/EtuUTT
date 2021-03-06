<?php

/*
 * This file is part of the TgaDrupalBridgeBundle package.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Etu\Core\UserBundle\Security\Layer;

use Etu\Core\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Layer based on Symfony session to find if the current user is an organization, a student,
 * a UTT member, an external or an anonymous.
 *
 * @author Titouan Galopin <galopintitouan@gmail.com>
 */
class SessionLayer
{
	/**
	 * @var Session
	 */
	protected $session;

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @param Session|null $session
	 */
	public function __construct($session)
	{
		$this->session = $session;
		$this->user = $session->get('user_data');
	}

	/**
	 * Connected user or organization
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->isUser() || $this->isOrga();
	}

	/**
	 * Connected user
	 *
	 * @return bool
	 */
	public function isUser()
	{
		return is_int($this->session->get('user')) && $this->session->get('user') > 0;
	}

	/**
	 * @return bool
	 */
	public function isOrga()
	{
		return is_int($this->session->get('orga')) && $this->session->get('orga') > 0;
	}

	/**
	 * Connected organization
	 *
	 * @return bool
	 */
	public function isOrganization()
	{
		return $this->isOrga();
	}

	/**
	 * Connected user as student
	 *
	 * @return bool
	 */
	public function isStudent()
	{
		return $this->isUser() && $this->user->getIsStudent();
	}

	/**
	 * Connected user as UTT member
	 *
	 * @return bool
	 */
	public function isUttMember()
	{
		return $this->isUser() && ! $this->user->getIsStudent() && ! $this->user->getKeepActive();
	}

	/**
	 * Connected user as external
	 *
	 * @return bool
	 */
	public function isExternal()
	{
		return $this->isUser() && $this->user->getKeepActive();
	}
}
