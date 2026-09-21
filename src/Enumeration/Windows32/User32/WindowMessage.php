<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\User32;

abstract class WindowMessage
{
    const WM_ACCESS_WINDOW = 0x004f;
    /**
     * Sent when a window is activated/deactivated
     */
    const WM_ACTIVATE = 0x0006;
    const WM_ACTIVATEAPP = 0x001c;
    const WM_AFX1 = 0x0361;
    const WM_AFX10 = 0x036a;
    const WM_AFX11 = 0x036b;
    const WM_AFX12 = 0x036c;
    const WM_AFX13 = 0x036d;
    const WM_AFX14 = 0x036e;
    const WM_AFX15 = 0x036f;
    const WM_AFX16 = 0x0370;
    const WM_AFX17 = 0x0371;
    const WM_AFX18 = 0x0372;
    const WM_AFX19 = 0x0373;
    const WM_AFX2 = 0x0362;
    const WM_AFX20 = 0x0374;
    const WM_AFX21 = 0x0375;
    const WM_AFX22 = 0x0376;
    const WM_AFX23 = 0x0377;
    const WM_AFX24 = 0x0378;
    const WM_AFX25 = 0x0379;
    const WM_AFX26 = 0x037a;
    const WM_AFX27 = 0x037b;
    const WM_AFX28 = 0x037c;
    const WM_AFX29 = 0x037d;
    const WM_AFX3 = 0x0363;
    const WM_AFX30 = 0x037e;
    const WM_AFX4 = 0x0364;
    const WM_AFX5 = 0x0365;
    const WM_AFX6 = 0x0366;
    const WM_AFX7 = 0x0367;
    const WM_AFX8 = 0x0368;
    const WM_AFX9 = 0x0369;
    const WM_AFXFIRST = 0x0360;
    const WM_AFXLAST = 0x037f;
    const WM_ALTTABACTIVE = 0x0029;
    /**
     * Base for application-defined messages
     */
    const WM_APP = 0x8000;
    const WM_APPCOMMAND = 0x0319;
    const WM_ASKCBFORMATNAME = 0x030c;
    const WM_BEGINDRAG = 0x022c;
    const WM_BRIGHTNESSCHANGED = 0x02ce;
    const WM_BSDRDATA = 0x0329;
    const WM_CANCELJOURNAL = 0x004b;
    const WM_CANCELMODE = 0x001f;
    const WM_CAPTURECHANGED = 0x0215;
    const WM_CAP_DRIVER_GET_NAMEW = 0x0470;
    const WM_CAP_DRIVER_GET_VERSIONW = 0x0471;
    const WM_CAP_FILE_GET_CAPTURE_FILEW = 0x0479;
    const WM_CAP_FILE_SAVEASW = 0x047b;
    const WM_CAP_FILE_SAVEDIBW = 0x047d;
    const WM_CAP_FILE_SET_CAPTURE_FILEW = 0x0478;
    const WM_CAP_GET_MCI_DEVICEW = 0x04a7;
    const WM_CAP_PAL_OPENW = 0x04b4;
    const WM_CAP_PAL_SAVEW = 0x04b5;
    const WM_CAP_SET_CALLBACK_ERRORW = 0x0466;
    const WM_CAP_SET_CALLBACK_STATUSW = 0x0467;
    const WM_CAP_SET_MCI_DEVICEW = 0x04a6;
    const WM_CAP_UNICODE_START = 0x0464;
    const WM_CBT_RESERVED1 = 0x03f1;
    const WM_CBT_RESERVED10 = 0x03fa;
    const WM_CBT_RESERVED11 = 0x03fb;
    const WM_CBT_RESERVED12 = 0x03fc;
    const WM_CBT_RESERVED13 = 0x03fd;
    const WM_CBT_RESERVED14 = 0x03fe;
    const WM_CBT_RESERVED2 = 0x03f2;
    const WM_CBT_RESERVED3 = 0x03f3;
    const WM_CBT_RESERVED4 = 0x03f4;
    const WM_CBT_RESERVED5 = 0x03f5;
    const WM_CBT_RESERVED6 = 0x03f6;
    const WM_CBT_RESERVED7 = 0x03f7;
    const WM_CBT_RESERVED8 = 0x03f8;
    const WM_CBT_RESERVED9 = 0x03f9;
    const WM_CBT_RESERVED_FIRST = 0x03f0;
    const WM_CBT_RESERVED_LAST = 0x03ff;
    const WM_CE_ONLY10 = 0x033a;
    const WM_CE_ONLY11 = 0x033b;
    const WM_CE_ONLY12 = 0x033c;
    const WM_CE_ONLY13 = 0x033d;
    const WM_CE_ONLY2 = 0x0332;
    const WM_CE_ONLY5 = 0x0335;
    const WM_CE_ONLY6 = 0x0336;
    const WM_CE_ONLY7 = 0x0337;
    const WM_CE_ONLY8 = 0x0338;
    const WM_CE_ONLY9 = 0x0339;
    const WM_CE_ONLY_LAST = 0x033e;
    const WM_CHANGECBCHAIN = 0x030d;
    const WM_CHANGEUISTATE = 0x0127;
    /**
     * Character input
     */
    const WM_CHAR = 0x0102;
    const WM_CHARTOITEM = 0x002f;
    const WM_CHILDACTIVATE = 0x0022;
    const WM_CHOOSEFONT_GETLOGFONT = 0x0401;
    const WM_CHOOSEFONT_SETFLAGS = 0x0466;
    const WM_CHOOSEFONT_SETLOGFONT = 0x0465;
    const WM_CLEAR = 0x0303;
    const WM_CLIENTSHUTDOWN = 0x003b;
    const WM_CLIPBOARDUPDATE = 0x031d;
    /**
     * Request to close window
     */
    const WM_CLOSE = 0x0010;
    const WM_COALESCE1 = 0x0391;
    const WM_COALESCE10 = 0x039a;
    const WM_COALESCE11 = 0x039b;
    const WM_COALESCE12 = 0x039c;
    const WM_COALESCE13 = 0x039d;
    const WM_COALESCE14 = 0x039e;
    const WM_COALESCE2 = 0x0392;
    const WM_COALESCE3 = 0x0393;
    const WM_COALESCE4 = 0x0394;
    const WM_COALESCE5 = 0x0395;
    const WM_COALESCE6 = 0x0396;
    const WM_COALESCE7 = 0x0397;
    const WM_COALESCE8 = 0x0398;
    const WM_COALESCE9 = 0x0399;
    const WM_COALESCE_FIRST = 0x0390;
    const WM_COALESCE_LAST = 0x039f;
    /**
     * Menu or control command
     */
    const WM_COMMAND = 0x0111;
    const WM_COMMNOTIFY = 0x0044;
    const WM_COMPACTING = 0x0041;
    const WM_COMPAREITEM = 0x0039;
    const WM_CONSOLIDATED = 0x0273;
    const WM_CONTEXTMENU = 0x007b;
    const WM_CONVERTREQUEST = 0x010a;
    const WM_CONVERTRESULT = 0x010b;
    const WM_COPY = 0x0301;
    const WM_COPYDATA = 0x004a;
    const WM_COPYGLOBALDATA = 0x0049;
    const WM_CPL_LAUNCH = 0x07e8;
    const WM_CPL_LAUNCHED = 0x07e9;
    /**
     * Sent when a window is being created
     */
    const WM_CREATE = 0x0001;
    const WM_CTLCOLOR = 0x0019;
    const WM_CTLCOLORBTN = 0x0135;
    const WM_CTLCOLORDLG = 0x0136;
    const WM_CTLCOLOREDIT = 0x0133;
    const WM_CTLCOLORLISTBOX = 0x0134;
    const WM_CTLCOLORMSGBOX = 0x0132;
    const WM_CTLCOLORSCROLLBAR = 0x0137;
    const WM_CTLCOLORSTATIC = 0x0138;
    const WM_CTLINIT = 0x0387;
    const WM_CUT = 0x0300;
    const WM_DDEMLEVENT = 0x003c;
    /**
     * Dead character input
     */
    const WM_DEADCHAR = 0x0103;
    const WM_DELETEITEM = 0x002d;
    const WM_DESKTOPNOTIFY = 0x031c;
    /**
     * Sent when a window is being destroyed
     */
    const WM_DESTROY = 0x0002;
    const WM_DESTROYCLIPBOARD = 0x0307;
    const WM_DEVICECHANGE = 0x0219;
    const WM_DEVMODECHANGE = 0x001b;
    const WM_DISPLAYCHANGE = 0x007e;
    const WM_DPICHANGED = 0x02e0;
    const WM_DRAGLOOP = 0x022d;
    const WM_DRAGMOVE = 0x022f;
    const WM_DRAGSELECT = 0x022e;
    const WM_DRAWCLIPBOARD = 0x0308;
    const WM_DRAWITEM = 0x002b;
    const WM_DROPFILES = 0x0233;
    const WM_DROPOBJECT = 0x022a;
    const WM_DWMCOLORIZATIONCOLORCHANGED = 0x0320;
    const WM_DWMCOMPOSITIONCHANGED = 0x031e;
    const WM_DWMEXILEFRAME = 0x0322;
    const WM_DWMNCRENDERINGCHANGED = 0x031f;
    const WM_DWMSENDICONICLIVEPREVIEWBITMAP = 0x0326;
    const WM_DWMSENDICONICTHUMBNAIL = 0x0323;
    const WM_DWMTHUMBNAILSIZECHANGED = 0x0327;
    const WM_DWMTRANSITIONSTATECHANGED = 0x032a;
    const WM_DWMWINDOWMAXIMIZEDCHANGE = 0x0321;
    const WM_EDGYINERTIA = 0x023d;
    /**
     * Enable/disable a window
     */
    const WM_ENABLE = 0x000a;
    const WM_ENDINERTIA = 0x023c;
    const WM_ENDSESSION = 0x0016;
    const WM_ENTERIDLE = 0x0121;
    const WM_ENTERMENULOOP = 0x0211;
    const WM_ENTERSIZEMOVE = 0x0231;
    const WM_ERASEBKGND = 0x0014;
    const WM_EXITMENULOOP = 0x0212;
    const WM_EXITPROCESS = 0x0315;
    const WM_EXITSIZEMOVE = 0x0232;
    const WM_FINALDESTROY = 0x0070;
    const WM_FLICK = 0x02cb;
    const WM_FLICKINTERNAL = 0x02cd;
    const WM_FONTCHANGE = 0x001d;
    const WM_FORWARDKEYDOWN = 0x0333;
    const WM_FORWARDKEYUP = 0x0334;
    const WM_FULLSCREEN = 0x003a;
    const WM_GESTURE = 0x0119;
    const WM_GESTUREINPUT = 0x011b;
    const WM_GESTURENOTIFIED = 0x011c;
    const WM_GESTURENOTIFY = 0x011a;
    const WM_GETACTIONTEXT = 0x0331;
    const WM_GETDLGCODE = 0x0087;
    const WM_GETFONT = 0x0031;
    const WM_GETHOTKEY = 0x0033;
    const WM_GETICON = 0x007f;
    const WM_GETMINMAXINFO = 0x0024;
    const WM_GETOBJECT = 0x003d;
    const WM_GETTEXT = 0x000d;
    const WM_GETTEXTLENGTH = 0x000e;
    const WM_GETTITLEBARINFOEX = 0x033f;
    const WM_GLOBALRCCHANGE = 0x0383;
    const WM_HANDHELDFIRST = 0x0358;
    const WM_HANDHELDLAST = 0x035f;
    const WM_HANDHELD_reserved_359 = 0x0359;
    const WM_HANDHELD_reserved_35a = 0x035a;
    const WM_HANDHELD_reserved_35b = 0x035b;
    const WM_HANDHELD_reserved_35c = 0x035c;
    const WM_HANDHELD_reserved_35d = 0x035d;
    const WM_HANDHELD_reserved_35e = 0x035e;
    const WM_HEDITCTL = 0x0385;
    const WM_HELP = 0x0053;
    const WM_HOOKMSG = 0x0314;
    const WM_HOOKRCRESULT = 0x0382;
    const WM_HOTKEY = 0x0312;
    const WM_HSCROLL = 0x0114;
    const WM_HSCROLLCLIPBOARD = 0x030e;
    const WM_ICONERASEBKGND = 0x0027;
    const WM_IMEKEYDOWN = 0x0290;
    const WM_IMEKEYUP = 0x0291;
    const WM_IME_CHAR = 0x0286;
    const WM_IME_COMPOSITION = 0x010f;
    const WM_IME_COMPOSITIONFULL = 0x0284;
    const WM_IME_CONTROL = 0x0283;
    const WM_IME_ENDCOMPOSITION = 0x010e;
    const WM_IME_KEYDOWN = 0x0290;
    const WM_IME_KEYLAST = 0x010f;
    const WM_IME_KEYUP = 0x0291;
    const WM_IME_NOTIFY = 0x0282;
    const WM_IME_REPORT = 0x0280;
    const WM_IME_REQUEST = 0x0288;
    const WM_IME_SELECT = 0x0285;
    const WM_IME_SETCONTEXT = 0x0281;
    const WM_IME_STARTCOMPOSITION = 0x010d;
    const WM_IME_SYSTEM = 0x0287;
    const WM_INITDIALOG = 0x0110;
    /**
     * Menu is about to be displayed
     */
    const WM_INITMENU = 0x0116;
    /**
     * Popup menu is about to be displayed
     */
    const WM_INITMENUPOPUP = 0x0117;
    const WM_INPUT = 0x00ff;
    const WM_INPUTLANGCHANGE = 0x0051;
    const WM_INPUTLANGCHANGEREQUEST = 0x0050;
    const WM_INPUT_DEVICE_CHANGE = 0x00fe;
    const WM_INTERIM = 0x010c;
    const WM_INTERNAL_DDE1 = 0x03e1;
    const WM_INTERNAL_DDE10 = 0x03ea;
    const WM_INTERNAL_DDE11 = 0x03eb;
    const WM_INTERNAL_DDE12 = 0x03ec;
    const WM_INTERNAL_DDE13 = 0x03ed;
    const WM_INTERNAL_DDE14 = 0x03ee;
    const WM_INTERNAL_DDE2 = 0x03e2;
    const WM_INTERNAL_DDE3 = 0x03e3;
    const WM_INTERNAL_DDE4 = 0x03e4;
    const WM_INTERNAL_DDE5 = 0x03e5;
    const WM_INTERNAL_DDE6 = 0x03e6;
    const WM_INTERNAL_DDE7 = 0x03e7;
    const WM_INTERNAL_DDE8 = 0x03e8;
    const WM_INTERNAL_DDE9 = 0x03e9;
    const WM_INTERNAL_DDE_FIRST = 0x03e0;
    const WM_INTERNAL_DDE_LAST = 0x03ef;
    const WM_ISACTIVEICON = 0x0035;
    const WM_KANJI10 = 0x028a;
    const WM_KANJI11 = 0x028b;
    const WM_KANJI12 = 0x028c;
    const WM_KANJI13 = 0x028d;
    const WM_KANJI14 = 0x028e;
    const WM_KANJI15 = 0x028f;
    const WM_KANJI18 = 0x0292;
    const WM_KANJI19 = 0x0293;
    const WM_KANJI20 = 0x0294;
    const WM_KANJI21 = 0x0295;
    const WM_KANJI22 = 0x0296;
    const WM_KANJI23 = 0x0297;
    const WM_KANJI24 = 0x0298;
    const WM_KANJI25 = 0x0299;
    const WM_KANJI26 = 0x029a;
    const WM_KANJI27 = 0x029b;
    const WM_KANJI28 = 0x029c;
    const WM_KANJI29 = 0x029d;
    const WM_KANJI30 = 0x029e;
    const WM_KANJI9 = 0x0289;
    const WM_KANJILAST = 0x029f;
    const WM_KEYBOARDCORRECTIONACTION = 0x032d;
    const WM_KEYBOARDCORRECTIONCALLOUT = 0x032c;
    /**
     * Key is pressed
     */
    const WM_KEYDOWN = 0x0100;
    const WM_KEYF1 = 0x004d;
    const WM_KEYFIRST = 0x0100;
    const WM_KEYLAST = 0x0108;
    /**
     * Key is released
     */
    const WM_KEYUP = 0x0101;
    /**
     * Window loses keyboard focus
     */
    const WM_KILLFOCUS = 0x0008;
    const WM_KLUDGEMINRECT = 0x008b;
    const WM_LBTRACKPOINT = 0x0131;
    const WM_LBUTTONDBLCLK = 0x0203;
    /**
     * Left mouse button pressed
     */
    const WM_LBUTTONDOWN = 0x0201;
    /**
     * Left mouse button released
     */
    const WM_LBUTTONUP = 0x0202;
    const WM_LOGOFF = 0x0025;
    const WM_LPKDRAWSWITCHWND = 0x008c;
    const WM_MAGNIFICATION_ENDED = 0x0325;
    const WM_MAGNIFICATION_OUTPUT = 0x0328;
    const WM_MAGNIFICATION_STARTED = 0x0324;
    const WM_MBUTTONDBLCLK = 0x0209;
    const WM_MBUTTONDOWN = 0x0207;
    const WM_MBUTTONUP = 0x0208;
    const WM_MDIACTIVATE = 0x0222;
    const WM_MDICASCADE = 0x0227;
    const WM_MDICREATE = 0x0220;
    const WM_MDIDESTROY = 0x0221;
    const WM_MDIGETACTIVE = 0x0229;
    const WM_MDIICONARRANGE = 0x0228;
    const WM_MDIMAXIMIZE = 0x0225;
    const WM_MDINEXT = 0x0224;
    const WM_MDIREFRESHMENU = 0x0234;
    const WM_MDIRESTORE = 0x0223;
    const WM_MDISETMENU = 0x0230;
    const WM_MDITILE = 0x0226;
    const WM_MEASURECONTROL = 0x0330;
    const WM_MEASUREITEM = 0x002c;
    const WM_MEASUREITEM_CLIENTDATA = 0x0071;
    const WM_MENUCHAR = 0x0120;
    const WM_MENUCOMMAND = 0x0126;
    const WM_MENUDRAG = 0x0123;
    const WM_MENUGETOBJECT = 0x0124;
    const WM_MENURBUTTONUP = 0x0122;
    /**
     * Menu item selected
     */
    const WM_MENUSELECT = 0x011f;
    const WM_MM_RESERVED1 = 0x03a1;
    const WM_MM_RESERVED10 = 0x03aa;
    const WM_MM_RESERVED11 = 0x03ab;
    const WM_MM_RESERVED12 = 0x03ac;
    const WM_MM_RESERVED13 = 0x03ad;
    const WM_MM_RESERVED14 = 0x03ae;
    const WM_MM_RESERVED15 = 0x03af;
    const WM_MM_RESERVED16 = 0x03b0;
    const WM_MM_RESERVED17 = 0x03b1;
    const WM_MM_RESERVED18 = 0x03b2;
    const WM_MM_RESERVED19 = 0x03b3;
    const WM_MM_RESERVED2 = 0x03a2;
    const WM_MM_RESERVED20 = 0x03b4;
    const WM_MM_RESERVED21 = 0x03b5;
    const WM_MM_RESERVED22 = 0x03b6;
    const WM_MM_RESERVED23 = 0x03b7;
    const WM_MM_RESERVED24 = 0x03b8;
    const WM_MM_RESERVED25 = 0x03b9;
    const WM_MM_RESERVED26 = 0x03ba;
    const WM_MM_RESERVED27 = 0x03bb;
    const WM_MM_RESERVED28 = 0x03bc;
    const WM_MM_RESERVED29 = 0x03bd;
    const WM_MM_RESERVED3 = 0x03a3;
    const WM_MM_RESERVED30 = 0x03be;
    const WM_MM_RESERVED31 = 0x03bf;
    const WM_MM_RESERVED32 = 0x03c0;
    const WM_MM_RESERVED33 = 0x03c1;
    const WM_MM_RESERVED34 = 0x03c2;
    const WM_MM_RESERVED35 = 0x03c3;
    const WM_MM_RESERVED36 = 0x03c4;
    const WM_MM_RESERVED37 = 0x03c5;
    const WM_MM_RESERVED38 = 0x03c6;
    const WM_MM_RESERVED39 = 0x03c7;
    const WM_MM_RESERVED4 = 0x03a4;
    const WM_MM_RESERVED40 = 0x03c8;
    const WM_MM_RESERVED41 = 0x03c9;
    const WM_MM_RESERVED42 = 0x03ca;
    const WM_MM_RESERVED43 = 0x03cb;
    const WM_MM_RESERVED44 = 0x03cc;
    const WM_MM_RESERVED45 = 0x03cd;
    const WM_MM_RESERVED46 = 0x03ce;
    const WM_MM_RESERVED47 = 0x03cf;
    const WM_MM_RESERVED48 = 0x03d0;
    const WM_MM_RESERVED49 = 0x03d1;
    const WM_MM_RESERVED5 = 0x03a5;
    const WM_MM_RESERVED50 = 0x03d2;
    const WM_MM_RESERVED51 = 0x03d3;
    const WM_MM_RESERVED52 = 0x03d4;
    const WM_MM_RESERVED53 = 0x03d5;
    const WM_MM_RESERVED54 = 0x03d6;
    const WM_MM_RESERVED55 = 0x03d7;
    const WM_MM_RESERVED56 = 0x03d8;
    const WM_MM_RESERVED57 = 0x03d9;
    const WM_MM_RESERVED58 = 0x03da;
    const WM_MM_RESERVED59 = 0x03db;
    const WM_MM_RESERVED6 = 0x03a6;
    const WM_MM_RESERVED61 = 0x03dc;
    const WM_MM_RESERVED62 = 0x03dd;
    const WM_MM_RESERVED63 = 0x03de;
    const WM_MM_RESERVED7 = 0x03a7;
    const WM_MM_RESERVED8 = 0x03a8;
    const WM_MM_RESERVED9 = 0x03a9;
    const WM_MM_RESERVED_FIRST = 0x03a0;
    const WM_MM_RESERVED_LAST = 0x03df;
    const WM_MOUSEACTIVATE = 0x0021;
    const WM_MOUSEFIRST = 0x0200;
    const WM_MOUSEHOVER = 0x02a1;
    const WM_MOUSEHWHEEL = 0x020e;
    const WM_MOUSELAST = 0x0209;
    /**
     * Mouse leaves window
     */
    const WM_MOUSELEAVE = 0x02a3;
    /**
     * Mouse moved
     */
    const WM_MOUSEMOVE = 0x0200;
    /**
     * Mouse wheel rotated
     */
    const WM_MOUSEWHEEL = 0x020a;
    /**
     * Sent when a window is moved
     */
    const WM_MOVE = 0x0003;
    const WM_MOVING = 0x0216;
    const WM_NCACTIVATE = 0x0086;
    const WM_NCCALCSIZE = 0x0083;
    const WM_NCCREATE = 0x0081;
    const WM_NCDESTROY = 0x0082;
    const WM_NCHITTEST = 0x0084;
    const WM_NCLBUTTONDBLCLK = 0x00a3;
    /**
     * left mouse button is pressed in a non-client area of a window
     * 
     * Title bar
     * Window borders
     * Scroll bars
     * Menu bar
     * System buttons (minimize, maximize, close)
     */
    const WM_NCLBUTTONDOWN = 0x00a1;
    const WM_NCLBUTTONUP = 0x00a2;
    const WM_NCMBUTTONDBLCLK = 0x00a9;
    const WM_NCMBUTTONDOWN = 0x00a7;
    const WM_NCMBUTTONUP = 0x00a8;
    const WM_NCMOUSEHOVER = 0x02a0;
    const WM_NCMOUSELEAVE = 0x02a2;
    const WM_NCMOUSEMOVE = 0x00a0;
    const WM_NCPAINT = 0x0085;
    const WM_NCPOINTERDOWN = 0x0242;
    const WM_NCPOINTERLAST = 0x0244;
    const WM_NCPOINTERUP = 0x0243;
    const WM_NCPOINTERUPDATE = 0x0241;
    const WM_NCRBUTTONDBLCLK = 0x00a6;
    const WM_NCRBUTTONDOWN = 0x00a4;
    const WM_NCRBUTTONUP = 0x00a5;
    const WM_NCUAHDRAWCAPTION = 0x00ae;
    const WM_NCUAHDRAWFRAME = 0x00af;
    const WM_NCXBUTTONDBLCLK = 0x00ad;
    const WM_NCXBUTTONDOWN = 0x00ab;
    const WM_NCXBUTTONUP = 0x00ac;
    const WM_NEXTDLGCTL = 0x0028;
    const WM_NEXTMENU = 0x0213;
    /**
     * Notification from a control
     */
    const WM_NOTIFY = 0x004e;
    const WM_NOTIFYFORMAT = 0x0055;
    const WM_NOTIFYWOW = 0x0340;
    /**
     * No operation
     */
    const WM_NULL = 0x0000;
    const WM_OTHERWINDOWCREATED = 0x0042;
    const WM_OTHERWINDOWDESTROYED = 0x0043;
    /**
     * Request to repaint window
     */
    const WM_PAINT = 0x000f;
    const WM_PAINTCLIPBOARD = 0x0309;
    const WM_PAINTICON = 0x0026;
    const WM_PALETTECHANGED = 0x0311;
    const WM_PALETTEISCHANGING = 0x0310;
    const WM_PARENTNOTIFY = 0x0210;
    const WM_PASTE = 0x0302;
    const WM_PENCTL = 0x0385;
    const WM_PENEVENT = 0x0388;
    const WM_PENMISC = 0x0386;
    const WM_PENMISCINFO = 0x0383;
    const WM_PENWIN10 = 0x038a;
    const WM_PENWIN11 = 0x038b;
    const WM_PENWIN12 = 0x038c;
    const WM_PENWIN13 = 0x038d;
    const WM_PENWIN14 = 0x038e;
    const WM_PENWIN9 = 0x0389;
    const WM_PENWINFIRST = 0x0380;
    const WM_PENWINLAST = 0x038f;
    const WM_POINTER1 = 0x0248;
    const WM_POINTER2 = 0x0250;
    const WM_POINTER3 = 0x0251;
    const WM_POINTER4 = 0x0252;
    const WM_POINTER5 = 0x0253;
    const WM_POINTER6 = 0x0254;
    const WM_POINTER7 = 0x0255;
    const WM_POINTER8 = 0x0256;
    const WM_POINTERACTIVATE = 0x024b;
    const WM_POINTERCAPTURECHANGED = 0x024c;
    const WM_POINTERDEVICEADDED = 0x02c8;
    const WM_POINTERDEVICECHANGE = 0x0238;
    const WM_POINTERDEVICEDELETED = 0x02c9;
    const WM_POINTERDEVICEINRANGE = 0x0239;
    const WM_POINTERDEVICEOUTOFRANGE = 0x023a;
    const WM_POINTERDOWN = 0x0246;
    const WM_POINTERENTER = 0x0249;
    const WM_POINTERHWHEEL = 0x024f;
    const WM_POINTERLAST = 0x0257;
    const WM_POINTERLEAVE = 0x024a;
    const WM_POINTERUP = 0x0247;
    const WM_POINTERUPDATE = 0x0245;
    const WM_POINTERWHEEL = 0x024e;
    const WM_POWER = 0x0048;
    const WM_POWERBROADCAST = 0x0218;
    /**
     * Request to print window
     */
    const WM_PRINT = 0x0317;
    /**
     * Print client area
     */
    const WM_PRINTCLIENT = 0x0318;
    const WM_PSD_ENVSTAMPRECT = 0x0405;
    const WM_PSD_FULLPAGERECT = 0x0401;
    const WM_PSD_GREEKTEXTRECT = 0x0404;
    const WM_PSD_MARGINRECT = 0x0403;
    const WM_PSD_MINMARGINRECT = 0x0402;
    const WM_PSD_PAGESETUPDLG = 0x0400;
    const WM_PSD_YAFULLPAGERECT = 0x0406;
    const WM_QUERYDRAGICON = 0x0037;
    const WM_QUERYDROPOBJECT = 0x022b;
    const WM_QUERYENDSESSION = 0x0011;
    const WM_QUERYNEWPALETTE = 0x030f;
    const WM_QUERYOPEN = 0x0013;
    const WM_QUERYPARKICON = 0x0036;
    const WM_QUERYUISTATE = 0x0129;
    const WM_QUEUESYNC = 0x0023;
    /**
     * Application is quitting
     */
    const WM_QUIT = 0x0012;
    const WM_RASDIALEVENT = 0xcccd;
    const WM_RBUTTONDBLCLK = 0x0206;
    /**
     * Right mouse button pressed
     */
    const WM_RBUTTONDOWN = 0x0204;
    /**
     * Right mouse button released
     */
    const WM_RBUTTONUP = 0x0205;
    const WM_RCRESULT = 0x0381;
    const WM_RENDERALLFORMATS = 0x0306;
    const WM_RENDERFORMAT = 0x0305;
    const WM_ROUTED_UI_EVENT = 0x032f;
    const WM_SETCURSOR = 0x0020;
    /**
     * Window gains keyboard focus
     */
    const WM_SETFOCUS = 0x0007;
    const WM_SETFONT = 0x0030;
    const WM_SETHOTKEY = 0x0032;
    const WM_SETICON = 0x0080;
    const WM_SETREDRAW = 0x000b;
    const WM_SETTEXT = 0x000c;
    const WM_SETVISIBLE = 0x0009;
    const WM_SHELLNOTIFY = 0x0034;
    const WM_SHOWWINDOW = 0x0018;
    /**
     * Sent when a window is resized
     */
    const WM_SIZE = 0x0005;
    const WM_SIZECLIPBOARD = 0x030b;
    const WM_SIZEWAIT = 0x0004;
    const WM_SIZING = 0x0214;
    const WM_SKB = 0x0384;
    const WM_SPOOLERSTATUS = 0x002a;
    const WM_STOPINERTIA = 0x023b;
    const WM_STYLECHANGED = 0x007d;
    const WM_STYLECHANGING = 0x007c;
    const WM_SYNCPAINT = 0x0088;
    const WM_SYNCTASK = 0x0089;
    /**
     * System character input
     */
    const WM_SYSCHAR = 0x0106;
    const WM_SYSCOLORCHANGE = 0x0015;
    const WM_SYSCOMMAND = 0x0112;
    const WM_SYSDEADCHAR = 0x0107;
    /**
     * System key pressed (Alt, etc.)
     */
    const WM_SYSKEYDOWN = 0x0104;
    /**
     * System key released
     */
    const WM_SYSKEYUP = 0x0105;
    const WM_SYSMENU = 0x0313;
    const WM_SYSTEMERROR = 0x0017;
    const WM_SYSTIMER = 0x0118;
    const WM_TABLET1 = 0x02c1;
    const WM_TABLET10_2ca = 0x02ca;
    const WM_TABLET12_2cc = 0x02cc;
    const WM_TABLET15 = 0x02cf;
    const WM_TABLET16 = 0x02d0;
    const WM_TABLET17 = 0x02d1;
    const WM_TABLET18 = 0x02d2;
    const WM_TABLET19 = 0x02d3;
    const WM_TABLET2 = 0x02c2;
    const WM_TABLET20 = 0x02d4;
    const WM_TABLET21 = 0x02d5;
    const WM_TABLET22 = 0x02d6;
    const WM_TABLET23 = 0x02d7;
    const WM_TABLET24 = 0x02d8;
    const WM_TABLET25 = 0x02d9;
    const WM_TABLET26 = 0x02da;
    const WM_TABLET27 = 0x02db;
    const WM_TABLET28 = 0x02dc;
    const WM_TABLET29 = 0x02dd;
    const WM_TABLET3 = 0x02c3;
    const WM_TABLET30 = 0x02de;
    const WM_TABLET4 = 0x02c4;
    const WM_TABLET5 = 0x02c5;
    const WM_TABLET6 = 0x02c6;
    const WM_TABLET7 = 0x02c7;
    const WM_TABLET_FIRST = 0x02c0;
    const WM_TABLET_LAST = 0x02df;
    const WM_TCARD = 0x0052;
    const WM_TESTING = 0x0040;
    const WM_THEMECHANGED = 0x031a;
    const WM_TIMECHANGE = 0x001e;
    /**
     * Timer event
     */
    const WM_TIMER = 0x0113;
    const WM_TOUCH = 0x0240;
    const WM_TOUCHHITTESTING = 0x024d;
    const WM_TRACKMOUSEEVENT0 = 0x02a4;
    const WM_TRACKMOUSEEVENT1 = 0x02a5;
    const WM_TRACKMOUSEEVENT10 = 0x02ae;
    const WM_TRACKMOUSEEVENT2 = 0x02a6;
    const WM_TRACKMOUSEEVENT3 = 0x02a7;
    const WM_TRACKMOUSEEVENT4 = 0x02a8;
    const WM_TRACKMOUSEEVENT5 = 0x02a9;
    const WM_TRACKMOUSEEVENT6 = 0x02aa;
    const WM_TRACKMOUSEEVENT7 = 0x02ab;
    const WM_TRACKMOUSEEVENT8 = 0x02ac;
    const WM_TRACKMOUSEEVENT9 = 0x02ad;
    const WM_TRACKMOUSEEVENT_LAST = 0x02af;
    const WM_UAHDESTROYWINDOW = 0x0090;
    const WM_UAHDRAWMENU = 0x0091;
    const WM_UAHDRAWMENUITEM = 0x0092;
    const WM_UAHINIT = 0x031b;
    const WM_UAHINITMENU = 0x0093;
    const WM_UAHMEASUREMENUITEM = 0x0094;
    const WM_UAHNCPAINTMENUPOPUP = 0x0095;
    const WM_UAHUPDATE = 0x0096;
    const WM_UIACTION = 0x032e;
    const WM_UNDO = 0x0304;
    const WM_UNICHAR = 0x0109;
    const WM_UNINITMENUPOPUP = 0x0125;
    const WM_UNREGISTER_WINDOW_SERVICES = 0x0272;
    const WM_UPDATEUISTATE = 0x0128;
    /**
     * Base for user-defined messages
     */
    const WM_USER = 0x0400;
    const WM_USERCHANGED = 0x0054;
    const WM_VIEWSTATECHANGED = 0x0271;
    const WM_VISIBILITYCHANGED = 0x0270;
    const WM_VKEYTOITEM = 0x002e;
    const WM_VSCROLL = 0x0115;
    const WM_VSCROLLCLIPBOARD = 0x030a;
    const WM_WAKETHREAD = 0x0316;
    const WM_WINDOWPOSCHANGED = 0x0047;
    const WM_WINDOWPOSCHANGING = 0x0046;
    const WM_WINHELP = 0x0038;
    const WM_WININICHANGE = 0x001a;
    const WM_WNT_CONVERTREQUESTEX = 0x0109;
    const WM_WTSSESSION_CHANGE = 0x02b1;
    const WM_XBUTTONDBLCLK = 0x020d;
    const WM_XBUTTONDOWN = 0x020b;
    const WM_XBUTTONUP = 0x020c;
    const WM_YOMICHAR = 0x0108;

    const WM_CAP_START             = 0x0400;
    const WM_CAP_DRIVER_CONNECT    = WM_CAP_START + 10; // Connect driver #N
    const WM_CAP_DRIVER_DISCONNECT = WM_CAP_START + 11;
    const WM_CAP_GRAB_FRAME        = WM_CAP_START + 60; // Grab single frame
    const WM_CAP_FILE_SAVEDIB      = WM_CAP_START + 25; // Save frame as .bmp
}
