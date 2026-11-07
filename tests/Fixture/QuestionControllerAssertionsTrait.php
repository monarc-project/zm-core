<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Fixture;

use Doctrine\ORM\EntityNotFoundException;
use Laminas\Diactoros\ServerRequest;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\Question\GetQuestionsInputFormatter;
use Monarc\Core\InputFormatter\Question\GetQuestionChoicesInputFormatter;
use Monarc\Core\Service\ConnectedUserService;

trait QuestionControllerAssertionsTrait
{
    private QuestionTestEnvironment $env;

    protected function setUp(): void
    {
        require_once __DIR__ . '/QuestionTestEnvironment.php';
        require_once __DIR__ . '/GuideTestEnvironment.php';
        $identity = $this->createMock(ConnectedUserService::class);
        $identity->method('getConnectedUser')->willReturn(GuideTestEnvironment::user());
        $this->env = new QuestionTestEnvironment($identity);
    }

    protected function tearDown(): void
    {
        $this->env->manager->close();
    }

    public function testCreatesAndReplacementValidatePayloadsOnce(): void
    {
        $data = ['type' => '2', 'multichoice' => false, 'label1' => 'Question'];
        $questionDataInputValidator = $this->createMock(
            \Monarc\Core\Validator\InputValidator\Question\QuestionDataInputValidator::class
        );
        $questionDataInputValidator->expects(self::once())->method('validate')
            ->with($data)
            ->willReturn(['type' => 2, 'multichoice' => 0, 'label1' => 'Question']);
        $questions = new $this->questionControllerClass(
            new GetQuestionsInputFormatter(),
            $questionDataInputValidator,
            $this->env->service
        );
        $questions->handle($this->serverRequest('POST', null, $data));
        self::assertSame(1, $this->env->questions->countAll());

        $definition = $this->definition();
        $data = ['question' => (string)$definition, 'label1' => 'Choice'];
        $replacement = ['questionId' => $definition, 'choice' => [['label1' => 'Replacement']]];
        $questionChoiceDataInputValidator = $this->createMock(
            \Monarc\Core\Validator\InputValidator\Question\QuestionChoiceDataInputValidator::class
        );
        $questionChoiceDataInputValidator->expects(self::once())->method('validate')->with($data)
            ->willReturn(['question' => $definition, 'label1' => 'Choice']);
        $questionChoiceDataInputValidator->expects(self::once())->method('validateReplacement')->with($replacement)
            ->willReturn(['questionId' => $definition, 'choice' => [[
                'question' => $definition, 'position' => 1, 'label1' => 'Replacement',
            ]]]);
        $choices = new $this->choiceControllerClass(
            new GetQuestionChoicesInputFormatter(),
            $questionChoiceDataInputValidator,
            $this->env->choiceService
        );
        $choices->handle($this->serverRequest('POST', null, $data));
        $choices->handle($this->serverRequest('PUT', null, $replacement));
        self::assertSame(1, $this->env->choices->countAll());
        self::assertSame('Replacement', $this->env->choices->findChoicesOfQuestion(
            $this->env->questions->findById($definition)
        )[0]->getLabel(1));
    }

    public function testControllersKeepListSingleCreatePatchDeleteAndReplacementContracts(): void
    {
        $q = $this->request('questions', 'POST', null, ['label1' => 'New', 'type' => 2, 'multichoice' => 0]);
        self::assertSame('ok', $q['status']);
        $id = $q['id'];
        $this->request('questions', 'PATCH', $id, ['label4' => 'Fourth']);
        self::assertSame('New', $this->request('questions', 'GET', $id)['label1']);
        $list = $this->request('questions', 'GET', null, null, ['filter' => 'New', 'limit' => 1]);
        self::assertSame(1, $list['count']);
        self::assertCount(1, $list['questions']);
        // BackOffice definitions can contain choices.
        $definition = $this->definition();
        $replacement = $this->request('choices', 'PUT', null, ['questionId' => $definition,
            'choice' => [['label1' => 'A'], ['label2' => 'B']]]);
        self::assertSame(['status' => 'ok'], $replacement);
        $choices = $this->request('choices', 'GET', null, null, ['question' => $definition]);
        self::assertSame(2, $choices['count']);
        self::assertSame([1, 2], array_column($choices['choices'], 'position'));
        // Round-trip choices from the existing AngularJS editor, including its opaque question object.
        self::assertSame([], $choices['choices'][0]['question']);
        $this->request('choices', 'PUT', null, ['questionId' => $definition, 'choice' => $choices['choices']]);
        $this->request('choices', 'PUT', null, ['questionId' => $definition, 'choice' => []]);
        self::assertSame(0, $this->request('choices', 'GET', null, null, ['question' => $definition])['count']);
        self::assertSame(['status' => 'ok'], $this->request('questions', 'DELETE', $id));
    }

    public function testControllersRejectInvalidReplacementAndDeletionBatchesWithoutPartialMutation(): void
    {
        $q = $this->definition();
        $this->request('choices', 'PUT', null, ['questionId' => $q, 'choice' => [['label1' => 'Kept']]]);
        $this->reject(fn () => $this->request(
            'choices',
            'PUT',
            null,
            ['questionId' => $q, 'choice' => [['label1' => 'Good'], ['label4' => 0]]]
        ));
        self::assertSame('Kept', $this->request('choices', 'GET', null)['choices'][0]['label1']);
        $this->reject(fn () => $this->request('choices', 'DELETE', null, [1, 999]), EntityNotFoundException::class);
        self::assertSame(1, $this->env->choices->countAll());
        $this->reject(fn () => $this->request('questions', 'POST', null, ['type' => 12, 'multichoice' => 0]));
        self::assertSame(1, $this->env->questions->countAll());
    }

    public function testIndividualQuestionAndChoiceWritesKeepTheirResponseContracts(): void
    {
        $question = $this->request('questions', 'POST', null, ['type' => 1, 'multichoice' => 0])['id'];
        $this->request('questions', 'PUT', $question, ['type' => 1, 'multichoice' => 0, 'label2' => 'Updated']);
        $this->request('questions', 'PATCH', $question, ['label3' => 'Partial']);
        $data = $this->request('questions', 'GET', $question);
        self::assertSame('Updated', $data['label2']);
        self::assertSame('Partial', $data['label3']);
        self::assertSame('guide@example.invalid', $data['creator']);
        self::assertSame('guide@example.invalid', $data['updater']);

        $definition = $this->definition();
        $choice = $this->request('choices', 'POST', null, ['question' => $definition, 'label1' => 'First'])['id'];
        self::assertIsInt($choice);
        $this->request('choices', 'PUT', $choice, ['question' => $definition, 'label2' => 'Changed']);
        $this->request('choices', 'PATCH', $choice, ['label3' => 'Retained']);
        $data = $this->request('choices', 'GET', $choice);
        self::assertSame('Changed', $data['label2']);
        self::assertSame('Retained', $data['label3']);
        self::assertSame('guide@example.invalid', $data['creator']);
        self::assertSame('guide@example.invalid', $data['updater']);
        self::assertSame(['status' => 'ok'], $this->request('choices', 'DELETE', $choice));
        self::assertSame(['status' => 'ok'], $this->request('questions', 'DELETE', null, [$question]));
    }

    public function testSingleQuestionCreatesReturnOneIdentifier(): void
    {
        $one = $this->request('questions', 'POST', null, ['type' => 1, 'multichoice' => 0]);
        $two = $this->request('questions', 'POST', null, ['type' => 1, 'multichoice' => 0]);
        self::assertIsInt($one['id']);
        self::assertIsInt($two['id']);
        self::assertNotSame($one['id'], $two['id']);
        $this->request('questions', 'DELETE', null, [$one['id'], $two['id']]);
        self::assertSame(0, $this->request('questions', 'GET', null)['count']);
    }

    /** @dataProvider unsupportedMethods */
    public function testUnsupportedVerbsReturn405(string $kind, string $method): void
    {
        $controller = $this->controller($kind);
        $response = $controller->handle($this->serverRequest($method));
        self::assertSame(405, $response->getStatusCode());
        if ($method === 'HEAD') {
            self::assertSame('', (string)$response->getBody());
        }
    }

    public static function unsupportedMethods(): array
    {
        return [['questions', 'PUT'], ['questions', 'PATCH'], ['questions', 'HEAD'], ['questions', 'OPTIONS'],
            ['questions', 'TRACE'], ['choices', 'PATCH'], ['choices', 'HEAD'], ['choices', 'OPTIONS'],
            ['choices', 'TRACE']];
    }

    public function testExistingRoutesAndAuthorizationStillAllowAssignedRolesAndDenyGuests(): void
    {
        require_once $this->officeRoot . '/Module.php';
        $config = require $this->officeRoot . '/config/module.config.php';
        $module = new $this->moduleClass();
        $identity = $this->createMock(ConnectedUserService::class);
        $user = null;
        $identity->method('getConnectedUser')->willReturnCallback(function () use (&$user) {
            return $user;
        });
        $container = new ServiceManager(['services' => [
            'Config' => $config, ConnectedUserService::class => $identity,
        ]]);
        $app = $this->createMock(Application::class);
        $app->method('getServiceManager')->willReturn($container);
        $router = TreeRouteStack::factory($config['router']);
        $routes = ['/api/questions' => 'monarc_api_questions',
            '/api/questions-choices' => 'monarc_api_questions_choices'];
        foreach ($routes as $uri => $route) {
            $request = new \Laminas\Http\Request();
            $request->setUri($uri);
            self::assertSame($route, $router->match($request)->getMatchedRouteName());
            $match = (new RouteMatch([]));
            $match->setMatchedRouteName($route);
            $event = (new MvcEvent())->setApplication($app)->setRouteMatch($match)
                ->setResponse(new Response())->setViewModel(new ViewModel());
            $module->initRbac($event);
            $user = GuideTestEnvironment::user()->setRoles([$this->allowedRole]);
            self::assertNull($module->checkRbac($event));
            $user = null;
            self::assertSame(401, $module->checkRbac($event)->getStatusCode());
            $user = GuideTestEnvironment::user()->setRoles(['guest']);
            self::assertSame(403, $module->checkRbac($event)->getStatusCode());
        }
    }

    private function definition(): int
    {
        $class = \Monarc\Core\Entity\Question::class;
        $question = (new $class())->setType(2)->setIsMultiChoice(false)->setPosition(1)
            ->setLabels(['label1' => 'Definition']);
        $this->env->questions->save($question);
        return $question->getId();
    }

    private function controller(string $kind): object
    {
        $questions = new GetQuestionsInputFormatter();
        return $kind === 'questions'
            ? new $this->questionControllerClass($questions, $this->env->validator, $this->env->service)
            : new $this->choiceControllerClass(
                new GetQuestionChoicesInputFormatter(),
                $this->env->choiceValidator,
                $this->env->choiceService
            );
    }

    private function serverRequest(
        string $method,
        ?int $id = null,
        ?array $data = null,
        array $query = []
    ): ServerRequest {
        $params = $id === null ? [] : ['id' => $id];
        $body = (new \Laminas\Diactoros\StreamFactory())->createStream(json_encode($data ?? [], JSON_THROW_ON_ERROR));
        return (new ServerRequest([], [], '/api/questions', $method))->withParsedBody($data)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body)
            ->withQueryParams($query)->withAttribute(RouteMatch::class, new RouteMatch($params));
    }

    private function request(
        string $kind,
        string $method,
        ?int $id = null,
        ?array $data = null,
        array $query = []
    ): array {
        $response = $this->controller($kind)->handle($this->serverRequest($method, $id, $data, $query));
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function reject(callable $action, string $exceptionClass = Exception::class): void
    {
        try {
            $action();
            self::fail('Expected validation failure');
        } catch (\Exception $error) {
            self::assertInstanceOf($exceptionClass, $error);
            if ($error instanceof Exception) {
                self::assertSame(412, $error->getCode());
            }
        }
    }
}
