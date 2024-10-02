<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\InstanceRiskOp;

use Laminas\Filter\ToInt;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PatchInstanceRiskOpDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'instanceRiskScaleId',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
            [
                'name' => 'brutValue',
                'required' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
            [
                'name' => 'netValue',
                'required' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
            [
                'name' => 'targetedValue',
                'required' => false,
                'filters' => [
                    ['name' => ToInt::class],
                ],
                'validators' => [],
            ],
        ];
    }
}
