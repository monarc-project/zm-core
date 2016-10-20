<?php
namespace MonarcCore;

//use Zend\Mvc\ModuleRouteListener;
use MonarcCore\Model\Table\ScaleImpactTypeTable;
use MonarcCore\Service\InstanceService;
use MonarcCore\Service\ObjectService;
use Zend\Di\ServiceLocator;
use Zend\Mvc\MvcEvent;
use \Zend\Mvc\Controller\ControllerManager;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Router\RouteMatch;

class Module
{

    public function onBootstrap(MvcEvent $e)
    {
        if(!$e->getRequest() instanceof \Zend\Console\Request){
            $eventManager = $e->getApplication()->getEventManager();
            //$moduleRouteListener = new ModuleRouteListener();
            //$moduleRouteListener->attach($eventManager);

            $sm  = $e->getApplication()->getServiceManager();
            $serv = $sm->get('\MonarcCore\Service\AuthenticationService');
            $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this,'MCEventRoute'),-100);

            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
            $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);


            $sharedEventManager = $eventManager->getSharedManager();

            $sharedEventManager->attach('addcomponent', 'createinstance', function($e) use ($sm){
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                $instanceService = $sm->get('MonarcCore\Service\InstanceService');
                $result = $instanceService->instantiateObjectToAnr($params['anrId'], $params['dataInstance']);
                return $result;
            }, 100);

            $sharedEventManager->attach('instance', 'patch', function($e) use ($sm){
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                $instanceService = $sm->get('MonarcCore\Service\InstanceService');
                $result = $instanceService->patchInstance($params['anrId'], $params['instanceId'], $params['data']);
                return $result;
            }, 100);

            $sharedEventManager->attach('object', 'patch', function($e) use ($sm){
                $params = $e->getParams();
                /** @var ObjectService $objectService */
                $objectService = $sm->get('MonarcCore\Service\ObjectService');
                $result = $objectService->patch($params['objectId'], $params['data']);
                return $result;
            }, 100);
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            // ./vendor/bin/classmap_generator.php --library module/MonarcCore/src/MonarcCore/ -w -s -o module/MonarcCore/autoload_classmap.php
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            /*'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),*/
        );
    }

    public function getDefaultLanguage($sm)
    {
        $config = $sm->get('Config');

        $defaultLanguageIndex = $config['defaultLanguageIndex'];

        return $defaultLanguageIndex;
    }

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
            ),
            'factories' => array(
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'invokables' => array(
            ),
            'factories' => array(
                '\MonarcCore\Controller\Index'                          => '\MonarcCore\Controller\IndexControllerFactory',
                '\MonarcCore\Controller\Authentication'                 => '\MonarcCore\Controller\AuthenticationControllerFactory',
                '\MonarcCore\Controller\ApiAnr'                         => '\MonarcCore\Controller\ApiAnrControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstances'                => '\MonarcCore\Controller\ApiAnrInstancesControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesConsequences'    => '\MonarcCore\Controller\ApiAnrInstancesConsequencesControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesRisks'           => '\MonarcCore\Controller\ApiAnrInstancesRisksControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesRisksOp'         => '\MonarcCore\Controller\ApiAnrInstancesRisksOpControllerFactory',
                '\MonarcCore\Controller\ApiAnrLibrary'                  => '\MonarcCore\Controller\ApiAnrLibraryControllerFactory',
                '\MonarcCore\Controller\ApiAnrLibraryCategory'          => '\MonarcCore\Controller\ApiAnrLibraryCategoryControllerFactory',
                '\MonarcCore\Controller\ApiAnrObject'                   => '\MonarcCore\Controller\ApiAnrObjectControllerFactory',
                '\MonarcCore\Controller\ApiModels'                      => '\MonarcCore\Controller\ApiModelsControllerFactory',
                '\MonarcCore\Controller\ApiModelsDuplication'           => '\MonarcCore\Controller\ApiModelsDuplicationControllerFactory',
                '\MonarcCore\Controller\ApiScales'                      => '\MonarcCore\Controller\ApiScalesControllerFactory',
                '\MonarcCore\Controller\ApiScalesTypes'                 => '\MonarcCore\Controller\ApiScalesTypesControllerFactory',
                '\MonarcCore\Controller\ApiScalesComments'              => '\MonarcCore\Controller\ApiScalesCommentsControllerFactory',
            ),
        );
    }

    public function getInputFilterConfig(){
        return array(
            'invokables' => array(
                '\MonarcCore\Filter\Password' => '\MonarcCore\Filter\Password',
                '\MonarcCore\Filter\SpecAlnum' => '\MonarcCore\Filter\SpecAlnum',
            ),
        );
    }

    public function getValidatorConfig(){
        return array(
            'invokables' => array(
                '\MonarcCore\Validator\UniqueEmail' => '\MonarcCore\Validator\UniqueEmail',
                '\MonarcCore\Validator\UniqueDocModel' => '\MonarcCore\Validator\UniqueDocModel',
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

        $response = $e->getResponse();
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

    public function MCEventRoute($e){
        $sm  = $e->getApplication()->getServiceManager();
        $serv = $sm->get('\MonarcCore\Service\AuthenticationService');
        $match = $e->getRouteMatch();

        // No route match, this is a 404
        if (!$match instanceof RouteMatch) {
            return;
        }

        // Route is whitelisted
        $config = $e->getApplication()->getServiceManager()->get('Config');
        $permissions = $config['permissions'];
        $name = $match->getMatchedRouteName();
        if (in_array($name, $permissions)) {
            return;
        }

        $token = $e->getRequest()->getHeader('token');
        if(!empty($token)){
            if($serv->checkConnect(array('token'=>$token->getFieldValue()))){
                return;
            }
        }

        return $e->getResponse()->setStatusCode(401);
    }
}
