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
            $eventManager->attach(MvcEvent::EVENT_ROUTE, function($e) use ($serv) {
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
            },-100);

            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
            $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);


            $sharedEventManager = $eventManager->getSharedManager();

            $sharedEventManager->attach('addcomponent', 'createinstance', function($e) use($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                $instanceService = $sm->get('MonarcCore\Service\InstanceService');
                $result = $instanceService->instantiateObjectToAnr($params['anrId'], $params['data']);
                return $result;
            }, 100);

            $sharedEventManager->attach('instance', 'patch', function($e) use($sm) {
                $params = $e->getParams();
                /** @var InstanceService $instanceService */
                $instanceService = $sm->get('MonarcCore\Service\InstanceService');
                $result = $instanceService->patchInstance($params['anrId'], $params['instanceId'], $params['data']);
                return $result;
            }, 100);

            $sharedEventManager->attach('object', 'patch', function($e) use($sm) {
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
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
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
                '\MonarcCore\Model\Entity\Model' => '\MonarcCore\Model\Entity\Model',
            ),
            'factories' => array(
                '\MonarcCore\Model\Db' => function($sm){
                    return new Model\Db($sm->get('doctrine.entitymanager.orm_default'));
                },
                '\MonarcCli\Model\Db' => function($sm){
                    try{
                        $sm->get('doctrine.entitymanager.orm_cli')->getConnection()->connect();
                        return new Model\Db($sm->get('doctrine.entitymanager.orm_cli'));
                    }catch(\Exception $e){
                        return new Model\Db($sm->get('doctrine.entitymanager.orm_default'));
                    }
                },
                '\MonarcCore\Model\Entity\DocModel' => function($sm){
                    $dm = new Model\Entity\DocModel();
                    $dm->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    return $dm;
                },
                '\MonarcCore\Model\Entity\Asset' => function($sm){
                    $entity = new Model\Entity\Asset();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\City' => function($sm){
                    $entity = new Model\Entity\City();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Country' => function($sm){
                    $entity = new Model\Entity\Country();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Measure' => function($sm){
                    $entity = new Model\Entity\Measure();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Model' => function($sm){
                    $entity = new Model\Entity\Model();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Object' => function($sm){
                    $entity = new Model\Entity\Object();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Instance' => function($sm){
                    $entity = new Model\Entity\Instance();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\InstanceConsequence' => function($sm){
                    $entity = new Model\Entity\InstanceConsequence();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\InstanceRisk' => function($sm){
                    $entity = new Model\Entity\InstanceRisk();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\InstanceRiskOp' => function($sm){
                    $entity = new Model\Entity\InstanceRiskOp();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\ObjectCategory' => function($sm){
                    $entity = new Model\Entity\ObjectCategory();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\RolfRisk' => function($sm){
                    $entity = new Model\Entity\RolfRisk();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\RolfCategory' => function($sm){
                    $entity = new Model\Entity\RolfCategory();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\RolfTag' => function($sm){
                    $entity = new Model\Entity\RolfTag();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Theme' => function($sm){
                    $entity = new Model\Entity\Theme();
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\Threat' => function($sm){
                    $entity = new Model\Entity\Threat();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Entity\User' => function($sm){
                    $u = new Model\Entity\User();
                    $u->setDbAdapter($sm->get('\MonarcCli\Model\Db'));
                    $conf = $sm->get('Config');
                    $salt = isset($conf['monarc']['salt'])?$conf['monarc']['salt']:'';
                    $u->setUserSalt($salt);
                    return $u;
                },
                '\MonarcCore\Model\Entity\Vulnerability' => function($sm){
                    $entity = new Model\Entity\Vulnerability();
                    $entity->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    $entity->setLanguage($this->getDefaultLanguage($sm));
                    return $entity;
                },
                '\MonarcCore\Model\Table\UserTable' => function($sm){
                    $table = new Model\Table\UserTable($sm->get('\MonarcCli\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    $table->setUserRoleTable($sm->get('\MonarcCore\Model\Table\UserRoleTable'));
                    $table->setUserTokenTable($sm->get('\MonarcCore\Model\Table\UserTokenTable'));
                    $table->setPasswordTokenTable($sm->get('\MonarcCore\Model\Table\PasswordTokenTable'));
                    return $table;
                },
                '\MonarcCore\Model\Table\ModelTable' => function($sm){
                    $table = new Model\Table\ModelTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\AnrTable' => function($sm){
                    $table = new Model\Table\AnrTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\CityTable' => function($sm){
                    $table = new Model\Table\CityTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\CountryTable' => function($sm){
                    $table = new Model\Table\CountryTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\GuideTable' => function($sm){
                    $table = new Model\Table\GuideTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\GuideItemTable' => function($sm){
                    $table = new Model\Table\GuideItemTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\MeasureTable' => function($sm){
                    $table = new Model\Table\MeasureTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ObjectTable' => function($sm){
                    $table = new Model\Table\ObjectTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    $table->setObjectObjectTable($sm->get('\MonarcCore\Model\Table\ObjectObjectTable'));
                    return $table;
                },
                '\MonarcCore\Model\Table\InstanceTable' => function($sm){
                    $table = new Model\Table\InstanceTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\InstanceConsequenceTable' => function($sm){
                    $table = new Model\Table\InstanceConsequenceTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\InstanceRiskTable' => function($sm){
                    $table = new Model\Table\InstanceRiskTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\InstanceRiskOpTable' => function($sm){
                    $table = new Model\Table\InstanceRiskOpTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ObjectCategoryTable' => function($sm){
                    $table = new Model\Table\ObjectCategoryTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ObjectObjectTable' => function($sm){
                    $table = new Model\Table\ObjectObjectTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ThemeTable' => function($sm){
                    $table = new Model\Table\ThemeTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\HistoricalTable' => function($sm){
                    $table = new Model\Table\HistoricalTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\AssetTable' => function($sm){
                    $table = new Model\Table\AssetTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\AmvTable' => function($sm){
                    $table = new Model\Table\AmvTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Entity\Amv' => function($sm){
                    $amv = new Model\Entity\Amv();
                    $amv->setDbAdapter($sm->get('\MonarcCore\Model\Db'));
                    return $amv;
                },
                '\MonarcCore\Model\Table\ThreatTable' => function($sm){
                    $table = new Model\Table\ThreatTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\RolfCategoryTable' => function($sm){
                    $table = new Model\Table\RolfCategoryTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\RolfTagTable' => function($sm){
                    $table = new Model\Table\RolfTagTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\RolfRiskTable' => function($sm){
                    $table = new Model\Table\RolfRiskTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ScaleTable' => function($sm){
                    $table = new Model\Table\ScaleTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ScaleCommentTable' => function($sm){
                    $table = new Model\Table\ScaleCommentTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ScaleImpactTypeTable' => function($sm){
                    $table = new Model\Table\ScaleImpactTypeTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\VulnerabilityTable' => function($sm){
                    $table = new Model\Table\VulnerabilityTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\PasswordTokenTable' => function($sm){
                    return new Model\Table\PasswordTokenTable($sm->get('\MonarcCli\Model\Db'));
                },
                // User Role table
                '\MonarcCore\Model\Table\UserRoleTable' => function($sm){
                    $table = new Model\Table\UserRoleTable($sm->get('\MonarcCli\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\UserTokenTable' => function($sm){
                    return new Model\Table\UserTokenTable($sm->get('\MonarcCli\Model\Db'));
                },
                '\MonarcCore\Model\Table\DocModelTable' => function($sm){
                    $table = new Model\Table\DocModelTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                /* Security */
                '\MonarcCore\Service\SecurityService' => '\MonarcCore\Service\SecurityServiceFactory',
                /* Authentification */
                '\MonarcCore\Storage\Authentication' => function($sm){
                    $sa = new Storage\Authentication();
                    $sa->setUserTokenTable($sm->get('\MonarcCore\Model\Table\UserTokenTable'));
                    $sa->setConfig($sm->get('config'));
                    return $sa;
                },
                '\MonarcCore\Adapter\Authentication' => function($sm){
                    $aa = new Adapter\Authentication();
                    $aa->setUserTable($sm->get('\MonarcCore\Model\Table\UserTable'));
                    $aa->setSecurity($sm->get('\MonarcCore\Service\SecurityService'));
                    return $aa;
                },
                '\MonarcCore\Service\ConnectedUserService' => function($sm){
                    $uc = new Service\ConnectedUserService();
                    $request = $token = $sm->get('Request');
                    if(!empty($request) && method_exists($request, 'getHeader')){
                        $token = $request->getHeader('token');
                        if(!empty($token)){
                            $success = false;
                            $dd = $sm->get('\MonarcCore\Storage\Authentication')->getItem($token->getFieldValue(),$success);
                            if($success){
                                $uc->setConnectedUser($dd->get('user'));
                            }
                        }
                    }
                    return $uc;
                },
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
                '\MonarcCore\Controller\ApiModels'                      => '\MonarcCore\Controller\ApiModelsControllerFactory',
                '\MonarcCore\Controller\ApiAnr'                         => '\MonarcCore\Controller\ApiAnrControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstances'                => '\MonarcCore\Controller\ApiAnrInstancesControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesConsequences'    => '\MonarcCore\Controller\ApiAnrInstancesConsequencesControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesRisks'           => '\MonarcCore\Controller\ApiAnrInstancesRisksControllerFactory',
                '\MonarcCore\Controller\ApiAnrInstancesRisksOp'         => '\MonarcCore\Controller\ApiAnrInstancesRisksOpControllerFactory',
                '\MonarcCore\Controller\ApiAnrLibrary'                  => '\MonarcCore\Controller\ApiAnrLibraryControllerFactory',
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
}
