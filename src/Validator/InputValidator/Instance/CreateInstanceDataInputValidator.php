<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Instance;

use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class CreateInstanceDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'object',
                'required' => true,
                'filters' => [],
                'validators' => [],
            ],
            [
                'name' => 'parent',
                'required' => true,
                'allow_empty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
            [
                'name' => 'position',
                'required' => true,
                'allow_empty' => true,
                'filters' => [
                    ['name' => 'ToInt'],
                ],
                'validators' => [],
            ],
        ];
    }
}
