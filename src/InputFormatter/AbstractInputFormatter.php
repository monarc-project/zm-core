<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use LogicException;
use Monarc\Core\Exception\Exception;

abstract class AbstractInputFormatter
{
    protected const DEFAULT_SEARCH_LOGICAL_OPERATOR = CompositeExpression::TYPE_OR;

    protected const DEFAULT_FILTER_OPERATOR = Comparison::EQ;

    protected const DEFAULT_LIMIT = 20;

    /** Formatted params response type. */
    protected ?FormattedInputParams $formattedInputParams = null;

    /**
     * List of allowed search fields or composition of relation field separated by "." with the destination field.
     * There is a special placeholder available {languageIndex} to specify that language index has to be added.
     *  example: 'asset.label{languageIndex}'
     */
    protected static array $allowedSearchFields = [];

    /**
     * Can be optionally defined including the default value and operator, like:
     * [
     * 'status' => ['default' => 1, 'operator' => '='],
     * 'name' => ['default' => '', 'operator' => '<>', 'type' => 'string'],
     * 'mode' => ['default' => 'anr', 'inArray' => ['anr', 'edit']]
     * ],
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

    /**
     * The default order fields string.
     * Ex. '-position:name:-date'. '-' means descending order, ':' fields separation.
     */
    protected static string $defaultOrderFields = '';

    protected int $defaultLanguageIndex = 1;

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

    public function setDefaultLanguageIndex(int $languageIndex): void
    {
        $this->defaultLanguageIndex = $languageIndex;
    }

    protected function processInputParams(array $inputParams): FormattedInputParams
    {
        $this->formattedInputParams = new FormattedInputParams();

        /* Add search filter. */
        if (!empty($inputParams['filter']) && !empty(static::$allowedSearchFields)) {
            $searchFields = array_map(function ($field) {
                return strpos('{languageIndex}', $field) !== false
                    ? str_replace('{languageIndex}', (string)$this->defaultLanguageIndex, $field)
                    : $field;
            }, static::$allowedSearchFields);

            $this->formattedInputParams->setSearch([
                'fields' => $searchFields,
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
                if (isset($paramValues['convert']['value'], $paramValues['convert']['to']['value'])
                    && $paramValues['convert']['value'] === $value
                ) {
                    $value = $paramValues['convert']['to']['value'];
                    $paramValues['operator'] = $paramValues['convert']['to']['operator'] ?? $paramValues['operator'];
                }
                if (isset($paramValues['type'])) {
                    if ($paramValues['type'] === 'boolean') {
                        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    } else {
                        settype($value, $paramValues['type']);
                    }
                }
                if (isset($paramValues['inArray']) && !\in_array($value, $paramValues['inArray'], true)) {
                    throw new Exception(sprintf('Param "%s" is not allowed to have value "%s".', $field, $value), 412);
                }

                $this->formattedInputParams->addFilter($filterFieldName, [
                    'value' => $value,
                    'operator' => $paramValues['operator'] ?? static::DEFAULT_FILTER_OPERATOR,
                    'isUsedInQuery' => $paramValues['isUsedInQuery'] ?? true,
                ]);
            } elseif (isset($paramValues['default'])) {
                $this->formattedInputParams->addFilter($filterFieldName, [
                    'value' => $paramValues['default'],
                    'operator' => $paramValues['operator'] ?? static::DEFAULT_FILTER_OPERATOR,
                    'isUsedInQuery' => $paramValues['isUsedInQuery'] ?? true,
                ]);
            }
        }

        /* Add order params. */
        $orderFields = $inputParams['order'] ?? static::$defaultOrderFields;
        if (!empty($orderFields)) {
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
