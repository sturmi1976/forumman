<?php

declare(strict_types=1);

namespace Lanius\Forumman\Pagination;

use TYPO3\CMS\Core\Pagination\PaginatorInterface;

class ElasticsearchPaginator implements PaginatorInterface
{
    public function __construct(
        protected array $items,
        protected int $totalItems,
        protected int $currentPage,
        protected int $itemsPerPage
    ) {}

    public function getCurrentPageNumber(): int
    {
        return $this->currentPage;
    }

    public function withCurrentPageNumber(int $pageNumber): static
    {
        return new self(
            $this->items,
            $this->totalItems,
            $pageNumber,
            $this->itemsPerPage
        );
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function withItemsPerPage(int $itemsPerPage): static
    {
        return new self(
            $this->items,
            $this->totalItems,
            $this->currentPage,
            $itemsPerPage
        );
    }

    public function getNumberOfPages(): int
    {
        return (int)ceil($this->totalItems / $this->itemsPerPage);
    }

    public function getPaginatedItems(): iterable
    {
        return $this->items;
    }

    public function getKeyOfFirstPaginatedItem(): int
    {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    public function getKeyOfLastPaginatedItem(): int
    {
        return $this->getKeyOfFirstPaginatedItem() + count($this->items) - 1;
    }

    public function count(): int
    {
        return $this->totalItems;
    }
}
