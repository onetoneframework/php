<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\CLI\Component;

use Clover\Classes\CLI\TerminalUI;
use InvalidArgumentException;
use RuntimeException;
use function array_values;
use function is_string;
use function preg_match;

/**
 * Selects a terminal menu item from SGR mouse input.
 */
final class MouseSelector
{
	private const CURSOR_HOME_SEQUENCE = "\033[H";
	private const ENABLE_MOUSE_TRACKING_SEQUENCE = "\033[?1000h\033[?1006h";
	private const DISABLE_MOUSE_TRACKING_SEQUENCE = "\033[?1000l\033[?1006l";
	private const MOUSE_EVENT_PATTERN = '/^\e\[<(?<button>\d+);\d+;(?<row>\d+)(?<state>[mM])$/';
	private const MOUSE_BUTTON_PRESS_STATE = 'M';
	private const PRIMARY_MOUSE_BUTTON = 0;
	private const FIRST_ITEM_INDEX = 0;
	private const FIRST_ITEM_ROW = 1;

	/**
	 * @var list<string>
	 */
	private array $items;

	private TerminalUI $terminalUi;

	private int $selectedIndex = self::FIRST_ITEM_INDEX;

	/**
	 * Creates a selector for a non-empty list of labels.
	 *
	 * @param list<string> $items
	 */
	public function __construct(array $items, TerminalUI $terminalUi)
	{
		if ($items === []) {
			throw new InvalidArgumentException('Mouse selector items cannot be empty.');
		}

		foreach ($items as $item) {
			if (!is_string($item)) {
				throw new InvalidArgumentException('Mouse selector items must be strings.');
			}
		}

		$this->items = array_values($items);
		$this->terminalUi = $terminalUi;
	}

	/**
	 * Display the items and return the label selected with the primary mouse button.
	 */
	public function show(): string
	{
		$this->selectedIndex = self::FIRST_ITEM_INDEX;
		$this->terminalUi->clearScreen();
		$this->terminalUi->hideCursor();
		echo self::ENABLE_MOUSE_TRACKING_SEQUENCE;

		try {
			$this->draw();

			// The loop ends when a valid item is clicked or terminal input fails.
			while (true) {
				$input = $this->terminalUi->getKey();
				if (!is_string($input) || $input === '') {
					throw new RuntimeException('Unable to read mouse selector input.');
				}

				$matches = [];
				if (preg_match(self::MOUSE_EVENT_PATTERN, $input, $matches) !== 1) {
					continue;
				}

				$mouseButton = (int) $matches['button'];
				if ($mouseButton !== self::PRIMARY_MOUSE_BUTTON || $matches['state'] !== self::MOUSE_BUTTON_PRESS_STATE) {
					continue;
				}

				$selectedIndex = (int) $matches['row'] - self::FIRST_ITEM_ROW;
				if (!isset($this->items[$selectedIndex])) {
					continue;
				}

				$this->selectedIndex = $selectedIndex;
				$this->draw();

				return $this->items[$this->selectedIndex];
			}
		} finally {
			echo self::DISABLE_MOUSE_TRACKING_SEQUENCE;
			$this->terminalUi->showCursor();
		}
	}

	/**
	 * Render the menu with the current selection highlighted.
	 */
	private function draw(): void
	{
		echo self::CURSOR_HOME_SEQUENCE;

		foreach ($this->items as $index => $item) {
			if ($index === $this->selectedIndex) {
				echo $this->terminalUi->color(" > {$item}", 'cyan') . "\n";
			} else {
				echo "   {$item}\n";
			}
		}
	}
}
