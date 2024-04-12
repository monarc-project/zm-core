<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\SoaCategory;

use Laminas\Validator\StringLength;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostSoaCategoryDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        $rules = [
            [
                'name' => 'referential',
                'required' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 36,
                            'max' => 36,
                        ]
                    ],
                ],
            ],
        ];

        $labelRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelRules[] = $this->getLabelRule($systemLanguageIndex);
        }

        return array_merge($rules, $labelRules);
    }
}
