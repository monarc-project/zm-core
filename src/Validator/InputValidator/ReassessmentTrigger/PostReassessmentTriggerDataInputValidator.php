<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\ReassessmentTrigger;

use Laminas\Filter\Callback;
use Laminas\InputFilter\ArrayInput;
use Laminas\Validator\NumberComparison;
use Monarc\Core\Traits\TranslationNormalizationTrait;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostReassessmentTriggerDataInputValidator extends AbstractInputValidator
{
    use TranslationNormalizationTrait;

    protected function getRules(): array
    {
        return [
            [
                'name' => 'triggerTypes',
                'required' => true,
                'allow_empty' => false,
                'type' => ArrayInput::class,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslationValue'],
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'descriptions',
                'required' => true,
                'allow_empty' => false,
                'type' => ArrayInput::class,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslationValue'],
                        ],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'monitoringApproaches',
                'required' => true,
                'allow_empty' => false,
                'type' => ArrayInput::class,
                'filters' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => [$this, 'normalizeTranslationValue'],
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
                            'callback' => static function ($value): bool {
                                if ($value === null || $value === '') {
                                    return true;
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
