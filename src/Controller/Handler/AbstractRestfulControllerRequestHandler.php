<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller\Handler;

use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Monarc\Core\Request\Psr7Bridge\RequestConverter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractRestfulControllerRequestHandler extends AbstractRestfulController implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $laminasRequest = RequestConverter::toLaminas($request);
        $routerMatch = $laminasRequest->getAttribute(RouteMatch::class);
        if ($routerMatch !== null) {
            $this->setEvent((new MvcEvent())->setRouteMatch($routerMatch));
        }

        return $this->dispatch($laminasRequest);
    }
}
