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
    public function testPostValidatorTrimsLabelAndCastsBooleanField(): void
    {
        $validator = new PostRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'label' => '  Supplier failure  ',
            'isActive' => '0',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame('Supplier failure', $validatedData['label']);
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
            'label' => ' Supplier / third party ',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame('Supplier / third party', $validatedData['label']);
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

        self::assertTrue($validator->isValid(['label' => 'ttt']));
        self::assertTrue($validator->getValidData()['isActive']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\RiskSource\PostRiskSourceDataInputValidator::getRules
     */
    public function testPostValidatorRejectsEmptyLabel(): void
    {
        $validator = new PostRiskSourceDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertFalse($validator->isValid([
            'label' => '   ',
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
