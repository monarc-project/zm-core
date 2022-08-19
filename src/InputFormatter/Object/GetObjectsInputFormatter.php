<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Object;

use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetObjectsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'name1',
        'name2',
        'name3',
        'name4',
        'label1',
        'label2',
        'label3',
        'label4',
    ];

    protected static array $allowedFilterFields = [
        'anr' => [
            'isUsedInQuery' => false,
        ],
        'asset' => [
            'type' => 'string',
            'fieldName' => 'asset.uuid',
        ],
        'category' => [
            'type' => 'int',
            'fieldName' => 'category.id',
        ],
        'lock' => [
            'type' => 'boolean',
            'default' => false,
            'isUsedInQuery' => false,
        ],
        'model' => [
            'type' => 'int',
            'isUsedInQuery' => false,
        ],
    ];
}
