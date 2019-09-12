<?php

namespace Monarc\Core;

use Monarc\Core\Service\AuthenticationService;
use Monarc\Core\Service\InstanceService;
use Monarc\Core\Service\ObjectService;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Zend\Console\Request;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Zend\Router\RouteMatch;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        if (!$e->getRequest() instanceof Request) {
            $eventManager = $e->getApplication()->getEventManager();

            $sm = $e->getApplication()->getServiceManager();
            $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'MCEventRoute'), -100);

            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
            $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);

            $sharedEventManager = $eventManager->getSharedManager();

            $sharedEventManager->attach('addcomponent', 'createinstance', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                if ($sm->has('Monarc\FrontOffice\Service\AnrInstanceService')) {
                    $instanceService = $sm->get('\Monarc\FrontOffice\Service\AnrInstanceService');
                } else {
                    $instanceService = $sm->get(InstanceService::class);
                }
                $result = $instanceService->instantiateObjectToAnr($params['anrId'], $params['dataInstance']);
                return $result;
            }, 100);

            $sharedEventManager->attach('instance', 'patch', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                if ($sm->has('\Monarc\FrontOffice\Service\AnrInstanceService')) {
                    $instanceService = $sm->get('\Monarc\FrontOffice\Service\AnrInstanceService');
                } else {
                    $instanceService = $sm->get(InstanceService::class);
                }
                $result = $instanceService->patchInstance($params['anrId'], $params['instanceId'], $params['data'], [], true);
                return $result;
            }, 100);

            $sharedEventManager->attach('object', 'patch', function ($e) use ($sm) {
                $params = $e->getParams();
                /** @var ObjectService $objectService */
                if ($sm->has('\Monarc\FrontOffice\Service\AnrObjectService')) {
                    $objectService = $sm->get('\Monarc\FrontOffice\Service\AnrObjectService');
                } else {
                    $objectService = $sm->get(ObjectService::class);
                }
                $result = $objectService->patch($params['objectId'], $params['data']);
                return $result;
            }, 100);

            if (file_exists('./data/cache/upgrade')) {
                // Clear caches
                $appConf = file_exists('./config/application.config.php') ? include './config/application.config.php' : [];
                $cacheDir = isset($appConf['module_listener_options']['cache_dir']) ? $appConf['module_listener_options']['cache_dir'] : "";
                if (isset($appConf['module_listener_options']['config_cache_key']) &&
                    file_exists($cacheDir . "module-config-cache." . $appConf['module_listener_options']['config_cache_key'] . ".php")) {
                    unlink($cacheDir . "module-config-cache." . $appConf['module_listener_options']['config_cache_key'] . ".php");
                }
                if (isset($appConf['module_listener_options']['module_map_cache_key']) &&
                    file_exists($cacheDir . "module-classmap-cache." . $appConf['module_listener_options']['module_map_cache_key'] . ".php")) {
                    unlink($cacheDir . "module-classmap-cache." . $appConf['module_listener_options']['module_map_cache_key'] . ".php");
                }

                $output = new NullOutput();
                $inputMetadata = new ArrayInput(array(
                    'command' => 'orm:clear-cache:metadata',
                ));
                $sm->get('doctrine.cli')->get('orm:clear-cache:metadata')->run($inputMetadata, $output);
                $inputQuery = new ArrayInput(array(
                    'command' => 'orm:clear-cache:query',
                ));
                $sm->get('doctrine.cli')->get('orm:clear-cache:query')->run($inputQuery, $output);
                $inputResult = new ArrayInput(array(
                    'command' => 'orm:clear-cache:result',
                ));
                $sm->get('doctrine.cli')->get('orm:clear-cache:result')->run($inputResult, $output);

                // Rm cache doctrine
                unlink('./data/cache/upgrade');
            }
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

    public function getInputFilterConfig(){
        return array(
            'invokables' => array(
                '\Monarc\Core\Filter\Password' => '\Monarc\Core\Filter\Password',
                '\Monarc\Core\Filter\SpecAlnum' => '\Monarc\Core\Filter\SpecAlnum',
            ),
        );
    }

    public function getValidatorConfig(){
        return array(
            'invokables' => array(
                '\Monarc\Core\Validator\UniqueEmail' => '\Monarc\Core\Validator\UniqueEmail',
                '\Monarc\Core\Validator\UniqueDeliveryModel' => '\Monarc\Core\Validator\UniqueDeliveryModel',
            ),
        );
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
