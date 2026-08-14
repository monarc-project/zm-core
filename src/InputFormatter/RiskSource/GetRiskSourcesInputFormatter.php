<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\RiskSource;

use Doctrine\Common\Collections\Expr\Comparison;
use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetRiskSourcesInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = [
        'label',
    ];

    protected static array $allowedFilterFields = [
        'anr',
        'status' => [
            'fieldName' => 'isActive',
            'default' => true,
            'operator' => Comparison::EQ,
            'type' => 'boolean',
            'convert' => [
                'value' => 'active',
                'to' => [
                    'value' => true,
                ],
            ],
        ],
    ];

    protected static array $ignoredFilterFieldValues = [
        'status' => 'all',
    ];

    protected static string $defaultOrderFields = '-isDefault:label';
}
