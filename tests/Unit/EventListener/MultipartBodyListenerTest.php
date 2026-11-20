<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\EventListener {
    // Supply the SAPI parser result without reading a real HTTP body in unit tests.
    function request_parse_body(): array
    {
        ++\Monarc\Core\Tests\Unit\EventListener\MultipartBodyListenerTest::$parseCalls;
        $result = \Monarc\Core\Tests\Unit\EventListener\MultipartBodyListenerTest::$parsed;
        if ($result instanceof \RequestParseBodyException) {
            throw $result;
        }

        return $result;
    }
}

namespace Monarc\Core\Tests\Unit\EventListener {
    use Laminas\Http\Request;
    use Laminas\Mvc\MvcEvent;
    use Laminas\Psr7Bridge\Psr7ServerRequest;
    use Laminas\Router\RouteMatch;
    use Monarc\Core\EventListener\MultipartBodyListener;
    use Monarc\Core\Exception\Exception;
    use PHPUnit\Framework\TestCase;

    /** @coversNothing */
    class MultipartBodyListenerTest extends TestCase
    {
        public static array|\RequestParseBodyException $parsed = [];
        public static int $parseCalls = 0;

        protected function setUp(): void
        {
            self::$parseCalls = 0;
        }

        public function testModuleParsesOtherMiddlewareRoutesBeforeThePsr7Bridge(): void
        {
            require_once __DIR__ . '/../../../Module.php';
            $sharedEvents = new \Laminas\EventManager\SharedEventManager();
            $applicationEvents = new \Laminas\EventManager\EventManager($sharedEvents);
            $application = $this->createMock(\Laminas\Mvc\Application::class);
            $application->method('getEventManager')->willReturn($applicationEvents);
            (new \Monarc\Core\Module())->onBootstrap(
                (new MvcEvent())->setApplication($application)->setRequest(new Request())
            );
            $event = $this->event('PUT', 'multipart/form-data');
            $event->setName(MvcEvent::EVENT_DISPATCH);
            $applicationEvents->triggerEvent($event);
            self::assertSame(0, self::$parseCalls);
            $handler = $this->createMock(\Psr\Http\Server\RequestHandlerInterface::class);
            $handler->expects(self::exactly(2))->method('handle')->willReturnCallback(
                static function (
                    \Psr\Http\Message\ServerRequestInterface $request
                ): \Psr\Http\Message\ResponseInterface {
                    self::assertSame(['description' => 'Shared upload'], $request->getParsedBody());
                    self::assertSame('', (string)$request->getBody());
                    self::assertSame(
                        'unrelated_route',
                        $request->getAttribute(RouteMatch::class)->getMatchedRouteName()
                    );

                    return new \Laminas\Diactoros\Response\JsonResponse([]);
                }
            );
            foreach (['PUT', 'PATCH'] as $method) {
                self::$parsed = [['description' => 'Shared upload'], []];
                $event = $this->event($method, 'multipart/form-data; boundary=fixture', 'unrelated_route');
                $controller = new \Laminas\Mvc\Middleware\MiddlewareController(
                    $handler,
                    new \Laminas\EventManager\EventManager($sharedEvents),
                    $event
                );
                $controller->dispatch($event->getRequest(), new \Laminas\Http\Response());
            }
            self::assertSame(2, self::$parseCalls);
        }

        public function testMultipartPutIsParsedBeforeTheBridgeAndNestedLanguagesReachTheController(): void
        {
            $path = tempnam(sys_get_temp_dir(), 'php84-upload-');
            try {
                file_put_contents($path, 'document');
                self::$parsed = [['category' => '1'], ['file' => [
                    'tmp_name' => [2 => $path],
                    'name' => [2 => 'template.docx'],
                    'type' => [2 => 'application/octet-stream'],
                    'size' => [2 => 8],
                    'error' => [2 => UPLOAD_ERR_OK],
                ]]];
                $event = $this->event('PUT', 'multipart/form-data; boundary=fixture');
                (new MultipartBodyListener())($event);
                self::assertSame(1, self::$parseCalls);
                $psrRequest = Psr7ServerRequest::fromLaminas($event->getRequest());
                self::assertSame(['category' => '1'], $psrRequest->getParsedBody());
                self::assertSame('template.docx', $psrRequest->getUploadedFiles()['file'][2]->getClientFilename());
                self::assertSame('document', (string)$psrRequest->getUploadedFiles()['file'][2]->getStream());
            } finally {
                unlink($path);
            }
        }

        public function testParserDoesNotConsumePostJsonOrGetBodies(): void
        {
            foreach ([
                ['POST', 'multipart/form-data', 'monarc_api_doc_models'],
                ['PUT', 'application/json', 'monarc_api_doc_models'],
                ['GET', null, 'monarc_api_doc_models'],
            ] as [$method, $contentType, $route]) {
                $event = $this->event($method, $contentType, $route);
                (new MultipartBodyListener())($event);
                self::assertSame('original', $event->getRequest()->getContent());
            }
            self::assertSame(0, self::$parseCalls);
        }

        public function testMalformedMultipartProducesAFieldValidationFailure(): void
        {
            self::$parsed = new \RequestParseBodyException('invalid fixture boundary');
            try {
                (new MultipartBodyListener())($this->event('PATCH', 'multipart/form-data'));
                self::fail('Malformed body accepted');
            } catch (Exception $error) {
                self::assertSame(400, $error->getCode());
                self::assertArrayHasKey('body', json_decode($error->getMessage(), true)['validationErrors']);
            }
        }

        private function event(string $method, ?string $contentType, string $route = 'monarc_api_doc_models'): MvcEvent
        {
            $request = (new Request())->setMethod($method)->setContent('original');
            if ($contentType !== null) {
                $request->getHeaders()->addHeaderLine('Content-Type', $contentType);
            }
            $routeMatch = (new RouteMatch([]))->setMatchedRouteName($route);

            return (new MvcEvent())->setRequest($request)->setRouteMatch($routeMatch);
        }
    }
}
