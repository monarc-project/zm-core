<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;
/**
 * Class UniqueCode is an implementation of AbstractValidator that ensures the unicity of element based on the code field.
 * @package MonarcCore\Validator
 * @see MonarcCore\Model\Entity\Asset
 * @see MonarcCore\Model\Entity\Measure
 * @see MonarcCore\Model\Entity\RolfRisk
 * @see MonarcCore\Model\Entity\RolfTag
 * @see MonarcCore\Model\Entity\Threat
 * @see MonarcCore\Model\Entity\Vulnerability
 */
class UniqueCode extends AbstractValidator
{
    protected $options = array(
        'entity' => null,
    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This code is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value){

        if(empty($this->options['entity'])){
            return false;
        }else{
            $fields = [
                'code' => $value,
            ];
            if(isset($this->options['entity']->anr)){
                $fields['anr'] = (isset($this->options['entity']->anr->id)) ? $this->options['entity']->anr->id : null;
            }
            $res = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findOneBy($fields);
            if(!empty($res) && $this->options['entity']->getId() != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
