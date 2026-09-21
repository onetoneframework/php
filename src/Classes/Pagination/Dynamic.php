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
 * Class Dynamic
 *
 * A dynamic pagination class to handle page calculations.
 */
class Dynamic
{
    private int $current_page;
    private int $list_count;
    private int $page_count;
    private int $point = 0;
    private int $page_margin = 0;
    private int $first_page = 0;
    private int $last_page;
    private int $item_count;
    private int $document_count;

    /**
     * Constructor
     *
     * @param int $current_page Current page number
     * @param int $item_count Number of items per page
     * @param int $document_count Total number of items
     * @param int $list_count Number of pages to display in the pagination
     */
    public function __construct(int $current_page = 1, int $item_count = 20, int $document_count = 10, int $list_count = 10)
    {
        $page_margin = 0;
        $first_page = 0;

        $half_page_count = ceil($list_count / 2);

        $last_page = ceil($document_count / $item_count);
        $last_page = ($last_page < 0) ? 1 : $last_page;

        if ($last_page > $list_count) {
            if ($current_page > $last_page - ($list_count - 1)) {
                $page_margin = $last_page - $list_count;
                $first_page = $page_margin < $list_count ? 0 : -1;
            } else if ($current_page > $half_page_count) {
                $page_margin = $current_page - ($half_page_count);
                $first_page = $page_margin > $list_count ? 0 : -1;
            }

            if ($current_page > $last_page - ($list_count - 1) && $current_page < $last_page - ($half_page_count - 1)) {
                $page_margin = $current_page - $half_page_count;
                $first_page = $page_margin > $list_count ? 0 : -1;
            }
        }

        $this->page_count = (int) $last_page;
        $this->page_margin = (int) $page_margin;
        $this->first_page = (int) $first_page;
        $this->last_page = (int) $last_page;
        $this->current_page = (int) $current_page;
        $this->list_count = (int) $list_count;
        $this->item_count = (int) $item_count;
        $this->document_count = (int) $document_count;
    }

    /**
     * Get last page number.
     *
     * @return int
     */
    public function getLastPage(): int
    {
        return $this->page_count;
    }

    /**
     * Check if there is a next page.
     *
     * @return bool
     */
    public function hasNextPage(): bool
    {
        $page = $this->first_page + (++$this->point);

        if ($page > ($this->list_count) || $this->getCurrentPage() > $this->last_page) {
            $this->point = 0;

            return false;
        }

        return true;
    }

    /**
     * Get current page number.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return ($this->page_margin + $this->first_page + $this->point);
    }

    /**
     * Get active page number.
     *
     * @return int
     */
    public function getActivePage(): int
    {
        return $this->current_page;
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
     * Reset iterator point.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->point = 0;
    }

    /**
     * Get previous page number.
     *
     * @return int
     */
    public function getPreviousPage(): int
    {
        return max(1, $this->current_page - 1);
    }

    /**
     * Get next page number.
     *
     * @return int
     */
    public function getNextPage(): int
    {
        return min($this->last_page, $this->current_page + 1);
    }

    /**
     * Check if has previous page.
     *
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->current_page > 1;
    }

    /**
     * Check if can go to next page.
     *
     * @return bool
     */
    public function canGoNext(): bool
    {
        return $this->current_page < $this->last_page;
    }

    /**
     * Check if given page is active page.
     *
     * @param int $page
     * 
     * @return bool
     */
    public function isActivePage(int $page): bool
    {
        return $page === $this->current_page;
    }

    /**
     * Get offset for database query.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->current_page - 1) * $this->item_count;
    }

    /**
     * Get limit for database query.
     *
     * @return int
     */
    public function getLimit(): int
    {
        return $this->item_count;
    }

    /**
     * Get items per page.
     *
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->item_count;
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
     * Get list count.
     *
     * @return int
     */
    public function getListCount(): int
    {
        return $this->list_count;
    }

    /**
     * Get pagination info array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'current_page' => $this->current_page,
            'per_page' => $this->item_count,
            'total_items' => $this->document_count,
            'total_pages' => $this->last_page,
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->canGoNext(),
            'offset' => $this->getOffset(),
            'limit' => $this->getLimit()
        ];
    }

    /**
     * Create from request parameters.
     *
     * @param int $document_count
     * @param int $item_count
     * @param int $list_count
     * @param string $page_param
     * 
     * @return static
     */
    public static function fromRequest(
        int $document_count,
        int $item_count = 20,
        int $list_count = 10,
        string $page_param = 'page'
    ): static {
        $current_page = (int) ($_GET[$page_param] ?? 1);

        return new static($current_page, $item_count, $document_count, $list_count);
    }
}