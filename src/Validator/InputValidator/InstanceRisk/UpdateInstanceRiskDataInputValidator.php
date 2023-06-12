<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\InstanceRisk;

use Laminas\Filter\Callback;
use Laminas\Filter\StringTrim;
use Laminas\Filter\ToInt;
use Laminas\Validator\InArray;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class UpdateInstanceRiskDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'threatRate',
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => Callback::class,
                        'callback' => [\get_class($this), 'filterRate'],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'vulnerabilityRate',
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                    [
                        'name' => Callback::class,
                        'callback' => [\get_class($this), 'filterRate'],
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'comment',
                'required' => false,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
                'validators' => [],
            ],
            [
                'name' => 'kindOfMeasure',
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
                            'haystack' => array_keys(InstanceRiskSuperClass::getAvailableMeasureTypes()),
                        ]
                    ],
                ],
            ],
        ];
    }

    public static function filterRate($param): int
    {
        if ($param === '-') {
            return -1;
        }

        return (int)$param;
    }
}
