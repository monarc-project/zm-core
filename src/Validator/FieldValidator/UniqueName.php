<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;

/**
 * Class UniqueName is an implementation of AbstractValidator that ensures the unicity of name.
 * @package Monarc\Core\Validator
 * @see \Monarc\Core\Model\Entity\MonarcObject
 */
class UniqueName extends AbstractValidator
{
    protected $options = [
        'entity' => null,
        'field' => 'name1',
    ];

    const ALREADY_USED = "ALREADY_USED";

    protected $messageTemplates = [
        self::ALREADY_USED => 'This name is already used',
    ];

    /**
     * @inheritdoc
     */
    public function isValid($value)
    {
        if (empty($this->options['entity'])) {
            return false;
        }

        $entity = $this->options['entity'];
        $fields = [
            $this->options['field'] => $value,
            'anr' => ($entity->anr) ? $entity->anr->id : null,
        ];
        $res = $entity->getDbAdapter()->getRepository(get_class($entity))->findOneBy($fields);

        if (!empty($res) && $entity->getId() != $res->get('id')) {
            $this->error(self::ALREADY_USED);

            return false;
        }

        return true;
    }
}
