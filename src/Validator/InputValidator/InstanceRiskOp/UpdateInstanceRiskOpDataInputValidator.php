<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\InstanceRiskOp;

use Laminas\Filter\ToInt;
use Laminas\Validator\InArray;
use Monarc\Core\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class UpdateInstanceRiskOpDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'netProb',
                'required' => false,
                'allow_empty' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
            ],
            [
                'name' => 'brutProb',
                'required' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
            [
                'name' => 'targetedProb',
                'required' => false,
                'filters' => [
                    ['name' => ToInt::class],
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
                            'haystack' => array_keys(InstanceRiskOpSuperClass::getAvailableMeasureTypes()),
                        ]
                    ],
                ],
            ],
        ];
    }
}
