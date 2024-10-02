<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\ObjectComposition;

use Laminas\Validator\InArray;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class CreateDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'parent',
                'required' => true,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'child',
                'required' => true,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'implicitPosition',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [
                                PositionUpdatableServiceInterface::IMPLICIT_POSITION_START,
                                PositionUpdatableServiceInterface::IMPLICIT_POSITION_END,
                                PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER
                            ],
                        ]
                    ]
                ],
            ],
            [
                'name' => 'previous',
                'required' => false,
                'allow_empty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
        ];
    }
}
