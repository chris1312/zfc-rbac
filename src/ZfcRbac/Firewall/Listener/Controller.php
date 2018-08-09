<?php

namespace ZfcRbac\Firewall\Listener;

use InvalidArgumentException;
use Zend\Mvc\MvcEvent;
use Zend\Http\Request as HttpRequest;

class Controller
{
    /**
     * @param MvcEvent $e
     */
    public static function onRoute(MvcEvent $e)
    {
        if (!$e->getRequest() instanceof HttpRequest) {
            return;
        }
        $app         = $e->getTarget();
        $rbacService = $app->getServiceManager()->get('ZfcRbac\Service\Rbac');
        $match       = $app->getMvcEvent()->getRouteMatch();
        $controller  = $match->getParam('controller');
        $action      = $match->getParam('action');
        $resource    = sprintf('%s:%s', $controller, $action);

        try {
            if ($rbacService->getFirewall('controller')->isGranted($resource)) {
                return;
            }
            $e->setError($rbacService::ERROR_CONTROLLER_UNAUTHORIZED);
                $e->setParam('identity', $rbacService->getIdentity());
                $e->setParam('controller', $controller);
                $e->setParam('action', $action);
        } catch (InvalidArgumentException $ex) {
            $e->setError($rbacService::ERROR_RUNTIME)
                ->setParam('message', $ex->getMessage());
        }
        $app->getEventManager()->trigger('dispatch.error', $e);
    }
}
