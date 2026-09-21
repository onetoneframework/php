<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32;

/**
 * System Metric constants
 */
enum SystemMetric : int
{
    case SM_CLEANBOOT = 67;
    case SM_CMONITORS = 80;
    case SM_CONVERTIBLESLATEMODE = 0x2003;
    case SM_CXSCREEN = 0;
    case SM_CXSMSIZE = 52;
    case SM_CXVIRTUALSCREEN = 78;
    case SM_CYFULLSCREEN = 17;
    case SM_CYHSCROLL = 3;
    case SM_CYICON = 12;
    case SM_CYICONSPACING = 39;
    case SM_CYKANJIWINDOW = 18;
    case SM_CYMAXIMIZED = 62;
    case SM_CYMAXTRACK = 60;
    case SM_CYMENU = 15;
    case SM_CYMENUCHECK = 72;
    case SM_CYMENUSIZE = 55;
    case SM_CYMIN = 29;
    case SM_CYMINIMIZED = 58;
    case SM_CYMINSPACING = 48;
    case SM_CYMINTRACK = 35;
    case SM_CYSCREEN = 1;
    case SM_CYSIZE = 31;
    case SM_CYSIZEFRAME = 33;
    case SM_CYSMCAPTION = 51;
    case SM_CYSMICON = 50;
    case SM_CYSMSIZE = 53;
    case SM_CYVIRTUALSCREEN = 79;
    case SM_CYVSCROLL = 20;
    case SM_CYVTHUMB = 9;
    case SM_DBCSENABLED = 42;
    case SM_DEBUG = 22;
    case SM_DIGITIZER = 94;
    case SM_IMMENABLED = 82;
    case SM_MAXIMUMTOUCHES = 95;
    case SM_MEDIACENTER = 87;
    case SM_MENUDROPALIGNMENT = 40;
    case SM_MIDEASTENABLED = 74;
    case SM_MOUSEHORIZONTALWHEELPRESENT = 91;
    case SM_MOUSEPRESENT = 19;
    case SM_MOUSEWHEELPRESENT = 75;
    case SM_NETWORK = 63;
    case SM_PENWINDOWS = 41;
    case SM_REMOTECONTROL = 0x2001;
    case SM_REMOTESESSION = 0x1000;
    case SM_SAMEDISPLAYFORMAT = 81;
    case SM_SECURE = 44;
    case SM_SERVERR2 = 89;
    case SM_SHOWSOUNDS = 70;
    case SM_SHUTTINGDOWN = 0x2000;
    case SM_SLOWMACHINE = 73;
    case SM_STARTER = 88;
    case SM_SWAPBUTTON = 23;
    case SM_SYSTEMDOCKED = 0x2004;
    case SM_TABLETPC = 86;
    case SM_XVIRTUALSCREEN = 76;
    case SM_YVIRTUALSCREEN = 77;
}
