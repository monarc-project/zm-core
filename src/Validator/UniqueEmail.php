<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Table\UserTable;
use Laminas\Validator\AbstractValidator;

class UniqueEmail extends AbstractValidator
{
    private const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This email is already used',
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

        $this->error(self::ALREADYUSED);

        return false;
    }
}
