<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Class UniqueEmail is an implementation of AbstractValidator that ensures the unicity of email.
 * @package MonarcCore\Validator
 * @see MonarcCore\Model\Entity\User
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
            $res = $this->options['adapter']->getRepository('\MonarcCore\Model\Entity\User')->findOneByEmail($value);
            if(!empty($res) && $this->options['id'] != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
