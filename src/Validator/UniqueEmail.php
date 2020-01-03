<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Table\UserTable;
use Zend\Validator\AbstractValidator;

/**
 * Class UniqueEmail is an implementation of AbstractValidator that ensures the unicity of email.
 * @package Monarc\Core\Validator
 * @see Monarc\Core\Model\Entity\User
 */
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
            $userTable->getByEmailAndNotUserId($value, $this->getOptions()['currentUserId']);
        } catch (Exception $e) {
            return true;
        }

        $this->error(self::ALREADYUSED);

        return false;
    }
}
