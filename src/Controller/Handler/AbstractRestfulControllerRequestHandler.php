<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller\Handler;

use Laminas\Diactoros\Response;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\View\Model\ViewModel;
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

        $result = $this->dispatch($laminasRequest);

        /* Handles the case of the missing controller's action or other dispatching issues. */
        if ($result instanceof ViewModel) {
            $content = json_encode([
                'errors' => [
                    [
                        'message' => ($result->getVariables()['content'] ?? '')
                            . '. Check if the controller\'s action template is presented for: "'
                            . $result->getTemplate() . '"',
                    ],
                ],
            ], JSON_THROW_ON_ERROR);
            $stream = fopen('data://application/json,' . $content, 'rb+');

            return new Response($stream, 412);
        }

        return $result;
    }
}
