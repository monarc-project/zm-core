<?php declare(strict_types=1);

namespace Unit\Validator\InputValidator\ReassessmentTrigger;

use Monarc\Core\Service\ConnectedUserService;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator;
use Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PostReassessmentTriggerDataInputValidator;
use PHPUnit\Framework\TestCase;

class ReassessmentTriggerInputValidatorTest extends TestCase
{
    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PostReassessmentTriggerDataInputValidator::getRules
     */
    public function testPostValidatorTrimsTranslationsAndDefaultsIsActive(): void
    {
        $validator = new PostReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerTypes' => ['fr' => 'security_incident'],
            'descriptions' => ['fr' => '  Reassess after a critical incident.  '],
            'monitoringApproaches' => ['fr' => 'SOC alerts'],
            'position' => '2',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame(['fr' => 'security_incident'], $validatedData['triggerTypes']);
        self::assertSame(['fr' => 'Reassess after a critical incident.'], $validatedData['descriptions']);
        self::assertTrue($validatedData['isActive']);
        self::assertSame(2, $validatedData['position']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PostReassessmentTriggerDataInputValidator::getRules
     */
    public function testPostValidatorKeepsPluralTranslationFields(): void
    {
        $validator = new PostReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerTypes' => [
                'en' => 'System change',
                'fr' => 'Changement du systeme',
            ],
            'descriptions' => [
                'en' => 'English description',
                'de' => 'Deutsche Beschreibung',
            ],
            'monitoringApproaches' => [
                'en' => 'SOC alerts',
                'fr' => 'Alertes SOC',
            ],
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame([
            'en' => 'System change',
            'fr' => 'Changement du systeme',
        ], $validatedData['triggerTypes']);
        self::assertSame([
            'en' => 'English description',
            'de' => 'Deutsche Beschreibung',
        ], $validatedData['descriptions']);
        self::assertSame([
            'en' => 'SOC alerts',
            'fr' => 'Alertes SOC',
        ], $validatedData['monitoringApproaches']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator::getRules
     */
    public function testPatchValidatorAcceptsTranslationPayload(): void
    {
        $validator = new PatchReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerTypes' => ['fr' => 'System change'],
            'descriptions' => ['fr' => 'Description'],
            'monitoringApproaches' => ['fr' => 'SOC alerts'],
            'isActive' => '0',
            'position' => '4',
        ]));

        $validatedData = $validator->getValidData();
        self::assertFalse($validatedData['isActive']);
        self::assertSame(4, $validatedData['position']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator::getValidData
     */
    public function testPatchValidatorReturnsNullForMissingOptionalFields(): void
    {
        $validator = new PatchReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerTypes' => ['fr' => 'System change'],
            'descriptions' => ['fr' => 'Description'],
            'monitoringApproaches' => ['fr' => 'SOC alerts'],
            'position' => '4',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame(4, $validatedData['position']);
        self::assertNull($validatedData['isActive']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator::getRules
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator::getValidData
     */
    public function testPatchValidatorKeepsPluralTranslationFields(): void
    {
        $validator = new PatchReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerTypes' => [
                'en' => 'System change',
                'fr' => 'Changement du systeme',
            ],
            'descriptions' => [
                'en' => 'English description',
                'de' => 'Deutsche Beschreibung',
            ],
            'monitoringApproaches' => [
                'en' => 'SOC alerts',
                'fr' => 'Alertes SOC',
            ],
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame([
            'en' => 'System change',
            'fr' => 'Changement du systeme',
        ], $validatedData['triggerTypes']);
        self::assertSame([
            'en' => 'English description',
            'de' => 'Deutsche Beschreibung',
        ], $validatedData['descriptions']);
        self::assertSame([
            'en' => 'SOC alerts',
            'fr' => 'Alertes SOC',
        ], $validatedData['monitoringApproaches']);
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
