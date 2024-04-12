<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Measure;

use Monarc\Core\Entity\MeasureSuperClass;
use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetMeasuresInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'code',
        'label1',
        'label2',
        'label3',
        'label4',
        'category.label1',
        'category.label2',
        'category.label3',
        'category.label4',
    ];

    protected static array $allowedFilterFields = [
        'anr',
        'status' => [
            'default' => MeasureSuperClass::STATUS_ACTIVE,
            'type' => 'int',
        ],
        'referential' => [
            'fieldName' => 'referential.uuid',
        ],
        'includeLinks' => [
            'type' => 'boolean',
            'isUsedInQuery' => false,
        ],
    ];

    protected static array $ignoredFilterFieldValues = ['status' => 'all'];

    protected static array $orderParamsToFieldsMap = [
        'category' => 'category.label{languageIndex}',
    ];
}
