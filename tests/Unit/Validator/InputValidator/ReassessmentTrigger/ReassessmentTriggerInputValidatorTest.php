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
    public function testPostValidatorTrimsDescriptionAndDefaultsIsActive(): void
    {
        $validator = new PostReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerType' => 'security_incident',
            'description' => '  Reassess after a critical incident.  ',
            'position' => '2',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame('security_incident', $validatedData['triggerType']);
        self::assertSame('Reassess after a critical incident.', $validatedData['description']);
        self::assertTrue($validatedData['isActive']);
        self::assertSame(2, $validatedData['position']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PostReassessmentTriggerDataInputValidator::getRules
     */
    public function testPostValidatorAllowsOptionalMonitoringApproach(): void
    {
        $validator = new PostReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerType' => 'made_up_type',
            'description' => 'Valid trigger type',
            'monitoringApproach' => '  Monitor regulatory updates and supplier notices.  ',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame('Monitor regulatory updates and supplier notices.', $validatedData['monitoringApproach']);
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
            'triggerType' => 'System change',
            'triggerTypes' => [
                'en' => 'System change',
                'fr' => 'Changement du systeme',
            ],
            'description' => 'English description',
            'descriptions' => [
                'en' => 'English description',
                'de' => 'Deutsche Beschreibung',
            ],
            'monitoringApproach' => 'SOC alerts',
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
    public function testPatchValidatorAcceptsPartialPayloadAndAllowsNullType(): void
    {
        $validator = new PatchReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'triggerType' => ' ',
            'isActive' => '0',
            'position' => '4',
            'monitoringApproach' => '  SOC alerts  ',
        ]));

        $validatedData = $validator->getValidData();
        self::assertNull($validatedData['triggerType']);
        self::assertFalse($validatedData['isActive']);
        self::assertSame(4, $validatedData['position']);
        self::assertSame('SOC alerts', $validatedData['monitoringApproach']);
    }

    /**
     * @covers \Monarc\Core\Validator\InputValidator\ReassessmentTrigger\PatchReassessmentTriggerDataInputValidator::getValidData
     */
    public function testPatchValidatorDoesNotReturnMissingOptionalFields(): void
    {
        $validator = new PatchReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertTrue($validator->isValid([
            'position' => '4',
        ]));

        $validatedData = $validator->getValidData();
        self::assertSame(['position' => 4], $validatedData);
        self::assertArrayNotHasKey('isActive', $validatedData);
        self::assertArrayNotHasKey('triggerType', $validatedData);
        self::assertArrayNotHasKey('description', $validatedData);
        self::assertArrayNotHasKey('monitoringApproach', $validatedData);
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
