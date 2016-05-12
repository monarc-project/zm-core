<?php
namespace MonarcCore;

//use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use \Zend\Mvc\Controller\ControllerManager;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Router\RouteMatch;

class Module
{

    public function onBootstrap(MvcEvent $e)
    {
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

            $config = $e->getApplication()->getServiceManager()->get('Config');
            $permissions = $config['permissions'];

            // Route is whitelisted
            $name = $match->getMatchedRouteName();
            if (in_array($name, $permissions)) {
                return;
            }

            $request = $e->getRequest();
            if($request instanceof \Zend\Console\Request){
                return;
            }

            $token = $request->getHeader('token');
            if(!empty($token)){
                if($serv->checkConnect(array('token'=>$token->getFieldValue()))){
                    return;
                }
            }

            return $e->getResponse()->setStatusCode(401);
        },-100);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onDispatchError'), 0);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onRenderError'), 0);
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

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                //'\MonarcCore\Model\Entity\User' => '\MonarcCore\Model\Entity\User',
                '\MonarcCore\Model\Entity\Model' => '\MonarcCore\Model\Entity\Model',
                '\MonarcCore\Model\Entity\Asset' => '\MonarcCore\Model\Entity\Asset',
                '\MonarcCore\Model\Entity\Threat' => '\MonarcCore\Model\Entity\Threat',
            ),
            'factories' => array(
                '\MonarcCore\Model\Db' => function($sm){
                    return new Model\Db($sm->get('doctrine.entitymanager.orm_default'));
                },
                '\MonarcCore\Model\Entity\User' => function($sm){
                    $u = new Model\Entity\User();
                    $u->setDbAdpater($sm->get('\MonarcCore\Model\Db'));
                    $conf = $sm->get('Config');
                    $salt = isset($conf['monarc']['salt'])?$conf['monarc']['salt']:'';
                    $u->setUserSalt($salt);
                    return $u;
                },
                '\MonarcCore\Model\Table\UserTable' => function($sm){
                    $utable = new Model\Table\UserTable($sm->get('\MonarcCore\Model\Db'));
                    $utable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $utable;
                },
                '\MonarcCore\Model\Table\ModelTable' => function($sm){
                    $utable = new Model\Table\ModelTable($sm->get('\MonarcCore\Model\Db'));
                    $utable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $utable;
                },
                '\MonarcCore\Model\Table\MeasureTable' => function($sm){
                    $table = new Model\Table\MeasureTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\ThemeTable' => function($sm){
                    $table = new Model\Table\ThemeTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\AssetTable' => function($sm){
                    $utable = new Model\Table\AssetTable($sm->get('\MonarcCore\Model\Db'));
                    $utable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $utable;
                },
                '\MonarcCore\Model\Table\ThreatTable' => function($sm){
                    $utable = new Model\Table\ThreatTable($sm->get('\MonarcCore\Model\Db'));
                    $utable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $utable;
                },
                '\MonarcCore\Model\Table\VulnerabilityTable' => function($sm){
                    $table = new Model\Table\VulnerabilityTable($sm->get('\MonarcCore\Model\Db'));
                    $table->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $table;
                },
                '\MonarcCore\Model\Table\PasswordTokenTable' => function($sm){
                    return new Model\Table\PasswordTokenTable($sm->get('\MonarcCore\Model\Db'));
                },
                '\MonarcCore\Model\Table\UserRoleTable' => function($sm){
                    $urtable = new Model\Table\UserRoleTable($sm->get('\MonarcCore\Model\Db'));
                    $urtable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $urtable;
                },
                '\MonarcCore\Model\Table\UserTokenTable' => function($sm){
                    return new Model\Table\UserTokenTable($sm->get('\MonarcCore\Model\Db'));
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
                '\MonarcCore\Controller\Index' => '\MonarcCore\Controller\IndexControllerFactory',
                '\MonarcCore\Controller\Authentication' => '\MonarcCore\Controller\AuthenticationControllerFactory',
            ),
        );
    }

    public function getInputFilterConfig(){
        return array(
            'invokables' => array(
                '\MonarcCore\Filter\Password' => '\MonarcCore\Filter\Password',
            ),
        );
    }

    public function getValidatorConfig(){
        return array(
            'invokables' => array(
                '\MonarcCore\Validator\UniqueEmail' => '\MonarcCore\Validator\UniqueEmail',
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
