<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\ObjectCategory;

use Doctrine\Common\Collections\Expr\Comparison;
use Monarc\Core\InputFormatter\AbstractInputFormatter;

class ObjectCategoriesInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'label1',
        'label2',
        'label3',
        'label4',
    ];

    public static string $defaultOrderFields = 'position';

    protected static array $allowedFilterFields = [
        'anr',
        'category' => [
            'type' => 'int',
            'fieldName' => 'id',
        ],
        'lock' => [
            'type' => 'boolean',
            'default' => true,
            'isUsedInQuery' => false,
        ],
        'catid' => [
            'type' => 'int',
            'isUsedInQuery' => false,
        ],
        'parentId' => [
            'type' => 'int',
            'isUsedInQuery' => false,
        ],
        'model' => [
            'type' => 'int',
            'isUsedInQuery' => false,
        ],
    ];

    public function prepareCategoryFilter(): void
    {
        $lockFilter = $this->formattedInputParams->getFilterFor('lock');
        $parentIdFilter = $this->formattedInputParams->getFilterFor('parentId');
        $categoryIdFilter = $this->formattedInputParams->getFilterFor('catid');

        $isParentIdFilterEmpty = empty($parentIdFilter['value']);
        if (!empty($categoryIdFilter['value'])) {
            $excludeCategoriesIds = [$categoryIdFilter['value']];
            if (!$isParentIdFilterEmpty) {
                $excludeCategoriesIds[] = $parentIdFilter['value'];
            }
            $this->formattedInputParams->setFilterValueFor(
                'parent',
                $isParentIdFilterEmpty ? null : $parentIdFilter['value']
            );
            $this->formattedInputParams->setFilterFor('id', [
                'value' => $excludeCategoriesIds,
                'operator' => Comparison::NIN,
            ]);
        } elseif (!$isParentIdFilterEmpty) {
            $this->formattedInputParams->setFilterValueFor('parent', $parentIdFilter['value']);
        } elseif (empty($lockFilter['value'])) {
            $this->formattedInputParams->setFilterValueFor('parent', null);
        }
    }
}
