<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter\User;

use Monarc\Core\InputFormatter\AbstractInputFormatter;

class GetUsersInputFormatter extends AbstractInputFormatter
{
    protected static array $allowedQueryParams = ['filter', 'status', 'page', 'limit', 'order'];

    /**
     * Formats users input to the format accepted by the table class.
     *
     * @param array $inputData
     *
     * @return array ['search' (optional) => ..., 'filter' => ...., 'order' => ...]
     */
    protected function processInputData(array $inputData): array
    {
        $filteredInputDataParams = $this->filterQueryParams($inputData);

        $formattedData = [];
        if (isset($filteredInputDataParams['filter'])) {
            $formattedData['search'] = [
                'fields' => ['firstname', 'lastname', 'email'],
                'string' => (string)$filteredInputDataParams['filter'],
                'operand' => 'OR',
            ];
        }

        $status = $filteredInputDataParams['status'] ?? 1;
        $formattedData['filter'] = $status === 'all'
            ? null
            : ['status' => (int)$status];

        $formattedData['order'] = [];
        $orderField =  $filteredInputDataParams['order'] ?? null;
        if ($orderField !== null) {
            if (strncmp($orderField, '-', 1) === 0) {
                $formattedData['order'][ltrim($orderField, '-')] = 'DESC';
            } else {
                $formattedData['order'][$orderField] = 'ASC';
            }
        }

        return $formattedData;
    }
}
