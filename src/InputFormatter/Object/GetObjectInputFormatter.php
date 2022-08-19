<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Object;

use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\Service\ObjectService;

class GetObjectInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedFilterFields = [
        'anr',
        'mode' => [
            'default' => ObjectService::MODE_OBJECT_EDIT,
            'inArray' => [ObjectService::MODE_OBJECT_EDIT, ObjectService::MODE_ANR, ObjectService::MODE_KNOWLEDGE_BASE],
            'type' => 'string',
            'isUsedInQuery' => false,
        ],
    ];
}
