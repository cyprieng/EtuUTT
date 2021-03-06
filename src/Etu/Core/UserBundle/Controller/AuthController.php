<?php

namespace Etu\Core\UserBundle\Controller;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Etu\Core\UserBundle\Entity\Session;
use Etu\Core\UserBundle\Ldap\LdapManager;
use Etu\Core\UserBundle\Sync\Iterator\Element\ElementToImport;
use Etu\Core\CoreBundle\Framework\Definition\Controller;
use Etu\Core\UserBundle\Ldap\Model\User;
use Etu\Core\UserBundle\Ldap\Model\Organization;
use Etu\Module\BuckUTTBundle\Soap\SoapManager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class AuthController extends Controller
{
	/**
	 * Connect the user or the organization automatically if possible,
	 * ask for method to connect otherwise.
	 *
	 * @Route("/user", name="user_connect")
	 * @Template()
	 */
	public function connectAction()
	{
		if ($this->getUserLayer()->isConnected()) {
			return $this->redirect($this->generateUrl('homepage'));
		}

		if ($this->get('session')->has('etu.last_url')) {
			$this->get('session')->set('etu.login_target', $this->get('session')->get('etu.last_url'));
		} else {
			$this->get('session')->set('etu.login_target', $this->generateUrl('homepage'));
		}

		if ($this->getKernel()->getEnvironment() != 'test') {
			$this->initializeCAS();
			\phpCAS::setNoCasServerValidation();

			if (\phpCAS::isAuthenticated()) {
				// Try to connect user automatically
				$login = \phpCAS::getUser();

				$em = $this->getDoctrine()->getManager();

				$user = $em->getRepository('EtuUserBundle:User')->findOneBy(array('login' => $login));

				if ($user && $user->getIsBanned()) {
					$this->get('session')->getFlashBag()->set('message', array(
						'type' => 'error',
						'message' => 'Vous avez été banni d\'EtuUTT.'
					));

					return $this->redirect($this->generateUrl('homepage'));
				}

				// If the user can't be loaded from database, we try for an organization
				if (! $user) {
					$orga = $em->getRepository('EtuUserBundle:Organization')->findOneBy(array('login' => $login));

					if ($orga) {
						$user = $orga;
					}
				}

				// If the user can't be loaded even as organization, we try using LDAP
				if (! $user) {
					/** @var $ldap LdapManager */
					$ldap = $this->get('etu.user.ldap');

					$ldapUser = $ldap->getUser($login);

					// If we can't use a classic user, try with an organization
					if (! $ldapUser) {
						$ldapUser = $ldap->getOrga($login);
					}

					// We caught a user that is not in the database : we import it !
					if ($ldapUser instanceof User) {
						$import = new ElementToImport($this->getDoctrine(), $ldapUser);
						$user = $import->import(true);
					} elseif ($ldapUser instanceof Organization) {
						$this->get('session')->getFlashBag()->set('message', array(
							'type' => 'error',
							'message' => 'user.auth.connect.orga_exists_ldap'
						));

						return $this->redirect($this->generateUrl('homepage'));
					}
				}

				if ($user instanceof \Etu\Core\UserBundle\Entity\User) {
                    $this->createSession(Session::TYPE_USER, $user);

					// Remove BuckUTT cookie
					$this->get('session')->remove(SoapManager::cookie_name);

					$this->get('session')->getFlashBag()->set('message', array(
						'type' => 'success',
						'message' => 'user.auth.connect.confirm'
					));

					if (in_array($user->getLanguage(), $this->container->getParameter('etu.translation.languages'))) {
						$this->get('session')->set('_locale', $user->getLanguage());
					}

					if ($this->get('session')->has('etu.login_target')) {
						return $this->redirect($this->get('session')->get('etu.login_target'));
					} else {
						return $this->redirect($this->generateUrl('homepage'));
					}
				} elseif ($user instanceof \Etu\Core\UserBundle\Entity\Organization) {
                    $this->createSession(Session::TYPE_ORGA, $user);

					// Remove BuckUTT cookie
					$this->get('session')->remove(SoapManager::cookie_name);

					$this->get('session')->getFlashBag()->set('message', array(
						'type' => 'success',
						'message' => 'user.auth.connect.confirm'
					));

					if ($this->get('session')->has('etu.login_target')) {
						return $this->redirect($this->get('session')->get('etu.login_target'));
					} else {
						return $this->redirect($this->generateUrl('homepage'));
					}
				}
			}
		}

		// If we can't auto-connect, we ask for the method
		return array();
	}

	/**
	 * @Route("/user/cas", name="user_connect_cas")
	 */
	public function connectCasAction()
	{
		if ($this->getUserLayer()->isConnected()) {
			return $this->redirect($this->generateUrl('homepage'));
		}

		// Catch the CAS ticket to connect emails
		$ticket = $this->getRequest()->get('ticket', false);

		if (! empty($ticket) && is_string($ticket)) {
			$this->get('session')->set('ticket', $ticket);
		}

		// Otherwise, load phpCAS
		$this->initializeCAS();
		\phpCAS::setNoCasServerValidation();
		\phpCAS::forceAuthentication();

		// Try to connect user
		$login = \phpCAS::getUser();

		/** @var EntityManager $em */
		$em = $this->getDoctrine()->getManager();

		/** @var \Etu\Core\UserBundle\Entity\User $user */
		$user = $em->getRepository('EtuUserBundle:User')->findOneBy(array('login' => $login));

		if ($user && $user->getIsBanned()) {
			$this->get('session')->getFlashBag()->set('message', array(
				'type' => 'error',
				'message' => 'Vous avez été banni d\'EtuUTT.'
			));

			return $this->redirect($this->generateUrl('homepage'));
		}

		// If the user can't be loaded from database, we try for an organization
		if (! $user) {
			$orga = $em->getRepository('EtuUserBundle:Organization')->findOneBy(array('login' => $login));

			if ($orga) {
				$user = $orga;
			}
		}

		// If the user can't be loaded even as organization, we try using LDAP
		if (! $user) {
			/** @var $ldap LdapManager */
			$ldap = $this->get('etu.user.ldap');

			$ldapUser = $ldap->getUser($login);

			// If we can't use a classic user, try with an organization
			if (! $ldapUser) {
				$ldapUser = $ldap->getOrga($login);
			}

			// We caught a user that is not in the database : we import it !
			if ($ldapUser instanceof User) {
				$import = new ElementToImport($this->getDoctrine(), $ldapUser);
				$user = $import->import(true);
			} elseif ($ldapUser instanceof Organization) {
				$this->get('session')->set('orga', null);
				$this->get('session')->set('user', null);
				$this->get('session')->clear();

				if (ini_get('session.use_cookies')) {
					$params = session_get_cookie_params();
					setcookie(session_name(), '', time() - 42000,
						$params['path'], $params['domain'],
						$params['secure'], $params['httponly']
					);
				}

				$this->get('session')->getFlashBag()->set('message', array(
					'type' => 'success',
					'message' => 'user.auth.connect.orga_exists_ldap'
				));

				return $this->redirect($this->generateUrl('homepage'));
			} else {
				$this->get('session')->getFlashBag()->set('message', array(
					'type' => 'error',
					'message' => 'user.auth.connect.error'
				));

				return $this->redirect($this->generateUrl('user_connect'));
			}
		}


        // Create the session
		if ($user instanceof \Etu\Core\UserBundle\Entity\User) {
            $this->createSession(Session::TYPE_USER, $user);

			// Remove BuckUTT cookie
			$this->get('session')->remove(SoapManager::cookie_name);

			$this->get('session')->getFlashBag()->set('message', array(
				'type' => 'success',
				'message' => 'user.auth.connect.confirm'
			));

			if (in_array($user->getLanguage(), $this->container->getParameter('etu.translation.languages'))) {
				$this->get('session')->set('_locale', $user->getLanguage());
			}
		} elseif ($user instanceof \Etu\Core\UserBundle\Entity\Organization) {
            $this->createSession(Session::TYPE_ORGA, $user);

			// Remove BuckUTT cookie
			$this->get('session')->remove(SoapManager::cookie_name);

			$this->get('session')->getFlashBag()->set('message', array(
				'type' => 'success',
				'message' => 'user.auth.connect.confirm'
			));
		} else {
			$this->get('session')->getFlashBag()->set('message', array(
				'type' => 'success',
				'message' => 'user.auth.connect.error'
			));

			return $this->redirect($this->generateUrl('user_connect'));
		}

		if ($this->get('session')->has('etu.login_target')) {
			return $this->redirect($this->get('session')->get('etu.login_target'));
		} else {
			return $this->redirect($this->generateUrl('homepage'));
		}
	}

	/**
	 * @Route("/user/external", name="user_connect_external")
	 * @Template()
	 */
	public function connectExternalAction()
	{
		if ($this->getUserLayer()->isConnected()) {
			return $this->redirect($this->generateUrl('homepage'));
		}

		$em = $this->getDoctrine()->getManager();

		$user = new \Etu\Core\UserBundle\Entity\User();

		$form = $this->createFormBuilder($user)
			->add('login')
			->add('password', 'password')
			->getForm();

		$request = $this->getRequest();

		if ($request->getMethod() == 'POST' && $form->submit($request)->isValid()) {
			/** @var \Etu\Core\UserBundle\Entity\User $result */
			$result = $em->getRepository('EtuUserBundle:User')->findOneBy(array(
				'login' => $user->getLogin(),
				'password' => $this->get('etu.user.crypting')->encrypt($user->getPassword())
			));

			if ($result) {
                $this->createSession(Session::TYPE_USER, $result);

				$this->get('session')->getFlashBag()->set('message', array(
					'type' => 'success',
					'message' => 'user.auth.connect.confirm'
				));

				if (in_array($user->getLanguage(), $this->container->getParameter('etu.translation.languages'))) {
					$this->get('session')->set('_locale', $user->getLanguage());
				}

				if ($this->get('session')->has('etu.login_target')) {
					return $this->redirect($this->get('session')->get('etu.login_target'));
				} else {
					return $this->redirect($this->generateUrl('homepage'));
				}
			} else {
				$this->get('session')->getFlashBag()->set('message', array(
					'type' => 'error',
					'message' => 'user.auth.connect.error'
				));

				return $this->redirect($this->generateUrl('user_connect_external'));
			}
		}

		return array(
			'form' => $form->createView()
		);
	}

	/**
	 * @Route("/user/disconnect", name="user_disconnect")
	 */
	public function disconnectAction()
	{
		if (! $this->getUserLayer()->isConnected()) {
			return $this->redirect($this->generateUrl('homepage'));
		}

        if ($this->getUserLayer()->isConnected()) {
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();

            $query = $em->createQueryBuilder()
                ->delete()
                ->from('EtuUserBundle:Session', 's')
                ->where('s.entityType = :type')
                ->andWhere('s.entityId = :id')
                ->setParameter('id', $this->getUser()->getId());

            if ($this->getUserLayer()->isOrga()) {
                $query->setParameter('type', Session::TYPE_ORGA);
            } else {
                $query->setParameter('type', Session::TYPE_USER);
            }

            $query->getQuery()->execute();
        }

        setcookie(md5('etuutt-session-cookie-name'), '', time() - 10, '/');

		$this->get('session')->set('orga', null);
		$this->get('session')->set('user', null);
		$this->get('session')->clear();

		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params['path'], $params['domain'],
				$params['secure'], $params['httponly']
			);
		}

		$this->initializeCAS();
		\phpCAS::setNoCasServerValidation();
		\phpCAS::logoutWithRedirectService('http://'.$this->container->getParameter('etu.domain'));

		return $this->redirect($this->generateUrl('homepage'));
	}

	/**
	 * Initialize the CAS connection
	 */
	private function initializeCAS()
	{
		\phpCAS::client(
			$this->container->getParameter('etu.cas.version'),
			$this->container->getParameter('etu.cas.host'),
			$this->container->getParameter('etu.cas.port'),
			$this->container->getParameter('etu.cas.path'),
			$this->container->getParameter('etu.cas.change_session_id')
		);
	}

    /**
     * @param $type
     * @param User|Organization $user
     */
    private function createSession($type, $user)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $session = new Session($type, $user->getId());
        $session->createName($_SERVER);

        $em->persist($session);
        $em->flush();

        setcookie(md5('etuutt-session-cookie-name'), $session->getToken(), $session->getExpireAt()->format('U'), '/');
    }
}
