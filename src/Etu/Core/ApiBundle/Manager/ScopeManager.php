<?php

namespace Etu\Core\ApiBundle\Manager;

use Doctrine\ORM\EntityManager;

class ScopeManager
{
    private $em;

    function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Creates a new scope
     *
     * @param string $scope
     *
     * @param string $description
     *
     * @return Scope
     */
    public function createScope($scope, $description = NULL)
    {
        $scopeObject = new \Etu\Core\ApiBundle\Entity\Scope();
        $scopeObject->setScope($scope);
        $scopeObject->setDescription($description);

        // Store Scope
        $this->em->persist($scopeObject);
        $this->em->flush();

        return $scopeObject;
    }
}
