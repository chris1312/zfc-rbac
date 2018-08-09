<?php

namespace ZfcRbac;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;
use Zend\Mvc\Application;
use Zend\ServiceManager\ServiceManager;
use ZfcRbac\Collector\RbacCollector;
use ZfcRbac\View\UnauthorizedStrategy;

class Module implements
    BootstrapListenerInterface,
    AutoloaderProviderInterface,
    ConfigProviderInterface,
    ServiceProviderInterface,
    ViewHelperProviderInterface
{
    /**
     * @param  EventInterface $e
     * @return array|void
     */
    public function onBootstrap(EventInterface $e)
    {
        /** @var Application $app */
        $app         = $e->getTarget();
        /** @var ServiceManager $sm */
        $sm          = $app->getServiceManager();
        $rbacService = $sm->get('ZfcRbac\Service\Rbac');
        /** @var UnauthorizedStrategy $strategy */
        $strategy    = $sm->get('ZfcRbac\View\UnauthorizedStrategy');

        if ($rbacService->getOptions()->getFirewallRoute()) {
            $app->getEventManager()->attach('route', array('ZfcRbac\Firewall\Listener\Route', 'onRoute'), -1000);
        }

        if ($rbacService->getOptions()->getFirewallController()) {
            $app->getEventManager()->attach('route', array('ZfcRbac\Firewall\Listener\Controller', 'onRoute'), -1000);
        }

        $strategy->attach($app->getEventManager());
    }

    /**
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * @return array|\Zend\ServiceManager\Config
     */
    public function getServiceConfig()
    {
        return array(
            'aliases' => array(
                'service.security' => 'ZfcRbac\Service\Rbac',
            ),
            'factories' => array(
                'ZfcRbac\Collector\RbacCollector' => function($sm) {
                    return new RbacCollector($sm->get('ZfcRbac\Service\Rbac'));
                },
                'ZfcRbac\View\UnauthorizedStrategy' => 'ZfcRbac\Service\UnauthorizedStrategyFactory',
                'ZfcRbac\Service\Rbac' => 'ZfcRbac\Service\RbacFactory'
            )
        );
    }

    /**
     * @return array|\Zend\ServiceManager\Config
     */
    public function getControllerPluginConfig()
    {
        return array(
            'invokables' => array(
                'isGranted' => 'ZfcRbac\Controller\Plugin\IsGranted',
                'hasRole' => 'ZfcRbac\Controller\Plugin\HasRole',
            )
        );
    }

    /**
     * @return array|\Zend\ServiceManager\Config
     */
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'isGranted' => function($sm) {
                    $sl = $sm->getServiceLocator();
                    return new View\Helper\IsGranted($sl->get('ZfcRbac\Service\Rbac'));
                },
                'hasRole' => function($sm) {
                    $sl = $sm->getServiceLocator();
                    return new View\Helper\HasRole($sl->get('ZfcRbac\Service\Rbac'));
                },
            ),
        );
    }

    /**
     * @return array|mixed|\Traversable
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }
}
