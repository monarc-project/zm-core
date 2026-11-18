<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\DeliveryModel;

use Monarc\Core\Validator\FieldValidator\DeliveryModelUpload;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class DeliveryModelDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('category', true, 1, 7),
            $this->getIntegerRule('editable', false, 0, 1),
            [
                'name' => 'file',
                'required' => false,
                'allow_empty' => true,
                'validators' => [['name' => DeliveryModelUpload::class]],
            ],
        ];
        // A new record needs a category; updates preserve omitted fields.
        foreach ($this->systemLanguageIndexes as $index) {
            $rules[] = $this->getTextRule('description' . $index);
        }

        return $rules;
    }
}
