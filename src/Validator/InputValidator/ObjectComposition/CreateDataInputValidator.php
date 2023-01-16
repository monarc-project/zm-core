<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
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
                'allowEmpty' => false,
                'continueIfEmpty' => false,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'child',
                'required' => true,
                'allowEmpty' => false,
                'continueIfEmpty' => false,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'implicitPosition',
                'required' => false,
                'allowEmpty' => true,
                'continueIfEmpty' => true,
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
                'allowEmpty' => true,
                'continueIfEmpty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
        ];
    }
}
