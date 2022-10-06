<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\InputValidator\ObjectComposition;

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
        ];
    }
}
