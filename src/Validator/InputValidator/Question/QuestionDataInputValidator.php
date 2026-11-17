<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Question;

use Monarc\Core\Entity\QuestionSuperClass;
use Laminas\Filter\ToString;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class QuestionDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('type', true, QuestionSuperClass::TYPE_SINGLE, QuestionSuperClass::TYPE_CHOICE),
            $this->getIntegerRule('multichoice', true, 0, 1),
            $this->getIntegerRule('position', false),
            $this->getIntegerRule('implicitPosition', false, 1, 3),
            $this->getIntegerRule('previous', false, 1, self::MAX_INTEGER),
            $this->getTextRule('response', self::MAX_STRING_LENGTH) + ['filters' => [['name' => ToString::class]]],
        ];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $rules[] = $this->getLabelRule($systemLanguageIndex);
        }

        return $rules;
    }
}
