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
use Monarc\Core\Controller\Handler\ControllerRequestResponseHandlerTrait;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class AbstractRestfulControllerRequestHandlerTest extends TestCase
{
    /** @dataProvider validationModes */
    public function testPostValidationSetsTrustedLanguageAndValidatesEachPayloadOnce(bool $batch): void
    {
        $data = $batch ? [['label3' => 'First'], ['label3' => 'Second']] : ['label3' => 'Question'];
        $inputValidator = $this->createMock(AbstractInputValidator::class);
        $inputValidator->expects(self::once())->method('resetValidation');
        $inputValidator->expects(self::once())->method('setDefaultLanguageIndex')->with(3);
        $inputValidator->expects(self::exactly($batch ? 2 : 1))->method('isValid')->willReturn(true);
        $inputValidator->expects(self::never())->method('validate');
        $inputValidator->method($batch ? 'getValidDataSets' : 'getValidData')->willReturn($data);
        $controller = new class ($inputValidator, $batch) extends AbstractRestfulControllerRequestHandler {
            use ControllerRequestResponseHandlerTrait;

            public function __construct(private AbstractInputValidator $inputValidator, private bool $batch)
            {
            }

            public function create($data)
            {
                $this->validatePostParams($this->inputValidator, $data, $this->batch);
                $validData = $this->batch
                    ? $this->inputValidator->getValidDataSets()
                    : $this->inputValidator->getValidData();

                return $this->getPreparedJsonResponse($validData);
            }
        };
        $anr = new class extends AnrSuperClass {
            public function getLanguage(): int
            {
                return 3;
            }
        };
        $request = (new ServerRequest([], [], '/api/validation', 'POST'))->withParsedBody($data)
            ->withAttribute('anr', $anr)
            ->withAttribute(RouteMatch::class, new RouteMatch([]));
        $response = $controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($data, json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function validationModes(): array
    {
        return [
            'single write' => [false],
            'existing batch write' => [true],
        ];
    }

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

    /** @dataProvider analysisScopeCases */
    public function testAnalysisScopeRespectsExplicitFormatterDeclarations(
        bool $hasAnalysis,
        array $allowedFilterFields,
        array $expected
    ): void {
        $anr = $hasAnalysis ? new AnrSuperClass() : null;
        $inputFormatter = new class ($allowedFilterFields) extends AbstractInputFormatter {
            protected static array $allowedFilterFields = [];

            public function __construct(array $allowedFilterFields)
            {
                static::$allowedFilterFields = $allowedFilterFields;
            }
        };
        $controller = new class ($inputFormatter, $anr) extends AbstractRestfulControllerRequestHandler {
            use ControllerRequestResponseHandlerTrait;

            public function __construct(private AbstractInputFormatter $inputFormatter, private ?AnrSuperClass $anr)
            {
            }

            public function getList()
            {
                $formattedParams = $this->getFormattedInputParams($this->inputFormatter);
                $anrFilter = $formattedParams->getFilterFor('anr');

                return $this->getPreparedJsonResponse([
                    'hasAnrFilter' => $formattedParams->hasFilterFor('anr'),
                    'hasTrustedAnr' => $this->anr !== null && ($anrFilter['value'] ?? null) === $this->anr,
                    'isUsedInQuery' => $anrFilter === [] ? null : ($anrFilter['isUsedInQuery'] ?? true),
                ]);
            }
        };
        $request = (new ServerRequest([], [], '/api/scoped-list', 'GET'))
            ->withQueryParams(['anr' => 999])
            ->withAttribute(RouteMatch::class, new RouteMatch([]));
        if ($anr !== null) {
            $request = $request->withAttribute('anr', $anr);
        }
        $response = $controller->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($expected, json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function analysisScopeCases(): array
    {
        return [
            'explicit scope overrides query' => [true, ['anr'],
                ['hasAnrFilter' => true, 'hasTrustedAnr' => true, 'isUsedInQuery' => true]],
            'explicit context-only filter preserved' => [true, ['anr' => ['isUsedInQuery' => false]],
                ['hasAnrFilter' => true, 'hasTrustedAnr' => true, 'isUsedInQuery' => false]],
            'undeclared scope is not added implicitly' => [true, [],
                ['hasAnrFilter' => false, 'hasTrustedAnr' => false, 'isUsedInQuery' => null]],
            'query cannot invent trusted scope' => [false, [],
                ['hasAnrFilter' => false, 'hasTrustedAnr' => false, 'isUsedInQuery' => null]],
        ];
    }
}
