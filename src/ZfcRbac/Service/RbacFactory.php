<?php

namespace ZfcRbac\Service;

use Interop\Container\ContainerInterface;
use RuntimeException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class RbacFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return object|Rbac
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Configuration');
        $config = $config['zfcrbac'];

        $rbac    = new Rbac($config);
        $options = $rbac->getOptions();

        foreach($options->getProviders() as $class => $config) {
            $rbac->addProvider($class::factory($container, $config));
        }

        foreach($options->getFirewalls() as $class => $config) {
            $rbac->addFirewall(new $class($config));
        }

        $identity = $rbac->getOptions()->getIdentityProvider();
        if (!$container->has($identity)) {
            throw new RuntimeException(sprintf(
                'An identity provider with the name "%s" does not exist',
                $identity
            ));
        }

        try {
            $rbac->setIdentity($container->get($identity));
        } catch (ServiceNotFoundException $e) {
            throw new RuntimeException(sprintf(
                'Unable to set your identity - are you sure the alias "%s" is correct?',
                $identity
            ));
        }

        return $rbac;
    }
}