<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Tests\Unit\Validator\InputValidator;

use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;
use PHPUnit\Framework\TestCase;

/** @coversNothing */
class AbstractInputValidatorTest extends TestCase
{
    public function testMissingOptionalNullsAreOmittedWithoutDroppingFalsyValuesOrDefaults(): void
    {
        $validator = $this->validator();
        $data = ['count' => 0, 'enabled' => false, 'text' => '', 'items' => []];

        self::assertTrue($validator->isValid($data));
        self::assertSame($data + ['fallback' => 0], $validator->getValidData());
    }

    public function testExplicitNullRemainsAvailableForClearingAField(): void
    {
        $validator = $this->validator();

        self::assertTrue($validator->isValid(['optional' => null]));
        self::assertSame(['optional' => null, 'fallback' => 0], $validator->getValidData());
    }

    public function testEachBatchRowKeepsItsOwnFieldPresence(): void
    {
        $validator = $this->validator();
        self::assertTrue($validator->isValid(['optional' => null, 'count' => 0]));
        self::assertTrue($validator->isValid(['text' => 'Second row']));

        $expected = [
            ['optional' => null, 'count' => 0, 'fallback' => 0],
            ['text' => 'Second row', 'fallback' => 0],
        ];
        self::assertSame($expected, $validator->getValidDataSets());
        self::assertSame($expected[0], $validator->getValidData(0));
        self::assertSame($expected[1], $validator->getValidData(1));
    }

    private function validator(): AbstractInputValidator
    {
        return new class ([], $this->createMock(InputValidationTranslator::class)) extends AbstractInputValidator {
            protected function getRules(): array
            {
                return [
                    ['name' => 'optional', 'required' => false, 'allow_empty' => true],
                    ['name' => 'count', 'required' => false, 'allow_empty' => true],
                    ['name' => 'enabled', 'required' => false, 'allow_empty' => true],
                    ['name' => 'text', 'required' => false, 'allow_empty' => true],
                    ['name' => 'items', 'required' => false, 'allow_empty' => true],
                    ['name' => 'fallback', 'required' => true, 'fallback_value' => 0],
                ];
            }
        };
    }
}
