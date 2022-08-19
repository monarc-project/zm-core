<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Object;

use Laminas\Filter\StringTrim;
use Laminas\I18n\Validator\IsInt;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostObjectDataInputValidator extends AbstractInputValidator
{
    public function __construct(
        InputFilter $inputFilter,
        array $config
    ) {
        parent::__construct($inputFilter, $config);
    }

    protected function getRules(): array
    {
        $rules = [
            [
                'name' => 'scope',
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'required' => false,
                'filters' => [],
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
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [],
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
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => IsInt::class,
                    ]
                ],
            ],
            [
                'name' => 'rolfTag',
                'required' => false,
                'allow_empty' => true,
                'continue_if_empty' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => IsInt::class,
                    ]
                ],
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
