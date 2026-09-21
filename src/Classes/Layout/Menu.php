<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Layout;

/**
 * Class MenuItem
 *
 * @package Clover\Classes\Layout
 */
class Menu
{
    public array $items = [];

    /**
     * Add a menu item.
     *
     * @param string $title The display title of the menu item.
     * @param string $link The URL or link associated with the menu item.
     * 
     * @return MenuItem
     */
    public function addItem(string $title, string $link): MenuItem
    {
        $item = new MenuItem($title, $link);
        $this->items[] = $item;
        return $item;
    }

    /**
     * Convert the menu to an array representation.
     *
     * @return array<MenuItem> An array of menu items, where each item is represented as an associative array with 'title' and 'link' keys.
     */
    public function toArray(): array
    {
        return array_map(fn($item) => $item->toArray(), $this->items);
    }
}
