<?php

namespace Monarc\Core;

use Monarc\Core\Service\AuthenticationService;
use Monarc\Core\Service\InstanceService;
use Monarc\Core\Service\ObjectService;
use Monarc\FrontOffice\Service\AnrInstanceService;
use Monarc\FrontOffice\Service\AnrObjectService;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\Router\RouteMatch;
use Monarc\Core\Validator\UniqueEmail;
use Monarc\Core\Validator\UniqueDeliveryModel;
use Monarc\Core\Filter\SpecAlnum;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        if ($e->getRequest() instanceof Request) {
            $eventManager = $e->getApplication()->getEventManager();

            $sm = $e->getApplication()->getServiceManager();
            $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'MCEventRoute'), -100);

            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
            $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);

            $sharedEventManager = $eventManager->getSharedManager();

            $sharedEventManager->attach('addcomponent', 'createinstance', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                if ($sm->has(AnrInstanceService::class)) {
                    $instanceService = $sm->get(AnrInstanceService::class);
                } else {
                    $instanceService = $sm->get(InstanceService::class);
                }
                $result = $instanceService->instantiateObjectToAnr($params['anrId'], $params['dataInstance']);
                return $result;
            }, 100);

            $sharedEventManager->attach('instance', 'patch', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                if ($sm->has(AnrInstanceService::class)) {
                    $instanceService = $sm->get(AnrInstanceService::class);
                } else {
                    $instanceService = $sm->get(InstanceService::class);
                }
                $result = $instanceService->patchInstance($params['anrId'], $params['instanceId'], $params['data'], [], true);
                return $result;
            }, 100);

            $sharedEventManager->attach('object', 'patch', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var ObjectService $objectService */
                if ($sm->has(AnrObjectService::class)) {
                    $objectService = $sm->get(AnrObjectService::class);
                } else {
                    $objectService = $sm->get(ObjectService::class);
                }
                $result = $objectService->patch($params['objectId'], $params['data']);
                return $result;
            }, 100);
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getDefaultLanguage($sm)
    {
        $config = $sm->get('Config');

        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        return $defaultLanguageIndex;
    }

    public function getInputFilterConfig()
    {
        return [
            'invokables' => [
                SpecAlnum::class => SpecAlnum::class,
            ],
        ];
    }

    public function getValidatorConfig()
    {
        return [
            'invokables' => [
                UniqueEmail::class => UniqueEmail::class,
                UniqueDeliveryModel::class => UniqueDeliveryModel::class,
            ],
        ];
    }

    public function onDispatchError($e)
    {
        return $this->getJsonModelError($e);
    }

    public function onRenderError($e)
    {
        return $this->getJsonModelError($e);
    }

    public function getJsonModelError($e)
    {
        $error = $e->getError();
        if (!$error) {
            return;
        }

        $exception = $e->getParam('exception');
        $exceptionJson = array();
        if ($exception) {
            $exceptionJson = array(
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'stacktrace' => $exception->getTraceAsString()
            );
        }

        $errorJson = array(
            'message'   => 'An error occurred during execution; please try again later.',
            'error'     => $error,
            'exception' => $exceptionJson,
        );
        if ($error == 'error-router-no-match') {
            $errorJson['message'] = 'Resource not found.';
        }

        $model = new JsonModel(array('errors' => array($errorJson)));

        $e->setResult($model);

        return $model;
    }

    public function MCEventRoute($event){
        $serv = $event->getApplication()
            ->getServiceManager()
            ->get(AuthenticationService::class);
        $match = $event->getRouteMatch();

        // No route match, this is a 404
        if (!$match instanceof RouteMatch) {
            return;
        }

        // Route is whitelisted
        $config = $event->getApplication()->getServiceManager()->get('Config');
        $permissions = $config['permissions'];
        $name = $match->getMatchedRouteName();
        if (in_array($name, $permissions)) {
            return;
        }

        $token = $event->getRequest()->getHeader('token');
        if(!empty($token)){
            if($serv->checkConnect(array('token'=>$token->getFieldValue()))){
                return;
            }
        }

        return $event->getResponse()->setStatusCode(401);
    }
}
