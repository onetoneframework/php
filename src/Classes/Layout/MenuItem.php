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
class MenuItem
{
    /* @var string $title The display title of the menu item. */
    public string $title;
    /* @var string $link The URL or link associated with the menu item. */
    public string $link;
    /* @var array<MenuItem> $children An array of child menu items, allowing for nested menu structures. */
    public array $children = [];

    /**
     * MenuItem constructor.
     *
     * @param string $title The display title of the menu item.
     * @param string $link The URL or link associated with the menu item.
     */
    public function __construct(string $title, string $link)
    {
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * Add a child menu item.
     *
     * @param string $title The display title of the child menu item.
     * @param string $link The URL or link associated with the child menu item.
     * @return $this
     */
    public function addChild(string $title, string $link): self
    {
        $this->children[] = new MenuItem($title, $link);
        return $this;
    }

    /**
     * Convert the menu item to an array representation.
     *
     * @return array{title: string, link: string, children: array<MenuItem>} An associative array representing the menu item, including its title, link, and any child menu items.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'link' => $this->link,
            'children' => array_map(fn($child) => $child->toArray(), $this->children)
        ];
    }
}
