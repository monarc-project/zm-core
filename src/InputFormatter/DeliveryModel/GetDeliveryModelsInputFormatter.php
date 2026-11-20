<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\DeliveryModel;

use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetDeliveryModelsInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = ['description1', 'description2', 'description3', 'description4'];

    protected static array $allowedFilterFields = [
        'category' => ['type' => 'int', 'inArray' => [1, 2, 3, 4, 5, 6, 7]],
        'editable' => ['type' => 'int', 'inArray' => [0, 1]],
    ];
}
