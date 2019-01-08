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
          $update = false;
          $identifier = $this->options['entity']->getDbAdapter()->getClassMetadata(get_class($this->options['entity']))->getIdentifierFieldNames();
          $fields = [
                'code' => $value,
            ];

            if(isset($context['anr'])) //allow to pass in all cases the anr_id in the case of the entity is empty (create)
              $fields['anr'] = $context['anr'];
            if(isset($this->options['entity']->anr->id)){ //if we fetch an anr_id we are updating and not creating
                 $fields['anr'] = (isset($this->options['entity']->anr->id)) ? $this->options['entity']->anr->id : null;
                 $update = true;
             }
            if(isset($context['referential']['uniqid']))
              $referential = $context['referential']['uniqid'];
            if(isset($context['referential']) && !isset($context['anr'])) {//BO
              $referential = $context['referential'];
              $update = true;
            }

            $result = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findBy($fields);

          if($update){
              foreach ($result as $res){
                foreach ($identifier as $key => $value) {
                  if($this->options['entity']->get($value) != $res->get($value)){
                    if($referential == null){
                      $this->error(self::ALREADYUSED);
                      return false;
                    }
                    else if($referential != null && $referential == $res->get('referential')->uniqid ){
                      $this->error(self::ALREADYUSED);
                      return false;
                    }
                  }
                }
              }
            }
          else if(!$update && count($result)>0){ //if create and we find a record in the DB on the same anr it's not correct except if it's a measure and the referential is different
            foreach ($result as $res){
              if($referential == null){
                $this->error(self::ALREADYUSED);
                return false;
              }
              else if($referential != null && $referential == $res->get('referential')->uniqid ){
                $this->error(self::ALREADYUSED);
                return false;
              }
            }
          }
        }
        return true;
    }
}
