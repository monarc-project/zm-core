<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Table\UserTable;
use Laminas\Validator\AbstractValidator;

class UniqueEmail extends AbstractValidator
{
    private const ALREADY_USED = "ALREADY_USED";

    protected $messageTemplates = array(
        self::ALREADY_USED => 'This email is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value)
    {
        /** @var UserTable $userTable */
        $userTable = $this->getOptions()['userTable'];

        try {
            $userTable->findByEmail($value);
        } catch (EntityNotFoundException $e) {
            return true;
        }

        $this->error(self::ALREADY_USED);

        return false;
    }
}
