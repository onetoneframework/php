<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\FFI\Windows;

use Clover\Classes\FFI\Windows\WindowsAPI;
use Clover\Enumeration\Windows32\User32\{HitTestArea, WindowStyle, SetWindowPos, RedrawWindow, WindowMessage};
use function is_array;
use function in_array;
use function chr;
use Exception;
use FFI;
use FFI\CData;
use Closure;

/**
 * Custom Window Builder
 *
 * Provides a fluent builder API for creating custom Win32 windows using the WindowsAPI FFI wrapper.
 * Supports adding UI elements (buttons, text fields, labels, etc.), event handling via callbacks,
 * menus, tray icons, timers, and pre-built application templates (Notepad, Paint, Calculator, etc.).
 *
 * Usage:
 *   $window = new CustomWindow($windowsAPI);
 *   $window->setTitle('My App')->setSize(800, 600)->setPosition(100, 100);
 *   $window->addButton('OK', 10, 10, 80, 30, function() { echo 'clicked'; });
 *   $window->addMenu('File', ['Open' => fn() => ..., 'Exit' => fn() => ...]);
 *   $window->run();
 *
 * Pre-built templates:
 *   CustomWindow::createNotepad($api)->run();
 *   CustomWindow::createCalculator($api)->run();
 *   CustomWindow::createPaint($api)->run();
 *   CustomWindow::createSystemMonitor($api)->run();
 *   CustomWindow::createFileExplorer($api)->run();
 */
class CustomWindow
{
    private $initialized = false;
    private $resizing = false;
    private $layout_drawed = false;

    /** @var WindowsAPI */
    private WindowsAPI $api;

    /** @var string Window title */
    private string $title = 'Custom Window';

    /** @var int $width Window width */
    private int $width = 640;

    /** @var int $height Window height */
    private int $height = 480;

    /** @var int $x Window X position */
    private int $x = 100;

    /** @var int $y Window Y position */
    private int $y = 100;

    /** @var CData|null $hWnd Window handle */
    private ?CData $hWnd = null;

    /** @var CData|null Module instance handle */
    private ?CData $hInstance = null;

    /** @var mixed GDI+ token */
    private mixed $gdipToken = null;

    /** @var bool Whether GDI+ context is initialized */
    private bool $gdipInitialized = false;

    /** @var bool Whether the window has been created */
    private bool $created = false;

    /** @var bool Whether the message loop is running */
    private bool $running = false;

    /**
     * Element registry: stores all child controls by their command ID.
     *
     * @var array<int, array{handle: CData, type: string, name: string}>
     */
    private array $elements = [];

    /**
     * Global event handler registry.
     * Maps message type => list of callbacks.
     *
     * @var array<int, Closure[]>
     */
    private array $globalEventHandlers = [];

    /**
     * PHP-level timer registry (since SetTimer/KillTimer may not be in the FFI header).
     * Maps timer ID => ['interval' => int, 'handler' => Closure, 'lastFired' => float].
     *
     * @var array<int, array{interval: int, handler: Closure, lastFired: float}>
     */
    private array $timerHandlers = [];

    /**
     * Menu item command handler registry.
     * Maps menu/button command ID => callback.
     *
     * @var array<int, Closure>
     */
    private array $menuHandlers = [];

    /**
     * Popup sub-menu registry for tracking open popup menus.
     * Maps parent command ID => CData (HMENU popup handle).
     *
     * @var array<int, CData>
     */
    private array $subMenus = [];

    /** @var int Auto-incrementing command ID counter for child controls */
    private int $nextCommandId = 100;

    /** @var int Auto-incrementing command ID counter for menu items */
    private int $nextMenuId = 2000;

    /** @var int Auto-incrementing timer ID counter */
    private int $nextTimerId = 1;

    /** @var CData|null Main menu bar handle */
    private ?CData $mainMenuBar = null;

    /** @var CData|null Status bar handle */
    private ?CData $statusBar = null;

    /** @var bool Whether to show a status bar */
    private bool $showStatusBar = false;

    /** @var bool Whether to enable tray icon */
    private bool $enableTray = false;

    /** @var string Tray icon tooltip text */
    private string $trayTooltip = '';

    /** @var bool Whether resize tracking is active */
    private bool $startResize = false;

    /**
     * Layout rules for elements that should auto-resize with the window.
     * Maps command ID => ['anchor' => string, 'margin' => [top, right, bottom, left]].
     *
     * @var array<int, array{anchor: string, margin: array}>
     */
    private array $layoutRules = [];

    /** @var Closure|null Callback invoked on WM_PAINT */
    private ?Closure $onPaintHandler = null;

    /** @var Closure|null Callback invoked before the window is destroyed */
    private ?Closure $onCloseHandler = null;

    /** @var Closure|null Callback invoked after the window is created and shown */
    private ?Closure $onCreateHandler = null;

    /**
     * Deferred element factories — stored before window creation, materialized during build().
     *
     * @var array<int, array{id: int, type: string, name: string, factory: Closure}>
     */
    private array $deferredElements = [];

    /**
     * Deferred menu definitions — stored before window creation, materialized during build().
     *
     * @var array<array{label: string, items: array}>
     */
    private array $deferredMenus = [];

    /**
     * Create a new CustomWindow instance.
     *
     * @param WindowsAPI $api The WindowsAPI FFI wrapper instance
     */
    public function __construct(WindowsAPI $api)
    {
        $this->api = $api;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  CONFIGURATION (fluent setters — can be called before or after run)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Set the window title. If the window is already created, updates it live.
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        if ($this->hWnd !== null) {
            $this->api->setWindowContextText($this->hWnd, $title);
        }

        return $this;
    }

    /**
     * Set the window dimensions. If the window is already created, resizes it live.
     *
     * @param int $width
     * @param int $height
     * @return $this
     */
    public function setSize(int $width, int $height): static
    {
        $this->width = $width;
        $this->height = $height;

        if ($this->hWnd !== null) {
            $this->api->resizeWindow($this->hWnd, $width, $height);
        }

        return $this;
    }

    /**
     * Set the window screen position. If the window is already created, moves it live.
     *
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function setPosition(int $x, int $y): static
    {
        $this->x = $x;
        $this->y = $y;

        if ($this->hWnd !== null) {
            $this->api->moveWindow($this->hWnd, $x, $y);
        }

        return $this;
    }

    /**
     * Enable or disable the status bar at the bottom of the window.
     *
     * @param bool $enabled
     * @return $this
     */
    public function enableStatusBar(bool $enabled = true): static
    {
        $this->showStatusBar = $enabled;
        return $this;
    }

    /**
     * Enable the system tray icon with the specified tooltip.
     *
     * @param string $tooltip Tooltip text shown on tray hover
     * @return $this
     */
    public function enableTrayIcon(string $tooltip = ''): static
    {
        $this->enableTray = true;
        $this->trayTooltip = $tooltip ?: $this->title;
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ELEMENT CREATION — all are deferred until build()
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Add a push button to the window.
     *
     * @param string $label Button text
     * @param int $x X position relative to client area
     * @param int $y Y position relative to client area
     * @param int $width Button width in pixels
     * @param int $height Button height in pixels
     * @param Closure|null $onClick Click callback: fn(int $commandId) => void
     * @return int The command ID assigned to this button
     */
    public function addButton(string $label, int $x, int $y, int $width = 80, int $height = 30, ?Closure $onClick = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'button', $label, function () use ($id, $label, $x, $y, $width, $height) {
            return $this->api->CreateButton($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $label, $id, $x, $y, $width, $height);
        });

        if ($onClick !== null) {
            $this->menuHandlers[$id] = $onClick;
        }

        return $id;
    }

    /**
     * Add a multi-line edit text control to the window.
     *
     * @param string $text Initial text content
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param string|null $anchor Anchor mode for auto-resize: 'fill' stretches to fill window
     * @param array $margin Margins for fill anchor [top, right, bottom, left] in pixels
     * @return int The command ID assigned to this element
     */
    public function addEditText(string $text, int $x, int $y, int $width = 300, int $height = 200, ?string $anchor = null, array $margin = [0, 0, 0, 0]): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'edittext', 'edittext_' . $id, function () use ($id, $text, $x, $y, $width, $height) {
            return $this->api->CreateEditText($this->hWnd, $text, $id, $x, $y, $width, $height);
        });

        if ($anchor !== null) {
            $this->layoutRules[$id] = ['anchor' => $anchor, 'margin' => $margin];
        }

        return $id;
    }

    /**
     * Add a static text label control to the window.
     *
     * @param string $text Label text
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Label width
     * @param int $height Label height
     * @return int The command ID assigned to this element
     */
    public function addLabel(string $text, int $x, int $y, int $width = 100, int $height = 25): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'label', 'label_' . $id, function () use ($id, $text, $x, $y, $width, $height) {
            return $this->api->CreateLabel($this->hWnd, $text, $id, $x, $y, $width, $height);
        });

        return $id;
    }

    /**
     * Add a checkbox control to the window.
     *
     * @param string $label Checkbox label text
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param bool $checked Initial checked state
     * @param Closure|null $onClick Click callback: fn(int $commandId) => void
     * @return int The command ID assigned to this element
     */
    public function addCheckBox(string $label, int $x, int $y, int $width = 120, int $height = 25, bool $checked = false, ?Closure $onClick = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'checkbox', $label, function () use ($id, $label, $x, $y, $width, $height, $checked) {
            return $this->api->CreateCheckBox($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $label, $checked, $id, $x, $y, $width, $height);
        });

        if ($onClick !== null) {
            $this->menuHandlers[$id] = $onClick;
        }

        return $id;
    }

    /**
     * Add a radio button control to the window.
     *
     * @param string $label Radio button label text
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param Closure|null $onClick Click callback: fn(int $commandId) => void
     * @return int The command ID assigned to this element
     */
    public function addRadioButton(string $label, int $x, int $y, int $width = 120, int $height = 25, ?Closure $onClick = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'radio', $label, function () use ($id, $label, $x, $y, $width, $height) {
            return $this->api->CreateRadioButton($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $label, $id, $x, $y, $width, $height);
        });

        if ($onClick !== null) {
            $this->menuHandlers[$id] = $onClick;
        }

        return $id;
    }

    /**
     * Add a combo box (dropdown) control to the window.
     *
     * @param array $items List of string items to populate the dropdown
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height (includes dropdown area)
     * @param Closure|null $onSelect Selection change callback: fn(int $commandId) => void
     * @return int The command ID assigned to this element
     */
    public function addComboBox(array $items, int $x, int $y, int $width = 150, int $height = 200, ?Closure $onSelect = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'combobox', 'combobox_' . $id, function () use ($id, $items, $x, $y, $width, $height) {
            return $this->api->CreateComboBox($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $items, $id, $x, $y, $width, $height);
        });

        if ($onSelect !== null) {
            $this->menuHandlers[$id] = $onSelect;
        }

        return $id;
    }

    /**
     * Add a list box control to the window.
     *
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param Closure|null $onSelect Selection change callback: fn(int $commandId) => void
     * @return int The command ID assigned to this element
     */
    public function addListBox(int $x, int $y, int $width = 150, int $height = 200, ?Closure $onSelect = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'listbox', 'listbox_' . $id, function () use ($id, $x, $y, $width, $height) {
            return $this->api->CreateListBox($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $id, $x, $y, $width, $height);
        });

        if ($onSelect !== null) {
            $this->menuHandlers[$id] = $onSelect;
        }

        return $id;
    }

    /**
     * Add a progress bar control to the window.
     *
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param int $min Minimum range value
     * @param int $max Maximum range value
     * @param int $pos Initial position
     * @return int The command ID assigned to this element
     */
    public function addProgressBar(int $x, int $y, int $width = 200, int $height = 25, int $min = 0, int $max = 100, int $pos = 0): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'progressbar', 'progressbar_' . $id, function () use ($id, $x, $y, $width, $height, $min, $max, $pos) {
            return $this->api->CreateProgressBar($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $min, $max, $pos, $id, $x, $y, $width, $height);
        });

        return $id;
    }

    /**
     * Add a trackbar (slider) control to the window.
     *
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param int $min Minimum range value
     * @param int $max Maximum range value
     * @param Closure|null $onChange Horizontal scroll change callback
     * @return int The command ID assigned to this element
     */
    public function addTrackBar(int $x, int $y, int $width = 200, int $height = 40, int $min = 0, int $max = 100, ?Closure $onChange = null): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'trackbar', 'trackbar_' . $id, function () use ($id, $x, $y, $width, $height, $min, $max) {
            return $this->api->CreateTrackBar($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $min, $max, $id, $x, $y, $width, $height);
        });

        if ($onChange !== null) {
            $this->addGlobalEventHandler(276/*WindowMessage::WM_HSCROLL*/, $onChange);
        }

        return $id;
    }

    /**
     * Add an up-down spinner control to the window.
     *
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @param int $pos Initial value
     * @return int The command ID assigned to this element
     */
    public function addUpDown(int $x, int $y, int $width = 50, int $height = 25, int $min = 0, int $max = 100, int $pos = 0): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'updown', 'updown_' . $id, function () use ($id, $x, $y, $width, $height, $min, $max, $pos) {
            return $this->api->CreateUpDown($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $min, $max, $pos, $id, $x, $y, $width, $height);
        });

        return $id;
    }

    /**
     * Add a group box control to the window (a bordered container with a title).
     *
     * @param string $label Group title
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @return int The command ID assigned to this element
     */
    public function addGroupBox(string $label, int $x, int $y, int $width = 200, int $height = 150): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'groupbox', $label, function () use ($label, $x, $y, $width, $height) {
            return $this->api->CreateGroupBox($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $label, $x, $y, $width, $height);
        });

        return $id;
    }

    /**
     * Add a date picker control to the window.
     *
     * @param int $x X position
     * @param int $y Y position
     * @param int $width Control width
     * @param int $height Control height
     * @return int The command ID assigned to this element
     */
    public function addDatePicker(int $x, int $y, int $width = 200, int $height = 25): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'datepicker', 'datepicker_' . $id, function () use ($id, $x, $y, $width, $height) {
            return $this->api->CreateDatePicker($this->hWnd, $id, $x, $y, $width, $height);
        });

        return $id;
    }

    /**
     * Add an image box control (supports BMP natively, other formats via GDI+).
     *
     * @param string $imagePath Absolute path to the image file
     * @param int $x X position
     * @param int $y Y position
     * @param int $width
     * @param int $height
     * @return int The command ID assigned to this element
     *
     * @throws Exception If the image cannot be loaded
     */
    public function addImageBox(string $imagePath, int $x = 0, int $y = 0, int $width = 100, int $height = 100): int
    {
        $id = $this->nextCommandId++;

        $this->deferElement($id, 'imagebox', 'imagebox_' . $id, function () use ($id, $imagePath, $x, $y, $width, $height) {
            return $this->api->CreateImageBox($this->hWnd, $this->api->cast($this->api->user32, "void*", 0), $imagePath, $id, $x, $y, $width, $height);
        });

        return $id;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  MENU SYSTEM
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Add a top-level menu with sub-items to the menu bar.
     * Creates the menu bar automatically if it does not yet exist.
     *
     * Items format:
     *   'Label' => fn() => void          // Regular clickable item
     *   '-'     => null                   // Separator line
     *   'Sub'   => ['Child' => fn(), ...] // Nested sub-menu
     *
     * @param string $label Top-level menu label (e.g. "File", "Edit", "Help")
     * @param array<string, Closure|array|null> $items Associative array of items
     * @return $this
     */
    public function addMenu(string $label, array $items): static
    {
        $this->deferredMenus[] = ['label' => $label, 'items' => $items];
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  EVENT HANDLING
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Register a callback for WM_COMMAND events on a specific command ID.
     * This is used for button clicks, menu item selections, etc.
     *
     * @param int $commandId The control or menu command ID
     * @param Closure $handler fn(int $commandId) => void
     * @return $this
     */
    public function onCommand(int $commandId, Closure $handler): static
    {
        $this->menuHandlers[$commandId] = $handler;
        return $this;
    }

    /**
     * Register a handler for a specific Win32 message type globally.
     * The handler is not tied to a specific control.
     *
     * @param int $message Win32 message constant (e.g. self::WM_KEYDOWN)
     * @param Closure $handler Callback with message-specific arguments
     * @return $this
     */
    public function addGlobalEventHandler(int $message, Closure $handler): static
    {
        $this->globalEventHandlers[$message][] = $handler;
        return $this;
    }

    /**
     * Register a keyboard key-down event handler.
     *
     * @param Closure $handler fn(string $key, int $lParam) => void
     * @return $this
     */
    public function onKeyDown(Closure $handler): static
    {
        return $this->addGlobalEventHandler(256/*WindowMessage::WM_KEYDOWN*/, $handler);
    }

    /**
     * Register a keyboard key-up event handler.
     *
     * @param Closure $handler fn(string $key, int $lParam) => void
     * @return $this
     */
    public function onKeyUp(Closure $handler): static
    {
        return $this->addGlobalEventHandler(257/*WindowMessage::WM_KEYUP*/, $handler);
    }

    /**
     * Register a mouse move event handler.
     *
     * @param Closure $handler fn(int $x, int $y) => void
     * @return $this
     */
    public function onMouseMove(Closure $handler): static
    {
        return $this->addGlobalEventHandler(512/*WindowMessage::WM_MOUSEMOVE*/, $handler);
    }

    /**
     * Register a left mouse button down event handler.
     *
     * @param Closure $handler fn(int $x, int $y) => void
     * @return $this
     */
    public function onLeftButtonDown(Closure $handler): static
    {
        return $this->addGlobalEventHandler(513/*WindowMessage::WM_LBUTTONDOWN*/, $handler);
    }

    /**
     * Register a left mouse button up event handler.
     *
     * @param Closure $handler fn(int $x, int $y) => void
     * @return $this
     */
    public function onLeftButtonUp(Closure $handler): static
    {
        return $this->addGlobalEventHandler(514/*WindowMessage::WM_LBUTTONUP*/, $handler);
    }

    /**
     * Register a right mouse button down event handler.
     *
     * @param Closure $handler fn(int $x, int $y) => void
     * @return $this
     */
    public function onRightButtonDown(Closure $handler): static
    {
        return $this->addGlobalEventHandler(516/*WindowMessage::WM_RBUTTONDOWN*/, $handler);
    }

    /**
     * Register a paint event handler. Called each time the window receives WM_PAINT.
     *
     * @param Closure $handler fn(CData $hWnd) => void
     * @return $this
     */
    public function onPaint(Closure $handler): static
    {
        $this->onPaintHandler = $handler;
        return $this;
    }

    /**
     * Register a handler called before the window is destroyed.
     * Return false from the handler to cancel the close.
     *
     * @param Closure $handler fn() => bool
     * @return $this
     */
    public function onClose(Closure $handler): static
    {
        $this->onCloseHandler = $handler;
        return $this;
    }

    /**
     * Register a handler called after the window is fully created and visible.
     *
     * @param Closure $handler fn(CData $hWnd) => void
     * @return $this
     */
    public function onCreate(Closure $handler): static
    {
        $this->onCreateHandler = $handler;
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  TIMERS (PHP-level polling — checked after each Win32 message)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Register a recurring timer callback.
     * The callback is invoked approximately every $intervalMs milliseconds,
     * checked after each processed Win32 message.
     *
     * @param int $intervalMs Interval in milliseconds
     * @param Closure $handler fn(int $timerId) => void
     * @return int The timer ID
     */
    public function addTimer(int $intervalMs, Closure $handler): int
    {
        $id = $this->nextTimerId++;
        $this->timerHandlers[$id] = [
            'interval' => $intervalMs,
            'handler' => $handler,
            'lastFired' => microtime(true),
        ];
        return $id;
    }

    /**
     * Remove a previously registered timer by its ID.
     *
     * @param int $timerId
     * @return $this
     */
    public function removeTimer(int $timerId): static
    {
        unset($this->timerHandlers[$timerId]);
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  ELEMENT MANIPULATION (available after run/build)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get the native window handle (HWND) of a child element by its command ID.
     *
     * @param int $commandId
     * @return CData|null
     */
    public function getElementHandle(int $commandId): ?CData
    {
        return $this->elements[$commandId]['handle'] ?? null;
    }

    /**
     * Set the text content of an element (button label, edit text, label, etc.).
     *
     * @param int $commandId
     * @param string $text
     * @return $this
     */
    public function setElementText(int $commandId, string $text): static
    {
        $handle = $this->getElementHandle($commandId);
        if ($handle !== null) {
            $this->api->setWindowContextText($handle, $text);
        }
        return $this;
    }

    /**
     * Get the text content of an element.
     *
     * @param int $commandId
     * @return string|null
     */
    public function getElementText(int $commandId): ?string
    {
        $handle = $this->getElementHandle($commandId);
        return $handle !== null ? $this->api->getWindowContextText($handle) : null;
    }

    /**
     * Set the position of a progress bar element.
     *
     * @param int $commandId The command ID of the progress bar
     * @param int $position The new position value
     * @return $this
     */
    public function setProgressBarPosition(int $commandId, int $position): static
    {
        $handle = $this->getElementHandle($commandId);
        if ($handle !== null) {
            $this->api->setProgressBarPosition($handle, $position);
        }
        return $this;
    }

    /**
     * Enable or disable a child control.
     *
     * @param int $commandId
     * @param bool $enabled
     * @return $this
     */
    public function setElementEnabled(int $commandId, bool $enabled): static
    {
        $handle = $this->getElementHandle($commandId);
        if ($handle !== null) {
            $this->api->enableWindow($handle, $enabled);
        }
        return $this;
    }

    /**
     * Set keyboard focus to a child control.
     *
     * @param int $commandId
     * @return $this
     */
    public function setElementFocus(int $commandId): static
    {
        $handle = $this->getElementHandle($commandId);
        if ($handle !== null) {
            $this->api->setFocusToWindow($handle);
        }
        return $this;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  WINDOW OPERATIONS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get the main window handle (HWND).
     *
     * @return CData|null
     */
    public function getWindowHandle(): ?CData
    {
        return $this->hWnd;
    }

    /**
     * Set the status bar text. Only works if enableStatusBar(true) was called.
     *
     * @param string $text
     * @return $this
     */
    public function setStatusBarText(string $text): static
    {
        if ($this->statusBar !== null) {
            $this->api->setWindowContextText($this->statusBar, $text);
        }
        return $this;
    }

    /**
     * Show a modal message box.
     *
     * @param string $caption Dialog title
     * @param string $message Dialog body text
     * @param int $flags Win32 MB_* flags (defaults to MB_OK)
     * @return int The button result code (IDOK, IDYES, etc.)
     */
    public function showMessageBox(string $caption, string $message, int $flags = 0): int
    {
        return (int) $this->api->showMessageBox($caption, $message, $flags);
    }

    /**
     * Show a system tray notification balloon.
     *
     * @param string $title Notification title
     * @param string $message Notification body
     * @return $this
     */
    public function showNotification(string $title, string $message): static
    {
        $this->api->showNotification($title, $message);
        return $this;
    }

    /**
     * Open a file-open dialog and return the selected file path.
     *
     * @param string $title Dialog title
     * @param string $filter File filter string (null-separated pairs)
     * @return string|false File path or false if cancelled
     */
    public function openFileDialog(string $title = 'Open File', string $filter = "All Files\0*.*\0"): string|false
    {
        return $this->api->showFileOpenDialog($title, $filter);
    }

    /**
     * Open a file-save dialog and return the chosen file path.
     *
     * @param string $title Dialog title
     * @param string $filter File filter string
     * @param string $defaultExt Default file extension
     * @return string|false File path or false if cancelled
     */
    public function saveFileDialog(string $title = 'Save File', string $filter = "All Files\0*.*\0", string $defaultExt = ''): string|false
    {
        return $this->api->showFileSaveDialog($title, $filter, $defaultExt);
    }

    /**
     * Open the color picker dialog and return the selected color.
     *
     * @return string|false Hex color string (e.g. "#FF0000") or false if cancelled
     */
    public function openColorPicker(): string|false
    {
        return $this->api->showColorPicker();
    }

    /**
     * Read a file's full contents into a string.
     *
     * @param string $filePath Absolute path to the file
     * @return string|null File contents or null on failure
     */
    public function readFile(string $filePath): ?string
    {
        return $this->api->readFileContents($filePath);
    }

    /**
     * Minimize the window to the taskbar.
     *
     * @return $this
     */
    public function minimize(): static
    {
        if ($this->hWnd !== null) {
            $this->api->minimizeWindow($this->hWnd);
        }
        return $this;
    }

    /**
     * Maximize the window to fill the screen.
     *
     * @return $this
     */
    public function maximize(): static
    {
        if ($this->hWnd !== null) {
            $this->api->maximizeWindow($this->hWnd);
        }
        return $this;
    }

    /**
     * Restore the window from minimized or maximized state.
     *
     * @return $this
     */
    public function restore(): static
    {
        if ($this->hWnd !== null) {
            $this->api->restoreWindow($this->hWnd);
        }
        return $this;
    }

    /**
     * Programmatically close the window and stop the message loop.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->hWnd !== null) {
            $this->api->unregisterWindowProcedure($this->hWnd);
        }
        $this->running = false;
    }

    /**
     * Get the underlying WindowsAPI instance for advanced direct access.
     *
     * @return WindowsAPI
     */
    public function getAPI(): WindowsAPI
    {
        return $this->api;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  MAIN ENTRY POINT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Build the window, create all elements, and enter the blocking Win32 message loop.
     * This method blocks until the window is closed.
     *
     * @return void
     *
     * @throws Exception If the window fails to create
     */
    public function run(): void
    {
        $this->build();
        $this->messageLoop();
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  PRE-BUILT APPLICATION TEMPLATES
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Create a Notepad-like text editor window with File/Edit/Help menus.
     * Supports New, Open, Save As, Select All, Clear, and About actions.
     *
     * @param WindowsAPI $api
     * @param string $title Window title
     * @param int $width Window width
     * @param int $height Window height
     * @return static A fully configured instance — call ->run() to start
     */
    public static function createNotepad(WindowsAPI $api, string $title = 'Notepad', int $width = 800, int $height = 600): static
    {
        $window = new static($api);
        $window->setTitle($title)->setSize($width, $height)->setPosition(100, 100);
        $window->enableStatusBar(true);

        $editId = $window->addEditText('', 0, 0, $width, $height - 30, 'fill', [0, 0, 30, 0]);

        $window->addMenu('File', [
            'New' => function () use ($window, $editId) {
                $window->setElementText($editId, '');
                $window->setTitle('Notepad');
                $window->setStatusBarText('New file created');
            },
            'Open' => function () use ($window, $editId) {
                $filePath = $window->openFileDialog('Open File', "Text Files\0*.txt\0All Files\0*.*\0");
                if ($filePath) {
                    $content = $window->readFile($filePath);
                    if ($content !== null) {
                        $window->setElementText($editId, $content);
                        $window->setTitle("Notepad - {$filePath}");
                        $window->setStatusBarText("Opened: {$filePath}");
                    }
                }
            },
            'Save As' => function () use ($window, $editId) {
                $filePath = $window->saveFileDialog('Save File', "Text Files\0*.txt\0All Files\0*.*\0", 'txt');
                if ($filePath) {
                    $content = $window->getElementText($editId) ?? '';
                    file_put_contents($filePath, $content);
                    $window->setTitle("Notepad - {$filePath}");
                    $window->setStatusBarText("Saved: {$filePath}");
                }
            },
            '-' => null,
            'Exit' => function () use ($window) {
                $window->close();
            },
        ]);

        /*$window->addMenu('Edit', [
            'Select All' => function () use ($window, $editId) {
                $handle = $window->getElementHandle($editId);
                if ($handle !== null) {
                    // EM_SETSEL (0x00B1): wParam=0 lParam=-1 selects all text
                    $window->getAPI()->sendMessageToWindow($handle, 0x00B1, 0, -1);
                }
            },
            'Clear' => function () use ($window, $editId) {
                $window->setElementText($editId, '');
            },
        ]);

        $window->addMenu('Help', [
            'About' => function () use ($window) {
                $window->showMessageBox('About', 'PHP FFI Notepad — Built with WindowsAPI + CustomWindow');
            },
        ]);*/

        return $window;
    }

    /**
     * Create a simple Paint-like window with color tools and mouse position tracking.
     *
     * @param WindowsAPI $api
     * @param string $title Window title
     * @param int $width Window width
     * @param int $height Window height
     * @return static A fully configured instance — call ->run() to start
     */
    public static function createPaint(WindowsAPI $api, string $title = 'Paint', int $width = 900, int $height = 650): static
    {
        $window = new static($api);
        $window->setTitle($title)->setSize($width, $height)->setPosition(50, 50);
        $window->enableStatusBar(true);

        $window->addButton('Color', 10, 10, 60, 28);
        $window->addButton('Clear', 80, 10, 60, 28);
        $window->addLabel('Tool:', 160, 15, 40, 20);
        $window->addComboBox(['Pen', 'Line', 'Rectangle', 'Ellipse'], 200, 10, 100, 120);

        $window->addMenu('File', [
            'New' => function () use ($window) {
                $window->setStatusBarText('Canvas cleared');
            },
            'Open Image' => function () use ($window) {
                $path = $window->openFileDialog('Open Image', "Images\0*.bmp;*.png;*.jpg\0All Files\0*.*\0");
                if ($path) {
                    $window->setStatusBarText("Opened: {$path}");
                }
            },
            '-' => null,
            'Exit' => function () use ($window) {
                $window->close();
            },
        ]);

        $window->addMenu('Colors', [
            'Pick Color' => function () use ($window) {
                $color = $window->openColorPicker();
                if ($color) {
                    $window->setStatusBarText("Selected color: {$color}");
                }
            },
        ]);

        $window->addMenu('Help', [
            'About' => function () use ($window) {
                $window->showMessageBox('About', 'PHP FFI Paint — Built with WindowsAPI + CustomWindow');
            },
        ]);

        $window->onMouseMove(function (int $x, int $y) use ($window) {
            $window->setStatusBarText("Position: {$x}, {$y}");
        });

        return $window;
    }

    /**
     * Create a Calculator window with digit buttons, operator buttons, and a display.
     *
     * @param WindowsAPI $api
     * @param string $title Window title
     * @return static A fully configured instance — call ->run() to start
     */
    public static function createCalculator(WindowsAPI $api, string $title = 'Calculator'): static
    {
        $window = new static($api);
        $window->setTitle($title)->setSize(280, 380)->setPosition(200, 200);

        $displayId = $window->addEditText('0', 10, 10, 245, 70);
        $state = ['operand' => '', 'operator' => '', 'newInput' => true];

        $appendDigit = function (string $digit) use ($window, $displayId, &$state) {
            if ($state['newInput']) {
                $window->setElementText($displayId, $digit);
                $state['newInput'] = false;
            } else {
                $current = $window->getElementText($displayId) ?? '0';
                $window->setElementText($displayId, $current . $digit);
            }
        };

        $performOp = function () use ($window, $displayId, &$state) {
            $current = (float) ($window->getElementText($displayId) ?? '0');
            $prev = (float) $state['operand'];
            $result = match ($state['operator']) {
                '+' => $prev + $current,
                '-' => $prev - $current,
                '*' => $prev * $current,
                '/' => $current != 0 ? $prev / $current : 0,
                default => $current,
            };
            $window->setElementText($displayId, (string) $result);
            $state['operand'] = (string) $result;
            $state['newInput'] = true;
        };

        $buttons = [
            ['7', '8', '9', '/'],
            ['4', '5', '6', '*'],
            ['1', '2', '3', '-'],
            ['0', '.', '=', '+'],
        ];

        $btnW = 55;
        $btnH = 35;
        $gap = 5;
        $startX = 10;
        $startY = 100;

        foreach ($buttons as $row => $cols) {
            foreach ($cols as $col => $label) {
                $bx = $startX + $col * ($btnW + $gap);
                $by = $startY + $row * ($btnH + $gap);

                if ($label === '=') {
                    $window->addButton($label, $bx, $by, $btnW, $btnH, $performOp);
                } elseif (in_array($label, ['+', '-', '*', '/'])) {
                    $op = $label;
                    $window->addButton($label, $bx, $by, $btnW, $btnH, function () use ($window, $displayId, &$state, $performOp, $op) {
                        if ($state['operator'] !== '' && !$state['newInput']) {
                            $performOp();
                        } else {
                            $state['operand'] = $window->getElementText($displayId) ?? '0';
                        }
                        $state['operator'] = $op;
                        $state['newInput'] = true;
                    });
                } else {
                    $digit = $label;
                    $window->addButton($label, $bx, $by, $btnW, $btnH, function () use ($appendDigit, $digit) {
                        $appendDigit($digit);
                    });
                }
            }
        }

        $clearY = $startY + 4 * ($btnH + $gap);
        $window->addButton('C', $startX, $clearY, 4 * $btnW + 3 * $gap, $btnH, function () use ($window, $displayId, &$state) {
            $window->setElementText($displayId, '0');
            $state['operand'] = '';
            $state['operator'] = '';
            $state['newInput'] = true;
        });

        return $window;
    }

    /**
     * Create a file explorer-like window with a path bar, Go button, and list box.
     *
     * @param WindowsAPI $api
     * @param string $title Window title
     * @param int $width Window width
     * @param int $height Window height
     * @return static A fully configured instance — call ->run() to start
     */
    public static function createFileExplorer(WindowsAPI $api, string $title = 'File Explorer', int $width = 700, int $height = 500): static
    {
        $window = new static($api);
        $window->setTitle($title)->setSize($width, $height)->setPosition(100, 100);
        $window->enableStatusBar(true);

        $window->addEditText('C:\\', 10, 10, $width - 100, 25);
        $window->addButton('Go', $width - 80, 10, 60, 25);
        $window->addListBox(10, 45, $width - 40, $height - 100);

        $window->addMenu('File', [
            'Open Folder' => function () use ($window) {
                $path = $window->openFileDialog('Select File');
                if ($path) {
                    $window->setStatusBarText("Selected: {$path}");
                }
            },
            '-' => null,
            'Exit' => function () use ($window) {
                $window->close();
            },
        ]);

        return $window;
    }

    /**
     * Create a system monitor window that periodically refreshes system info.
     *
     * @param WindowsAPI $api
     * @param string $title Window title
     * @return static A fully configured instance — call ->run() to start
     */
    public static function createSystemMonitor(WindowsAPI $api, string $title = 'System Monitor'): static
    {
        $window = new static($api);
        $window->setTitle($title)->setSize(450, 350)->setPosition(150, 150);
        $window->enableStatusBar(true);
        $window->enableTrayIcon('System Monitor');

        $infoEditId = $window->addEditText('Loading...', 10, 10, 415, 260, 'fill', [10, 10, 40, 10]);

        $window->addTimer(100, function () use ($window, $infoEditId, $api) {
            $tickCount = $api->getTickCount();
            $uptimeSec = intdiv($tickCount, 1000);
            $h = intdiv($uptimeSec, 3600);
            $m = intdiv($uptimeSec % 3600, 60);
            $s = $uptimeSec % 60;

            $pid = $api->getCurrentProcessId();
            $tid = $api->getCurrentThreadId();

            $text = "=== System Monitor ===\r\n\r\n";
            $text .= "Uptime: {$h}h {$m}m {$s}s\r\n";
            $text .= "Process ID: {$pid}\r\n";
            $text .= "Thread ID: {$tid}\r\n";
            $text .= "Tick Count: {$tickCount}\r\n";

            try {
                $text .= "User: {$api->getUserName()}\r\n";
            } catch (\Throwable) {
            }
            try {
                $text .= "Computer: {$api->getComputerName()}\r\n";
            } catch (\Throwable) {
            }

            $window->setElementText($infoEditId, $text);
            $window->setStatusBarText("Updated: " . date('H:i:s'));
        });

        $window->addMenu('File', [
            'Refresh' => function () use ($window) {
                $window->setStatusBarText('Refreshed');
            },
            '-' => null,
            'Exit' => function () use ($window) {
                $window->close();
            },
        ]);

        $window->addMenu('Help', [
            'About' => function () use ($window) {
                $window->showMessageBox('About', 'PHP FFI System Monitor — Built with WindowsAPI + CustomWindow');
            },
        ]);

        return $window;
    }

    // ═══════════════════════════════════════════════════════════════════════
    //  INTERNAL — BUILD, MENUS, MESSAGE LOOP, DISPATCH
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Register a deferred element creation factory.
     * The factory is called during build() when the window handle is available.
     *
     * @param int $id Command ID
     * @param string $type Element type identifier
     * @param string $name Human-readable name
     * @param Closure $factory fn() => CData
     * @return void
     */
    private function deferElement(int $id, string $type, string $name, Closure $factory): void
    {
        $this->deferredElements[$id] = [
            'id' => $id,
            'type' => $type,
            'name' => $name,
            'factory' => $factory,
        ];
    }

    /**
     * Build the window: register class, create HWND, materialize all deferred elements and menus.
     *
     * @return void
     *
     * @throws Exception If the window fails to create
     */
    private function build(): void
    {
        $this->hInstance = $this->api->getModuleHandle();
        $this->api->registerWindowClass($this->hInstance);
        $this->hWnd = $this->api->createWindow($this->api->cast($this->api->user32, "void*", $this->hInstance), $this->title, $this->width, $this->height, $this->x, $this->y);

        if ($this->hWnd === null || FFI::isNull($this->hWnd)) {
            throw new Exception("Failed to create window");
        }

        // Register per-window WNDPROC hook so WM_COMMAND is processed even when sent directly.
        $this->api->registerWindowProcedure($this->hWnd, function ($hWnd, $uMsg, $wParam, $lParam) {
            if ($uMsg === WindowMessage::WM_COMMAND) {
                $this->handleCommandNative((int)$wParam, (int)$lParam);
            }
            return null; // continue default handling
        });

        $this->api->showWindow($this->hWnd);

        $this->gdipToken = $this->api->createGDIPlusContext();
        $this->gdipInitialized = true;

        if ($this->enableTray) {
            $this->api->createTray($this->trayTooltip ?: $this->title, $this->hWnd);
        }

        if ($this->showStatusBar) {
            $this->statusBar = $this->api->CreateStatusBar($this->hWnd);
        }

        $this->buildMenus();

        foreach ($this->deferredElements as $id => $def) {
            $handle = ($def['factory'])();
            $this->elements[$id] = [
                'handle' => $handle,
                'type' => $def['type'],
                'name' => $def['name'],
            ];
        }

        $this->deferredElements = [];
        $this->deferredMenus = [];
        $this->created = true;
        // Mark initialized as soon as the window is fully built
        $this->initialized = true;

        if ($this->onCreateHandler !== null) {
            ($this->onCreateHandler)($this->hWnd);
        }
    }

    /**
     * Build all deferred menus and attach them to the window's menu bar.
     *
     * @return void
     */
    private function buildMenus(): void
    {
        if (empty($this->deferredMenus)) {
            return;
        }

        foreach ($this->deferredMenus as $menuDef) {
            $subMenu = $this->api->createPopupMenu();
            $this->buildMenuItems($subMenu, $menuDef['items']);

            $parentId = $this->nextMenuId++;
            $this->subMenus[$parentId] = $subMenu;
            $mainMenu = $this->api->createMenu($this->hWnd, $menuDef['label'], $parentId);

            if ($this->mainMenuBar === null) {
                $this->mainMenuBar = $mainMenu;
            }
        }
    }

    /**
     * Recursively populate a popup menu with items, separators, and nested sub-menus.
     *
     * @param CData $menu The HMENU popup handle
     * @param array $items Label => handler pairs
     * @return void
     */
    private function buildMenuItems(CData $menu, array $items): void
    {
        foreach ($items as $label => $handler) {
            if ($label === '-' || $handler === null) {
                $this->api->appendMenuSeparator($menu);
                continue;
            }

            if (is_array($handler)) {
                // Nested sub-menu
                $subMenu = $this->api->createPopupMenu();
                $this->buildMenuItems($subMenu, $handler);
                $this->api->appendSubMenu($menu, $subMenu, $label);
            } else {
                // Regular menu item with callback
                $menuId = $this->nextMenuId++;
                $this->api->appendMenuItem($menu, $menuId, $label);
                if ($handler instanceof Closure) {
                    $this->menuHandlers[$menuId] = $handler;
                }
            }
        }
    }

    /**
     * The Win32 message loop. Uses non-blocking PeekMessageW so tickTimers can run continuously,
     * even when no Win32 messages are pending.
     *
     * @return void
     */
    private function messageLoop(): void
    {
        $this->running = true;

        $msg = $this->api->user32->new('MSG');
        while ($this->running) {
            // Non-blocking message check
            $ret = $this->api->user32->PeekMessageW(FFI::addr($msg), null, 0, 0, 0x0001); // PM_REMOVE

            if ($ret) {
                // WM_QUIT received
                if ($msg->message === 0x0012) {
                    break;
                }

                // Dispatch to our handler before Windows default handling
                $this->handleMessage($msg);

                // Let Windows translate and dispatch to the window procedure
                $this->api->user32->TranslateMessage(FFI::addr($msg));
                $this->api->user32->DispatchMessageW(FFI::addr($msg));
            } else {
                // No message available — yield CPU and check timers
                $this->api->user32->MsgWaitForMultipleObjects(0, null, $this->api->user32->cast("bool*", 0), 10, 0x04FF); // QS_ALLINPUT
            }

            // Fire PHP-level timers continuously
            $this->tickTimers();
        }

        // Shutdown GDI+
        if ($this->gdipInitialized && $this->gdipToken !== null) {
            $this->api->shutdownGDIPlus($this->gdipToken);
        }
    }

    /**
     * Check all PHP-level timers and fire those whose interval has elapsed.
     *
     * @return void
     */
    private function tickTimers(): void
    {
        if (empty($this->timerHandlers)) {
            return;
        }

        $now = microtime(true);

        foreach ($this->timerHandlers as $id => &$timer) {
            $elapsed = ($now - $timer['lastFired']) * 1000;
            if ($elapsed >= $timer['interval']) {
                $timer['lastFired'] = $now;
                if (is_callable($timer['handler'])) {
                    ($timer['handler'])($id);
                }
            }
        }
        unset($timer);
    }

    /**
     * Dispatch a single Win32 message to the appropriate handler(s).
     *
     * @param CData $msg The MSG structure
     * @return void
     */
    private function handleMessage(CData $msg): void
    {
        $message = $msg->message;

        switch ($message) {
            case 273: //WindowMessage::WM_COMMAND:
                if (!$this->initialized) {
                    // WM_DWMNCRENDERINGCHANGED (799) may not fire on some systems;
                    // initialized is set at end of build(), so re-check here before handling WM_COMMAND
                    return;
                }
                $this->handleCommand($msg);
                break;

            case 15: //WindowMessage::WM_PAINT:
                if ($this->onPaintHandler !== null) {
                    ($this->onPaintHandler)($this->hWnd);
                }
                $this->applyLayout();
                break;

            case 256: //WindowMessage::WM_KEYDOWN:
                $key = chr((int) $msg->wParam);
                $this->dispatchGlobalHandlers(256, $key, (int) $msg->lParam);
                break;

            case 257: //WindowMessage::WM_KEYUP:
                $key = chr((int) $msg->wParam);
                $this->dispatchGlobalHandlers(257, $key, (int) $msg->lParam);
                break;

            case 512: //WindowMessage::WM_MOUSEMOVE:
            case 513: //WindowMessage::WM_LBUTTONDOWN:
            case 514: //WindowMessage::WM_LBUTTONUP:
            case 516: //WindowMessage::WM_RBUTTONDOWN:
            case 517: //WindowMessage::WM_RBUTTONUP:
                $lParam = (int) $msg->lParam;
                $x = $lParam & 0xFFFF;
                $y = ($lParam >> 16) & 0xFFFF;

                $this->dispatchGlobalHandlers($message, $x, $y);
                break;

            case 276: //WindowMessage::WM_HSCROLL:
            case 277: //WindowMessage::WM_VSCROLL:
                $this->dispatchGlobalHandlers($message);
                break;

            case 160:
                if ($this->resizing) {
                    $this->resizing = false;
                    $this->layout_drawed = false;
                }
                break;

            case 0x00a1: //WindowMessage::WM_NCLBUTTONDOWN:
                if($msg->lParam == 17) {
                    // Start resize
                    $this->resizing = true;
                }

                $this->handleNonClientClick($msg);
                break;

            case 799: //WindowMessage::WM_DWMNCRENDERINGCHANGED:
                $this->applyLayout();
                
                $this->initialized = true;
                break;
        }
    }

    /**
     * Handle WM_COMMAND messages (button clicks, menu item selections, control notifications).
     *
     * @param CData $msg The MSG structure
     * @return void
     */
    private function handleCommand(CData $msg): void
    {
        if (!$msg) {
            return;
        }

        $wParam = (int)$msg->wParam;
        $lParam = (int)$msg->lParam;

        $this->handleCommandNative($wParam, $lParam);
    }

    /**
     * Handle native WM_COMMAND values extracted from the message structure.
     *
     * @param int $wParam
     * @param int $lParam
     *
     * @return void
     */
    private function handleCommandNative(int $wParam, int $lParam): void
    {
        $commandId = $wParam & 0xFFFF;

        // Registered command handler (button click or menu item)
        if (isset($this->menuHandlers[$commandId])) {
            ($this->menuHandlers[$commandId])($commandId);
            return;
        }

        // Popup menu trigger (top-level menu bar entry)
        if (isset($this->subMenus[$commandId])) {
            $this->api->showPopupMenuAtCursor($this->subMenus[$commandId], $this->hWnd);
        }
    }

    /**
     * Handle WM_NCLBUTTONDOWN for window resize tracking and close button detection.
     *
     * @param CData $msg The MSG structure
     * @return void
     */
    private function handleNonClientClick(CData $msg): void
    {
        $hitTest = (int) $msg->lParam;

        switch ($hitTest) {
            case 10://HitTestArea::HTLEFT:
            case 11://HitTestArea::HTRIGHT:
            case 12://HitTestArea::HTTOP:
            case 15://HitTestArea::HTBOTTOM:
            case 17://HitTestArea::HTBOTTOMRIGHT:
            case 2://HitTestArea::HTCAPTION:
            case 9://HitTestArea::HTMAXBUTTON:
                $this->startResize = true;
                break;

            case 20://HitTestArea::HTCLOSE:
                if ($this->onCloseHandler !== null) {
                    $result = ($this->onCloseHandler)();
                    if ($result === false) {
                        return;
                    }
                }
                $this->api->postQuitMessage(0);
                $this->running = false;
                break;
        }
    }

    /**
     * Apply layout rules to auto-resize elements and reposition the status bar
     * when the window is resized.
     *
     * @return void
     */
    private function applyLayout(): void
    {
        if (!$this->layout_drawed) {
            $this->layout_drawed = true;
        } else {
            return;
        }

        if (!$this->startResize && empty($this->layoutRules)) {
            return;
        }

        $this->startResize = false;

        $client = $this->api->getClientDimensions($this->hWnd);
        if ($client === false) {
            return;
        }

        $cw = $client['width'];
        $ch = $client['height'];

        // Resize elements with layout rules
        foreach ($this->layoutRules as $cmdId => $rule) {
            $handle = $this->getElementHandle($cmdId);
            if ($handle === null) {
                continue;
            }

            [$top, $right, $bottom, $left] = $rule['margin'];

            if ($rule['anchor'] === 'fill') {
                $this->api->setWindowPosition($handle, $left, $top, $cw - $left - $right, $ch - $top - $bottom);
            }
        }

        // Keep status bar at the bottom
        if ($this->statusBar !== null) {
            $this->api->setWindowPosition($this->statusBar, 0, $ch - 30, $cw, 30);
        }
    }

    /**
     * Dispatch global (non-element-specific) event handlers for a message type.
     *
     * @param int $message The Win32 message constant
     * @param mixed ...$args Arguments passed to each handler
     * @return void
     */
    private function dispatchGlobalHandlers(int $message, mixed ...$args): void
    {
        if (!isset($this->globalEventHandlers[$message])) {
            return;
        }

        foreach ($this->globalEventHandlers[$message] as $handler) {
            if (is_callable($handler)) {
                $handler(...$args);
            }
        }
    }
}
