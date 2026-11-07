<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2026 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\Guide;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\AbstractInputFormatter;
use Monarc\Core\InputFormatter\FormattedInputParams;

class GetGuidesInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedSearchFields = ['description1', 'description2', 'description3', 'description4'];

    protected static array $allowedFilterFields = ['type' => ['type' => 'int', 'inArray' => [1, 2, 3, 4]]];

    protected static string $defaultOrderFields = 'id';

    protected array $allowedOrderFields = [
        'id', 'type', 'isWithItems', 'description1', 'description2', 'description3', 'description4',
        'createdAt', 'updatedAt', 'creator', 'updater',
    ];

    public function format(array $inputParams): FormattedInputParams
    {
        foreach (['page', 'limit', 'guide', 'type'] as $field) {
            if (isset($inputParams[$field])
                && ((!is_int($inputParams[$field])
                    && !(is_string($inputParams[$field]) && ctype_digit($inputParams[$field])))
                    || $inputParams[$field] < (in_array($field, ['guide', 'type'], true) ? 1 : 0))
            ) {
                throw new Exception('Invalid query parameter: ' . $field, 412);
            }
        }
        if (isset($inputParams['filter']) && !is_string($inputParams['filter'])) {
            throw new Exception('Invalid query parameter: filter', 412);
        }
        if (isset($inputParams['order'])) {
            if (!is_string($inputParams['order'])) {
                throw new Exception('Invalid query parameter: order', 412);
            }
            foreach (explode(':', $inputParams['order']) as $field) {
                if (!in_array(ltrim($field, '-'), $this->allowedOrderFields, true)) {
                    throw new Exception('Invalid order field', 412);
                }
            }
        }

        return parent::format($inputParams);
    }
}
