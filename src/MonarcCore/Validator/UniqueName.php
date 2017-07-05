<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

/**
 * Class UniqueName is an implementation of AbstractValidator that ensures the unicity of name.
 * @package MonarcCore\Validator
 * @see MonarcCore\Model\Entity\Object
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
