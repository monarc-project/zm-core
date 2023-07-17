<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Validator\FieldValidator;

use Laminas\Validator\AbstractValidator;
use Monarc\Core\Model\Entity\DeliveriesModels;

/**
 * Class UniqueDeliveryModel is an implementation of AbstractValidator that ensures the uniqueness of DeliveriesModels
 * based on the category.
 * @package Monarc\Core\Validator
 * @see \Monarc\Core\Model\Entity\DeliveriesModels
 * @see \Monarc\Core\Model\Table\DeliveriesModelsTable
 */
class UniqueDeliveryModel extends AbstractValidator
{
    protected $options = [
        'adapter' => null,
        'category' => 0,
        'id' => 0,
    ];

    private const ALREADY_USED = 'ALREADY_USED';
    private const MAX_BY_CATEGORY_REACHED = 'MAX_BY_CATEGORY_REACHED';

    protected $messageTemplates = [
        self::ALREADY_USED => 'This category is already used.',
        self::MAX_BY_CATEGORY_REACHED => 'Maximum number of templates reached for this category.',
    ];

    /**
     * @inheritdoc
     */
    public function isValid($value)
    {
        if (empty($this->options['adapter'])) {
            return false;
        }

        $res = $this->options['adapter']
            ->getRepository(DeliveriesModels::class)
            ->findByCategory($value);
        if (\count($res) >= 5) {
            $this->error(self::MAX_BY_CATEGORY_REACHED);

            return false;
        }

        return true;
    }
}
