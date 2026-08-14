<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\RiskSource;

use Laminas\Filter\Callback;
use Laminas\InputFilter\ArrayInput;
use Monarc\Core\Traits\TranslationNormalizationTrait;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostRiskSourceDataInputValidator extends AbstractInputValidator
{
    use TranslationNormalizationTrait;

    protected function getRules(): array
    {
        return [
            [
                'name' => 'labels',
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
        ];
    }
}
