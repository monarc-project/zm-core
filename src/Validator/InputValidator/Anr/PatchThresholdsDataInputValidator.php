<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Anr;

use Laminas\Validator\GreaterThan;
use Laminas\Validator\LessThan;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PatchThresholdsDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'seuil1',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => LessThan::class,
                        'options' => [
                            'inclusive' => true,
                            'max' => (int)($this->initialData['seuil2'] ?? 9999),
                        ]
                    ],
                ],
            ],
            [
                'name' => 'seuil2',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => GreaterThan::class,
                        'options' => [
                            'inclusive' => true,
                            'min' => (int)($this->initialData['seuil1'] ?? 0),
                        ]
                    ],
                ],
            ],
            [
                'name' => 'seuilRolf1',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => LessThan::class,
                        'options' => [
                            'inclusive' => true,
                            'max' => (int)($this->initialData['seuilRolf2'] ?? 9999),
                        ]
                    ],
                ],
            ],
            [
                'name' => 'seuilRolf2',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => GreaterThan::class,
                        'options' => [
                            'inclusive' => true,
                            'min' => (int)($this->initialData['seuilRolf1'] ?? 0),
                        ]
                    ],
                ],
            ],
        ];
    }
}
