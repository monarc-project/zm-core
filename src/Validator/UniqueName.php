<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Class UniqueName is an implementation of AbstractValidator that ensures the unicity of name.
 * @package Monarc\Core\Validator
 * @see Monarc\Core\Model\Entity\MonarcObject
 */
class UniqueName extends AbstractValidator
{
    protected $options = array(
        'entity' => null,
        'field' => 'name1',
    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This name is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value){

        if (empty($this->options['entity'])) {
            return false;
        } else {

            $entity = $this->options['entity'];
            $fields = [
                $this->options['field'] => $value,
                'anr' => ($entity->anr) ? $entity->anr->id : null,
            ];
            $res = $entity->getDbAdapter()->getRepository(get_class($entity))->findOneBy($fields);

            if(!empty($res) && $entity->getId() != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }

        return true;
    }
}
