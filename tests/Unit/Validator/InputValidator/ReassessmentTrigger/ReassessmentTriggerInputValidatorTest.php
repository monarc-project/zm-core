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
    public function testPostValidatorRejectsUnknownTriggerType(): void
    {
        $validator = new PostReassessmentTriggerDataInputValidator(
            ['defaultLanguageIndex' => 1],
            $this->createTranslator()
        );

        self::assertFalse($validator->isValid([
            'triggerType' => 'made_up_type',
            'description' => 'Invalid trigger type',
        ]));
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
        ]));

        $validatedData = $validator->getValidData();
        self::assertNull($validatedData['triggerType']);
        self::assertFalse($validatedData['isActive']);
        self::assertSame(4, $validatedData['position']);
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
