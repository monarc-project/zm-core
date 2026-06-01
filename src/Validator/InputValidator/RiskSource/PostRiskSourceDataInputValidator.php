<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\RiskSource;

use Laminas\Filter\Callback;
use Laminas\Filter\StringTrim;
use Laminas\Validator\StringLength;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostRiskSourceDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'label',
                'required' => true,
                'allow_empty' => false,
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
                        ],
                    ],
                ],
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
