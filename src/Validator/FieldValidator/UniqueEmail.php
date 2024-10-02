<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Table\UserTable;
use Laminas\Validator\AbstractValidator;

class UniqueEmail extends AbstractValidator
{
    private const ALREADY_USED = 'ALREADY_USED';

    protected $messageTemplates = [
        self::ALREADY_USED => 'This email is already used',
    ];

    public function isValid($value)
    {
        /** @var UserTable $userTable */
        $userTable = $this->getOptions()['userTable'];
        $excludeEmail = !empty($this->getOptions()['excludeEmail']) ? $this->getOptions()['excludeEmail'] : null;

        try {
            $userTable->findByEmail($value, $excludeEmail);
        } catch (EntityNotFoundException $e) {
            return true;
        }

        $this->error(self::ALREADY_USED);

        return false;
    }
}
