<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;

/**
 * Class PasswordStrength is an implementation of AbstractValidator that ensures the strength of passwords.
 * @package Monarc\Core\Validator
 */
class PasswordStrength extends AbstractValidator
{
    const LENGTH = 'length';
    const UPPER  = 'upper';
    const LOWER  = 'lower';
    const DIGIT  = 'digit';
    const SPECIAL  = 'special';

    protected $messageTemplates = array(
        self::LENGTH => "be at least 8 characters in length",
        self::UPPER  => "contain at least one uppercase letter",
        self::LOWER  => "contain at least one lowercase letter",
        self::DIGIT  => "contain at least one digit character",
        self::SPECIAL  => "contain at least one special character"
    );

    /**
     * @inheritdoc
     */
    public function isValid($value)
    {
        $this->setValue($value);

        $isValid = true;

        if (strlen($value) < 8) {
            $this->error(self::LENGTH);
            $isValid = false;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $this->error(self::UPPER);
            $isValid = false;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $this->error(self::LOWER);
            $isValid = false;
        }

        if (!preg_match('/\d/', $value)) {
            $this->error(self::DIGIT);
            $isValid = false;
        }

        if (!preg_match('/[\W_]/', $value)) {
            $this->error(self::SPECIAL);
            $isValid = false;
        }
        return $isValid;
    }
}
