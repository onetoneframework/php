<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum WindowStyle : int
{
    /**
     * Marks the window (or control) as initially visible. Without this flag, the window remains hidden until you explicitly call ShowWindow or set it visible via other means.
     */
    case WS_VISIBLE = 0x10000000;
    /**
     * Indicates the window is a child of another window.
     */
    case WS_CHILD = 0x40000000;
    /**
     * Combines title bar, border, sizing border, and more.
     */
    case WS_OVERLAPPEDWINDOW = 0x00CF0000;
    /**
     * Gives the window a thin line border.
     */
    case WS_BORDER = 0x00800000;
    /**
     * Adds a title bar (includes WS_BORDER and WS_DLGFRAME).
     */
    case WS_CAPTION = 0x00C00000;
    /**
     * (Extended style) Adds a sunken edge border.
     */
    case WS_EX_CLIENTEDGE = 0x00000200;

    case WS_VSCROLL = 0x00200000;
    case WS_HSCROLL = 0x00100000;
    case WS_BOTHSCROLL = 0xA00000;
    case WS_TABSTOP = 0x00010000;
}