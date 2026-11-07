<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Controller;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\StreamFactory;
use Laminas\Mvc\View\Http\CreateViewModelListener;
use Laminas\Router\RouteMatch;
use Monarc\Core\Controller\Handler\AbstractRestfulControllerRequestHandler;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class AbstractRestfulControllerRequestHandlerTest extends TestCase
{
    /** @dataProvider unsupportedWrites */
    public function testMvcViewModelListenerPreservesMethodNotAllowedResponses(string $method): void
    {
        $controller = new class extends AbstractRestfulControllerRequestHandler {
        };
        (new CreateViewModelListener())->attach($controller->getEventManager());
        $request = (new ServerRequest([], [], '/api/read-only', $method))
            ->withHeader('Content-Type', 'application/json')->withBody((new StreamFactory())->createStream('{}'))
            ->withAttribute(RouteMatch::class, new RouteMatch([]));

        $response = $controller->handle($request);

        self::assertSame(405, $response->getStatusCode());
        self::assertSame(
            ['content' => 'Method Not Allowed'],
            json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public static function unsupportedWrites(): array
    {
        return [['POST'], ['PUT'], ['PATCH'], ['DELETE']];
    }
}
