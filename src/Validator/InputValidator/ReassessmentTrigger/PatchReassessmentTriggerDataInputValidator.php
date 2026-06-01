<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\ReassessmentTrigger;

use Laminas\Filter\Callback;
use Laminas\Filter\StringTrim;
use Laminas\Validator\NumberComparison;
use Laminas\Validator\StringLength;
use Monarc\Core\Traits\TranslationNormalizationTrait;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PatchReassessmentTriggerDataInputValidator extends AbstractInputValidator
{
    use TranslationNormalizationTrait;

    public function getValidData(int $validatedSetNum = 0): array
    {
        $validData = parent::getValidData($validatedSetNum);

        foreach (array_keys($validData) as $fieldName) {
            if (!array_key_exists($fieldName, $this->initialData)) {
                unset($validData[$fieldName]);
            }
        }

        return $validData;
    }

    protected function getRules(): array
    {
        return [
            [
                'name' => 'triggerType',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => static function ($value): ?string {
                                $value = trim((string)$value);

                                return $value === '' ? null : $value;
                            },
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'triggerTypes',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslations'],
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'description',
                'required' => false,
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
                        ],
                    ],
                ],
            ],
            [
                'name' => 'descriptions',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslations'],
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'monitoringApproach',
                'required' => false,
                'allow_empty' => true,
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
                        ],
                    ],
                ],
            ],
            [
                'name' => 'monitoringApproaches',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslations'],
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'isActive',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => static function ($value): ?bool {
                                if ($value === null || $value === '') {
                                    return null;
                                }

                                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                            },
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'position',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => static function ($value): ?int {
                                if ($value === null || $value === '') {
                                    return null;
                                }

                                return (int)$value;
                            },
                        ],
                    ],
                ],
                'validators' => [
                    [
                        'name' => NumberComparison::class,
                        'options' => [
                            'min' => 1,
                            'max' => PHP_INT_MAX,
                        ],
                    ],
                ],
            ],
        ];
    }
}
