<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Class UniqueEmail is an implementation of AbstractValidator that ensures the unicity of email.
 * @package Monarc\Core\Validator
 * @see Monarc\Core\Model\Entity\User
 */
class UniqueEmail extends AbstractValidator
{
    protected $options = array(
        'adapter' => null,
        'id' => 0,
    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This email is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value){

        if(empty($this->options['adapter'])){
            return false;
        }else{
            $res = $this->options['adapter']->getRepository('\Monarc\Core\Model\Entity\User')->findOneByEmail($value);
            if(!empty($res) && $this->options['id'] != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
