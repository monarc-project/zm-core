<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Traits;

trait QueryParamsFormatterTrait
{
    protected function getFormattedFilterParams(string $searchString, array $filter = []): array
    {
        $params = [];
        $searchFields = $this->getSearchFields();
        if ($searchString !== '' && !empty($searchFields)) {
            $params['search'] = [
                'fields' => $searchFields,
                'string' => $searchString,
                'operand' => 'OR',
            ];
        }
        if (!empty($filter)) {
            $params['filter'] = $filter;
        }

        return $params;
    }

    protected function getFormattedOrder(string $orderField): array
    {
        $order = [];
        if ($orderField !== '') {
            if (strncmp($orderField, '-', 1) === 0) {
                $order[ltrim($orderField, '-')] = 'DESC';
            } else {
                $order[$orderField] = 'ASC';
            }
        }

        return $order;
    }

    protected function getSearchFields(): array
    {
        return property_exists($this, 'searchFields') ? self::$searchFields : [];
    }
}
