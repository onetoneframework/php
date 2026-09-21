<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Pagination;

/**
 * Class Statics
 *
 * A static pagination class to handle page calculations.
 */
class Statics
{
    private int $page_start;
    private int $page_count;
    private int $list_count;
    private int $document_count;
    private int $remainder_count;

    private bool $needFirstPage = false;

    private int $first_page;
    private int $last_page;

    private int $point = 1;

    /**
     * Constructor
     *
     * @param int $page_start Current page number
     * @param int $document_count Total number of items
     * @param int $page_count Number of items per page
     * @param int $list_count Number of pages to display in the pagination
     */
    public function __construct(int $page_start, int $document_count, int $page_count, int $list_count)
    {
        $this->page_start = (int) $page_start;
        $this->page_count = (int) $page_count;
        $this->list_count = (int) $list_count;
        $this->document_count = (int) $document_count;

        $this->remainder_count = ($this->document_count % $this->page_count);

        $this->first_page = $this->page_start - ($this->page_start % $this->list_count);

        if ($this->page_start % $this->page_count == 0) {
            $this->first_page -= $this->page_count;
        }

        $this->last_page = (int) (($this->document_count - $this->remainder_count) / $this->page_count);

        if ($this->page_start > $this->list_count) {
            $this->needFirstPage = true;
        }
    }

    /**
     * Check if needs go to first page.
     *
     * @return bool
     */
    public function needFirstPage(): bool
    {
        return $this->needFirstPage;
    }

    /**
     * Check if needs go to last page.
     *
     * @return bool
     */
    public function needLastPage(): bool
    {
        return ($this->first_page + $this->list_count) < $this->last_page;
    }

    /**
     * Get last page number.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return $this->last_page + 1;
    }

    /**
     * Get previous page number.
     *
     * @return int
     */
    public function getPreviousPage(): int
    {
        return $this->page_start - 1;
    }

    /**
     * Get next page number.
     *
     * @return int
     */
    public function getNextPage(): int
    {
        return $this->page_start + 1;
    }

    /**
     * Get current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->first_page + ($this->point - 1);
    }

    /**
     * Get page start (active page).
     *
     * @return int
     */
    public function getPageStart(): int
    {
        return $this->page_start;
    }

    /**
     * Set current page number.
     *
     * @param int $page
     * 
     * @return void
     */
    public function setCurrentPage(int $page): void
    {
        $this->point = $page;
    }

    /**
     * @deprecated Use setCurrentPage instead
     */
    public function setCurentPage($page): void
    {
        $this->point = (int) $page;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        $page = $this->first_page + (++$this->point);

        if ($this->point < $this->list_count + 2 && $page - 3 < $this->last_page) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reset iterator point.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->point = 1;
    }

    /**
     * Check if has previous page.
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->page_start > 1;
    }

    /**
     * Check if can go to next page.
     *
     * @return bool
     */
    public function canGoNext(): bool
    {
        return $this->page_start < $this->getLastPage();
    }

    /**
     * Check if given page is active page.
     *
     * @param int $page
     * 
     * @return bool
     */
    public function isCurrentPage(int $page): bool
    {
        return $page === $this->page_start;
    }

    /**
     * Get offset for database query.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->page_start - 1) * $this->page_count;
    }

    /**
     * Get limit for database query.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->page_count;
    }

    /**
     * Get total document count.
     *
     * @return int
     */
    public function getDocumentCount(): int
    {
        return $this->document_count;
    }

    /**
     * Get items per page.
     *
     * @return int
     */
    public function getPageCount(): int
    {
        return $this->page_count;
    }

    /**
     * Get list count.
     *
     * @return int
     */
    public function getListCount(): int
    {
        return $this->list_count;
    }

    /**
     * Get first page in block.
     *
     * @return int
     */
    public function getFirstPage(): int
    {
        return $this->first_page;
    }

    /**
     * Get pagination info array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->page_start,
            'per_page' => $this->page_count,
            'total_items' => $this->document_count,
            'total_pages' => $this->getLastPage(),
            'first_page' => $this->first_page,
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->canGoNext(),
            'offset' => $this->getOffset(),
            'limit' => $this->getLimit()
        ];
    }
}