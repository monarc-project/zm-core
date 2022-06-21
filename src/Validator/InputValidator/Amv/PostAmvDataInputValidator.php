<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Amv;

use Laminas\Validator\StringLength;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class PostAmvDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'asset',
                'required' => true,
                'filters' => [
                ],
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
                'name' => 'threat',
                'required' => true,
                'filters' => [
                ],
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
                'name' => 'vulnerability',
                'required' => true,
                'filters' => [
                ],
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
        ];
    }
}
