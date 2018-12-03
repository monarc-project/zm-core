<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
      $identifier[] = null;
        if(empty($this->options['entity'])){
            return false;
        }else{
            if (count($this->options['entity']->getDbAdapter()->getClassMetadata(get_class($this->options['entity']))->getIdentifierFieldNames())>1)
              {
                $identifier = $this->options['entity']->getDbAdapter()->getClassMetadata(get_class($this->options['entity']))->getIdentifierFieldNames();
                // foreach ($temp as $key => $value) {
                //   $identifier = $value;
                // }
              }
            else
              $identifier = $this->options['entity']->getDbAdapter()->getClassMetadata(get_class($this->options['entity']))->getSingleIdentifierFieldName();

            $fields = [
                'code' => $value,
            ];
            if(isset($this->options['entity']->anr)){
                $fields['anr'] = (isset($this->options['entity']->anr->id)) ? $this->options['entity']->anr->id : null;
            }
            $res = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findOneBy($fields);
            if(!empty($res) && $this->options['entity']->getId() != $res->get($identifier)){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
