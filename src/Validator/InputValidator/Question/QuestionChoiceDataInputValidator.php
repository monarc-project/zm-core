<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Question;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class QuestionChoiceDataInputValidator extends AbstractInputValidator
{
    protected array $integerFields = ['question', 'position'];

    protected array $defaultValues = ['label1' => null, 'label2' => null, 'label3' => null, 'label4' => null];

    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('question', true, 1, self::MAX_INTEGER),
            $this->getIntegerRule('position', false),
        ];
        foreach ($this->systemLanguageIndexes as $index) {
            $rules[] = $this->getTextRule('label' . $index, self::MAX_STRING_LENGTH);
        }

        return $rules;
    }

    public function validateReplacement(array $data): array
    {
        $questionId = $this->validateIds([$data['questionId'] ?? null])[0];
        $choices = array_key_exists('choice', $data) ? $data['choice'] : [];
        if (!is_array($choices) || !array_is_list($choices) || count($choices) > self::MAX_UNSIGNED_SMALLINT) {
            throw new Exception('Invalid choice list', 412);
        }
        foreach ($choices as $index => $choice) {
            if (!is_array($choice) || ($choice !== [] && array_is_list($choice))) {
                throw new Exception('Invalid choice list', 412);
            }
            // Only content is reused; questionId and list order determine the new associations and positions.
            $choices[$index] = $this->validate(array_merge($choice, [
                'question' => $questionId, 'position' => $index + 1,
            ]));
        }

        return ['questionId' => $questionId, 'choice' => $choices];
    }
}
