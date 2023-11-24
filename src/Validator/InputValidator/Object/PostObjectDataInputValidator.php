<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Object;

use Laminas\Filter\StringTrim;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostObjectDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        $rules = [
            [
                'name' => 'scope',
                'required' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [ObjectSuperClass::SCOPE_LOCAL, ObjectSuperClass::SCOPE_GLOBAL],
                        ]
                    ]
                ],
            ],
            [
                'name' => 'mode',
                'required' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [ObjectSuperClass::MODE_GENERIC, ObjectSuperClass::MODE_SPECIFIC],
                        ]
                    ]
                ],
            ],
            [
                'name' => 'asset',
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
            [
                'name' => 'category',
                'required' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
            [
                'name' => 'rolfTag',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
        ];

        $nameRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $nameRules[] = $this->getNameRule($systemLanguageIndex);
        }

        $labelRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelRules[] = $this->getLabelRule($systemLanguageIndex);
        }

        return array_merge($labelRules, $nameRules, $rules);
    }

    protected function getNameRule(int $languageIndex): array
    {
        return [
            'name' => 'name' . $languageIndex,
            'required' => $this->defaultLanguageIndex === $languageIndex,
            'filters' => [
                [
                    'name' => StringTrim::class,
                ],
            ],
            'validators' => [
                [
                    'name' => StringLength::class,
                    'options' => [
                        'min' => 1,
                        'max' => 255,
                    ]
                ],
            ],
        ];
    }
}
