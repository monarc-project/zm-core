<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Amv;

use Doctrine\ORM\Query\Expr\Comparison;
use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\Model\Entity\AmvSuperClass;

class GetAmvsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'asset.code',
        'asset.label1',
        'asset.label2',
        'asset.label3',
        'asset.label4',
        'asset.description1',
        'asset.description2',
        'asset.description3',
        'asset.description4',
        'threat.code',
        'threat.label1',
        'threat.label2',
        'threat.label3',
        'threat.label4',
        'threat.description1',
        'threat.description2',
        'threat.description3',
        'threat.description4',
        'vulnerability.code',
        'vulnerability.label1',
        'vulnerability.label2',
        'vulnerability.label3',
        'vulnerability.label4',
        'vulnerability.description1',
        'vulnerability.description2',
        'vulnerability.description3',
        'vulnerability.description4',
    ];

    protected static array $allowedFilterFields = [
        'anr',
        'asset' => [
            'fieldName' => 'asset.uuid',
        ],
        'amvid' => [
            'fieldName' => 'uuid',
            'operator' => Comparison::NEQ,
        ],
        'status' => [
            'default' => AmvSuperClass::STATUS_ACTIVE,
            'type' => 'int',
        ],
    ];

    protected static array $ignoredFilterFieldValues = ['status' => 'all'];

    protected static array $orderParamsToFieldsMap = ['asset' => 'asset.code'];
}
