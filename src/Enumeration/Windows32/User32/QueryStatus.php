<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

enum QueryStatus : int
{
    /**
     * A WM_KEYUP, WM_KEYDOWN, WM_SYSKEYUP, or WM_SYSKEYDOWN message is in the queue.
     */
    case QS_KEY = 0x0001;
    /**
     * A WM_MOUSEMOVE message is in the queue.
     */
    case QS_MOUSEMOVE = 0x0002;
    /**
     * A mouse-button message (WM_LBUTTONUP, WM_RBUTTONDOWN, and so on).
     */
    case QS_MOUSEBUTTON = 0x0004;
    /**
     * A posted message (other than those listed here) is in the queue. For more information, see PostMessage.
     * This value is cleared when you call GetMessage or PeekMessage, whether or not you are filtering messages.
     */ 
    case QS_POSTMESSAGE = 0x0008;
    /**
     * A WM_TIMER message is in the queue.
     */
    case QS_TIMER = 0x0010;
    /**
     * A WM_PAINT message is in the queue.
     */
    case QS_PAINT = 0x0020;
    /**
     * A message sent by another thread or application is in the queue. For more information, see SendMessage.
     */
    case QS_SENDMESSAGE = 0x0040;
    case QS_HOTKEY = 0x0080;
    case QS_ALLPOSTMESSAGE = 0x0100;
    case QS_RAWINPUT = 0x0400;
    case QS_TOUCH = 0x0800;
    case QS_POINTER = 0x1000;
    case QS_MOUSE = 0x0006;
    case QS_INPUT = 7175;
    case QS_ALLEVENTS = 7351;
    /**
     * Any message is in the queue.
     */
    case QS_ALLINPUT = 0x04FF;
}
