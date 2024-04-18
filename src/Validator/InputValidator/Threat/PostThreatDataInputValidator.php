<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Threat;

use Laminas\Filter\Boolean;
use Laminas\Filter\StringTrim;
use Laminas\Filter\ToInt;
use Laminas\Validator\Callback;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;
use Monarc\Core\Entity\ThreatSuperClass;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;
use Monarc\Core\Validator\FieldValidator\UniqueCode;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;
use Monarc\Core\Validator\InputValidator\FilterFieldsValidationTrait;
use Monarc\Core\Validator\InputValidator\InputValidationTranslator;

/**
 * Note. For UniqueCode validator $excludeFilter/$includeFilter properties have to be set before calling isValid method.
 */
class PostThreatDataInputValidator extends AbstractInputValidator
{
    use FilterFieldsValidationTrait;

    public function __construct(
        array $config,
        InputValidationTranslator $translator,
        protected UniqueCodeTableInterface $threatTable
    ) {
        parent::__construct($config, $translator);
    }

    protected function getRules(): array
    {
        $rules = [
            [
                'name' => 'code',
                'required' => true,
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
                    [
                        'name' => UniqueCode::class,
                        'options' => [
                            'uniqueCodeValidationTable' => $this->threatTable,
                            'includeFilter' => $this->includeFilter,
                            'excludeFilter' => $this->excludeFilter,
                        ],
                    ],
                ],
            ],
            [
                'name' => 'c',
                'required' => true,
                'allow_empty' => true,
                'filters' => [
                    ['name' => Boolean::class],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => 'At least one of C I A criteria has to be selected/set.',
                            ],
                            'callback' => function () {
                                return $this->validateCia();
                            },
                        ],
                    ],
                ],
            ],
            [
                'name' => 'i',
                'required' => true,
                'allow_empty' => true,
                'filters' => [
                    ['name' => Boolean::class],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'a',
                'required' => true,
                'allow_empty' => true,
                'filters' => [
                    ['name' => Boolean::class],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [false, true],
                        ]
                    ],
                ],
            ],
            [
                'name' => 'theme',
                'required' => false,
                'filters' => [
                    [
                        'name' => ToInt::class
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'status',
                'required' => false,
                'filters' => [
                    [
                        'name' => ToInt::class
                    ],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [ThreatSuperClass::STATUS_ACTIVE, ThreatSuperClass::STATUS_INACTIVE],
                        ]
                    ],
                ],
            ],

        ];

        $labelDescriptionRules = [];
        foreach ($this->systemLanguageIndexes as $systemLanguageIndex) {
            $labelDescriptionRules[] = $this->getLabelRule($systemLanguageIndex);
            $labelDescriptionRules[] = $this->getDescriptionRule($systemLanguageIndex);
        }

        return array_merge($labelDescriptionRules, $rules);
    }

    public function validateCia(): bool
    {
        return !empty($this->initialData['c']) || !empty($this->initialData['i']) || !empty($this->initialData['a']);
    }
}
