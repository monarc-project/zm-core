<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
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

    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This code is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value, $context=null){ //we put the context option which correspond to the submitted form values
      $identifier[] = null;
        if(empty($this->options['entity'])){
            return false;
        }else{
          $referential = null;
          $identifier = $this->options['entity']->getDbAdapter()->getClassMetadata(get_class($this->options['entity']))->getIdentifierFieldNames();
          $fields = [
                'code' => $value,
            ];

            if(isset($context['anr'])) //allow to pass in all cases the anr_id in the case of the entity is empty (create)
              $fields['anr'] = $context['anr'];
            if(isset($this->options['entity']->anr->id)){ //if we fetch an anr_id we are updating and not creating
                 $fields['anr'] = (isset($this->options['entity']->anr->id)) ? $this->options['entity']->anr->id : null;
             }
            if(isset($context['referential']['uuid']))
              $referential = $context['referential']['uuid'];
            if(isset($context['referential']) && !isset($context['anr'])) {//BO
              $referential = $context['referential'];
            }

            $result = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findBy($fields);

            foreach ($result as $res){
              foreach ($identifier as $key => $value) {
                if($this->options['entity']->get($value) != $res->get($value)){
                  if($referential != null && $referential == $res->get('referential')->uuid ){
                    $this->error(self::ALREADYUSED);
                    return false;
                  }
                }
              }
            }

        }
        return true;
    }
}
