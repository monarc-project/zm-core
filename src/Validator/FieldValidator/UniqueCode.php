<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;
use Monarc\Core\Table\Interfaces\UniqueCodeTableInterface;

class UniqueCode extends AbstractValidator
{
    private const ALREADY_USED = 'ALREADY_USED';

    protected $messageTemplates = [
        self::ALREADY_USED => 'The code is unique. Please, specify another value.',
    ];

    public function isValid($value)
    {
        /** @var UniqueCodeTableInterface $uniqueCodeValidationTable */
        $uniqueCodeValidationTable = $this->getOptions()['uniqueCodeValidationTable'];

        /** @var array */
        $includeFilter = !empty($this->getOptions()['includeFilter']) ? $this->getOptions()['includeFilter'] : [];
        /** @var array */
        $excludeFilter = !empty($this->getOptions()['excludeFilter']) ? $this->getOptions()['excludeFilter'] : [];

        if ($uniqueCodeValidationTable->doesCodeAlreadyExist($value, $includeFilter, $excludeFilter)) {
            $this->error(self::ALREADY_USED);

            return false;
        }

        return true;
    }
}
