<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\ObjectComposition;

use Laminas\Validator\InArray;
use Monarc\Core\Service\ObjectObjectService;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class MovePositionDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'move',
                'required' => true,
                'filters' => [],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [
                                ObjectObjectService::MOVE_COMPOSITION_POSITION_UP,
                                ObjectObjectService::MOVE_COMPOSITION_POSITION_DOWN
                            ],
                        ]
                    ]
                ],
            ],
        ];
    }
}
