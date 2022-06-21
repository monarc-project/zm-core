<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\InputFormatter;

class FormattedInputParams
{
    private array $search = [];
    private array $filter = [];
    private array $order = [];
    private int $page = 1;
    private int $limit = 0;

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    public function getSearch(): array
    {
        return $this->search;
    }

    public function setSearch(array $search): self
    {
        $this->search = $search;

        return $this;
    }

    public function hasFilter(): bool
    {
        return !empty($this->filter);
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function addFilter($field, array $criteria): self
    {
        $this->filter[$field] = $criteria;

        return $this;
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    public function addOrder($field, string $direction): self
    {
        $this->order[$field] = $direction;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = abs($page);

        return $this;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = abs($limit);

        return $this;
    }
}
