<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\Instance;

use Laminas\InputFilter\ArrayInput;
use Laminas\Validator\NotEmpty;
use Monarc\Core\Validator\InputValidator\AbstractInputValidator;

class UpdateInstanceDataInputValidator extends AbstractInputValidator
{
    protected function getRules(): array
    {
        return [
            [
                'name' => 'consequences',
                'required' => true,
                'type' => ArrayInput::class,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                ],
            ],
        ];
    }
}
