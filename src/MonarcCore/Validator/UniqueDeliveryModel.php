<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;
/**
 * Class UniqueDeliveryModel is an implementation of AbstractValidator that ensures the unicity of DeliveriesModels based on the category.
 * @package MonarcCore\Validator
 * @see MonarcCore\Model\Entity\DeliveriesModels
 * @see MonarcCore\Model\Table\DeliveriesModelsTable
 */
class UniqueDeliveryModel extends AbstractValidator
{
    protected $options = array(
        'adapter' => null,
        'category' => 0,
        'id' => 0,
    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This category is already used',
    );

    /**
     * @inheritdoc
     */
    public function isValid($value){
        if(empty($this->options['adapter'])){
            return false;
        }else{
            $res = $this->options['adapter']->getRepository('\MonarcCore\Model\Entity\DeliveriesModels')->findOneByCategory($value);
            if(!empty($res) && $this->options['id'] != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
