<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Question;

use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class QuestionChoiceDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('question', true, 1, self::MAX_INTEGER),
            $this->getIntegerRule('position', false),
        ];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $rules[] = $this->getLabelRule($systemLanguageIndex);
        }

        return $rules;
    }
}
