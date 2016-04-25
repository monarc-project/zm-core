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


            $sm = $e->getApplication()->getServiceManager();
            $config = $sm->get('Config');
            $permissions = $config['permissions'];

            // Route is whitelisted
            $name = $match->getMatchedRouteName();
<<<<<<< HEAD
            if (in_array($name, $permissions)) {
=======
            if($name == 'auth' || $name == 'home'){
>>>>>>> ad640f20274e670f9094992577f2a3112e27bbe1
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
                '\MonarcCore\Model\Entity\User' => '\MonarcCore\Model\Entity\User',
                '\MonarcCore\Model\Entity\UserRole' => '\MonarcCore\Model\Entity\UserRole',
                '\MonarcCore\Model\Entity\Role' => '\MonarcCore\Model\Entity\Role',
            ),
            'factories' => array(
                '\MonarcCore\Model\Db' => function($sm){
                    return new Model\Db($sm->get('doctrine.entitymanager.orm_default'));
                },
                '\MonarcCore\Service\IndexService' => '\MonarcCore\Service\IndexServiceFactory',

                '\MonarcCore\Model\Table\UserTable' => function($sm){
                    $utable = new Model\Table\UserTable($sm->get('\MonarcCore\Model\Db'));
                    $utable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $utable;
                },
                '\MonarcCore\Model\Table\PasswordTokenTable' => function($sm){
                    return new Model\Table\PasswordTokenTable($sm->get('\MonarcCore\Model\Db'));
                },
                // User Role table
                '\MonarcCore\Model\Table\UserRoleTable' => function($sm){
                    $urtable = new Model\Table\UserRoleTable($sm->get('\MonarcCore\Model\Db'));
                    $urtable->setConnectedUser($sm->get('\MonarcCore\Service\ConnectedUserService')->getConnectedUser());
                    return $urtable;
                },
                '\MonarcCore\Service\UserService' => '\MonarcCore\Service\UserServiceFactory',
                '\MonarcCore\Service\UserRoleService' => '\MonarcCore\Service\UserRoleServiceFactory',
                '\MonarcCore\Service\RoleService' => '\MonarcCore\Service\RoleServiceFactory',
                '\MonarcCore\Service\MailService' => '\MonarcCore\Service\MailServiceFactory',
                '\MonarcCore\Service\PasswordService' => '\MonarcCore\Service\PasswordServiceFactory',
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
                '\MonarcCore\Service\AuthenticationService' => '\MonarcCore\Service\AuthenticationServiceFactory',

                // Récupération du user connecté
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
