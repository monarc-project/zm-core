<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Guide;

class GetGuideItemsInputFormatter extends GetGuidesInputFormatter
{
    protected static array $allowedFilterFields = ['guide' => ['type' => 'int']];

    protected static string $defaultOrderFields = 'position:id';

    protected array $allowedOrderFields = [
        'id', 'guide', 'position', 'description1', 'description2', 'description3', 'description4',
        'createdAt', 'updatedAt', 'creator', 'updater',
    ];
}
