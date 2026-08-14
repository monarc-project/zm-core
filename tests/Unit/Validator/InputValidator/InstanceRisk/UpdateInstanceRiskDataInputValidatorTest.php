<?php declare(strict_types=1);

namespace Unit\Validator\InputValidator\InstanceRisk;

use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use Monarc\Core\Validator\InputValidator\InstanceRisk\UpdateInstanceRiskDataInputValidator;
use PHPUnit\Framework\TestCase;

class UpdateInstanceRiskDataInputValidatorTest extends TestCase
{
    /**
     * @covers UpdateInstanceRiskDataInputValidator::filterRate
     */
    public function testFilterRateReturnsMinusOneForDash(): void
    {
        self::assertSame(-1, UpdateInstanceRiskDataInputValidator::filterRate('-'));
        self::assertSame(4, UpdateInstanceRiskDataInputValidator::filterRate('4'));
    }

    /**
     * @covers UpdateInstanceRiskDataInputValidator::getRules
     */
    public function testValidatorCastsRatesAndTrimsComment(): void
    {
        $validator = new UpdateInstanceRiskDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'threatRate' => '3',
            'vulnerabilityRate' => '-',
            'comment' => ' A comment ',
        ]));

        $validatedData = $validator->getValidData();

        self::assertSame(3, $validatedData['threatRate']);
        self::assertSame(-1, $validatedData['vulnerabilityRate']);
        self::assertSame('A comment', $validatedData['comment']);
    }

    private function createTranslator(): InputValidationTranslator
    {
        $connectedUserService = $this->createMock(ConnectedUserService::class);
        $connectedUserService->method('getConnectedUser')->willReturn(null);

        return new InputValidationTranslator($connectedUserService, [
            'languages' => ['fr', 'en', 'de', 'nl'],
        ]);
    }
}
