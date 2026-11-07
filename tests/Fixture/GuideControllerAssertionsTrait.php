<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Laminas\Diactoros\ServerRequest;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Guide\GetGuidesInputFormatter;
use Monarc\Core\InputFormatter\Guide\GetGuideItemsInputFormatter;
use Monarc\Core\Service\ConnectedUserService;

trait GuideControllerAssertionsTrait
{
    private GuideTestEnvironment $env;

    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/GuideTestEnvironment.php';
    }

    protected function setUp(): void
    {
        $identity = $this->createMock(ConnectedUserService::class);
        $identity->method('getConnectedUser')->willReturn(GuideTestEnvironment::user());
        $this->env = new GuideTestEnvironment($identity);
    }

    protected function tearDown(): void
    {
        $this->env->manager->close();
    }

    public function testCreateValidatesGuideAndItemPayloadsOnce(): void
    {
        $data = ['type' => '1', 'isWithItems' => true];
        $guideDataInputValidator = $this->createMock(
            \Monarc\Core\Validator\InputValidator\Guide\GuideDataInputValidator::class
        );
        $guideDataInputValidator->expects(self::once())->method('validate')->with($data)
            ->willReturn(['type' => 1, 'isWithItems' => 1]);
        $guides = new $this->guideControllerClass(
            new GetGuidesInputFormatter(),
            $guideDataInputValidator,
            $this->env->guideService
        );
        $guide = $this->decode($guides->create($data))['id'];

        $data = ['guide' => (string)$guide, 'description1' => 'Item'];
        $guideItemDataInputValidator = $this->createMock(
            \Monarc\Core\Validator\InputValidator\Guide\GuideItemDataInputValidator::class
        );
        $guideItemDataInputValidator->expects(self::once())->method('validate')->with($data)
            ->willReturn(['guide' => $guide, 'description1' => 'Item']);
        $items = new $this->itemControllerClass(
            new GetGuideItemsInputFormatter(),
            $guideItemDataInputValidator,
            $this->env->itemService
        );
        $item = $this->decode($items->create($data))['id'];
        self::assertSame($guide, $this->env->itemService->getEntity($item)['guide']['id']);
        self::assertSame('Item', $this->env->itemService->getEntity($item)['description1']);
    }

    public function testGuideAndItemControllersKeepTheirPayloadContractsAcrossCrud(): void
    {
        $controller = new $this->guideControllerClass(
            new GetGuidesInputFormatter(),
            $this->env->guideValidator,
            $this->env->guideService
        );
        $created = $this->decode($controller->create(['type' => 1, 'isWithItems' => 1, 'description3' => 'Guide']));
        self::assertSame('ok', $created['status']);
        $id = $created['id'];
        self::assertIsInt($id);
        self::assertSame('Risk analysis context', $this->decode($controller->get((string)$id))['type']);
        $controller->patch($id, ['description3' => 'Edited']);
        self::assertSame('Edited', $this->decode($controller->get($id))['description3']);
        self::assertSame('guide@example.invalid', $this->decode($controller->get($id))['creator']);
        self::assertSame('guide@example.invalid', $this->decode($controller->get($id))['updater']);
        $items = new $this->itemControllerClass(
            new GetGuideItemsInputFormatter(),
            $this->env->itemValidator,
            $this->env->itemService
        );
        $child = $this->decode($items->create(['guide' => (string)$id, 'description1' => 'First']));
        $items->create(['guide' => $id, 'description1' => 'Second']);
        $response = $this->request($items, 'GET', null, ['guide' => (string)$id, 'limit' => 1]);
        self::assertSame(2, $response['count']);
        self::assertCount(1, $response[$this->itemListName]);
        self::assertSame($id, $response[$this->itemListName][0]['guide']['id']);
        $items->update($child['id'], ['guide' => $id, 'description1' => 'Updated']);
        $items->patch($child['id'], ['description3' => 'Partial']);
        $item = $this->decode($items->get($child['id']));
        self::assertSame('Updated', $item['description1']);
        self::assertSame('Partial', $item['description3']);
        self::assertSame('guide@example.invalid', $item['creator']);
        self::assertSame('guide@example.invalid', $item['updater']);
        $controller->update($id, ['type' => 1, 'isWithItems' => 1, 'description1' => 'Full update']);
        self::assertSame('Full update', $this->decode($controller->get($id))['description1']);
        self::assertSame('ok', $this->decode($items->delete($child['id']))['status']);
        self::assertSame('ok', $this->decode($controller->deleteList([$id]))['status']);
        self::assertSame(0, $this->env->items->countAll());
    }

    public function testSingleCreatesAndItemDeletionRetainTheirResponseShapes(): void
    {
        $guides = new $this->guideControllerClass(
            new GetGuidesInputFormatter(),
            $this->env->guideValidator,
            $this->env->guideService
        );
        $guide = $this->decode($guides->create(['type' => 1, 'isWithItems' => 1]))['id'];
        self::assertIsInt($guide);
        $items = new $this->itemControllerClass(
            new GetGuideItemsInputFormatter(),
            $this->env->itemValidator,
            $this->env->itemService
        );
        $one = $this->decode($items->create(['guide' => $guide, 'description1' => 'One']))['id'];
        $two = $this->decode($items->create(['guide' => $guide, 'description1' => 'Two']))['id'];
        $ids = [$one, $two];
        self::assertIsInt($one);
        self::assertIsInt($two);
        self::assertSame(['status' => 'ok'], $this->decode($items->deleteList($ids)));
        self::assertSame(0, $this->env->items->countAll());
    }

    public function testInvalidGuideWritesAreRejectedBeforeCallingServices(): void
    {
        $controller = new $this->guideControllerClass(
            new GetGuidesInputFormatter(),
            $this->env->guideValidator,
            $this->env->guideService
        );
        try {
            $controller->create(['type' => 9, 'isWithItems' => 1]);
            self::fail('Invalid guide must be rejected');
        } catch (Exception $error) {
            self::assertSame(412, $error->getCode());
        }
        self::assertSame(0, $this->env->guides->countAll());
        $id = $this->env->guideService->create($this->env->guideValidator->validate(['type' => 1, 'isWithItems' => 1]));
        $controller->patch($id, ['description1' => 'kept']);
        try {
            $controller->update($id, ['description1' => 'invalid PUT']);
            self::fail('PUT requires its mandatory fields');
        } catch (Exception $error) {
            self::assertSame(412, $error->getCode());
        }
        self::assertSame('kept', $this->env->guideService->getEntity($id)['description1']);
    }

    /** @dataProvider unsupportedVerbs */
    public function testUnsupportedCollectionVerbsReturn405(string $method): void
    {
        $controller = new $this->guideControllerClass(
            new GetGuidesInputFormatter(),
            $this->env->guideValidator,
            $this->env->guideService
        );
        $match = new RouteMatch([]);
        $request = (new ServerRequest([], [], '/api/guides', $method))
            ->withAttribute(RouteMatch::class, $match);
        $response = $controller->handle($request);
        self::assertSame(405, $response->getStatusCode());
        if ($method === 'HEAD') {
            self::assertSame('', (string)$response->getBody());
        }
    }

    public static function unsupportedVerbs(): array
    {
        return [['PUT'], ['PATCH'], ['HEAD'], ['OPTIONS'], ['TRACE']];
    }

    public function testExistingGuideRoutesKeepAuthorizedAndDeniedRolePaths(): void
    {
        require_once $this->officeRoot . '/Module.php';
        $config = require $this->officeRoot . '/config/module.config.php';
        $module = new $this->moduleClass();
        $identity = $this->createMock(ConnectedUserService::class);
        $currentUser = null;
        $identity->method('getConnectedUser')->willReturnCallback(function () use (&$currentUser) {
            return $currentUser;
        });
        $container = new ServiceManager(['services' => [
            'Config' => $config, ConnectedUserService::class => $identity,
        ]]);
        $app = $this->createMock(Application::class);
        $app->method('getServiceManager')->willReturn($container);
        $router = TreeRouteStack::factory($config['router']);
        $routes = ['/api/guides' => 'monarc_api_guides', '/api/guides-items' => 'monarc_api_guides_items'];
        foreach ($routes as $uri => $name) {
            $request = new \Laminas\Http\Request();
            $request->setUri($uri);
            self::assertSame($name, $router->match($request)->getMatchedRouteName());
        }
        foreach (['monarc_api_guides', 'monarc_api_guides_items'] as $route) {
            $match = new RouteMatch([]);
            $match->setMatchedRouteName($route);
            $event = (new MvcEvent())->setApplication($app)->setRouteMatch($match)
                ->setResponse(new Response())->setViewModel(new ViewModel());
            $module->initRbac($event);
            $currentUser = GuideTestEnvironment::user()->setRoles([$this->allowedRole]);
            self::assertNull($module->checkRbac($event));
            $currentUser = null;
            self::assertSame(401, $module->checkRbac($event)->getStatusCode());
            $currentUser = GuideTestEnvironment::user()->setRoles(['guest']);
            self::assertSame(403, $module->checkRbac($event)->getStatusCode());
        }
    }

    private function request(object $controller, string $method, ?int $id = null, array $query = []): array
    {
        $match = new RouteMatch($id === null ? [] : ['id' => (string)$id]);
        $request = (new ServerRequest([], [], '/api/guides-items', $method))
            ->withQueryParams($query)->withAttribute(RouteMatch::class, $match);

        return $this->decode($controller->handle($request));
    }

    private function decode(object $response): array
    {
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
