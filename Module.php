<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core;

use Monarc\Core\Service\AuthenticationService;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\Router\RouteMatch;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        if ($e->getRequest() instanceof Request) {
            $eventManager = $e->getApplication()->getEventManager();

            $eventManager->attach(MvcEvent::EVENT_ROUTE, [$this, 'MCEventRoute'], -100);
            $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onDispatchError'], 0);
            $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'onRenderError'], 0);
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getDefaultLanguage($sm)
    {
        return $sm->get('Config')['defaultLanguageIndex'];
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
        $exceptionJson = [];
        if ($exception) {
            $exceptionJson = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'stacktrace' => $exception->getTraceAsString(),
            ];
        }

        $errorJson = [
            'message' => 'An error occurred during execution; please try again later.',
            'error' => $error,
            'exception' => $exceptionJson,
        ];
        if ($error === 'error-router-no-match') {
            $errorJson['message'] = 'Resource not found.';
        }

        if ($exception->getCode() === 400) {
            $model = new JsonModel([
                'errors' => [json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR)],
            ]);
        } else {
            $model = new JsonModel(['errors' => [$errorJson]]);
        }

        $e->setResult($model);

        return $model;
    }

    public function MCEventRoute($event)
    {
        /** @var AuthenticationService $authenticationService */
        $authenticationService = $event->getApplication()
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
        if (!empty($token)) {
            if ($authenticationService->checkConnect(['token' => $token->getFieldValue()])) {
                return;
            }
        }

        return $event->getResponse()->setStatusCode(401);
    }
}
