<?php declare(strict_types=1);

namespace Unit\Validator\InputValidator\RiskSource;

use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use Monarc\Core\Validator\InputValidator\RiskSource\PatchRiskSourceDataInputValidator;
use Monarc\Core\Validator\InputValidator\RiskSource\PostRiskSourceDataInputValidator;
use PHPUnit\Framework\TestCase;

class RiskSourceInputValidatorTest extends TestCase
{
    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PostRiskSourceDataInputValidator::getRules
     */
    public function testPostValidatorTrimsLabelsAndCastsBooleanField(): void
    {
        $validator = new PostRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'labels' => ['fr' => '  Supplier failure  '],
            'isActive' => '0',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame(['fr' => 'Supplier failure'], $validatedData['labels']);
        self::assertFalse($validatedData['isActive']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PatchRiskSourceDataInputValidator::getRules
     */
    public function testPatchValidatorAcceptsPartialPayload(): void
    {
        $validator = new PatchRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'labels' => ['fr' => 'Supplier failure'],
            'isActive' => '1',
        ]));

        $validatedData = $validator->getValidData();
        self::assertTrue($validatedData['isActive']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PatchRiskSourceDataInputValidator::getRules
     */
    public function testPatchValidatorDoesNotDefaultIsActiveWhenAbsent(): void
    {
        $validator = new PatchRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'labels' => ['fr' => ' Supplier / third party '],
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame(['fr' => 'Supplier / third party'], $validatedData['labels']);
        self::assertTrue(!array_key_exists('isActive', $validatedData) || $validatedData['isActive'] === null);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PostRiskSourceDataInputValidator::getRules
     */
    public function testPostValidatorDefaultsIsActiveToTrueWhenAbsent(): void
    {
        $validator = new PostRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid(['labels' => ['fr' => 'ttt']]));
        self::assertTrue($validator->getValidData()['isActive']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PostRiskSourceDataInputValidator::getRules
     */
    public function testPostValidatorRejectsMissingLabels(): void
    {
        $validator = new PostRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertFalse($validator->isValid([
            'labels' => [],
        ]));
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
