<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use LogicException;

abstract class AbstractInputFormatter
{
    protected const DEFAULT_SEARCH_LOGICAL_OPERATOR = CompositeExpression::TYPE_OR;

    protected const DEFAULT_FILTER_OPERATOR = Comparison::EQ;

    protected const DEFAULT_LIMIT = 20;

    /** Formatted params response type. */
    protected FormattedInputParams $formattedInputParams;

    /** List of allowed search fields or composition of relation field separated by "." with the destination field. */
    protected static array $allowedSearchFields = [];

    /**
     * Can be optionally defined including the default value and operator, like:
     * ['status' => ['default' => 1, 'operator' => '='], 'name' => ['default' => '', 'operator' => '<>']],
     */
    protected static array $allowedFilterFields = [];

    /**
     * Associative list of ignored filter fields and values, the array key is field name and value is ignored value.
     * Ex. ['status' => 'all']
     */
    protected static array $ignoredFilterFieldValues = [];

    /**
     * Contains a map of passed order params to the exact fields.
     * Ex. ['asset' => 'asset.code', 'threat' => 'threat.label']
     */
    protected static array $orderParamsToFieldsMap = [];

    private array $originalInput = [];

    public function getOriginalInput(): array
    {
        return $this->originalInput;
    }

    public function getFormattedInputParams(): FormattedInputParams
    {
        if ($this->formattedInputParams === null) {
            throw new LogicException('Before calling "getFormattedInputParams", the "format" method should be called.');
        }

        return $this->formattedInputParams;
    }

    public function format(array $inputParams): FormattedInputParams
    {
        $this->originalInput = $inputParams;

        return $this->processInputParams($inputParams);
    }

    protected function processInputParams(array $inputParams): FormattedInputParams
    {
        $this->formattedInputParams = new FormattedInputParams();

        /* Add search filter. */
        if (!empty($inputParams['filter'])) {
            $this->formattedInputParams->setSearch([
                'fields' => static::$allowedSearchFields,
                'string' => (string)$inputParams['filter'],
                'logicalOperator' => static::DEFAULT_SEARCH_LOGICAL_OPERATOR,
            ]);
        }

        /* Add filter params. */
        foreach (static::$allowedFilterFields as $paramName => $paramValues) {
            $field = \is_string($paramValues) ? $paramValues : $paramName;
            $filterFieldName = $paramValues['fieldName'] ?? $field;
            if (isset($inputParams[$field])) {
                $value = $inputParams[$field];
                if (!$this->areFilterFiledAndValueValid($field, $value)) {
                    continue;
                }
                if (isset($inputValues['type'])) {
                    $value = settype($value, $inputValues['type']);
                }

                $this->formattedInputParams->addFilter($filterFieldName, [
                    'value' => $value,
                    'operator' => $paramValues['operator'] ?? static::DEFAULT_FILTER_OPERATOR,
                ]);
            } elseif (isset($paramValues['default'])) {
                $this->formattedInputParams->addFilter($filterFieldName, [
                    'value' => $paramValues['default'],
                    'operator' => $paramValues['operator'] ?? static::DEFAULT_FILTER_OPERATOR,
                ]);
            }
        }

        /* Add order params. */
        $orderFields = $inputParams['order'] ?? null;
        if ($orderFields !== null) {
            $orderFields = \strpos($orderFields, ':') !== false
                ? explode(':', $orderFields)
                : [$orderFields];
            foreach ($orderFields as $orderField) {
                $direction = strncmp($orderField, '-', 1) === 0 ? Criteria::DESC : Criteria::ASC;
                $orderFieldName = ltrim($orderField, '-');
                if (isset(static::$orderParamsToFieldsMap[$orderFieldName])) {
                    $orderFieldName = static::$orderParamsToFieldsMap[$orderFieldName];
                }
                $this->formattedInputParams->addOrder($orderFieldName, $direction);
            }
        }

        /* Set page and limit */
        $this->formattedInputParams->setPage((int)($inputParams['page'] ?? 1));
        $this->formattedInputParams->setLimit((int)($inputParams['limit'] ?? static::DEFAULT_LIMIT));

        return $this->formattedInputParams;
    }

    private function areFilterFiledAndValueValid(string $paramName, $value): bool
    {
        return !isset(static::$ignoredFilterFieldValues[$paramName])
            || static::$ignoredFilterFieldValues[$paramName] !== $value;
    }
}
