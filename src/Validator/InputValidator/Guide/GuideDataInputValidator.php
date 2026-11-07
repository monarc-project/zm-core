<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Guide;

use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class GuideDataInputValidator extends AbstractInputValidator
{
    protected array $integerFields = ['type', 'isWithItems'];

    protected array $defaultValues = ['description1' => null, 'description2' => null,
        'description3' => null, 'description4' => null];

    public function validate(array $data, bool $partial = false): array
    {
        if (isset($data['isWithItems']) && is_bool($data['isWithItems'])) {
            $data['isWithItems'] = (int)$data['isWithItems'];
        }

        return parent::validate($data, $partial);
    }

    protected function getRules(): array
    {
        $rules = [
            $this->getIntegerRule('type', true, 1, 4),
            $this->getIntegerRule('isWithItems', true, 0, 1),
        ];
        foreach ($this->systemLanguageIndexes as $index) {
            $rules[] = $this->getTextRule('description' . $index);
        }

        return $rules;
    }
}
