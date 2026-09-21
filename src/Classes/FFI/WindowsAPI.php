<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI\Windows;

#region use

use Clover\Classes\Date\Date;
use Clover\Classes\Device\StorageDevice;
use Clover\Classes\Device\USBVendor;
use Clover\Classes\FFI\FFIWrapper;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\OperationSystem;
use Clover\Classes\OperationSystem\ServerAPI;
use Clover\Enumeration\Windows32\AdvApi32\RegistryOption;
use Clover\Enumeration\Windows32\Dialog\CommonConstraints;
use Clover\Enumeration\Windows32\Dialog\GetSystemMetrics;
use Clover\Enumeration\Windows32\Dialog\MouseEvent;
use Clover\Enumeration\Windows32\Dialog\SystemParametersInfo;
use Clover\Enumeration\Windows32\Gdi32\BitBit;
use Clover\Enumeration\Windows32\Gdi32\NotifyIconField;
use Clover\Enumeration\Windows32\Gdi32\NotifyIconMessage;
use Clover\Enumeration\Windows32\Gdi32\NotifyIconNotification;
use Clover\Enumeration\Windows32\Kernel\AtaFlags;
use Clover\Enumeration\Windows32\Kernel\GetStdHandle;
use Clover\Enumeration\Windows32\Kernel\IOControl;
use Clover\Enumeration\Windows32\Kernel\OpenProcess;
use Clover\Enumeration\Windows32\Kernel\PlaySound;
use Clover\Enumeration\Windows32\Kernel\ProcessCreation;
use Clover\Enumeration\Windows32\Kernel\ReadConsoleInput;
use Clover\Enumeration\Windows32\Kernel\SetConsoleMode;
use Clover\Enumeration\Windows32\Kernel\SmartFlags;
use Clover\Enumeration\Windows32\Kernel\StorageControlStorageProperty;
use Clover\Enumeration\Windows32\Registry;
use Clover\Enumeration\Windows32\RegistryKey;
use Clover\Enumeration\Windows32\SetupAPI\DeviceInformationGetClassFlags;
use Clover\Enumeration\Windows32\SetupAPI\SetupAPIDeviceRegistryProperty;
use Clover\Enumeration\Windows32\SystemMetric;
use Clover\Enumeration\Windows32\User32\ChangeDisplaySettings;
use Clover\Enumeration\Windows32\User32\DevMode;
use Clover\Enumeration\Windows32\User32\EnumDisplaySettings;
use Clover\Enumeration\Windows32\User32\FlashWindowEx;
use Clover\Enumeration\Windows32\User32\IconIdentifier;
use Clover\Enumeration\Windows32\User32\KeyboardEvent;
use Clover\Enumeration\Windows32\User32\LayeredWindowAttribute;
use Clover\Enumeration\Windows32\User32\LoadResource;
use Clover\Enumeration\Windows32\User32\MessageControl;
use Clover\Enumeration\Windows32\User32\QueryStatus;
use Clover\Enumeration\Windows32\User32\RedrawWindow;
use Clover\Enumeration\Windows32\User32\SendMessage;
use Clover\Enumeration\Windows32\User32\SetWindowPos;
use Clover\Enumeration\Windows32\User32\ShowMessage;
use Clover\Enumeration\Windows32\User32\StaticControl;
use Clover\Enumeration\Windows32\User32\StaticMessage;
use Clover\Enumeration\Windows32\User32\SystemParametersInfo as User32SystemParametersInfo;
use Clover\Enumeration\Windows32\User32\WindowEvent;
use Clover\Enumeration\Windows32\User32\WindowEventHook;
use Clover\Enumeration\Windows32\User32\WindowHook;
use Clover\Enumeration\Windows32\User32\WindowMessage;
use Clover\Enumeration\Windows32\User32\WindowStyle;
use Clover\Enumeration\Windows32\WinUser\MessageBox;
use Clover\Interface\FFI\AtaPassThroughExInterface;
use Clover\Interface\FFI\BitmapInfoInterface;
use Clover\Interface\FFI\CDataInterface;
use Clover\Interface\FFI\DevModeInterface;
use Clover\Interface\FFI\FFIWindowsAPIInterface;
use Clover\Interface\FFI\IAudioSessionEnumeratorVtbl;
use Clover\Interface\FFI\IAudioSessionManager;
use Clover\Interface\FFI\MemoryStatusExInterface;
use Clover\Interface\FFI\NotifyIconDataAInterface;
use Clover\Interface\FFI\NotifyIconDataWInterface;
use Clover\Interface\FFI\PointInterface;
use Clover\Interface\FFI\PowerStatusInterface;
use Clover\Interface\FFI\ProcessSentry32Interface;
use Clover\Interface\FFI\RectInterface;
use Clover\Interface\FFI\StorageDeviceDescriptorInterface;
use Clover\Interface\FFI\SystemInfoInterface;
use Clover\Interface\FFI\SystemTimeInterface;
use Clover\Interface\FFI\Win32FindDataAInterface;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use FFI;
use FFI\CData;
use function chr;
use function count;
use function in_array;
use function intval;
use function is_int;
use function is_object;
use function is_string;
use function ord;
use function sprintf;
use function strlen;
use RuntimeException;

#endregion

/** @generate-class-entries */

/**
 * Windows API FFI Wrapper class
 */
class WindowsAPI extends FFIWrapper
{
    #region properties

    private array $mutedSessions = [];   // [['ctrl2'=>CData, 'vol'=>CData, 'pid'=>int], ...]
    private int $myPid;

    private const S_OK = 0;
    // CoInitialize apartment model
    private const COINIT_APARTMENTTHREADED = 0x2;
    // IMMDevice::Activate / CoCreateInstance context
    private const CLSCTX_ALL = 0x17;
    // eDataFlow::eRender, ERole::eConsole
    private const eRender = 0;
    private const eConsole = 0;

    // COM GUIDs
    // CLSID_MMDeviceEnumerator  {BCDE0395-E52F-467C-8E3D-C4579291692E}
    private const CLS_MMDE = [0xBCDE0395, 0xE52F, 0x467C, [0x8E, 0x3D, 0xC4, 0x57, 0x92, 0x91, 0x69, 0x2E]];
    // IID_IMMDeviceEnumerator   {A95664D2-9614-4F35-A746-DE8DB63617E6}
    private const IID_MMDE = [0xA95664D2, 0x9614, 0x4F35, [0xA7, 0x46, 0xDE, 0x8D, 0xB6, 0x36, 0x17, 0xE6]];
    // IID_IAudioSessionManager2 {77AA99A0-1BD6-484F-8BC7-2C654C9A9B6F}
    private const IID_ASM2 = [0x77AA99A0, 0x1BD6, 0x484F, [0x8B, 0xC7, 0x2C, 0x65, 0x4C, 0x9A, 0x9B, 0x6F]];
    // IID_IAudioSessionControl2 {BFB7FF88-7239-4FC9-8FA2-07C950BE9C6D}
    private const IID_ASC2 = [0xBFB7FF88, 0x7239, 0x4FC9, [0x8F, 0xA2, 0x07, 0xC9, 0x50, 0xBE, 0x9C, 0x6D]];
    // IID_ISimpleAudioVolume    {87CE5498-68D6-44E5-9215-6DA47EF883D8}
    private const IID_SAV = [0x87CE5498, 0x68D6, 0x44E5, [0x92, 0x15, 0x6D, 0xA4, 0x7E, 0xF8, 0x83, 0xD8]];

    /**
     * KiriKiri engine module signature list.
     *
     * krkr2 family: krglob.dll and krmovie.dll are loaded together with krkr.exe.
     * krkrZ family: characterized by modules like v2link.dll, krkrz.dll, etc.
     */
    private const KIRIKIRI_MODULE_SIGNATURES = [
        'v2link.dll',
        'krkrz.dll',
        'krmovie.dll',
        'krglob.dll',
        'krdeviceinfo.dll',
        'krkr2.dll',
    ];

    /**
     * Artemis / BurikoGP engine module signature list.
     *
     * Eushully-based games load plugin DLLs with the ag_*.dll pattern.
     * BurikoGP uses buriko.ini and buriko*.dll modules.
     */
    private const ARTEMIS_MODULE_SIGNATURES = [
        'ag_',          // prefix match: ag_picture.dll, ag_sound.dll, etc.
        'buriko',       // burikogp.dll
        'artemis',      // artemis.dll (legacy)
    ];

    /**
     * Prefix patterns considered as KiriKiri script commands for filtering.
     *
     * In KiriKiri KAG scripts, strings starting with '@', '*', '['
     * are script control statements rather than dialogue text.
     */
    private const KIRIKIRI_SCRIPT_PREFIXES = ['@', '*', '[', '#', ';', '//'];

    /** File notify information mapping */
    const ACTIONS = [
        0x01 => 'ADDED',
        0x02 => 'REMOVED',
        0x03 => 'MODIFIED',
        0x04 => 'RENAMED_OLD_NAME',
        0x05 => 'RENAMED_NEW_NAME',
    ];

    /** Allocates a buffer large enough to hold the formatted message */
    const FORMAT_MESSAGE_ALLOCATE_BUFFER = 0x00000100;
    /** Searches the system message-table resource(s) for the requested message */
    const FORMAT_MESSAGE_FROM_SYSTEM = 0x00001000;
    /** Ignores insert sequences in the message definition */
    const FORMAT_MESSAGE_IGNORE_INSERTS = 0x00000200;
    /** Language-neutral language identifier */
    const LANG_NEUTRAL = 0x00;
    /** Default sub-language identifier */
    const SUBLANG_DEFAULT = 0x01;

    /** Window class name */
    private const WND_CLASS_NAME = "PHP_FFI_WINDOW_CLASS";
    /** Enables line-by-line input mode for the console */
    private const ENABLE_LINE_INPUT = 0x0002;
    /** Echoes typed characters back to the console */
    private const ENABLE_ECHO_INPUT = 0x0004;

    /** @var array<int, Closure> Registered per-window WNDPROC callbacks */
    private array $windowProcedureHandlers = [];
    /** Enables CTRL key processing by the system for the console */
    private const ENABLE_PROCESSED_INPUT = 0x0001;
    /** Enables mouse input events to be placed in the input buffer */
    private const ENABLE_MOUSE_INPUT = 0x0010;
    /** Required with ENABLE_MOUSE_INPUT to enable mouse selection */
    private const ENABLE_EXTENDED_FLAGS = 0x0080;
    /** Enables virtual terminal input sequences for the console */
    private const ENABLE_VIRTUAL_TERMINAL_INPUT = 0x0200;
    /** Enables quick-edit mode (mouse selection) on the console */
    private const ENABLE_QUICK_EDIT_MODE = 0x0040;
    /** Standard handle value for console standard input */
    private const STD_INPUT_HANDLE = 0xFFFFFFF6;
    /** Standard handle value for console standard output */
    private const STD_OUTPUT_HANDLE = 0xFFFFFFF5;

    /** Index for the extended window style in SetWindowLong */
    const GWL_EXSTYLE = -20;
    /** Extended window style that enables layered window attributes */
    const WS_EX_LAYERED = 0x00080000;
    const COLORREF_MAGENTA = 0xFF00FF; // Magenta color (RRGGBB)
    /** Default alpha transparency value (0=transparent, 255=opaque) */
    const DEFAULT_ALPHA = 128;

    /** BitBlt raster operation: copies source rectangle directly to destination */
    const SRCCOPY = 0x00CC0020;
    /** Open file for read access */
    const GENERIC_READ = 0x80000000;
    /** Open file for write access */
    const GENERIC_WRITE = 0x40000000;
    /** Creates a new file; overwrites if it already exists */
    const CREATE_ALWAYS = 2;
    /** Opens an existing file; fails if the file does not exist */
    const OPEN_EXISTING = 3;
    /** File has no special attributes */
    const FILE_ATTRIBUTE_NORMAL = 0x00000080;
    /** Subsequent read operations on the file will succeed */
    const FILE_SHARE_READ = 0x00000001;
    /** Subsequent write operations on the file will succeed */
    const FILE_SHARE_WRITE = 0x00000002;
    /** Indicates an invalid handle value returned by CreateFile */
    const INVALID_HANDLE_VALUE = -1;

    /** Clipboard format: ANSI text */
    const CF_TEXT = 1;
    /** Clipboard format: Unicode (UTF-16LE) text */
    const CF_UNICODETEXT = 13;
    /** Allocates moveable memory for GlobalAlloc */
    const GMEM_MOVEABLE = 0x0002;
    /** Initialises allocated memory to zero */
    const GMEM_ZEROINIT = 0x0040;

    /** Logs off the interactive user */
    const EWX_LOGOFF = 0x00000000;
    /** Shuts down the system to a point where it is safe to turn off the power */
    const EWX_SHUTDOWN = 0x00000001;
    /** Shuts down and then restarts the system */
    const EWX_REBOOT = 0x00000002;
    /** Forces running applications to close without prompting the user to save */
    const EWX_FORCE = 0x00000004;

    public CData|null $windowOwner = null;
    public CData|int $language = 0x00;
    public int $properties = 0x00000000;
    /** @var FFI|FFIWindowsAPIInterface $user32 */
    public $user32;
    /** @var FFI|FFIWindowsAPIInterface $kernel32 */
    private $kernel32;
    /** @var FFI|FFIWindowsAPIInterface $tts */
    private $tts;
    /** @var FFI|FFIWindowsAPIInterface $shell32 */
    private $shell32;
    /** @var FFI|FFIWindowsAPIInterface $gdi32 */
    private $oleaut32;
    /** @var FFI|FFIWindowsAPIInterface $oleaut32 */
    private $gdi32;
    /** @var FFI|FFIWindowsAPIInterface $ntdll */
    private $ntdll;
    /** @var FFI|FFIWindowsAPIInterface $winmm */
    private $winmm;
    /** @var FFI|FFIWindowsAPIInterface $advapi32 */
    private $advapi32;
    /** @var FFI|FFIWindowsAPIInterface $wininet */
    private $wininet;
    /** @var FFI|FFIWindowsAPIInterface $dxva2 */
    private $dxva2;
    /** @var FFI|FFIWindowsAPIInterface $msvcrt */
    private $msvcrt;
    /** @var FFI|FFIWindowsAPIInterface $comdlg32 */
    private $comdlg32;
    /** @var FFI|FFIWindowsAPIInterface $gdiplus */
    private $gdiplus;
    /** @var FFI|FFIWindowsAPIInterface $ws2_32 */
    private $ws2_32;
    /** @var FFI|FFIWindowsAPIInterface $iphlpapi */
    private $iphlpapi;
    /**
     * Bound on first use rather than in the constructor, which already opens
     * twenty-three libraries; powrprof carries one method nobody calls twice.
     *
     * @var FFI|FFIWindowsAPIInterface|null $powrprof
     */
    private $powrprof = null;
    /** @var FFI|FFIWindowsAPIInterface $setupapi */
    private $setupapi;
    /** @var FFI|FFIWindowsAPIInterface $d3d9 */
    private $d3d9;
    /** @var FFI|FFIWindowsAPIInterface $combase */
    private $combase;
    /** @var FFI|FFIWindowsAPIInterface $ole32 */
    private $ole32;
    /** @var FFI|FFIWindowsAPIInterface $rasapi32 */
    private $rasapi32;
    /** @var FFI|FFIWindowsAPIInterface $apimswin */
    private $apimswin;
    /** @var FFI|FFIWindowsAPIInterface $cfgmgr32 */
    private $cfgmgr32;
    /** @var FFI|FFIWindowsAPIInterface $avicap32 */
    private $avicap32;
    /** @var FFI|FFIWindowsAPIInterface $shellapi */
    private $shellapi;

    #endregion

    #region function

    /**
     * WindowsAPI constructor.
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
        if (!extension_loaded(extension: 'ffi')) {
            throw new RuntimeException('FFI is not loaded');
        }

        $windowsHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/windows.h"));
        if (!$windowsHeader) {
            throw new RuntimeException('Windows header file is not exists');
        }
        $typedef = $windowsHeader;

        $user32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/user32.h"));
        if (!$user32Header) {
            throw new RuntimeException('User32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $user32Header);
        $this->user32 = FFI::cdef($cdef, "User32.dll");

        $kernel32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/kernel32.h"));
        if (!$kernel32Header) {
            throw new RuntimeException('Kernel32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $kernel32Header);
        $this->kernel32 = FFI::cdef($cdef, "kernel32.dll");

        $winmmHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/winmm.h"));
        if (!$winmmHeader) {
            throw new RuntimeException('Winmm header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $winmmHeader);
        $this->winmm = FFI::cdef($cdef, "winmm.dll");

        $ntdllHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/ntdll.h"));
        if (!$ntdllHeader) {
            throw new RuntimeException('Ntdll header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $ntdllHeader);
        $this->ntdll = FFI::cdef($cdef, "ntdll.dll");

        $d3d9Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/d3d9.h"));
        if (!$d3d9Header) {
            throw new RuntimeException('D3d9 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $d3d9Header);
        $this->d3d9 = FFI::cdef($cdef, "d3d9.dll");

        $shell32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/shell32.h"));
        if (!$shell32Header) {
            throw new RuntimeException('Shell32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $shell32Header);
        $this->shell32 = FFI::cdef($cdef, "shell32.dll");

        $combaseHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/combase.h"));
        if (!$combaseHeader) {
            throw new RuntimeException('Combase header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $combaseHeader);
        $this->combase = FFI::cdef($cdef, "combase.dll");

        $old32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/ole32.h"));
        if (!$old32Header) {
            throw new RuntimeException('Ole32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $old32Header);
        $this->ole32 = FFI::cdef($cdef, "ole32.dll");

        $gdi32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/gdi32.h"));
        if (!$gdi32Header) {
            throw new RuntimeException('Gdi32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $gdi32Header);
        $this->gdi32 = FFI::cdef($cdef, "gdi32.dll");

        $advapi32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/advapi32.h"));
        if (!$advapi32Header) {
            throw new RuntimeException('Advapi32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $advapi32Header);
        $this->advapi32 = FFI::cdef($cdef, "Advapi32.dll");

        $rasapi32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/rasapi32.h"));
        if (!$rasapi32Header) {
            throw new RuntimeException('Rasapi32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $rasapi32Header);
        $this->rasapi32 = FFI::cdef($cdef, "rasapi32.dll");

        $wininetHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/wininet.h"));
        if (!$wininetHeader) {
            throw new RuntimeException('Wininet header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $wininetHeader);
        $this->wininet = FFI::cdef($cdef, "wininet.dll");

        $dxva2Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/dxva2.h"));
        if (!$dxva2Header) {
            throw new RuntimeException('Dxva2 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $dxva2Header);
        $this->dxva2 = FFI::cdef($cdef, "dxva2.dll");

        $setupapiHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/setupapi.h"));
        if (!$setupapiHeader) {
            throw new RuntimeException('Setupapi header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $setupapiHeader);
        $this->setupapi = FFI::cdef($cdef, lib: "setupapi.dll");

        $comdlg32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/comdlg32.h"));
        if (!$comdlg32Header) {
            throw new RuntimeException('Comdlg32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $comdlg32Header);
        $this->comdlg32 = FFI::cdef($cdef, lib: "comdlg32.dll");

        $gdiplusHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/gdiplus.h"));
        if (!$gdiplusHeader) {
            throw new RuntimeException('Gdiplus header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $gdiplusHeader);
        $this->gdiplus = FFI::cdef($cdef, lib: "gdiplus.dll");

        $msvcrtHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/msvcrt.h"));
        if (!$msvcrtHeader) {
            throw new RuntimeException('Msvcrt header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $msvcrtHeader);
        $this->msvcrt = FFI::cdef($cdef, lib: "msvcrt.dll");

        $apimswinHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/api-ms-win-core-winrt-l1-1-0.h"));
        if (!$apimswinHeader) {
            throw new RuntimeException('Api-ms-win-core-winrt-l1-1-0 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $apimswinHeader);
        $this->apimswin = FFI::cdef($cdef, lib: "api-ms-win-core-winrt-l1-1-0.dll");

        $cfgmgr32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/cfgmgr32.h"));
        if (!$cfgmgr32Header) {
            throw new RuntimeException('Cfgmgr32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $cfgmgr32Header);
        $this->cfgmgr32 = FFI::cdef($cdef, lib: "cfgmgr32.dll");

        $ws2_32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/ws2_32.h"));
        if (!$ws2_32Header) {
            throw new RuntimeException('ws2_32 header file is not exists');
        }
        $this->ws2_32 = FFI::cdef($ws2_32Header->__toString(), lib: "Ws2_32.dll");

        $iphlpapiHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/iphlpapi.h"));
        if (!$iphlpapiHeader) {
            throw new RuntimeException('Iphlpapi header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $iphlpapiHeader);
        $this->iphlpapi = FFI::cdef($cdef, lib: "iphlpapi.dll");

        $oleaut32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/oleaut32.h"));
        if (!$oleaut32Header) {
            throw new RuntimeException('ws2_32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $oleaut32Header);
        $this->oleaut32 = FFI::cdef($cdef, lib: "oleaut32.dll");

        $avicap32Header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/avicap32.h"));
        if (!$avicap32Header) {
            throw new RuntimeException('avicap32 header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $avicap32Header);
        $this->avicap32 = FFI::cdef($cdef, lib: "avicap32.dll");

        $shellapiHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/shellapi.h"));
        if (!$shellapiHeader) {
            throw new RuntimeException('shellapi header file is not exists');
        }
        $cdef = sprintf("%s%s", $typedef, $shellapiHeader);
        $this->shellapi = FFI::cdef($cdef, lib: "shell32.dll");
    }

    /**
     * Retrieves the last Win32 error code and formats it into a human-readable string.
     *
     * @param bool $unicode When true, uses the wide-character (UTF-16LE) FormatMessageW;
     *                      otherwise uses the ANSI FormatMessageA.
     * @return string Formatted error description followed by the numeric error code in parentheses.
     */
    private function getLastErrorMessage(bool $unicode = true)
    {
        if ($unicode) {
            $errorCode = $this->kernel32->GetLastError();
            $msgBuffer = $this->kernel32->new('unsigned short[512]');
            $pointer = 0;
            $len = $this->kernel32->FormatMessageW(self::FORMAT_MESSAGE_FROM_SYSTEM | self::FORMAT_MESSAGE_IGNORE_INSERTS, $this->kernel32->cast('void*', $pointer), $errorCode, 0, $msgBuffer, 512, $this->kernel32->cast('void*', $pointer));

            $raw = '';
            for ($i = 0; $i < $len; $i++) {
                $raw .= pack('v', $msgBuffer[$i]);
            }

            return sprintf("%s (%lu)", trim(mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE')), $errorCode);
        }

        $errorCode = $this->kernel32->GetLastError();
        $msgBuffer = $this->kernel32->new('char[256]');
        $pointer = 0;
        $this->kernel32->FormatMessageA(self::FORMAT_MESSAGE_FROM_SYSTEM | self::FORMAT_MESSAGE_IGNORE_INSERTS, $this->kernel32->cast('void*', $pointer), $errorCode, 0, $this->kernel32->cast('char*', $msgBuffer), 256, $this->kernel32->cast('void*', $pointer));

        $pointer = $this->kernel32->cast('char*', $msgBuffer);
        return sprintf("%s(%lu)", FFI::string($pointer), $errorCode);
    }

    /**
     * Create a server socket that listens for incoming connections
     *
     * @param int $port The port to listen on (default is 9090).
     * @return void
     */
    public function createServerSocket(int $port = 9090): void
    {
        $wsa = $this->initializeWinSocket();
        $server = $this->createWinSocket();
        $sockaddr = $this->bindWinSocket($port);

        if ($this->ws2_32->bind($server, FFI::addr($sockaddr), FFI::sizeof($sockaddr)) != 0) {
            throw new RuntimeException("Bind failed");
        }

        $this->ws2_32->listen($server, 5);

        $client = $this->ws2_32->accept($server, null, null);
        if ($client != ~0) {
            $msg = "Hello from PHP+Winsock!\n";
            $this->ws2_32->send($client, $msg, strlen($msg), 0);
            $this->ws2_32->closesocket($client);
        }

        $this->ws2_32->closesocket($server);
        $this->ws2_32->WSACleanup();
    }

    /**
     * Initialize Winsock
     *
     * @return FFI\CData The WSADATA structure.
     * @throws Exception If WSAStartup fails.
     */
    public function initializeWinSocket()
    {
        $wsa = $this->ws2_32->new("WSADATA");
        if ($this->ws2_32->WSAStartup(0x202, FFI::addr($wsa)) !== 0) {
            throw new Exception("WSAStartup failed");
        }

        return $wsa;
    }

    /**
     * Create a Winsock socket
     *
     * @return FFI\CData The created socket.
     * @throws Exception If socket creation fails.
     */
    public function createWinSocket()
    {
        $socket = $this->ws2_32->socket(2, 1, 0);
        if ($socket == ~0) {
            throw new Exception("Socket creation failed");
        }

        return $socket;
    }

    /**
     * Bind Winsock to a port
     *
     * @param int $port The port to bind to.
     * @param string $address The IP address to bind to (default is "127.0.0.1").
     * @return FFI\CData The SOCKADDR_IN structure.
     */
    public function bindWinSocket(int $port, string $address = "127.0.0.1")
    {
        $sockaddr = $this->ws2_32->new("SOCKADDR_IN");
        $sockaddr->sin_family = 2;
        $sockaddr->sin_port = $this->ws2_32->htons($port);
        $sockaddr->sin_addr->s_addr = $this->ws2_32->inet_addr($address);

        return $sockaddr;
    }

    /**
     * Create a client socket and connect to the server
     *
     * @param int $port The port to connect to (default is 9090).
     * @return void
     */
    public function createClientSocket(int $port = 9090)
    {
        $wsa = $this->initializeWinSocket();
        $client = $this->createWinSocket();
        $sockaddr = $this->bindWinSocket($port);

        if ($this->ws2_32->connect($client, FFI::addr($sockaddr), FFI::sizeof($sockaddr)) != 0) {
            throw new RuntimeException("Connect failed");
        }

        $msg = "Hello from PHP Winsock Client!";
        $this->ws2_32->send($client, $msg, strlen($msg), 0);

        $buf = $this->ws2_32->new("char[512]");
        $bytes = $this->ws2_32->recv($client, $buf, 512, 0);
        if ($bytes > 0) {
            $receivedPacket = FFI::string($buf, $bytes);
            echo "Server says: " . $receivedPacket . "\n";
        }

        $this->ws2_32->closesocket($client);
        $this->ws2_32->WSACleanup();
    }

    /**
     * Get disk free spaces
     *
     * @param string $path The path to check (default is "C:\").
     * @return array{freeBytesAvailable: mixed, totalBytes: mixed, totalFreeBytes: mixed} An associative array with 'freeBytesAvailable', 'totalBytes', and 'totalFreeBytes'.
     * @throws Exception If there is an error retrieving the disk space information.
     */
    public function getDiskFreeSpaces(string $path = "C:\\"): array
    {
        /** @var CDataInterface $freeBytesAvailable */
        $freeBytesAvailable = $this->user32->new("ULARGE_INTEGER");
        /** @var CDataInterface $totalBytes */
        $totalBytes = $this->user32->new("ULARGE_INTEGER");
        /** @var CDataInterface $totalFreeBytes */
        $totalFreeBytes = $this->user32->new("ULARGE_INTEGER");

        $result = $this->kernel32->GetDiskFreeSpaceExA($path, FFI::addr($freeBytesAvailable), FFI::addr($totalBytes), FFI::addr($totalFreeBytes));

        if (!$result) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("Error getting disk space: {$errorCode}");
        }

        return [
            'freeBytesAvailable' => $freeBytesAvailable->cdata,
            'totalBytes' => $totalBytes->cdata,
            'totalFreeBytes' => $totalFreeBytes->cdata
        ];
    }

    /**
     * Download web contents from a URL
     *
     * @param string $url The URL to download from.
     * @param string $agent The user agent string to use.
     * @return string The downloaded content.
     * @throws Exception If there is an error during the download.
     */
    public function downloadWebContents(string $url, string $agent = 'PHP')
    {
        $internet = $this->wininet->InternetOpenA($agent, 1, null, null, 0);
        if (!$internet) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("Error initializing internet connection: {$errorCode}");
        }

        $totalContent = "";
        $urlHandle = $this->wininet->InternetOpenUrlA($internet, $url, null, 0, 0, 0);
        if (!$urlHandle) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("Error opening URL: {$errorCode}");
        }

        $buffer = $this->user32->new("char[4096]");
        /** @var CDataInterface $bytesRead */
        $bytesRead = $this->user32->new("DWORD");

        while ($this->wininet->InternetReadFile($urlHandle, $buffer, 4096, FFI::addr($bytesRead))) {
            if ($bytesRead->cdata == 0) {
                break;
            }

            $totalContent .= FFI::string($buffer, $bytesRead->cdata);
        }

        $this->wininet->InternetCloseHandle($urlHandle);
        $this->wininet->InternetCloseHandle($internet);

        return $totalContent;
    }

    /**
     * Get the current console cursor position
     *
     * @return bool True on success, false on failure.
     */
    public function setConsoleCursorPosition(): bool
    {
        $stdOut = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        $coord = $this->user32->new("COORD");
        $coord->X = 10;
        $coord->Y = 5;

        $result = $this->kernel32->SetConsoleCursorPosition($stdOut, $coord);

        return $result;
    }

    /**
     * Click left mouse button
     *
     * @param int $dx The change in x-coordinate.
     * @param int $dy The change in y-coordinate.
     * @return void
     */
    public function clickLeftMouse(int $dx = 0, int $dy = 0): void
    {
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::LEFTDOWN->value, $dx, $dy, 0, 0);
    }

    /**
     * Click and release left mouse button
     *
     * @param int $dx The change in x-coordinate.
     * @param int $dy The change in y-coordinate.
     * @return void
     */
    public function clickAndReleaseLeftMouse(int $dx = 0, int $dy = 0): void
    {
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::LEFTDOWN->value, $dx, $dy, 0, 0);
        usleep(50000);
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::LEFTUP->value, $dx, $dy, 0, 0);
    }

    /**
     * Click and release right mouse button
     * 
     * @param int $dx
     * @param int $dy
     * @return void
     */
    public function clickAndReleaseRightMouse(int $dx = 0, int $dy = 0): void
    {
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::RIGHTDOWN->value, $dx, $dy, 0, 0);
        usleep(50000);
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::RIGHTUP->value, $dx, $dy, 0, 0);
    }

    /**
     * Click and release middle mouse button
     * 
     * @param int $dx
     * @param int $dy
     * @return void
     */
    public function clickAndReleaseMiddleMouse(int $dx = 0, int $dy = 0): void
    {
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::MIDDLEDOWN->value, $dx, $dy, 0, 0);
        usleep(50000);
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::MIDDLEUP->value, $dx, $dy, 0, 0);
    }

    /**
     * Scroll the mouse wheel
     * 
     * @param int $delta
     * @return void
     */
    public function scrollMouse(int $delta = 120): void
    {
        $this->user32->mouse_event(\Clover\Enumeration\Windows32\User32\MouseEvent::WHEEL->value, 0, 0, $delta, 0);
    }

    /**
     * Press and release a keyboard key
     *
     * @param int $keyCode The virtual key code of the key to press and release.
     * @return void
     */
    public function pressAndReleaseKeyboard(int $keyCode): void
    {
        $this->user32->keybd_event($keyCode, 0, 0, 0);
        usleep(50000);
        $this->user32->keybd_event($keyCode, 0, KeyboardEvent::KEYUP->value, 0);
    }

    /**
     * Press a combination of keys and release them in reverse order
     * 
     * @param array $keyCodes
     * @return void
     */
    public function pressKeyCombination(array $keyCodes): void
    {
        foreach ($keyCodes as $keyCode) {
            $this->user32->keybd_event($keyCode, 0, 0, 0);
        }

        usleep(50000);
        foreach (array_reverse($keyCodes) as $keyCode) {
            $this->user32->keybd_event($keyCode, 0, KeyboardEvent::KEYUP->value, 0);
        }
    }

    /**
     * Type a string using the keyboard events
     * 
     * @param string $text
     * @return void
     */
    public function typeString(string $text): void
    {
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $code = mb_ord($char, 'UTF-8');
            $vk = $this->user32->VkKeyScanA($code & 0xFF);
            if ($vk !== -1) {
                $virtualKey = $vk & 0xFF;
                $shift = ($vk >> 8) & 0x01;
                if ($shift) {
                    $this->user32->keybd_event(0x10, 0, 0, 0);
                }
                $this->pressAndReleaseKeyboard($virtualKey);
                if ($shift) {
                    $this->user32->keybd_event(0x10, 0, KeyboardEvent::KEYUP->value, 0);
                }
            }
            usleep(20000);
        }
    }

    /**
     * Set the window title for a given window handle.
     *
     * @param CData $hwnd The handle to the window.
     * @param string $newTitle The new title for the window.
     * @return bool True on success, false on failure.
     * @throws Exception If there is an error setting the window title.
     */
    public function setWindowTitleByPid(CData $hwnd, $newTitle)
    {
        $wcharTitle = self::stringToWchar($this->user32, $newTitle);
        $result = $this->user32->SetWindowTextW($hwnd, $wcharTitle);

        if ($result === 0) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("SetWindowTextW failed with error code: {$errorCode}");
        }

        return true;
    }

    /**
     * Get the device context for a given window handle.
     *
     * @param CData $hwnd The handle to the window.
     * @return mixed The device context handle.
     */
    public function getWindowsDeviceContext($hwnd): mixed
    {
        return $this->user32->GetWindowDC($hwnd);
    }

    /**
     * Get the handle to the desktop window.
     *
     * @return CData The handle to the desktop window.
     */
    public function getDesktopWindow()
    {
        return $this->user32->GetDesktopWindow();
    }

    /**
     * Capture the screen and return bitmap data.
     *
     * @return array{width: int, height: int, data: CData} An array containing 'data', 'width', and 'height' of the captured screen.
     * @throws Exception If there is an error capturing the screen.
     */
    public function screenCapture()
    {
        $hwnd = $this->getDesktopWindow();
        $hdc = $this->getWindowsDeviceContext($hwnd);

        $width = $this->getScreenWidth();
        $height = $this->getScreenHeight();

        $hCaptureDC = $this->gdi32->CreateCompatibleDC($hdc);
        $hBitmap = $this->gdi32->CreateCompatibleBitmap($hdc, $width, $height);
        $hOld = $this->gdi32->SelectObject($hCaptureDC, $hBitmap);

        $this->gdi32->BitBlt($hCaptureDC, 0, 0, $width, $height, $hdc, 0, 0, BitBit::SRCCOPY->value);

        /** @var BitmapInfoInterface $bitmapInfo */
        $bitmapInfo = $this->user32->new("BITMAPINFO");
        $bitmapInfo->bmiHeader->biSize = FFI::sizeof($bitmapInfo->bmiHeader);
        $bitmapInfo->bmiHeader->biWidth = $width;
        $bitmapInfo->bmiHeader->biHeight = -$height;
        $bitmapInfo->bmiHeader->biPlanes = 1;
        $bitmapInfo->bmiHeader->biBitCount = 24;
        $bitmapInfo->bmiHeader->biCompression = 0;

        $dataSize = $width * $height * 3;
        $data = $this->user32->new("char[$dataSize]");

        $result = $this->gdi32->GetDIBits($hCaptureDC, $hBitmap, 0, $height, FFI::addr($data), FFI::addr($bitmapInfo), 0);

        if ($result == 0) {
            $error = $this->getLastErrorMessage();
            throw new Exception("Error getting bitmap data: {$error}");
        }

        $this->gdi32->SelectObject($hCaptureDC, $hOld);
        $this->gdi32->DeleteDC($hCaptureDC);
        $this->gdi32->DeleteObject($hBitmap);
        $this->user32->ReleaseDC($hwnd, $hdc);

        return ['data' => $data, 'width' => $width, 'height' => $height];
    }

    /**
     * Capture a specific region of the screen and return bitmap data.
     * 
     * @param CData $hwnd
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return array{width: int, height: int, data: CData}
     * @throws Exception
     */
    public function screenCaptureRegion(CData $hwnd, int $x, int $y, int $width, int $height): array
    {
        $hdc = $this->getWindowsDeviceContext($hwnd);
        $hCaptureDC = $this->gdi32->CreateCompatibleDC($hdc);
        $hBitmap = $this->gdi32->CreateCompatibleBitmap($hdc, $width, $height);
        $hOld = $this->gdi32->SelectObject($hCaptureDC, $hBitmap);

        $this->gdi32->BitBlt($hCaptureDC, 0, 0, $width, $height, $hdc, $x, $y, BitBit::SRCCOPY->value);

        /** @var BitmapInfoInterface $bitmapInfo */
        $bitmapInfo = $this->user32->new("BITMAPINFO");
        $bitmapInfo->bmiHeader->biSize = FFI::sizeof($bitmapInfo->bmiHeader);
        $bitmapInfo->bmiHeader->biWidth = $width;
        $bitmapInfo->bmiHeader->biHeight = -$height;
        $bitmapInfo->bmiHeader->biPlanes = 1;
        $bitmapInfo->bmiHeader->biBitCount = 24;
        $bitmapInfo->bmiHeader->biCompression = 0;

        $dataSize = $width * $height * 3;
        $data = $this->user32->new("char[$dataSize]");

        $result = $this->gdi32->GetDIBits($hCaptureDC, $hBitmap, 0, $height, FFI::addr($data), FFI::addr($bitmapInfo), 0);

        if ($result == 0) {
            throw new Exception("Error getting bitmap data for region");
        }

        $this->gdi32->SelectObject($hCaptureDC, $hOld);
        $this->gdi32->DeleteDC($hCaptureDC);
        $this->gdi32->DeleteObject($hBitmap);
        $this->user32->ReleaseDC($hwnd, $hdc);

        return ['data' => $data, 'width' => $width, 'height' => $height];
    }

    /**
     * Capture the screen and save it to a file in BMP format.
     *
     * @param string $filePath The path to save the captured screen image.
     * @return bool True on success, false on failure.
     * @throws Exception If there is an error during screen capture or file saving.
     */
    public function saveCaptureScreen(string $filePath)
    {
        $capture = $this->screenCapture();
        return $this->saveBitmapToFile($capture, $filePath);
    }

    /**
     * Save bitmap data to a file in BMP format.
     * 
     * @param array{width: int, height: int, data: CData} $captureData
     * @param string $filePath
     * @return bool
     */
    public function saveBitmapToFile(array $captureData, string $filePath): bool
    {
        $width = $captureData['width'];
        $height = $captureData['height'];
        $data = $captureData['data'];

        $rowSize = (($width * 3 + 3) & ~3);
        $imageSize = $rowSize * $height;
        $headerSize = 14 + 40;
        $fileSize = $headerSize + $imageSize;

        $bmpHeader = pack('c2Vv2V', ord('B'), ord('M'), $fileSize, 0, 0, $headerSize);
        $dibHeader = pack('V3v2V6', 40, $width, $height, 1, 24, 0, $imageSize, 0, 0, 0, 0);

        $rawData = FFI::string($data, $width * $height * 3);

        $paddedData = '';
        $srcRowSize = $width * 3;
        $padding = str_repeat("\0", $rowSize - $srcRowSize);
        for ($row = $height - 1; $row >= 0; $row--) {
            $paddedData .= substr($rawData, $row * $srcRowSize, $srcRowSize) . $padding;
        }

        echo $filePath;
        return file_put_contents($filePath, $bmpHeader . $dibHeader . $paddedData) !== false;
    }

    /**
     * Execute a file using ShellExecuteW.
     *
     * @param string $file The file to execute.
     * @param string|null $operation The operation to perform (default is "open").
     * @param string|null $parameters The parameters to pass to the file (default is empty).
     * @param string|null $directory The working directory (default is null).
     * @param int $showCmd The show command (default is 1 - SW_SHOWNORMAL).
     * @return CData The result of the ShellExecuteW call.
     */
    public function shellExecute(string $file, ?string $operation = "open", ?string $parameters = "", ?string $directory = null, int $showCmd = 1)
    {
        $hwnd = null;
        $lpOperation = self::stringToWchar($this->user32, $operation);
        $lpFile = self::stringToWchar($this->user32, $file);
        $lpParameters = self::stringToWchar($this->user32, $parameters);

        $lpDirectory = null;
        if ($directory !== null && $directory !== "") {
            $lpDirectory = self::stringToWchar($this->user32, $directory);
        }

        return $this->shell32->ShellExecuteW($hwnd, $lpOperation, $lpFile, $lpParameters, $lpDirectory, $showCmd);
    }

    /**
     * Get error description from error code.
     *
     * @param int $errorCode The error code.
     * @return string The error description.
     */
    public function getErrorDescription(int $errorCode): string
    {
        $descriptions = [
            0 => "The operating system is out of memory or resources.",
            2 => "The specified file was not found.",
            3 => "The specified path was not found.",
            5 => "The operating system denied access to the specified file.",
            8 => "There was not enough memory to complete the operation.",
            11 => "The specified .dll was invalid.",
            26 => "A sharing violation occurred.",
            27 => "The file association is incomplete or invalid.",
            28 => "The DDE transaction failed.",
            29 => "The DDE transaction timed out.",
            30 => "The DDE server is busy.",
            31 => "There was an error when attempting to start the DDE server application.",
            42 => "The specified file name extension is invalid.",
            115 => "The specified process already exists.",
            127 => "The specified function could not be found.",
            128 => "The specified file is an executable file and is not valid for this operation.",
            193 => "The specified program is not a Windows or MS-DOS program.",
            194 => "The specified program requires a new version of Windows.",
            195 => "The specified program is designed for a different version of Windows.",
            196 => "The specified program cannot be run in DOS mode.",
            197 => "There was a problem running the specified program.",
            198 => "The specified program requires Windows to be running in a protected mode.",
            199 => "The specified program is incompatible with the current version of Windows.",
            200 => "The specified program is incompatible with the current hardware.",
            201 => "The specified program requires a graphics adapter with a higher resolution.",
            202 => "The specified program requires a graphics adapter with more colors.",
            203 => "The specified program requires a mouse.",
            204 => "The specified program requires a keyboard.",
            205 => "The specified program requires a display adapter.",
            206 => "The specified program requires a higher version of the operating system.",
            207 => "The specified program is incompatible with the network.",
            208 => "The specified program requires more memory.",
            209 => "The specified program requires more disk space.",
            210 => "The specified program requires a higher processor.",
            211 => "The specified program requires a different operating system.",
        ];

        return $descriptions[$errorCode] ?? "Unknown error code: " . $errorCode;
    }

    /**
     * Show the Start Menu.
     *
     * @return bool True on success, false on failure.
     */
    public function showStartMenu()
    {
        $desktopWindow = $this->user32->GetDesktopWindow();

        return $this->user32->ShowWindow($desktopWindow, 1);
    }

    /**
     * Play a sound file.
     *
     * @param string $filename The path to the sound file.
     * @param int $flags The flags for playing the sound (default is SND_FILENAME).
     * @return void
     */
    public function playSoundFile(string $filename, int $flags = (0x00020000)): void
    {
        $this->winmm->PlaySoundA($filename, null, $flags);
    }

    /**
     * Stop any currently playing sound.
     *
     * @return bool True on success, false on failure.
     */
    public function stopSound(): bool
    {
        return $this->winmm->PlaySoundA(null, null, PlaySound::PURGE->value);
    }

    /**
     * Get the screen width.
     *
     * @return int The width of the screen in pixels.
     */
    public function getScreenWidth()
    {
        return $this->user32->GetSystemMetrics(GetSystemMetrics::CXSCREEN->value);
    }

    /**
     * Generate a beep sound.
     *
     * @param int $frequency The frequency of the beep in hertz.
     * @param int $duration The duration of the beep in milliseconds.
     * @return bool True on success, false on failure.
     */
    public function beep(int $frequency, int $duration)
    {
        return $this->kernel32->Beep($frequency, $duration);
    }

    /**
     * Play a simple beep sound using the system default sound.
     * 
     * @param int $type The type of beep to play (default is MB_ICONASTERISK).
     * @return bool True on success, false on failure.
     */
    public function messageBeep(int $type = 0x00000040): bool
    {
        return $this->user32->MessageBeep($type);
    }

    /**
     * Get the user default language ID.
     *
     * @return int The user default language ID.
     */
    public function getUserDefaultLanguageID()
    {
        return $this->kernel32->GetUserDefaultLangID();
    }

    /**
     * Get the list of logical drives.
     *
     * @return string[] An array of logical drive strings (e.g., "C:\", "D:\").
     */
    public function getLogicalDrives()
    {
        $drivesMask = $this->kernel32->GetLogicalDrives();
        $drives = [];
        for ($i = 0; $i < 26; $i++) {
            if (($drivesMask >> $i) & 1) {
                $drives[] = chr(ord('A') + $i) . ":\\";
            }
        }

        return $drives;
    }

    /**
     * Get the screen height.
     *
     * @return int The height of the screen in pixels.
     */
    public function getScreenHeight()
    {
        return $this->user32->GetSystemMetrics(GetSystemMetrics::CYSCREEN->value);
    }

    /**
     * Move the mouse to the specified coordinates.
     *
     * @param int $x The x-coordinate to move the mouse to.
     * @param int $y The y-coordinate to move the mouse to.
     * @return void
     */
    public function moveMouse(int $x = 0, int $y = 0): void
    {
        $screenDimensions = $this->getScreenDimensions();
        $screenWidth = $screenDimensions['width'];
        $screenHeight = $screenDimensions['height'];

        $scaledX = (int) round(($x / $screenWidth) * 65535.0);
        $scaledY = (int) round(($y / $screenHeight) * 65535.0);

        $this->user32->mouse_event(MouseEvent::ABSOLUTE->value | MouseEvent::MOVE->value, $scaledX, $scaledY, 0, 0);
    }

    /**
     * Get the screen dimensions.
     *
     * @return array<int> An associative array with 'width' and 'height' keys.
     */
    public function getScreenDimensions()
    {
        $screenWidth = $this->user32->GetSystemMetrics(SystemMetric::SM_CXSCREEN->value);
        $screenHeight = $this->user32->GetSystemMetrics(SystemMetric::SM_CYSCREEN->value);
        return ['width' => $screenWidth, 'height' => $screenHeight];
    }

    /**
     * Returns the bounding rectangle of the entire virtual screen (all monitors combined).
     *
     * @return array{x: int, y: int, width: int, height: int}
     *   'x'      – left edge of the virtual screen (may be negative on multi-monitor setups).
     *   'y'      – top edge of the virtual screen (may be negative on multi-monitor setups).
     *   'width'  – total width of the virtual screen in pixels.
     *   'height' – total height of the virtual screen in pixels.
     */
    public function getVirtualScreenDimensions(): array
    {
        $x = $this->user32->GetSystemMetrics(SystemMetric::SM_XVIRTUALSCREEN->value);
        $y = $this->user32->GetSystemMetrics(SystemMetric::SM_YVIRTUALSCREEN->value);
        $width = $this->user32->GetSystemMetrics(SystemMetric::SM_CXVIRTUALSCREEN->value);
        $height = $this->user32->GetSystemMetrics(SystemMetric::SM_CYVIRTUALSCREEN->value);
        return ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];
    }

    /**
     * Get the number of monitors.
     * 
     * @return int
     */
    public function getMonitorCount(): int
    {
        return $this->user32->GetSystemMetrics(SystemMetric::SM_CMONITORS->value);
    }

    /**
     * Open a process by its ID.
     *
     * @param int $processId The ID of the process to open.
     * @return void
     */
    public function openProcess(int $processId): void
    {
        $desiredAccess = OpenProcess::VM_READ->value | OpenProcess::VM_WRITE->value | OpenProcess::VM_OPERATION->value | OpenProcess::QUERY_INFORMATION->value;

        $this->kernel32->OpenProcess($desiredAccess, false, $processId);
    }

    /**
     * Close a process by its ID.
     *
     * @param int $processId The ID of the process to close.
     * @return void
     */
    public function closeProcess(int $processId): void
    {
        $this->kernel32->CloseHandle($processId);
    }

    /**
     * Terminate a process by its ID.
     *
     * @param CData $processId The ID of the process to terminate.
     * @param int $inheritHandle Whether to inherit the handle (0 = false, 1 = true).
     * @return bool True on success, false on failure.
     */
    public function terminateProcess(CData $processId, int $inheritHandle = 0)
    {
        $processHandle = $this->kernel32->OpenProcess(0x0001, $this->user32->cast("bool*", $inheritHandle), $processId);

        if ($processHandle === null) {
            return false;
        }

        $result = $this->kernel32->TerminateProcess($processHandle, 0); // 0 is the exit code

        $this->kernel32->CloseHandle($processHandle);

        return (bool) $result;
    }

    /**
     * Shutdown or restart the system.
     *
     * @param int $flag The shutdown flag (e.g., EWX_SHUTDOWN, EWX_REBOOT).
     * @param int $reason The reason code for the shutdown.
     * @return void
     */
    public function shutdown(int $flag = 0, int $reason = 0): void
    {
        $this->user32->ExitWindowsEx($flag, $reason);
    }

    /**
     * Lock the workstation.
     * 
     * @return bool
     */
    public function lockWorkstation(): bool
    {
        return (bool) $this->user32->LockWorkStation();
    }

    /**
     * Get the title of a window by its handle.
     *
     * @param CData $handle The handle of the window.
     * @return string|null The title of the window, or null if not found.
     */
    public function getWindowText(CData $handle): string|null
    {
        // "wchar" is not a C type and FFI rejects it, so this allocation threw
        // every time it was reached. WCHAR is what the rest of the file uses.
        $buffer = $this->user32->new("WCHAR[256]");
        $length = $this->user32->GetWindowTextW($handle, $buffer, 256);

        $title = null;
        if ($length > 0) {
            // GetWindowTextW writes UTF-16LE. FFI::string would hand those
            // bytes back unconverted, so the title has to be decoded.
            $title = parent::wideToPhp($buffer, $length);
        }

        return $title;
    }

    /**
     * Bring a window to the top by its process ID.
     *
     * @param CData $processId The process ID of the window to bring to top.
     * @return void
     */
    public function bringToTop(CData $processId): void
    {
        $this->user32->BringWindowToTop($processId);
    }

    /**
     * Set a window to the foreground by its process ID.
     *
     * @param string $processId The process ID of the window to set to foreground.
     * @return void
     */
    public function setForeground(string $processId): void
    {
        $this->user32->SetForegroundWindow($processId);
    }

    /**
     * Hide a window by its process ID.
     *
     * @param CData|string $processId The process ID of the window to hide.
     * @return void
     */
    public function showWindow(CData|string $processId)
    {
        $this->user32->ShowWindow($processId, ShowMessage::SW_SHOWNORMAL);
    }

    /**
     * Restore a window by its handle.
     * 
     * @param CData $hwnd
     * @return void
     */
    public function minimizeWindow(CData $hwnd): void
    {
        $this->user32->ShowWindow($hwnd, ShowMessage::SW_SHOWMINIMIZED);
    }

    /**
     * Maximize a window by its handle.
     * 
     * @param CData $hwnd
     * @return void
     */
    public function maximizeWindow(CData $hwnd): void
    {
        $this->user32->ShowWindow($hwnd, ShowMessage::SW_SHOWMAXIMIZED);
    }

    /**
     * Restore a window by its handle.
     * 
     * @param CData $hwnd
     * @return void
     */
    public function restoreWindow(CData $hwnd): void
    {
        $this->user32->ShowWindow($hwnd, ShowMessage::SW_RESTORE);
    }

    /**
     * Toggle the visibility of a window by its handle.
     * 
     * @param CData $hwnd
     * @return void
     */
    public function hideWindow(CData $hwnd): void
    {
        $this->user32->ShowWindow($hwnd, ShowMessage::SW_HIDE);
    }

    /**
     * Set the visibility of the taskbar.
     *
     * @param int $flag The visibility flag (0 = hide, 1 = show).
     * @return void
     */
    public function setTaskbar(int $flag = 0)
    {
        $this->setProcessVisibility("Shell_TrayWnd", $flag);
    }

    /**
     * Set the visibility of a process window.
     *
     * @param CData|string $hwnd The handle of the window or its title.
     * @param int $flag The visibility flag (0 = hide, 1 = show).
     * @return bool The result of the ShowWindow function.
     */
    public function setProcessVisibility(CData|string $hwnd, int $flag = 0)
    {
        $result = $this->user32->ShowWindow($hwnd, $flag);

        return $result;
    }

    /**
     * Find a window by its title using the ANSI version of the API.
     *
     * @param string $windowTitle The title of the window to find.
     * @return mixed The handle of the found window, or false if not found.
     */
    public function findWindowA(string $windowTitle)
    {
        $hwnd = $this->user32->FindWindowA(null, $windowTitle);

        if ($hwnd === null) {
            return false;
        }

        return $hwnd;
    }

    /**
     * Get the dimensions of a window.
     *
     * @param CData $hwnd The handle of the window.
     * @return array{height: float|int, left: mixed, top: mixed, width: float|int}|false An associative array with keys 'left', 'top', 'width', 'height', or false on failure.
     */
    public function getWindowDimensions(CData $hwnd)
    {
        /** @var RectInterface $rect */
        $rect = $this->user32->new("RECT");

        $result = $this->user32->GetWindowRect($hwnd, FFI::addr($rect));

        if (!$result) {
            return false;
        }

        return [
            'left' => $rect->x,
            'top' => $rect->y,
            'width' => $rect->cx - $rect->x,
            'height' => $rect->cy - $rect->y,
        ];
    }

    /**
     * Find a window by its title using the wide-character version of the API.
     *
     * @param string $windowTitle The title of the window to find.
     * @param string|null $className The class name of the window (optional).
     * @return mixed The handle of the found window, or false if not found.
     */
    public function findWindowW(string $windowTitle, string|null $className = null)
    {
        $wcharTitle = self::stringToWchar($this->user32, $windowTitle);

        $hwnd = $this->user32->FindWindowW($className, $wcharTitle);

        if ($hwnd === null) {
            return false;
        }

        return $hwnd;
    }

    /**
     * Flash a window to draw the user's attention.
     *
     * @param CData|null $hwnd Window handle or null to auto-detect.
     * @param int $flashCount Number of flashes
     */
    public function flashWindowEx(CData|null $hwnd = null, int $flashCount = 5)
    {
        if (!$hwnd) {
            $hwnd = $this->user32->GetDesktopWindow();
        }

        if (!$hwnd) {
            $hwnd = $this->user32->GetShellWindow();
        }

        if (!$hwnd) {
            throw new Exception("Could not find a suitable window handle");
        }

        $flashInfo = $this->user32->new("FLASHWINFO");
        $flashInfo->cbSize = FFI::sizeof($flashInfo);
        $flashInfo->hwnd = $hwnd;
        $flashInfo->dwFlags = FlashWindowEx::TRAY->value | FlashWindowEx::TIMERNOFG->value; // Flash taskbar continuously
        $flashInfo->uCount = $flashCount;
        $flashInfo->dwTimeout = 0;

        $result = $this->user32->FlashWindowEx(FFI::addr($flashInfo));

        return $result;
    }

    /**
     * Get the handle of the currently focused window.
     *
     * @return CData|bool The handle of the focused window, or false on failure.
     */
    public function getFocusWindow(): CData|bool
    {
        $hWnd = $this->user32->GetForegroundWindow();

        if (!$hWnd) {
            return false;
        }

        $buffer = $this->user32->new("char[256]");
        $length = $this->user32->GetWindowTextA($hWnd, $buffer, 256);

        if ($length <= 0) {
            return false;
        }

        return $hWnd;
    }

    /**
     * Move a window to the specified x and y coordinates.
     *
     * @param CData $processId The handle of the window to move.
     * @param int $x The new x-coordinate of the window.
     * @param int $y The new y-coordinate of the window.
     * @param int $bRepaint Whether to repaint the window (1 for true, 0 for false).
     *
     * @return bool True on success, false on failure.
     */
    public function moveWindow(CData $processId, int $x, int $y, int $bRepaint = 1): bool
    {
        $rect = $this->getWindowDimensions($processId);

        $result = $this->user32->MoveWindow($processId, $x, $y, $rect['width'], $rect['height'], $this->user32->cast("bool*", $bRepaint));

        return (bool) $result;
    }

    /**
     * Resize a window to the specified width and height.
     *
     * @param CData $processId The handle of the window to resize.
     * @param int $width The new width of the window.
     * @param int $height The new height of the window.
     * @param int $bRepaint Whether to repaint the window (1 for true, 0 for false).
     *
     * @return bool True on success, false on failure.
     */
    public function resizeWindow(CData $processId, int $width, int $height, int $bRepaint = 1): bool
    {
        $rect = $this->getWindowDimensions($processId);

        $result = $this->user32->MoveWindow($processId, $rect['left'], $rect['top'], $width, $height, $this->user32->cast("bool*", $bRepaint));

        return (bool) $result;
    }

    /**
     * Check if the screensaver is currently active.
     *
     * @return bool|null True if active, false if inactive, null on failure.
     */
    public function isScreenSaverActive(): ?bool
    {
        /** @var CDataInterface $val */
        $val = $this->user32->new('UINT');
        $active = $this->user32->SystemParametersInfoW(SystemParametersInfo::GETSCREENSAVEACTIVE->value, 0, FFI::addr($val), 0);
        return $active ? (bool) $val->cdata : null;
    }

    /**
     * Returns the login name of the user running the current process.
     *
     * Calls GetUserNameA from advapi32. The result matches what you see in
     * the Start Menu or from `whoami` on the command line.
     *
     * @return string|null Username, or null if the call fails.
     */
    public function getUserName()
    {
        $buffer = $this->user32->new("char[256]");
        $size = $this->user32->new("int");
        $size->cdata = 256;

        if ($this->advapi32->GetUserNameA($buffer, FFI::addr($size))) {
            // size includes the null terminator, so subtract one.
            return FFI::string($buffer, max(0, (int) $size->cdata - 1));
        }

        return null;
    }

    /**
     * Returns the NetBIOS name of the local computer (hostname).
     *
     * Calls GetComputerNameA with a 256-character buffer. The returned value
     * is identical to the string you would see in System Properties or from
     * the `hostname` command.
     *
     * @return string|null Computer name, or null if the call fails.
     */
    public function getComputerName(): string|null
    {
        $buffer = $this->user32->new("char[256]");
        // GetComputerNameA writes into buf and updates size with the actual length.
        $size = $this->user32->new("int");
        $size->cdata = 256;

        if ($this->kernel32->GetComputerNameA($buffer, FFI::addr($size))) {
            return FFI::string($buffer);
        }

        return null;
    }

    /**
     * Returns the current screen coordinates of the mouse cursor.
     *
     * Calls GetCursorPos via user32 and returns the result as an associative
     * array so callers never need to interact with CData directly.
     *
     * @return array{x: int, y: int}|bool An associative array with 'x' and 'y' keys, or false on failure.
     */
    public function getMousePosition()
    {
        /** @var PointInterface $point */
        $point = $this->user32->new("POINT");

        $cursor = $this->user32->GetCursorPos(FFI::addr($point));
        if (!$cursor) {
            return false;
        }

        return ['x' => (int) $point->x, 'y' => (int) $point->y];
    }

    /**
     * Enable or disable the screensaver.
     *
     * @param int $flag 1 to enable, 0 to disable.
     * @param int $reason Reason code (not used).
     *
     * @return bool True on success, false on failure.
     */
    public function screenInactive(int $flag = 0, int $reason = 0)
    {
        $pvParam = $this->user32->cast("void*", $flag);

        return $this->user32->SystemParametersInfoW(SystemParametersInfo::SETSCREENSAVEACTIVE->value, 0, $pvParam, SystemParametersInfo::UPDATEINIFILE->value);
    }

    /**
     * Read a registry value.
     *
     * @return int The result of the RegOpenKeyExW function.
     */
    public function readRegistryValue()
    {
        $pointer = 0x80000001;
        $hKey = $this->user32->new("void*");
        $key = $this->user32->cast("void*", $pointer);

        $imagePathUtf16 = mb_convert_encoding("Control Panel\\Desktop", 'UTF-16LE', 'UTF-8');
        $imagePathCData = $this->user32->new("wchar_t[" . (strlen($imagePathUtf16) / 2 + 1) . "]");
        FFI::memcpy($imagePathCData, $imagePathUtf16, strlen($imagePathUtf16));

        $result = $this->advapi32->RegOpenKeyExW($key, $imagePathCData, 0, Registry::KEY_ALL_ACCESS->value, FFI::addr($hKey));

        return $result;
    }

    /**
     * Read a registry value by its name.
     * 
     * @param int $rootKey The root key (e.g., HKEY_CURRENT_USER, HKEY_LOCAL_MACHINE).
     * @param string $subKey The subkey path (e.g., "Control Panel\\Desktop").
     * @param string $valueName The name of the value to read (e.g., "Wallpaper").
     * @return string|int|null
     */
    public function readRegistryValueByName(int $rootKey, string $subKey, string $valueName)
    {
        $hKeyArr = $this->advapi32->new("HKEY[1]");
        if ($this->advapi32->RegOpenKeyExA($rootKey, $subKey, 0, Registry::KEY_READ->value, $hKeyArr) !== 0) {
            return null;
        }

        $hKey = $hKeyArr[0];
        $typeBuf = $this->advapi32->new("DWORD[1]");
        $dataLen = $this->advapi32->new("DWORD[1]");
        $dataBuf = $this->advapi32->new("unsigned char[1024]");
        $dataLen[0] = 1024;

        if ($this->advapi32->RegQueryValueExA($hKey, $valueName, null, $typeBuf, $dataBuf, $dataLen) !== 0) {
            $this->advapi32->RegCloseKey($hKey);
            return null;
        }

        $type = $typeBuf[0];
        $this->advapi32->RegCloseKey($hKey);

        if ($type === 1 || $type === 2) {
            return FFI::string($dataBuf, $dataLen[0] - 1);
        } elseif ($type === 4) {
            $pointer1 = $dataBuf + 1;
            $pointer2 = $dataBuf + 2;
            $pointer3 = $dataBuf + 3;

            $val = ord(FFI::string($dataBuf, 1))
                | (ord(FFI::string($pointer1, 1)) << 8)
                | (ord(FFI::string($pointer2, 1)) << 16)
                | (ord(FFI::string($pointer3, 1)) << 24);
            return $val;
        }

        return FFI::string($dataBuf, $dataLen[0]);
    }

    /**
     * Set the desktop wallpaper.
     *
     * @param string $temporaryBitmapFile The path to the bitmap file to set as wallpaper.
     *
     * @return bool True on success, false on failure.
     */
    public function setWallpaper(string $temporaryBitmapFile)
    {
        $imagePathUtf16 = mb_convert_encoding($temporaryBitmapFile, 'UTF-16LE', 'UTF-8');
        $imagePathCData = $this->user32->new("wchar_t[" . (strlen($imagePathUtf16) / 2 + 1) . "]");
        FFI::memcpy($imagePathCData, $imagePathUtf16, strlen($imagePathUtf16));

        $result = $this->user32->SystemParametersInfoW(User32SystemParametersInfo::SETDESKWALLPAPER_UNICODE->value, 0, $imagePathCData, User32SystemParametersInfo::UPDATEINIFILE->value | User32SystemParametersInfo::SENDWININICHANGE->value);

        return $result;
    }

    /**
     * Get the current system volume level.
     *
     * @return array|bool An array containing left and right volume levels.
     */
    public function getVolume(): array|bool
    {
        $handlePtr = $this->winmm->new("HWAVEOUT");
        $wfx = $this->winmm->new("WAVEFORMATEX");
        $wfx->wFormatTag = 1;
        $wfx->nChannels = 2;
        $wfx->nSamplesPerSec = 44100;
        $wfx->wBitsPerSample = 16;
        $wfx->nBlockAlign = $wfx->nChannels * ($wfx->wBitsPerSample / 8);
        $wfx->nAvgBytesPerSec = $wfx->nSamplesPerSec * $wfx->nBlockAlign;
        $wfx->cbSize = 0;

        $openResult = $this->winmm->waveOutOpen(FFI::addr($handlePtr), 0xFFFFFFFF, FFI::addr($wfx), 0, 0, 0);

        if ($openResult !== 0) {
            return false;
        }

        $volume = $this->winmm->new("uint32_t");
        $result = $this->winmm->waveOutGetVolume($handlePtr, FFI::addr($volume));

        $this->winmm->waveOutClose($handlePtr);

        if ($result !== 0) {
            return false;
        }

        $raw = $volume->cdata;

        return [$raw & 0xFFFF, ($raw >> 16) & 0xFFFF];
    }

    /**
     * Returns the current master volume level as a scalar between 0.0 and 1.0.
     * 
     * @return float|bool
     */
    public function getMasterVolume(): float|bool
    {
        $this->ole32->CoInitialize(null);

        // CLSID_MMDeviceEnumerator / IID_IMMDeviceEnumerator / IID_IAudioEndpointVolume
        $clsidEnum = $this->buildGuid('BCDE0395-E52F-467C-8E3D-C4579291692E');
        $iidEnum = $this->buildGuid('A95664D2-9614-4F35-A746-DE8DB63617E6');
        $iidVolume = $this->buildGuid('5CDF2C82-841E-4546-9722-0CF74078229A');

        $pEnum = $this->ole32->new("void*");
        $hr = $this->ole32->CoCreateInstance(FFI::addr($clsidEnum[0]), null, 0x1, /* CLSCTX_INPROC_SERVER */ FFI::addr($iidEnum[0]), FFI::addr($pEnum));

        if ($hr !== 0) {
            $this->ole32->CoUninitialize();
            return false;
        }

        $enumVtable = $this->ole32->cast("void**", $this->ole32->cast("void**", $pEnum)[0]);
        /** @var Closure $fnGetDefaultAudioEndpoint */
        $fnGetDefaultAudioEndpoint = $this->ole32->cast($this->ole32->type("pfnGetDefaultAudioEndpoint"), $enumVtable[4]);

        $pDevice = $this->ole32->new("void*");
        $hr = $fnGetDefaultAudioEndpoint($pEnum, 0, 0, FFI::addr($pDevice)); // eRender=0, eConsole=0

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $enumVtable[2]);
        $fnRelease($pEnum);

        if ($hr !== 0) {
            $this->ole32->CoUninitialize();
            return false;
        }

        $deviceVtable = $this->ole32->cast("void**", $this->ole32->cast("void**", $pDevice)[0]);
        /** @var Closure $fnActivate */
        $fnActivate = $this->ole32->cast($this->ole32->type("pfnActivate"), $deviceVtable[3]);

        $pVolume = $this->ole32->new("void*");
        $hr = $fnActivate($pDevice, FFI::addr($iidVolume[0]), 0x1, null, FFI::addr($pVolume));

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $deviceVtable[2]);
        $fnRelease($pDevice);

        if ($hr !== 0) {
            $this->ole32->CoUninitialize();
            return false;
        }

        // IAudioEndpointVolume::GetMasterVolumeLevelScalar is at vtable index 9
        $volumeVtable = $this->ole32->cast("void**", $this->ole32->cast("void**", $pVolume)[0]);
        /** @var Closure $fnGetMasterVolumeLevelScalar */
        $fnGetMasterVolumeLevelScalar = $this->ole32->cast($this->ole32->type("pfnGetMasterVolumeLevelScalar"), $volumeVtable[9]);

        $level = $this->ole32->new("float");
        $hr = $fnGetMasterVolumeLevelScalar($pVolume, FFI::addr($level));

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $volumeVtable[2]);
        $fnRelease($pVolume);
        $this->ole32->CoUninitialize();

        return $hr === 0 ? $level->cdata : false;
    }

    /**
     * Converts a GUID string into a little-endian packed uint8_t[16] CData buffer.
     * 
     * @param string $guid The GUID string in the format "XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX".
     * 
     * @return CData
     */
    private function buildGuid(string $guid): CData
    {
        $hex = str_replace('-', '', $guid);
        $bytes = $this->ole32->new("uint8_t[16]");

        // Data1: 4 bytes little-endian
        for ($i = 0; $i < 4; $i++) {
            $bytes[$i] = hexdec(substr($hex, (3 - $i) * 2, 2));
        }
        // Data2: 2 bytes little-endian
        for ($i = 0; $i < 2; $i++) {
            $bytes[4 + $i] = hexdec(substr($hex, 8 + (1 - $i) * 2, 2));
        }
        // Data3: 2 bytes little-endian
        for ($i = 0; $i < 2; $i++) {
            $bytes[6 + $i] = hexdec(substr($hex, 12 + (1 - $i) * 2, 2));
        }
        // Data4: 8 bytes big-endian
        for ($i = 0; $i < 8; $i++) {
            $bytes[8 + $i] = hexdec(substr($hex, 16 + $i * 2, 2));
        }

        return $bytes;
    }

    /**
     * Set the system volume level.
     * 
     * @param int $left Left channel volume (0 to 65535).
     * @param int $right Right channel volume (0 to 65535).
     * @return bool
     */
    public function setVolume(int $left, int $right): bool
    {
        $left = max(0, min(0xFFFF, $left));
        $right = max(0, min(0xFFFF, $right));
        $volume = ($right << 16) | $left;

        return $this->winmm->waveOutSetVolume(null, $volume) === 0;
    }

    /**
     * Acquires IAudioEndpointVolume COM interface for the default audio render endpoint.
     * Returns [pVolume, volumeVtable] on success, false on failure.
     * 
     * @return array<CData|null>|bool
     */
    private function getAudioEndpointVolume(): array|bool
    {
        $clsidEnum = $this->buildGuid('BCDE0395-E52F-467C-8E3D-C4579291692E');
        $iidEnum = $this->buildGuid('A95664D2-9614-4F35-A746-DE8DB63617E6');
        $iidVolume = $this->buildGuid('5CDF2C82-841E-4546-9722-0CF74078229A');

        $pEnum = $this->ole32->new("void*");
        $hr = $this->ole32->CoCreateInstance(FFI::addr($clsidEnum[0]), null, 0x1, FFI::addr($iidEnum[0]), FFI::addr($pEnum));

        if ($hr !== 0) {
            return false;
        }

        $enumVtable = $this->ole32->cast("void**", $this->ole32->cast("void**", $pEnum)[0]);
        /** @var Closure $fnGetDefaultAudioEndpoint */
        $fnGetDefaultAudioEndpoint = $this->ole32->cast($this->ole32->type("pfnGetDefaultAudioEndpoint"), $enumVtable[4]);

        $pDevice = $this->ole32->new("void*");
        $hr = $fnGetDefaultAudioEndpoint($pEnum, 0, 0, FFI::addr($pDevice));

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $enumVtable[2]);
        $fnRelease($pEnum);

        if ($hr !== 0) {
            return false;
        }

        $deviceVtable = $this->ole32->cast("void**", $this->ole32->cast("void**", $pDevice)[0]);
        $fnActivate = $this->ole32->cast($this->ole32->type("pfnActivate"), $deviceVtable[3]);

        $pVolume = $this->ole32->new("void*");
        /** @var Closure $fnActivate */
        $hr = $fnActivate($pDevice, FFI::addr($iidVolume[0]), 0x1, null, FFI::addr($pVolume));

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $deviceVtable[2]);
        $fnRelease($pDevice);

        if ($hr !== 0) {
            return false;
        }

        return [
            $pVolume,
            $this->ole32->cast("void**", $this->ole32->cast("void**", $pVolume)[0]),
        ];
    }

    /**
     * Sets the master volume level. $level must be between 0.0 and 1.0.
     * 
     * @param float $level The desired master volume level as a scalar between 0.0 (silent) and 1.0 (full volume).
     * @return bool
     */
    public function setMasterVolume(float $level): bool
    {
        if ($level < 0.0 || $level > 1.0) {
            return false;
        }

        $this->ole32->CoInitialize(null);

        $result = $this->getAudioEndpointVolume();

        if ($result === false) {
            $this->ole32->CoUninitialize();
            return false;
        }

        [$pVolume, $volumeVtable] = $result;

        /** @var Closure $fnSet */
        $fnSet = $this->ole32->cast($this->ole32->type("pfnSetMasterVolumeLevelScalar"), $volumeVtable[7]);
        $hr = $fnSet($pVolume, $level, null);

        /** @var Closure $fnRelease */
        $fnRelease = $this->ole32->cast($this->ole32->type("pfnRelease"), $volumeVtable[2]);
        $fnRelease($pVolume);
        $this->ole32->CoUninitialize();

        return $hr === 0;
    }

    /**
     * Show a message box.
     *
     * @param string $caption The caption of the message box.
     * @param string $message The message to display.
     * @param int $flags The flags for the message box (default is MB_OK | MB_ICONINFORMATION).
     *
     * @return mixed The result of the MessageBoxA function.
     */
    public function showMessageBox(string $caption, string $message, int $flags = 0): mixed
    {
        return $this->user32->MessageBoxA(null, $message, $caption, $flags ?? MessageBox::MB_YESNO | MessageBox::MB_ICONINFORMATION);
    }

    /**
     * Show a Unicode message box using MessageBoxW.
     *
     * @param string $message The message to display.
     * @param string $title The title of the message box.
     * @param int $type The type of message box (default is MB_OK | MB_ICONINFORMATION).
     *
     * @return int
     */
    public function showMessageBoxW(string $message, string $title, int $type = 0x00000004 | 0x00000020): int
    {
        $msg = mb_convert_encoding($message . "\0", 'UTF-16LE', 'UTF-8');
        $ttl = mb_convert_encoding($title . "\0", 'UTF-16LE', 'UTF-8');

        $msgBuf = $this->user32->new("wchar_t[" . (strlen($msg) / 2) . "]");
        $ttlBuf = $this->user32->new("wchar_t[" . (strlen($ttl) / 2) . "]");

        FFI::memcpy($msgBuf, $msg, strlen($msg));
        FFI::memcpy($ttlBuf, $ttl, strlen($ttl));

        return $this->user32->MessageBoxW(null, $msgBuf, $ttlBuf, $type);
    }

    /**
     * Draw an image onto the desktop.
     *
     * @param string $imagePath The path to the image file.
     *
     * @return bool True on success, false on failure.
     */
    public function drawImageToDesktop(string $imagePath, int $cx = 0, int $cy = 0): bool
    {
        if (!file_exists($imagePath)) {
            error_log("Image file not found: $imagePath");
            return false;
        }

        $desktopHwnd = $this->user32->GetDesktopWindow();
        if ($desktopHwnd === null) {
            error_log("Failed to get desktop window handle.");
            return false;
        }

        $desktopHdc = $this->user32->GetWindowDC($desktopHwnd);
        if ($desktopHdc === null) {
            error_log("Failed to get desktop device context.");
            return false;
        }

        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            error_log("Failed to get image size for: $imagePath");
            $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
            return false;
        }
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $imageType = $imageInfo[2];

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp($imagePath);
                break;
            default:
                error_log("Unsupported image type: $imageType");
                $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
                return false;
        }

        if ($image === false) {
            error_log("Failed to create image from file: $imagePath");
            $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
            return false;
        }

        $memHdc = $this->gdi32->CreateCompatibleDC($desktopHdc);
        if ($memHdc === null) {
            error_log("Failed to create compatible DC.");
            $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($image);
            }
            return false;
        }

        $bitmap = $this->gdi32->CreateCompatibleBitmap($desktopHdc, $width, $height);
        if ($bitmap === null) {
            error_log("Failed to create compatible bitmap.");
            $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
            $this->gdi32->DeleteDC($memHdc);
            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($image);
            }
            return false;
        }

        $oldBitmap = $this->gdi32->SelectObject($memHdc, $bitmap);
        if ($oldBitmap === null) {
            error_log("Failed to select bitmap into DC.");
            $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);
            $this->gdi32->DeleteDC($memHdc);
            $this->gdi32->DeleteObject($bitmap);
            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($image);
            }
            return false;
        }

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $this->gdi32->SetPixelV($memHdc, $x, $y, ($b << 16) | ($g << 8) | $r);
            }
        }

        $result = $this->gdi32->BitBlt($desktopHdc, $cx, $cy, $width, $height, $memHdc, 0, 0, self::SRCCOPY);
        if ($result === false) {
            error_log("BitBlt failed.");
        }

        $this->gdi32->SelectObject($memHdc, $oldBitmap);
        $this->gdi32->DeleteDC($memHdc);
        $this->gdi32->DeleteObject($bitmap);
        $this->user32->ReleaseDC($desktopHwnd, $desktopHdc);

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($image);
        }

        return (bool) $result;
    }

    /**
     * Set a window to be transparent.
     * 
     * @param CData $hWnd The handle of the window to modify.
     * @return int
     */
    public function setWindowTransparent(CData $hWnd)
    {
        $oldStyle = $this->user32->SetWindowLongA($hWnd, self::GWL_EXSTYLE, 0);
        $currentExtendedStyle = $this->user32->SetWindowLongA($hWnd, self::GWL_EXSTYLE, $oldStyle);

        $newExtendedStyle = $currentExtendedStyle | self::WS_EX_LAYERED;

        $this->user32->SetWindowLongA($hWnd, self::GWL_EXSTYLE, $newExtendedStyle);

        return $this->user32->SetLayeredWindowAttributes($hWnd, self::COLORREF_MAGENTA, self::DEFAULT_ALPHA, LayeredWindowAttribute::COLORKEY->value | LayeredWindowAttribute::ALPHA->value);
    }

    /**
     * Set the alpha transparency of a window.
     * 
     * @param CData $hWnd The handle of the window to modify.
     * @param int $alpha The alpha value to set (0-255).
     * @return int
     */
    public function setWindowAlpha(CData $hWnd, int $alpha): int
    {
        $alpha = max(0, min(255, $alpha));

        $currentStyle = $this->user32->SetWindowLongA($hWnd, self::GWL_EXSTYLE, 0);
        $this->user32->SetWindowLongA($hWnd, self::GWL_EXSTYLE, $currentStyle | self::WS_EX_LAYERED);

        return $this->user32->SetLayeredWindowAttributes($hWnd, 0, $alpha, LayeredWindowAttribute::ALPHA->value);
    }

    /**
     * Check if the system has a battery.
     *
     * @param PowerStatusInterface|null $powerStatus Optional. If provided, will be populated with the power status.
     *
     * @return bool True if a battery is present, false otherwise.
     */
    public function hasBattery(PowerStatusInterface|null &$powerStatus = null)
    {
        $powerStatus = $this->kernel32->new("SYSTEM_POWER_STATUS");

        /** @var PowerStatusInterface $powerStatus */
        if (!$this->kernel32->GetSystemPowerStatus(FFI::addr($powerStatus))) {
            return false;
        }

        if ($powerStatus->BatteryFlag & 128) {
            return false;
        }

        return true;
    }

    /**
     * Get the current battery status.: array|bool
     *
     * @return array{
     *  batteryFullLifeTime: int, 
     *  batteryLifeTime: int, 
     *  chargingPercantage: int, 
     *  isCharging: int, 
     *  powerSavingFlag: int
     * }|bool An associative array with 'isCharging' and 'chargingPercantage' keys, or false if no battery is present.
     */
    public function getBatteryStatus()
    {
        if (!$this->hasBattery($powerStatus)) {
            return false;
        }

        /** @var PowerStatusInterface $powerStatus */
        if ($this->kernel32->GetSystemPowerStatus(FFI::addr($powerStatus))) {
            $batteryPercentage = $powerStatus->BatteryLifePercent;
            $charging = $powerStatus->BatteryFlag;
            // The field Windows documents here is SystemStatusFlag, and that is
            // what the struct declares; PowerSavingFlag was never a member, so
            // every call to this threw. The returned key keeps its name.
            $powerSavingFlag = $powerStatus->SystemStatusFlag;
            $batteryLifeTime = $powerStatus->BatteryLifeTime;
            $batteryFullLifeTime = $powerStatus->BatteryFullLifeTime;

            return [
                'isCharging' => $charging,
                'chargingPercantage' => $batteryPercentage,
                'powerSavingFlag' => $powerSavingFlag,
                'batteryLifeTime' => $batteryLifeTime,
                'batteryFullLifeTime' => $batteryFullLifeTime,
            ];
        }

        return false;
    }

    /**
     * Find a window by its class name or title.
     *
     * @param string $className The class name of the window.
     * @param string $windowTitle The title of the window.
     *
     * @return int|null The handle to the window if found, null otherwise.
     */
    public function findWindowByClassOrTitle(string $className, string $windowTitle): ?int
    {
        $hwnd = $this->user32->FindWindowA(null, $windowTitle);
        if ($hwnd !== 0) {
            if ($this->user32->IsWindow($hwnd)) {
                return $hwnd;
            }
        }

        $hwnd = $this->user32->FindWindowA($className, null);
        if ($hwnd !== 0) {
            if ($this->user32->IsWindow($hwnd)) {
                return $hwnd;
            }
        }

        return null;
    }

    /**
     * Register a window class with extended parameters using wide strings.
     *
     * @param CData $hInstance Handle to the application instance.
     * @param string $windowClassName The name of the window class.
     * @param string $windowTitle The title of the window.
     *
     * @return int The atom returned by RegisterClassExW.
     *
     * @throws Exception If registration fails.
     */
    public function registerClassExW(CData $hInstance, string $windowClassName, string $windowTitle)
    {
        $wClassName = $this->user32->new("WCHAR[" . (strlen($windowClassName) + 1) . "]", false);
        FFI::memcpy($wClassName, $windowClassName, strlen($windowClassName));
        $wClassName[strlen($windowClassName)] = "\0"; // Null-terminate

        $wClassTitle = $this->user32->new("WCHAR[" . (strlen($windowTitle) + 1) . "]", false);
        FFI::memcpy($wClassTitle, $windowTitle, strlen($windowTitle));
        $wClassTitle[strlen(string: $windowTitle)] = "\0";  // Null-terminate

        $wcx = $this->user32->new("WNDCLASSEXW");
        ; // Assuming you have WNDCLASSEXW struct defined

        $wcx->cbSize = ffi::sizeof($wcx);
        $wcx->style = 0;
        // Assign the function address to lpfnWndProc (window procedure)
        $pointer = FFI::addr($wcx->lpfnWndProc);
        $wcx->lpfnWndProc = $this->user32->cast('void*', $pointer);
        $wcx->cbClsExtra = 0;
        $wcx->cbWndExtra = 0;
        $wcx->hInstance = $hInstance;
        $wcx->hIcon = null;  // LoadIconW(NULL, IDI_APPLICATION); // Needs string to int conversion.
        $wcx->hCursor = null; //LoadCursorW(NULL, IDC_ARROW);
        $wcx->hbrBackground = null; //(HBRUSH)COLOR_WINDOW;
        $wcx->lpszMenuName = null;
        $wcx->lpszClassName = $wClassName; // Needs conversion string to wchar
        $wcx->hIconSm = null; // LoadIconW(NULL, IDI_APPLICATION);

        $atom = $this->user32->RegisterClassExW(ffi::addr($wcx));

        if ($atom == 0) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("Window class registration failed with error code: {$errorCode}");
        }

        return $atom;
    }

    /**
     * Show a notification in the system tray.
     * 
     * @param string $title The title of the notification.
     * @param string $message The message of the notification.
     * @param bool $wideString Whether to use wide strings (UTF-16) or not.
     * 
     * @return int The result of the Shell_NotifyIcon call.
     */
    public function showNotification(string $title, string $message, bool $wideString = false)
    {
        if ($wideString) {
            /** @var NotifyIconDataWInterface $nid */
            $nid = $this->shell32->new("NOTIFYICONDATAW");
            $nid->cbSize = FFI::sizeof($nid);
            $nid->uFlags = NotifyIconField::NIF_INFO->value;
            $nid->dwInfoFlags = NotifyIconNotification::NIIF_INFO->value;

            $szInfoPtr = FFI::addr($nid->szInfo[0]);
            $szInfoTitlePtr = FFI::addr($nid->szInfoTitle[0]);

            FFI::memset($szInfoPtr, 0, FFI::sizeof($nid->szInfo));
            FFI::memset($szInfoTitlePtr, 0, FFI::sizeof($nid->szInfoTitle));

            $from = iconv("UTF-8", "UTF-16LE", $message);
            FFI::memcpy($szInfoPtr, $from, strlen($message) * 2);
            $from = iconv("UTF-8", "UTF-16LE", $title);
            FFI::memcpy($szInfoTitlePtr, $from, strlen($title) * 2);

            $this->shell32->Shell_NotifyIconW(NotifyIconMessage::NIM_ADD->value, FFI::addr($nid));
            $result = $this->shell32->Shell_NotifyIconW(NotifyIconMessage::NIM_MODIFY->value, FFI::addr($nid));
        } else {
            /** @var NotifyIconDataAInterface $nid */
            $nid = $this->shell32->new("NOTIFYICONDATAA");
            $nid->cbSize = FFI::sizeof($nid);
            $nid->uFlags = NotifyIconField::NIF_INFO->value;

            $szInfoPtr = FFI::addr($nid->szInfo[0]);
            $szInfoTitlePtr = FFI::addr($nid->szInfoTitle[0]);

            FFI::memset($szInfoPtr, 0, FFI::sizeof($nid->szInfo));
            FFI::memset($szInfoTitlePtr, 0, FFI::sizeof($nid->szInfoTitle));

            FFI::memcpy($szInfoPtr, $message, strlen($message));
            FFI::memcpy($szInfoTitlePtr, $title, strlen($title));

            $nid->dwInfoFlags = NotifyIconNotification::NIIF_INFO->value;

            $this->shell32->Shell_NotifyIconA(NotifyIconMessage::NIM_ADD->value, FFI::addr($nid));
            $result = $this->shell32->Shell_NotifyIconA(NotifyIconMessage::NIM_MODIFY->value, FFI::addr($nid));
        }

        return $result;
    }

    /**
     * Get the number of physical monitors connected to the primary monitor.
     * 
     * @return CData|null The number of physical monitors, or null on failure.
     * 
     * @throws Exception If there is an error retrieving the number of physical monitors.
     */
    public function getNumberOfPhysicalMonitors(): ?CData
    {
        $pointer = 0;
        $hMonitor = $this->user32->MonitorFromWindow($this->user32->cast('void*', $pointer), 2);
        if ($hMonitor == null) {
            throw new Exception("Could not get primary monitor handle.");
        }

        $pdwNumberOfPhysicalMonitors = $this->user32->new("DWORD");
        if (!$this->dxva2->GetNumberOfPhysicalMonitorsFromHMONITOR($hMonitor, FFI::addr($pdwNumberOfPhysicalMonitors))) {
            $errorCode = $this->getLastErrorMessage();
            throw new Exception("Could not get the number of physical monitors. Error code: {$errorCode}");
        }
        $numMonitors = $pdwNumberOfPhysicalMonitors;

        return $numMonitors;
    }

    /**
     * Check if a file exists.
     * 
     * @param string $filePath The path to the file.
     * 
     * @return bool True if the file exists, false otherwise.
     */
    public function isFileExists(string $filePath): bool
    {
        $wideFilePath = mb_convert_encoding($filePath, 'UTF-16LE', 'UTF-8');

        $cFilePath = $this->user32->new("wchar_t[" . (strlen($wideFilePath) / 2 + 1) . "]");
        FFI::memcpy($cFilePath, $wideFilePath, strlen($wideFilePath));
        $cFilePath[strlen($wideFilePath) / 2] = "\0"; // Null-terminate

        $attributes = $this->kernel32->GetFileAttributesW($cFilePath);

        return $attributes !== 0xFFFFFFFF;
    }

    /**
     * Get the size of a file.
     * 
     * @param string $filePath The path to the file.
     * 
     * @return int|false The size of the file in bytes, or false on failure.
     */
    public function getFileSize(string $filePath): int|false
    {
        $wideFilePath = self::stringToWchar($this->user32, $filePath);
        $hFile = $this->kernel32->CreateFileW($wideFilePath, self::GENERIC_READ, self::FILE_SHARE_READ, NULL, self::OPEN_EXISTING, self::FILE_ATTRIBUTE_NORMAL, NULL);

        // CreateFileW reports failure as INVALID_HANDLE_VALUE, which is a
        // non-null pointer, so a missing file passes a truthiness check and the
        // size is then read through an invalid handle.
        if (!$hFile || $this->handleValue($hFile) === -1) {
            return false;
        }

        $fileSizeHigh = $this->user32->new('DWORD');
        $fileSizeLow = $this->kernel32->GetFileSize($this->user32->cast('void*', $hFile), FFI::addr($fileSizeHigh));

        if ($fileSizeLow === 0xFFFFFFFF) {
            // CloseHandle is a kernel32 export. Reached through user32 it does
            // not resolve at all, and the guard that stood here was
            // !is_object($this->user32), which is never true - so this handle
            // was leaked on every failed read.
            $this->kernel32->CloseHandle($this->user32->cast('void*', $hFile));

            return false;
        }

        $fileSize = (intval($fileSizeHigh->cdata) << 32) | intval($fileSizeLow);

        $this->kernel32->CloseHandle($this->user32->cast('void*', $hFile));
        return $fileSize;
    }

    /**
     * Write content to a file.
     * 
     * @param string $filename The path to the file.
     * @param string $content The content to write to the file.
     * 
     * @return void
     * 
     * @throws Exception If there is an error writing to the file.
     */
    public function writeFile(string $filename, string $content)
    {
        // Define constants (for better readability).  These are taken from the Windows API documentation.
        define('GENERIC_WRITE', 0x40000000);
        define('CREATE_ALWAYS', 2);
        define('FILE_ATTRIBUTE_NORMAL', 0x00000080);
        define('INVALID_HANDLE_VALUE', -1);
        define('FALSE', 0);

        $handle = $this->kernel32->CreateFileA($filename, self::GENERIC_WRITE, 0, null, self::CREATE_ALWAYS, self::FILE_ATTRIBUTE_NORMAL, null);
        $c_content = $this->user32->new("char[" . strlen($content) . "]", false);
        FFI::memcpy($c_content, $content, strlen($content));

        $bytes_written = $this->user32->new("DWORD");
        $success = $this->kernel32->WriteFile($handle, $c_content, strlen($content), FFI::addr($bytes_written), null);

        if (!$success) {
            $errorCode = $this->getLastErrorMessage();
            $this->kernel32->CloseHandle($handle);
            throw new Exception("Error writing to file: {$errorCode}");
        }

        $this->kernel32->CloseHandle($handle);
    }

    /**
     * Copy a file from source to destination.
     * 
     * @param string $source
     * @param string $destination
     * @param bool $failIfExists
     * @return bool
     */
    public function copyFile(string $source, string $destination, bool $failIfExists = false): bool
    {
        return (bool) $this->kernel32->CopyFileA($source, $destination, $failIfExists ? 1 : 0);
    }

    /**
     * Delete a file.
     * 
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        return (bool) $this->kernel32->DeleteFileA($filePath);
    }

    /**
     * Moves or renames a file from $source to $destination.
     *
     * Wraps MoveFileA. The source and destination may be on different volumes;
     * in that case Windows performs a cross-device copy + delete. The call
     * fails if the destination already exists.
     *
     * @param string $source      Path to the file to move.
     * @param string $destination Target path (including file name).
     * @return bool True on success, false on failure.
     */
    public function moveFile(string $source, string $destination): bool
    {
        return (bool) $this->kernel32->MoveFileA($source, $destination);
    }

    /**
     * Read the contents of a file.
     * 
     * @param string $filename The path to the file.
     * @param string $mode The mode in which to open the file. Default is "rb" (read binary).
     * 
     * @return string|null The contents of the file, or null on failure.
     */
    public function readFileContents(string $filename, string $mode = "rb"): ?string
    {
        $fileHandle = $this->msvcrt->fopen($filename, $mode);

        if ($fileHandle === null) {
            return null;
        }

        $this->msvcrt->fseek($fileHandle, 0, 2);
        $fileSize = $this->msvcrt->ftell($fileHandle);
        $this->msvcrt->fseek($fileHandle, 0, 0);

        if ($fileSize === false || $fileSize < 0) {
            $this->msvcrt->fclose($fileHandle);
            return null;
        }

        $buffer = $this->user32->new("char[$fileSize]");
        $bytesRead = $this->msvcrt->fread($buffer, 1, $fileSize, $fileHandle);

        $this->msvcrt->fclose($fileHandle);

        if ($bytesRead != $fileSize) {
            return null;
        }

        $content = FFI::string($buffer, $bytesRead);

        return $content;
    }

    /**
     * Window procedure to handle messages.
     * 
     * @param FFI\CData $hWnd
     * @param int $uMsg
     * @param int|FFI\CData $wParam
     * @param int|FFI\CData $lParam
     * 
     * @return int|FFI\CData
     */
    public function wndProc(FFI\CData $hWnd, int $uMsg, int|FFI\CData $wParam, int|FFI\CData $lParam): int|FFI\CData
    {
        $pointer = 0;

        switch ($uMsg) {
            case WindowMessage::WM_CLOSE:
                $this->user32->DestroyWindow($hWnd);
                return $this->user32->cast('LRESULT', $pointer);

            case WindowMessage::WM_DESTROY:
                $this->user32->PostQuitMessage(0);
                return $this->user32->cast('LRESULT', $pointer);
        }

        $hWndInt = is_int($hWnd) ? $hWnd : (int) $hWnd;
        if (isset($this->windowProcedureHandlers[$hWndInt])) {
            $callback = $this->windowProcedureHandlers[$hWndInt];
            $callbackResult = $callback($hWnd, $uMsg, $wParam, $lParam);
            if ($callbackResult !== null) {
                return $callbackResult;
            }
        }

        // default fallback
        return $this->user32->DefWindowProcA($hWnd, $uMsg, $wParam, $lParam);
    }

    /**
     * Register a per-window WNDPROC callback to interpose WM_COMMAND etc.
     *
     * @param CData $hWnd
     * @param Closure $proc (hWnd, uMsg, wParam, lParam) => int|null
     */
    public function registerWindowProcedure(CData $hWnd, Closure $proc): void
    {
        $hWndInt = (int) $hWnd;
        $this->windowProcedureHandlers[$hWndInt] = $proc;
    }

    /**
     * Unregister a per-window WNDPROC callback.
     *
     * @param CData $hWnd
     */
    public function unregisterWindowProcedure(CData $hWnd): void
    {
        $hWndInt = (int) $hWnd;
        unset($this->windowProcedureHandlers[$hWndInt]);
    }

    /**
     * Create a list box control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateListBox(CData $parent, CData $hInstance, int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $windowStyle = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | WindowStyle::WS_BORDER->value | WindowStyle::WS_BOTHSCROLL->value | SendMessage::LBS_STANDARD->value;
        $listBox = $this->user32->CreateWindowExA(0, "LISTBOX", null, $windowStyle, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        return $listBox;
    }

    /**
     * Create an edit text control.
     * 
     * @param CData $parent
     * @param string $label
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateEditText(CData $parent, string $label = "", int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $hInstance = $this->kernel32->GetModuleHandleA(null);

        // Hardcoded style values to avoid potential enum issues:
        // WS_VISIBLE=0x10000000 | WS_CHILD=0x40000000 | WS_BORDER=0x00800000
        // WS_HSCROLL=0x00100000 | WS_VSCROLL=0x00200000
        // ES_MULTILINE=0x0004 | ES_AUTOVSCROLL=0x0040 | ES_AUTOHSCROLL=0x0080
        $style = 0x50B000C4;

        // Allocate a persistent C string for lpWindowName to avoid potential
        // PHP string→LPCSTR conversion issues in FFI
        $labelLen = strlen($label);
        $lpWindowName = $this->user32->new("char[" . ($labelLen + 1) . "]");
        if ($labelLen > 0) {
            FFI::memcpy($lpWindowName, $label, $labelLen);
        }
        $lpWindowName[$labelLen] = "\0";

        return $this->user32->CreateWindowExA(0, "EDIT", FFI::addr($lpWindowName[0]), $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Diagnostic: Create an EDIT control step-by-step to identify crash cause.
     * Prints output at each stage — check which echo is the last one before crash.
     *
     * @param CData $parent
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData|null
     */
    public function CreateEditTextDebug(CData $parent, int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100): mixed
    {
        $dbg = function (string $msg) {
            echo $msg . "\n";
            ob_flush();
            flush();
        };

        $dbg("[DEBUG] Step 1: Getting hInstance");
        $hInstance = $this->kernel32->GetModuleHandleA(null);
        $dbg("[DEBUG] Step 1 OK - hInstance obtained");

        $dbg("[DEBUG] Step 2: Casting hMenu");
        $hMenu = $this->user32->cast("void*", $ptr);
        $dbg("[DEBUG] Step 2 OK - hMenu cast done");

        // Test A: absolute minimum style — WS_VISIBLE | WS_CHILD only
        $dbg("[DEBUG] Step 3A: CreateWindowExA MINIMAL style (0x50000000)");
        $minimalResult = $this->user32->CreateWindowExA(0, "EDIT", "test", 0x50000000, /* WS_VISIBLE | WS_CHILD */ $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
        $dbg("[DEBUG] Step 3A OK - minimal EDIT: " . ($minimalResult === null ? "NULL" : "valid"));

        if ($minimalResult !== null && !FFI::isNull($minimalResult)) {
            $this->user32->DestroyWindow($minimalResult);
            $dbg("[DEBUG] Step 3A cleanup done");
        }

        // Test B: add ES_MULTILINE
        $dbg("[DEBUG] Step 3B: + ES_MULTILINE (0x50000004)");
        $result = $this->user32->CreateWindowExA(0, "EDIT", "test", 0x50000004, /* + ES_MULTILINE */ $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
        $dbg("[DEBUG] Step 3B OK: " . ($result === null ? "NULL" : "valid"));

        if ($result !== null && !FFI::isNull($result)) {
            $this->user32->DestroyWindow($result);
        }

        // Test C: add scrollbars
        $dbg("[DEBUG] Step 3C: + WS_HSCROLL|WS_VSCROLL (0x50300004)");
        $result = $this->user32->CreateWindowExA(0, "EDIT", "test", 0x50300004, /* + WS_HSCROLL | WS_VSCROLL */ $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
        $dbg("[DEBUG] Step 3C OK: " . ($result === null ? "NULL" : "valid"));

        if ($result !== null && !FFI::isNull($result)) {
            $this->user32->DestroyWindow($result);
        }

        // Test D: full style with non-empty string
        $dbg("[DEBUG] Step 3D: full style (0x50B000C4) + non-empty text");
        $result = $this->user32->CreateWindowExA(0, "EDIT", "test", 0x50B000C4, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
        $dbg("[DEBUG] Step 3D OK: " . ($result === null ? "NULL" : "valid"));

        // Test E: same as D but with empty string
        $dbg("[DEBUG] Step 3E: full style + empty string");
        if ($result !== null && !FFI::isNull($result)) {
            $this->user32->DestroyWindow($result);
        }
        $result = $this->user32->CreateWindowExA(0, "EDIT", "", 0x50B000C4, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
        $dbg("[DEBUG] Step 3E OK: " . ($result === null ? "NULL" : "valid"));

        $dbg("[DEBUG] ALL TESTS PASSED");
        return $result;
    }

    /**
     * Create a button control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param string $title
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateButton(CData $parent, CData $hInstance, string $title = 'Button', int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100)
    {
        $hMenu = $this->user32->cast("HWND", $ptr);

        return $this->user32->CreateWindowExA(0, "BUTTON", $title, WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x00000000, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Create a checkbox control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param string $title
     * @param bool $checked
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateCheckBox(CData $parent, CData $hInstance, string $title = 'Checkbox', bool $checked = false, int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 25)
    {
        $hMenu = $this->user32->cast("HWND", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x00000003;

        $checkbox = $this->user32->CreateWindowExA(0, "BUTTON", $title, $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        if ($checked) {
            $this->user32->SendMessageA($checkbox, 0x00F1, 1, 0);
        }

        return $checkbox;
    }

    /**
     * Create a radio button control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param string $title
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateRadioButton(CData $parent, CData $hInstance, string $title = 'Radio', int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 25)
    {
        $hMenu = $this->user32->cast("HWND", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x00000009;

        return $this->user32->CreateWindowExA(0, "BUTTON", $title, $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Create a combo box control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param array $items
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateComboBox(CData $parent, CData $hInstance, array $items = [], int $ptr = 1, int $x = 0, int $y = 0, int $width = 150, int $height = 200)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x00800000 | 0x00000003 | 0x00200000;

        $comboBox = $this->user32->CreateWindowExA(0, "COMBOBOX", "", $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        foreach ($items as $item) {
            $this->user32->SendMessageA($comboBox, 0x0143, 0, $item);
        }

        return $comboBox;
    }

    /**
     * Create a progress bar control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param int $min
     * @param int $max
     * @param int $pos
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateProgressBar(CData $parent, CData $hInstance, int $min = 0, int $max = 100, int $pos = 0, int $ptr = 1, int $x = 0, int $y = 0, int $width = 200, int $height = 25)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value;

        $progressBar = $this->user32->CreateWindowExA(0, "msctls_progress32", "", $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        $pointer = ($max << 16) | $min;
        $this->user32->SendMessageA($progressBar, 0x0401, 0, $this->user32->cast("LPARAM", $pointer));
        $this->user32->SendMessageA($progressBar, 0x0402, $pos, 0);

        return $progressBar;
    }

    /**
     * Set the position of a progress bar control.
     * 
     * @param CData $progressBar
     * @param int $position
     * @return void
     */
    public function setProgressBarPosition(CData $progressBar, int $position): void
    {
        $this->user32->SendMessageA($progressBar, 0x0402, $position, 0);
    }

    /**
     * Create a group box control.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param string $title
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateGroupBox(CData $parent, CData $hInstance, string $title = 'Group', int $x = 0, int $y = 0, int $width = 200, int $height = 150)
    {
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x00000007;

        return $this->user32->CreateWindowExA(0, "BUTTON", $title, $style, $x, $y, $width, $height, $parent, null, $hInstance, null);
    }

    /**
     * Create an image box (static control) to display an image.
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param string $imagePath
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     * 
     * @throws Exception
     */
    public function CreateImageBox(CData $parent, CData $hInstance, string $imagePath, int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100)
    {
        $windowStyle = WindowStyle::WS_CHILD->value | WindowStyle::WS_VISIBLE->value | StaticControl::SS_BITMAP->value | WindowStyle::WS_BORDER->value | StaticControl::SS_REALSIZEIMAGE->value | StaticControl::SS_CENTERIMAGE->value | StaticControl::SS_NOTIFY->value;

        $hMenu = $this->user32->cast("HMENU", $ptr);
        $context = $this->user32->CreateWindowExA(0, "STATIC", null, $windowStyle, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        if ($context === null) {
            throw new Exception("Failed to create STATIC control");
        }

        if (str_ends_with($imagePath, '.bmp')) {
            $hBitmap = $this->user32->LoadImageA(null, $imagePath, 0, 0, 0, LoadResource::LR_LOADFROMFILE->value | LoadResource::LR_CREATEDIBSECTION->value);
            if ($hBitmap === null) {
                throw new Exception("Failed to load bitmap");
            }

            $bitmapPtr = $this->user32->cast("LPARAM", $hBitmap);
            $this->user32->SendMessageA($context, StaticMessage::STM_SETIMAGE->value, 0, $bitmapPtr);
            $this->user32->InvalidateRect($context, null, 1);
            $this->user32->UpdateWindow($context);

            $bmp = $this->gdi32->new("BITMAP");
            $this->gdi32->GetObjectA($hBitmap, FFI::sizeof($bmp), FFI::addr($bmp));
            $this->user32->SetWindowPos($context, null, 0, 0, $bmp->bmWidth, $bmp->bmHeight, SetWindowPos::SWP_NOZORDER->value);

            $redrawMode = RedrawWindow::RDW_INVALIDATE->value | RedrawWindow::RDW_UPDATENOW->value | RedrawWindow::RDW_ERASE->value;
            $this->user32->RedrawWindow($context, null, null, $redrawMode);
        } else {
            $imagePtr = $this->gdiplus->new("GpImage*[1]");
            $utf16le = mb_convert_encoding($imagePath, "UTF-16LE") . "\0\0";
            $count = strlen($utf16le) / 2;
            $wfile = $this->user32->new("uint16_t[$count]");
            FFI::memcpy($wfile, $utf16le, strlen($utf16le));
            $this->gdiplus->GdipLoadImageFromFile($wfile, FFI::addr($imagePtr));

            $imgWidth = $this->gdiplus->new("uint32_t");
            $imgHeight = $this->gdiplus->new("uint32_t");
            $this->gdiplus->GdipGetImageWidth($imagePtr[0], FFI::addr($imgWidth));
            $this->gdiplus->GdipGetImageHeight($imagePtr[0], FFI::addr($imgHeight));
            $w = (int) $imgWidth->cdata;
            $h = (int) $imgHeight->cdata;

            $hdc = $this->user32->GetDC($context);
            $hdcMem = $this->gdi32->CreateCompatibleDC($hdc);
            $hBitmap = $this->gdi32->CreateCompatibleBitmap($hdc, $w, $h);
            $hOld = $this->gdi32->SelectObject($hdcMem, $hBitmap);

            $hdcInt = $this->gdiplus->new("intptr_t");
            $hdcInt->cdata = (int) $hdcMem;
            $pointer = FFI::addr($hdcInt);
            $hdcPtr = $this->gdiplus->cast("void*", $pointer);

            $graphics = $this->gdiplus->new("GpGraphics*[1]");
            $this->gdiplus->GdipCreateFromHDC($hdcPtr, FFI::addr($graphics));
            $this->gdiplus->GdipDrawImageRectI($graphics[0], $imagePtr[0], 0, 0, $w, $h);
            $this->gdiplus->GdipDeleteGraphics($graphics[0]);

            $this->gdi32->SelectObject($hdcMem, $hOld);
            $this->gdi32->DeleteDC($hdcMem);
            $this->user32->ReleaseDC($context, $hdc);

            $hBitmapCData = $this->gdi32->new("intptr_t");
            $hBitmapCData->cdata = (int) $hBitmap;
            $pointer = FFI::addr($hBitmapCData);
            $bitmapPtr = $this->user32->cast("intptr_t*", $pointer)[0];

            $this->user32->SendMessageA($context, StaticMessage::STM_SETIMAGE->value, 0, $bitmapPtr);

            $this->user32->SetWindowPos($context, null, 0, 0, $w, $h, SetWindowPos::SWP_NOZORDER->value | SetWindowPos::SWP_NOMOVE->value);
            $this->user32->InvalidateRect($context, null, 1);
            $this->user32->UpdateWindow($context);

            $this->gdiplus->GdipDisposeImage($imagePtr[0]);
        }

        return $context;
    }

    /**
     * Create a date picker control.
     * 
     * @param CData $parent
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateStatusBar(CData $parent, int $ptr = 1, int $x = 0, int $y = 0, $width = 100, $height = 100)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $hInstance = $this->kernel32->GetModuleHandleA(null);

        return $this->user32->CreateWindowExA(0, "msctls_statusbar32", "", 0x10000000 | 0x40000000, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Create a date picker control.
     * 
     * @param CData $parent
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateDatePicker(CData $parent, int $ptr = 1, int $x = 0, int $y = 0, $width = 100, $height = 100)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $hInstance = $this->kernel32->GetModuleHandleA(null);

        return $this->user32->CreateWindowExA(0, "SysDateTimePick32", "", 0x10000000 | 0x40000000, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Create a label (static text) control.
     * 
     * @param CData $parent
     * @param string $label
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * 
     * @return CData
     */
    public function CreateLabel(CData $parent, string $label = 'Label', int $ptr = 1, int $x = 0, int $y = 0, int $width = 100, int $height = 100)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $hInstance = $this->kernel32->GetModuleHandleA(null);

        return $this->user32->CreateWindowExA(0, "STATIC", $label, 0x10000000 | 0x40000000, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);
    }

    /**
     * Create a trackbar (slider) control.
     * @param CData $parent
     * @param CData $hInstance
     * @param int $min
     * @param int $max
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateTrackBar(CData $parent, CData $hInstance, int $min = 0, int $max = 100, int $ptr = 1, int $x = 0, int $y = 0, int $width = 200, int $height = 40)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x0001 | 0x0020;

        $trackbar = $this->user32->CreateWindowExA(0, "msctls_trackbar32", "", $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        $this->user32->SendMessageA($trackbar, 0x0407, 1, $min);
        $this->user32->SendMessageA($trackbar, 0x0408, 1, $max);

        return $trackbar;
    }

    /**
     * Create an up-down control (spinner).
     * 
     * @param CData $parent
     * @param CData $hInstance
     * @param int $min
     * @param int $max
     * @param int $pos
     * @param int $ptr
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return CData
     */
    public function CreateUpDown(CData $parent, CData $hInstance, int $min = 0, int $max = 100, int $pos = 0, int $ptr = 1, int $x = 0, int $y = 0, int $width = 50, int $height = 25)
    {
        $hMenu = $this->user32->cast("void*", $ptr);
        $style = WindowStyle::WS_VISIBLE->value | WindowStyle::WS_CHILD->value | 0x0020 | 0x0002 | 0x0010;

        $updown = $this->user32->CreateWindowExA(0, "msctls_updown32", "", $style, $x, $y, $width, $height, $parent, $hMenu, $hInstance, null);

        $pointer = ($max << 16) | $min;
        $this->user32->SendMessageA($updown, 0x0467, 0, $this->user32->cast("LPARAM", $pointer));
        $this->user32->SendMessageA($updown, 0x0469, 0, $pos);

        return $updown;
    }

    /**
     * Enumerates all running processes and returns an array of process entries.
     *
     * Uses the kernel32 Toolhelp32 snapshot API (CreateToolhelp32Snapshot /
     * Process32First / Process32Next) to walk the process list. Each entry
     * in the returned array contains:
     *
     *   'pid'        – numeric process ID
     *   'parentPid'  – PID of the parent process
     *   'name'       – executable file name (e.g. "notepad.exe")
     *   'threads'    – current thread count
     *
     * @return array<int, array{pid: int, parentPid: int, name: string, threads: int}> Indexed array of process-info maps. Empty if the snapshot fails.
     */
    public function getProcessList()
    {
        $list = [];
        $snapshot = $this->kernel32->CreateToolhelp32Snapshot(0x00000002, 0);

        /** @var ProcessSentry32Interface $entries */
        $entries = $this->kernel32->new("PROCESSENTRY32");
        $structSize = FFI::sizeof($entries);
        $entries->dwSize = FFI::sizeof($entries);

        if (!$this->kernel32->Process32First($snapshot, $entries)) {
            throw new Exception("Failed to get process list");
        }

        do {
            $list[] = [
                "process_id" => $entries->th32ProcessID,
                "threads" => $entries->cntThreads,
                "parent_process_id" => $entries->th32ParentProcessID,
                "priority_class_base" => $entries->pcPriClassBase,
                "exe_file" => FFI::string($entries->szExeFile, 260)
            ];

            $pointer = $entries->szExeFile;
            FFI::memset($pointer, 0, 260);
            $entries->dwSize = $structSize;
        }
        while ($this->kernel32->Process32Next($snapshot, $entries));

        return $list;
    }

    /**
     * Find processes by name.
     * 
     * @param string $processName
     * @return array
     */
    public function findProcessByName(string $processName): array
    {
        $list = $this->getProcessList();
        $found = [];

        foreach ($list as $process) {
            $exeName = trim($process['exe_file'], "\0");
            if (stripos($exeName, $processName) !== false) {
                $found[] = $process;
            }
        }

        return $found;
    }

    /**
     * Create a menu and attach it to the specified window.
     * 
     * @param CData $hWnd
     * @param string $title
     * @param int|CData $pid
     * 
     * @return CData
     */
    public function createMenu(CData $hWnd, string $title = '', int|Cdata $pid = 1)
    {
        $hMenu = $this->user32->CreateMenu();

        $this->user32->AppendMenuA($hMenu, 0x0000, $pid, $title);
        $this->user32->SetMenu($hWnd, $hMenu);

        return $hMenu;
    }

    /**
     * Open Remote Desktop Connection application.
     * 
     * @return CData
     */
    public function openRemoteDesktop()
    {
        return $this->shellExecute("C:\\Windows\\System32\\mstsc.exe");
    }

    /**
     * Open Notepad application.
     * 
     * @return CData
     */
    public function openNotepad()
    {
        return $this->shellExecute("C:\\Windows\\System32\\notepad.exe");
    }

    /**
     * Open Disk Cleanup Manager application.
     * 
     * @return CData
     */
    public function openCleanManager()
    {
        return $this->shellExecute("C:\\Windows\\System32\\cleanmgr.exe");
    }

    /**
     * Open Microsoft Paint application.
     * 
     * @return CData|null
     */
    public function openPainter()
    {
        $userName = $this->getUserName();

        if ($this->isFileExists("C:\\Windows\\System32\\mspaint.exe")) {
            return $this->shellExecute("C:\\Windows\\System32\\mspaint.exe");
        } else if ($userName && $this->isFileExists("C:\\Users\\{$userName}\\AppData\\Local\\Microsoft\\WindowsApps\\mspaint.exe")) {
            return $this->shellExecute("C:\\Users\\{$userName}\\AppData\\Local\\Microsoft\\WindowsApps\\mspaint.exe");
        }

        return null;
    }

    /**
     * Open Command Line Prompt (cmd.exe).
     * 
     * @return CData
     */
    public function openCommandLinePrompt()
    {
        return $this->shellExecute("C:\\Windows\\System32\\cmd.exe");
    }

    /**
     * Open Calculator application.
     * 
     * @return CData
     */
    public function openCalculator()
    {
        return $this->shellExecute("C:\\Windows\\System32\\calc.exe");
    }

    /**
     * Open Task Manager application.
     * 
     * @return CData
     */
    public function openTaskManager()
    {
        return $this->shellExecute("C:\\Windows\\System32\\Taskmgr.exe");
    }

    /**
     * Open Device Manager application.
     * 
     * @return CData
     */
    public function openDeviceManager()
    {
        return $this->shellExecute("C:\\Windows\\System32\\devmgmt.msc");
    }

    /**
     * Open Control Panel application.
     * 
     * @return CData
     */
    public function openControlPanel()
    {
        return $this->shellExecute("C:\\Windows\\System32\\control.exe");
    }

    /**
     * Open Registry Editor application.
     * 
     * @return CData
     */
    public function openRegistryEditor()
    {
        return $this->shellExecute("C:\\Windows\\regedit.exe");
    }

    /**
     * Open PowerShell application.
     * 
     * @return CData
     */
    public function openPowerShell()
    {
        return $this->shellExecute("C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe");
    }

    /**
     * Open a URL in the default web browser.
     * 
     * @param string $url
     * @return mixed
     */
    public function openURLW(string $url)
    {
        return $this->shellExecute($url);
    }

    /**
     * Open a directory in File Explorer.
     * @param string $directoryPath
     * @return mixed
     */
    public function openDirectory(string $directoryPath)
    {
        return $this->shellExecute($directoryPath, "explore");
    }

    /**
     * Create a new process from command string.
     * 
     * @param string $cmd
     * 
     * @return bool
     */
    public function createProcessFromCommand(string $cmd)
    {
        $false = 0;

        return $this->kernel32->CreateProcessA($cmd, null, null, null, $this->kernel32->cast('bool*', $false), 0, null, null, null, null);
    }

    /**
     * Create a new window with specified title and dimensions.
     * 
     * @param string $title
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * 
     * @return void
     * 
     * @throws Exception
     */
    public function createNewWindow(string $title = 'Windows', int $width = 100, int $height = 100, int $x = 0, int $y = 0)
    {
        $hInstance = $this->kernel32->GetModuleHandleA(null);

        $this->registerWindowClass($hInstance);
        $hWnd = $this->createWindow($hInstance, $title, $width, $height, $x, $y);

        if (FFI::isNull($hWnd) || $hWnd === null) {
            throw new Exception("Failed to CreateWindow");
        }

        $this->user32->ShowWindow($hWnd, 1);
        $this->user32->UpdateWindow($hWnd);

        $msg = $this->user32->new("MSG");
        $hmidi = $this->openMidiChannel();
        $prevKeycode = -1;
        $prevNote = -1;

        $gdipToken = $this->createGDIPlusContext();

        /** @var bool Guard to prevent duplicate control creation on repeated WM_DWMNCRENDERINGCHANGED */
        $initialized = false;
        $textBox = null;
        $statusBar = null;
        $subMenu = null;

        $dbg = function (string $m) {
            echo "[DBG] {$m}\n";
            if (function_exists('ob_flush')) {
                @ob_flush();
            }@flush();
        };

        while (true) {
            $break = false;
            $this->user32->GetMessageW(FFI::addr($msg), null, 0, 0);

            $msgId = $msg->message;
            //$dbg("MSG: 0x" . dechex($msgId));

            switch ($msgId) {
                case 799: //WindowMessage::WM_DWMNCRENDERINGCHANGED:
                    $dbg("WM_DWMNCRENDERINGCHANGED - initialized=" . ($initialized ? "true" : "false"));
                    // This message fires multiple times (initial render, maximize, restore, etc.)
                    // Only create controls on the first occurrence
                    if ($initialized) {
                        $dbg("SKIP (already initialized)");
                        break;
                    }
                    $initialized = true;

                    //$this->createTray('test', $hWnd);

                    $dbg("Creating PopupMenu");
                    $subMenu = $this->user32->CreatePopupMenu();
                    $this->user32->AppendMenuA($subMenu, 0x0000, 1001, "System &Information");
                    $this->user32->AppendMenuA($subMenu, 0x0000, 1002, "Sub Item 2");
                    $mainMenu = $this->createMenu($hWnd, "File", $subMenu);
                    $dbg("Menu created");

                    $dbg("Creating StatusBar");
                    $statusBar = $this->CreateStatusBar($hWnd);
                    $dbg("StatusBar created: " . ($statusBar === null ? "NULL" : "ok"));

                    $dbg("Creating EditText");
                    $textBox = $this->CreateEditText($hWnd, '', 2, 0, 0, 480, 400);
                    $dbg("EditText created: " . ($textBox === null ? "NULL" : "ok"));

                    //$this->CreateImageBox($hWnd, $hInstance, 'C:\\Users\\A\\Desktop\\fixing.png', 3);

                    $dbg("Init complete");
                    break;
                case 1: //WindowMessage::WM_CREATE:
                    $dbg("WM_CREATE");
                    break;
                case 0x0005: // WM_SIZE
                    $dbg("WM_SIZE - initialized=" . ($initialized ? "true" : "false") . " textBox=" . ($textBox === null ? "NULL" : "ok") . " statusBar=" . ($statusBar === null ? "NULL" : "ok"));
                    if ($initialized && $textBox !== null && $statusBar !== null) {
                        $dbg("WM_SIZE: GetClientRect...");
                        /** @var RectInterface $rcClient */
                        $rcClient = $this->user32->new('RECT');
                        $this->user32->GetClientRect($hWnd, FFI::addr($rcClient));
                        $clientWidth = $rcClient->cx;
                        $clientHeight = $rcClient->cy;
                        $dbg("WM_SIZE: client={$clientWidth}x{$clientHeight}");

                        if ($clientWidth > 0 && $clientHeight > 30) {
                            $dbg("WM_SIZE: SetWindowPos textBox");
                            $this->user32->SetWindowPos($textBox, null, 0, 0, $clientWidth, $clientHeight - 30, SetWindowPos::SWP_NOZORDER->value);
                            $dbg("WM_SIZE: SetWindowPos statusBar");
                            $this->user32->SetWindowPos($statusBar, null, 0, $clientHeight - 30, $clientWidth, 30, SetWindowPos::SWP_NOZORDER->value);
                            $dbg("WM_SIZE: done");
                        } else {
                            $dbg("WM_SIZE: SKIP (invalid size)");
                        }
                    } else {
                        $dbg("WM_SIZE: SKIP (not ready)");
                    }
                    break;
                case 15: //WindowMessage::WM_PAINT:
                    break;
                case 256://WindowMessage::WM_KEYDOWN:
                    $keycode = chr($msg->lParam);
                    echo "click keyboard : {$keycode}";

                    if ($prevKeycode == $keycode) {
                        echo 'overlap';
                        break;
                    }

                    $keyMap = [
                        'A' => 60,
                        'W' => 61,
                        'S' => 62,
                        'E' => 63,
                        'D' => 64,
                        'F' => 65,
                        'T' => 66,
                        'G' => 67,
                        'Y' => 68,
                        'H' => 69,
                        'U' => 70,
                        'J' => 71,
                        'K' => 72,
                        'I' => 73,
                        'L' => 74,
                    ];

                    $note = $keyMap[$keycode] ?? 60;
                    $prevNote = $note;
                    $prevKeycode = $keycode;

                    $this->playMidi($hmidi, $note);

                    echo 'keyup';
                    break;
                case 257: //WindowMessage::WM_KEYUP:
                    echo 'keydown';
                    $this->stopMidi($hmidi, $prevNote);
                    break;
                case 512: //WindowMessage::WM_MOUSEMOVE:
                    break;
                case 513: //WindowMessage::WM_LBUTTONDOWN:
                    break;
                case 514: //WindowMessage::WM_LBUTTONUP:
                    break;
                case 273: //WindowMessage::WM_COMMAND:
                    $menuId = $msg->lParam;
                    echo "Menu ID: {$menuId}\n";

                    if ($menuId == 1) {
                        $hMenu = $this->user32->GetMenu($hWnd);

                        $itemIndex = 0;
                        /** @var RectInterface $rect */
                        $rect = $this->user32->new("RECT");
                        $this->user32->GetMenuItemRect($hWnd, $hMenu, $itemIndex, FFI::addr($rect));
                        $x = $rect->cx;
                        $y = $rect->cy;

                        $this->user32->TrackPopupMenu($subMenu, 0x0000, $x, $y, 0, $hWnd, null);
                    } else if ($menuId == 1001) {
                        $usb = $this->getUSBDevices();
                        $devices = $this->getStorageInformation(0);

                        $usbText = "";
                        foreach ($usb as $device) {
                            $usbText .= "Device hardware id: {$device['hardwareId']}\r\n";
                            $usbText .= "Device class: {$device['deviceClass']}\r\n";
                            $usbText .= "Location: {$device['location']}\r\n";
                            if (!empty($device['vendor'])) {
                                $usbText .= "Vendor: {$device['vendor']['company']['name']}\r\n";
                                $usbText .= "Device Name: {$device['vendor']['device']['name']}\r\n";
                                $usbText .= "Revision: {$device['vendor']['revision']}\r\n";
                            }

                            $usbText .= "-----------------------------------\r\n";
                        }

                        $text = <<<EOD
                        # USB Device Information\r
                        \r
                        {$usbText}

                        # Storage Device Information\r
                        \r
                        Seek penalty type : {$devices['seek_penalty_type']}\r
                        Model : {$devices['descriptor']['model']}\r
                        Serial : {$devices['descriptor']['serial']}\r
                        Revision : {$devices['descriptor']['revision']}\r
                        BusType : {$devices['descriptor']['busType']}
                        EOD;

                        $this->setWindowContextText($textBox, $text);
                    } else if ($menuId == 1002) {
                        $filePath = $this->showFileOpenDialog();

                        if ($filePath) {
                            $content = $this->readFileContents($filePath);
                            $this->setWindowContextText($textBox, $content);
                            $this->setWindowTitleByPid($hWnd, $filePath);
                        }
                        $this->showNotification("Menu Clicked", "You clicked the menu item!", false);
                    }

                    break;
                case 161: //WindowMessage::WM_NCLBUTTONDOWN:
                    $lParam = $msg->lParam;

                    switch ($lParam) {
                        case 10: // Left border
                        case 11: // Right border
                        case 12: // Top border
                        case 15: // Bottom border
                        case 17:
                            $startResize = true;
                            break;
                        case 20: // Close button
                            $this->user32->PostQuitMessage(0);
                            $break = true;
                            break;
                        case 2: // Title bar
                            $startResize = true;
                            break;
                        case 3: // Program Icon
                            break;
                        case 8: // Minimalize
                            break;
                        case 9: // Maximize
                            $startResize = true;
                            break;
                    }

                    break;
            }

            $this->user32->TranslateMessage(FFI::addr($msg));
            $this->user32->DispatchMessageW(FFI::addr($msg));

            if ($break) {
                break;
            }
        }

        $this->gdiplus->GdiplusShutdown($gdipToken[0]);
    }

    /**
     * Open a MIDI channel.
     * 
     * @return CData
     * @throws Exception
     */
    public function openMidiChannel()
    {
        $hmidi_ptr = $this->winmm->new("HMIDIOUT");
        $res = $this->winmm->midiOutOpen(FFI::addr($hmidi_ptr), 0, 0, 0, 0);
        if ($res !== 0) {
            throw new Exception("midiOutOpen failed: $res\n");
        }

        $hmidi = $hmidi_ptr[0];
        return $hmidi;
    }

    /**
     * Close the MIDI channel.
     * 
     * @param CData $hmidi
     * 
     * @return void
     */
    public function closeMidiChannel(CData $hmidi)
    {
        $this->winmm->midiOutClose(FFI::addr($hmidi));
    }

    /**
     * Stop a MIDI note.
     * 
     * @param CData $hmidi
     * @param int $note
     * 
     * @return void
     */
    public function stopMidi(CData $hmidi, int $note)
    {
        $msg = 0x80 | ($note << 8);
        $this->winmm->midiOutShortMsg(FFI::addr($hmidi), $msg);
    }

    /**
     * Play a MIDI note.
     * 
     * @param CData $hmidi
     * @param int $note
     * @param int $velocity
     * 
     * @return void
     */
    public function playMidi(CData $hmidi, int $note = 60, int $velocity = 100): void
    {
        $msg = 0x90 | ($note << 8) | ($velocity << 16);
        $this->winmm->midiOutShortMsg(FFI::addr($hmidi), $msg);
    }

    /**
     * Create COLORREF value from RGB components.
     * 
     * @param int $r
     * @param int $g
     * @param int $b
     * 
     * @return int
     */
    private function COLORREF(int $r, int $g, int $b): int
    {
        return ($r) | ($g << 8) | ($b << 16);
    }

    /**
     * Show color picker dialog.
     * 
     * @return string|false
     */
    public function showColorPicker(): bool|string
    {
        $custColors = $this->comdlg32->new("COLORREF[16]");
        for ($i = 0; $i < 16; $i++) {
            $custColors[$i] = 0xFFFFFF;
        }

        $cc = $this->comdlg32->new("CHOOSECOLORW");
        $cc->lStructSize = FFI::sizeof($cc);
        $cc->hwndOwner = null;
        $cc->lpCustColors = FFI::addr($custColors[0]);
        $cc->Flags = 0x00000001; // CC_RGBINIT

        if (!$this->comdlg32->ChooseColorW(FFI::addr($cc))) {
            return false;
        }

        return sprintf("#%06X", $cc->rgbResult);
    }

    /**
     * Show file open dialog.
     * 
     * @param string $title Dialog title.
     * @param string $filter Null-separated filter string (e.g. "All Files\0*.*\0Text Files\0*.TXT")
     * 
     * @return string|false
     */
    public function showFileOpenDialog(string $title = 'Select File', string $filter = "All Files\0*.*\0Text Files\0*.TXT")
    {
        $fileBuffer = $this->comdlg32->new("char[260]");
        $ofn = $this->comdlg32->new("OPENFILENAMEA");
        $ofn->lStructSize = FFI::sizeof($ofn);
        $ofn->hwndOwner = null;

        $buf = $this->comdlg32->new("char[" . (strlen($filter) + 1) . "]");
        FFI::memcpy($buf, $filter, strlen($filter));
        $ofn->lpstrFilter = FFI::addr($buf[0]);
        $ofn->lpstrFile = FFI::addr($fileBuffer[0]);
        $ofn->nMaxFile = 260;

        $buf = $this->comdlg32->new("char[" . (strlen($title) + 1) . "]");
        FFI::memcpy($buf, $title, strlen($title));
        $ofn->lpstrTitle = FFI::addr($buf[0]);
        $ofn->Flags = 0x00000008;
        $ofn->lpstrDefExt = null;

        if (!$this->comdlg32->GetOpenFileNameA(FFI::addr($ofn))) {
            return false;
        }

        $selectedPath = FFI::string($ofn->lpstrFile);
        return $selectedPath;
    }

    /**
     * Show file save dialog.
     * 
     * @param string $title
     * @param string $filter
     * @param string $defaultExt
     * @return string|false
     */
    public function showFileSaveDialog(string $title = 'Save File', string $filter = "All Files\0*.*\0", string $defaultExt = ""): string|false
    {
        $fileBuffer = $this->comdlg32->new("char[260]");
        FFI::memset($fileBuffer, 0, 260);

        $ofn = $this->comdlg32->new("OPENFILENAMEA");
        $ofn->lStructSize = FFI::sizeof($ofn);
        $ofn->hwndOwner = null;

        $filterBuf = $this->comdlg32->new("char[" . (strlen($filter) + 1) . "]");
        FFI::memcpy($filterBuf, $filter, strlen($filter));
        $ofn->lpstrFilter = FFI::addr($filterBuf[0]);
        $ofn->lpstrFile = FFI::addr($fileBuffer[0]);
        $ofn->nMaxFile = 260;

        $titleBuf = $this->comdlg32->new("char[" . (strlen($title) + 1) . "]");
        FFI::memcpy($titleBuf, $title, strlen($title));
        $ofn->lpstrTitle = FFI::addr($titleBuf[0]);
        $ofn->Flags = 0x00000002 | 0x00000004;

        if ($defaultExt !== "") {
            $extBuf = $this->comdlg32->new("char[" . (strlen($defaultExt) + 1) . "]");
            FFI::memcpy($extBuf, $defaultExt, strlen($defaultExt));
            $ofn->lpstrDefExt = FFI::addr($extBuf[0]);
        } else {
            $ofn->lpstrDefExt = null;
        }

        if (!$this->comdlg32->GetSaveFileNameA(FFI::addr($ofn))) {
            return false;
        }

        return FFI::string($ofn->lpstrFile);
    }

    /**
     * Set window context text.
     * 
     * @param CData $hWnd Window handle.
     * @param string $text
     * 
     * @return void
     */
    public function setWindowContextText(CData $hWnd, string $text)
    {
        $this->user32->SetWindowTextA($hWnd, $text);
    }

    /**
     * Get window context text.
     * 
     * @param CData $hWnd Window handle.
     * 
     * @return string
     */
    public function getWindowContextText(CData $hWnd): string
    {
        $length = $this->user32->GetWindowTextLengthA($hWnd);
        $buf = $this->user32->new("char[" . ($length + 1) . "]");
        $this->user32->GetWindowTextA($hWnd, $buf, $length + 1);

        return FFI::string($buf);
    }

    /**
     * Get the title/text of a window (ANSI) via GetWindowTextA.
     *
     * Unlike `getWindowContextText()`, this method accepts a generic window handle type
     * (e.g. `CData` or castable handle values) for convenience.
     *
     * @param mixed $hwnd Window handle.
     * @return string Window title, or empty string if unavailable.
     */
    public function getWindowTitle(mixed $hwnd): string
    {
        $len = $this->user32->GetWindowTextLengthA($hwnd);
        if ($len <= 0) {
            return '';
        }
        $buf = $this->user32->new('char[' . ($len + 1) . ']');
        $this->user32->GetWindowTextA($hwnd, $buf, $len + 1);
        return FFI::string($buf);
    }

    /**
     * Get process ID from mouse cursor position.
     * 
     * @return int
     */
    public function getProcessIdFromMouseCursorPosition()
    {
        $point = $this->user32->new("POINT");
        $this->user32->GetCursorPos(FFI::addr($point));

        $hwnd = $this->user32->WindowFromPoint($point);

        /** @var CDataInterface $pid_ptr */
        $pid_ptr = $this->user32->new("DWORD");
        $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pid_ptr));

        return $pid_ptr->cdata;
    }

    /**
     * Register a window class.
     * 
     * @param CData $hInstance
     * @param bool $wide
     * 
     * @return int
     * 
     * @throws RuntimeException
     */
    public function registerWindowClass(CData $hInstance, bool $wide = false)
    {
        if ($wide) {
            $defaultIcon = 32512;
            $pointer = 5 + 1;

            $wndClass = $this->user32->new("WNDCLASSEXW");
            $wndClass->cbSize = FFI::sizeof($wndClass);
            $wndClass->style = 0x0002 | 0x0001;
            $wndClass->lpfnWndProc = $this->user32->DefWindowProcA;
            $wndClass->cbClsExtra = 0;
            $wndClass->cbWndExtra = 0;
            $wndClass->hInstance = $hInstance;
            $wndClass->hIcon = $this->user32->LoadIconA(null, $this->user32->cast('LPCSTR', $defaultIcon));
            $wndClass->hCursor = $this->user32->LoadCursorA(null, $this->user32->cast('LPCSTR', $defaultIcon));
            $wndClass->hbrBackground = $this->user32->cast('HBRUSH', $pointer);
            $wndClass->lpszMenuName = null;

            $classNameW = $this->user32->new('wchar_t[' . (strlen(self::WND_CLASS_NAME) + 1) . ']', false);
            $from = self::WND_CLASS_NAME;
            FFI::memcpy($classNameW, $from, strlen(self::WND_CLASS_NAME));
            $wndClass->lpszClassName = $classNameW;

            $wndClass->hIconSm = $this->user32->LoadIconA(null, $this->user32->cast('LPCSTR', $defaultIcon));

            $result = $this->user32->RegisterClassExW(FFI::addr($wndClass));
            if (!$result) {
                throw new RuntimeException("Failed to register window class.");
            }

            return $result;
        } else {
            $wc = $this->user32->new("WNDCLASSEXA");
            $wc->cbSize = FFI::sizeof($wc);
            $wc->style = 0;
            $wc->lpfnWndProc = $this->user32->DefWindowProcA;
            $wc->hInstance = null;
            $wc->hCursor = null;
            $wc->hbrBackground = null;
            $className = $this->user32->new("char[256]");
            $from = self::WND_CLASS_NAME . "\0";
            FFI::memcpy($className, $from, strlen(self::WND_CLASS_NAME) + 1);
            $wc->lpszClassName = FFI::addr($className[0]);
            return $this->user32->RegisterClassExA(FFI::addr($wc));
        }
    }

    /**
     * Create a window.
     * 
     * @param CData $hInstance
     * @param string $title
     * @param int $width
     * @param int $height
     * @param int $x
     * @param int $y
     * 
     * @return CData
     * 
     * @throws RuntimeException
     */
    public function createWindow(CData $hInstance, string $title, int $width, int $height, int $x = 0, int $y = 0)
    {
        $windowStyle = 0x00CF0000/*WindowStyle::WS_OVERLAPPEDWINDOW->value*/ | 0x10000000/*WindowStyle::WS_VISIBLE->value*/ ;
        $hWnd = $this->user32->CreateWindowExA(0, self::WND_CLASS_NAME, $title, $windowStyle, $x, $y, $width, $height, null, null, $hInstance, null);

        if ($hWnd === null) {
            throw new RuntimeException("Failed to create window.");
        }

        return $hWnd;
    }

    /**
     * Set keyboard layout.
     * 
     * @param string $layoutIdentifier Keyboard layout identifier (e.g. "00000409" for English US).
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public function setKeyboardLayout(string $layoutIdentifier)
    {
        $hkl = $this->user32->LoadKeyboardLayoutA($layoutIdentifier, 0x00000001);
        if ($hkl === null) {
            throw new Exception("Error loading keyboard layout: {$layoutIdentifier}");
        }

        $result = $this->user32->ActivateKeyboardLayout($hkl, 0x00000001);
        if (!$result) {
            throw new Exception("Error activating keyboard layout: {$layoutIdentifier}");
        }

        return true;
    }

    /**
     * Get asynchronous key state.
     * 
     * @param int $keyCode
     * 
     * @return int
     */
    public function getAsyncKeyState(int $keyCode)
    {
        return $this->user32->GetAsyncKeyState($keyCode);
    }

    /**
     * Check if a specific key is currently pressed.
     * 
     * @param int $keyCode Virtual key code (e.g. 0x41 for 'A', 0x42 for 'B', etc.)
     * @return bool
     */
    public function isKeyPressed(int $keyCode): bool
    {
        return ($this->user32->GetAsyncKeyState($keyCode) & 0x8000) !== 0;
    }

    /**
     * Get network interface types.
     * 
     * @return string[]|bool
     */
    public function getNetworkInterface()
    {
        /** @var CDataInterface $flags */
        $flags = $this->user32->new("DWORD");
        $connected = $this->wininet->InternetGetConnectedState(FFI::addr($flags), 0);

        if (!$connected) {
            return false;
        }

        $f = (int) $flags->cdata;

        $bits = [
            0x01 => 'MODEM',
            0x02 => 'LAN',
            0x04 => 'PROXY',
            0x10 => 'RAS_INSTALLED',
            0x20 => 'OFFLINE',
            0x40 => 'CONFIGURED',
        ];

        $types = [];
        foreach ($bits as $bit => $label) {
            if ($f & $bit) {
                $types[] = $label;
            }
        }

        return $types;
    }

    /**
     * Check internet connection.
     * 
     * @return bool
     */
    public function isInternetConnected()
    {
        $flags = $this->user32->new("DWORD");
        $connected = $this->wininet->InternetGetConnectedState(FFI::addr($flags), 0);

        return (bool) $connected;
    }

    /**
     * Get window state (minimized, maximized, normal).
     * 
     * @param CData $hwnd
     * 
     * @return string|bool
     */
    public function getWindowState(CData $hwnd): bool|string
    {
        $placement = $this->user32->new("WINDOWPLACEMENT");
        $placement->length = FFI::sizeof($placement);

        if (!$this->user32->GetWindowPlacement($hwnd, FFI::addr($placement))) {
            return false;
        }

        $status = [
            0x01 => 'NORMAL',
            0x02 => 'MINIMIZED',
            0x03 => 'MAXIMIZED',
        ];

        return $status[$placement->showCmd] ?? 'UNKNOWN';
    }

    /**
     * Set screen resolution.
     * 
     * @param int $width
     * @param int $height
     * 
     * @return bool
     */
    public function setScreenResolution(int $width = 1280, int $height = 720)
    {
        $devmode = $this->user32->new("DEVMODE");
        $devmode->dmSize = FFI::sizeof($devmode);
        $this->user32->EnumDisplaySettingsA(null, EnumDisplaySettings::ENUM_CURRENT_SETTINGS->value, FFI::addr($devmode));

        $devmode->dmPelsWidth = $width;
        $devmode->dmPelsHeight = $height;
        $devmode->dmFields = DevMode::DM_PELSWIDTH->value | DevMode::DM_PELSHEIGHT->value;
        return $this->user32->ChangeDisplaySettingsA(FFI::addr($devmode), ChangeDisplaySettings::CDS_UPDATEREGISTRY->value) === 0;
    }

    /**
     * Get list of subdirectories in a directory.
     * 
     * @param string $pattern Search pattern (e.g. "C:\\Windows\\*").
     * 
     * @return string[]
     */
    public function getSubDirectoriesInDirectory(string $pattern = "C:\\\\Windows\\\\*")
    {
        $list = [];
        /** @var Win32FindDataAInterface $data */
        $data = $this->kernel32->new("WIN32_FIND_DATAA");
        $handle = $this->kernel32->FindFirstFileA($pattern, FFI::addr($data));

        if ($handle) {
            do {
                $name = FFI::string($data->cFileName);
                $isDir = ($data->dwFileAttributes & 0x10) !== 0; // FILE_ATTRIBUTE_DIRECTORY
                if ($isDir && $name !== "." && $name !== "..") {
                    $list[] = $name;
                }
            } while ($this->kernel32->FindNextFileA($handle, FFI::addr($data)));

            $this->kernel32->FindClose($handle);
        }

        return $list;
    }

    /**
     * Get list of files in a directory.
     * 
     * @param string $pattern Search pattern (e.g. "C:\\Windows\\System32\\*").
     * 
     * @return string[]
     */
    public function getFileListInDirectory(string $pattern = "C:\\\\Windows\\\\System32\\\\*")
    {
        $list = [];
        /** @var Win32FindDataAInterface $data */
        $data = $this->kernel32->new("WIN32_FIND_DATAA");
        $handle = $this->kernel32->FindFirstFileA($pattern, FFI::addr($data));

        if ($handle) {
            do {
                $name = FFI::string($data->cFileName);

                if ($name !== "." && $name !== "..") {
                    $list[] = $name;
                }
            } while ($this->kernel32->FindNextFileA($handle, FFI::addr($data)));

            $this->kernel32->FindClose($handle);
        }

        return $list;
    }

    /**
     * Get list of files in a directory with details (size, attributes, etc.).
     * 
     * @param string $pattern
     * @return array{name: string, isDirectory: bool, size: int, attributes: int}[]
     */
    public function getFileListWithDetails(string $pattern = "C:\\\\Windows\\\\System32\\\\*"): array
    {
        $list = [];
        /** @var Win32FindDataAInterface $data */
        $data = $this->kernel32->new("WIN32_FIND_DATAA");
        $handle = $this->kernel32->FindFirstFileA($pattern, FFI::addr($data));

        if ($handle) {
            do {
                $name = FFI::string($data->cFileName);
                if ($name !== "." && $name !== "..") {
                    $isDir = ($data->dwFileAttributes & 0x10) !== 0;
                    $size = ($data->nFileSizeHigh << 32) | $data->nFileSizeLow;

                    $list[] = [
                        'name' => $name,
                        'isDirectory' => $isDir,
                        'size' => $size,
                        'attributes' => $data->dwFileAttributes,
                    ];
                }
            } while ($this->kernel32->FindNextFileA($handle, FFI::addr($data)));

            $this->kernel32->FindClose($handle);
        }

        return $list;
    }

    /**
     * Create a new directory.
     * 
     * @param string $path Path of the directory to create.
     * @return bool
     */
    public function createDirectory(string $path): bool
    {
        return (bool) $this->kernel32->CreateDirectoryA($path, null);
    }

    /**
     * Remove a directory.
     * 
     * @param string $path Path of the directory to remove.
     * @return bool
     */
    public function removeDirectory(string $path): bool
    {
        return (bool) $this->kernel32->RemoveDirectoryA($path);
    }

    /**
     * Get supported screen resolutions.
     * 
     * @return array{frequency: mixed, height: mixed, width: mixed}[]
     */
    public function getSupportedScreenResolutionList()
    {
        $list = [];
        $modeNum = -1;

        while (true) {
            /** @var DevModeInterface $devmode */
            $devmode = $this->user32->new("DEVMODE");
            $devmode->dmSize = FFI::sizeof($devmode);

            $result = $this->user32->EnumDisplaySettingsA(null, $modeNum++, FFI::addr($devmode));
            if ($result === 0) {
                break;
            }

            $list[] = [
                'width' => $devmode->dmPelsWidth,
                'height' => $devmode->dmPelsHeight,
                'frequency' => $devmode->dmDisplayFrequency
            ];
        }

        return $list;
    }

    /**
     * Wait until a key is pressed and return the key event information.
     * 
     * @return CData
     * 
     * @throws Exception
     */
    public function waitUntilKeyPress()
    {
        $handle = $this->kernel32->GetStdHandle(GetStdHandle::STD_INPUT_HANDLE->value);
        $oldMode = $this->kernel32->new('DWORD');
        if (!$this->kernel32->GetConsoleMode($handle, FFI::addr($oldMode))) {
            throw new Exception("Cannot initialize old console mode");
        }

        $newConsoleMode = SetConsoleMode::ENABLE_WINDOW_INPUT->value | SetConsoleMode::ENABLE_PROCESSED_INPUT->value;
        if (!$this->kernel32->SetConsoleMode($handle, $newConsoleMode)) {
            throw new Exception("Impossible to change the console mode");
        }

        /** @var CDataInterface $bufferSize */
        $bufferSize = $this->kernel32->new('DWORD');
        $arrayBufferSize = 128;
        $inputBuffer = $this->kernel32->new("INPUT_RECORD[$arrayBufferSize]");
        /** @var CDataInterface $cNumRead */
        $cNumRead = $this->kernel32->new('DWORD');

        while (true) {
            $this->kernel32->GetNumberOfConsoleInputEvents($handle, FFI::addr($bufferSize));

            if ($bufferSize->cdata <= 1) {
                continue;
            }

            if (!$this->kernel32->ReadConsoleInputA($handle, $inputBuffer, $arrayBufferSize, FFI::addr($cNumRead))) {
                throw new Exception("Read console input failing");
            }

            for ($j = $cNumRead->cdata - 1; $j >= 0; $j--) {
                if ($inputBuffer[$j]->EventType !== ReadConsoleInput::KEY_EVENT->value) {
                    continue;
                }

                $keyEvent = $inputBuffer[$j]->Event->KeyEvent;
                return $keyEvent;
            }
        }

        $this->kernel32->CloseHandle($handle);
    }

    /**
     * Create a system tray icon.
     * 
     * @param string $title Tooltip text for the tray icon.
     * @param CData $hwnd Handle to the window that will receive notifications from the tray icon.
     * 
     * @return bool
     */
    public function createTray(string $title, CData $hwnd)
    {
        $icon = IconIdentifier::IDI_APPLICATION->value;
        $hIcon = $this->user32->LoadIconA(null, $this->user32->cast("LPCSTR", $icon));

        $nid = $this->shell32->new("NOTIFYICONDATAA");
        $nid->cbSize = FFI::sizeof($nid);
        $nid->hWnd = $hwnd;
        $nid->uID = 1;
        $nid->uFlags = NotifyIconField::NIF_MESSAGE->value | NotifyIconField::NIF_ICON->value | NotifyIconField::NIF_TIP->value;
        $nid->uCallbackMessage = 0;
        $nid->hIcon = $hIcon;
        $szTipPtr = FFI::addr($nid->szTip);
        $titleMem = "{$title}\0";
        FFI::memcpy($szTipPtr, $titleMem, strlen($title));

        return $this->shell32->Shell_NotifyIconA(NotifyIconMessage::NIM_ADD->value, FFI::addr($nid));
    }

    /**
     * Get list of installed programs.
     * 
     * @return string[][]
     * 
     * @throws Exception
     */
    public function getInstalledProgramList()
    {
        $list = [];
        $mainKeyArr = $this->advapi32->new("HKEY[1]");
        if ($this->advapi32->RegOpenKeyExA(Registry::HKEY_LOCAL_MACHINE->value, RegistryKey::UNINSTALL_PATHS->value, 0, Registry::KEY_READ->value, $mainKeyArr) !== 0) {
            throw new Exception("Failed to open Uninstall registry key\n");
        }

        $mainKey = $mainKeyArr[0];
        $index = 0;

        while (true) {
            $item = [];

            $nameBuf = $this->advapi32->new("char[256]");
            $nameLen = $this->advapi32->new("DWORD[1]");
            $nameLen[0] = 256;

            $ret = $this->advapi32->RegEnumKeyExA($mainKey, $index, $nameBuf, $nameLen, NULL, NULL, NULL, NULL);
            if ($ret !== 0) {
                break;
            }

            $subKeyName = FFI::string($nameBuf, $nameLen[0]);
            $item['key'] = $subKeyName;

            $subKeyPathArr = $this->advapi32->new("HKEY[1]");
            $fullPath = RegistryKey::UNINSTALL_PATHS->value . "\\" . $subKeyName;

            $index++;
            if ($this->advapi32->RegOpenKeyExA(Registry::HKEY_LOCAL_MACHINE->value, $fullPath, 0, Registry::KEY_READ->value, $subKeyPathArr) !== 0) {
                continue;
            }

            $typeBuf = $this->advapi32->new("DWORD[1]");
            $dataLen = $this->advapi32->new("DWORD[1]");
            $dataBuf = $this->advapi32->new("unsigned char[1024]");

            $dataLen[0] = 1024;
            $subKeyHandle = $subKeyPathArr[0];
            if ($this->advapi32->RegQueryValueExA($subKeyHandle, "DisplayName", NULL, $typeBuf, $dataBuf, $dataLen) === 0) {
                $displayName = FFI::string($dataBuf, $dataLen[0]);
                $item['DisplayName'] = $displayName;
            }

            $this->advapi32->RegCloseKey($subKeyHandle);

            $list[] = $item;
        }

        $this->advapi32->RegCloseKey($mainKey);

        return $list;
    }

    /**
     * Handle the OnPaint event.
     * 
     * @param FFI\CData $hwnd
     * @return void
     */
    private function OnPaint(FFI\CData $hwnd)
    {
        /*$ps = $this->user32->new("PAINTSTRUCT");
        $hdc = $this->user32->BeginPaint($hwnd, FFI::addr($ps));
        $memdc = $this->gdi32->CreateCompatibleDC($hdc);
        $oldBmp = $this->gdi32->SelectObject($memdc, $this->hBitmap);

        $this->gdi32->BitBlt($hdc,0,0,$this->bmp->bmWidth,$this->bmp->bmHeight,$memdc,0,0,$this->user32->cast('int', 0x00CC0020));
        $this->gdi32->SelectObject($memdc, $oldBmp);
        $this->gdi32->DeleteDC($memdc);
        $this->user32->EndPaint($hwnd, FFI::addr($ps));*/
    }

    /**
     * Get list of connected USB devices.
     * 
     * @return array{
     *  description: string|null, 
     *  deviceClass: string|null, 
     *  enumerator: string|null, 
     *  friendlyName: string|null,
     *  hardwareId: string|null, 
     *  location: string|null, 
     *  manufacturer: string|null, 
     *  vendor: array{
     *      company: array{
     *          name: string, 
     *          id: int|float, 
     *          hexid: string
     *      },
     *      device: array{
     *          name: string, 
     *          id: int|float, 
     *          hexid: string
     *      },
     *      revision: string
     *  }
     * }|null}[]
     * 
     * @throws Exception
     */
    public function getUSBDevices(): array
    {
        $list = [];
        $hDevInfo = $this->setupapi->SetupDiGetClassDevsA(null, "USB", null, DeviceInformationGetClassFlags::DIGCF_PRESENT->value | DeviceInformationGetClassFlags::DIGCF_ALLCLASSES->value);
        /** @var CDataInterface $handleValue */
        $handleValue = $this->setupapi->cast('intptr_t', $hDevInfo);

        if ($handleValue->cdata == 0 || $handleValue->cdata == -1) {
            $error = $this->kernel32->GetLastError();
            trigger_error("SetupDiGetClassDevs Failed. Error Code: $error (" . $this->getLastErrorString($error) . ")");

            $hDevInfo = $this->setupapi->SetupDiGetClassDevsA(null, null, null, DeviceInformationGetClassFlags::DIGCF_PRESENT->value | DeviceInformationGetClassFlags::DIGCF_ALLCLASSES->value);
            $handleValue = $this->setupapi->cast('intptr_t', $hDevInfo);

            if ($handleValue == 0 || $handleValue == -1) {
                $error = $this->kernel32->GetLastError();
                throw new Exception("Failed to get devices. Error Code: $error (" . $this->getLastErrorString($error) . ")\n");
            }
        }

        $memberIndex = 0;

        $deviceInfoData = $this->setupapi->new("SP_DEVINFO_DATA");
        $deviceInfoData->cbSize = FFI::sizeof($deviceInfoData);

        while ($this->setupapi->SetupDiEnumDeviceInfo($hDevInfo, $memberIndex, FFI::addr($deviceInfoData))) {
            $memberIndex++;
            $enumerator = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_ENUMERATOR_NAME->value);

            if ($enumerator && stripos($enumerator, 'USB') === false) {
                continue;
            }

            $description = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_DEVICEDESC->value);
            $hardwareId = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_HARDWAREID->value);
            $friendlyName = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_FRIENDLYNAME->value);
            $manufacturer = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_MFG->value);
            $deviceClass = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_CLASS->value);
            $location = $this->getDeviceProperty($hDevInfo, $deviceInfoData, SetupAPIDeviceRegistryProperty::SPDRP_LOCATION_INFORMATION->value);

            $list[] = [
                'description' => $description,
                'friendlyName' => $friendlyName,
                'manufacturer' => $manufacturer,
                'hardwareId' => $hardwareId,
                'deviceClass' => $deviceClass,
                'location' => $location,
                'enumerator' => $enumerator,
                'vendor' => isset($hardwareId) ? USBVendor::parseFromHardwareId($hardwareId) : null
            ];
        }

        $lastError = $this->kernel32->GetLastError();
        if ($lastError != CommonConstraints::ERROR_NO_MORE_ITEMS->value) {
            throw new Exception("Error while enumeration. Error Code: $lastError (" . $this->getLastErrorString($lastError) . ")");
        }

        $this->setupapi->SetupDiDestroyDeviceInfoList($hDevInfo);

        return $list;
    }

    /**
     * Get device property from device info data.
     * 
     * @param CData $hDevInfo Device info set handle.
     * @param CData $deviceInfoData Device info data.
     * @param int $property Property to retrieve.
     * 
     * @return string|null
     */
    public function getDeviceProperty(CData $hDevInfo, CData $deviceInfoData, int $property): string|null
    {
        $buffer = $this->setupapi->new("char[2048]");
        $bufferSize = FFI::sizeof($buffer);
        /** @var CDataInterface $requiredSize */
        $requiredSize = $this->setupapi->new("DWORD");
        $dataType = $this->setupapi->new("DWORD");
        $bufferPointer = FFI::addr($buffer);
        $result = $this->setupapi->SetupDiGetDeviceRegistryPropertyA($hDevInfo, FFI::addr($deviceInfoData), $property, FFI::addr($dataType), $this->setupapi->cast("PBYTE", $bufferPointer), $bufferSize, FFI::addr($requiredSize));

        if ($result) {
            $propertyValue = FFI::string($buffer);
            return $propertyValue;
        }

        $error = $this->kernel32->GetLastError();
        if ($error == CommonConstraints::ERROR_INSUFFICIENT_BUFFER->value) {
            $newSize = $requiredSize->cdata;
            if ($newSize > 0 && $newSize < 8192) {
                $bigBuffer = $this->setupapi->new("char[$newSize]");
                $bigBufferPointer = FFI::addr($bigBuffer);
                $result = $this->setupapi->SetupDiGetDeviceRegistryPropertyA($hDevInfo, FFI::addr($deviceInfoData), $property, FFI::addr($dataType), $this->setupapi->cast("PBYTE", $bigBufferPointer), $newSize, FFI::addr($requiredSize));
                if ($result) {
                    return FFI::string($bigBuffer);
                }
            }
        }

        return null;
    }

    /**
     * Get last error string from error code.
     * 
     * @param int $errorCode Error code.
     * 
     * @return string
     */
    private function getLastErrorString(int $errorCode): string
    {
        $buffer = $this->setupapi->new("char[512]");
        $result = $this->kernel32->FormatMessageA(CommonConstraints::FORMAT_MESSAGE_FROM_SYSTEM, null, $errorCode, 0, FFI::addr($buffer), 512, null);

        if ($result > 0) {
            return trim(FFI::string($buffer));
        }

        return "Unknown Error (Error Code: $errorCode)";
    }

    /**
     * Get storage seek penalty type (HDD/SSD).
     * 
     * @param CData $h Handle to the storage device.
     * 
     * @return string|null
     */
    public function getStorageSeekPenaltyType(CData $h): ?string
    {
        $query = $this->kernel32->new("STORAGE_PROPERTY_QUERY");
        $query->PropertyId = 7;
        $query->QueryType = 0;
        $query->AdditionalParameters[0] = 0;

        $desc = $this->kernel32->new("DEVICE_SEEK_PENALTY_DESCRIPTOR");
        $bytes = $this->kernel32->new("DWORD");
        $success = $this->kernel32->DeviceIoControl($h, StorageControlStorageProperty::IOCTL_STORAGE_QUERY_PROPERTY->value, FFI::addr($query), FFI::sizeof($query), FFI::addr($desc), FFI::sizeof($desc), FFI::addr($bytes), null);
        if (!$success) {
            return null;
        }

        return $desc->IncursSeekPenalty ? "HDD" : "SSD";
    }

    /**
     * Open physical drive by index.
     * 
     * @param int $idx: Physical drive index (e.g., 0 for PhysicalDrive0).
     * 
     * @return mixed: Handle to the opened drive.
     */
    public function openDrive(int $idx): mixed
    {
        $path = sprintf("\\\\.\\PhysicalDrive%d", $idx);
        $handle = $this->kernel32->CreateFileA($path, 0, CommonConstraints::FILE_SHARE_READ->value | CommonConstraints::FILE_SHARE_WRITE->value, null, CommonConstraints::OPEN_EXISTING->value, 0, null);
        return $handle;
    }

    /**
     * Get storage device descriptor information.
     * 
     * @param CData $h Handle to the storage device.
     * 
     * @return array{
     *  vendor: string|null, 
     *  model: string|null, 
     *  revision: string|null, 
     *  serial: string|null, 
     *  busType: string|null
     * }
     */
    public function getStorageDeviceDescriptor(CData $h): array
    {
        $vendor = $model = $revision = $serial = $busType = null;

        $query = $this->kernel32->new("STORAGE_PROPERTY_QUERY");
        $query->PropertyId = 0;
        $query->QueryType = 0;
        $query->AdditionalParameters[0] = 0;

        $outSize = 1024;
        $buf = $this->kernel32->new("BYTE[$outSize]");
        /** @var CDataInterface $bytes */
        $bytes = $this->kernel32->new("DWORD");
        $success = $this->kernel32->DeviceIoControl($h, StorageControlStorageProperty::IOCTL_STORAGE_QUERY_PROPERTY->value, FFI::addr($query), FFI::sizeof($query), $buf, $outSize, FFI::addr($bytes), null);
        if ($success) {
            /** @var StorageDeviceDescriptorInterface $dev */
            $dev = $this->kernel32->cast("STORAGE_DEVICE_DESCRIPTOR", $buf);

            $descSize = (int) $dev->Size;
            $bytesRet = isset($bytes) && is_object($bytes) ? (int) $bytes->cdata : 0;
            $validLen = $bytesRet > 0 ? min($bytesRet, $descSize ?: $bytesRet) : ($descSize ?: 1024);

            $vendor = $this->ffiReadCStrAtOffset($this->kernel32, $buf, (int) $dev->VendorIdOffset, $validLen);
            $model = $this->ffiReadCStrAtOffset($this->kernel32, $buf, (int) $dev->ProductIdOffset, $validLen);
            $revision = $this->ffiReadCStrAtOffset($this->kernel32, $buf, (int) $dev->ProductRevisionOffset, $validLen);
            $serial = $this->ffiReadCStrAtOffset($this->kernel32, $buf, (int) $dev->SerialNumberOffset, $validLen);

            $busTypeVal = (int) $dev->BusType;
            $busType = StorageDevice::getBusType($busTypeVal);
        }

        return [
            'vendor' => $vendor,
            'model' => $model,
            'revision' => $revision,
            'serial' => $serial,
            'busType' => $busType,
        ];
    }

    /**
     * Send ATA PASS THROUGH command to the storage device and get data.
     * 
     * @param mixed $h: Handle to the storage device.
     * @param array{Features: mixed, SectorCount: mixed, SectorNumber: mixed, CylinderLow: mixed, CylinderHigh: mixed, DeviceHead: mixed, Command: mixed} $tf: Task file register values.
     * @param int $dataLen: Length of data to read.
     * @param int $timeoutSec: Timeout in seconds.
     * 
     * @return string|null
     * 
     * @throws Exception
     */
    public function getStorageAtaPassThroughIn(mixed $h, array $tf, int $dataLen, int $timeoutSec = 10): ?string
    {
        $pointer = $this->kernel32->new('ATA_PASS_THROUGH_EX');
        $aptSize = FFI::sizeof($pointer);
        $total = $aptSize + $dataLen;
        $buf = $this->kernel32->new("BYTE[$total]");
        $address = FFI::addr($buf[0]);
        FFI::memset($address, 0, $total);

        // Cast the address of the first byte to a struct pointer
        $pointer = FFI::addr($buf[0]);

        /** @var AtaPassThroughExInterface $apt */
        $apt = $this->kernel32->cast('ATA_PASS_THROUGH_EX *', $pointer);

        $apt->Length = $aptSize;
        $apt->AtaFlags = AtaFlags::ATA_FLAGS_DATA_IN->value | AtaFlags::ATA_FLAGS_DRDY_REQUIRED->value;
        $apt->PathId = 0;
        $apt->TargetId = 0;
        $apt->Lun = 0;
        $apt->ReservedAsUchar = 0;
        $apt->DataTransferLength = $dataLen;
        $apt->TimeOutValue = $timeoutSec;
        $apt->ReservedAsUlong = 0;
        $apt->DataBufferOffset = $aptSize;

        $pointer = FFI::addr($apt->PreviousTaskFile);
        FFI::memset($pointer, 0, FFI::sizeof($apt->PreviousTaskFile));
        $pointer = FFI::addr($apt->CurrentTaskFile);
        FFI::memset($pointer, 0, FFI::sizeof($apt->CurrentTaskFile));

        $apt->CurrentTaskFile->Features = $tf['Features'] ?? 0;
        $apt->CurrentTaskFile->SectorCount = $tf['SectorCount'] ?? 0;
        $apt->CurrentTaskFile->SectorNumber = $tf['SectorNumber'] ?? 0; // LBA Low
        $apt->CurrentTaskFile->CylinderLow = $tf['CylinderLow'] ?? 0; // LBA Mid
        $apt->CurrentTaskFile->CylinderHigh = $tf['CylinderHigh'] ?? 0; // LBA High
        $apt->CurrentTaskFile->DeviceHead = $tf['DeviceHead'] ?? 0xA0; // master, CHS=0/LBA=bit6
        $apt->CurrentTaskFile->Command = $tf['Command'] ?? 0;

        $out = $this->kernel32->new("BYTE[$total]");
        $bytes = $this->kernel32->new('DWORD');

        $ok = $this->kernel32->DeviceIoControl($this->kernel32->cast("void*", $h), IOControl::IOCTL_ATA_PASS_THROUGH_EX->value, FFI::addr($buf[0]), $total, FFI::addr($out[0]), $total, FFI::addr($bytes), null);
        if (!$ok) {
            $err = $this->getLastErrorMessage();
            throw new Exception("ATA_PASS_THROUGH_EX Failed, Error Code : {$err}");
        }

        $pointer = FFI::addr($out[0]);
        $base = $this->kernel32->cast("char*", $pointer);
        $pointer = $base + $aptSize;
        return FFI::string($pointer, $dataLen);
    }

    /**
     * Get SMART data from the storage device.
     * 
     * @param int $idx Physical drive index (e.g., 0 for PhysicalDrive0).
     * @param bool $byEx Whether to use ATA_PASS_THROUGH_EX or SMART_RCV_DRIVE_DATA method to retrieve SMART data for ATA/SATA drives. If false, will use SMART_RCV_DRIVE_DATA which is more broadly supported on SATA drives. If true, will use ATA_PASS_THROUGH_EX which may provide more detailed data on some drives but is less widely supported.
     * @return array{bus_type: int, model: string, protocol: int, smart: array|null}|null
     * @throws Exception
     */
    public function getStorageSmartData(int $idx = 0, bool $byEx = false): array|null
    {
        $path = sprintf("\\\\.\\PhysicalDrive%d", $idx);
        $widePath = self::stringToWchar($this->user32, $path);
        $h = $this->kernel32->CreateFileW($widePath, 0x80000000 | 0x40000000, 0x00000001 | 0x00000002, null, 3, 0, null);

        // Validate handle before use (INVALID_HANDLE_VALUE = -1)
        $handleVal = $this->kernel32->new('int64_t');
        $from = FFI::addr($handleVal);
        $to = FFI::addr($h);
        FFI::memcpy($from, $to, 8);
        if ($handleVal->cdata === -1) {
            $err = $this->getLastErrorMessage();
            throw new Exception("CreateFileW failed on PhysicalDrive{$idx}, Error Code: {$err}");
        }

        try {
            $busType = $this->resolveStorageBusType($h);
            $protocol = $this->resolveStorageProtocol($h);
            $model = $this->resolveStorageModel($h);

            if ($busType === 17) {
                //return $this->getStorageSmartDataNvme($h);
            }

            if (in_array($busType, [2, 3, 11], true)) {
                if ($byEx) {
                    $data = $this->getStorageAtaPassThroughIn($h, [
                        'Command' => AtaFlags::ATA_CMD_SMART->value,
                        'Features' => SmartFlags::SMART_FEATURE_CODE->value,
                        'SectorCount' => 1,
                        'SectorNumber' => 0,
                        'CylinderLow' => SmartFlags::SMART_LBA_MID->value,
                        'CylinderHigh' => SmartFlags::SMART_LBA_HIGH->value,
                        'DeviceHead' => 0xA0,
                    ], 512);

                    if ($data === null || strlen($data) !== 512) {
                        return null;
                    }

                    $headerSize = 16;
                    $pointer = FFI::addr($outBuf[0]);
                    $base = $this->kernel32->cast('char*', $pointer);
                    $pointer = $base + $headerSize;
                    $data = FFI::string($pointer, 512);
                    return $this->parseSmartAttributes($data);
                }

                return ['smart' => $this->getStorageSmartDataAta($h), 'model' => $model, 'bus_type' => $busType, 'protocol' => $protocol];
            }

            throw new Exception("Unsupported BusType: $busType");
        } finally {
            $this->kernel32->CloseHandle($h);
        }
    }

    /**
     * Create a GDI+ context and return the token.
     * 
     * @return CData
     */
    public function createGDIPlusContext(): mixed
    {
        $gdipToken = $this->gdiplus->new("ULONG_PTR[1]");
        $startupInput = $this->gdiplus->new("unsigned long[1]");
        $this->gdiplus->GdiplusStartup($gdipToken, FFI::addr($startupInput[0]), NULL);

        return $gdipToken;
    }

    /**
     * Get storage information for the specified drive index.
     * 
     * @param int $driveIndex: array|null
     * 
     * @return array{
     *  descriptor: array{
     *      busType: string|null, 
     *      model: string|null, 
     *      revision: string|null, 
     *      serial: string|null, 
     *      vendor: string|null
     *  }, 
     *  seek_penalty_type: string|null
     * }|null
     */
    public function getStorageInformation(int $driveIndex): array|null
    {
        $handle = $this->openDrive($driveIndex);
        if ($handle[0] === -1) {
            return null;
        }

        $type = $this->getStorageSeekPenaltyType($handle);
        $descriptor = $this->getStorageDeviceDescriptor($handle);

        return [
            'seek_penalty_type' => $type,
            'descriptor' => $descriptor,
        ];
    }

    /**
     * Reads SMART data via IOCTL_SMART_RCV_DRIVE_DATA (0x0007C088).
     * More broadly supported than IOCTL_ATA_PASS_THROUGH_EX on SATA drives.
     */
    private function getStorageSmartDataAta(mixed $h, bool $fixedHeaderSize = true): array|null
    {
        $in = $this->kernel32->new('SENDCMDINPARAMS');
        $out = $this->kernel32->new('SENDCMDOUTPARAMS');
        $inSize = FFI::sizeof($in);
        $outSize = FFI::sizeof($out) - 1 + 512;

        $in = $this->kernel32->new('SENDCMDINPARAMS');
        $pointer = FFI::addr($in);
        FFI::memset($pointer, 0, $inSize);

        $in->cBufferSize = 512;
        $in->irDriveRegs->bFeaturesReg = SmartFlags::SMART_FEATURE_CODE->value;
        $in->irDriveRegs->bSectorCountReg = 1;
        $in->irDriveRegs->bSectorNumberReg = 1;
        $in->irDriveRegs->bCylLowReg = SmartFlags::SMART_LBA_MID->value;
        $in->irDriveRegs->bCylHighReg = SmartFlags::SMART_LBA_HIGH->value;
        $in->irDriveRegs->bDriveHeadReg = 0xA0;
        $in->irDriveRegs->bCommandReg = AtaFlags::ATA_CMD_SMART->value;
        $in->bDriveNumber = 0;

        $outBuf = $this->kernel32->new("BYTE[$outSize]");
        $pointer = FFI::addr($outBuf[0]);
        FFI::memset($pointer, 0, $outSize);
        $bytes = $this->kernel32->new('DWORD');

        // IOCTL_SMART_RCV_DRIVE_DATA = 0x0007C088
        $ok = $this->kernel32->DeviceIoControl($h, 0x0007C088, FFI::addr($in), $inSize, FFI::addr($outBuf[0]), $outSize, FFI::addr($bytes), null);

        if (!$ok) {
            $err = $this->getLastErrorMessage();
            throw new Exception("IOCTL_SMART_RCV_DRIVE_DATA failed, Error Code: $err");
        }

        $pointer = FFI::addr($outBuf[0]);
        $out = $this->kernel32->cast('SENDCMDOUTPARAMS*', $pointer);
        if ($out->DriverStatus->bDriverError !== 0) {
            throw new Exception("SMART driver error: " . $out->DriverStatus->bDriverError);
        }

        $out = $this->kernel32->new('SENDCMDOUTPARAMS');
        $headerSize = $fixedHeaderSize ? 16 : FFI::sizeof($out) - 1;
        $pointer = FFI::addr($outBuf[0]);
        $base = $this->kernel32->cast('char*', $pointer);
        $pointer = $base + $headerSize;
        $data = FFI::string($pointer, 512);

        return $this->parseSmartAttributes($data);
    }

    /**
     * Read the device model string from IDENTIFY DEVICE response.
     *
     * @param mixed $h
     *
     * @return string
     */
    private function resolveStorageModel(mixed $h): string
    {
        $in = $this->kernel32->new('SENDCMDINPARAMS');
        $out = $this->kernel32->new('SENDCMDOUTPARAMS');
        $inSize = FFI::sizeof($in);
        $outSize = FFI::sizeof($out) - 1 + 512;

        $in = $this->kernel32->new('SENDCMDINPARAMS');
        $pointer = FFI::addr($in);
        FFI::memset($pointer, 0, $inSize);

        $in->cBufferSize = 512;
        $in->irDriveRegs->bSectorCountReg = 1;
        $in->irDriveRegs->bDriveHeadReg = AtaFlags::ATA_CMD_PACKET->value;
        $in->irDriveRegs->bCommandReg = AtaFlags::ATA_CMD_IDENTIFY_DEVICE->value;

        $outBuf = $this->kernel32->new("BYTE[$outSize]");
        $pointer = FFI::addr($outBuf[0]);
        FFI::memset($pointer, 0, $outSize);
        $bytes = $this->kernel32->new('DWORD');

        // IOCTL_SMART_RCV_DRIVE_DATA = 0x0007C088
        $ok = $this->kernel32->DeviceIoControl($h, 0x0007C088, FFI::addr($in), $inSize, FFI::addr($outBuf[0]), $outSize, FFI::addr($bytes), null);

        if (!$ok) {
            return 'unknown';
        }

        // IDENTIFY DEVICE: model string at word 27-46 = byte offset 54, length 40
        $pointer = FFI::addr($outBuf[0]);
        $base = $this->kernel32->cast('char*', $pointer);
        $pointer = $base + 16 + 54;
        $raw = FFI::string($pointer, 40);

        // Byte-swap each word pair (ATA stores model in big-endian word order)
        $model = '';
        for ($i = 0; $i < 40; $i += 2) {
            $model .= $raw[$i + 1] . $raw[$i];
        }

        return trim($model);
    }

    /**
     * Parses 512-byte SMART attribute table into an associative array.
     * 
     * @return array{
     *  flags: int, 
     *  id: int, 
     *  name: string|null, 
     *  raw: int, 
     *  value: int, 
     *  worst: int
     * }[]
     */
    private function parseSmartAttributes(string $data): array
    {
        $mapNames = [
            1 => 'Raw Read Error Rate',
            3 => 'Spin-Up Time',
            4 => 'Start/Stop Count',
            5 => 'Reallocated Sectors Count',
            7 => 'Seek Error Rate',
            9 => 'Power-On Hours',
            10 => 'Spin Retry Count',
            11 => 'Recalibration Retries',
            12 => 'Power Cycle Count',
            13 => 'Soft Read Error Rate',
            100 => 'Erase Fail Count (Chip)',
            117 => 'Wear Leveling Count',
            171 => 'Program Fail Count (SSD)',
            172 => 'Erase Fail Count (SSD)',
            173 => 'Wear Leveling Count',
            174 => 'Unexpected Power Loss',
            175 => 'Program Fail Count (Chip)',
            176 => 'Erase Fail Count (Chip)',
            177 => 'Wear Leveling Count',
            178 => 'Used Reserved Block Count (Chip)',
            179 => 'Used Reserved Block Count (Total)',
            180 => 'Unused Reserved Block Count (Total)',
            181 => 'Program Fail Count (Total)',
            182 => 'Erase Fail Count',
            183 => 'Runtime Bad Block',
            184 => 'End-to-End Error',
            187 => 'Reported Uncorrectable Errors',
            188 => 'Command Timeout',
            190 => 'Airflow Temperature',
            191 => 'G-sense Error Rate',
            192 => 'Power-off Retract Count',
            193 => 'Load Cycle Count',
            194 => 'Temperature',
            195 => 'Hardware ECC Recovered',
            196 => 'Reallocation Event Count',
            197 => 'Current Pending Sector',
            198 => 'Uncorrectable Sector Count',
            199 => 'UDMA CRC Error Count',
            200 => 'Multi-Zone Error Rate',
            201 => 'Soft Read Error Rate',
            204 => 'Soft ECC Correction',
            230 => 'Drive Life Protection Status',
            231 => 'SSD Life Left',
            232 => 'Endurance Remaining',
            233 => 'Media Wearout Indicator',
            234 => 'Average Erase Count',
            235 => 'Good Block Count',
            240 => 'Head Flying Hours',
            241 => 'Total LBAs Written',
            242 => 'Total LBAs Read',
            243 => 'Total LBAs Written Expanded',
            244 => 'Total LBAs Read Expanded',
            250 => 'Read Error Retry Rate',
            251 => 'Minimum Spares Remaining',
            252 => 'Newly Added Bad Flash Block',
            254 => 'Free Fall Protection',
        ];

        $attrs = [];
        for ($i = 0; $i < 30; $i++) {
            $off = 2 + $i * 12;
            $id = ord($data[$off]);
            if ($id === 0x00 || $id === 0xFF) {
                continue;
            }

            $flags = ord($data[$off + 1]) | (ord($data[$off + 2]) << 8);
            $val = ord($data[$off + 3]);
            $worst = ord($data[$off + 4]);
            $raw = 0;
            for ($b = 0; $b < 6; $b++) {
                $raw |= (ord($data[$off + 5 + $b]) << (8 * $b));
            }

            $attrs[$id] = [
                'name' => $mapNames[$id] ?? null,
                'id' => $id,
                'value' => $val,
                'worst' => $worst,
                'raw' => $raw,
                'flags' => $flags,
            ];
        }

        return $attrs;
    }

    /**
     * Get current process ID.
     * 
     * @return int
     */
    public function getCurrentProcessId(): int
    {
        return $this->kernel32->GetCurrentProcessId();
    }

    public function getProcessFromPid(int $pid)
    {
        $gHwnd = $this->kernel32->new("HWND");

        $EnumWindowsProcMy = function (mixed $hwnd, int $lParam) use ($gHwnd, $pid) {
            $lpdwProcessId = $this->user32->new("DWORD");
            $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($lpdwProcessId));

            //var_dump(['lpdwProcessId' => $lpdwProcessId->cdata, 'lParam' => $lParam, 'hwnd' => $hwnd, 'pid' => $pid]);
            if ($lpdwProcessId->cdata == $lParam) {
                $gHwnd = $hwnd;
                var_dump('aa');

                $pointer = 0;
                return $this->user32->cast("bool*", $pointer);
            }

            $pointer = 1;
            return $this->user32->cast("bool*", $pointer);
        };

        $this->user32->EnumWindows($EnumWindowsProcMy, $pid);
    }

    /**
     * Get current thread ID.
     * 
     * @return int
     */
    public function getCurrentThreadId(): int
    {
        return $this->kernel32->GetCurrentThreadId();
    }

    /**
     * Get tick count since system start in milliseconds.
     * 
     * @return int
     */
    public function getTickCount(): int
    {
        return $this->kernel32->GetTickCount(null);
    }

    /**
     * Get system uptime in seconds.
     * 
     * @return int
     */
    public function getSystemUptime(): int
    {
        return (int) ($this->kernel32->GetTickCount(null) / 1000);
    }

    /**
     * Get an environment variable. Returns the value of the environment variable on success, null if the variable does not exist.
     * 
     * @param string $name
     * @return string|null
     */
    public function getEnvironmentVariable(string $name): ?string
    {
        $buffer = $this->kernel32->new("char[32767]");
        $result = $this->kernel32->GetEnvironmentVariableA($name, $buffer, 32767);

        if ($result === 0) {
            return null;
        }

        return FFI::string($buffer, $result);
    }

    /**
     * Set an environment variable. Returns true on success, false on failure.
     * 
     * @param string $name
     * @param string|null $value
     * @return bool
     */
    public function setEnvironmentVariable(string $name, ?string $value): bool
    {
        return (bool) $this->kernel32->SetEnvironmentVariableA($name, $value);
    }

    /**
     * Get the path of the Windows directory. Returns the path on success, false on failure.
     * Returns the fully-qualified path to the Windows directory (e.g. C:\Windows).
     * 
     * @return string|false Windows directory path, or null on failure.
     */
    public function getWindowsDirectory(): string|false
    {
        $buffer = $this->kernel32->new("char[260]");
        $result = $this->kernel32->GetWindowsDirectoryA($buffer, 260);

        if ($result === 0) {
            return false;
        }

        return FFI::string($buffer, $result);
    }

    /**
     * Returns the path to the System32 directory (e.g. C:\Windows\System32).
     * Get the path of the system directory. Returns the path on success, false on failure.
     * 
     * @return string|false System directory path, or null on failure.
     */
    public function getSystemDirectory(): string|false
    {
        $buffer = $this->kernel32->new("char[260]");
        $result = $this->kernel32->GetSystemDirectoryA($buffer, 260);

        if ($result === 0) {
            return false;
        }

        return FFI::string($buffer, $result);
    }

    /**
     * Get the path of the temporary directory. Returns the path on success, false on failure.
     * 
     * Returns the path to the system temporary directory (e.g. C:\Users\user\AppData\Local\Temp\).
     *
     * The trailing backslash is included as Windows always appends it.
     *
     * @return string|null Temp directory path, or null on failure.
     */
    public function getTempPath(): string|null
    {
        $buffer = $this->kernel32->new("char[260]");
        $result = $this->kernel32->GetTempPathA(260, $buffer);

        if ($result === 0) {
            return null;
        }

        return FFI::string($buffer, $result);
    }

    /**
     * Get the current directory. Returns the current directory on success, false on failure.
     * 
     * @return string|false
     */
    public function getCurrentDirectory(): string|false
    {
        $buffer = $this->kernel32->new("char[260]");
        $result = $this->kernel32->GetCurrentDirectoryA(260, $buffer);

        if ($result === 0) {
            return false;
        }

        return FFI::string($buffer, $result);
    }

    /**
     * Set the current directory. Returns true on success, false on failure.
     * 
     * @param string $path
     * @return bool
     */
    public function setCurrentDirectory(string $path): bool
    {
        return (bool) $this->kernel32->SetCurrentDirectoryA($path);
    }

    /**
     * Set the console title. Returns true on success, false on failure.
     * 
     * @param string $title
     * @return bool
     */
    public function setConsoleTitle(string $title): bool
    {
        return (bool) $this->kernel32->SetConsoleTitleA($title);
    }

    /**
     * Get the number of processors in the system.
     * 
     * @return int
     */
    public function getProcessorCount(): int
    {
        $sysInfo = $this->kernel32->new("SYSTEM_INFO");
        $this->kernel32->GetSystemInfo(FFI::addr($sysInfo));

        return $sysInfo->dwNumberOfProcessors;
    }

    /**
     * Get system information. Returns an array with system information on success.
     * 
     * @return array{
     *  allocationGranularity: mixed, 
     *  numberOfProcessors: mixed, 
     *  pageSize: mixed, 
     *  processorArchitecture: mixed, 
     *  processorLevel: mixed, 
     *  processorRevision: mixed, 
     *  processorType: mixed
     * }
     */
    public function getSystemInformation(): array
    {
        /** @var SystemInfoInterface $sysInfo */
        $sysInfo = $this->kernel32->new("SYSTEM_INFO");
        $this->kernel32->GetSystemInfo(FFI::addr($sysInfo));

        return [
            'processorArchitecture' => $sysInfo->wProcessorArchitecture,
            'numberOfProcessors' => $sysInfo->dwNumberOfProcessors,
            'processorType' => $sysInfo->dwProcessorType,
            'pageSize' => $sysInfo->dwPageSize,
            'allocationGranularity' => $sysInfo->dwAllocationGranularity,
            'processorLevel' => $sysInfo->wProcessorLevel,
            'processorRevision' => $sysInfo->wProcessorRevision,
        ];
    }

    /**
     * Get global memory status. Returns an array with memory information on success, false on failure.
     * 
     * @return array{
     *  availablePageFile: mixed, 
     *  availablePhysical: mixed, 
     *  availableVirtual: mixed, 
     *  memoryLoad: mixed, 
     *  totalPageFile: mixed, 
     *  totalPhysical: mixed, 
     *  totalVirtual: mixed
     * }|false
     */
    public function getGlobalMemoryStatus(): array|false
    {
        /** @var MemoryStatusExInterface $memStatus */
        $memStatus = $this->kernel32->new("MEMORYSTATUSEX");
        $memStatus->dwLength = FFI::sizeof($memStatus);

        if (!$this->kernel32->GlobalMemoryStatusEx(FFI::addr($memStatus))) {
            return false;
        }

        return [
            'memoryLoad' => $memStatus->dwMemoryLoad,
            'totalPhysical' => $memStatus->ullTotalPhys,
            'availablePhysical' => $memStatus->ullAvailPhys,
            'totalPageFile' => $memStatus->ullTotalPageFile,
            'availablePageFile' => $memStatus->ullAvailPageFile,
            'totalVirtual' => $memStatus->ullTotalVirtual,
            'availableVirtual' => $memStatus->ullAvailVirtual,
        ];
    }

    /**
     * Sleep for the specified number of milliseconds.
     * 
     * @param int $milliseconds
     * @return void
     */
    public function sleep(int $milliseconds): void
    {
        $this->kernel32->Sleep($milliseconds);
    }

    /**
     * Get text from the clipboard. Returns the text on success, false on failure.
     * 
     * @return string|false
     */
    public function getClipboardText(): string|false
    {
        if (!$this->user32->OpenClipboard(null)) {
            return false;
        }

        $hData = $this->user32->GetClipboardData(self::CF_TEXT);
        if ($hData === null || FFI::isNull($hData)) {
            $this->user32->CloseClipboard();
            return false;
        }

        $pText = $this->kernel32->GlobalLock($hData);
        if ($pText === null || FFI::isNull($pText)) {
            $this->user32->CloseClipboard();
            return false;
        }

        $pointer = $this->kernel32->cast("char*", $pText);
        $text = FFI::string($pointer);

        $this->kernel32->GlobalUnlock($hData);
        $this->user32->CloseClipboard();

        return $text;
    }

    /**
     * Set the specified text to the clipboard. Returns true on success, false on failure.
     * 
     * @param string $text
     * @return bool
     */
    public function setClipboardText(string $text): bool
    {
        if (!$this->user32->OpenClipboard(null)) {
            return false;
        }

        $this->user32->EmptyClipboard();

        $len = strlen($text) + 1;
        $hMem = $this->kernel32->GlobalAlloc(self::GMEM_MOVEABLE, $len);
        if ($hMem === null || FFI::isNull($hMem)) {
            $this->user32->CloseClipboard();
            return false;
        }

        $pMem = $this->kernel32->GlobalLock($hMem);
        if ($pMem === null || FFI::isNull($pMem)) {
            $this->kernel32->GlobalFree($hMem);
            $this->user32->CloseClipboard();
            return false;
        }

        FFI::memcpy($pMem, $text, strlen($text));
        $charPtr = $this->kernel32->cast("char*", $pMem);
        $charPtr[strlen($text)] = "\0";

        $this->kernel32->GlobalUnlock($hMem);
        $this->user32->SetClipboardData(self::CF_TEXT, $hMem);
        $this->user32->CloseClipboard();

        return true;
    }

    /**
     * Empties the clipboard and frees handles to data in the clipboard.
     * 
     * @return bool
     */
    public function emptyClipboard(): bool
    {
        if (!$this->user32->OpenClipboard(null)) {
            return false;
        }

        $result = (bool) $this->user32->EmptyClipboard();
        $this->user32->CloseClipboard();

        return $result;
    }

    /**
     * Expand environment variables in the input string and return the expanded string.
     * If the function fails, return false.
     * 
     * @param string $source
     * @return string|false
     */
    public function expandEnvironmentStrings(string $source): string|false
    {
        $buffer = $this->kernel32->new("char[32767]");
        $result = $this->kernel32->ExpandEnvironmentStringsA($source, $buffer, 32767);

        if ($result === 0) {
            return false;
        }

        return FFI::string($buffer, $result - 1);
    }

    /**
     * Get the drive type for the specified drive path.
     * 
     * @param string $drivePath
     * @return int
     */
    public function getDriveType(string $drivePath): int
    {
        return $this->kernel32->GetDriveTypeA($drivePath);
    }

    /**
     * Get the drive type name for the specified drive path.
     * 
     * @param string $drivePath
     * @return string
     */
    public function getDriveTypeName(string $drivePath): string
    {
        $type = $this->getDriveType($drivePath);
        $types = [
            0 => 'UNKNOWN',
            1 => 'NO_ROOT_DIR',
            2 => 'REMOVABLE',
            3 => 'FIXED',
            4 => 'REMOTE',
            5 => 'CDROM',
            6 => 'RAMDISK',
        ];

        return $types[$type] ?? 'UNKNOWN';
    }

    /**
     * Get list of logical drives with their types.
     * 
     * @return array{path: mixed, type: string}[]
     */
    public function getLogicalDrivesWithType(): array
    {
        $drives = $this->getLogicalDrives();
        $result = [];

        foreach ($drives as $drive) {
            $result[] = [
                'path' => $drive,
                'type' => $this->getDriveTypeName($drive),
            ];
        }

        return $result;
    }

    /**
     * Get the current system time as an associative array with keys: year, month, dayOfWeek, day, hour, minute, second, milliseconds.
     * 
     * @return array
     */
    public function getLocalTime(): array
    {
        /** @var SystemTimeInterface $systemTime */
        $systemTime = $this->kernel32->new("SYSTEMTIME");
        $this->kernel32->GetLocalTime(FFI::addr($systemTime));

        return [
            'year' => $systemTime->wYear,
            'month' => $systemTime->wMonth,
            'dayOfWeek' => $systemTime->wDayOfWeek,
            'day' => $systemTime->wDay,
            'hour' => $systemTime->wHour,
            'minute' => $systemTime->wMinute,
            'second' => $systemTime->wSecond,
            'milliseconds' => $systemTime->wMilliseconds,
        ];
    }

    /**
     * Get the current system time in UTC and return it as an associative array.
     * 
     * @return array
     */
    public function getSystemTime(): array
    {
        /** @var SystemTimeInterface $systemTime */
        $systemTime = $this->kernel32->new("SYSTEMTIME");
        $this->kernel32->GetSystemTime(FFI::addr($systemTime));

        return [
            'year' => $systemTime->wYear,
            'month' => $systemTime->wMonth,
            'dayOfWeek' => $systemTime->wDayOfWeek,
            'day' => $systemTime->wDay,
            'hour' => $systemTime->wHour,
            'minute' => $systemTime->wMinute,
            'second' => $systemTime->wSecond,
            'milliseconds' => $systemTime->wMilliseconds,
        ];
    }

    /**
     * Get the color of the pixel at the specified coordinates and return it as a COLORREF value.
     * COLORREF format: 0x00BBGGRR (red in lowest byte, then green, then blue).
     * 
     * @param int $x
     * @param int $y
     * @return FFI\CData
     */
    public function getPixelColor(int $x, int $y): FFI\CData
    {
        $hdc = $this->user32->GetDC(null);
        $color = $this->gdi32->GetPixel($hdc, $x, $y);
        $this->user32->ReleaseDC(null, $hdc);

        return $color;
    }

    /**
     * Get the RGB components of the pixel color at the specified coordinates.
     * 
     * @param int $x
     * @param int $y
     * @return array
     */
    public function getPixelColorRGB(int $x, int $y): array
    {
        $color = $this->getPixelColor($x, $y);

        return [
            'r' => $color & 0xFF,
            'g' => ($color >> 8) & 0xFF,
            'b' => ($color >> 16) & 0xFF,
        ];
    }

    /**
     * Load the specified DLL and return the module handle, or null on failure.
     * 
     * @param string $dllPath
     * @return mixed
     */
    public function loadLibrary(string $dllPath): mixed
    {
        return $this->kernel32->LoadLibraryA($dllPath);
    }

    /**
     * Free the loaded library and return true on success.
     * 
     * @param mixed $hModule
     * @return bool
     */
    public function freeLibrary(mixed $hModule): bool
    {
        return (bool) $this->kernel32->FreeLibrary($hModule);
    }

    /**
     * Set the specified window as topmost or not and return true on success.
     * 
     * @param CData $hwnd
     * @param bool $topmost
     * @return bool
     */
    public function setWindowTopmost(CData $hwnd, bool $topmost = true): bool
    {
        $hWndInsertAfter = $topmost ? -1 : -2;

        return (bool) $this->user32->SetWindowPos($hwnd, $this->user32->cast('HWND', $hWndInsertAfter), 0, 0, 0, 0, SetWindowPos::SWP_NOMOVE->value | SetWindowPos::SWP_NOSIZE->value);
    }

    /**
     * Move the specified window to the given coordinates and return true on success.
     * 
     * @param CData $hwnd
     * @return bool
     */
    public function centerWindow(CData $hwnd): bool
    {
        $rect = $this->getWindowDimensions($hwnd);
        if ($rect === false) {
            return false;
        }

        $screen = $this->getScreenDimensions();
        $x = ($screen['width'] - $rect['width']) / 2;
        $y = ($screen['height'] - $rect['height']) / 2;

        return $this->moveWindow($hwnd, (int) $x, (int) $y);
    }

    /**
     * Get the double-click time in milliseconds.
     * 
     * @return int
     */
    public function getDoubleClickTime(): int
    {
        return $this->user32->GetDoubleClickTime();
    }

    /**
     * Set the double-click time to the specified interval (in milliseconds) and return true on success.
     * 
     * @param int $interval
     * @return bool
     */
    public function setDoubleClickTime(int $interval): bool
    {
        return (bool) $this->user32->SetDoubleClickTime($interval);
    }

    /**
     * Set the input focus to the specified window and return true on success.
     * 
     * @param CData|null $hwnd
     * @return bool
     */
    public function setFocusToWindow(?CData $hwnd): bool
    {
        if ($hwnd === null) {
            return false;
        }
        return $this->user32->SetFocus($hwnd) !== null;
    }

    /**
     * Set the cursor position to the specified coordinates and return true on success.
     * 
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function setCursorPosition(int $x, int $y): bool
    {
        return (bool) $this->user32->SetCursorPos($x, $y);
    }

    /**
     * Show or hide the cursor and return the new display count.
     * 
     * @param bool $show
     * @return int
     */
    public function showCursor(bool $show): int
    {
        return $this->user32->ShowCursor($show ? 1 : 0);
    }

    /**
     * Get the process ID associated with the specified window handle.
     * 
     * @param CData $hwnd
     * @return int
     */
    public function getWindowProcessId(CData $hwnd): int
    {
        /** @var CDataInterface $pid */
        $pid = $this->user32->new("DWORD");
        $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pid));
        return $pid->cdata;
    }

    /**
     * Check if the specified window is visible.
     * 
     * @param CData $hwnd
     * @return bool
     */
    public function isWindowVisible(CData $hwnd): bool
    {
        return (bool) $this->user32->IsWindowVisible($hwnd);
    }

    /**
     * Check if the specified window is enabled.
     * 
     * @param CData $hwnd
     * @return bool
     */
    public function isWindowEnabled(CData $hwnd): bool
    {
        return (bool) $this->user32->IsWindowEnabled($hwnd);
    }

    /**
     * Enable or disable the specified window.
     * 
     * @param CData $hwnd
     * @param bool $enable
     * @return bool
     */
    public function enableWindow(CData $hwnd, bool $enable): bool
    {
        return (bool) $this->user32->EnableWindow($hwnd, $enable ? 1 : 0);
    }

    /**
     * Restore the console mode.
     * 
     * @param mixed $hStdin
     * @param int $originalMode
     * @return void
     */
    public function restoreMode(mixed $hStdin, int $originalMode)
    {
        $this->kernel32->SetConsoleMode($hStdin, $originalMode);
    }

    /**
     * Enable raw mode for the console.
     * 
     * @return array
     */
    public function enableRawMode(): array
    {
        $hStdin = $this->kernel32->GetStdHandle(self::STD_INPUT_HANDLE);
        $hStdout = $this->kernel32->GetStdHandle(self::STD_OUTPUT_HANDLE);

        /** @var CDataInterface $modePtr */
        $modePtr = $this->kernel32->new('unsigned int');
        $this->kernel32->GetConsoleMode($hStdin, FFI::addr($modePtr));
        $originalMode = $modePtr->cdata;

        $this->kernel32->SetConsoleMode($hStdin, ($originalMode | self::ENABLE_MOUSE_INPUT | self::ENABLE_EXTENDED_FLAGS) & ~self::ENABLE_QUICK_EDIT_MODE & ~self::ENABLE_VIRTUAL_TERMINAL_INPUT);

        return [$this->kernel32, $hStdin, $hStdout, $originalMode];
    }

    /**
     * Restore console mode from original.
     * 
     * @param int $originalMode
     * @return void
     */
    public function restoreConsoleMode(int $originalMode): void
    {
        $hStdin = $this->kernel32->GetStdHandle(self::STD_INPUT_HANDLE);
        $this->kernel32->SetConsoleMode($hStdin, $originalMode);
    }

    /**
     * Query the current cursor row in the console.
     * 
     * @param mixed $hStdout
     * @return int
     */
    public function queryCursorRow(mixed $hStdout): int
    {
        $csbi = $this->kernel32->new('CONSOLE_SCREEN_BUFFER_INFO');
        return $this->kernel32->GetConsoleScreenBufferInfo($hStdout, FFI::addr($csbi)) ? (int) $csbi->dwCursorPosition->Y + 1 : 1;
    }

    /**
     * Read a raw byte from the console input.
     * 
     * @param mixed $hStdin
     * @return string|null
     */
    public function readRawByte(mixed $hStdin): ?string
    {
        $buf = $this->kernel32->new('unsigned char[1]');
        /** @var CDataInterface $numRead */
        $numRead = $this->kernel32->new('unsigned int');

        $ok = $this->kernel32->ReadFile($hStdin, $buf, 1, FFI::addr($numRead), null);

        if (!$ok || !$numRead->cdata) {
            return null;
        }

        return chr((int) $buf[0]->cdata);
    }

    /**
     * Set raw console mode.
     * 
     * @return int
     */
    public function setRawConsoleMode(): int
    {
        $hStdin = $this->kernel32->GetStdHandle(self::STD_INPUT_HANDLE);

        /** @var CDataInterface $modePtr */
        $modePtr = $this->kernel32->new('unsigned int');
        $this->kernel32->GetConsoleMode($hStdin, FFI::addr($modePtr));
        $original = $modePtr->cdata;

        $this->kernel32->SetConsoleMode($hStdin, $original & ~self::ENABLE_LINE_INPUT & ~self::ENABLE_ECHO_INPUT & ~self::ENABLE_PROCESSED_INPUT);

        return $original;
    }

    /**
     * Get the module handle for the current process.
     *
     * @return CData The HINSTANCE module handle
     */
    public function getModuleHandle(): CData
    {
        return $this->kernel32->GetModuleHandleA(null);
    }

    /**
     * Create an empty popup menu (used for sub-menus and context menus).
     *
     * @return CData The HMENU popup menu handle
     */
    public function createPopupMenu(): CData
    {
        return $this->user32->CreatePopupMenu();
    }

    /**
     * Append a string menu item to a menu handle.
     *
     * @param CData $hMenu The menu handle to append to
     * @param int $commandId The unique command identifier for this item
     * @param string $label The display text of the menu item
     * @return bool True on success
     */
    public function appendMenuItem(CData $hMenu, int $commandId, string $label): bool
    {
        // MF_STRING = 0x0000
        $this->user32->AppendMenuA($hMenu, 0x0000, $commandId, $label);

        return true;
    }

    /**
     * Append a separator line to a menu handle.
     *
     * @param CData $hMenu The menu handle to append to
     * @return bool True on success
     */
    public function appendMenuSeparator(CData $hMenu): bool
    {
        // MF_SEPARATOR = 0x0800
        $this->user32->AppendMenuA($hMenu, 0x0800, 0, null);

        return true;
    }

    /**
     * Append a sub-menu (popup) to a parent menu handle.
     *
     * @param CData $parentMenu The parent menu handle
     * @param CData $subMenu The popup sub-menu handle
     * @param string $label The display text of the sub-menu entry
     * @return bool True on success
     */
    public function appendSubMenu(CData $parentMenu, CData $subMenu, string $label): bool
    {
        // MF_POPUP = 0x0010
        $subMenuInt = $this->user32->cast('uintptr_t', $subMenu);
        return $this->user32->AppendMenuA($parentMenu, 0x0010, $subMenuInt->cdata, $label);
    }

    /**
     * Get the menu handle attached to a window.
     *
     * @param CData $hWnd The window handle
     * @return CData The HMENU menu handle
     */
    public function getWindowMenu(CData $hWnd): mixed
    {
        return $this->user32->GetMenu($hWnd);
    }

    /**
     * Show a popup menu at the current cursor position.
     *
     * @param CData $hMenu The popup menu handle
     * @param CData $hWnd The owner window handle
     * @return bool True on success
     */
    public function showPopupMenuAtCursor(CData $hMenu, CData $hWnd): bool
    {
        /** @var PointInterface $pt */
        $pt = $this->user32->new("POINT");
        $this->user32->GetCursorPos(FFI::addr($pt));

        // TPM_LEFTALIGN = 0x0000
        $this->user32->TrackPopupMenu($hMenu, 0x0000, $pt->x, $pt->y, 0, $hWnd, null);

        return true; /** FIXME */
    }

    /**
     * Set the position and size of a child window using SetWindowPos.
     *
     * @param CData $hWnd The child window handle
     * @param int $x New X position
     * @param int $y New Y position
     * @param int $width New width
     * @param int $height New height
     * @return mixed
     */
    public function setWindowPosition(CData $hWnd, int $x, int $y, int $width, int $height): mixed
    {
        return $this->user32->SetWindowPos($hWnd, null, $x, $y, $width, $height, SetWindowPos::SWP_NOZORDER->value);
    }

    /**
     * Send a message to a window or control handle.
     *
     * @param CData $hWnd The target window/control handle
     * @param int $msg The message identifier
     * @param mixed $wParam The WPARAM value
     * @param mixed $lParam The LPARAM value
     * @return mixed
     */
    public function sendMessageToWindow(CData $hWnd, int $msg, mixed $wParam, mixed $lParam): mixed
    {
        return $this->user32->SendMessageA($hWnd, $msg, $wParam, $lParam);
    }

    /**
     * Retrieve a message from the message queue (blocking call).
     * Returns the MSG CData structure, or false if WM_QUIT was received.
     *
     * @param CData|null $hWnd Optional window handle filter (null for all)
     * @return CData|false The MSG structure, or false on WM_QUIT
     */
    public function getNextMessage(?CData $hWnd = null): CData|false
    {
        $msg = $this->user32->new("MSG");
        $result = $this->user32->GetMessageW(FFI::addr($msg), $hWnd, 0, 0);

        if ($result === 0 || $result === -1) {
            return false;
        }

        return $msg;
    }

    /**
     * Translate and dispatch a message to the appropriate window procedure.
     *
     * @param CData $msg The MSG structure to translate and dispatch
     * @return void
     */
    public function translateAndDispatch(CData $msg): void
    {
        $this->user32->TranslateMessage(FFI::addr($msg));
        $this->user32->DispatchMessageW(FFI::addr($msg));
    }

    /**
     * Shutdown the GDI+ subsystem.
     *
     * @param mixed $gdipToken The GDI+ startup token
     * @return void
     */
    public function shutdownGDIPlus(mixed $gdipToken): void
    {
        $this->gdiplus->GdiplusShutdown($gdipToken[0]);
    }

    /**
     * Get the client area dimensions of a window.
     *
     * @param CData $hWnd The window handle
     * @return array{width: int, height: int}|false Client dimensions or false on failure
     */
    public function getClientDimensions(CData $hWnd): array|false
    {
        /** @var RectInterface $rcClient */
        $rcClient = $this->user32->new('RECT');
        $result = $this->user32->GetClientRect($hWnd, FFI::addr($rcClient));

        if (!$result) {
            return false;
        }

        return [
            'width' => $rcClient->cx,
            'height' => $rcClient->cy,
        ];
    }

    /**
     * Post a quit message to terminate the message loop.
     *
     * @param int $exitCode The exit code (default 0)
     * @return void
     */
    public function postQuitMessage(int $exitCode = 0): void
    {
        $this->user32->PostQuitMessage($exitCode);
    }

    /**
     * Invalidate a window's client area to trigger a repaint.
     *
     * @param CData $hWnd The window handle
     * @param bool $erase Whether to erase the background
     * @return int
     */
    public function invalidateWindow(CData $hWnd, bool $erase = true): int
    {
        return $this->user32->InvalidateRect($hWnd, null, $erase ? 1 : 0);
    }

    /**
     * Allocate a new FFI CData structure from user32 type definitions.
     *
     * @param string $type The C type name (e.g. "MSG", "RECT", "POINT")
     * @return CData
     */
    public function newStruct(string $type): CData
    {
        return $this->user32->new($type);
    }

    /**
     * Cast a value to a specified FFI type using user32's type context.
     *
     * @param string $type The target C type (e.g. "LPARAM", "void*")
     * @param mixed $value The value to cast
     * @return CData
     */
    public function castValue(string $type, mixed $value): CData
    {
        return $this->user32->cast($type, $value);
    }

    /**
     * Show and update a window by its CData handle.
     * Equivalent to calling ShowWindow(hWnd, SW_SHOWNORMAL) + UpdateWindow(hWnd).
     *
     * @param CData $hWnd The window handle
     * @param int $cmdShow Show command (default SW_SHOWNORMAL = 1)
     * @return void
     */
    public function showAndUpdateWindow(CData $hWnd, int $cmdShow = 1): void
    {
        $this->user32->ShowWindow($hWnd, $cmdShow);
        $this->user32->UpdateWindow($hWnd);
    }

    /**
     * Get the current module handle as an integer suitable for passing
     * to Create* control methods that expect int $hInstance.
     *
     * @return int The HINSTANCE value as an integer
     */
    public function getModuleHandleAsInt(): int
    {
        $h = $this->kernel32->GetModuleHandleA(null);
        $ptr = $this->kernel32->cast('uintptr_t', $h);
        return $ptr->cdata;
    }

    /**
     * Schedules a shell command to run at a specified date/time using a waitable timer.
     * Blocks the current process until the timer fires, then executes the command via passthru().
     *
     * @param string $rawDate Human-readable date/time string (e.g. "2025-12-31 23:59:59").
     * @param string $command Shell command to execute when the timer fires.
     * @return void
     * @throws Exception If the target time is in the past.
     * @throws RuntimeException If CreateWaitableTimerW or SetWaitableTimer fails.
     */
    public function createScheduleTask(string $rawDate, string $command)
    {
        $false = 0;

        $target = Date::parseTarget($rawDate);
        $now = new DateTimeImmutable();
        $diff = $target->getTimestamp() - $now->getTimestamp();

        if ($diff <= 0) {
            throw new Exception("Error: Target time \"{$target->format('Y-m-d H:i:s')}\" is in the past.");
        }

        $hTimer = $this->kernel32->CreateWaitableTimerW(null, $this->kernel32->cast("bool*", $false), null);
        if (FFI::isNull($hTimer)) {
            throw new RuntimeException("Error: CreateWaitableTimerW failed.");
        }

        $fileTime = Date::datetimeToFileTime($target); // Positive LARGE_INTEGER = absolute UTC FILETIME
        $dueTime = $this->kernel32->new('LARGE_INTEGER');
        $dueTime->cdata = $fileTime;

        $ok = $this->kernel32->SetWaitableTimer($hTimer, FFI::addr($dueTime), 0, null, null, $this->kernel32->cast("bool*", $false));
        if (!$ok) {
            $this->kernel32->CloseHandle($hTimer);
            throw new RuntimeException("Error: SetWaitableTimer failed.");
        }

        $remaining = $target->getTimestamp() - (new DateTimeImmutable())->getTimestamp();
        if ($remaining > 0) {
            echo sprintf("\r[Scheduler] %02d:%02d:%02d remaining …", intdiv($remaining, 3600), intdiv($remaining % 3600, 60), $remaining % 60);
        }

        $remaining = $diff;
        while ($remaining > 0) { // Cap each WaitForSingleObject call to 60 s so we can print progress ticks.
            $waitMs = min($remaining, 60) * 1000;
            $result = $this->kernel32->WaitForSingleObject($hTimer, $waitMs);

            if ($result === 0x00000000) {
                break;   // Timer fired
            }

            if ($result === 0xFFFFFFFF) {
                $this->kernel32->CloseHandle($hTimer);
                throw new RuntimeException("Error: WaitForSingleObject failed.");
            }

            $remaining = $target->getTimestamp() - (new DateTimeImmutable())->getTimestamp(); // WAIT_TIMEOUT — print tick and loop
            if ($remaining > 0) {
                echo sprintf("\r[Scheduler] %02d:%02d:%02d remaining …", intdiv($remaining, 3600), intdiv($remaining % 3600, 60), $remaining % 60);
            }
        }

        $this->kernel32->CloseHandle($hTimer);
        $exitCode = 0;
        passthru($command, $exitCode);
    }

    /**
     * Spawn scheduler_worker.php as a fully detached process.
     * Returns the child PID. PHP parent can exit immediately after.
     *
     * @param DateTimeInterface $runAt
     * @param string $command
     * @param null|string $phpBinary
     * @param null|string $workerPath
     * 
     * @throws RuntimeException
     */
    public function addScheduleWorker(DateTimeInterface $runAt, string $command, ?string $phpBinary = null, ?string $workerPath = null): int
    {
        $phpBin = $phpBinary ?? PHP_BINARY;
        $worker = $workerPath ?? __DIR__ . '/scheduler_worker.php';

        if (!file_exists($worker)) {
            throw new RuntimeException("Worker not found: $worker");
        }

        $cmdLine = sprintf('"%s" "%s" %d "%s"', $phpBin, $worker, $runAt->getTimestamp(), addslashes($command));

        return $this->spawnDetached($cmdLine);
    }

    /**
     * Spawns a detached process using CreateProcessW and returns the child PID.
     *
     * Uses DETACHED_PROCESS + CREATE_NO_WINDOW so the child is independent of the parent
     * and does not inherit a console window.
     *
     * @param string $cmdLine Full command line (will be converted to a mutable UTF-16LE buffer).
     * @return int Spawned process ID (PID).
     * @throws RuntimeException When CreateProcessW fails.
     */
    private function spawnDetached(string $cmdLine): int
    {
        $creationFlags = 0x00000008 | 0x08000000; // DETACHED_PROCESS | CREATE_NO_WINDOW, Child gets no console, no inherited handles, no parent link.
        $si = $this->kernel32->new('STARTUPINFOW');
        $si->cb = FFI::sizeof($si);
        $pi = $this->kernel32->new('PROCESS_INFORMATION');

        $wideCmd = mb_convert_encoding($cmdLine . "\0", 'UTF-16LE', 'UTF-8'); // CreateProcessW requires a *mutable* wide-char buffer for lpCommandLine.
        $dwordCount = (int) ceil((strlen($wideCmd) + 4) / 4); // Allocate as a byte array (DWORD array wide enough to hold it)
        $buf = $this->kernel32->new(FFI::arrayType($this->kernel32->type('DWORD'), [$dwordCount]));
        FFI::memcpy($buf, $wideCmd, strlen($wideCmd));

        $ok = $this->kernel32->CreateProcessW(null, $buf, null, null, 0, $creationFlags, null, null, FFI::addr($si), FFI::addr($pi));
        if (!$ok) {
            throw new RuntimeException('CreateProcessW failed.');
        }

        $pid = $pi->dwProcessId;

        // Close both handles — child is now fully independent.
        $this->kernel32->CloseHandle($pi->hProcess);
        $this->kernel32->CloseHandle($pi->hThread);

        return $pid;
    }

    /**
     * Find a process ID by its executable name using Toolhelp32 snapshots.
     *
     * @param string $exeName Executable file name (e.g. "game.exe").
     * @return int|null PID if found, otherwise null.
     */
    public function findPidByName(string $exeName): ?int
    {
        $TH32CS_SNAPPROCESS = 0x00000002;
        $hSnap = $this->kernel32->CreateToolhelp32Snapshot($TH32CS_SNAPPROCESS, 0);

        // INVALID_HANDLE_VALUE = (void*)-1; check via raw int64 reinterpret
        $snapVal = $this->kernel32->new('int64_t');
        $to = FFI::addr($snapVal);
        $from = FFI::addr($hSnap);
        FFI::memcpy($to, $from, 8);
        if ($snapVal->cdata === 0 || $snapVal->cdata === -1) {
            return null;
        }

        $entry = $this->kernel32->new('PROCESSENTRY32W');
        $entry->dwSize = FFI::sizeof($entry);

        if (!$this->kernel32->Process32FirstW($hSnap, FFI::addr($entry))) {
            $this->kernel32->CloseHandle($hSnap);
            return null;
        }

        $target = strtolower($exeName);

        do {
            $name = parent::wideToPhp($entry->szExeFile, 260);
            if (strtolower($name) === $target) {
                $pid = (int) $entry->th32ProcessID;
                $this->kernel32->CloseHandle($hSnap);
                return $pid;
            }
        } while ($this->kernel32->Process32NextW($hSnap, FFI::addr($entry)));

        $this->kernel32->CloseHandle($hSnap);
        return null;
    }

    /**
     * Reinterpret a void* (HWND) CData as int64 to get a stable hashable key.
     * PHP has no direct pointer-to-int cast, so we memcpy the raw 8 bytes.
     * 
     * @param CData $hwnd
     * @return int
     */
    public function hwndKey(FFI\CData $hwnd): int
    {
        $i = $this->kernel32->new('int64_t');
        $to = FFI::addr($i);
        $from = FFI::addr($hwnd);
        FFI::memcpy($to, $from, 8);
        return $i->cdata;
    }

    /**
     * Enumerates all top-level windows belonging to a process and sets their text.
     * Optionally filters by window class name.
     * Returns the number of windows updated.
     */
    public function setElementText(string|int $arg, string $newText, string $filterClass = ''): int
    {
        $targetPid = ctype_digit((string) $arg) ? (int) $arg : $this->findPidByName($arg);

        if ($targetPid === null) {
            throw new Exception("Error: process {$arg} not found. Is it running?");
        }

        $wideText = mb_convert_encoding($newText, 'UTF-16LE', 'UTF-8') . "\x00\x00";
        $charCount = mb_strlen($newText, 'UTF-8') + 1;
        $textBuf = $this->user32->new("WCHAR[$charCount]");
        FFI::memcpy($textBuf, $wideText, strlen($wideText));

        $pidRef = $this->kernel32->new('DWORD');
        $updated = 0;

        $enumCallback = function (mixed $hwnd, int $lParam) use (&$updated, $targetPid, $filterClass, $textBuf, &$pidRef) {
            $pidRef->cdata = 0;
            $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pidRef));

            if ((int) $pidRef->cdata !== $targetPid) {
                $pointer = 1;
                return $this->user32->cast("bool*", $pointer);
            }

            if ($filterClass !== '') {
                $classBuf = $this->user32->new('WCHAR[256]');
                $classLen = $this->user32->GetClassNameW($hwnd, $classBuf, 256);
                $cls = $classLen > 0 ? parent::wideToPhp($classBuf, $classLen) : '';
                if ($cls !== $filterClass) {
                    $pointer = 1;
                    return $this->user32->cast("bool*", $pointer);
                }
            }

            $this->user32->SetWindowTextW($hwnd, $textBuf);
            $updated++;
            $pointer = 1;
            return $this->user32->cast("bool*", $pointer);
        };

        $this->user32->EnumWindows($enumCallback, 0);
        return $updated;
    }

    /**
     * Returns a map of [controlId => hwndKey] for all child windows of a given process.
     * Use this to identify the target element before calling setElementText.
     * 
     * @param string|int $arg
     * @return array
     */
    public function getElementIds(string|int $arg): array
    {
        $targetPid = ctype_digit((string) $arg) ? (int) $arg : $this->findPidByName($arg);

        if ($targetPid === null) {
            throw new Exception("Error: process {$arg} not found. Is it running?");
        }

        $pidRef = $this->kernel32->new('DWORD');
        $results = [];

        $enumTopCallback = function (mixed $hwnd) use (&$results, $targetPid, &$pidRef): int {
            $pidRef->cdata = 0;
            $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pidRef));
            if ((int) $pidRef->cdata !== $targetPid) {
                return 1;
            }

            $enumChildCallback = function (mixed $childHwnd) use (&$results): int {
                $ctrlId = $this->user32->GetDlgCtrlID($childHwnd);
                $key = $this->hwndKey($childHwnd);

                $classBuf = $this->user32->new('WCHAR[256]');
                $classLen = $this->user32->GetClassNameW($childHwnd, $classBuf, 256);
                $className = $classLen > 0 ? parent::wideToPhp($classBuf, $classLen) : '?';

                $textBuf = $this->user32->new('WCHAR[512]');
                $textLen = $this->user32->GetWindowTextW($childHwnd, $textBuf, 512);
                $text = $textLen > 0 ? parent::wideToPhp($textBuf, $textLen) : '';

                $results[$ctrlId] = [
                    'hwnd_key' => $key,
                    'class_name' => $className,
                    'text' => $text,
                ];
                return 1;
            };

            $this->user32->EnumChildWindows($hwnd, $enumChildCallback, 0);
            return 1;
        };

        $this->user32->EnumWindows($enumTopCallback, 0);
        return $results;
    }

    /**
     * Monitors WinEvents for a target process.
     * By default fires on EVENT_OBJECT_NAMECHANGE, which is raised by SetWindowTextW.
     * 
     * @param string|int $pidOrProcessName
     * @param null|Closure(array{api_name: string, callCount: int, class_name: string, event: int, event_thread: int, event_time: int, mhook: mixed, new_title: string, prev: string, timestamp: string}): void $eventCallback
     * @param null|WindowEvent $min
     * @param null|WindowEvent $max
     * 
     * @return void
     */
    public function watchEvent(string|int $pidOrProcessName, null|Closure $eventCallback = null, null|int|WindowEvent $min = null, null|int|WindowEvent $max = null): void
    {
        $min ??= WindowEvent::EVENT_OBJECT_NAMECHANGE;
        $max ??= WindowEvent::EVENT_OBJECT_NAMECHANGE;

        if (ctype_digit($pidOrProcessName)) {
            $targetPid = (int) $pidOrProcessName;
        } else {
            $targetPid = $this->findPidByName($pidOrProcessName);
        }

        if ($targetPid === null) {
            throw new Exception("Error: process \"$pidOrProcessName\" not found. Is it running?");
        }

        $prevTitles = [];   // hwndKey → last known title
        $callCount = 0;
        $callback = function (mixed $hHook, int $event, mixed $hwnd, int $idObject, int $idChild, int $eventThread, int $eventTime) use (&$prevTitles, &$callCount, $eventCallback): void {
            // Filters out non-title-bar name changes (controls, menu items, etc.)
            if ($idObject !== 0 || $idChild !== 0) {
                return;
            }

            $key = $this->hwndKey($hwnd);
            if ($key === 0) {
                return;
            }

            $titleBuf = $this->user32->new('WCHAR[512]');
            $titleLen = $this->user32->GetWindowTextW($hwnd, $titleBuf, 512);
            $newTitle = $titleLen > 0 ? parent::wideToPhp($titleBuf, $titleLen) : '';

            // Skip if WinEvent fired but title genuinely didn't change
            // (can happen when DefWindowProc echoes the same text)
            $prevTitle = $prevTitles[$key] ?? null;
            if ($prevTitle === $newTitle) {
                return;
            }
            $prevTitles[$key] = $newTitle;

            $classBuf = $this->user32->new('WCHAR[256]');
            $classLen = $this->user32->GetClassNameW($hwnd, $classBuf, 256);
            $className = $classLen > 0 ? parent::wideToPhp($classBuf, $classLen) : '?';

            $isUnicode = (bool) $this->user32->IsWindowUnicode($hwnd);
            $apiName = $isUnicode ? 'SetWindowTextW' : 'SetWindowTextA';

            $now = microtime(true);
            $ts = date('H:i:s', (int) $now) . sprintf('.%03d', (int) (fmod($now, 1.0) * 1000));
            $prev = $prevTitle !== null ? sprintf('"%s"', addcslashes($prevTitle, '"\\')) : '(first capture)';

            $result = [
                'mhook' => $hHook,
                'event' => $event,
                'event_thread' => $eventThread,
                'event_time' => $eventTime,
                'timestamp' => $ts,
                'callCount' => ++$callCount,
                'api_name' => $apiName,
                'class_name' => $className,
                'prev' => $prev,
                'new_title' => addcslashes($newTitle, '"\\')
            ];

            if (is_callable($eventCallback)) {
                $eventCallback($result);
            }
        };

        $hHook = $this->user32->SetWinEventHook($min, $max, null, $callback, $targetPid, 0, 0x0002);
        $hookVal = $this->user32->new('int64_t');
        $to = FFI::addr($hookVal);
        $from = FFI::addr($hHook);
        FFI::memcpy($to, $from, 8);
        if ($hookVal->cdata === 0) {
            throw new Exception("Error: SetWinEventHook failed (is the process running and accessible?)");
        }

        $threadId = $this->kernel32->GetCurrentThreadId();
        ServerAPI::setControlHandler(function (int $event) use ($hHook, $threadId): bool {
            $this->user32->UnhookWinEvent($hHook);
            $this->user32->PostThreadMessageW($threadId, WindowMessage::WM_QUIT, 0, 0);
            return true;
        }, true);

        $msg = $this->user32->new('MSG');
        while (true) {
            $ret = $this->user32->GetMessageW(FFI::addr($msg), null, 0, 0);
            if ($ret <= WindowMessage::WM_QUIT) {
                break;
            }

            $this->user32->TranslateMessage(FFI::addr($msg));
            $this->user32->DispatchMessageW(FFI::addr($msg));
        }

        $this->user32->UnhookWinEvent($hHook);
    }

    /**
     * Installs a global low-level keyboard hook (WH_KEYBOARD_LL).
     * Callback receives: vkCode, scanCode, flags, time, injected.
     * Runs until CTRL+C or the callback returns false.
     * 
     * @param Closure(array{flags: mixed, injected: bool, message: int, scan_code: mixed, time: mixed, vk_code: mixed}): bool $eventCallback
     * @return void
     */
    public function watchKeyboard(Closure $eventCallback): void
    {
        $callback = function (int $nCode, int $wParam, int $lParam) use ($eventCallback): int {
            if ($nCode < 0) {
                return $this->user32->CallNextHookEx(null, $nCode, $wParam, $lParam);
            }

            $addr = $this->user32->new('intptr_t');
            $addr->cdata = $lParam;
            $struct = $this->user32->cast('KBDLLHOOKSTRUCT*', $addr);

            $result = [
                'message' => $wParam,
                'vk_code' => $struct->vkCode,
                'scan_code' => $struct->scanCode,
                'flags' => $struct->flags,
                'time' => $struct->time,
                'injected' => (bool) ($struct->flags & 0x10),
            ];

            if ($eventCallback($result) === false) {
                $this->user32->PostThreadMessageW($this->kernel32->GetCurrentThreadId(), 0x0012, 0, 0);
            }

            return $this->user32->CallNextHookEx(null, $nCode, $wParam, $lParam);
        };

        $hHook = $this->user32->SetWindowsHookExW(WindowHook::WH_KEYBOARD_LL, $callback, null, 0);
        $hookVal = $this->user32->new('int64_t');
        $to = FFI::addr($hookVal);
        $from = FFI::addr($hHook);
        FFI::memcpy($to, $from, 8);
        if ($hookVal->cdata === 0) {
            throw new Exception("Error: SetWindowsHookExW (keyboard) failed.");
        }

        $this->runMessageLoop($hHook);
    }

    /**
     * Installs a global low-level mouse hook (WH_MOUSE_LL).
     * Callback receives: message, x, y, mouseData, flags, time, injected.
     * Runs until CTRL+C or the callback returns false.
     * 
     * @param Closure $eventCallback
     */
    public function watchMouse(Closure $eventCallback): void
    {
        $callback = function (int $nCode, int $wParam, int $lParam) use ($eventCallback): int {
            if ($nCode < 0) {
                return $this->user32->CallNextHookEx(null, $nCode, $wParam, $lParam);
            }

            $addr = $this->user32->new('intptr_t');
            $addr->cdata = $lParam;
            $struct = $this->user32->cast('MSLLHOOKSTRUCT*', $addr);

            $result = [
                'message' => $wParam,
                'x' => $struct->pt->x,
                'y' => $struct->pt->y,
                'mouse_data' => $struct->mouseData,
                'flags' => $struct->flags,
                'time' => $struct->time,
                'injected' => (bool) ($struct->flags & 0x01),
            ];

            if ($eventCallback($result) === false) {
                $this->user32->PostThreadMessageW($this->kernel32->GetCurrentThreadId(), 0x0012, 0, 0);
            }

            return $this->user32->CallNextHookEx(null, $nCode, $wParam, $lParam);
        };

        $hHook = $this->user32->SetWindowsHookExW(WindowHook::WH_MOUSE_LL, $callback, null, 0);
        $hookVal = $this->user32->new('int64_t');
        $to = FFI::addr($hookVal);
        $from = FFI::addr($hHook);
        FFI::memcpy($to, $from, 8);
        if ($hookVal->cdata === 0) {
            throw new Exception("Error: SetWindowsHookExW (mouse) failed.");
        }

        $this->runMessageLoop($hHook);
    }

    /**
     * Runs the message loop and unhooks on WM_QUIT or CTRL+C.
     * 
     * @param mixed $hHook
     * @return void
     */
    private function runMessageLoop(mixed $hHook): void
    {
        $threadId = $this->kernel32->GetCurrentThreadId();
        ServerAPI::setControlHandler(function (int $event) use ($hHook, $threadId): bool {
            $this->user32->UnhookWindowsHookEx($hHook);
            $this->user32->PostThreadMessageW($threadId, 0x0012, 0, 0);
            return true;
        }, true);

        $msg = $this->user32->new('MSG');
        while (true) {
            $ret = $this->user32->GetMessageW(FFI::addr($msg), null, 0, 0);
            if ($ret <= 0) {
                break;
            }

            $this->user32->TranslateMessage(FFI::addr($msg));
            $this->user32->DispatchMessageW(FFI::addr($msg));
        }

        $this->user32->UnhookWindowsHookEx($hHook);
    }

    /**
     * Resolves the physical monitor handle from the primary display.
     * Caller must destroy the returned monitor array via DestroyPhysicalMonitors.
     * Returns [hPhysical, monitorArray, count] or throws on failure.
     * 
     * @return array
     * @throws Exception
     */
    private function resolvePhysicalMonitor(): array
    {
        $hDesktop = $this->user32->GetDesktopWindow();
        // MONITOR_DEFAULTTOPRIMARY = 0x00000001
        $hMonitor = $this->user32->MonitorFromWindow($hDesktop, 0x00000001);

        $count = $this->dxva2->new('DWORD');
        if (!$this->dxva2->GetNumberOfPhysicalMonitorsFromHMONITOR($hMonitor, FFI::addr($count))) {
            throw new Exception("Error: GetNumberOfPhysicalMonitorsFromHMONITOR failed.");
        }

        $n = (int) $count->cdata;
        $monitors = $this->dxva2->new("PHYSICAL_MONITOR[$n]");
        if (!$this->dxva2->GetPhysicalMonitorsFromHMONITOR($hMonitor, $n, $monitors)) {
            throw new Exception("Error: GetPhysicalMonitorsFromHMONITOR failed.");
        }

        return [$monitors[0]->hPhysicalMonitor, $monitors, $n];
    }

    /**
     * Returns ['min', 'current', 'max'] brightness values (0–100 typically).
     * 
     * @return array{min: int, max: int, current: int}
     * @throws Exception
     */
    public function getScreenBrightness(): array
    {
        [$hPhysical, $monitors, $n] = $this->resolvePhysicalMonitor();

        $min = $this->dxva2->new('DWORD');
        $current = $this->dxva2->new('DWORD');
        $max = $this->dxva2->new('DWORD');

        $ok = $this->dxva2->GetMonitorBrightness($hPhysical, FFI::addr($min), FFI::addr($current), FFI::addr($max));

        $this->dxva2->DestroyPhysicalMonitors($n, $monitors);

        if (!$ok) {
            throw new Exception("Error: GetMonitorBrightness failed. Monitor may not support DDC/CI.");
        }

        return [
            'min' => (int) $min->cdata,
            'current' => (int) $current->cdata,
            'max' => (int) $max->cdata,
        ];
    }

    /**
     * Sets the screen brightness. Value must be within the monitor's supported range.
     * 
     * @param int $brightness
     * @return void
     */
    public function setScreenBrightness(int $brightness): void
    {
        [$hPhysical, $monitors, $n] = $this->resolvePhysicalMonitor();

        $ok = $this->dxva2->SetMonitorBrightness($hPhysical, $brightness);

        $this->dxva2->DestroyPhysicalMonitors($n, $monitors);

        if (!$ok) {
            throw new Exception("Error: SetMonitorBrightness failed. Monitor may not support DDC/CI.");
        }
    }

    /**
     * Detects the storage protocol type (ATA, NVMe, SCSI, etc.) for a given handle.
     * 
     * @param mixed $hDevice
     * @return int
     */
    private function resolveStorageProtocol(mixed $hDevice): int
    {
        $query = $this->kernel32->new('STORAGE_PROPERTY_QUERY');
        $proto = $this->kernel32->new('STORAGE_PROTOCOL_SPECIFIC_DATA');
        $querySize = FFI::sizeof($query);
        $protoSize = FFI::sizeof($proto);
        $total = $querySize + $protoSize;

        $buf = $this->kernel32->new("BYTE[$total]");
        $pointer = FFI::addr($buf[0]);
        FFI::memset($pointer, 0, $total);

        $pointer = FFI::addr($buf[0]);
        $query = $this->kernel32->cast('STORAGE_PROPERTY_QUERY*', $pointer);
        $query->PropertyId = 49; // StorageAdapterProtocolSpecificProperty = 49
        $query->QueryType = 0; // PropertyStandardQuery = 0

        $pointer = FFI::addr($buf[0]);
        $pointer = $this->kernel32->cast('char*', $pointer) + $querySize - 1;
        $proto = $this->kernel32->cast('STORAGE_PROTOCOL_SPECIFIC_DATA*', $pointer);
        $proto->ProtocolType = 0;
        $proto->DataType = 0;
        $proto->ProtocolDataRequestValue = 0;
        $proto->ProtocolDataRequestSubValue = 0;
        $proto->ProtocolDataOffset = $protoSize;
        $proto->ProtocolDataLength = 0;

        $pointer = $this->kernel32->new('STORAGE_PROTOCOL_DATA_DESCRIPTOR');
        $descSize = FFI::sizeof($pointer);
        $out = $this->kernel32->new("BYTE[$descSize]");
        $bytes = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->DeviceIoControl($hDevice, StorageControlStorageProperty::IOCTL_STORAGE_QUERY_PROPERTY->value, FFI::addr($buf[0]), $total, FFI::addr($out[0]), $descSize, FFI::addr($bytes), null);

        if (!$ok) {
            return 0; // ProtocolTypeUnknown
        }

        $pointer = FFI::addr($out[0]);
        $desc = $this->kernel32->cast('STORAGE_PROTOCOL_DATA_DESCRIPTOR*', $pointer);
        return (int) $desc->ProtocolSpecificData->ProtocolType;
    }

    /**
     * Returns BusType from STORAGE_DEVICE_DESCRIPTOR via StorageDeviceProperty.
     * 
     * @param mixed $hDevice
     * @return int
     */
    private function resolveStorageBusType(mixed $hDevice): int
    {
        $query = $this->kernel32->new('STORAGE_PROPERTY_QUERY');
        $pointer = FFI::addr($query);
        FFI::memset($pointer, 0, FFI::sizeof($query));
        $query->PropertyId = 0; // StorageDeviceProperty
        $query->QueryType = 0; // PropertyStandardQuery

        $descSize = 1024;
        $out = $this->kernel32->new("BYTE[$descSize]");
        $bytes = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->DeviceIoControl($hDevice, StorageControlStorageProperty::IOCTL_STORAGE_QUERY_PROPERTY->value, FFI::addr($query), FFI::sizeof($query), FFI::addr($out[0]), $descSize, FFI::addr($bytes), null);
        if (!$ok) {
            return 0;
        }

        $pointer = FFI::addr($out[0]);
        $desc = $this->kernel32->cast('STORAGE_DEVICE_DESCRIPTOR*', $pointer);
        return (int) $desc->BusType;
    }

    /**
     * Executes a file with elevated privileges using ShellExecuteExA.
     * Typically used internally with the "runas" verb to trigger a UAC prompt.
     *
     * @param string $lpVerb   Shell verb to use (e.g. "runas", "open").
     * @param string $lpFile   Path to the executable or document to launch.
     * @param string $lpParameters Command-line parameters to pass to the launched process.
     * @return void
     */
    public function runElevated(string $lpVerb, string $lpFile, string $lpParameters)
    {
        $verb = $this->shell32->new('char[6]');
        $file = $this->shell32->new('char[8]');
        $params = $this->shell32->new('char[256]');

        $from = "{$lpVerb}\0";
        FFI::memcpy($verb, $from, 6);
        $from = "{$lpFile}\0";
        FFI::memcpy($file, $from, 8);
        FFI::memcpy($params, $lpParameters, strlen($lpParameters));

        $void = $this->shell32->new('void*');
        $char = $this->shell32->new('char*');

        $info = $this->shell32->new('SHELLEXECUTEINFOA');
        $info->cbSize = FFI::sizeof($info);
        $info->fMask = 0x00000040;
        $info->hwnd = $this->shell32->cast('void*', $void);
        $info->lpDirectory = $this->shell32->cast('const char*', $char);
        $info->lpVerb = $this->shell32->cast('const char*', $verb);
        $info->lpFile = $this->shell32->cast('const char*', $file);
        $info->lpParameters = $this->shell32->cast('const char*', $params);
        $info->nShow = 1;

        $this->shell32->ShellExecuteExA(FFI::addr($info));
    }

    /**
     * Runs a command in an elevated command prompt via UAC.
     * 
     * @param string $command
     * @param bool $persist
     * @return void
     */
    public function runElevatedCommandPrompt(string $command, bool $persist = false)
    {
        $paramStr = ($persist ? "/k " : "/c ") . $command . "\0";
        $this->runElevated("runas", "cmd.exe", $paramStr);
    }

    /**
     * Returns a list of all available ANSI wave audio output devices.
     * Each entry contains the device id, name, number of channels,
     * supported formats bitmask, and capabilities flags.
     *
     * @return array<int, array{id: int, name: string, channels: int, formats: int, support: int}>
     */
    public function getAudioOutputDeviceList(): array
    {
        $count = $this->winmm->waveOutGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('WAVEOUTCAPSA');
            $this->winmm->waveOutGetDevCapsA($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => FFI::string($caps->szPname),
                'channels' => $caps->wChannels,
                'formats' => $caps->dwFormats,
                'support' => $caps->dwSupport,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available ANSI wave audio input (recording) devices.
     * Each entry contains the device id, name, channel count, and supported formats bitmask.
     *
     * @return array<int, array{id: int, name: string, channels: int, formats: int}>
     */
    public function getAudioInputDeviceList(): array
    {
        $count = $this->winmm->waveInGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('WAVEINCAPSA');
            $this->winmm->waveInGetDevCapsA($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => FFI::string($caps->szPname),
                'channels' => $caps->wChannels,
                'formats' => $caps->dwFormats,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available ANSI MIDI output devices.
     * Each entry contains the device id, name, technology type, voice count,
     * note polyphony, channel mask, and capability flags.
     *
     * @return array<int, array{id: int, name: string, technology: int, voices: int, notes: int, channelMask: int, support: int}>
     */
    public function getMidiOutputDeviceList(): array
    {
        $count = $this->winmm->midiOutGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('MIDIOUTCAPSA');
            $this->winmm->midiOutGetDevCapsA($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => FFI::string($caps->szPname),
                'technology' => $caps->wTechnology,
                'voices' => $caps->wVoices,
                'notes' => $caps->wNotes,
                'channelMask' => $caps->wChannelMask,
                'support' => $caps->dwSupport,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available ANSI MIDI input devices.
     * Each entry contains the device id, name, and capability flags.
     *
     * @return array<int, array{id: int, name: string, support: int}>
     */
    public function getMidiInputDeviceList(): array
    {
        $count = $this->winmm->midiInGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('MIDIINCAPSA');
            $this->winmm->midiInGetDevCapsA($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => FFI::string($caps->szPname),
                'support' => $caps->wSupport,
            ];
        }

        return $devices;
    }

    /**
     * Decodes a wide-character (UTF-16LE) device name array returned by the
     * winmm CAPS structs (szPname) into a UTF-8 PHP string.
     * Reads up to 32 code units and stops at the null terminator.
     *
     * @param FFI\CData $szPname Pointer to a uint16_t[32] device-name buffer.
     * @return string UTF-8 encoded device name.
     */
    private function decodePname(FFI\CData $szPname): string
    {
        $raw = '';
        for ($i = 0; $i < 32 && $szPname[$i] !== 0; $i++) {
            $raw .= pack('v', $szPname[$i]);
        }
        return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
    }

    /**
     * Returns a list of all available wide-character (Unicode) wave audio output devices.
     * Unlike getAudioOutputDeviceList(), device names are decoded from UTF-16LE,
     * making this safe for non-ASCII device names.
     *
     * @return array<int, array{id: int, name: string, channels: int, formats: int, support: int}>
     */
    public function getAudioOutputDeviceListW(): array
    {
        $count = $this->winmm->waveOutGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('WAVEOUTCAPSW');
            $this->winmm->waveOutGetDevCapsW($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => $this->decodePname($caps->szPname),
                'channels' => $caps->wChannels,
                'formats' => $caps->dwFormats,
                'support' => $caps->dwSupport,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available wide-character (Unicode) wave audio input devices.
     * Device names are decoded from UTF-16LE, making this safe for non-ASCII names.
     *
     * @return array<int, array{id: int, name: string, channels: int, formats: int}>
     */
    public function getAudioInputDeviceListW(): array
    {
        $count = $this->winmm->waveInGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('WAVEINCAPSW');
            $this->winmm->waveInGetDevCapsW($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => $this->decodePname($caps->szPname),
                'channels' => $caps->wChannels,
                'formats' => $caps->dwFormats,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available wide-character (Unicode) MIDI output devices.
     * Device names are decoded from UTF-16LE, making this safe for non-ASCII names.
     *
     * @return array<int, array{id: int, name: string, technology: int, voices: int, notes: int, channelMask: int, support: int}>
     */
    public function getMidiOutputDeviceListW(): array
    {
        $count = $this->winmm->midiOutGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('MIDIOUTCAPSW');
            $this->winmm->midiOutGetDevCapsW($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => $this->decodePname($caps->szPname),
                'technology' => $caps->wTechnology,
                'voices' => $caps->wVoices,
                'notes' => $caps->wNotes,
                'channelMask' => $caps->wChannelMask,
                'support' => $caps->dwSupport,
            ];
        }

        return $devices;
    }

    /**
     * Returns a list of all available wide-character (Unicode) MIDI input devices.
     * Device names are decoded from UTF-16LE, making this safe for non-ASCII names.
     *
     * @return array<int, array{id: int, name: string, support: int}>
     */
    public function getMidiInputDeviceListW(): array
    {
        $count = $this->winmm->midiInGetNumDevs();
        $devices = [];

        for ($i = 0; $i < $count; $i++) {
            $caps = $this->winmm->new('MIDIINCAPSW');
            $this->winmm->midiInGetDevCapsW($i, FFI::addr($caps), FFI::sizeof($caps));
            $devices[] = [
                'id' => $i,
                'name' => $this->decodePname($caps->szPname),
                'support' => $caps->wSupport,
            ];
        }

        return $devices;
    }

    /**
     * Returns the current physical and virtual memory usage statistics via GlobalMemoryStatusEx.
     * All byte values are 64-bit unsigned integers.
     *
     * @return array{
     *   totalPhys: int,
     *   availPhys: int,
     *   totalPageFile: int,
     *   availPageFile: int,
     *   totalVirtual: int,
     *   availVirtual: int,
     *   memoryLoad: int
     * }
     */
    public function getMemoryStatus(): array
    {
        $status = $this->kernel32->new('MEMORYSTATUSEX');
        $status->dwLength = FFI::sizeof($status);
        $this->kernel32->GlobalMemoryStatusEx(FFI::addr($status));

        return [
            'totalPhys' => $status->ullTotalPhys,
            'availPhys' => $status->ullAvailPhys,
            'totalPageFile' => $status->ullTotalPageFile,
            'availPageFile' => $status->ullAvailPageFile,
            'totalVirtual' => $status->ullTotalVirtual,
            'availVirtual' => $status->ullAvailVirtual,
            'memoryLoad' => $status->dwMemoryLoad,
        ];
    }

    /**
     * Converts a PHP UTF-8 string to a null-terminated UTF-16LE wide-character buffer
     * suitable for passing to OLE/WMI COM interfaces via FFI.
     *
     * @param string $str Input UTF-8 string.
     * @return FFI\CData Null-terminated uint16_t[] buffer containing the wide-character string.
     */
    private function toWideString(string $str): FFI\CData
    {
        $buf = $this->ole32->new('unsigned short[' . (strlen($str) + 1) . ']');
        for ($i = 0; $i < strlen($str); $i++) {
            $buf[$i] = ord($str[$i]);
        }
        $buf[strlen($str)] = 0;
        return $buf;
    }

    /**
     * Initialises COM via CoInitializeEx and connects to the specified WMI namespace
     * using the IWbemLocator::ConnectServer COM interface.
     *
     * @param string $namespace WMI namespace path, e.g. "ROOT\\CIMV2".
     * @return array{locator: FFI\CData, services: FFI\CData}
     *   'locator'  – the IWbemLocator COM pointer (must be released by caller).
     *   'services' – the IWbemServices COM pointer (must be released by caller).
     * @throws RuntimeException If CoCreateInstance or ConnectServer fails.
     */
    private function connectWmiNamespace(string $namespace): array
    {
        $pointer = 0;
        $this->ole32->CoInitializeEx($this->ole32->cast('void*', $pointer), 0x2);

        $clsid = $this->ole32->new('unsigned char[16]');
        $iid = $this->ole32->new('unsigned char[16]');
        $clsidBytes = [0x11, 0xF8, 0x90, 0x45, 0x3A, 0x1D, 0xD0, 0x11, 0x89, 0x1F, 0x00, 0xAA, 0x00, 0x4B, 0x2E, 0x24];
        $iidBytes = [0x87, 0xA6, 0x12, 0xDC, 0x7F, 0x73, 0xCF, 0x11, 0x88, 0x4D, 0x00, 0xAA, 0x00, 0x4B, 0x2E, 0x24];

        foreach ($clsidBytes as $i => $b) {
            $clsid[$i] = $b;
        }
        foreach ($iidBytes as $i => $b) {
            $iid[$i] = $b;
        }

        $locator = $this->ole32->new('void*');
        $result = $this->ole32->CoCreateInstance($clsid, $this->ole32->cast('void*', $pointer), 0x1, $iid, FFI::addr($locator));
        if ($result !== 0) {
            throw new RuntimeException(sprintf('CoCreateInstance failed: 0x%08X', $result));
        }

        $nsWide = $this->toWideString($namespace);
        $services = $this->ole32->new('void*');
        $vtLocator = $this->ole32->cast('void***', $locator);
        /** @var callable $connectFn */
        $connectFn = $this->ole32->cast('int(*)(void*,unsigned short*,void*,void*,void*,void*,unsigned long,void*,void**)', $vtLocator[0][3]);

        $hr = $connectFn($locator, $nsWide, $this->ole32->cast('void*', $pointer), $this->ole32->cast('void*', $pointer), $this->ole32->cast('void*', $pointer), $this->ole32->cast('void*', $pointer), 0, $this->ole32->cast('void*', $pointer), FFI::addr($services));

        if ($hr !== 0) {
            throw new RuntimeException(sprintf('ConnectServer failed: 0x%08X', $hr));
        }

        return ['locator' => $locator, 'services' => $services];
    }

    /**
     * Executes a WQL query against the supplied IWbemServices pointer and returns
     * an IEnumWbemClassObject enumerator for iterating over the result rows.
     *
     * @param mixed  $services IWbemServices COM pointer obtained from connectWmiNamespace().
     * @param string $wql      WQL query string, e.g. "SELECT * FROM Win32_Processor".
     * @return mixed IEnumWbemClassObject COM pointer.
     * @throws RuntimeException If ExecQuery fails.
     */
    private function executeWmiQuery(mixed $services, string $wql): mixed
    {
        $vtSvc = $this->ole32->cast('void***', $services);
        /** @var callable $execQueryFn */
        $execQueryFn = $this->ole32->cast('int(*)(void*,unsigned short*,unsigned short*,unsigned long,void*,void**)', $vtSvc[0][20]);

        $lang = $this->toWideString('WQL');
        $query = $this->toWideString($wql);
        $enumerator = $this->ole32->new('void*');

        $pointer = 0;
        $hr = $execQueryFn($services, $lang, $query, 0x30, $this->ole32->cast('void*', $pointer), FFI::addr($enumerator));
        if ($hr !== 0) {
            throw new RuntimeException(sprintf('ExecQuery failed: 0x%08X', $hr));
        }

        return $enumerator;
    }

    /**
     * Reads a named integer property from a WMI IWbemClassObject COM pointer.
     * Internally calls IWbemClassObject::Get, initialises a VARIANT, and extracts
     * the integer value via VariantInit/SysAllocString from oleaut32.
     *
     * @param mixed  $obj      IWbemClassObject COM pointer for a single WMI result row.
     * @param string $propName WMI property name to retrieve, e.g. "NumberOfCores".
     * @return int The integer value of the property.
     */
    private function readVariantInt(mixed $obj, string $propName): int
    {
        $vtObj = $this->ole32->cast('void***', $obj);
        /** @var callable $getFn */
        $getFn = $this->ole32->cast('int(*)(void*,unsigned short*,long,void*,long*,long*)', $vtObj[0][4]);

        $bstr = $this->oleaut32->SysAllocString($this->toWideString($propName));
        $variant = $this->oleaut32->new('VARIANT');
        $this->oleaut32->VariantInit(FFI::addr($variant));

        $pointer = 0;
        $getFn($obj, $bstr, 0, FFI::addr($variant), $this->ole32->cast('long*', $pointer), $this->ole32->cast('long*', $pointer));
        $this->oleaut32->SysFreeString($bstr);

        $value = (int) $variant->val;
        $this->oleaut32->VariantClear(FFI::addr($variant));

        return $value;
    }

    /**
     * Writes a named integer property into a WMI IWbemClassObject COM pointer.
     * Internally calls IWbemClassObject::Put with a VT_I4 VARIANT.
     *
     * @param mixed  $obj      IWbemClassObject COM pointer for a single WMI result row.
     * @param string $propName WMI property name to write, e.g. "CPU".
     * @param int    $value    Integer value to assign.
     */
    private function writeVariantInt(mixed $obj, string $propName, int $value): void
    {
        $vtObj = $this->ole32->cast('void***', $obj);
        /** @var callable $putFn */
        $putFn = $this->ole32->cast('int(*)(void*,unsigned short*,long,void*)', $vtObj[0][5]);

        $bstr = $this->oleaut32->SysAllocString($this->toWideString($propName));
        $variant = $this->oleaut32->new('VARIANT');
        $this->oleaut32->VariantInit(FFI::addr($variant));
        $variant->vt = 0x3; // VT_I4
        $variant->val = $value;

        $putFn($obj, $bstr, 0, FFI::addr($variant));
        $this->oleaut32->SysFreeString($bstr);
        $this->oleaut32->VariantClear(FFI::addr($variant));
    }

    /**
     * Commits a modified WMI object back to the WMI repository via IWbemServices::PutInstance.
     *
     * @param mixed $services IWbemServices COM pointer obtained from connectWmiNamespace().
     * @param mixed $obj      IWbemClassObject COM pointer containing updated properties.
     * @throws RuntimeException If PutInstance returns a non-zero HRESULT.
     */
    private function putWmiInstance(mixed $services, mixed $obj): void
    {
        $vtSvc = $this->ole32->cast('void***', $services);
        /** @var callable $putInstFn */
        $putInstFn = $this->ole32->cast('int(*)(void*,void*,long,void*,void**)', $vtSvc[0][14]);

        $pointer = 0;
        $hr = $putInstFn($services, $obj, 0, $this->ole32->cast('void*', $pointer), $this->ole32->cast('void**', $pointer));
        if ($hr !== 0) {
            throw new RuntimeException(sprintf('PutInstance failed: 0x%08X', $hr));
        }
    }

    /**
     * Retrieves the MSI fan curve points via the ROOT\WMI MSI_CPU class.
     *
     * Each element contains 'instance' (InstanceName integer) and 'value' (CPU fan speed).
     *
     * @return array<int, array{instance: int, value: int}>
     */
    public function getMsiFanCurve(): array
    {
        ['services' => $services] = $this->connectWmiNamespace('ROOT\\WMI');
        $enumerator = $this->executeWmiQuery($services, 'SELECT * FROM MSI_CPU');

        $vtEnum = $this->ole32->cast('void***', $enumerator);
        /** @var callable $nextFn */
        $nextFn = $this->ole32->cast('int(*)(void*,long,unsigned long,void**,unsigned long*)', $vtEnum[0][4]);

        $points = [];
        $obj = $this->ole32->new('void*');
        $count = $this->ole32->new('unsigned long');

        while ($nextFn($enumerator, -1, 1, FFI::addr($obj), FFI::addr($count)) === 0 && $count->cdata > 0) {
            $points[] = [
                'instance' => $this->readVariantInt($obj, 'InstanceName'),
                'value' => $this->readVariantInt($obj, 'CPU'),
            ];
        }

        $this->ole32->CoUninitialize();

        return $points;
    }

    /**
     * Writes a single fan curve point into the ROOT\WMI MSI_CPU class.
     *
     * @param int $index Fan curve point index.
     * @param int $value Fan speed value (clamped to 0–100).
     */
    public function setMsiFanCurvePoint(int $index, int $value): void
    {
        ['services' => $services] = $this->connectWmiNamespace('ROOT\\WMI');

        $instanceName = sprintf('ACPI\\\\PNP0C14\\\\0_%d', $index);
        $enumerator = $this->executeWmiQuery($services, sprintf("SELECT * FROM MSI_CPU WHERE InstanceName='%s'", $instanceName));

        $vtEnum = $this->ole32->cast('void***', $enumerator);
        /** @var callable $nextFn */
        $nextFn = $this->ole32->cast('int(*)(void*,long,unsigned long,void**,unsigned long*)', $vtEnum[0][4]);

        $obj = $this->ole32->new('void*');
        $count = $this->ole32->new('unsigned long');

        if ($nextFn($enumerator, -1, 1, FFI::addr($obj), FFI::addr($count)) === 0 && $count->cdata > 0) {
            $this->writeVariantInt($obj, 'CPU', max(0, min(100, $value)));
            $this->putWmiInstance($services, $obj);
        }

        $this->ole32->CoUninitialize();
    }

    /**
     * Retrieves the MSI power mode settings via the ROOT\WMI MSI_Power class.
     *
     * Each element contains 'instance' (InstanceName integer) and 'power' (power mode value).
     *
     * @return array<int, array{instance: int, power: int}>
     */
    public function getMsiPowerMode(): array
    {
        ['services' => $services] = $this->connectWmiNamespace('ROOT\\WMI');
        $enumerator = $this->executeWmiQuery($services, 'SELECT * FROM MSI_Power');

        $vtEnum = $this->ole32->cast('void***', $enumerator);
        /** @var callable $nextFn */
        $nextFn = $this->ole32->cast('int(*)(void*,long,unsigned long,void**,unsigned long*)', $vtEnum[0][4]);

        $modes = [];
        $obj = $this->ole32->new('void*');
        $count = $this->ole32->new('unsigned long');

        while ($nextFn($enumerator, -1, 1, FFI::addr($obj), FFI::addr($count)) === 0 && $count->cdata > 0) {
            $modes[] = [
                'instance' => $this->readVariantInt($obj, 'InstanceName'),
                'power' => $this->readVariantInt($obj, 'Power'),
            ];
        }

        $this->ole32->CoUninitialize();

        return $modes;
    }

    /**
     * Writes a power mode value into the ROOT\WMI MSI_Power class.
     *
     * @param int $index Device instance index.
     * @param int $mode  Power mode value to set.
     */
    public function setMsiPowerMode(int $index, int $mode): void
    {
        ['services' => $services] = $this->connectWmiNamespace('ROOT\\WMI');

        $instanceName = sprintf('ACPI\\\\PNP0C14\\\\0_%d', $index);
        $enumerator = $this->executeWmiQuery($services, sprintf("SELECT * FROM MSI_Power WHERE InstanceName='%s'", $instanceName));

        $vtEnum = $this->ole32->cast('void***', $enumerator);
        /** @var callable $nextFn */
        $nextFn = $this->ole32->cast('int(*)(void*,long,unsigned long,void**,unsigned long*)', $vtEnum[0][4]);

        $obj = $this->ole32->new('void*');
        $count = $this->ole32->new('unsigned long');

        if ($nextFn($enumerator, -1, 1, FFI::addr($obj), FFI::addr($count)) === 0 && $count->cdata > 0) {
            $this->writeVariantInt($obj, 'Power', $mode);
            $this->putWmiInstance($services, $obj);
        }

        $this->ole32->CoUninitialize();
    }

    /**
     * Retrieves the registered ANSI class name for the specified window handle.
     *
     * @param mixed $hwnd Target window handle.
     * @return string Class name string (empty string on failure).
     */
    public function getWindowClass(mixed $hwnd)
    {
        $buf = $this->user32->new('char[256]');
        $this->user32->GetClassNameA($hwnd, $buf, 256);
        return FFI::string($buf);
    }

    /**
     * Retrieves the text content of a window control via WM_GETTEXT.
     *
     * @param mixed $hwnd Target window or control handle.
     * @return string Control text, or empty string if the control is empty or unavailable.
     */
    public function getText(mixed $hwnd): string
    {
        $len = $this->user32->SendMessageA($hwnd, WindowMessage::WM_GETTEXTLENGTH, 0, 0);
        if ($len <= 0) {
            return '';
        }
        $buf = $this->user32->new('char[' . ($len + 1) . ']');
        $this->user32->SendMessageA($hwnd, WindowMessage::WM_GETTEXT, $len + 1, $buf);
        return FFI::string($buf);
    }

    /**
     * Sets the text content of a window control via WM_SETTEXT.
     *
     * @param mixed  $hwnd Target window or control handle.
     * @param string $text New text content.
     */
    public function setText(mixed $hwnd, string $text): void
    {
        $buf = $this->user32->new('char[' . (strlen($text) + 1) . ']');
        FFI::memcpy($buf, $text, strlen($text));
        $this->user32->SendMessageA($hwnd, WindowMessage::WM_SETTEXT, 0, $buf);
    }

    /**
     * Replace a selected range in an Edit/RichEdit control.
     *
     * Uses EM_SETSEL then EM_REPLACESEL, which is the common way to modify the text
     * of standard Windows edit controls.
     *
     * @param mixed $hwnd Target edit control handle.
     * @param int $start Selection start (0-based). Use 0 to start at the beginning.
     * @param int $end Selection end. Use -1 to select to the end.
     * @param string $text Replacement text (ANSI).
     */
    public function replaceRangeText(mixed $hwnd, int $start, int $end, string $text): void
    {
        $this->user32->SendMessageA($hwnd, MessageControl::EM_SETSEL, $start, $end);
        $buf = $this->user32->new('char[' . (strlen($text) + 1) . ']');
        FFI::memcpy($buf, $text, strlen($text));
        $this->user32->SendMessageA($hwnd, MessageControl::EM_REPLACESEL, 1, $buf);
    }

    /**
     * Convenience helper to clear a control and set new text.
     *
     * @param mixed $hwnd Target edit control handle.
     * @param string $text New text to set.
     */
    public function clearAndSet(mixed $hwnd, string $text): void
    {
        $this->replaceRangeText($hwnd, 0, -1, $text);
    }

    /**
     * Get the current selection range of an Edit/RichEdit control.
     *
     * EM_GETSEL returns start/end packed into the low/high 16 bits.
     *
     * @param mixed $hwnd Target edit control handle.
     * @return array{start:int,end:int}
     */
    public function getSelection(mixed $hwnd): array
    {
        $result = $this->user32->SendMessageA($hwnd, MessageControl::EM_GETSEL, 0, 0);
        return [
            'start' => $result & 0xFFFF,
            'end' => ($result >> 16) & 0xFFFF,
        ];
    }

    /**
     * Get the number of lines in an Edit/RichEdit control.
     *
     * @param mixed $hwnd Target edit control handle.
     * @return int Line count.
     */
    public function getLineCount(mixed $hwnd): int
    {
        return (int) $this->user32->SendMessageA($hwnd, MessageControl::EM_GETLINECOUNT, 0, 0);
    }

    /**
     * Programmatically click a button control.
     *
     * @param mixed $hwnd Target button handle.
     */
    public function clickButton(mixed $hwnd): void
    {
        $this->user32->SendMessageA($hwnd, MessageControl::BM_CLICK, 0, 0);
    }

    /**
     * Best-effort "is this HWND null/invalid" check for mixed handle values.
     *
     * Some FFI calls may return null or non-CData values depending on the binding;
     * this helper normalizes the check used by the window-finding helpers.
     *
     * @param mixed $hwnd Candidate window handle.
     * @return bool True if null/zero/uncastable.
     */
    private function isNullHwnd(mixed $hwnd): bool
    {
        if ($hwnd === null) {
            return true;
        }

        try {
            return $this->user32->cast('intptr_t', $hwnd)->cdata === 0;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * Find all child Edit controls under a parent window.
     *
     * @param mixed $parentHwnd Parent window handle.
     * @return array<int, mixed> List of child handles (order: FindWindowEx iteration order).
     */
    public function findEditControls(mixed $parentHwnd): array
    {
        $found = [];
        $prev = null;

        while (true) {
            $hwnd = $this->user32->FindWindowExA($parentHwnd, $prev, 'Edit', null);
            if ($this->isNullHwnd($hwnd)) {
                break;
            }
            $found[] = $hwnd;
            $prev = $hwnd;
        }

        return $found;
    }

    /**
     * Find the first child window by class name.
     *
     * @param mixed $parentHwnd Parent window handle.
     * @param string $className Child window class name (e.g. "Edit", "Button").
     * @return mixed|null Child window handle or null if not found.
     */
    public function findChildByClass(mixed $parentHwnd, string $className): mixed
    {
        $hwnd = $this->user32->FindWindowExA($parentHwnd, null, $className, null);
        return $this->isNullHwnd($hwnd) ? null : $hwnd;
    }

    /**
     * Find a top-level window by its title (caption).
     *
     * @param string $title Exact window title.
     * @return mixed|null Window handle or null if not found.
     */
    public function findWindowByTitle(string $title): mixed
    {
        $hwnd = $this->user32->FindWindowA(null, $title);
        return $this->isNullHwnd($hwnd) ? null : $hwnd;
    }

    /**
     * Find a top-level window by its class name.
     *
     * @param string $className Window class name.
     * @return mixed|null Window handle or null if not found.
     */
    public function findWindowByClass(string $className): mixed
    {
        $hwnd = $this->user32->FindWindowA($className, null);
        return $this->isNullHwnd($hwnd) ? null : $hwnd;
    }

    /**
     * Resolves a PID from either an integer or an exe name like "game.exe".
     * 
     * @param string|int $v PID as integer or process name.
     * @return int
     */
    private function resolvePid(string|int $v): int
    {
        if (ctype_digit((string) $v)) {
            return (int) $v;
        }
        $pid = $this->findPidByName($v);
        if ($pid === null) {
            throw new Exception("Process \"{$v}\" not found. Is it running?");
        }
        return $pid;
    }

    /**
     * Reads text from a window control cross-process via WM_GETTEXT.
     * Works on standard Edit, RichEdit, and any control that handles WM_GETTEXT.
     * Returns empty string for controls >1 MB (safety guard).
     * 
     * @param mixed $hwnd
     * @return string
     */
    private function readWindowText(mixed $hwnd): string
    {
        $len = (int) $this->user32->SendMessageA($hwnd, WindowMessage::WM_GETTEXTLENGTH, 0, 0);
        if ($len <= 0 || $len >= 1_048_576) {
            return '';
        }

        $buf = $this->user32->new('char[' . ($len + 2) . ']');
        $got = $this->user32->SendMessageA($hwnd, WindowMessage::WM_GETTEXT, $len + 1, $buf);
        return $got > 0 ? FFI::string($buf, $got) : '';
    }

    /**
     * Writes text into a window control, auto-detecting Unicode vs ANSI.
     * Uses WM_SETTEXT (0x000C) — works on Edit, RichEdit, and custom controls
     * that handle WM_SETTEXT. Does NOT work on GDI-rendered VN text overlays.
     * 
     * @param mixed $hwnd
     * @param string $text
     * @return void
     */
    public function injectTextToWindow(mixed $hwnd, string $text): void
    {
        if ((bool) $this->user32->IsWindowUnicode($hwnd)) {
            $utf16 = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8') . "\x00\x00";
            $n = mb_strlen($text, 'UTF-8') + 1;
            $buf = $this->user32->new("WCHAR[$n]");
            FFI::memcpy($buf, $utf16, strlen($utf16));
            $this->user32->SendMessageW($hwnd, WindowMessage::WM_SETTEXT, 0, $buf);
        } else {
            $n = strlen($text) + 1;
            $buf = $this->user32->new("char[$n]");
            FFI::memcpy($buf, $text, strlen($text));
            $this->user32->SendMessageA($hwnd, WindowMessage::WM_SETTEXT, 0, $buf);
        }
    }

    /**
     * Enumerates child windows of a process matching class name and/or ctrl ID.
     * Call once to build the target list; results are cached by the caller.
     *
     * @param int $targetPid
     * @param string $filterClass
     * @param ?int $filterCtrlId
     * 
     * @return list<mixed>  Array of HWND CData values
     */
    private function discoverTargetWindows(int $targetPid, string $filterClass, ?int $filterCtrlId): array
    {
        $targets = [];
        $pidRef = $this->kernel32->new('DWORD');
        $continueEnum = $this->user32->new('bool');
        $continueEnum->cdata = true;

        $enumChild = function (mixed $child, mixed $lParam) use ($filterClass, $filterCtrlId, &$targets, $continueEnum): mixed {
            if ($filterClass !== '') {
                $cb = $this->user32->new('WCHAR[256]');
                $cl = $this->user32->GetClassNameW($child, $cb, 256);
                $cls = $cl > 0 ? parent::wideToPhp($cb, $cl) : '';
                if (stripos($cls, $filterClass) === false) {
                    return FFI::addr($continueEnum);
                }
            }
            if ($filterCtrlId !== null && $this->user32->GetDlgCtrlID($child) !== $filterCtrlId) {
                return FFI::addr($continueEnum);
            }
            $targets[] = $child;
            return FFI::addr($continueEnum);
        };

        $enumTop = function (mixed $hwnd, mixed $lParam) use ($targetPid, $enumChild, &$targets, &$pidRef, $continueEnum): mixed {
            $pidRef->cdata = 0;
            $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pidRef));
            if ((int) $pidRef->cdata !== $targetPid) {
                return FFI::addr($continueEnum);
            }
            $childLParam = $this->user32->new('bool');
            $this->user32->EnumChildWindows($hwnd, $enumChild, FFI::addr($childLParam));
            return FFI::addr($continueEnum);
        };

        $topLParam = $this->user32->new('bool');
        $this->user32->EnumWindows($enumTop, FFI::addr($topLParam));
        return $targets;
    }

    /**
     * Hooks WH_MOUSE_LL to watch for left-clicks on the VN window.
     * After each click, waits $clickDelayMs for the engine to update its text,
     * then reads every matching control, passes the text to $textTransformer,
     * and injects the returned string back into the same control.
     *
     * This approach works for VN engines that do NOT fire Win32 accessibility
     * events (KiriKiri, Artemis, ONScripter, many Unity/custom engines).
     * For standard Win32 Edit controls use watchAndInjectText() instead.
     *
     * Transformer signature:
     *   fn(string $originalText, array $ctx): string|null|false
     *   - string  → inject this text
     *   - null    → skip (keep original)
     *   - false   → stop the hook loop
     *
     * @param string|int $pidOrProcessName  PID or exe name, e.g. "game.exe"
     * @param Closure(string,array{hwnd_key: string, ctrl_id: string}):void $textTransformer   Text transform / translation callback
     * @param string     $filterClass       Window class to match, e.g. "" = all
     * @param int|null   $filterCtrlId      Control ID from getElementIds(), or null
     * @param int        $clickDelayMs      Ms to wait after click before reading
     * @return void
     *
     * @throws \Exception
     */
    public function watchClickAndInjectText(string|int $pidOrProcessName, Closure $textTransformer, string $filterClass = '', ?int $filterCtrlId = null, int $clickDelayMs = 80): void
    {
        $targetPid = $this->resolvePid($pidOrProcessName);

        // Lazily discovered target HWNDs — re-enumerated if the list empties
        $targets = $this->discoverTargetWindows($targetPid, $filterClass, $filterCtrlId);

        // hwndKey → text we last injected (skip re-triggering our own write)
        $injectedTexts = [];
        // hwndKey → last game-originated text (skip unchanged)
        $gameTexts = [];

        $TIMER_ID = 3001;
        $actualTimerId = 0;  // real ID returned by SetTimer(NULL, ...) — differs from $TIMER_ID
        $pending = false; // true when a click was detected and timer is armed

        // Shared flag — set from CTRL+C handler (runs on a different thread).
        // The main-thread pump checks this on every iteration.
        // This avoids calling any FFI function from the signal-handler thread,
        // which deadlocks PHP's internal FFI state.
        $stopFlag = false;

        // Reads all target controls, transforms, and injects.
        $processRead = function () use (&$targets, &$injectedTexts, &$gameTexts, $textTransformer, $targetPid, $filterClass, $filterCtrlId): void {
            // Re-discover if window list changed (e.g. scene transition)
            if (empty($targets)) {
                $targets = $this->discoverTargetWindows($targetPid, $filterClass, $filterCtrlId);
            }

            foreach ($targets as $hwnd) {
                $key = $this->hwndKey($hwnd);
                $curText = $this->readWindowText($hwnd);

                // Skip text that we ourselves wrote — prevents injection loops
                if (isset($injectedTexts[$key]) && $injectedTexts[$key] === $curText) {
                    continue;
                }

                // Skip genuinely unchanged text (player clicked same line twice)
                if (isset($gameTexts[$key]) && $gameTexts[$key] === $curText) {
                    continue;
                }
                $gameTexts[$key] = $curText;

                $replacement = $textTransformer($curText, [
                    'hwnd_key' => $key,
                    'ctrl_id' => $this->user32->GetDlgCtrlID($hwnd),
                ]);

                if ($replacement === false) {
                    // Caller requested a clean stop.
                    // PostQuitMessage is safe from the same thread — it sets an
                    // internal flag that makes PeekMessageW/GetMessageW return 0.
                    // Unlike PostThreadMessageW(GetCurrentThreadId(), WM_QUIT, …),
                    // it needs no thread-ID lookup and cannot deadlock.
                    $this->user32->PostQuitMessage(0);
                    return;
                }

                if (is_string($replacement) && $replacement !== $curText) {
                    $injectedTexts[$key] = $replacement;
                    $this->injectTextToWindow($hwnd, $replacement);
                }
            }
        };

        // WH_MOUSE_LL callback.
        // The low-level hook runs on the installing thread (our thread) during
        // message dispatch. We ONLY set a flag here — the pump handles the
        // delay via SetTimer and processes the read on WM_TIMER.
        //
        // No PostThreadMessageW needed: SetTimer already causes the system to
        // post WM_TIMER into our queue, which wakes PeekMessageW/GetMessageW.
        $mouseCallback = function (int $nCode, int $wParam, int $lParam) use (&$pending, $TIMER_ID, $clickDelayMs, &$actualTimerId): int {
            if ($nCode >= 0 && $wParam === 0x0201) {
                // Arm a one-shot timer.  SetTimer is safe from low-level hooks.
                // When $clickDelayMs elapses the system posts WM_TIMER into our
                // thread queue — the pump picks it up and calls $processRead().
                $actualTimerId = (int) $this->user32->SetTimer(null, $TIMER_ID, $clickDelayMs, null);
                $pending = true;
            }
            return $this->user32->CallNextHookEx(null, $nCode, $wParam, $lParam);
        };

        $hHook = $this->user32->SetWindowsHookExW(WindowHook::WH_MOUSE_LL, $mouseCallback, null, 0);

        // Validate hook handle
        $hookVal = $this->user32->new('int64_t');
        $to = FFI::addr($hookVal);
        $from = FFI::addr($hHook);
        FFI::memcpy($to, $from, 8);
        if ($hookVal->cdata === 0) {
            throw new Exception('SetWindowsHookExW (mouse) failed.');
        }

        $ctrlHandler = function () use (&$stopFlag): bool {
            $stopFlag = true;
            return true;
        };
        ServerAPI::setControlHandler($ctrlHandler, true);

        // ── Message pump ────────────────────────────────────────────
        // Uses PeekMessageW (non-blocking) so we can periodically
        // check $stopFlag even when no Win32 messages are pending.
        //
        // MsgWaitForMultipleObjects lets us sleep efficiently (up to
        // 50 ms) instead of busy-spinning when the queue is empty.
        $msg = $this->user32->new('MSG');
        while (!$stopFlag) {
            // PM_REMOVE = 0x0001
            $ret = $this->user32->PeekMessageW(FFI::addr($msg), null, 0, 0, 0x0001);
            if ($ret) {
                // WM_QUIT = 0x0012 — posted by PostQuitMessage()
                if ($msg->message === 0x0012) {
                    break;
                }

                // WM_TIMER = 0x0113 — one-shot: kill immediately, then process
                if ($msg->message === 0x0113 && (int) $msg->lParam === $actualTimerId && $pending) {
                    $this->user32->KillTimer(null, $actualTimerId); // make it one-shot
                    $actualTimerId = 0;
                    $pending = false;
                    $processRead();
                    continue; // processRead may have posted WM_QUIT — re-check
                }

                $this->user32->TranslateMessage(FFI::addr($msg));
                $this->user32->DispatchMessageW(FFI::addr($msg));
            } else {
                // No message available — yield CPU.
                $pointer = 0;
                $this->user32->MsgWaitForMultipleObjects(0, null, $this->user32->cast("bool*", $pointer), 50, QueryStatus::QS_ALLINPUT);
            }
        }

        // ── Cleanup ─────────────────────────────────────────────────
        if ($actualTimerId !== 0) {
            $this->user32->KillTimer(null, $actualTimerId);
        }
        $this->user32->UnhookWindowsHookEx($hHook);
    }

    /**
     * Event-driven variant using EVENT_OBJECT_VALUECHANGE (0x800E).
     * Use this when the VN uses standard Win32 Edit / RichEdit controls
     * that properly fire WinEvent accessibility notifications.
     * For most VN engines, watchClickAndInjectText() is more reliable.
     *
     * Transformer signature: same as watchClickAndInjectText().
     *
     * @param string|int $pidOrProcessName PID or exe name, e.g. "game.exe"
     * @param Closure(string,array{hwnd_key: string, ctrl_id: string, event_thread: string, event: string}):string|null|void $textTransformer
     * @param string $filterClass Window class to match, e.g. "" = all
     * @param ?int $filterCtrlId Control ID from getElementIds(), or null
     * @param int $eventMin  First event ID in range (default EVENT_OBJECT_VALUECHANGE)
     * @param int $eventMax  Last event ID in range  (default EVENT_OBJECT_VALUECHANGE)
     * @return void
     */
    public function watchAndInjectText(string|int $pidOrProcessName, Closure $textTransformer, string $filterClass = '', ?int $filterCtrlId = null, int $eventMin = WindowEvent::EVENT_MIN, int $eventMax = WindowEvent::EVENT_MAX): void
    {
        $targetPid = $this->resolvePid($pidOrProcessName);
        $injectedTexts = [];
        $gameTexts = [];
        $pidRef = $this->kernel32->new('DWORD');

        // Shared flag — set by CTRL+C handler (runs on a different thread),
        // checked by the message pump on the main thread.
        // This avoids calling FFI functions (PostThreadMessageW) from the
        // signal-handler thread, which deadlocks PHP's internal FFI state.
        $stopFlag = false;

        $callback = function (mixed $hHook, int $event, mixed $hwnd, int $idObject, int $idChild, int $eventThread, int $eventTime) use (&$injectedTexts, &$gameTexts, $targetPid, $filterClass, $filterCtrlId, $textTransformer, &$pidRef): void {
            // Ignore sub-element events (tooltips, menu items, etc.)
            if ($idObject !== 0 || $idChild !== 0) {
                return;
            }

            $key = $this->hwndKey($hwnd);
            if ($key === 0) {
                return;
            }

            // PID gate
            $pidRef->cdata = 0;
            $this->user32->GetWindowThreadProcessId($hwnd, FFI::addr($pidRef));
            if ((int) $pidRef->cdata !== $targetPid) {
                return;
            }

            // Class gate
            if ($filterClass !== '') {
                $cb = $this->user32->new('WCHAR[256]');
                $cl = $this->user32->GetClassNameW($hwnd, $cb, 256);
                $cls = $cl > 0 ? parent::wideToPhp($cb, $cl) : '';
                if (stripos($cls, $filterClass) === false) {
                    return;
                }
            }

            // Ctrl ID gate
            if ($filterCtrlId !== null && $this->user32->GetDlgCtrlID($hwnd) !== $filterCtrlId) {
                return;
            }

            $curText = $this->readWindowText($hwnd);

            // Ignore our own injections (prevents VALUECHANGE→inject→VALUECHANGE loop)
            if (isset($injectedTexts[$key]) && $injectedTexts[$key] === $curText) {
                return;
            }

            if (isset($gameTexts[$key]) && $gameTexts[$key] === $curText) {
                return;
            }
            $gameTexts[$key] = $curText;

            $replacement = $textTransformer($curText, [
                'hwnd_key' => $key,
                'ctrl_id' => $this->user32->GetDlgCtrlID($hwnd),
                'event' => $event,
                'event_thread' => $eventThread,
            ]);

            if ($replacement === false) {
                $this->user32->PostQuitMessage(0);
                return;
            }

            if (is_string($replacement) && $replacement !== $curText) {
                $injectedTexts[$key] = $replacement;
                $this->injectTextToWindow($hwnd, $replacement);
            }
        };

        $hHook = $this->user32->SetWinEventHook($eventMin, $eventMax, null, $callback, $targetPid, 0, WindowEventHook::WINEVENT_OUTOFCONTEXT);

        $hookVal = $this->user32->new('int64_t');
        $to = FFI::addr($hookVal);
        $from = FFI::addr($hHook);
        FFI::memcpy($to, $from, 8);
        if ($hookVal->cdata === 0) {
            throw new Exception('SetWinEventHook failed.');
        }

        ServerAPI::setControlHandler(function () use (&$stopFlag): bool {
            $stopFlag = true;
            return true;
        }, true);

        // ── Message pump ────────────────────────────────────────────
        // Uses PeekMessageW (non-blocking) so we can periodically
        // check $stopFlag even when no Win32 messages are pending.
        //
        // MsgWaitForMultipleObjects lets us sleep efficiently (up to
        // 50 ms) instead of busy-spinning when the queue is empty.
        $msg = $this->user32->new('MSG');
        while (!$stopFlag) {
            // PM_REMOVE = 0x0001
            $ret = $this->user32->PeekMessageW(FFI::addr($msg), null, 0, 0, 0x0001);
            if ($ret) {
                // WM_QUIT = 0x0012 — posted by PostQuitMessage()
                if ($msg->message === 0x0012) {
                    break;
                }
                $this->user32->TranslateMessage(FFI::addr($msg));
                $this->user32->DispatchMessageW(FFI::addr($msg));
            } else {
                // No message available — yield CPU.
                $pointer = 0;
                $this->user32->MsgWaitForMultipleObjects(0, null, $this->user32->cast("bool*", $pointer), 50, QueryStatus::QS_ALLINPUT);
            }
        }

        $this->user32->UnhookWinEvent($hHook);
    }

    /**
     * Reads installed font names from the registry.
     * Returns an array of font names like ["Arial", "Times New Roman", …].
     * 
     * @return list<string>
     */
    public function getFontList(): array
    {
        $ERROR_SUCCESS = 0;
        $ERROR_NO_MORE_ITEMS = 259;

        $subKeyUtf16 = mb_convert_encoding("SOFTWARE\\Microsoft\\Windows NT\\CurrentVersion\\Fonts\0", 'UTF-16LE', 'UTF-8');
        $subKeyLen = strlen($subKeyUtf16);
        $subKeyBuf = $this->advapi32->new("uint16_t[$subKeyLen]");
        FFI::memcpy($subKeyBuf, $subKeyUtf16, $subKeyLen);

        $pointer = -2147483646;
        $hklm = $this->advapi32->cast('void*', $pointer);
        $hKey = $this->advapi32->new('HKEY');
        $hKeyPtr = FFI::addr($hKey);

        $ret = $this->advapi32->RegOpenKeyExW($hklm, $subKeyBuf, 0, Registry::KEY_READ->value, $hKeyPtr);
        if ($ret !== NULL) {
            throw new RuntimeException("RegOpenKeyExW failed with code: {$ret}");
        }

        $fonts = [];
        $index = 0;

        while (true) {
            $nameBuf = $this->advapi32->new('uint16_t[512]');
            $nameLen = $this->advapi32->new('uint32_t');
            $nameLen->cdata = 512;

            $dataBuf = $this->advapi32->new('uint8_t[1024]');
            $dataLen = $this->advapi32->new('uint32_t');
            $dataLen->cdata = 1024;

            $type = $this->advapi32->new('uint32_t');
            $res = $this->advapi32->RegEnumValueW($hKey, $index, $nameBuf, FFI::addr($nameLen), null, FFI::addr($type), $dataBuf, FFI::addr($dataLen));

            if ($res === $ERROR_NO_MORE_ITEMS) {
                break;
            }
            if ($res !== $ERROR_SUCCESS) {
                break;
            }

            $rawName = FFI::string($nameBuf, $nameLen->cdata * 2);
            $fontName = mb_convert_encoding($rawName, 'UTF-8', 'UTF-16LE');
            $fontName = preg_replace('/\s*\(.*?\)\s*$/', '', $fontName);

            $fonts[] = $fontName;
            $index++;
        }

        $this->advapi32->RegCloseKey($hKey);

        sort($fonts);
        return array_unique($fonts);
    }

    /**
     * Measures text dimensions using GDI. Returns width, height, ascent, descent, line height, and average char width.
     * 
     * @param  string $text       The text to measure (UTF-8)
     * @param  string $fontFamily Font face name, e.g. "Arial"
     * @param  int    $pointSize  Font size in points (e.g. 12)
     * @param  int    $weight     Font weight: 400 = normal, 700 = bold
     * @param  bool   $italic     Italic flag
     * @return array{
     *   width: int,
     *   height: int,
     *   ascent: int,
     *   descent: int,
     *   lineHeight: int,
     *   avgCharWidth: int,
     *   font: string,
     *   pointSize: int,
     *   text: string
     * }
     */
    public function measureText(string $text, string $fontFamily, int $pointSize = 12, int $weight = 400, bool $italic = false): array
    {
        $dpi = 96;
        $height = -(int) round($pointSize * $dpi / 72);

        $hdc = $this->gdi32->CreateCompatibleDC(null);
        $utf16Name = mb_convert_encoding($fontFamily . "\0", 'UTF-16LE', 'UTF-8');
        $nameLen = strlen($utf16Name);
        $nameBuf = $this->gdi32->new("uint16_t[$nameLen]");
        FFI::memcpy($nameBuf, $utf16Name, $nameLen);

        $hFont = $this->gdi32->CreateFontW($height, 0, 0, 0, $weight, $italic ? 1 : 0, 0, 0, 1, 0, 0, 5, 0, $nameBuf);

        $oldFont = $this->gdi32->SelectObject($hdc, $hFont);
        $size = $this->gdi32->new('SIZE');
        $utf16Text = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
        $charCount = mb_strlen($text, 'UTF-8');  // character count, not byte count

        $nameLen = strlen($utf16Text);
        $nameBuf = $this->gdi32->new("uint16_t[$nameLen]");
        FFI::memcpy($nameBuf, $utf16Text, $nameLen);

        $this->gdi32->GetTextExtentPoint32W($hdc, $nameBuf, $charCount, FFI::addr($size));
        $tm = $this->gdi32->new('TEXTMETRICW');
        $this->gdi32->GetTextMetricsW($hdc, FFI::addr($tm));
        $this->gdi32->SelectObject($hdc, $oldFont);
        $this->gdi32->DeleteObject($hFont);
        $this->gdi32->DeleteDC($hdc);

        return [
            'text' => $text,
            'font' => $fontFamily,
            'pointSize' => $pointSize,
            'weight' => $weight,
            'italic' => $italic,
            'width' => (int) $size->cx,   // total string width in pixels
            'height' => (int) $size->cy,   // string height in pixels (= tmHeight)
            'ascent' => (int) $tm->tmAscent,
            'descent' => (int) $tm->tmDescent,
            'lineHeight' => (int) ($tm->tmHeight + $tm->tmExternalLeading),
            'avgCharWidth' => (int) $tm->tmAveCharWidth,
        ];
    }

    /**
     * Toggles the Start Menu by simulating a press of the Windows key (VK_LWIN).
     * This is a simple and universal method that works regardless of Start Menu implementation.
     * 
     * @return void
     */
    public function toggleStartMenu(): void
    {
        $this->user32->keybd_event(KeyboardEvent::VK_LWIN->value, 0, KeyboardEvent::KEYDOWN->value, 0);
        usleep(50000); // 50 ms
        $this->user32->keybd_event(KeyboardEvent::VK_LWIN->value, 0, KeyboardEvent::KEYUP->value, 0);
    }

    /**
     * Toggles the visibility of the Start Menu by finding its window and sending SW_SHOW or SW_HIDE.
     * Returns true if the Start Menu was visible before the toggle, false if it was hidden.
     * 
     * @return bool
     */
    public function showStartMenuWindow(): bool
    {
        // Win10 Start: "Windows.UI.Core.CoreWindow" titled "Start"
        $hWnd = $this->user32->FindWindowA("Windows.UI.Core.CoreWindow", "Start");
        if ($hWnd === null) {
            return false;
        }

        $visible = $this->user32->IsWindowVisible($hWnd);
        $this->user32->ShowWindow($hWnd, $visible ? ShowMessage::SW_HIDE : ShowMessage::SW_SHOW);
        $this->user32->SetForegroundWindow($hWnd);
        return $visible;
    }

    /**
     * Checks if a camera is available by attempting to connect to the default video capture driver.
     * Returns true if a camera is detected, false otherwise.
     * 
     * @return bool
     */
    public function isCameraExists(): bool
    {
        $hWnd = $this->avicap32->capCreateCaptureWindowA("probe", WindowStyle::WS_CHILD->value | WindowStyle::WS_VISIBLE->value, 0, 0, 320, 240, $this->user32->GetDesktopWindow(), 0);
        if ($hWnd === null) {
            return false;
        }

        $connected = $this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_DRIVER_CONNECT->value, 0, 0);
        $this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_DRIVER_DISCONNECT->value, 0, 0);
        $this->user32->DestroyWindow($hWnd);
        return (bool) $connected;
    }

    /**
     * Captures a single frame from the default camera and saves it as a BMP file.
     * Returns true on success, false on failure (e.g. no camera, permission issues, etc.).
     * 
     * @param string $outputPath Path to save the captured image, e.g. "capture.bmp"
     * @param int $driverIndex Camera driver index (0 = default camera)
     * @return bool
     */
    public function captureImage(string $outputPath = 'capture.bmp', int $driverIndex = 0): bool
    {
        $hWnd = $this->avicap32->capCreateCaptureWindowA("capture", WindowStyle::WS_CHILD->value | WindowStyle::WS_VISIBLE->value, 0, 0, 640, 480, $this->user32->GetDesktopWindow(), 0);
        if (!$hWnd) {
            return false;
        }

        // Connect to camera driver N (0 = first camera)
        if (!$this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_DRIVER_CONNECT->value, $driverIndex, 0)) {
            $this->user32->DestroyWindow($hWnd);
            return false;
        }

        sleep(1); // Let sensor warm up

        // Grab single frame into internal buffer
        $this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_GRAB_FRAME->value, 0, 0);

        // Save buffer to disk as BMP
        $pathPtr = $this->user32->new('char[260]');
        FFI::memcpy($pathPtr, $outputPath, strlen($outputPath));
        $pathAddr = FFI::addr($pathPtr);
        $ok = $this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_FILE_SAVEDIB->value, 0, $this->user32->cast('intptr_t', $pathAddr));

        $this->user32->SendMessageA($hWnd, WindowMessage::WM_CAP_DRIVER_DISCONNECT->value, 0, 0);
        $this->user32->DestroyWindow($hWnd);

        return (bool) $ok;
    }

    /**
     * Returns an array of taskbar window handles (HWNDs). Typically one for the main taskbar and one for the secondary taskbar on multi-monitor setups.
     * 
     * @return array
     */
    public function getTaskbarWindows(): array
    {
        $handles = [];

        $h = $this->user32->FindWindowA("Shell_TrayWnd", null);
        if ($h) {
            $handles[] = $h;
        }

        $h2 = $this->user32->FindWindowA("Shell_SecondaryTrayWnd", null);
        if ($h2) {
            $handles[] = $h2;
        }

        return $handles;
    }

    /**
     * Hides the taskbar by sending SW_HIDE to its windows.
     * 
     * @return void
     */
    public function hideTaskbar(): void
    {
        foreach ($this->getTaskbarWindows() as $hWnd) {
            $this->user32->ShowWindow($hWnd, ShowMessage::SW_HIDE);
        }
    }

    /**
     * Shows the taskbar if it was hidden. Does not check current visibility state.
     * 
     * @return void
     */
    public function showTaskbar(): void
    {
        foreach ($this->getTaskbarWindows() as $hWnd) {
            $this->user32->ShowWindow($hWnd, ShowMessage::SW_SHOW);
        }
    }

    /**
     * Toggles taskbar visibility. Returns true if the toggle was successful, false if the taskbar window couldn't be found.
     * 
     * @return bool
     */
    public function toggleTaskbar(): bool
    {
        $hWnd = $this->user32->FindWindowA("Shell_TrayWnd", null);
        if (!$hWnd) {
            return false;
        }
        $this->user32->IsWindowVisible($hWnd) ? $this->hideTaskbar() : $this->showTaskbar();

        return true;
    }

    /**
     * Returns an array of taskbar button info: ['title' => string, 'hwnd' => string].
     * Useful for identifying the game window among multiple taskbar buttons.
     * Navigates the taskbar's window hierarchy and enumerates visible buttons.
     * 
     * @return array{hwnd: string, title: string}[]
     */
    public function getTaskBarProgramList()
    {
        $taskButtons = [];

        // Navigate: Shell_TrayWnd > ReBarWindow32 > MSTaskSwWClass > MSTaskListWClass
        $hTray = $this->user32->FindWindowA("Shell_TrayWnd", null);
        $hRebar = $this->user32->FindWindowExA($hTray, null, "ReBarWindow32", null);
        $hTaskSw = $this->user32->FindWindowExA($hRebar, null, "MSTaskSwWClass", null);
        $hTaskList = $this->user32->FindWindowExA($hTaskSw, null, "MSTaskListWClass", null);

        if (!$hTaskList) {
            throw new Exception("Could not locate MSTaskListWClass\n");
        }

        $callback = function ($hWnd, $lParam) use (&$taskButtons): int {
            $classPtr = $this->user32->new('char[256]');
            $this->user32->GetClassNameA($hWnd, $classPtr, 256);
            $class = FFI::string($classPtr);

            // Task buttons use "Button" class inside MSTaskListWClass
            if ($class === 'Button' && $this->user32->IsWindowVisible($hWnd)) {
                $textPtr = $this->user32->new('char[512]');
                $this->user32->GetWindowTextA($hWnd, $textPtr, 512);
                $title = FFI::string($textPtr);
                if (strlen($title) > 0) {
                    $taskButtons[] = [
                        'title' => $title,
                        'hwnd' => (string) $hWnd,
                    ];
                }
            }
            return 1; // Return 1 = continue enumeration
        };

        // Cast closure to WNDENUMPROC and enumerate
        $pointer = $this->user32->new('void*');
        $cbPtr = $this->user32->cast($this->user32->type('void*'), $pointer);
        $this->user32->EnumChildWindows($hTaskList, $callback, 0);

        return $taskButtons;
    }

    // =========================================================================
    // CLIPBOARD OPERATIONS
    // Provides read, write, and clear access to the Windows clipboard via
    // the user32 OpenClipboard / GetClipboardData / SetClipboardData APIs.
    // =========================================================================

    /**
     * Reads plain text from the Windows clipboard.
     *
     * Opens the clipboard, requests a global memory handle for CF_TEXT (ANSI)
     * or CF_UNICODETEXT (UTF-16LE), locks the handle to get a raw pointer,
     * copies the string into PHP, then unlocks and closes the clipboard.
     *
     * @param bool $unicode When true, reads as CF_UNICODETEXT and converts
     *                      the UTF-16LE payload to UTF-8. Defaults to false
     *                      (ANSI / CF_TEXT).
     * @return string|null  The clipboard text on success, or null if the
     *                      clipboard is empty or does not contain text.
     */
    public function getClipboardTextW(bool $unicode = false): ?string
    {
        // Open the clipboard; pass null as the owner window handle.
        if (!$this->user32->OpenClipboard(null)) {
            return null;
        }

        $format = $unicode ? self::CF_UNICODETEXT : self::CF_TEXT;

        // Retrieve the memory handle for the requested clipboard format.
        $hMem = $this->user32->GetClipboardData($format);
        if (!$hMem) {
            $this->user32->CloseClipboard();
            return null;
        }

        // Lock the global memory handle to get a usable pointer.
        $ptr = $this->kernel32->GlobalLock($hMem);
        if (!$ptr) {
            $this->user32->CloseClipboard();
            return null;
        }

        // Determine the byte-length of the null-terminated string.
        $size = $this->kernel32->GlobalSize($hMem);

        // Copy the raw bytes into a PHP string.
        $raw = FFI::string($ptr, max(0, $size - ($unicode ? 2 : 1)));

        $this->kernel32->GlobalUnlock($hMem);
        $this->user32->CloseClipboard();

        // Convert UTF-16LE to UTF-8 when reading the Unicode format.
        return $unicode
            ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE')
            : $raw;
    }

    /**
     * Writes plain text to the Windows clipboard.
     *
     * Opens the clipboard, empties it, allocates moveable global memory,
     * copies the text into that memory, and sets it as clipboard data.
     * The system takes ownership of the allocated memory on success.
     *
     * @param string $text    The text to place on the clipboard.
     * @param bool   $unicode When true, converts the input to UTF-16LE and
     *                        uses CF_UNICODETEXT. Defaults to false (ANSI).
     * @return bool           True on success, false on any Win32 failure.
     */
    public function setClipboardTextW(string $text, bool $unicode = false): bool
    {
        if (!$this->user32->OpenClipboard(null)) {
            return false;
        }

        // Clear whatever is currently on the clipboard.
        $this->user32->EmptyClipboard();

        // Encode text and calculate required byte length (including null terminator).
        if ($unicode) {
            $encoded = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
            $byteLen = strlen($encoded) + 2; // UTF-16LE null = 2 bytes
        } else {
            $encoded = $text;
            $byteLen = strlen($encoded) + 1; // ANSI null = 1 byte
        }

        // Allocate moveable, zero-initialised global memory.
        $hMem = $this->kernel32->GlobalAlloc(self::GMEM_MOVEABLE | self::GMEM_ZEROINIT, $byteLen);
        if (!$hMem) {
            $this->user32->CloseClipboard();
            return false;
        }

        // Lock the handle to write the text bytes into the buffer.
        $ptr = $this->kernel32->GlobalLock($hMem);
        if (!$ptr) {
            $this->kernel32->GlobalFree($hMem);
            $this->user32->CloseClipboard();
            return false;
        }

        FFI::memcpy($ptr, $encoded, strlen($encoded));
        $this->kernel32->GlobalUnlock($hMem);

        $format = $unicode ? self::CF_UNICODETEXT : self::CF_TEXT;
        $result = $this->user32->SetClipboardData($format, $hMem);

        $this->user32->CloseClipboard();

        // If SetClipboardData fails the system did NOT take ownership, so free.
        if (!$result) {
            $this->kernel32->GlobalFree($hMem);
            return false;
        }

        return true;
    }

    /**
     * Clears all content currently held on the Windows clipboard.
     *
     * Opens the clipboard, calls EmptyClipboard to release all data and
     * free associated handles, then closes it.
     *
     * @return bool True if the clipboard was successfully emptied, false otherwise.
     */
    public function clearClipboard(): bool
    {
        if (!$this->user32->OpenClipboard(null)) {
            return false;
        }

        $result = (bool) $this->user32->EmptyClipboard();
        $this->user32->CloseClipboard();

        return $result;
    }

    /**
     * Moves the cursor to (x, y) then fires a single left-click.
     *
     * Internally calls moveMouse() for coordinate scaling, then fires
     * MOUSEEVENTF_LEFTDOWN followed by MOUSEEVENTF_LEFTUP with a brief
     * pause between them to ensure reliable event delivery.
     *
     * @param int $x Target x-coordinate in screen pixels.
     * @param int $y Target y-coordinate in screen pixels.
     * @return void
     */
    public function leftClickAt(int $x, int $y): void
    {
        $this->moveMouse($x, $y);
        usleep(30000); // 30 ms — let the cursor settle before clicking
        $this->clickAndReleaseLeftMouse();
    }

    /**
     * Moves the cursor to (x, y) then fires a single right-click.
     *
     * Useful for programmatically opening context menus at a known position.
     *
     * @param int $x Target x-coordinate in screen pixels.
     * @param int $y Target y-coordinate in screen pixels.
     * @return void
     */
    public function rightClickAt(int $x, int $y): void
    {
        $this->moveMouse($x, $y);
        usleep(30000);
        $this->clickAndReleaseRightMouse();
    }

    /**
     * Simulates a double left-click at the current cursor position.
     *
     * Sends two complete press+release cycles separated by the system
     * double-click interval so the target window registers a WM_LBUTTONDBLCLK
     * event rather than two independent single-clicks.
     *
     * @param int $delayUs Delay in microseconds between the two click cycles.
     *                     Defaults to 100 000 µs (100 ms), which is safely
     *                     within the default 500 ms double-click threshold.
     * @return void
     */
    public function doubleClick(int $delayUs = 100000): void
    {
        $this->clickAndReleaseLeftMouse();
        usleep($delayUs);
        $this->clickAndReleaseLeftMouse();
    }

    /**
     * Moves the cursor to (x, y) and performs a double left-click.
     *
     * Convenience wrapper that combines moveMouse() with doubleClick().
     *
     * @param int $x        Target x-coordinate in screen pixels.
     * @param int $y        Target y-coordinate in screen pixels.
     * @param int $delayUs  Inter-click delay in microseconds (default 100 ms).
     * @return void
     */
    public function doubleClickAt(int $x, int $y, int $delayUs = 100000): void
    {
        $this->moveMouse($x, $y);
        usleep(30000);
        $this->doubleClick($delayUs);
    }

    /**
     * Retrieves the RGB colour of a single pixel on the screen.
     *
     * Obtains the desktop device context, calls GDI GetPixel, then releases
     * the DC and decomposes the COLORREF value into its R, G, B components.
     *
     * @param int $x X-coordinate of the pixel (screen coordinates).
     * @param int $y Y-coordinate of the pixel (screen coordinates).
     * @return array{r: int, g: int, b: int}|false Colour components on success,
     *                                               false if GetPixel returns CLR_INVALID.
     */
    public function getDesktopPixelColor(int $x, int $y): array|false
    {
        $hWnd = $this->user32->GetDesktopWindow();
        $hDC = $this->user32->GetDC($hWnd);

        // GetPixel returns 0xBBGGRR (little-endian COLORREF).
        $colorRef = $this->gdi32->GetPixel($hDC, $x, $y);
        $this->user32->ReleaseDC($hWnd, $hDC);

        // 0xFFFFFFFF (CLR_INVALID) means the coordinates are out of range.
        if ($colorRef === 0xFFFFFFFF) {
            return false;
        }

        return [
            'r' => $colorRef & 0xFF,
            'g' => ($colorRef >> 8) & 0xFF,
            'b' => ($colorRef >> 16) & 0xFF,
        ];
    }

    /**
     * Returns the title of the window that currently has keyboard focus.
     *
     * Combines GetForegroundWindow and GetWindowTextA into a single convenient
     * call that returns a plain PHP string instead of a CData handle.
     *
     * @return string|null Window title, or null if no foreground window exists
     *                     or if the window has no title text.
     */
    public function getActiveWindowTitle(): ?string
    {
        $hWnd = $this->user32->GetForegroundWindow();
        if (!$hWnd) {
            return null;
        }

        $buffer = $this->user32->new("char[512]");
        $length = $this->user32->GetWindowTextA($hWnd, $buffer, 512);

        return $length > 0 ? FFI::string($buffer, $length) : null;
    }

    /**
     * Enumerates all top-level windows that are currently visible on screen.
     *
     * Uses EnumWindows with an inline callback to collect every top-level
     * window that passes IsWindowVisible and has a non-empty title. Returns
     * an array of maps keyed by 'title' and 'hwnd'.
     *
     * @return array<int, array{title: string, hwnd: string}> List of visible windows.
     */
    public function getAllVisibleWindows(): array
    {
        $windows = [];

        $callback = function ($hWnd, $lParam) use (&$windows): int {
            // Skip windows that are not visible to the user.
            if (!$this->user32->IsWindowVisible($hWnd)) {
                return 1; // 1 = continue enumeration
            }

            $buf = $this->user32->new("char[512]");
            $len = $this->user32->GetWindowTextA($hWnd, $buf, 512);

            if ($len > 0) {
                $windows[] = [
                    'title' => FFI::string($buf, $len),
                    'hwnd' => (string) $hWnd,
                ];
            }

            return 1; // Continue enumeration
        };

        $this->user32->EnumWindows($callback, 0);

        return $windows;
    }

    /**
     * Checks whether a window is currently minimised (iconified).
     *
     * Wraps the IsIconic() user32 function, which returns non-zero when the
     * window is in its minimised state.
     *
     * @param CData $hwnd Handle to the window to test.
     * @return bool True if minimised, false otherwise.
     */
    public function isWindowMinimized(CData $hwnd): bool
    {
        return (bool) $this->user32->IsIconic($hwnd);
    }

    /**
     * Checks whether a window is currently maximised (zoomed).
     *
     * Wraps the IsZoomed() user32 function, which returns non-zero when the
     * window occupies the entire work area of its monitor.
     *
     * @param CData $hwnd Handle to the window to test.
     * @return bool True if maximised, false otherwise.
     */
    public function isWindowMaximized(CData $hwnd): bool
    {
        return (bool) $this->user32->IsZoomed($hwnd);
    }

    /**
     * Minimises every open application window, revealing the desktop.
     *
     * Sends SW_MINIMIZE to every top-level window returned by getAllVisibleWindows().
     * To restore, the user can click the taskbar buttons or call restoreAllWindows().
     *
     * @return void
     */
    public function minimizeAllWindows(): void
    {
        foreach ($this->getAllVisibleWindows() as $info) {
            $hWnd = $this->user32->FindWindowA(null, $info['title']);
            if ($hWnd) {
                $this->user32->ShowWindow($hWnd, ShowMessage::SW_SHOWMINIMIZED);
            }
        }
    }

    /**
     * Returns the uptime formatted as a human-readable string.
     *
     * Converts the raw millisecond value from getSystemUptime() into
     * days, hours, minutes, and seconds for easy display.
     *
     * @return string Formatted uptime string, e.g. "3d 4h 12m 07s".
     */
    public function getUptimeFormatted(): string
    {
        $ms = $this->getSystemUptime();
        $seconds = intdiv($ms, 1000);
        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $days = intdiv($hours, 24);

        return sprintf('%dd %dh %02dm %02ds', $days, $hours % 24, $minutes % 60, $seconds % 60);
    }

    /**
     * Retrieves the duration in milliseconds that the user has been idle
     * (i.e., no mouse movement, keyboard input, or touch events).
     *
     * Calls GetLastInputInfo, which stores the tick count of the most recent
     * input event in an LASTINPUTINFO struct. The idle time is the difference
     * between the current tick count and that stored value.
     *
     * @return int|false Idle time in milliseconds, or false on failure.
     */
    public function getUserIdleTime(): int|false
    {
        // LASTINPUTINFO: { cbSize: UINT, dwTime: DWORD }
        $lii = $this->user32->new("LASTINPUTINFO");
        $lii->cbSize = FFI::sizeof($lii);

        if (!$this->user32->GetLastInputInfo(FFI::addr($lii))) {
            return false;
        }

        $now = (int) $this->kernel32->GetTickCount();
        $last = (int) $lii->dwTime;

        // GetTickCount wraps at 2^32 ms (~49.7 days); handle the wrap case.
        $idle = ($now >= $last) ? ($now - $last) : (0xFFFFFFFF - $last + $now + 1);

        return $idle;
    }

    /**
     * Reads the entire content of a file into a PHP string.
     *
     * Opens the file with GENERIC_READ via kernel32 CreateFileA, reads all
     * bytes in 4 KiB chunks, then closes the handle. Returns null if the file
     * cannot be opened or if any read operation fails.
     *
     * @param string $filePath Absolute or relative path to the file.
     * @return string|null File content on success, null on any failure.
     */
    public function readFile(string $filePath): ?string
    {
        $hFile = $this->kernel32->CreateFileA($filePath, self::GENERIC_READ, self::FILE_SHARE_READ, null, self::OPEN_EXISTING, self::FILE_ATTRIBUTE_NORMAL, null);
        if (!$hFile) { // INVALID_HANDLE_VALUE is -1 cast to HANDLE (a signed pointer-sized int).
            return null;
        }

        $content = '';
        $chunkSize = 4096;
        $buf = $this->kernel32->new("char[$chunkSize]");
        $bytesRead = $this->kernel32->new("DWORD");

        // Read the file in 4 KiB chunks until EOF.
        while (true) {
            $ok = $this->kernel32->ReadFile($hFile, $buf, $chunkSize, FFI::addr($bytesRead), null);
            if (!$ok || (int) $bytesRead->cdata === 0) {
                break; // EOF reached or error
            }
            $content .= FFI::string($buf, (int) $bytesRead->cdata);
        }

        $this->kernel32->CloseHandle($hFile);

        return $content;
    }

    /**
     * Writes a string (REG_SZ) value to the Windows registry.
     *
     * Opens (or creates) the specified key with KEY_SET_VALUE access, then
     * calls RegSetValueExA to store the value. The key is closed regardless
     * of success or failure.
     *
     * @param int    $rootKey   One of the predefined root key constants:
     *                          0x80000000 HKEY_CLASSES_ROOT
     *                          0x80000001 HKEY_CURRENT_USER
     *                          0x80000002 HKEY_LOCAL_MACHINE
     *                          0x80000003 HKEY_USERS
     * @param string $subKey    Registry path relative to $rootKey, e.g.
     *                          "Software\MyApp\Settings".
     * @param string $valueName Name of the value to write.
     * @param string $data      String data to store (REG_SZ).
     * @return bool True on success, false on any Win32 error.
     */
    public function writeRegistryValue(int $rootKey, string $subKey, string $valueName, string $data): bool
    {
        $hKeyArr = $this->advapi32->new("HKEY[1]");
        $rootPtr = $this->advapi32->cast("void*", $rootKey);

        // KEY_SET_VALUE = 0x0002; KEY_CREATE_SUB_KEY = 0x0004
        $access = 0x0002 | 0x0004;

        // RegCreateKeyExA creates the key if it does not already exist.
        $ret = $this->advapi32->RegCreateKeyExA($rootPtr, $subKey, 0, null, RegistryOption::REG_OPTION_NON_VOLATILE->value, $access, null, $hKeyArr, null);
        if ($ret !== 0) {
            return false;
        }

        $hKey = $hKeyArr[0];
        $len = strlen($data) + 1; // Include null terminator
        $dataBuf = $this->advapi32->new("char[$len]");
        FFI::memcpy($dataBuf, $data, strlen($data));

        // REG_SZ = 1
        $result = $this->advapi32->RegSetValueExA($hKey, $valueName, 0, 1, $dataBuf, $len);

        $this->advapi32->RegCloseKey($hKey);

        return $result === 0;
    }

    /**
     * Deletes a named value from a registry key.
     *
     * Opens the key with KEY_SET_VALUE access and calls RegDeleteValueA.
     * The key itself is not removed; only the named value entry is deleted.
     *
     * @param int    $rootKey   Predefined root key constant (see writeRegistryValue).
     * @param string $subKey    Registry path relative to $rootKey.
     * @param string $valueName Name of the value to delete.
     * @return bool True on success, false if the key or value does not exist
     *              or the caller lacks sufficient permissions.
     */
    public function deleteRegistryValue(int $rootKey, string $subKey, string $valueName): bool
    {
        $hKeyArr = $this->advapi32->new("HKEY[1]");
        $rootPtr = $this->advapi32->cast("void*", $rootKey);

        if ($this->advapi32->RegOpenKeyExA($rootPtr, $subKey, 0, 0x0002, $hKeyArr) !== 0) {
            return false;
        }

        $hKey = $hKeyArr[0];
        $result = $this->advapi32->RegDeleteValueA($hKey, $valueName);
        $this->advapi32->RegCloseKey($hKey);

        return $result === 0;
    }

    /**
     * Opens a URL, file, or application in the system default handler.
     *
     * Delegates to ShellExecuteA with the "open" verb. This is equivalent to
     * double-clicking an item in Explorer: the OS picks the right application
     * (browser for URLs, Word for .docx, etc.).
     *
     * @param string $target  URL (https://…), file path, or executable name.
     * @param string $params  Optional command-line parameters (only meaningful
     *                        when $target is an executable). Pass '' otherwise.
     * @param string $workDir Working directory for launched processes. Defaults
     *                        to an empty string (inherits current directory).
     * @param int    $showCmd ShowWindow flag (default SW_SHOW = 1).
     * @return bool True if ShellExecuteA returns a handle value > 32 (success);
     *              false on any error (e.g. file not found, access denied).
     */
    public function openURL(string $target, string $params = '', string $workDir = '', int $showCmd = 1): bool
    {
        // ShellExecuteA returns a pseudo-handle; values <= 32 indicate failure.
        $result = $this->shell32->ShellExecuteA(null, 'open', $target, $params ?: null, $workDir ?: null, $showCmd);

        // The return type is HINSTANCE (pointer-sized); cast to int for comparison.
        return (int) $result > 32;
    }

    /**
     * Executes a shell command and returns its combined stdout+stderr output.
     *
     * Creates a pipe pair via CreatePipe, spawns the command through
     * cmd.exe /C with CreateProcessA (stdout and stderr redirected to the
     * write end of the pipe), waits for the child to exit, then drains the
     * read end and returns the output as a PHP string.
     *
     * @param string $command The command line to run, e.g. "ipconfig /all".
     * @param int    $timeout Maximum time to wait for the child in milliseconds.
     *                        0 means no timeout (wait forever). Defaults to
     *                        30 000 ms (30 seconds).
     * @return string|null    Command output on success, null if the process
     *                        could not be created or the timeout expired.
     */
    public function executeCommand(string $command, int $timeout = 30000): ?string
    {
        // Allocate SECURITY_ATTRIBUTES with bInheritHandle = TRUE so the child
        // can use the pipe handles.
        $sa = $this->kernel32->new("SECURITY_ATTRIBUTES");
        $sa->nLength = FFI::sizeof($sa);
        $sa->bInheritHandle = 1;

        $hReadPipe = $this->kernel32->new("void*");
        $hWritePipe = $this->kernel32->new("void*");

        if (!$this->kernel32->CreatePipe(FFI::addr($hReadPipe), FFI::addr($hWritePipe), FFI::addr($sa), 0)) {
            return null;
        }

        // STARTUPINFOA: route stdout and stderr through the write end of the pipe.
        $si = $this->kernel32->new("STARTUPINFOA");
        $si->cb = FFI::sizeof($si);
        $si->dwFlags = 0x00000100; // STARTF_USESTDHANDLES
        $si->hStdOutput = $hWritePipe;
        $si->hStdError = $hWritePipe;

        $pi = $this->kernel32->new("PROCESS_INFORMATION");
        $cmd = "cmd.exe /C " . $command;

        $cmdBuf = $this->kernel32->new("char[" . (strlen($cmd) + 1) . "]");
        FFI::memcpy($cmdBuf, $cmd, strlen($cmd));

        $created = $this->kernel32->CreateProcessA(null, $cmdBuf, null, null, 1, ProcessCreation::CREATE_NO_WINDOW->value, null, null, FFI::addr($si), FFI::addr($pi));

        // Close write end in the parent so ReadFile sees EOF when child exits.
        $this->kernel32->CloseHandle($hWritePipe);

        if (!$created) {
            $this->kernel32->CloseHandle($hReadPipe);
            return null;
        }

        // Wait for the child process to finish (or hit our timeout).
        $waitMs = $timeout > 0 ? $timeout : 0xFFFFFFFF; // INFINITE
        $this->kernel32->WaitForSingleObject($pi->hProcess, $waitMs);

        // Drain the pipe.
        $output = '';
        $bufSize = 4096;
        $buf = $this->kernel32->new("char[$bufSize]");
        $bytesRead = $this->kernel32->new("DWORD");

        while ($this->kernel32->ReadFile($hReadPipe, $buf, $bufSize, FFI::addr($bytesRead), null)) {
            if ((int) $bytesRead->cdata === 0) {
                break;
            }
            $output .= FFI::string($buf, (int) $bytesRead->cdata);
        }

        $this->kernel32->CloseHandle($hReadPipe);
        $this->kernel32->CloseHandle($pi->hProcess);
        $this->kernel32->CloseHandle($pi->hThread);

        return $output;
    }

    /**
     * Sends a named system hotkey by its common alias.
     *
     * Maps friendly shortcut names to their virtual-key sequences and calls
     * pressKeyCombination() so callers never have to remember raw VK codes.
     *
     * Supported aliases (case-insensitive):
     *   'copy'        → Ctrl+C
     *   'paste'       → Ctrl+V
     *   'cut'         → Ctrl+X
     *   'undo'        → Ctrl+Z
     *   'redo'        → Ctrl+Y
     *   'selectall'   → Ctrl+A
     *   'save'        → Ctrl+S
     *   'find'        → Ctrl+F
     *   'close'       → Alt+F4
     *   'taskmanager' → Ctrl+Shift+Esc
     *   'screenshot'  → PrintScreen (VK_SNAPSHOT)
     *   'winscreen'   → Win+PrtSc (saves screenshot to Pictures)
     *   'desktop'     → Win+D
     *   'lock'        → Win+L
     *   'run'         → Win+R
     *
     * @param string $alias One of the recognised shortcut aliases listed above.
     * @return bool True if the alias was found and the keys sent; false if unknown.
     */
    public function sendHotkey(string $alias): bool
    {
        // VK constants used below:
        //   0x11 = VK_CONTROL, 0x12 = VK_MENU (Alt), 0x10 = VK_SHIFT
        //   0x5B = VK_LWIN,    0x2C = VK_SNAPSHOT (Print Screen)
        //   0x1B = VK_ESCAPE,  0x44 = 'D',  0x4C = 'L',  0x52 = 'R'
        //   0x41='A', 0x43='C', 0x46='F', 0x53='S', 0x56='V'
        //   0x58='X', 0x59='Y', 0x5A='Z'
        //   0x73 = F4

        $map = [
            'copy' => [0x11, 0x43],           // Ctrl+C
            'paste' => [0x11, 0x56],           // Ctrl+V
            'cut' => [0x11, 0x58],           // Ctrl+X
            'undo' => [0x11, 0x5A],           // Ctrl+Z
            'redo' => [0x11, 0x59],           // Ctrl+Y
            'selectall' => [0x11, 0x41],           // Ctrl+A
            'save' => [0x11, 0x53],           // Ctrl+S
            'find' => [0x11, 0x46],           // Ctrl+F
            'close' => [0x12, 0x73],           // Alt+F4
            'taskmanager' => [0x11, 0x10, 0x1B],     // Ctrl+Shift+Esc
            'screenshot' => [0x2C],                 // PrintScreen
            'winscreen' => [0x5B, 0x2C],           // Win+PrtSc
            'desktop' => [0x5B, 0x44],           // Win+D
            'lock' => [0x5B, 0x4C],           // Win+L
            'run' => [0x5B, 0x52],           // Win+R
        ];

        $key = strtolower($alias);

        if (!isset($map[$key])) {
            return false;
        }

        $this->pressKeyCombination($map[$key]);

        return true;
    }

    /**
     * Checks whether at least one process with the given executable name
     * is currently running on the system.
     *
     * The comparison is case-insensitive, so "notepad.exe" and "Notepad.EXE"
     * both match. Internally calls getProcessList() and scans its results.
     *
     * @param string $processName Executable name to search for (e.g. "notepad.exe").
     * @return bool True if one or more matching processes are found.
     */
    public function isProcessRunning(string $processName): bool
    {
        $needle = strtolower($processName);

        foreach ($this->getProcessList() as $proc) {
            if (strtolower($proc['name']) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kills every process whose executable name matches $processName.
     *
     * For each match found by getProcessList(), opens the process with
     * PROCESS_TERMINATE access and calls TerminateProcess with exit code 1.
     * Failures for individual processes are silently skipped.
     *
     * @param string $processName Executable name to terminate (case-insensitive).
     * @return int Number of processes that were successfully terminated.
     */
    public function killProcessByName(string $processName): int
    {
        $needle = strtolower($processName);
        $killed = 0;

        foreach ($this->getProcessList() as $proc) {
            if (strtolower($proc['name']) !== $needle) {
                continue;
            }

            // PROCESS_TERMINATE = 0x0001
            $hProc = $this->kernel32->OpenProcess(0x0001, 0, $proc['pid']);
            if (!$hProc) {
                continue;
            }

            if ($this->kernel32->TerminateProcess($hProc, 1)) {
                $killed++;
            }

            $this->kernel32->CloseHandle($hProc);
        }

        return $killed;
    }

    /**
     * Returns a formatted, human-readable system summary string.
     *
     * Collects computer name, username, Windows directory, system directory,
     * temp path, screen dimensions, monitor count, and uptime, then formats
     * them into a multi-line report suitable for logging or display.
     *
     * @return string Multi-line system information report.
     */
    public function getSystemSummary(): string
    {
        $dim = $this->getScreenDimensions();

        $lines = [
            '=== Windows System Summary ===',
            'Computer Name : ' . ($this->getComputerName() ?? 'N/A'),
            'User Name     : ' . ($this->getUserName() ?? 'N/A'),
            'Windows Dir   : ' . ($this->getWindowsDirectory() ?? 'N/A'),
            'Resolution    : ' . $dim['width'] . 'x' . $dim['height'],
            'Monitors      : ' . $this->getMonitorCount(),
            'Uptime        : ' . $this->getUptimeFormatted(),
            '==============================',
        ];

        return implode(PHP_EOL, $lines);
    }

    /**
     * Injects a DLL into a running process via CreateRemoteThread + LoadLibraryA.
     *
     * @param int    $pid     Target process ID.
     * @param string $dllPath Absolute path to the DLL (e.g. EZTransXP J2K.dll).
     * @return bool True on success.
     */
    public function injectDll(int $pid, string $dllPath): bool
    {
        $dllPathLen = strlen($dllPath) + 1;

        // PROCESS_ALL_ACCESS = 0x001F0FFF
        $hProcess = $this->kernel32->OpenProcess(0x001F0FFF, 0, $pid);
        if (!$hProcess) {
            return false;
        }

        // MEM_COMMIT | MEM_RESERVE = 0x3000, PAGE_READWRITE = 0x04
        $remoteAddr = $this->kernel32->VirtualAllocEx($hProcess, null, $dllPathLen, 0x3000, 0x04);
        if (!$remoteAddr) {
            $this->kernel32->CloseHandle($hProcess);
            return false;
        }

        $buf = $this->kernel32->new("char[$dllPathLen]");
        FFI::memcpy($buf, $dllPath, strlen($dllPath));

        $written = $this->kernel32->new("SIZE_T");
        if (!$this->kernel32->WriteProcessMemory($hProcess, $remoteAddr, FFI::addr($buf), $dllPathLen, FFI::addr($written))) {
            $this->kernel32->VirtualFreeEx($hProcess, $remoteAddr, 0, 0x8000);
            $this->kernel32->CloseHandle($hProcess);
            return false;
        }

        $hKernel32 = $this->kernel32->GetModuleHandleA("kernel32.dll");
        $loadLibAddr = $this->kernel32->GetProcAddress($hKernel32, "LoadLibraryA");

        $hThread = $this->kernel32->CreateRemoteThread($hProcess, null, 0, $loadLibAddr, $remoteAddr, 0, null);
        if (!$hThread) {
            $this->kernel32->VirtualFreeEx($hProcess, $remoteAddr, 0, 0x8000);
            $this->kernel32->CloseHandle($hProcess);
            return false;
        }

        $this->kernel32->WaitForSingleObject($hThread, 0xFFFFFFFF);

        $this->kernel32->CloseHandle($hThread);
        $this->kernel32->VirtualFreeEx($hProcess, $remoteAddr, 0, 0x8000);
        $this->kernel32->CloseHandle($hProcess);

        return true;
    }

    /**
     * Finds the first process matching $processName and injects $dllPath into it.
     *
     * @param string $processName Executable name to target (case-insensitive, e.g. "game.exe").
     * @param string $dllPath     Absolute path to the DLL.
     * @return bool True if a matching process was found and injection succeeded.
     */
    public function injectDllByName(string $processName, string $dllPath): bool
    {
        $needle = strtolower($processName);

        foreach ($this->getProcessList() as $proc) {
            if (strtolower($proc['name']) === $needle) {
                return $this->injectDll($proc['pid'], $dllPath);
            }
        }

        return false;
    }

    /**
     * Enumerates all DLL modules loaded in the target process.
     *
     * Takes a snapshot using CreateToolhelp32Snapshot(TH32CS_SNAPMODULE),
     * then iterates with Module32First / Module32Next.
     *
     * @param int $pid Target process ID.
     * @return array<array{name: string, base: int, size: int}> List of loaded modules.
     */
    private function getProcessModules(int $pid): array
    {
        $TH32CS_SNAPMODULE = 0x00000008;
        $TH32CS_SNAPMODULE32 = 0x00000010; // include 32-bit modules on a 64-bit host

        $hSnap = $this->kernel32->CreateToolhelp32Snapshot($TH32CS_SNAPMODULE | $TH32CS_SNAPMODULE32, $pid);

        // Handle invalid handle (INVALID_HANDLE_VALUE = -1)
        if (!$hSnap || (int) $hSnap === self::INVALID_HANDLE_VALUE) {
            return [];
        }

        $entry = $this->kernel32->new('MODULEENTRY32');
        $entry->dwSize = FFI::sizeof($entry);

        if (!$this->kernel32->Module32First($hSnap, FFI::addr($entry))) {
            $this->kernel32->CloseHandle($hSnap);
            return [];
        }

        $modules = [];
        do {
            // szModule: char[256], szExePath: char[260]
            $rawName = FFI::string($entry->szModule, 256);
            $name = strtolower(rtrim($rawName, "\x00"));

            $modules[] = [
                'name' => $name,
                'base' => (int) $this->kernel32->cast('intptr_t', $entry->modBaseAddr)->cdata,
                'size' => (int) $entry->modBaseSize,
            ];

            $entry->dwSize = FFI::sizeof($entry);
        } while ($this->kernel32->Module32Next($hSnap, FFI::addr($entry)));

        $this->kernel32->CloseHandle($hSnap);

        return $modules;
    }

    /**
     * Automatically detects the VN engine used by the process.
     *
     * Identifies the engine by inspecting loaded DLL module names.
     * Returns: 'kirikiri' | 'artemis' | 'unknown'
     *
     * @param int $pid Target process ID.
     * @return string Engine identifier.
     */
    public function detectVnEngine(int $pid): string
    {
        $modules = $this->getProcessModules($pid);
        if ($modules === []) {
            return 'unknown';
        }

        foreach ($modules as $mod) {
            $name = $mod['name']; // already lowercase

            foreach (self::KIRIKIRI_MODULE_SIGNATURES as $sig) {
                if (str_contains($name, $sig)) {
                    return 'kirikiri';
                }
            }

            foreach (self::ARTEMIS_MODULE_SIGNATURES as $sig) {
                if (str_starts_with($name, $sig) || str_contains($name, $sig)) {
                    return 'artemis';
                }
            }
        }

        return 'unknown';
    }

    /**
     * Checks whether the specified process is a KiriKiri (krkr2 / krkrZ) engine.
     *
     * @param int $pid Target process ID.
     * @return bool True if KiriKiri.
     */
    public function isKiriKiriProcess(int $pid): bool
    {
        return $this->detectVnEngine($pid) === 'kirikiri';
    }

    /**
     * Checks whether the specified process is an Artemis / BurikoGP engine.
     *
     * @param int $pid Target process ID.
     * @return bool True if Artemis.
     */
    public function isArtemisProcess(int $pid): bool
    {
        return $this->detectVnEngine($pid) === 'artemis';
    }

    /**
     * Extracts UTF-8 encoded Japanese strings from a raw memory buffer.
     *
     * Japanese UTF-8 sequences:
     *   - Hiragana: E3 81 80–E3 82 9F
     *   - Katakana: E3 82 A0–E3 83 BF
     *   - CJK:       E4 B8 80–E9 BF 99
     *
     * Used by newer BurikoGP titles and some modified Artemis engines.
     *
     * @param string $buffer Raw bytes read from memory.
     * @param int    $minLen Minimum accepted character length.
     * @return array<string> Unique UTF-8 strings.
     */
    private function extractJapaneseStringsUtf8(string $buffer, int $minLen): array
    {
        $results = [];
        $len = strlen($buffer);
        $current = '';
        $i = 0;

        while ($i < $len) {
            $b = ord($buffer[$i]);

            // 3-byte UTF-8 sequence start: 0xE0–0xEF
            if ($b >= 0xE0 && $b <= 0xEF && $i + 2 < $len) {
                $b2 = ord($buffer[$i + 1]);
                $b3 = ord($buffer[$i + 2]);

                if (($b2 & 0xC0) === 0x80 && ($b3 & 0xC0) === 0x80) {
                    $cp = (($b & 0x0F) << 12) | (($b2 & 0x3F) << 6) | ($b3 & 0x3F);

                    $isJp = ($cp >= 0x3040 && $cp <= 0x309F)   // Hiragana
                        || ($cp >= 0x30A0 && $cp <= 0x30FF)   // Katakana
                        || ($cp >= 0x4E00 && $cp <= 0x9FFF)   // CJK Unified Ideographs
                        || ($cp >= 0xFF00 && $cp <= 0xFFEF);  // Full-width ASCII

                    if ($isJp) {
                        $current .= $buffer[$i] . $buffer[$i + 1] . $buffer[$i + 2];
                        $i += 3;
                        continue;
                    }
                }
            }

            // Keep printable ASCII (including space) within current sequence
            if ($current !== '' && $b >= 0x20 && $b < 0x80 && $b !== 0x00) {
                $current .= $buffer[$i];
                $i++;
                continue;
            }

            if ($current !== '') {
                $trimmed = rtrim($current);
                if (mb_strlen($trimmed, 'UTF-8') >= $minLen) {
                    $results[] = $trimmed;
                }
                $current = '';
            }

            $i++;
        }

        if ($current !== '') {
            $trimmed = rtrim($current);
            if (mb_strlen($trimmed, 'UTF-8') >= $minLen) {
                $results[] = $trimmed;
            }
        }

        return array_values(array_unique($results));
    }

    /**
     * Filters out KiriKiri script commands and control statements from strings.
     *
     * Excludes:
     *   - Strings starting with '@', '*', '[', '#', ';', '//' (KAG commands)
     *   - File path-like strings (contain '\' or '/' + extension)
     *   - Pure ASCII strings (no Japanese characters)
     *   - Strings consisting only of long ASCII tokens (>32 chars)
     *
     * @param array<string> $strings UTF-8 strings.
     * @return array<string> Filtered result.
     */
    private function filterKiriKiriText(array $strings): array
    {
        $filtered = [];

        foreach ($strings as $str) {
            // Exclude script prefixes
            $hasScriptPrefix = false;
            foreach (self::KIRIKIRI_SCRIPT_PREFIXES as $prefix) {
                if (str_starts_with($str, $prefix)) {
                    $hasScriptPrefix = true;
                    break;
                }
            }
            if ($hasScriptPrefix) {
                continue;
            }

            // Exclude file path patterns (e.g: storage/bg/001.png)
            if (preg_match('/[\\\\\/][^\s]+\.[a-zA-Z]{2,4}/', $str)) {
                continue;
            }

            // Exclude pure ASCII (no Japanese)
            if (!preg_match('/[\x{3040}-\x{9FFF}]/u', $str)) {
                continue;
            }

            $filtered[] = $str;
        }

        return $filtered;
    }

    /**
     * Artemis / BurikoGP-specific text filtering.
     *
     * Excludes:
     *   - File path patterns
     *   - Pure ASCII strings
     *   - XML/HTML-like tags (<...>)
     *
     * @param array<string> $strings UTF-8 strings.
     * @return array<string> Filtered result.
     */
    private function filterArtemisText(array $strings): array
    {
        $filtered = [];

        foreach ($strings as $str) {
            // Exclude file paths
            if (preg_match('/[\\\\\/][^\s]+\.[a-zA-Z]{2,5}/', $str)) {
                continue;
            }

            // Exclude XML/HTML-like tags (<tag attr="val">)
            if (preg_match('/^<[^>]+>$/', trim($str))) {
                continue;
            }

            // Exclude pure ASCII
            if (!preg_match('/[\x{3040}-\x{9FFF}]/u', $str)) {
                continue;
            }

            $filtered[] = $str;
        }

        return $filtered;
    }

    /**
     * Scans heap memory of a KiriKiri (krkr2 / krkrZ) process and extracts Japanese text.
     *
     * KiriKiri always uses UTF-16LE. This method applies the following optimizations:
     *   - Scan only MEM_PRIVATE (0x20000) regions (heap allocations)
     *   - Skip MEM_IMAGE (0x1000000), MEM_MAPPED (0x40000) regions (code/resources)
     *   - Prefer PAGE_READWRITE (0x04) regions
     *   - Filter script commands via filterKiriKiriText()
     *
     * krkrZ stores current dialogue as UTF-16LE heap strings,
     * so new text appears in heap when dialogue changes.
     *
     * @param int $pid      Target process ID.
     * @param int $minLen   Minimum Japanese character length (default: 2).
     * @return array<string> Unique UTF-8 strings.
     */
    public function scanKiriKiriText(int $pid, int $minLen = 2): array
    {
        // PROCESS_VM_READ | PROCESS_QUERY_INFORMATION = 0x0410
        $hProcess = $this->kernel32->OpenProcess(0x0410, 0, $pid);
        if (!$hProcess) {
            return [];
        }

        $mbi = $this->kernel32->new('MEMORY_BASIC_INFORMATION');
        $mbiSize = FFI::sizeof($mbi);
        $address = 0;
        $chunkSize = 0x10000; // 64 KB
        $readBuf = $this->kernel32->new("char[$chunkSize]");
        $bytesRead = $this->kernel32->new('SIZE_T');
        $allStrings = [];

        $MEM_COMMIT = 0x1000;
        $MEM_PRIVATE = 0x20000;
        $PAGE_RW = 0x04;   // PAGE_READWRITE
        $PAGE_GUARD = 0x100;
        $PAGE_NOACCESS = 0x01;

        while ($this->kernel32->VirtualQueryEx($hProcess, $this->kernel32->cast('void*', $address), FFI::addr($mbi), $mbiSize) === $mbiSize) {
            $regionBase = (int) $this->kernel32->cast('intptr_t', $mbi->BaseAddress)->cdata;
            $regionSize = (int) $mbi->RegionSize;
            $state = (int) $mbi->State;
            $protect = (int) $mbi->Protect;
            $type = (int) $mbi->Type;

            $nextAddress = $regionBase + $regionSize;
            if ($nextAddress <= $address) {
                break;
            }
            $address = $nextAddress;

            if ($state !== $MEM_COMMIT) {
                continue;
            }
            if ($type !== $MEM_PRIVATE) {
                continue;
            }
            if ($protect & $PAGE_GUARD || $protect & $PAGE_NOACCESS) {
                continue;
            }
            if (!in_array($protect & 0xFF, [$PAGE_RW, 0x08, 0x40, 0x80], true)) {
                continue;
            }

            $offset = 0;
            while ($offset < $regionSize) {
                $toRead = min($chunkSize, $regionSize - $offset);
                $readAddr = $regionBase + $offset;

                $ok = $this->kernel32->ReadProcessMemory($hProcess, $this->kernel32->cast('void*', $readAddr), $readBuf, $toRead, FFI::addr($bytesRead));
                if ($ok && (int) $bytesRead->cdata > 0) {
                    $raw = FFI::string($readBuf, (int) $bytesRead->cdata);
                    $strings = $this->extractJapaneseStringsFromBuffer($raw, 'UTF-16LE', $minLen);
                    foreach ($strings as $s) {
                        $allStrings[$s] = true;
                    }
                }

                $offset += $toRead;
            }
        }

        $this->kernel32->CloseHandle($hProcess);

        return $this->filterKiriKiriText(array_keys($allStrings));
    }

    /**
     * Scans heap memory of an Artemis / BurikoGP process and extracts Japanese text.
     *
     * Older Artemis uses Shift-JIS, while newer BurikoGP uses UTF-8.
     * Both are attempted and merged.
     *
     * Memory filtering strategy:
     *   - MEM_PRIVATE + MEM_COMMIT regions (heap)
     *   - PAGE_READWRITE / PAGE_WRITECOPY
     *   - Ignore regions smaller than 4 KB
     *
     * @param int $pid    Target process ID.
     * @param int $minLen Minimum Japanese character length (default: 2).
     * @return array<string> Unique UTF-8 strings.
     */
    public function scanArtemisText(int $pid, int $minLen = 2): array
    {
        // PROCESS_VM_READ | PROCESS_QUERY_INFORMATION = 0x0410
        $pointer = 0;
        $hProcess = $this->kernel32->OpenProcess(0x0410, $this->kernel32->cast("bool*", $pointer), $pid);
        if (!$hProcess) {
            $errorMessage = $this->getLastErrorMessage();
            throw new Exception("Error: {$errorMessage}");
        }

        $mbi = $this->kernel32->new('MEMORY_BASIC_INFORMATION');
        $mbiSize = FFI::sizeof($mbi);
        $address = 0;
        $chunkSize = 0x10000;
        $readBuf = $this->kernel32->new("char[$chunkSize]");
        $bytesRead = $this->kernel32->new('SIZE_T');
        $allStrings = [];

        $MEM_COMMIT = 0x1000;
        $MEM_PRIVATE = 0x20000;
        $PAGE_RW = 0x04;  // PAGE_READWRITE
        $PAGE_WC = 0x08;  // PAGE_WRITECOPY
        $PAGE_GUARD = 0x100;
        $PAGE_NOACCESS = 0x01;
        $MIN_REGION = 0x1000; // less than 4 KB

        while ($this->kernel32->VirtualQueryEx($hProcess, $this->kernel32->cast('void*', $address), FFI::addr($mbi), $mbiSize) === $mbiSize) {
            $regionBase = (int) $this->kernel32->cast('intptr_t', $mbi->BaseAddress)->cdata;
            $regionSize = (int) $mbi->RegionSize;
            $state = (int) $mbi->State;
            $protect = (int) $mbi->Protect;
            $type = (int) $mbi->Type;

            $nextAddress = $regionBase + $regionSize;
            if ($nextAddress <= $address) {
                break;
            }
            $address = $nextAddress;

            if ($state !== $MEM_COMMIT || $type !== $MEM_PRIVATE) {
                continue;
            }
            if ($regionSize < $MIN_REGION) {
                continue;
            }
            if ($protect & $PAGE_GUARD || $protect & $PAGE_NOACCESS) {
                continue;
            }
            $baseProtect = $protect & 0xFF;
            if (!in_array($baseProtect, [$PAGE_RW, $PAGE_WC, 0x40, 0x80], true)) {
                continue;
            }

            $offset = 0;
            while ($offset < $regionSize) {
                $toRead = min($chunkSize, $regionSize - $offset);
                $readAddr = $regionBase + $offset;

                $ok = $this->kernel32->ReadProcessMemory($hProcess, $this->kernel32->cast('void*', $readAddr), $readBuf, $toRead, FFI::addr($bytesRead));

                if ($ok && (int) $bytesRead->cdata > 0) {
                    $raw = FFI::string($readBuf, (int) $bytesRead->cdata);

                    foreach ($this->extractJapaneseStringsFromBuffer($raw, 'SJIS', $minLen) as $s) {
                        $allStrings[$s] = true;
                    }

                    foreach ($this->extractJapaneseStringsUtf8($raw, $minLen) as $s) {
                        $allStrings[$s] = true;
                    }
                }

                $offset += $toRead;
            }
        }

        $this->kernel32->CloseHandle($hProcess);

        return $this->filterArtemisText(array_keys($allStrings));
    }

    /**
     * Polls a KiriKiri process at a fixed interval and returns newly appeared text.
     *
     * Calls scanKiriKiriText() each tick and passes new strings
     * (not present in the previous scan) to the callback.
     *
     * @param int      $pid
     * @param callable $onChange function(array<string>): void
     * @param int      $intervalMs Poll interval in milliseconds (default: 300)
     * @param int      $maxIterations 0 = infinite loop
     */
    public function pollKiriKiriText(int $pid, callable $onChange, int $intervalMs = 300, int $maxIterations = 0): void
    {
        $previous = [];
        $iteration = 0;

        while (true) {
            $current = $this->scanKiriKiriText($pid);
            $diff = array_values(array_diff($current, $previous));

            if ($diff !== []) {
                $onChange($diff);
            }

            $previous = $current;
            $iteration++;

            if ($maxIterations > 0 && $iteration >= $maxIterations) {
                break;
            }

            usleep($intervalMs * 1000);
        }
    }

    /**
     * Polls an Artemis / BurikoGP process and emits new text.
     *
     * @param int      $pid
     * @param callable $onChange
     * @param int      $intervalMs
     * @param int      $maxIterations
     */
    public function pollArtemisText(int $pid, callable $onChange, int $intervalMs = 300, int $maxIterations = 0): void
    {
        $previous = [];
        $iteration = 0;

        while (true) {
            $current = $this->scanArtemisText($pid);
            $diff = array_values(array_diff($current, $previous));

            if ($diff !== []) {
                $onChange($diff);
            }

            $previous = $current;
            $iteration++;

            if ($maxIterations > 0 && $iteration >= $maxIterations) {
                break;
            }

            usleep($intervalMs * 1000);
        }
    }

    /**
     * Detects the engine automatically and dispatches to the appropriate polling method.
     *
     *   'kirikiri' → pollKiriKiriText()
     *   'artemis'  → pollArtemisText()
     *   'unknown'  → throws RuntimeException
     *
     * @throws RuntimeException if engine cannot be identified
     */
    public function pollVnText(int $pid, callable $onChange, int $intervalMs = 300, int $maxIterations = 0): void
    {
        $engine = $this->detectVnEngine($pid);

        match ($engine) {
            'kirikiri' => $this->pollKiriKiriText($pid, $onChange, $intervalMs, $maxIterations),
            'artemis' => $this->pollArtemisText($pid, $onChange, $intervalMs, $maxIterations),
            default => throw new RuntimeException(
                sprintf("Unknown VN engine (PID %d). detectVnEngine() returned '%s'", $pid, $engine)
            ),
        };
    }

    /**
     * Extracts Japanese text strings from a raw memory buffer.
     *
     * Supports 'SJIS' and 'UTF-16LE' encodings.
     * SJIS lead bytes: 0x81–0x9F, 0xE0–0xFC; trail bytes: 0x40–0xFC (excl. 0x7F).
     * UTF-16LE Japanese range: Hiragana U+3040–U+309F, Katakana U+30A0–U+30FF, CJK U+4E00–U+9FFF.
     *
     * @param string $buffer   Raw bytes read from process memory.
     * @param string $encoding 'SJIS' or 'UTF-16LE'.
     * @param int    $minLen   Minimum character count to accept a string.
     * @return array<string> Unique UTF-8 strings found.
     */
    private function extractJapaneseStringsFromBuffer(string $buffer, string $encoding, int $minLen): array
    {
        $results = [];
        $len = strlen($buffer);

        if ($encoding === 'SJIS') {
            $current = '';
            $i = 0;
            while ($i < $len) {
                $b = ord($buffer[$i]);
                $isLead = ($b >= 0x81 && $b <= 0x9F) || ($b >= 0xE0 && $b <= 0xFC);
                if ($isLead && $i + 1 < $len) {
                    $t = ord($buffer[$i + 1]);
                    if (($t >= 0x40 && $t <= 0xFC) && $t !== 0x7F) {
                        $current .= $buffer[$i] . $buffer[$i + 1];
                        $i += 2;
                        continue;
                    }
                }
                if ($current !== '') {
                    $utf8 = mb_convert_encoding($current, 'UTF-8', 'SJIS');
                    if (mb_strlen($utf8, 'UTF-8') >= $minLen) {
                        $results[] = $utf8;
                    }
                    $current = '';
                }
                $i++;
            }
            if ($current !== '') {
                $utf8 = mb_convert_encoding($current, 'UTF-8', 'SJIS');
                if (mb_strlen($utf8, 'UTF-8') >= $minLen) {
                    $results[] = $utf8;
                }
            }
        } elseif ($encoding === 'UTF-16LE') {
            $current = '';
            for ($i = 0; $i + 1 < $len; $i += 2) {
                $cp = ord($buffer[$i]) | (ord($buffer[$i + 1]) << 8);
                $isJapanese = ($cp >= 0x3040 && $cp <= 0x309F)
                    || ($cp >= 0x30A0 && $cp <= 0x30FF)
                    || ($cp >= 0x4E00 && $cp <= 0x9FFF)
                    || ($cp >= 0xFF00 && $cp <= 0xFFEF);
                $isPrintable = $cp >= 0x20 && $cp < 0xD800;

                if ($isJapanese || ($current !== '' && $isPrintable && $cp !== 0x0000)) {
                    $current .= $buffer[$i] . $buffer[$i + 1];
                } else {
                    if ($current !== '') {
                        $utf8 = mb_convert_encoding($current, 'UTF-8', 'UTF-16LE');
                        if (mb_strlen($utf8, 'UTF-8') >= $minLen) {
                            $results[] = $utf8;
                        }
                        $current = '';
                    }
                }
            }
            if ($current !== '') {
                $utf8 = mb_convert_encoding($current, 'UTF-8', 'UTF-16LE');
                if (mb_strlen($utf8, 'UTF-8') >= $minLen) {
                    $results[] = $utf8;
                }
            }
        }

        return array_values(array_unique($results));
    }

    /**
     * Scans all readable committed memory regions of a process and extracts Japanese text strings.
     *
     * Enumerates regions with VirtualQueryEx, reads each with ReadProcessMemory,
     * then delegates string extraction to extractJapaneseStringsFromBuffer().
     *
     * Compatible with most VN engines (KiriKiri, BGI/Ethornell, Artemis, ONScripter, etc.)
     * that store dialogue in flat heap or data-section buffers.
     *
     * @param int    $pid      Target process ID.
     * @param string $encoding 'SJIS' (default) or 'UTF-16LE'.
     * @param int    $minLen   Minimum character count per string (default 2).
     * @return array<string> Unique UTF-8 strings found across all readable regions.
     */
    public function scanProcessTextMemory(int $pid, string $encoding = 'SJIS', int $minLen = 2): array
    {
        // PROCESS_VM_READ | PROCESS_QUERY_INFORMATION = 0x0410
        $hProcess = $this->kernel32->OpenProcess(0x0410, 0, $pid);
        if (!$hProcess) {
            return [];
        }

        $mbi = $this->kernel32->new('MEMORY_BASIC_INFORMATION');
        $mbiSize = FFI::sizeof($mbi);
        $address = 0;
        $chunkSize = 0x10000; // 64 KB chunks
        $readBuf = $this->kernel32->new("char[$chunkSize]");
        $bytesRead = $this->kernel32->new('SIZE_T');
        $allStrings = [];

        // Readable, non-guarded protection flags
        $readable = [0x02, 0x04, 0x08, 0x20, 0x40, 0x80]; // RO, RW, WC, XR, XRW, XWC
        $MEM_COMMIT = 0x1000;
        $PAGE_GUARD = 0x100;
        $PAGE_NOACCESS = 0x01;

        while ($this->kernel32->VirtualQueryEx($hProcess, $this->kernel32->cast('void*', $address), FFI::addr($mbi), $mbiSize) === $mbiSize) {
            $regionBase = (int) $this->kernel32->cast('intptr_t', $mbi->BaseAddress)->cdata;
            $regionSize = (int) $mbi->RegionSize;
            $state = (int) $mbi->State;
            $protect = (int) $mbi->Protect;

            $nextAddress = $regionBase + $regionSize;
            if ($nextAddress <= $address) {
                break;
            }
            $address = $nextAddress;

            if ($state !== $MEM_COMMIT) {
                continue;
            }
            if ($protect & $PAGE_GUARD || $protect & $PAGE_NOACCESS) {
                continue;
            }
            if (!in_array($protect & 0xFF, $readable, true)) {
                continue;
            }

            $offset = 0;
            while ($offset < $regionSize) {
                $toRead = min($chunkSize, $regionSize - $offset);
                $readAddr = $regionBase + $offset;

                $ok = $this->kernel32->ReadProcessMemory($hProcess, $this->kernel32->cast('void*', $readAddr), $readBuf, $toRead, FFI::addr($bytesRead));
                if ($ok && (int) $bytesRead->cdata > 0) {
                    $raw = FFI::string($readBuf, (int) $bytesRead->cdata);
                    $strings = $this->extractJapaneseStringsFromBuffer($raw, $encoding, $minLen);
                    foreach ($strings as $s) {
                        $allStrings[$s] = true;
                    }
                }

                $offset += $toRead;
            }
        }

        $this->kernel32->CloseHandle($hProcess);

        return array_keys($allStrings);
    }

    /**
     * Polls a process for new Japanese text strings at a fixed interval.
     *
     * On each tick, calls scanProcessTextMemory() and invokes $onChange with
     * any strings that were not present in the previous scan.
     *
     * @param int      $pid           Target process ID.
     * @param callable $onChange      Called with array<string> of newly appeared strings.
     * @param string   $encoding      'SJIS' (default) or 'UTF-16LE'.
     * @param int      $intervalMs    Sleep duration between scans in milliseconds (default 500).
     * @param int      $maxIterations Stop after this many iterations; 0 = run indefinitely.
     */
    public function pollProcessText(int $pid, callable $onChange, string $encoding = 'SJIS', int $intervalMs = 500, int $maxIterations = 0): void
    {
        $previous = [];
        $iteration = 0;

        while (true) {
            $current = $this->scanProcessTextMemory($pid, $encoding);
            $diff = array_values(array_diff($current, $previous));

            if ($diff !== []) {
                $onChange($diff);
            }

            $previous = $current;
            $iteration++;

            if ($maxIterations > 0 && $iteration >= $maxIterations) {
                break;
            }

            usleep($intervalMs * 1000);
        }
    }

    /**
     * Empties the Recycle Bin on the specified drive.
     *
     * @param string|null $rootPath  Drive root (e.g., "C:\\"); null = all drives.
     * @param int         $flags     SHERB_NOCONFIRMATION=0x1, SHERB_NOPROGRESSUI=0x2, SHERB_NOSOUND=0x4.
     * @return bool  True if HRESULT is S_OK (0).
     */
    public function emptyRecycleBin(?string $rootPath = null, int $flags = 0x7): bool
    {
        return $this->shell32->SHEmptyRecycleBinA(null, $rootPath, $flags) === 0;
    }

    /**
     * Shows a "Browse for Folder" dialog and returns the selected path.
     *
     * @param string $title  Dialog title text.
     * @param int    $flags  BIF_RETURNONLYFSDIRS=0x1, BIF_NEWDIALOGSTYLE=0x40; combine as needed.
     * @return string|false  Selected directory path, or false if the user cancelled.
     */
    public function showFolderDialog(string $title, int $flags = 0x41): string|false
    {
        $bi = $this->shell32->new('BROWSEINFOA');
        $bi->hwndOwner = null;
        $bi->pidlRoot = null;
        $bi->ulFlags = $flags;

        $titleBuf = $this->kernel32->new('char[' . (strlen($title) + 1) . ']');
        for ($i = 0; $i < strlen($title); $i++) {
            $titleBuf[$i] = $title[$i];
        }
        $titleBuf[strlen($title)] = "\0";
        $bi->lpszTitle = $this->shell32->cast('char*', $titleBuf);

        $pidl = $this->shell32->SHBrowseForFolderA(FFI::addr($bi));
        if (!$pidl) {
            return false;
        }

        $buf = $this->shell32->new('char[512]');
        $ok = $this->shell32->SHGetPathFromIDListA($pidl, $buf);
        $this->ole32->CoTaskMemFree($pidl);
        return $ok ? FFI::string($buf) : false;
    }

    /**
     * Retrieves the exit code of the specified process.
     *
     * Returns STILL_ACTIVE (259) if the process has not terminated.
     *
     * @param CData $hProcess  Handle to the process.
     * @return int|false  Exit code on success, false on failure.
     */
    public function getProcessExitCode(CData $hProcess): int|false
    {
        $code = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->GetExitCodeProcess($hProcess, FFI::addr($code));
        return $ok ? (int) $code->cdata : false;
    }

    /**
     * Measures elapsed real time in microseconds using the high-resolution performance counter.
     *
     * Usage: $start = $api->measureElapsedUs(); ... $us = $api->measureElapsedUs($start);
     *
     * @param int|null $startCounter  Counter value from a previous call; null = return raw start value.
     * @return float|int  Raw counter start (int) when $startCounter is null; otherwise elapsed µs (float).
     */
    public function measureElapsedUs(?int $startCounter = null): float|int
    {
        $now = $this->queryPerformanceCounter();
        if ($startCounter === null) {
            return $now ?: 0;
        }
        $freq = $this->queryPerformanceFrequency();
        if (!$now || !$freq) {
            return 0.0;
        }
        return (($now - $startCounter) / $freq) * 1_000_000.0;
    }

    /**
     * Retrieves the current value of the high-resolution performance counter.
     *
     * @return int|false  Counter value, or false on failure.
     */
    public function queryPerformanceCounter(): int|false
    {
        $cnt = $this->kernel32->new('LARGE_INTEGER');
        $ok = $this->kernel32->QueryPerformanceCounter(FFI::addr($cnt));
        return $ok ? (int) $cnt->QuadPart : false;
    }

    /**
     * Queries the performance counter frequency (ticks per second) for high-resolution timing.
     *
     * @return int|false  Ticks per second, or false if the counter is not available.
     */
    public function queryPerformanceFrequency(): int|false
    {
        $freq = $this->kernel32->new('LARGE_INTEGER');
        $ok = $this->kernel32->QueryPerformanceFrequency(FFI::addr($freq));
        return $ok ? (int) $freq->QuadPart : false;
    }

    /**
     * Retrieves the NetBIOS name of the local computer via GetComputerNameExA.
     *
     * ComputerNameNetBIOS = 0; use for hostname resolution distinguished from getComputerName().
     *
     * @return string|false  NetBIOS computer name, or false on failure.
     */
    public function getComputerNameEx(): string|false
    {
        $buf = $this->kernel32->new('char[256]');
        $len = $this->kernel32->new('DWORD');
        $len->cdata = 256;
        $ok = $this->kernel32->GetComputerNameExA(0, $buf, FFI::addr($len));
        return $ok ? FFI::string($buf, (int) $len->cdata) : false;
    }

    /**
     * Retrieves the long path name corresponding to the specified short (8.3) or abbreviated path.
     *
     * @param string $shortPath  Short or partial path.
     * @return string|false  Full long path, or false on failure.
     */
    public function getLongPathName(string $shortPath): string|false
    {
        $buf = $this->kernel32->new('char[512]');
        $len = $this->kernel32->GetLongPathNameA($shortPath, $buf, 512);
        return $len > 0 ? FFI::string($buf, $len) : false;
    }

    /**
     * Retrieves the short (8.3) form of the specified file or directory path.
     *
     * @param string $longPath  Long path to convert.
     * @return string|false  Short path, or false on failure.
     */
    public function getShortPathName(string $longPath): string|false
    {
        $buf = $this->kernel32->new('char[512]');
        $len = $this->kernel32->GetShortPathNameA($longPath, $buf, 512);
        return $len > 0 ? FFI::string($buf, $len) : false;
    }


    /**
     * Returns whether the current process itself is running under WOW64.
     *
     * @return bool|null  True if running as WOW64, false if native, null on error.
     */
    public function isSelfWow64(): ?bool
    {
        $hSelf = $this->kernel32->GetCurrentProcess();
        return $this->isWow64Process($hSelf);
    }

    /**
     * Determines whether a process is running under WOW64 (32-bit process on 64-bit Windows).
     *
     * @param CData $hProcess  Handle to the process.
     * @return bool|null  True = WOW64, false = native 64-bit, null on error.
     */
    public function isWow64Process(CData $hProcess): ?bool
    {
        $wow64 = $this->kernel32->new('BOOL');
        $ok = $this->kernel32->IsWow64Process($hProcess, FFI::addr($wow64));
        return $ok ? (bool) $wow64->cdata : null;
    }

    /**
     * Retrieves the temporary path in UTF-8 using the wide-character API (supports non-ASCII paths).
     *
     * @return string|null  Temporary path string in UTF-8, or null on failure.
     */
    public function getTempPathW(): ?string
    {
        $buf = $this->kernel32->new('unsigned short[512]');
        $len = $this->kernel32->GetTempPathW(512, $buf);
        if ($len === 0) {
            return null;
        }
        $raw = '';
        for ($i = 0; $i < $len; $i++) {
            $raw .= pack('v', $buf[$i]);
        }
        return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
    }

    /**
     * Generates a unique temporary file name in the specified directory.
     *
     * @param string $pathName  Directory path for the temporary file.
     * @param string $prefix    File name prefix (up to 3 characters are used).
     * @return string|false  Full path to the new temporary file, or false on failure.
     */
    public function getTempFileName(string $pathName, string $prefix = 'tmp'): string|false
    {
        $buf = $this->kernel32->new('char[512]');
        $len = $this->kernel32->GetTempFileNameA($pathName, $prefix, 0, $buf);
        return $len > 0 ? FFI::string($buf) : false;
    }

    /**
     * Retrieves the path of the executable file for the current process.
     *
     * @return string|false  Full path of the executable, or false on failure.
     */
    public function getModuleFileName(): string|false
    {
        $buf = $this->kernel32->new('char[512]');
        $len = $this->kernel32->GetModuleFileNameA(null, $buf, 512);
        return $len > 0 ? FFI::string($buf, $len) : false;
    }

    /**
     * Causes the current thread to yield execution to another ready thread.
     *
     * @return bool  True if another thread was scheduled; false if no other thread is ready.
     */
    public function switchToThread(): bool
    {
        return (bool) $this->kernel32->SwitchToThread();
    }

    /**
     * Retrieves the number of milliseconds since system start as a 64-bit value.
     *
     * Unlike getTickCount(), this does not wrap at ~49.7 days.
     *
     * @return int  Elapsed time in milliseconds.
     */
    public function getTickCount64(): int
    {
        return (int) $this->kernel32->GetTickCount64();
    }

    /**
     * Releases a CSP handle acquired with cryptAcquireContext().
     *
     * @param CData $hProv  Provider handle.
     * @return bool  True on success.
     */
    public function cryptReleaseContext(CData $hProv): bool
    {
        return (bool) $this->advapi32->CryptReleaseContext($hProv, 0);
    }

    /**
     * Generates cryptographically random bytes using the specified CSP.
     *
     * @param CData $hProv   Provider handle from cryptAcquireContext().
     * @param int   $length  Number of random bytes to generate.
     * @return string|false  Binary string of random bytes, or false on failure.
     */
    public function cryptGenRandom(CData $hProv, int $length): string|false
    {
        $buf = $this->advapi32->new("BYTE[$length]");
        $ok = $this->advapi32->CryptGenRandom($hProv, $length, $buf);
        if (!$ok) {
            return false;
        }
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= chr((int) $buf[$i]);
        }
        return $result;
    }

    /**
     * Acquires a handle to a key container within a cryptographic service provider (CSP).
     *
     * @param string|null $container  Key container name; null = default.
     * @param string|null $provider   CSP name; null = default.
     * @param int         $provType   Provider type (PROV_RSA_FULL = 1).
     * @param int         $flags      0 = normal, CRYPT_NEWKEYSET = 0x8, CRYPT_VERIFYCONTEXT = 0xF0000000.
     * @return CData|null  HCRYPTPROV handle, or null on failure.
     */
    public function cryptAcquireContext(?string $container = null, ?string $provider = null, int $provType = 1, int $flags = 0xF0000000): ?CData
    {
        $hProv = $this->advapi32->new('HCRYPTPROV');
        $ok = $this->advapi32->CryptAcquireContextA(FFI::addr($hProv), $container, $provider, $provType, $flags);
        return $ok ? $hProv : null;
    }

    /**
     * Compares two FILETIME values.
     *
     * @param int $fileTime1  First FILETIME value.
     * @param int $fileTime2  Second FILETIME value.
     * @return int  -1 if fileTime1 < fileTime2, 0 if equal, 1 if fileTime1 > fileTime2.
     */
    public function compareFileTime(int $fileTime1, int $fileTime2): int
    {
        $ft1 = $this->kernel32->new('FILETIME');
        $ft2 = $this->kernel32->new('FILETIME');
        $ft1->dwLowDateTime = $fileTime1 & 0xFFFFFFFF;
        $ft1->dwHighDateTime = ($fileTime1 >> 32) & 0xFFFFFFFF;
        $ft2->dwLowDateTime = $fileTime2 & 0xFFFFFFFF;
        $ft2->dwHighDateTime = ($fileTime2 >> 32) & 0xFFFFFFFF;
        return (int) $this->kernel32->CompareFileTime(FFI::addr($ft1), FFI::addr($ft2));
    }

    /**
     * Retrieves the current system time as a 64-bit FILETIME value (UTC).
     *
     * @return int  Current UTC time as a FILETIME.
     */
    public function getSystemTimeAsFileTime(): int
    {
        $ft = $this->kernel32->new('FILETIME');
        $this->kernel32->GetSystemTimeAsFileTime(FFI::addr($ft));
        return ((int) $ft->dwHighDateTime << 32) | (int) $ft->dwLowDateTime;
    }

    /**
     * Converts a UTC-based FILETIME to a local FILETIME value using the current system time zone.
     *
     * @param int $utcFileTime  UTC FILETIME (100-ns intervals since 1601-01-01 UTC).
     * @return int|false  Local FILETIME value, or false on failure.
     */
    public function fileTimeToLocalFileTime(int $utcFileTime): int|false
    {
        $utc = $this->kernel32->new('FILETIME');
        $utc->dwLowDateTime = $utcFileTime & 0xFFFFFFFF;
        $utc->dwHighDateTime = ($utcFileTime >> 32) & 0xFFFFFFFF;
        $local = $this->kernel32->new('FILETIME');
        $ok = $this->kernel32->FileTimeToLocalFileTime(FFI::addr($utc), FFI::addr($local));
        return $ok ? (((int) $local->dwHighDateTime << 32) | (int) $local->dwLowDateTime) : false;
    }

    /**
     * Converts a 64-bit FILETIME integer to a SYSTEMTIME structure.
     *
     * @param int $fileTime  64-bit FILETIME value.
     * @return array{
     *  year:int, 
     *  month:int, 
     *  dayOfWeek:int, 
     *  day:int,
     *  hour:int, 
     *  minute:int, 
     *  second:int, 
     *  milliseconds:int
     * }|false
     */
    public function fileTimeToSystemTime(int $fileTime): array|false
    {
        $ft = $this->kernel32->new('FILETIME');
        $ft->dwLowDateTime = $fileTime & 0xFFFFFFFF;
        $ft->dwHighDateTime = ($fileTime >> 32) & 0xFFFFFFFF;
        $st = $this->kernel32->new('SYSTEMTIME');
        $ok = $this->kernel32->FileTimeToSystemTime(FFI::addr($ft), FFI::addr($st));
        if (!$ok) {
            return false;
        }

        return [
            'year' => (int) $st->wYear,
            'month' => (int) $st->wMonth,
            'dayOfWeek' => (int) $st->wDayOfWeek,
            'day' => (int) $st->wDay,
            'hour' => (int) $st->wHour,
            'minute' => (int) $st->wMinute,
            'second' => (int) $st->wSecond,
            'milliseconds' => (int) $st->wMilliseconds,
        ];
    }

    /**
     * Converts a SYSTEMTIME structure (filled from individual fields) to a FILETIME integer.
     *
     * @param int $year         Full year (e.g., 2025).
     * @param int $month        Month (1-12).
     * @param int $day          Day of the month (1-31).
     * @param int $hour         Hour (0-23).
     * @param int $minute       Minute (0-59).
     * @param int $second       Second (0-59).
     * @param int $milliseconds Milliseconds (0-999).
     * @return int|false  64-bit FILETIME value (100-ns intervals since 1601-01-01 UTC), or false.
     */
    public function systemTimeToFileTime(int $year, int $month, int $day, int $hour = 0, int $minute = 0, int $second = 0, int $milliseconds = 0): int|false
    {
        $st = $this->kernel32->new('SYSTEMTIME');
        $st->wYear = $year;
        $st->wMonth = $month;
        $st->wDay = $day;
        $st->wHour = $hour;
        $st->wMinute = $minute;
        $st->wSecond = $second;
        $st->wMilliseconds = $milliseconds;
        $ft = $this->kernel32->new('FILETIME');
        $ok = $this->kernel32->SystemTimeToFileTime(FFI::addr($st), FFI::addr($ft));
        return $ok ? (((int) $ft->dwHighDateTime << 32) | (int) $ft->dwLowDateTime) : false;
    }

    /**
     * Retrieves the standard host name for the local computer.
     *
     * @return string|false  Host name string, or false on failure.
     */
    public function getHostName(): string|false
    {
        $buf = $this->ws2_32->new('char[256]');
        $ok = $this->ws2_32->gethostname($buf, 256);
        return $ok === 0 ? FFI::string($buf) : false;
    }

    /**
     * Converts a dotted-decimal IPv4 address string to a 32-bit network-byte-order integer.
     *
     * @param string $ip  Dotted-decimal address (e.g., "192.168.1.1").
     * @return int   Network-byte-order integer, or INADDR_NONE (0xFFFFFFFF) on error.
     */
    public function inetAddr(string $ip): int
    {
        return (int) $this->ws2_32->inet_addr($ip);
    }

    /**
     * Sets a socket option at the specified protocol level.
     *
     * @param CData $socket    Socket handle.
     * @param int   $level     SOL_SOCKET=0xFFFF, IPPROTO_TCP=6, IPPROTO_IP=0.
     * @param int   $optName   SO_REUSEADDR=4, SO_KEEPALIVE=8, SO_RCVTIMEO=0x1006, SO_SNDBUF=0x1001.
     * @param int   $optValue  Integer option value.
     * @return bool  True on success.
     */
    public function setSocketOption(CData $socket, int $level, int $optName, int $optValue): bool
    {
        $val = $this->ws2_32->new('int');
        $val->cdata = $optValue;
        return $this->ws2_32->setsockopt($socket, $level, $optName, FFI::addr($val), FFI::sizeof($val)) === 0;
    }

    /**
     * Retrieves a socket option.
     *
     * @param CData $socket   Socket handle.
     * @param int   $level    Protocol level.
     * @param int   $optName  Option name.
     * @return int|false  Integer option value, or false on failure.
     */
    public function getSocketOption(CData $socket, int $level, int $optName): int|false
    {
        $val = $this->ws2_32->new('int');
        $len = $this->ws2_32->new('int');
        $len->cdata = FFI::sizeof($val);
        $ok = $this->ws2_32->getsockopt($socket, $level, $optName, FFI::addr($val), FFI::addr($len));
        return $ok === 0 ? (int) $val->cdata : false;
    }

    /**
     * Retrieves the last Winsock error code for the calling thread.
     *
     * @return int  WSA error code (WSAECONNREFUSED=10061, WSAETIMEDOUT=10060, WSAEADDRINUSE=10048).
     */
    public function getLastSocketError(): int
    {
        return (int) $this->ws2_32->WSAGetLastError();
    }

    /**
     * Places a bound socket in a state where it listens for incoming connections.
     *
     * @param CData $socket   Bound socket handle.
     * @param int   $backlog  Maximum length of the pending connection queue.
     * @return bool  True on success.
     */
    public function listenSocket(CData $socket, int $backlog = 5): bool
    {
        return $this->ws2_32->listen($socket, $backlog) === 0;
    }

    /**
     * Shuts down part or all of a full-duplex socket connection.
     *
     * @param CData $socket  Socket handle.
     * @param int   $how     SD_RECEIVE=0, SD_SEND=1, SD_BOTH=2.
     * @return bool  True on success.
     */
    public function shutdownSocket(CData $socket, int $how = 2): bool
    {
        return $this->ws2_32->shutdown($socket, $how) === 0;
    }

    /**
     * Converts a 32-bit integer from host-byte-order to network-byte-order (big-endian).
     *
     * @param int $hostLong  32-bit value in host byte order.
     * @return int  Value in network byte order.
     */
    public function htonl(int $hostLong): int
    {
        return (int) $this->ws2_32->htonl($hostLong);
    }

    /**
     * Converts a 32-bit integer from network-byte-order to host-byte-order.
     *
     * @param int $netLong  32-bit value in network byte order.
     * @return int  Value in host byte order.
     */
    public function ntohl(int $netLong): int
    {
        return (int) $this->ws2_32->ntohl($netLong);
    }

    /**
     * Converts a 16-bit integer from network-byte-order to host-byte-order.
     *
     * @param int $netShort  16-bit value in network byte order.
     * @return int  Value in host byte order.
     */
    public function ntohs(int $netShort): int
    {
        return (int) $this->ws2_32->ntohs($netShort);
    }

    /**
     * Suspends the system by entering sleep (S3) or hibernate (S4) state.
     *
     * Requires SeShutdownPrivilege in some configurations.
     *
     * @param bool $hibernate         True = hibernate (S4); false = suspend/sleep (S3).
     * @param bool $forceCritical     True forces critical suspend (no callbacks fired).
     * @param bool $disableWakeEvent  True disables all wake events.
     * @return bool  True on success; note this function does not return on actual suspend.
     */
    public function setSuspendState(bool $hibernate = false, bool $forceCritical = false, bool $disableWakeEvent = false): bool
    {
        return (bool) $this->powerProfile()->SetSuspendState(
            $hibernate ? 1 : 0,
            $forceCritical ? 1 : 0,
            $disableWakeEvent ? 1 : 0
        );
    }

    /**
     * The power profile library, bound the first time it is wanted.
     *
     * SetSuspendState is exported by powrprof.dll, not kernel32. Declared
     * against kernel32 it does not resolve, and because FFI resolves every
     * function in a header when the library is opened, that one wrong
     * declaration stopped the whole class from constructing.
     *
     * @return FFI|FFIWindowsAPIInterface
     *
     * @throws RuntimeException When the header is missing.
     */
    private function powerProfile()
    {
        if ($this->powrprof !== null) {
            return $this->powrprof;
        }

        $header = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/powrprof.h"));

        if (!$header) {
            throw new RuntimeException('Powrprof header file is not exists');
        }

        $windowsHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/windows.h"));

        if (!$windowsHeader) {
            throw new RuntimeException('Windows header file is not exists');
        }

        return $this->powrprof = FFI::cdef(
            sprintf("%s%s", $windowsHeader, $header),
            lib: "powrprof.dll"
        );
    }

    /**
     * Prevents the system from entering sleep or turning off the display.
     *
     * Call with ES_CONTINUOUS (0x80000000) alone to clear the requirement.
     *
     * @param int $flags  ES_CONTINUOUS=0x80000000, ES_DISPLAY_REQUIRED=0x2, ES_SYSTEM_REQUIRED=0x1,
     *                    ES_AWAYMODE_REQUIRED=0x40.
     * @return int|false  Previous thread execution state, or false on failure.
     */
    public function setThreadExecutionState(int $flags): int|false
    {
        $result = $this->kernel32->SetThreadExecutionState($flags);
        return $result !== 0 ? (int) $result : false;
    }

    /**
     * Destroys a cursor and frees any memory it occupied.
     *
     * Only cursors created with CreateCursor should be destroyed this way.
     *
     * @param CData $hCursor  HCURSOR handle.
     * @return bool  True on success.
     */
    public function destroyCursor(CData $hCursor): bool
    {
        return (bool) $this->user32->DestroyCursor($hCursor);
    }

    /**
     * Sets the cursor shape for the current thread.
     *
     * @param CData|null $hCursor  HCURSOR handle; null hides the cursor.
     * @return CData|null  Previous cursor handle, or null.
     */
    public function setCursor(?CData $hCursor): ?CData
    {
        $prev = $this->user32->SetCursor($hCursor);
        return $prev ?: null;
    }

    /**
     * Loads a predefined system cursor.
     *
     * @param int $cursorId  IDC_ARROW=32512, IDC_WAIT=32514, IDC_CROSS=32515, IDC_HAND=32649,
     *                       IDC_NO=32648, IDC_SIZEALL=32646, IDC_IBEAM=32513.
     * @return CData|null  HCURSOR handle, or null on failure.
     */
    public function loadCursor(int $cursorId = 32512): ?CData
    {
        $hCursor = $this->user32->LoadCursorA(null, $this->user32->cast('char*', $cursorId));
        return $hCursor ?: null;
    }

    /**
     * Draws an icon or cursor into the specified DC at the given coordinates.
     *
     * @param CData $hdc     Device context handle.
     * @param int   $x       X coordinate of the upper-left corner.
     * @param int   $y       Y coordinate of the upper-left corner.
     * @param CData $hIcon   HICON handle.
     * @param int   $width   Width to scale the icon to; 0 = natural width.
     * @param int   $height  Height to scale the icon to; 0 = natural height.
     * @param int   $flags   DI_NORMAL=3, DI_IMAGE=2, DI_MASK=1.
     * @return bool  True on success.
     */
    public function drawIcon(CData $hdc, int $x, int $y, CData $hIcon, int $width = 0, int $height = 0, int $flags = 3): bool
    {
        return (bool) $this->user32->DrawIconEx($hdc, $x, $y, $hIcon, $width, $height, 0, null, $flags);
    }

    /**
     * Destroys an icon and frees the memory it occupied.
     *
     * Only icons created with CreateIcon or CopyIcon should be destroyed this way.
     *
     * @param CData $hIcon  HICON handle.
     * @return bool  True on success.
     */
    public function destroyIcon(CData $hIcon): bool
    {
        return (bool) $this->user32->DestroyIcon($hIcon);
    }

    /**
     * Loads the specified icon resource from the executable or DLL file.
     *
     * @param string|null $moduleName  Module path; null = load a predefined system icon.
     * @param int         $iconId      Icon resource ID (IDI_APPLICATION=32512, IDI_ERROR=32513,
     *                                 IDI_QUESTION=32514, IDI_WARNING=32515, IDI_INFORMATION=32516).
     * @return CData|null  HICON handle, or null on failure.
     */
    public function loadIcon(?string $moduleName, int $iconId): ?CData
    {
        $hModule = $moduleName ? $this->kernel32->LoadLibraryA($moduleName) : null;
        $hIcon = $this->user32->LoadIconA($hModule, $this->user32->cast('char*', $iconId));
        return $hIcon ?: null;
    }

    /**
     * Deletes a GDI object and frees the memory it occupies.
     *
     * Applicable to pens, brushes, fonts, bitmaps, regions, and palettes.
     *
     * @param CData $hObject  Handle to the GDI object.
     * @return bool  True on success.
     */
    public function deleteObject(CData $hObject): bool
    {
        return (bool) $this->gdi32->DeleteObject($hObject);
    }

    /**
     * Selects a GDI object into the specified device context, replacing the previous object of the same type.
     *
     * @param CData $hdc     Device context handle.
     * @param CData $hObject New GDI object (pen, brush, font, bitmap, or region).
     * @return CData|null  Previously selected object of the same type, or null on failure.
     */
    public function selectObject(CData $hdc, CData $hObject): ?CData
    {
        $prev = $this->gdi32->SelectObject($hdc, $hObject);
        return $prev ?: null;
    }

    /**
     * Creates a device context compatible with the specified DC.
     *
     * Commonly used for off-screen/double-buffered drawing.
     *
     * @param CData $hdc  Reference DC; the new DC has the same color format.
     * @return CData|null  Compatible DC handle, or null on failure.
     */
    public function createCompatibleDC(CData $hdc): ?CData
    {
        $memDC = $this->gdi32->CreateCompatibleDC($hdc);
        return $memDC ?: null;
    }

    /**
     * Deletes the specified device context and frees its resources.
     *
     * @param CData $hdc  DC handle to delete.
     * @return bool  True on success.
     */
    public function deleteDC(CData $hdc): bool
    {
        return (bool) $this->gdi32->DeleteDC($hdc);
    }

    /**
     * Creates a logical pen with the specified style, width, and color.
     *
     * @param int $style  PS_SOLID=0, PS_DASH=1, PS_DOT=2, PS_DASHDOT=3, PS_NULL=5.
     * @param int $width  Pen width in logical units.
     * @param int $color  COLORREF value (0x00BBGGRR).
     * @return CData|null  HPEN handle, or null on failure.
     */
    public function createPen(int $style = 0, int $width = 1, int $color = 0x00000000): ?CData
    {
        $hPen = $this->gdi32->CreatePen($style, $width, $color);
        return $hPen ?: null;
    }

    /**
     * Creates a logical brush with the specified solid color.
     *
     * @param int $color  COLORREF value (0x00BBGGRR).
     * @return CData|null  HBRUSH handle, or null on failure.
     */
    public function createSolidBrush(int $color): ?CData
    {
        $hBrush = $this->gdi32->CreateSolidBrush($color);
        return $hBrush ?: null;
    }

    /**
     * Sets the foreground (text) color for the specified device context.
     *
     * @param CData $hdc    Device context handle.
     * @param int   $color  COLORREF value (0x00BBGGRR).
     * @return int  Previous text color, or CLR_INVALID (0xFFFFFFFF) on failure.
     */
    public function setTextColor(CData $hdc, int $color): int
    {
        return (int) $this->gdi32->SetTextColor($hdc, $color);
    }

    /**
     * Sets the background color for the specified device context.
     *
     * @param CData $hdc    Device context handle.
     * @param int   $color  COLORREF value (0x00BBGGRR).
     * @return int  Previous background color.
     */
    public function setBkColor(CData $hdc, int $color): int
    {
        return (int) $this->gdi32->SetBkColor($hdc, $color);
    }

    /**
     * Sets the background mix mode of the specified DC (TRANSPARENT=1 or OPAQUE=2).
     *
     * @param CData $hdc   Device context handle.
     * @param int   $mode  TRANSPARENT = 1, OPAQUE = 2.
     * @return int  Previous background mode.
     */
    public function setBackgroundMode(CData $hdc, int $mode): int
    {
        return (int) $this->gdi32->SetBkMode($hdc, $mode);
    }

    /**
     * Draws a filled rectangle using the current pen (border) and brush (fill).
     *
     * @param CData $hdc  Device context handle.
     * @param int   $x1   X of upper-left corner.
     * @param int   $y1   Y of upper-left corner.
     * @param int   $x2   X of lower-right corner.
     * @param int   $y2   Y of lower-right corner.
     * @return bool  True on success.
     */
    public function drawRectangle(CData $hdc, int $x1, int $y1, int $x2, int $y2): bool
    {
        return (bool) $this->gdi32->Rectangle($hdc, $x1, $y1, $x2, $y2);
    }

    /**
     * Draws a filled ellipse bounded by the specified rectangle.
     *
     * @param CData $hdc  Device context handle.
     * @param int   $x1   X of bounding rectangle upper-left.
     * @param int   $y1   Y of bounding rectangle upper-left.
     * @param int   $x2   X of bounding rectangle lower-right.
     * @param int   $y2   Y of bounding rectangle lower-right.
     * @return bool  True on success.
     */
    public function drawEllipse(CData $hdc, int $x1, int $y1, int $x2, int $y2): bool
    {
        return (bool) $this->gdi32->Ellipse($hdc, $x1, $y1, $x2, $y2);
    }

    /**
     * Draws a line from the current position to the specified endpoint using the current pen.
     *
     * @param CData $hdc  Device context handle.
     * @param int   $x    X coordinate of the endpoint.
     * @param int   $y    Y coordinate of the endpoint.
     * @return bool  True on success.
     */
    public function lineTo(CData $hdc, int $x, int $y): bool
    {
        return (bool) $this->gdi32->LineTo($hdc, $x, $y);
    }

    /**
     * Updates the current position in the DC and optionally retrieves the previous position.
     *
     * @param CData $hdc  Device context handle.
     * @param int   $x    New X position.
     * @param int   $y    New Y position.
     * @return array{x:int, y:int}|false  Previous position, or false on failure.
     */
    public function moveToEx(CData $hdc, int $x, int $y): array|false
    {
        $prev = $this->gdi32->new('POINT');
        $ok = $this->gdi32->MoveToEx($hdc, $x, $y, FFI::addr($prev));
        return $ok ? ['x' => (int) $prev->x, 'y' => (int) $prev->y] : false;
    }

    /**
     * Writes a character string at the specified position using the current font.
     *
     * @param CData  $hdc   Device context handle.
     * @param int    $x     X coordinate of the starting position.
     * @param int    $y     Y coordinate of the starting position.
     * @param string $text  String to draw.
     * @return bool  True on success.
     */
    public function textOut(CData $hdc, int $x, int $y, string $text): bool
    {
        return (bool) $this->gdi32->TextOutA($hdc, $x, $y, $text, strlen($text));
    }

    /**
     * Draws formatted text in the specified rectangle using DrawTextA from user32.
     *
     * @param CData  $hdc     Device context handle.
     * @param string $text    Text to draw.
     * @param int    $x       Left edge of the bounding rectangle.
     * @param int    $y       Top edge of the bounding rectangle.
     * @param int    $width   Width of the bounding rectangle.
     * @param int    $height  Height of the bounding rectangle.
     * @param int    $format  DT_LEFT=0, DT_CENTER=1, DT_RIGHT=2, DT_WORDBREAK=0x10.
     * @return int   Height of the text if DT_CALCRECT is used; 0 on failure.
     */
    public function drawText(CData $hdc, string $text, int $x, int $y, int $width, int $height, int $format = 0): int
    {
        $rect = $this->user32->new('tagRECT');
        $rect->left = $x;
        $rect->top = $y;
        $rect->right = $x + $width;
        $rect->bottom = $y + $height;
        return (int) $this->user32->DrawTextA($hdc, $text, strlen($text), FFI::addr($rect), $format);
    }

    /**
     * Draws an arc on the specified device context.
     *
     * @param CData $hdc  Device context handle.
     * @param int   $x1   X of bounding rectangle upper-left.
     * @param int   $y1   Y of bounding rectangle upper-left.
     * @param int   $x2   X of bounding rectangle lower-right.
     * @param int   $y2   Y of bounding rectangle lower-right.
     * @param int   $x3   X of arc start radial endpoint.
     * @param int   $y3   Y of arc start radial endpoint.
     * @param int   $x4   X of arc end radial endpoint.
     * @param int   $y4   Y of arc end radial endpoint.
     * @return bool  True on success.
     */
    public function drawArc(CData $hdc, int $x1, int $y1, int $x2, int $y2, int $x3, int $y3, int $x4, int $y4): bool
    {
        return (bool) $this->gdi32->Arc($hdc, $x1, $y1, $x2, $y2, $x3, $y3, $x4, $y4);
    }

    /**
     * Draws a rectangle with rounded corners using the current pen and brush.
     *
     * @param CData $hdc     Device context handle.
     * @param int   $x1      X of upper-left corner.
     * @param int   $y1      Y of upper-left corner.
     * @param int   $x2      X of lower-right corner.
     * @param int   $y2      Y of lower-right corner.
     * @param int   $width   Width of the ellipse used to round the corners.
     * @param int   $height  Height of the ellipse used to round the corners.
     * @return bool  True on success.
     */
    public function drawRoundRect(CData $hdc, int $x1, int $y1, int $x2, int $y2, int $width, int $height): bool
    {
        return (bool) $this->gdi32->RoundRect($hdc, $x1, $y1, $x2, $y2, $width, $height);
    }

    /**
     * Retrieves the width and height in pixels of the specified string using the current DC font.
     *
     * @param CData  $hdc   Device context handle.
     * @param string $text  String to measure.
     * @return array{width:int, height:int}|false
     */
    public function getTextExtent(CData $hdc, string $text): array|false
    {
        $size = $this->gdi32->new('SIZE');
        $ok = $this->gdi32->GetTextExtentPoint32A($hdc, $text, strlen($text), FFI::addr($size));
        return $ok ? ['width' => (int) $size->cx, 'height' => (int) $size->cy] : false;
    }

    /**
     * Creates a logical font with the specified characteristics.
     *
     * @param int    $height     Character cell height in logical units (negative = character height).
     * @param int    $width      Average character width; 0 = auto-match.
     * @param int    $weight     FW_NORMAL = 400, FW_BOLD = 700.
     * @param bool   $italic     True for italic.
     * @param bool   $underline  True for underline.
     * @param string $faceName   Typeface name (e.g., "Arial", "Consolas").
     * @return CData|null  HFONT handle, or null on failure.
     */
    public function createFont(int $height = -12, int $width = 0, int $weight = 400, bool $italic = false, bool $underline = false, string $faceName = 'Arial'): ?CData
    {
        $hFont = $this->gdi32->CreateFontA($height, $width, 0, 0, $weight, $italic ? 1 : 0, $underline ? 1 : 0, 0, 1, 0, 0, 0, 0, $faceName);
        return $hFont ?: null;
    }

    /**
     * Registers a system-wide hotkey combination.
     *
     * @param CData|null $hwnd  Window to receive WM_HOTKEY; null for the current thread's message queue.
     * @param int        $id    Application-defined hotkey identifier (1..0xBFFF).
     * @param int        $mods  Modifier keys: MOD_ALT=0x1, MOD_CONTROL=0x2, MOD_SHIFT=0x4, MOD_WIN=0x8.
     * @param int        $vk    Virtual-key code of the hotkey.
     * @return bool  True on success.
     */
    public function registerHotKey(?CData $hwnd, int $id, int $mods, int $vk): bool
    {
        return (bool) $this->user32->RegisterHotKey($hwnd, $id, $mods, $vk);
    }

    /**
     * Frees a hotkey previously registered with registerHotKey().
     *
     * @param CData|null $hwnd  Window handle used when registering the hotkey.
     * @param int        $id    Hotkey identifier.
     * @return bool  True on success.
     */
    public function unregisterHotKey(?CData $hwnd, int $id): bool
    {
        return (bool) $this->user32->UnregisterHotKey($hwnd, $id);
    }

    /**
     * Retrieves the number of clipboard formats currently on the clipboard.
     *
     * @return int  Number of available clipboard formats.
     */
    public function countClipboardFormats(): int
    {
        return (int) $this->user32->CountClipboardFormats();
    }

    /**
     * Determines whether the clipboard contains data in the specified format.
     *
     * @param int $format  Clipboard format identifier (CF_TEXT = 1, CF_UNICODETEXT = 13, etc.).
     * @return bool  True if the format is available.
     */
    public function isClipboardFormatAvailable(int $format): bool
    {
        return (bool) $this->user32->IsClipboardFormatAvailable($format);
    }

    /**
     * Registers a new clipboard format or returns the identifier of an existing one.
     *
     * @param string $formatName  Name of the custom clipboard format.
     * @return int|false  Format identifier (>= 0xC000) on success, false on failure.
     */
    public function registerClipboardFormat(string $formatName): int|false
    {
        $id = $this->user32->RegisterClipboardFormatA($formatName);
        return $id !== 0 ? (int) $id : false;
    }

    /**
     * Retrieves the handle to a control in the specified dialog box by its identifier.
     *
     * @param CData $hDlg        Handle to the dialog box.
     * @param int   $nIDDlgItem  Identifier of the control.
     * @return CData|null  Control handle, or null if not found.
     */
    public function getDlgItem(CData $hDlg, int $nIDDlgItem): ?CData
    {
        $hwnd = $this->user32->GetDlgItem($hDlg, $nIDDlgItem);
        return $hwnd ?: null;
    }

    /**
     * Converts screen coordinates to client-area coordinates of the specified window.
     *
     * @param CData $hwnd  Window whose client area is used.
     * @param int   $x     Screen X coordinate.
     * @param int   $y     Screen Y coordinate.
     * @return array{x:int, y:int}|false
     */
    public function screenToClient(CData $hwnd, int $x, int $y): array|false
    {
        $pt = $this->user32->new('POINT');
        $pt->x = $x;
        $pt->y = $y;
        $ok = $this->user32->ScreenToClient($hwnd, FFI::addr($pt));
        return $ok ? ['x' => (int) $pt->x, 'y' => (int) $pt->y] : false;
    }

    /**
     * Converts client-area coordinates of the specified window to screen coordinates.
     *
     * @param CData $hwnd  Window whose client area is used.
     * @param int   $x     Client X coordinate.
     * @param int   $y     Client Y coordinate.
     * @return array{x:int, y:int}|false
     */
    public function clientToScreen(CData $hwnd, int $x, int $y): array|false
    {
        $pt = $this->user32->new('POINT');
        $pt->x = $x;
        $pt->y = $y;
        $ok = $this->user32->ClientToScreen($hwnd, FFI::addr($pt));
        return $ok ? ['x' => (int) $pt->x, 'y' => (int) $pt->y] : false;
    }

    /**
     * Retrieves the bounding rectangle of the specified window in screen coordinates.
     *
     * @param CData $hwnd  Handle to the window.
     * @return array{left:int, top:int, right:int, bottom:int, width:int, height:int}|false
     */
    public function getWindowRect(CData $hwnd): array|false
    {
        $rect = $this->user32->new('RECT');
        $ok = $this->user32->GetWindowRect($hwnd, FFI::addr($rect));
        if (!$ok) {
            return false;
        }
        return [
            'left' => (int) $rect->left,
            'top' => (int) $rect->top,
            'right' => (int) $rect->right,
            'bottom' => (int) $rect->bottom,
            'width' => (int) $rect->right - (int) $rect->left,
            'height' => (int) $rect->bottom - (int) $rect->top,
        ];
    }

    /**
     * Destroys the specified window and frees all associated resources.
     *
     * @param CData $hwnd  Handle to the window to destroy.
     * @return bool  True on success.
     */
    public function destroyWindow(CData $hwnd): bool
    {
        return (bool) $this->user32->DestroyWindow($hwnd);
    }

    /**
     * Returns the handle to the parent or owner window of the specified window.
     *
     * @param CData $hwnd  Handle to the child window.
     * @return CData|null  Parent window handle, or null if the window has no parent.
     */
    public function getParentWindow(CData $hwnd): ?CData
    {
        $parent = $this->user32->GetParent($hwnd);
        return $parent ?: null;
    }

    /**
     * Returns whether the window was created with the Unicode (wide-char) API.
     *
     * @param CData $hwnd  Handle to the window.
     * @return bool  True if the window is a Unicode window.
     */
    public function isWindowUnicode(CData $hwnd): bool
    {
        return (bool) $this->user32->IsWindowUnicode($hwnd);
    }

    /**
     * Retrieves information about the specified window via GetWindowLongA.
     *
     * @param CData $hwnd   Handle to the window.
     * @param int   $index  GWL_STYLE = -16, GWL_EXSTYLE = -20, GWL_ID = -12, GWLP_USERDATA = -21.
     * @return int  Requested value, or 0 on failure.
     */
    public function getWindowLong(CData $hwnd, int $index): int
    {
        return (int) $this->user32->GetWindowLongA($hwnd, $index);
    }

    /**
     * Changes an attribute of the specified window via SetWindowLongA.
     *
     * @param CData $hwnd      Handle to the window.
     * @param int   $index     GWL_STYLE = -16, GWL_EXSTYLE = -20, GWL_ID = -12.
     * @param int   $newValue  Replacement value.
     * @return int  Previous value of the specified offset.
     */
    public function setWindowLong(CData $hwnd, int $index, int $newValue): int
    {
        return (int) $this->user32->SetWindowLongA($hwnd, $index, $newValue);
    }

    /**
     * Opens a connection to the Service Control Manager on the local computer.
     *
     * @param int $desiredAccess  SCM access mask (SC_MANAGER_ALL_ACCESS = 0xF003F).
     * @return CData|null  SCM handle, or null on failure.
     */
    public function openSCManager(int $desiredAccess = 0xF003F): ?CData
    {
        $hScm = $this->advapi32->OpenSCManagerA(null, null, $desiredAccess);
        return $hScm ?: null;
    }

    /**
     * Opens a handle to an existing service.
     *
     * @param CData  $hScm          SCM handle from openSCManager().
     * @param string $serviceName   Name of the service.
     * @param int    $desiredAccess Service access mask (SERVICE_ALL_ACCESS = 0xF01FF).
     * @return CData|null  Service handle, or null on failure.
     */
    public function openService(CData $hScm, string $serviceName, int $desiredAccess = 0xF01FF): ?CData
    {
        $hService = $this->advapi32->OpenServiceA($hScm, $serviceName, $desiredAccess);
        return $hService ?: null;
    }

    /**
     * Closes a handle to a service or to the SCM itself.
     *
     * @param CData $hService  Handle to close.
     * @return bool  True on success.
     */
    public function closeServiceHandle(CData $hService): bool
    {
        return (bool) $this->advapi32->CloseServiceHandle($hService);
    }

    /**
     * Starts a service, optionally passing command-line arguments to it.
     *
     * @param CData    $hService  Handle to the service.
     * @param string[] $args      Optional service-start arguments.
     * @return bool  True on success.
     */
    public function startService(CData $hService, array $args = []): bool
    {
        return (bool) $this->advapi32->StartServiceA($hService, count($args), $args ?: null);
    }

    /**
     * Sends a STOP (1) control code to the specified service.
     *
     * @param CData $hService  Handle to the service.
     * @return array{currentState:int, waitHint:int}|false  Service status or false on failure.
     */
    public function stopService(CData $hService): array|false
    {
        $status = $this->advapi32->new('SERVICE_STATUS');
        $ok = $this->advapi32->ControlService($hService, 1, FFI::addr($status));
        return $ok ? ['currentState' => (int) $status->dwCurrentState, 'waitHint' => (int) $status->dwWaitHint] : false;
    }

    /**
     * Retrieves the current status of the specified service.
     *
     * dwCurrentState: 1=STOPPED, 2=START_PENDING, 3=STOP_PENDING, 4=RUNNING,
     * 5=CONTINUE_PENDING, 6=PAUSE_PENDING, 7=PAUSED.
     *
     * @param CData $hService  Handle to the service.
     * @return array{
     *  serviceType:int, 
     *  currentState:int, 
     *  controlsAccepted:int, 
     *  win32ExitCode:int, 
     *  checkPoint:int, 
     *  waitHint:int
     * }|false
     */
    public function queryServiceStatus(CData $hService): array|false
    {
        $status = $this->advapi32->new('SERVICE_STATUS');
        $ok = $this->advapi32->QueryServiceStatus($hService, FFI::addr($status));
        if (!$ok) {
            return false;
        }
        return [
            'serviceType' => (int) $status->dwServiceType,
            'currentState' => (int) $status->dwCurrentState,
            'controlsAccepted' => (int) $status->dwControlsAccepted,
            'win32ExitCode' => (int) $status->dwWin32ExitCode,
            'checkPoint' => (int) $status->dwCheckPoint,
            'waitHint' => (int) $status->dwWaitHint,
        ];
    }

    /**
     * Deletes the specified registry key (the key must have no subkeys).
     *
     * @param int    $rootKey  Root key constant.
     * @param string $subKey   Path of the key to delete.
     * @return bool  True on success.
     */
    public function deleteRegistryKey(int $rootKey, string $subKey): bool
    {
        return $this->advapi32->RegDeleteKeyA($rootKey, $subKey) === 0;
    }

    /**
     * Enumerates the subkey names of the specified open registry key.
     *
     * @param int    $rootKey  Root key constant (e.g., HKEY_LOCAL_MACHINE = 0x80000002).
     * @param string $subKey   Path to the parent key.
     * @return string[]  Array of subkey name strings.
     */
    public function enumRegistryKeys(int $rootKey, string $subKey): array
    {
        $hKey = $this->advapi32->new('HKEY');
        // KEY_READ = 0x20019
        if ($this->advapi32->RegOpenKeyExA($rootKey, $subKey, 0, 0x20019, FFI::addr($hKey)) !== 0) {
            return [];
        }

        $names = [];
        $index = 0;
        $nameBuf = $this->advapi32->new('char[256]');
        $nameLen = $this->advapi32->new('DWORD');

        while (true) {
            $nameLen->cdata = 256;
            $ret = $this->advapi32->RegEnumKeyExA($hKey, $index, $nameBuf, FFI::addr($nameLen), null, null, null, null);
            if ($ret !== 0) {
                break;
            }
            $names[] = FFI::string($nameBuf, (int) $nameLen->cdata);
            $index++;
        }

        $this->advapi32->RegCloseKey($hKey);
        return $names;
    }

    /**
     * Enumerates the value names and data of the specified open registry key.
     *
     * @param int    $rootKey  Root key constant.
     * @param string $subKey   Path to the key.
     * @return array<string, mixed>  Associative array of value name => value data.
     */
    public function enumRegistryValues(int $rootKey, string $subKey): array
    {
        $hKey = $this->advapi32->new('HKEY');
        if ($this->advapi32->RegOpenKeyExA($rootKey, $subKey, 0, 0x20019, FFI::addr($hKey)) !== 0) {
            return [];
        }

        $values = [];
        $index = 0;
        $nameBuf = $this->advapi32->new('char[256]');
        $dataBuf = $this->advapi32->new('char[4096]');
        $nameLen = $this->advapi32->new('DWORD');
        $dataLen = $this->advapi32->new('DWORD');
        $type = $this->advapi32->new('DWORD');

        while (true) {
            $nameLen->cdata = 256;
            $dataLen->cdata = 4096;
            // cast('BYTE*', $dataBuf) does not decay the array - FFI reads the
            // array's own bytes as the pointer, so RegEnumValueA was handed a
            // fabricated address and wrote the value there.
            $ret = $this->advapi32->RegEnumValueA($hKey, $index, $nameBuf, FFI::addr($nameLen), null, FFI::addr($type), $this->advapi32->cast('BYTE*', FFI::addr($dataBuf[0])), FFI::addr($dataLen));
            if ($ret !== 0) {
                break;
            }
            $name = FFI::string($nameBuf, (int) $nameLen->cdata);
            $raw = FFI::string($dataBuf, (int) $dataLen->cdata);
            // REG_SZ = 1, REG_DWORD = 4
            $values[$name] = ((int) $type->cdata === 4) ? unpack('V', substr($raw, 0, 4))[1] : rtrim($raw, "\0");
            $index++;
        }

        $this->advapi32->RegCloseKey($hKey);
        return $values;
    }

    /**
     * Creates the specified registry key, or opens it if it already exists.
     *
     * @param int    $rootKey  Root key constant.
     * @param string $subKey   Path of the key to create.
     * @return bool  True on success.
     */
    public function createRegistryKey(int $rootKey, string $subKey): bool
    {
        $hKey = $this->advapi32->new('HKEY');
        $disposition = $this->advapi32->new('DWORD');
        // KEY_ALL_ACCESS = 0xF003F
        $ret = $this->advapi32->RegCreateKeyExA($rootKey, $subKey, 0, null, 0, 0xF003F, null, FFI::addr($hKey), FFI::addr($disposition));
        if ($ret === 0) {
            $this->advapi32->RegCloseKey($hKey);
            return true;
        }
        return false;
    }

    /**
     * Opens the access token associated with the specified process.
     *
     * @param CData $hProcess      Handle to the process.
     * @param int   $desiredAccess Token access mask (TOKEN_QUERY = 0x8, TOKEN_ALL_ACCESS = 0xF01FF).
     * @return CData|null  Handle to the process token, or null on failure.
     */
    public function openProcessToken(CData $hProcess, int $desiredAccess = 0x8): ?CData
    {
        $hToken = $this->advapi32->new('HANDLE');
        $ok = $this->advapi32->OpenProcessToken($hProcess, $desiredAccess, FFI::addr($hToken));
        return $ok ? $hToken : null;
    }

    /**
     * Returns whether the current process is running with elevated (administrator) privileges.
     *
     * Checks the token elevation type via GetTokenInformation (TokenElevation = 20).
     *
     * @return bool  True if the process token is elevated.
     */
    public function isElevated(): bool
    {
        $hToken = $this->kernel32->new('HANDLE');
        $hProc = $this->kernel32->GetCurrentProcess();
        if (!$this->advapi32->OpenProcessToken($hProc, 0x8, FFI::addr($hToken))) {
            return false;
        }
        $elevation = $this->advapi32->new('DWORD');
        $returnLen = $this->advapi32->new('DWORD');
        $this->advapi32->GetTokenInformation($hToken, 20, FFI::addr($elevation), 4, FFI::addr($returnLen));
        $this->kernel32->CloseHandle($hToken);
        return (bool) $elevation->cdata;
    }

    /**
     * Checks whether the current user is a member of the Administrators group.
     *
     * Uses the shell32 IsUserAnAdmin() helper.
     *
     * @return bool  True if the user is an administrator.
     */
    public function isUserAdmin(): bool
    {
        return (bool) $this->shell32->IsUserAnAdmin();
    }

    /**
     * Retrieves the locally unique identifier (LUID) for the named privilege.
     *
     * @param string $privilegeName  Privilege constant name (e.g., "SeDebugPrivilege").
     * @return CData|null  LUID structure, or null on failure.
     */
    public function lookupPrivilegeValue(string $privilegeName): ?CData
    {
        $luid = $this->advapi32->new('LUID');
        $ok = $this->advapi32->LookupPrivilegeValueA(null, $privilegeName, FFI::addr($luid));
        return $ok ? $luid : null;
    }

    /**
     * Enables or disables a privilege in the access token of the calling process.
     *
     * @param string $privilegeName  e.g., "SeDebugPrivilege", "SeShutdownPrivilege".
     * @param bool   $enable         True to enable, false to disable.
     * @return bool  True on success.
     */
    public function adjustPrivilege(string $privilegeName, bool $enable = true): bool
    {
        $hToken = $this->kernel32->new('HANDLE');
        $hProc = $this->kernel32->GetCurrentProcess();
        // TOKEN_ADJUST_PRIVILEGES | TOKEN_QUERY = 0x28
        if (!$this->advapi32->OpenProcessToken($hProc, 0x28, FFI::addr($hToken))) {
            return false;
        }

        $luid = $this->advapi32->new('LUID');
        if (!$this->advapi32->LookupPrivilegeValueA(null, $privilegeName, FFI::addr($luid))) {
            $this->kernel32->CloseHandle($hToken);
            return false;
        }

        $tp = $this->advapi32->new('TOKEN_PRIVILEGES');
        $tp->PrivilegeCount = 1;
        $tp->Privileges[0]->Luid = $luid;
        // SE_PRIVILEGE_ENABLED = 2, SE_PRIVILEGE_REMOVED = 4
        $tp->Privileges[0]->Attributes = $enable ? 2 : 4;

        $ok = $this->advapi32->AdjustTokenPrivileges($hToken, 0, FFI::addr($tp), 0, null, null);
        $this->kernel32->CloseHandle($hToken);
        return $ok && $this->kernel32->GetLastError() === 0;
    }

    /**
     * Retrieves the window handle used by the console associated with the calling process.
     *
     * @return CData|null  HWND of the console window, or null if there is none.
     */
    public function getConsoleWindow(): ?CData
    {
        $hwnd = $this->kernel32->GetConsoleWindow();
        return $hwnd ?: null;
    }

    /**
     * Sets the foreground (text) and background color attributes for the console screen buffer.
     *
     * @param int $attributes  Combination of FOREGROUND_* and BACKGROUND_* flags.
     *                         e.g., FOREGROUND_GREEN (0x2) | FOREGROUND_INTENSITY (0x8).
     * @return bool  True on success.
     */
    public function setConsoleTextAttribute(int $attributes): bool
    {
        $hStdout = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        return (bool) $this->kernel32->SetConsoleTextAttribute($hStdout, $attributes);
    }

    /**
     * Retrieves information about the specified console screen buffer.
     *
     * @return array{
     *  sizeX: int, 
     *  sizeY: int, 
     *  cursorX: int, 
     *  cursorY: int, 
     *  attributes: int, 
     *  windowLeft: int, 
     *  windowTop: int, 
     *  windowRight: int, 
     *  windowBottom: int
     * }|false
     */
    public function getConsoleScreenBufferInfo(): array|false
    {
        $hStdout = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        $info = $this->kernel32->new('CONSOLE_SCREEN_BUFFER_INFO');
        $ok = $this->kernel32->GetConsoleScreenBufferInfo($hStdout, FFI::addr($info));
        if (!$ok) {
            return false;
        }
        return [
            'sizeX' => (int) $info->dwSize->X,
            'sizeY' => (int) $info->dwSize->Y,
            'cursorX' => (int) $info->dwCursorPosition->X,
            'cursorY' => (int) $info->dwCursorPosition->Y,
            'attributes' => (int) $info->wAttributes,
            'windowLeft' => (int) $info->srWindow->Left,
            'windowTop' => (int) $info->srWindow->Top,
            'windowRight' => (int) $info->srWindow->Right,
            'windowBottom' => (int) $info->srWindow->Bottom,
        ];
    }

    /**
     * Changes the size of the console screen buffer.
     *
     * @param int $columns  New width in character columns.
     * @param int $rows     New height in character rows.
     * @return bool  True on success.
     */
    public function setConsoleScreenBufferSize(int $columns, int $rows): bool
    {
        $hStdout = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        $size = $this->kernel32->new('COORD');
        $size->X = $columns;
        $size->Y = $rows;
        return (bool) $this->kernel32->SetConsoleScreenBufferSize($hStdout, $size);
    }

    /**
     * Allocates a new console for the calling process.
     *
     * A process can have at most one console. Fails if one is already attached.
     *
     * @return bool  True on success.
     */
    public function allocConsole(): bool
    {
        return (bool) $this->kernel32->AllocConsole();
    }

    /**
     * Detaches the calling process from its current console.
     *
     * @return bool  True on success.
     */
    public function freeConsole(): bool
    {
        return (bool) $this->kernel32->FreeConsole();
    }

    /**
     * Writes a string directly to the console output using WriteConsoleA.
     *
     * @param string $text  The string to write.
     * @return int|false    Number of characters written, or false on failure.
     */
    public function writeConsole(string $text): int|false
    {
        $hStdout = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        $written = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->WriteConsoleA($hStdout, $text, strlen($text), FFI::addr($written), null);
        return $ok ? (int) $written->cdata : false;
    }

    /**
     * Fills a region of the console screen buffer with the specified character.
     *
     * @param string $char   Single character to fill with.
     * @param int    $count  Number of cells to fill.
     * @param int    $x      Starting column.
     * @param int    $y      Starting row.
     * @return int|false  Number of cells written, or false on failure.
     */
    public function fillConsoleOutputCharacter(string $char, int $count, int $x, int $y): int|false
    {
        $hStdout = $this->kernel32->GetStdHandle(GetStdHandle::STD_OUTPUT_HANDLE->value);
        $coord = $this->kernel32->new('COORD');
        $coord->X = $x;
        $coord->Y = $y;
        $written = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->FillConsoleOutputCharacterA($hStdout, ord($char[0]), $count, $coord, FFI::addr($written));
        return $ok ? (int) $written->cdata : false;
    }

    /**
     * Retrieves the title bar string of the current console window.
     *
     * @return string|false  Console title string, or false on failure.
     */
    public function getConsoleTitle(): string|false
    {
        $buf = $this->kernel32->new('char[512]');
        $len = $this->kernel32->GetConsoleTitleA($buf, 512);
        return $len > 0 ? FFI::string($buf, $len) : false;
    }

    /**
     * Sets the priority class of the specified process.
     *
     * @param CData $hProcess      Handle to the process.
     * @param int   $priorityClass Priority class constant (HIGH_PRIORITY_CLASS = 0x80,
     *                             NORMAL_PRIORITY_CLASS = 0x20, REALTIME_PRIORITY_CLASS = 0x100).
     * @return bool  True on success.
     */
    public function setProcessPriority(CData $hProcess, int $priorityClass): bool
    {
        return (bool) $this->kernel32->SetPriorityClass($hProcess, $priorityClass);
    }

    /**
     * Retrieves the priority class for the specified process.
     *
     * @param CData $hProcess  Handle to the process.
     * @return int  Priority class constant, or 0 on failure.
     */
    public function getProcessPriority(CData $hProcess): int
    {
        return (int) $this->kernel32->GetPriorityClass($hProcess);
    }

    /**
     * Sets a processor affinity mask for the specified process.
     *
     * Each set bit in the mask corresponds to a logical processor the process may run on.
     *
     * @param CData $hProcess      Handle to the process.
     * @param int   $affinityMask  New processor affinity mask.
     * @return bool  True on success.
     */
    public function setProcessAffinityMask(CData $hProcess, int $affinityMask): bool
    {
        return (bool) $this->kernel32->SetProcessAffinityMask($hProcess, $affinityMask);
    }

    /**
     * Retrieves the process affinity mask and system affinity mask for the specified process.
     *
     * @param CData $hProcess  Handle to the process.
     * @return array{process:int, system:int}|false
     */
    public function getProcessAffinityMask(CData $hProcess): array|false
    {
        $proc = $this->kernel32->new('DWORD_PTR');
        $sys = $this->kernel32->new('DWORD_PTR');
        $ok = $this->kernel32->GetProcessAffinityMask($hProcess, FFI::addr($proc), FFI::addr($sys));
        return $ok ? ['process' => (int) $proc->cdata, 'system' => (int) $sys->cdata] : false;
    }

    /**
     * Suspends all threads in the target process via NtSuspendProcess (ntdll.dll).
     *
     * @param int $pid  Target process ID.
     * @return bool  True on success.
     */
    public function suspendProcess(int $pid): bool
    {
        $hProcess = $this->kernel32->OpenProcess(0x0800, 0, $pid);
        if (!$hProcess) {
            return false;
        }
        $result = $this->ntdll->NtSuspendProcess($hProcess);
        $this->kernel32->CloseHandle($hProcess);
        return $result === 0;
    }

    /**
     * Resumes a previously suspended process via NtResumeProcess (ntdll.dll).
     *
     * @param int $pid  Target process ID.
     * @return bool  True on success.
     */
    public function resumeProcess(int $pid): bool
    {
        $hProcess = $this->kernel32->OpenProcess(0x0800, 0, $pid);
        if (!$hProcess) {
            return false;
        }
        $result = $this->ntdll->NtResumeProcess($hProcess);
        $this->kernel32->CloseHandle($hProcess);
        return $result === 0;
    }

    /**
     * Retrieves creation, exit, kernel, and user time for the specified process.
     *
     * All values are 64-bit FILETIME integers (100-ns intervals since 1601-01-01 UTC).
     *
     * @param CData $hProcess  Handle to the process.
     * @return array{creationTime:int, exitTime:int, kernelTime:int, userTime:int}|false
     */
    public function getProcessTimes(CData $hProcess): array|false
    {
        $ct = $this->kernel32->new('FILETIME');
        $et = $this->kernel32->new('FILETIME');
        $kt = $this->kernel32->new('FILETIME');
        $ut = $this->kernel32->new('FILETIME');
        $ok = $this->kernel32->GetProcessTimes($hProcess, FFI::addr($ct), FFI::addr($et), FFI::addr($kt), FFI::addr($ut));
        if (!$ok) {
            return false;
        }
        $toInt = static fn($ft) => ((int) $ft->dwHighDateTime << 32) | (int) $ft->dwLowDateTime;
        return [
            'creationTime' => $toInt($ct),
            'exitTime' => $toInt($et),
            'kernelTime' => $toInt($kt),
            'userTime' => $toInt($ut),
        ];
    }

    /**
     * Sets the priority of the specified thread.
     *
     * @param CData $hThread   Handle to the thread.
     * @param int   $priority  THREAD_PRIORITY_LOWEST=-2, NORMAL=0, HIGHEST=2, TIME_CRITICAL=15.
     * @return bool  True on success.
     */
    public function setThreadPriority(CData $hThread, int $priority): bool
    {
        return (bool) $this->kernel32->SetThreadPriority($hThread, $priority);
    }

    /**
     * Retrieves the priority value of the specified thread.
     *
     * @param CData $hThread  Handle to the thread.
     * @return int  Thread priority level; THREAD_PRIORITY_ERROR_RETURN (0x7FFFFFFF) on failure.
     */
    public function getThreadPriority(CData $hThread): int
    {
        return (int) $this->kernel32->GetThreadPriority($hThread);
    }

    /**
     * Retrieves file system attributes for the specified file or directory.
     *
     * @param string $path  Path to the file or directory.
     * @return int|false  Attribute bitmask (FILE_ATTRIBUTE_*), or false on error.
     */
    public function getFileAttributes(string $path): int|false
    {
        $attr = $this->kernel32->GetFileAttributesA($path);
        return ($attr === 0xFFFFFFFF || $attr < 0) ? false : (int) $attr;
    }

    /**
     * Sets the attributes for the specified file or directory.
     *
     * @param string $path        Path to the file or directory.
     * @param int    $attributes  New attribute bitmask (FILE_ATTRIBUTE_*).
     * @return bool  True on success.
     */
    public function setFileAttributes(string $path, int $attributes): bool
    {
        return (bool) $this->kernel32->SetFileAttributesA($path, $attributes);
    }

    /**
     * Retrieves the creation, last-access, and last-write timestamps of the specified file.
     *
     * Each value is a 64-bit Windows FILETIME (100-nanosecond intervals since 1601-01-01 UTC).
     *
     * @param string $filePath  Path to the file.
     * @return array{creation:int, lastAccess:int, lastWrite:int}|false
     */
    public function getFileTimestamps(string $filePath): array|false
    {
        $hFile = $this->kernel32->CreateFileA($filePath, self::GENERIC_READ, self::FILE_SHARE_READ, null, self::OPEN_EXISTING, self::FILE_ATTRIBUTE_NORMAL, null);
        if (!$hFile || (int) $this->kernel32->cast('intptr_t', $hFile)->cdata === self::INVALID_HANDLE_VALUE) {
            return false;
        }

        $ct = $this->kernel32->new('FILETIME');
        $lat = $this->kernel32->new('FILETIME');
        $lwt = $this->kernel32->new('FILETIME');
        $ok = $this->kernel32->GetFileTime($hFile, FFI::addr($ct), FFI::addr($lat), FFI::addr($lwt));
        $this->kernel32->CloseHandle($hFile);

        if (!$ok) {
            return false;
        }

        $toInt = static fn($ft) => (int) $ft->dwHighDateTime << 32 | (int) $ft->dwLowDateTime;
        return [
            'creation' => $toInt($ct),
            'lastAccess' => $toInt($lat),
            'lastWrite' => $toInt($lwt),
        ];
    }

    /**
     * Flushes the buffers of the specified file to disk, ensuring all data is written.
     *
     * @param CData $hFile  Open file handle.
     * @return bool  True on success.
     */
    public function flushFileBuffers(CData $hFile): bool
    {
        return (bool) $this->kernel32->FlushFileBuffers($hFile);
    }

    /**
     * Locks a byte range of a file, preventing other processes from accessing the locked range.
     *
     * @param CData $hFile       Open file handle.
     * @param int   $offsetLow   Low-order DWORD of the byte offset.
     * @param int   $offsetHigh  High-order DWORD of the byte offset.
     * @param int   $countLow    Low-order DWORD of the byte range to lock.
     * @param int   $countHigh   High-order DWORD of the byte range to lock.
     * @return bool  True on success.
     */
    public function lockFile(CData $hFile, int $offsetLow, int $offsetHigh, int $countLow, int $countHigh): bool
    {
        return (bool) $this->kernel32->LockFile($hFile, $offsetLow, $offsetHigh, $countLow, $countHigh);
    }

    /**
     * Unlocks a byte range of a file previously locked with lockFile().
     *
     * @param CData $hFile       Open file handle.
     * @param int   $offsetLow   Low-order DWORD of the byte offset.
     * @param int   $offsetHigh  High-order DWORD of the byte offset.
     * @param int   $countLow    Low-order DWORD of the byte range to unlock.
     * @param int   $countHigh   High-order DWORD of the byte range to unlock.
     * @return bool  True on success.
     */
    public function unlockFile(CData $hFile, int $offsetLow, int $offsetHigh, int $countLow, int $countHigh): bool
    {
        return (bool) $this->kernel32->UnlockFile($hFile, $offsetLow, $offsetHigh, $countLow, $countHigh);
    }

    /**
     * Truncates or extends the file at the current file pointer position.
     *
     * @param CData $hFile  Open file handle with GENERIC_WRITE access.
     * @return bool  True on success.
     */
    public function setEndOfFile(CData $hFile): bool
    {
        return (bool) $this->kernel32->SetEndOfFile($hFile);
    }

    /**
     * Creates an instance of a named pipe and returns a handle for subsequent operations.
     *
     * @param string $pipeName    Pipe name in the form \\.\pipe\<name>.
     * @param int    $openMode    Open mode (PIPE_ACCESS_DUPLEX = 0x3).
     * @param int    $pipeMode    Pipe type/mode flags (PIPE_TYPE_BYTE = 0x0).
     * @param int    $maxInst     Maximum number of instances (PIPE_UNLIMITED_INSTANCES = 255).
     * @param int    $outBufSize  Output buffer size in bytes.
     * @param int    $inBufSize   Input buffer size in bytes.
     * @param int    $timeout     Default time-out value in milliseconds.
     * @return CData|null  Handle to the server end of the pipe, or null on failure.
     */
    public function createNamedPipe(string $pipeName, int $openMode = 0x3, int $pipeMode = 0x0, int $maxInst = 255, int $outBufSize = 4096, int $inBufSize = 4096, int $timeout = 0): ?CData
    {
        $hPipe = $this->kernel32->CreateNamedPipeA($pipeName, $openMode, $pipeMode, $maxInst, $outBufSize, $inBufSize, $timeout, null);
        return $hPipe ?: null;
    }

    /**
     * Enables a named-pipe server process to wait for a client process to connect.
     *
     * @param CData $hPipe  Handle to the named pipe instance.
     * @return bool  True when a client connects, false on error.
     */
    public function connectNamedPipe(CData $hPipe): bool
    {
        return (bool) $this->kernel32->ConnectNamedPipe($hPipe, null);
    }

    /**
     * Disconnects the server end of a named pipe from a client process.
     *
     * @param CData $hPipe  Handle to the named pipe instance.
     * @return bool  True on success.
     */
    public function disconnectNamedPipe(CData $hPipe): bool
    {
        return (bool) $this->kernel32->DisconnectNamedPipe($hPipe);
    }

    /**
     * Waits until either a named pipe instance is available or the timeout elapses.
     *
     * @param string $pipeName   Pipe name in the form \\.\pipe\<name>.
     * @param int    $timeoutMs  Timeout in milliseconds; NMPWAIT_WAIT_FOREVER = 0xFFFFFFFF.
     * @return bool  True if a pipe instance is available before the timeout.
     */
    public function waitNamedPipe(string $pipeName, int $timeoutMs = 0xFFFFFFFF): bool
    {
        return (bool) $this->kernel32->WaitNamedPipeA($pipeName, $timeoutMs);
    }

    /**
     * Creates or opens a named or unnamed file-mapping object for the specified file.
     *
     * Pass INVALID_HANDLE_VALUE as $hFile to create a mapping backed by the paging file.
     *
     * @param CData|int   $hFile    Handle to the file; use -1 for page-file-backed shared memory.
     * @param int         $protect  Page protection (e.g., PAGE_READWRITE = 0x04).
     * @param int         $maxSize  Maximum size of the mapping in bytes.
     * @param string|null $name     Optional name for the mapping object.
     * @return CData|null  Handle to the mapping, or null on failure.
     */
    public function createFileMapping(CData|int $hFile, int $protect, int $maxSize, ?string $name = null): ?CData
    {
        $hi = ($maxSize >> 32) & 0xFFFFFFFF;
        $lo = $maxSize & 0xFFFFFFFF;
        $hMap = $this->kernel32->CreateFileMappingA(is_int($hFile) ? $this->kernel32->cast('HANDLE', $hFile) : $hFile, null, $protect, $hi, $lo, $name);
        return $hMap ?: null;
    }

    /**
     * Maps a view of a file mapping into the address space of the calling process.
     *
     * @param CData $hMap         Handle from createFileMapping().
     * @param int   $access       Desired access (FILE_MAP_ALL_ACCESS = 0xF001F, FILE_MAP_READ = 0x4).
     * @param int   $offset       Byte offset in the file mapping where the view begins.
     * @param int   $bytesToMap   Number of bytes to map; 0 maps the entire mapping.
     * @return CData|null  Base address of the mapped view, or null on failure.
     */
    public function mapViewOfFile(CData $hMap, int $access = 0xF001F, int $offset = 0, int $bytesToMap = 0): ?CData
    {
        $offHi = ($offset >> 32) & 0xFFFFFFFF;
        $offLo = $offset & 0xFFFFFFFF;
        $ptr = $this->kernel32->MapViewOfFile($hMap, $access, $offHi, $offLo, $bytesToMap);
        return $ptr ?: null;
    }

    /**
     * Unmaps a mapped view of a file from the calling process's address space.
     *
     * @param CData $baseAddress  Base address returned by mapViewOfFile().
     * @return bool  True on success.
     */
    public function unmapViewOfFile(CData $baseAddress): bool
    {
        return (bool) $this->kernel32->UnmapViewOfFile($baseAddress);
    }

    /**
     * Flushes dirty pages within a mapped view range to the backing file on disk.
     *
     * @param CData $baseAddress    Base address of the mapped view.
     * @param int   $numberOfBytes  Number of bytes to flush; 0 flushes to end of mapping.
     * @return bool  True on success.
     */
    public function flushViewOfFile(CData $baseAddress, int $numberOfBytes = 0): bool
    {
        return (bool) $this->kernel32->FlushViewOfFile($baseAddress, $numberOfBytes);
    }

    /**
     * Reserves, commits, or changes the state of a region of pages in virtual address space.
     *
     * @param int $size        Size in bytes to allocate.
     * @param int $allocType   MEM_COMMIT (0x1000) | MEM_RESERVE (0x2000).
     * @param int $protect     Page protection (e.g., PAGE_READWRITE = 0x04).
     * @return CData|null  Pointer to the allocated base address, or null on failure.
     */
    public function virtualAlloc(int $size, int $allocType = 0x3000, int $protect = 0x04): ?CData
    {
        $ptr = $this->kernel32->VirtualAlloc(null, $size, $allocType, $protect);
        return $ptr ?: null;
    }

    /**
     * Releases, decommits, or releases and decommits a region of pages within virtual address space.
     *
     * @param CData $address  Base address returned by virtualAlloc().
     * @param int   $size     Size in bytes; must be 0 when freeType is MEM_RELEASE (0x8000).
     * @param int   $freeType MEM_DECOMMIT (0x4000) or MEM_RELEASE (0x8000).
     * @return bool  True on success.
     */
    public function virtualFree(CData $address, int $size = 0, int $freeType = 0x8000): bool
    {
        return (bool) $this->kernel32->VirtualFree($address, $size, $freeType);
    }

    /**
     * Changes the protection on a region of committed pages in the virtual address space.
     *
     * @param CData $address    Base address of the region.
     * @param int   $size       Size in bytes.
     * @param int   $newProtect New protection (e.g., PAGE_READONLY = 0x02).
     * @return int|false  Old protection flags on success, false on failure.
     */
    public function virtualProtect(CData $address, int $size, int $newProtect): int|false
    {
        $old = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->VirtualProtect($address, $size, $newProtect, FFI::addr($old));
        return $ok ? (int) $old->cdata : false;
    }

    /**
     * Allocates bytes from the process default heap.
     *
     * @param int $bytes  Number of bytes to allocate.
     * @param int $flags  Heap allocation control flags (default: HEAP_ZERO_MEMORY = 0x8).
     * @return CData|null  Pointer to the allocated memory, or null on failure.
     */
    public function heapAlloc(int $bytes, int $flags = 0x8): ?CData
    {
        $hHeap = $this->kernel32->GetProcessHeap();
        if (!$hHeap) {
            return null;
        }
        $ptr = $this->kernel32->HeapAlloc($hHeap, $flags, $bytes);
        return $ptr ?: null;
    }

    /**
     * Frees a memory block allocated from the process default heap.
     *
     * @param CData $ptr  Pointer returned by heapAlloc().
     * @return bool  True on success.
     */
    public function heapFree(CData $ptr): bool
    {
        $hHeap = $this->kernel32->GetProcessHeap();
        if (!$hHeap) {
            return false;
        }
        return (bool) $this->kernel32->HeapFree($hHeap, 0, $ptr);
    }

    /**
     * Retrieves extended memory status using the 64-bit MEMORYSTATUSEX structure.
     *
     * Complements getGlobalMemoryStatus() / getMemoryStatus() with 64-bit field sizes.
     *
     * @return array{
     *  memoryLoad:int,
     *  totalPhys:int,
     *  availPhys:int,
     *  totalPageFile:int,
     *  availPageFile:int,
     *  totalVirtual:int,
     *  availVirtual:int
     * }|false
     */
    public function getMemoryStatusEx(): array|false
    {
        $ms = $this->kernel32->new('MEMORYSTATUSEX');
        $ms->dwLength = FFI::sizeof($ms);
        if (!$this->kernel32->GlobalMemoryStatusEx(FFI::addr($ms))) {
            return false;
        }
        return [
            'memoryLoad' => (int) $ms->dwMemoryLoad,
            'totalPhys' => (int) $ms->ullTotalPhys,
            'availPhys' => (int) $ms->ullAvailPhys,
            'totalPageFile' => (int) $ms->ullTotalPageFile,
            'availPageFile' => (int) $ms->ullAvailPageFile,
            'totalVirtual' => (int) $ms->ullTotalVirtual,
            'availVirtual' => (int) $ms->ullAvailVirtual,
        ];
    }

    /**
     * Waits until the specified object is in the signaled state or the timeout elapses.
     *
     * @param CData $handle       Handle to the synchronization object (thread, mutex, event, semaphore).
     * @param int   $milliseconds Timeout in milliseconds; INFINITE (0xFFFFFFFF) waits forever.
     * @return int  0 = WAIT_OBJECT_0 (signaled), 0x102 = WAIT_TIMEOUT, 0xFFFFFFFF = WAIT_FAILED.
     */
    public function waitForSingleObject(CData $handle, int $milliseconds = 0xFFFFFFFF): int
    {
        return (int) $this->kernel32->WaitForSingleObject($handle, $milliseconds);
    }

    /**
     * Waits until one or all of the specified objects are signaled, or the timeout elapses.
     *
     * @param CData[] $handles       Array of kernel object handles.
     * @param bool    $waitAll       True = wait for all handles; false = wait for any one.
     * @param int     $milliseconds  Timeout in milliseconds.
     * @return int    Index of the signaled handle (waitAll=false), 0 (waitAll=true), or error code.
     */
    public function waitForMultipleObjects(array $handles, bool $waitAll = false, int $milliseconds = 0xFFFFFFFF): int
    {
        $count = count($handles);
        $arr = $this->kernel32->new("HANDLE[$count]");
        foreach ($handles as $i => $h) {
            $arr[$i] = $h;
        }
        return (int) $this->kernel32->WaitForMultipleObjects($count, $arr, $waitAll ? 1 : 0, $milliseconds);
    }

    /**
     * Creates or opens a named or unnamed mutex object.
     *
     * @param bool        $initialOwner  True if the calling thread owns the mutex immediately.
     * @param string|null $name          Optional name for the mutex (null = unnamed).
     * @return CData|null  Handle to the mutex, or null on failure.
     */
    public function createMutex(bool $initialOwner = false, ?string $name = null): ?CData
    {
        $hMutex = $this->kernel32->CreateMutexA(null, $initialOwner ? 1 : 0, $name);
        return $hMutex ?: null;
    }

    /**
     * Releases ownership of a mutex object, making it available to other waiting threads.
     *
     * @param CData $hMutex  Handle obtained from createMutex().
     * @return bool  True on success.
     */
    public function releaseMutex(CData $hMutex): bool
    {
        return (bool) $this->kernel32->ReleaseMutex($hMutex);
    }

    /**
     * Creates or opens a named or unnamed event object.
     *
     * @param bool        $manualReset   True = manual-reset event; false = auto-reset.
     * @param bool        $initialState  True = initially signaled.
     * @param string|null $name          Optional name for the event.
     * @return CData|null  Handle to the event, or null on failure.
     */
    public function createEvent(bool $manualReset = false, bool $initialState = false, ?string $name = null): ?CData
    {
        $hEvent = $this->kernel32->CreateEventA(null, $manualReset ? 1 : 0, $initialState ? 1 : 0, $name);
        return $hEvent ?: null;
    }

    /**
     * Sets an event object to the signaled state.
     *
     * @param CData $hEvent  Handle obtained from createEvent().
     * @return bool  True on success.
     */
    public function setEvent(CData $hEvent): bool
    {
        return (bool) $this->kernel32->SetEvent($hEvent);
    }

    /**
     * Resets a manual-reset event object to the non-signaled state.
     *
     * @param CData $hEvent  Handle obtained from createEvent().
     * @return bool  True on success.
     */
    public function resetEvent(CData $hEvent): bool
    {
        return (bool) $this->kernel32->ResetEvent($hEvent);
    }

    /**
     * Creates or opens a named or unnamed semaphore object.
     *
     * @param int         $initialCount  Initial resource count (0..maximumCount).
     * @param int         $maximumCount  Maximum resource count.
     * @param string|null $name          Optional name for the semaphore.
     * @return CData|null  Handle to the semaphore, or null on failure.
     */
    public function createSemaphore(int $initialCount, int $maximumCount, ?string $name = null): ?CData
    {
        $hSem = $this->kernel32->CreateSemaphoreA(null, $initialCount, $maximumCount, $name);
        return $hSem ?: null;
    }

    /**
     * Increases the count of the specified semaphore object by a given amount.
     *
     * @param CData    $hSemaphore   Handle obtained from createSemaphore().
     * @param int      $releaseCount Count to add (must be >= 1).
     * @return int|false  Previous count on success, false on failure.
     */
    public function releaseSemaphore(CData $hSemaphore, int $releaseCount = 1): int|false
    {
        $prev = $this->kernel32->new('LONG');
        $ok = $this->kernel32->ReleaseSemaphore($hSemaphore, $releaseCount, FFI::addr($prev));
        return $ok ? (int) $prev->cdata : false;
    }

    /**
     * Suspends the execution of a thread.
     *
     * @param CData $hThread  Handle to the thread to suspend.
     * @return int   Previous suspend count, or -1 on failure.
     */
    public function suspendThread(CData $hThread): int
    {
        return (int) $this->kernel32->SuspendThread($hThread);
    }

    /**
     * Decrements the suspend count of a thread; when it reaches zero the thread resumes.
     *
     * @param CData $hThread  Handle to the suspended thread.
     * @return int   Previous suspend count, or -1 on failure.
     */
    public function resumeThread(CData $hThread): int
    {
        return (int) $this->kernel32->ResumeThread($hThread);
    }

    /**
     * Terminates a thread and sets its exit code.
     *
     * @param CData $hThread   Handle to the thread.
     * @param int   $exitCode  Exit code for the thread.
     * @return bool  True on success.
     */
    public function terminateThread(CData $hThread, int $exitCode = 0): bool
    {
        return (bool) $this->kernel32->TerminateThread($hThread, $exitCode);
    }

    /**
     * Retrieves the exit code of the specified thread.
     *
     * Returns STILL_ACTIVE (259) if the thread has not terminated.
     *
     * @param CData $hThread  Handle to the thread.
     * @return int|false  Exit code on success, false on failure.
     */
    public function getThreadExitCode(CData $hThread): int|false
    {
        $code = $this->kernel32->new('DWORD');
        $ok = $this->kernel32->GetExitCodeThread($hThread, FFI::addr($code));
        return $ok ? (int) $code->cdata : false;
    }

    /**
     * Build a binary dialog template used by the input dialog.
     *
     * @param string $title
     * @param string $prompt
     *
     * @return string
     */
    private function buildDialogTemplate(string $title, string $prompt): string
    {
        $wc = static fn(string $s): string => mb_convert_encoding($s . "\0", 'UTF-16LE', 'UTF-8');
        $pad = static function (string &$b): void {
            while (strlen($b) % 4) {
                $b .= "\0";
            }
        };

        // DS_SETFONT|DS_MODALFRAME|DS_CENTER|WS_POPUP|WS_CAPTION|WS_SYSMENU
        $dlgStyle = 0x40 | 0x80 | 0x800 | 0x80000000 | 0x00C00000 | 0x00080000;

        $b = pack('VVvvvvv', $dlgStyle, 0, 4, 0, 0, 220, 85);
        $b .= "\x00\x00\x00\x00" . $wc($title);
        $b .= pack('v', 9) . $wc('Segoe UI');
        $pad($b);

        // Static text (ID=0xFFFF, no tab stop)
        $b .= pack('VVvvvvv', 0x50000000, 0, 7, 10, 206, 12, 0xFFFF);
        $b .= "\xFF\xFF\x82\x00" . $wc($prompt) . "\x00\x00";
        $pad($b);

        // Edit (ID=100): WS_CHILD|WS_VISIBLE|WS_TABSTOP|WS_BORDER|ES_AUTOHSCROLL
        $b .= pack('VVvvvvv', 0x50810080, 0, 7, 26, 206, 14, 100);
        $b .= "\xFF\xFF\x81\x00\x00\x00\x00\x00";
        $pad($b);

        // OK (IDOK=1): WS_CHILD|WS_VISIBLE|WS_TABSTOP|BS_DEFPUSHBUTTON
        $b .= pack('VVvvvvv', 0x50010001, 0, 56, 60, 65, 14, 1);
        $b .= "\xFF\xFF\x80\x00" . $wc('OK') . "\x00\x00";
        $pad($b);

        // Cancel (IDCANCEL=2): WS_CHILD|WS_VISIBLE|WS_TABSTOP
        $b .= pack('VVvvvvv', 0x50010000, 0, 130, 60, 65, 14, 2);
        $b .= "\xFF\xFF\x80\x00" . $wc('Cancel') . "\x00\x00";

        return $b;
    }

    /**
     * Show a native input dialog and return submitted text.
     *
     * @param string $title
     * @param string $prompt
     *
     * @return string|null
     */
    public function showInputBox(string $title, string $prompt): ?string
    {
        $tpl = $this->buildDialogTemplate($title, $prompt);
        $tplBuf = $this->user32->new('uint8_t[' . strlen($tpl) . ']');
        FFI::memcpy($tplBuf, $tpl, strlen($tpl));

        $inputBuf = $this->user32->new('wchar_t[1024]');
        $confirmed = false;

        $proc = function ($hDlg, int $msg, $wParam, $lParam) use ($inputBuf, &$confirmed): int {
            if ($msg === 0x0110) {
                return 1; // WM_INITDIALOG
            }
            if ($msg === 0x0111) { // WM_COMMAND
                $id = (int) $wParam & 0xFFFF;
                if ($id === 1) {
                    $this->user32->GetDlgItemTextW($hDlg, 100, $inputBuf, 1024);
                    $confirmed = true;
                    $this->user32->EndDialog($hDlg, 1);
                    return 1;
                }
                if ($id === 2) {
                    $this->user32->EndDialog($hDlg, 0);
                    return 1;
                }
            }
            if ($msg === 0x0010) { // WM_CLOSE
                $this->user32->EndDialog($hDlg, 0);
                return 1;
            }
            return 0;
        };

        $this->user32->DialogBoxIndirectParamW(null, $tplBuf, null, $proc, 0);

        if (!$confirmed) {
            return null;
        }

        return $this->readWideChar($inputBuf, 2048);
    }


    /**
     * Build a modal lock-screen dialog template.
     *
     * @return string
     */
    public function buildLockScreenTemplate(): string
    {
        $pad = static function (string &$b): void {
            while (strlen($b) % 4) {
                $b .= "\0";
            }
        };

        // DS_SETFONT=0x40 | DS_MODALFRAME=0x80 | WS_POPUP=0x80000000, exStyle=WS_EX_TOPMOST=0x08
        $b = pack('VVvvvvv', 0x800000C0, 0x00000008, 4, 0, 0, 100, 100);
        $b .= "\x00\x00\x00\x00" . $this->toWideChar('');
        $b .= pack('v', 9) . $this->toWideChar('Segoe UI');
        $pad($b);

        // Label ID=200
        $b .= pack('VVvvvvv', 0x50000000, 0, 10, 10, 80, 10, 200);
        $b .= "\xFF\xFF\x82\x00" . $this->toWideChar('Enter password') . "\x00\x00";
        $pad($b);

        // Edit ID=100: WS_CHILD|WS_VISIBLE|WS_TABSTOP|WS_BORDER|ES_AUTOHSCROLL=0x80|ES_PASSWORD=0x20
        $b .= pack('VVvvvvv', 0x508100A0, 0, 10, 24, 80, 14, 100);
        $b .= "\xFF\xFF\x81\x00\x00\x00\x00\x00";
        $pad($b);

        // Unlock IDOK=1: WS_CHILD|WS_VISIBLE|WS_TABSTOP|BS_DEFPUSHBUTTON
        $b .= pack('VVvvvvv', 0x50010001, 0, 10, 42, 80, 14, 1);
        $b .= "\xFF\xFF\x80\x00" . $this->toWideChar('Unlock') . "\x00\x00";
        $pad($b);

        // Error label ID=201
        $b .= pack('VVvvvvv', 0x50000000, 0, 10, 60, 80, 10, 201);
        $b .= "\xFF\xFF\x82\x00" . $this->toWideChar('') . "\x00\x00";

        return $b;
    }

    /**
     * Convert a pointer CData value into a signed integer.
     *
     * @param CData $ptr
     *
     * @return int
     */
    public function ptr_to_int(CData $ptr): int
    {
        $buf = $this->kernel32->new('char[8]');
        $from = FFI::addr($ptr);
        FFI::memcpy($buf, $from, 8);
        return unpack('q', FFI::string($buf, 8))[1];
    }

    /**
     * Set a window caption using SetWindowTextW.
     *
     * @param CData $hWnd
     * @param string $text
     *
     * @return void
     */
    public function setWindowTextW(CData $hWnd, string $text): void
    {
        $ws = $this->toWideChar($text);
        $buf = $this->user32->new('wchar_t[' . (strlen($ws) / 2) . ']');
        FFI::memcpy($buf, $ws, strlen($ws));
        $this->user32->SetWindowTextW($hWnd, $buf);
    }

    /**
     * Show an always-on-top lock screen dialog and block input until unlocked.
     *
     * @param string $password
     *
     * @return void
     */
    public function showLockScreen(string $password): void
    {
        $tpl = $this->buildLockScreenTemplate();
        $tplBuf = $this->user32->new('uint8_t[' . strlen($tpl) . ']');
        FFI::memcpy($tplBuf, $tpl, strlen($tpl));

        $inputBuf = $this->user32->new('wchar_t[256]');
        $blackBrush = $this->gdi32->CreateSolidBrush(0x00000000);
        $darkBrush = $this->gdi32->CreateSolidBrush(0x00222222);
        $hookHandle = null;

        $hookProc = function (int $nCode, $wParam, $kb) use (&$hookHandle): int {
            if ($nCode < 0) {
                return (int) $this->user32->CallNextHookEx($hookHandle, $nCode, $wParam, $kb);
            }
            $vk = $kb->vkCode;
            $isAlt = (bool) ($kb->flags & 0x20);
            $isCtrl = (bool) ($this->user32->GetKeyState(0x11) & 0x8000);

            if (
                ($isAlt && in_array($vk, [0x09, 0x1B, 0x73], true))  // Alt+Tab, Alt+Esc, Alt+F4
                || ($isCtrl && $vk === 0x1B)                           // Ctrl+Esc (Start menu)
                || ($isCtrl && $isAlt && $vk === 0x09)                 // Ctrl+Alt+Tab
                || in_array($vk, [0x5B, 0x5C], true)                  // VK_LWIN, VK_RWIN
            ) {
                return 1;
            }
            return (int) $this->user32->CallNextHookEx($hookHandle, $nCode, $wParam, $kb);
        };

        $hookHandle = $this->user32->SetWindowsHookExW(13, $hookProc, null, 0); // WH_KEYBOARD_LL=13

        $dlgProc = function ($hDlg, int $msg, $wParam, $lParam) use ($inputBuf, $blackBrush, $darkBrush, $password, &$hookHandle): int {
            if ($msg === 0x0110) { // WM_INITDIALOG
                $sw = $this->user32->GetSystemMetrics(0); // SM_CXSCREEN
                $sh = $this->user32->GetSystemMetrics(1); // SM_CYSCREEN
                $pointer = -1;
                $this->user32->SetWindowPos($hDlg, $this->gdi32->cast("void*", $pointer), 0, 0, $sw, $sh, 0x0040); // HWND_TOPMOST|SWP_SHOWWINDOW

                $cx = intdiv($sw, 2);
                $cy = intdiv($sh, 2);
                $pointer = 0;
                $this->user32->MoveWindow($this->user32->GetDlgItem($hDlg, 200), $cx - 150, $cy - 60, 300, 20, $this->gdi32->cast("bool*", $pointer));
                $this->user32->MoveWindow($this->user32->GetDlgItem($hDlg, 100), $cx - 150, $cy - 35, 300, 25, $this->gdi32->cast("bool*", $pointer));
                $this->user32->MoveWindow($this->user32->GetDlgItem($hDlg, 1), $cx - 75, $cy + 5, 150, 28, $this->gdi32->cast("bool*", $pointer));
                $this->user32->MoveWindow($this->user32->GetDlgItem($hDlg, 201), $cx - 150, $cy + 45, 300, 20, $this->gdi32->cast("bool*", $pointer));

                $pointer = 0;
                $this->user32->SetFocus($this->user32->GetDlgItem($hDlg, 100));
                $this->user32->SetTimer($hDlg, 1, 300, null);
                $this->user32->ShowCursor($this->gdi32->cast("void*", $pointer));

                $taskbarHwnd = $this->user32->FindWindowA("Shell_TrayWnd", null);
                if ($taskbarHwnd !== null) {
                    $this->user32->ShowWindow($taskbarHwnd, 0); // SW_HIDE
                }

                return 0;
            }

            if ($msg === 0x0113) { // WM_TIMER: topmost
                $pointer = -1;
                $this->user32->SetWindowPos($hDlg, $this->gdi32->cast("void*", $pointer), 0, 0, 0, 0, 0x0003); // SWP_NOMOVE|SWP_NOSIZE
                $this->user32->SetForegroundWindow($hDlg);
                return 0;
            }

            if ($msg === 0x0014) { // WM_ERASEBKGND
                $this->gdi32->BitBlt($wParam, 0, 0, 32767, 32767, 0, 0, 0, 0x00000042); // BLACKNESS
                return 1;
            }

            if ($msg === 0x0136 || $msg === 0x0138) { // WM_CTLCOLORDLG, WM_CTLCOLORSTATIC
                $pointer = 0x00000000;
                $this->gdi32->SetBkColor($this->gdi32->cast("void*", $wParam), $this->gdi32->cast("void*", $pointer));
                $this->gdi32->SetTextColor($wParam, 0x00FFFFFF);
                return $this->ptr_to_int($blackBrush);
            }

            if ($msg === 0x0133) { // WM_CTLCOLOREDIT
                $pointer = 0x00222222;
                $this->gdi32->SetBkColor($this->gdi32->cast("void*", $wParam), $this->gdi32->cast("void*", $pointer));
                $this->gdi32->SetTextColor($wParam, 0x00FFFFFF);
                return $this->ptr_to_int($darkBrush);
            }

            if ($msg === 0x0111 && ((int) $wParam & 0xFFFF) === 1) { // WM_COMMAND IDOK
                $this->user32->GetDlgItemTextW($hDlg, 100, $inputBuf, 256);
                $entered = $this->readWideChar($inputBuf, 512);

                if ($entered === $password) {
                    $this->user32->KillTimer($hDlg, 1);
                    $this->user32->UnhookWindowsHookEx($hookHandle);
                    $hookHandle = null;
                    $this->user32->ShowCursor(1);
                    $this->user32->EndDialog($hDlg, 1);
                } else {
                    $this->setWindowTextW($this->user32->GetDlgItem($hDlg, 201), 'Wrong password');
                    $this->setWindowTextW($this->user32->GetDlgItem($hDlg, 100), '');
                    $this->user32->SetFocus($this->user32->GetDlgItem($hDlg, 100));
                }
                return 1;
            }

            if ($msg === 0x0086 || $msg === 0x0010) {
                $taskbarHwnd = $this->user32->FindWindowA("Shell_TrayWnd", null);
                if ($taskbarHwnd !== null) {
                    $this->user32->ShowWindow($taskbarHwnd, 1);
                }
                return 1; // WM_NCACTIVATE, WM_CLOSE
            }

            return 0;
        };

        $this->user32->DialogBoxIndirectParamW(null, $tplBuf, null, $dlgProc, 0);

        if ($hookHandle !== null) {
            $this->user32->UnhookWindowsHookEx($hookHandle);
        }
        $this->gdi32->DeleteObject($blackBrush);
        $this->gdi32->DeleteObject($darkBrush);
    }


    /**
     * Mute all active audio sessions except this process.
     *
     * @return void
     */
    public function muteAllExceptSelf(): void
    {
        /** @var IAudioSessionEnumeratorVtbl $pEnum */
        $pEnum = $this->createDeviceEnumerator();
        /** @var IAudioSessionEnumeratorVtbl $pDevice */
        $pDevice = $this->getDefaultDevice($pEnum);
        /** @var IAudioSessionManager $pMgr */
        $pMgr = $this->activateSessionManager($pDevice);
        /** @var IAudioSessionEnumeratorVtbl $pSessEnum */
        $pSessEnum = $this->getSessionEnumerator($pMgr);
        $count = $this->getSessionCount($pSessEnum);

        for ($i = 0; $i < $count; $i++) {
            // ── GetSession → IAudioSessionControl (void*) ──────
            $ppRaw = $this->ole32->new('void*');
            $hr = $pSessEnum->lpVtbl->GetSession($pSessEnum, $i, FFI::addr($ppRaw));
            if ($hr < 0 || FFI::isNull($ppRaw))
                continue;

            // ── QI: IAudioSessionControl → IAudioSessionControl2 ───
            $ppCtrl2 = $this->qi($ppRaw, self::IID_ASC2);
            // IAudioSessionControl is no longer needed after QI to IAudioSessionControl2
            $this->ole32->cast('IUnknownObj*', $ppRaw)->lpVtbl->Release($ppRaw);
            if ($ppCtrl2 === null)
                continue;

            $pCtrl2 = $this->ole32->cast('IAudioSessionControl2*', $ppCtrl2);

            $pidBuf = $this->ole32->new('unsigned long');
            $hr = $pCtrl2->lpVtbl->GetProcessId($pCtrl2, FFI::addr($pidBuf));
            $pid = ($hr < 0) ? 0 : (int) $pidBuf->cdata;

            if ($pid === 0 || $pid === $this->myPid) {
                $pCtrl2->lpVtbl->Release($pCtrl2);
                continue;
            }

            // ── QI: IAudioSessionControl2 → ISimpleAudioVolume ─────
            $ppVol = $this->qi($pCtrl2, self::IID_SAV);
            if ($ppVol === null) {
                $pCtrl2->lpVtbl->Release($pCtrl2);
                continue;
            }

            $pVol = $this->ole32->cast('ISimpleAudioVolume*', $ppVol);
            $pVol->lpVtbl->SetMute($pVol, 1, null);   // TRUE = mute

            $this->mutedSessions[] = ['ctrl2' => $pCtrl2, 'vol' => $pVol, 'pid' => $pid];
        }

        $pSessEnum->lpVtbl->Release($pSessEnum);
        $pMgr->lpVtbl->Release($pMgr);
        $pDevice->lpVtbl->Release($pDevice);
        $pEnum->lpVtbl->Release($pEnum);
    }

    /**
     * Restore mute state for sessions modified by muteAllExceptSelf().
     *
     * @return void
     */
    public function restoreAll(): void
    {
        foreach ($this->mutedSessions as $s) {
            $s['vol']->lpVtbl->SetMute($s['vol'], 0, null);  // FALSE = unmute
            $s['vol']->lpVtbl->Release($s['vol']);
            $s['ctrl2']->lpVtbl->Release($s['ctrl2']);
        }
        $this->mutedSessions = [];
    }

    /**
     * Get process IDs currently tracked as muted.
     *
     * @return array
     */
    public function getMutedPids(): array
    {
        return array_column($this->mutedSessions, 'pid');
    }

    /**
     * Create MMDevice enumerator COM instance.
     *
     * @return FFI\CData
     */
    private function createDeviceEnumerator(): FFI\CData
    {
        $ppv = $this->ole32->new('void*');
        $clsid = $this->guid(...self::CLS_MMDE);
        $iid = $this->guid(...self::IID_MMDE);

        $hr = $this->ole32->CoCreateInstance(FFI::addr($clsid), null, self::CLSCTX_ALL, FFI::addr($iid), FFI::addr($ppv));
        $this->assertHr($hr, 'CoCreateInstance(MMDeviceEnumerator)');

        return $this->ole32->cast('IMMDeviceEnumerator*', $ppv);
    }

    /**
     * Resolve default render endpoint device.
     *
     * @param IAudioSessionEnumeratorVtbl|CData $pEnum
     *
     * @return CData
     */
    private function getDefaultDevice(IAudioSessionEnumeratorVtbl|CData $pEnum): CData
    {
        $ppDev = $this->ole32->new('void*');
        $hr = $pEnum->lpVtbl->GetDefaultAudioEndpoint($pEnum, self::eRender, self::eConsole, FFI::addr($ppDev));
        $this->assertHr($hr, 'GetDefaultAudioEndpoint');

        return $this->ole32->cast('IMMDevice*', $ppDev);
    }

    /**
     * Activate IAudioSessionManager2 for a device endpoint.
     *
     * @param IAudioSessionEnumeratorVtbl|CData $pDevice
     *
     * @return CData
     */
    private function activateSessionManager(IAudioSessionEnumeratorVtbl|CData $pDevice): CData
    {
        $ppMgr = $this->ole32->new('void*');
        $iid = $this->guid(...self::IID_ASM2);
        $hr = $pDevice->lpVtbl->Activate(
            $pDevice,
            FFI::addr($iid),
            self::CLSCTX_ALL,
            null,
            FFI::addr($ppMgr)
        );
        $this->assertHr($hr, 'IMMDevice::Activate(IAudioSessionManager2)');

        return $this->ole32->cast('IAudioSessionManager2*', $ppMgr);
    }

    /**
     * Get IAudioSessionEnumerator from a session manager.
     *
     * @param IAudioSessionManager|CData $pMgr
     *
     * @return CData
     */
    private function getSessionEnumerator(IAudioSessionManager|CData $pMgr): CData
    {
        $ppSE = $this->ole32->new('void*');
        $hr = $pMgr->lpVtbl->GetSessionEnumerator($pMgr, FFI::addr($ppSE));
        $this->assertHr($hr, 'GetSessionEnumerator');

        return $this->ole32->cast('IAudioSessionEnumerator*', $ppSE);
    }

    /**
     * Get the number of active audio sessions.
     *
     * @param IAudioSessionEnumeratorVtbl|CData $pSE
     *
     * @return int
     */
    private function getSessionCount(IAudioSessionEnumeratorVtbl|CData $pSE): int
    {
        $n = $this->ole32->new('int');
        $hr = $pSE->lpVtbl->GetCount($pSE, FFI::addr($n));
        $this->assertHr($hr, 'IAudioSessionEnumerator::GetCount');

        return (int) $n->cdata;
    }

    /**
     * Calls QueryInterface on $pObj (void* or any COM interface pointer) using $iidConst.
     * On success returns a new void* CData; on failure returns null (caller must Release as needed).
     *
     * @param array $iidConst GUID constants in the form [d1, d2, d3, d4[]]
     */
    private function qi(CData $pObj, array $iidConst): ?CData
    {
        $ppOut = $this->kernel32->new('void*');
        $iid = $this->guid(...$iidConst);

        // All COM interfaces derive from IUnknown, so vtable[0] is QueryInterface
        $pUnk = $this->ole32->cast('IUnknownObj*', $pObj);
        $hr = $pUnk->lpVtbl->QueryInterface($pObj, FFI::addr($iid), FFI::addr($ppOut));

        return ($hr < 0 || FFI::isNull($ppOut)) ? null : $ppOut;
    }

    /**
     * Build a GUID structure from integer components.
     *
     * @param int $d1
     * @param int $d2
     * @param int $d3
     * @param array $d4
     *
     * @return CData
     */
    private function guid(int $d1, int $d2, int $d3, array $d4): CData
    {
        $g = $this->ole32->new('GUID');
        $g->Data1 = $d1;
        $g->Data2 = $d2;
        $g->Data3 = $d3;
        for ($i = 0; $i < 8; $i++) {
            $g->Data4[$i] = $d4[$i];
        }
        return $g;
    }

    /**
     * Encode a UTF-8 string as a null-terminated wide (UTF-16LE) argument on kernel32.
     *
     * Follows the proven pattern used by setWindowTextW: toWideChar() gives the
     * UTF-16LE bytes with a trailing null, and they are copied into a wchar_t
     * buffer the called function receives as LPCWSTR.
     *
     * @param string $value
     *
     * @return FFI\CData
     */
    private function encodeWideArgument(string $value): FFI\CData
    {
        $encoded = $this->toWideChar($value);
        $buffer = $this->kernel32->new('wchar_t[' . (strlen($encoded) / 2) . ']');
        FFI::memcpy($buffer, $encoded, strlen($encoded));

        return $buffer;
    }

    /**
     * Read a null-terminated wide buffer back into a UTF-8 string.
     *
     * @param FFI\CData $buffer   A wchar_t[] or unsigned short[] the API filled.
     * @param int       $maxChars Capacity of the buffer in wide characters.
     *
     * @return string
     */
    private function decodeWideBuffer(FFI\CData $buffer, int $maxChars): string
    {
        $bytes = '';

        for ($index = 0; $index < $maxChars; $index++) {
            $unit = (int) $buffer[$index];

            if ($unit === 0) {
                break;
            }

            $bytes .= pack('v', $unit & 0xFFFF);
        }

        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
    }

    /**
     * Real Windows version, read through ntdll's RtlGetVersion.
     *
     * Unlike kernel32's GetVersionEx, RtlGetVersion is not rewritten by the
     * Windows 8.1+ compatibility shim, so an app without a version manifest still
     * gets the true build rather than 6.2.
     *
     * @return array{major: int, minor: int, build: int, platformId: int}|false
     */
    public function getRealWindowsVersion(): array|false
    {
        $info = $this->ntdll->new('RTL_OSVERSIONINFOW');
        $info->dwOSVersionInfoSize = FFI::sizeof($info);

        // RtlGetVersion returns STATUS_SUCCESS (0) and never actually fails.
        if ($this->ntdll->RtlGetVersion(FFI::addr($info)) !== 0) {
            return false;
        }

        return [
            'major' => $info->dwMajorVersion,
            'minor' => $info->dwMinorVersion,
            'build' => $info->dwBuildNumber,
            'platformId' => $info->dwPlatformId,
        ];
    }

    /**
     * System-wide processor times since boot, in 100-nanosecond units.
     *
     * The kernel figure from GetSystemTimes includes idle, so the busy time is
     * (kernel - idle) + user - the quantity a CPU-load percentage is built from
     * by sampling twice and diffing.
     *
     * @return array{idle: int, kernel: int, user: int, busy: int}|false
     */
    public function getSystemProcessorTimes(): array|false
    {
        $idle = $this->kernel32->new('FILETIME');
        $kernel = $this->kernel32->new('FILETIME');
        $user = $this->kernel32->new('FILETIME');

        if (!$this->kernel32->GetSystemTimes(FFI::addr($idle), FFI::addr($kernel), FFI::addr($user))) {
            return false;
        }

        $ticks = static function (FFI\CData $fileTime): int {
            return ($fileTime->dwHighDateTime << 32) | ($fileTime->dwLowDateTime & 0xFFFFFFFF);
        };

        $idleTicks = $ticks($idle);
        $kernelTicks = $ticks($kernel);
        $userTicks = $ticks($user);

        return [
            'idle' => $idleTicks,
            'kernel' => $kernelTicks,
            'user' => $userTicks,
            'busy' => ($kernelTicks - $idleTicks) + $userTicks,
        ];
    }

    /**
     * Whether the machine booted through BIOS or UEFI firmware.
     *
     * @return string 'BIOS', 'UEFI' or 'Unknown'; false on failure.
     */
    public function getFirmwareType(): string|false
    {
        $type = $this->kernel32->new('DWORD');

        if (!$this->kernel32->GetFirmwareType(FFI::addr($type))) {
            return false;
        }

        return match ((int) $type->cdata) {
            1 => 'BIOS',
            2 => 'UEFI',
            default => 'Unknown',
        };
    }

    /**
     * The Windows edition code (PRODUCT_* value) for the given OS version.
     *
     * @param int $majorVersion OS major version to query (defaults to 10).
     * @param int $minorVersion OS minor version to query.
     *
     * @return int|false The product type code, or false on failure.
     */
    public function getProductInfo(int $majorVersion = 10, int $minorVersion = 0): int|false
    {
        $product = $this->kernel32->new('DWORD');

        if (!$this->kernel32->GetProductInfo($majorVersion, $minorVersion, 0, 0, FFI::addr($product))) {
            return false;
        }

        return (int) $product->cdata;
    }

    /**
     * Whether an executable is 32-bit, 64-bit or a legacy binary.
     *
     * @param string $applicationPath Path to the .exe to inspect.
     *
     * @return string One of WIN32, WIN64, DOS, WOW, PIF, POSIX, OS216, Unknown; false on failure.
     */
    public function getBinaryType(string $applicationPath): string|false
    {
        $type = $this->kernel32->new('DWORD');

        if (!$this->kernel32->GetBinaryTypeW($this->encodeWideArgument($applicationPath), FFI::addr($type))) {
            return false;
        }

        // SCS_* codes: 0 WIN32, 6 WIN64, 1 DOS, 2 WOW, 3 PIF, 4 POSIX, 5 OS216.
        return match ((int) $type->cdata) {
            0 => 'WIN32',
            6 => 'WIN64',
            1 => 'DOS',
            2 => 'WOW',
            3 => 'PIF',
            4 => 'POSIX',
            5 => 'OS216',
            default => 'Unknown',
        };
    }

    /**
     * Volume label, serial, filesystem and flags for a drive root.
     *
     * @param string $rootPath Drive root, e.g. "C:\\".
     *
     * @return array{label: string, serialNumber: string, fileSystem: string, maxComponentLength: int, flags: int}|false
     */
    public function getVolumeInformation(string $rootPath): array|false
    {
        $nameBuffer = $this->kernel32->new('wchar_t[261]');
        $fileSystemBuffer = $this->kernel32->new('wchar_t[261]');
        $serial = $this->kernel32->new('DWORD');
        $maxComponentLength = $this->kernel32->new('DWORD');
        $flags = $this->kernel32->new('DWORD');

        $ok = $this->kernel32->GetVolumeInformationW(
            $this->encodeWideArgument($rootPath),
            $nameBuffer,
            261,
            FFI::addr($serial),
            FFI::addr($maxComponentLength),
            FFI::addr($flags),
            $fileSystemBuffer,
            261
        );

        if (!$ok) {
            return false;
        }

        $serialValue = (int) $serial->cdata;

        return [
            'label' => $this->decodeWideBuffer($nameBuffer, 261),
            'serialNumber' => sprintf('%04X-%04X', ($serialValue >> 16) & 0xFFFF, $serialValue & 0xFFFF),
            'fileSystem' => $this->decodeWideBuffer($fileSystemBuffer, 261),
            'maxComponentLength' => (int) $maxComponentLength->cdata,
            'flags' => (int) $flags->cdata,
        ];
    }

    /**
     * Canonical absolute path for a relative or partial path.
     *
     * @param string $path Path to resolve.
     *
     * @return string|false The fully qualified path, or false on failure.
     */
    public function getFullPathName(string $path): string|false
    {
        $buffer = $this->kernel32->new('unsigned short[520]');

        // lpFilePart is optional; null skips returning the file-name offset.
        $written = $this->kernel32->GetFullPathNameW($this->encodeWideArgument($path), 520, $buffer, null);

        if ($written === 0) {
            return false;
        }

        return $this->decodeWideBuffer($buffer, 520);
    }

    /**
     * The size a file actually occupies on disk, in bytes.
     *
     * For a compressed or sparse file this is smaller than the logical size;
     * for an ordinary file it is the size rounded up to the cluster.
     *
     * @param string $path File to measure.
     *
     * @return int|false Bytes on disk, or false on failure.
     */
    public function getCompressedFileSize(string $path): int|false
    {
        $high = $this->kernel32->new('DWORD');
        $low = $this->kernel32->GetCompressedFileSizeW($this->encodeWideArgument($path), FFI::addr($high));

        // INVALID_FILE_SIZE is the documented sentinel.
        if ($low === 0xFFFFFFFF) {
            return false;
        }

        return ((int) $high->cdata << 32) | ($low & 0xFFFFFFFF);
    }

    /**
     * The DPI a specific window is being rendered at.
     *
     * @param CData $hwnd Window handle.
     *
     * @return int Dots per inch; 96 is the unscaled baseline.
     */
    public function getDpiForWindow(CData $hwnd): int
    {
        return (int) $this->user32->GetDpiForWindow($hwnd);
    }

    /**
     * The system-wide DPI of the primary display.
     *
     * @return int Dots per inch; 96 is the unscaled baseline.
     */
    public function getSystemDpi(): int
    {
        return (int) $this->user32->GetDpiForSystem();
    }

    /**
     * The monitor a window sits on, with its full and work-area rectangles.
     *
     * This binding's RECT is declared {x, y, cx, cy} rather than the Win32
     * {left, top, right, bottom}. The layout is four LONGs either way, so it
     * binds correctly; the names are simply not the ones Windows uses, and the
     * values are returned here under the Win32 meanings.
     *
     * @param CData $hwnd Window handle.
     *
     * @return array{monitor: array{left: int, top: int, right: int, bottom: int}, work: array{left: int, top: int, right: int, bottom: int}, primary: bool}|false
     */
    public function getMonitorInfoForWindow(CData $hwnd): array|false
    {
        // MONITOR_DEFAULTTONEAREST: a window fully off-screen still resolves.
        $monitor = $this->user32->MonitorFromWindow($hwnd, 2);

        if (!$monitor) {
            return false;
        }

        $info = $this->user32->new('MONITORINFO');
        $info->cbSize = FFI::sizeof($info);

        if (!$this->user32->GetMonitorInfoW($monitor, FFI::addr($info))) {
            return false;
        }

        $rectangle = static function (CData $rect): array {
            return [
                'left' => $rect->x,
                'top' => $rect->y,
                'right' => $rect->cx,
                'bottom' => $rect->cy,
            ];
        };

        return [
            'monitor' => $rectangle($info->rcMonitor),
            'work' => $rectangle($info->rcWork),
            // MONITORINFOF_PRIMARY
            'primary' => ((int) $info->dwFlags & 1) === 1,
        ];
    }

    /**
     * Every format currently on the clipboard, as id => registered name.
     *
     * Standard formats (CF_TEXT and friends) have no registered name, so their
     * entry is an empty string and the id is what identifies them.
     *
     * @return array<int, string>
     */
    public function getClipboardFormatList(): array
    {
        if (!$this->user32->OpenClipboard(null)) {
            return [];
        }

        $formats = [];
        $format = 0;

        while (($format = (int) $this->user32->EnumClipboardFormats($format)) !== 0) {
            $nameBuffer = $this->user32->new('unsigned short[256]');
            $length = (int) $this->user32->GetClipboardFormatNameW($format, $nameBuffer, 256);

            $formats[$format] = $length > 0 ? $this->decodeWideBuffer($nameBuffer, 256) : '';
        }

        $this->user32->CloseClipboard();

        return $formats;
    }

    /**
     * A counter that increments every time the clipboard contents change.
     *
     * Comparing it against a previously stored value detects a change without
     * opening the clipboard, which would lock out the owning application.
     *
     * @return int
     */
    public function getClipboardSequenceNumber(): int
    {
        return (int) $this->user32->GetClipboardSequenceNumber();
    }

    /**
     * Size and item count of the recycle bin.
     *
     * @param string|null $rootPath Drive to query, e.g. "C:\\"; null totals every drive.
     *
     * @return array{bytes: int, items: int}|false
     */
    public function getRecycleBinInfo(?string $rootPath = null): array|false
    {
        $info = $this->shell32->new('SHQUERYRBINFO');
        $info->cbSize = FFI::sizeof($info);

        $path = $rootPath === null ? null : $this->encodeWideArgument($rootPath);

        // S_OK is 0; anything else means the query did not answer.
        if ((int) $this->shell32->SHQueryRecycleBinW($path, FFI::addr($info)) !== 0) {
            return false;
        }

        return [
            'bytes' => (int) $info->i64Size,
            'items' => (int) $info->i64NumItems,
        ];
    }

    /**
     * Whether the desktop is in a state where a notification would intrude.
     *
     * @return int|false A QUNS_* value: 1 not present, 2 busy, 3 running D3D
     *                   full screen, 4 presentation mode, 5 accepts
     *                   notifications, 6 quiet time, 7 app running full screen.
     */
    public function getUserNotificationState(): int|false
    {
        $state = $this->shell32->new('QUERY_USER_NOTIFICATION_STATE');

        if ((int) $this->shell32->SHQueryUserNotificationState(FFI::addr($state)) !== 0) {
            return false;
        }

        return (int) $state->cdata;
    }

    /**
     * How many kernel handles a process currently holds.
     *
     * A number that climbs and never falls is the signature of a handle leak.
     *
     * @param CData $processHandle Handle opened with PROCESS_QUERY_INFORMATION.
     *
     * @return int|false
     */
    public function getProcessHandleCount(CData $processHandle): int|false
    {
        $count = $this->kernel32->new('DWORD');

        if (!$this->kernel32->GetProcessHandleCount($processHandle, FFI::addr($count))) {
            return false;
        }

        return (int) $count->cdata;
    }

    /**
     * Create a job object, the kernel container processes can be assigned to.
     *
     * Killing the job kills every process in it, which is the reliable way to
     * take down a process tree that spawns children of its own.
     *
     * @param string|null $name Optional name, so the job can be reopened by it.
     *
     * @return CData|null The job handle, or null when creation failed.
     */
    public function createJobObject(?string $name = null): ?CData
    {
        $handle = $this->kernel32->CreateJobObjectW(
            null,
            $name === null ? null : $this->encodeWideArgument($name)
        );

        return $handle ?: null;
    }

    /**
     * Put a process under a job object's control.
     *
     * @param CData $jobHandle     Job created by createJobObject().
     * @param CData $processHandle Process opened with PROCESS_SET_QUOTA | PROCESS_TERMINATE.
     *
     * @return bool
     */
    public function assignProcessToJobObject(CData $jobHandle, CData $processHandle): bool
    {
        return (bool) $this->kernel32->AssignProcessToJobObject($jobHandle, $processHandle);
    }

    /**
     * Terminate every process in a job object.
     *
     * @param CData $jobHandle Job created by createJobObject().
     * @param int   $exitCode  Exit code reported by each terminated process.
     *
     * @return bool
     */
    public function terminateJobObject(CData $jobHandle, int $exitCode = 0): bool
    {
        return (bool) $this->kernel32->TerminateJobObject($jobHandle, $exitCode);
    }

    /**
     * The multimedia timer's millisecond clock since Windows started.
     *
     * Its resolution follows whatever beginTimePeriod() has been asked for,
     * which is what makes it steadier than GetTickCount for short intervals.
     * It wraps roughly every 49.7 days.
     *
     * @return int Milliseconds.
     */
    public function getMultimediaTime(): int
    {
        return (int) $this->winmm->timeGetTime();
    }

    /**
     * Ask for a finer system timer resolution.
     *
     * This is a process-wide, system-affecting request: every call must be
     * matched by endTimePeriod() with the same value, or the raised resolution
     * outlives the process that wanted it and costs the machine battery.
     *
     * @param int $milliseconds Resolution wanted, typically 1.
     *
     * @return bool True when the request was accepted (TIMERR_NOERROR).
     */
    public function beginTimePeriod(int $milliseconds = 1): bool
    {
        return (int) $this->winmm->timeBeginPeriod($milliseconds) === 0;
    }

    /**
     * Release a resolution previously requested by beginTimePeriod().
     *
     * @param int $milliseconds The same value that was passed to beginTimePeriod().
     *
     * @return bool True when the release was accepted (TIMERR_NOERROR).
     */
    public function endTimePeriod(int $milliseconds = 1): bool
    {
        return (int) $this->winmm->timeEndPeriod($milliseconds) === 0;
    }

    /**
     * Send an MCI command string, the scripting surface for audio and video.
     *
     * Example: sendMciCommand('open "song.mp3" type mpegvideo alias track')
     * then sendMciCommand('play track').
     *
     * @param string $command MCI command string.
     *
     * @return string|false The command's reply, or false when MCI rejected it.
     */
    public function sendMciCommand(string $command): string|false
    {
        $returnBuffer = $this->winmm->new('wchar_t[512]');

        $result = (int) $this->winmm->mciSendStringW(
            $this->encodeWideArgument($command),
            $returnBuffer,
            512,
            null
        );

        // MMSYSERR_NOERROR
        if ($result !== 0) {
            return false;
        }

        return $this->decodeWideBuffer($returnBuffer, 512);
    }

    /**
     * Every service the SCM knows about, with its state and hosting process.
     *
     * The SCM answers a first, undersized call with the byte count it wants,
     * which is why the buffer is sized in two passes rather than guessed.
     *
     * @param CData $hScm         SCM handle from openSCManager(); needs SC_MANAGER_ENUMERATE_SERVICE (4).
     * @param int   $serviceType  SERVICE_WIN32 = 0x30; 0x3B adds drivers.
     * @param int   $serviceState SERVICE_STATE_ALL = 3, ACTIVE = 1, INACTIVE = 2.
     *
     * @return array<int, array{name: string, displayName: string, serviceType: int, currentState: int, processId: int}>|false
     */
    public function enumerateServices(CData $hScm, int $serviceType = 0x30, int $serviceState = 3): array|false
    {
        $needed = $this->advapi32->new('DWORD');
        $returned = $this->advapi32->new('DWORD');
        $resume = $this->advapi32->new('DWORD');

        // SC_ENUM_PROCESS_INFO = 0. The first call is expected to fail; it is
        // asked only for the size it wants.
        $this->advapi32->EnumServicesStatusExW(
            $hScm,
            0,
            $serviceType,
            $serviceState,
            null,
            0,
            FFI::addr($needed),
            FFI::addr($returned),
            FFI::addr($resume),
            null
        );

        $size = (int) $needed->cdata;

        if ($size <= 0) {
            return false;
        }

        $buffer = $this->advapi32->new('char[' . $size . ']');
        $resume->cdata = 0;

        $ok = $this->advapi32->EnumServicesStatusExW(
            $hScm,
            0,
            $serviceType,
            $serviceState,
            $buffer,
            $size,
            FFI::addr($needed),
            FFI::addr($returned),
            FFI::addr($resume),
            null
        );

        if (!$ok) {
            return false;
        }

        // Casting the array itself does not decay it. FFI reinterprets the
        // array's own bytes as the pointer value, so the first field read
        // dereferences whatever the buffer happens to begin with - here, the
        // real name pointer, which is why it looked plausible and then crashed.
        // Taking the address of element zero is what forms a pointer to it.
        $entries = $this->advapi32->cast('ENUM_SERVICE_STATUS_PROCESSW*', FFI::addr($buffer[0]));
        $services = [];

        for ($index = 0; $index < (int) $returned->cdata; $index++) {
            $entry = $entries[$index];

            $services[] = [
                'name' => $this->readWideString($entry->lpServiceName),
                'displayName' => $this->readWideString($entry->lpDisplayName),
                'serviceType' => (int) $entry->ServiceStatusProcess->dwServiceType,
                'currentState' => (int) $entry->ServiceStatusProcess->dwCurrentState,
                'processId' => (int) $entry->ServiceStatusProcess->dwProcessId,
            ];
        }

        return $services;
    }

    /**
     * A service's static configuration: what runs it, how it starts, as whom.
     *
     * dwStartType: 0 boot, 1 system, 2 automatic, 3 manual, 4 disabled.
     *
     * @param CData $hService Handle opened with SERVICE_QUERY_CONFIG (1).
     *
     * @return array{serviceType: int, startType: int, errorControl: int, binaryPath: string, loadOrderGroup: string, dependencies: string, startName: string, displayName: string}|false
     */
    public function getServiceConfig(CData $hService): array|false
    {
        $needed = $this->advapi32->new('DWORD');

        $this->advapi32->QueryServiceConfigW($hService, null, 0, FFI::addr($needed));

        $size = (int) $needed->cdata;

        if ($size <= 0) {
            return false;
        }

        $buffer = $this->advapi32->new('char[' . $size . ']');

        if (!$this->advapi32->QueryServiceConfigW($hService, $buffer, $size, FFI::addr($needed))) {
            return false;
        }

        $config = $this->advapi32->cast('QUERY_SERVICE_CONFIGW*', FFI::addr($buffer[0]));

        return [
            'serviceType' => (int) $config->dwServiceType,
            'startType' => (int) $config->dwStartType,
            'errorControl' => (int) $config->dwErrorControl,
            'binaryPath' => $this->readWideString($config->lpBinaryPathName),
            'loadOrderGroup' => $this->readWideString($config->lpLoadOrderGroup),
            'dependencies' => $this->readWideString($config->lpDependencies),
            'startName' => $this->readWideString($config->lpServiceStartName),
            'displayName' => $this->readWideString($config->lpDisplayName),
        ];
    }

    /**
     * The human-readable description shown in services.msc.
     *
     * @param CData $hService Handle opened with SERVICE_QUERY_CONFIG (1).
     *
     * @return string|false
     */
    public function getServiceDescription(CData $hService): string|false
    {
        $needed = $this->advapi32->new('DWORD');

        // SERVICE_CONFIG_DESCRIPTION = 1
        $this->advapi32->QueryServiceConfig2W($hService, 1, null, 0, FFI::addr($needed));

        $size = (int) $needed->cdata;

        if ($size <= 0) {
            return false;
        }

        $buffer = $this->advapi32->new('char[' . $size . ']');

        if (!$this->advapi32->QueryServiceConfig2W($hService, 1, $buffer, $size, FFI::addr($needed))) {
            return false;
        }

        return $this->readWideString($this->advapi32->cast('SERVICE_DESCRIPTIONW*', FFI::addr($buffer[0]))->lpDescription);
    }

    /**
     * Service status including the process it runs in.
     *
     * queryServiceStatus() answers the same states; this adds processId, which
     * is what connects a service to anything else that inspects processes. A
     * stopped service reports 0.
     *
     * @param CData $hService Handle opened with SERVICE_QUERY_STATUS (4).
     *
     * @return array{serviceType: int, currentState: int, controlsAccepted: int, win32ExitCode: int, checkPoint: int, waitHint: int, processId: int, serviceFlags: int}|false
     */
    public function queryServiceStatusEx(CData $hService): array|false
    {
        $status = $this->advapi32->new('SERVICE_STATUS_PROCESS');
        $needed = $this->advapi32->new('DWORD');

        // SC_STATUS_PROCESS_INFO = 0
        $ok = $this->advapi32->QueryServiceStatusEx(
            $hService,
            0,
            FFI::addr($status),
            FFI::sizeof($status),
            FFI::addr($needed)
        );

        if (!$ok) {
            return false;
        }

        return [
            'serviceType' => (int) $status->dwServiceType,
            'currentState' => (int) $status->dwCurrentState,
            'controlsAccepted' => (int) $status->dwControlsAccepted,
            'win32ExitCode' => (int) $status->dwWin32ExitCode,
            'checkPoint' => (int) $status->dwCheckPoint,
            'waitHint' => (int) $status->dwWaitHint,
            'processId' => (int) $status->dwProcessId,
            'serviceFlags' => (int) $status->dwServiceFlags,
        ];
    }

    /**
     * Send an arbitrary control code to a service.
     *
     * stopService() sends 1; this is the rest of the set: 2 pause, 3 continue,
     * 4 interrogate, 5 shutdown. A service only accepts what its
     * controlsAccepted mask advertises.
     *
     * @param CData $hService    Handle opened with the matching access right.
     * @param int   $controlCode SERVICE_CONTROL_* value.
     *
     * @return array{currentState: int, waitHint: int}|false
     */
    public function controlService(CData $hService, int $controlCode): array|false
    {
        $status = $this->advapi32->new('SERVICE_STATUS');

        if (!$this->advapi32->ControlService($hService, $controlCode, FFI::addr($status))) {
            return false;
        }

        return [
            'currentState' => (int) $status->dwCurrentState,
            'waitHint' => (int) $status->dwWaitHint,
        ];
    }

    /**
     * Change how a service starts, leaving everything else as it is.
     *
     * @param CData $hService  Handle opened with SERVICE_CHANGE_CONFIG (2).
     * @param int   $startType 2 automatic, 3 manual, 4 disabled.
     *
     * @return bool
     */
    public function setServiceStartType(CData $hService, int $startType): bool
    {
        // SERVICE_NO_CHANGE = 0xFFFFFFFF for every field being left alone.
        $noChange = 0xFFFFFFFF;

        return (bool) $this->advapi32->ChangeServiceConfigW(
            $hService,
            $noChange,
            $startType,
            $noChange,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );
    }

    /**
     * Mark a service for deletion.
     *
     * The service is removed once every handle to it is closed and it is not
     * running, so this returning true does not mean it is gone yet.
     *
     * @param CData $hService Handle opened with DELETE (0x10000).
     *
     * @return bool
     */
    public function deleteService(CData $hService): bool
    {
        return (bool) $this->advapi32->DeleteService($hService);
    }

    /**
     * Wait for a service to reach a state, polling at the interval it asks for.
     *
     * The SCM's waitHint is what the service says it needs; honouring it is
     * what keeps this from either spinning or waiting far too long.
     *
     * @param CData $hService      Handle opened with SERVICE_QUERY_STATUS (4).
     * @param int   $desiredState  1 stopped, 4 running.
     * @param int   $timeoutMillis Give up after this long.
     *
     * @return bool True when the state was reached before the timeout.
     */
    public function waitForServiceState(CData $hService, int $desiredState, int $timeoutMillis = 30000): bool
    {
        $deadline = (int) (microtime(true) * 1000) + $timeoutMillis;

        while (true) {
            $status = $this->queryServiceStatusEx($hService);

            if ($status === false) {
                return false;
            }

            if ($status['currentState'] === $desiredState) {
                return true;
            }

            $now = (int) (microtime(true) * 1000);

            if ($now >= $deadline) {
                return false;
            }

            // waitHint is a total, not a poll interval; a tenth of it is the
            // interval the SCM documentation suggests, clamped to something
            // that neither spins nor overshoots the deadline.
            $wait = max(200, min(2000, intdiv(max(0, $status['waitHint']), 10)));
            usleep(min($wait, $deadline - $now) * 1000);
        }
    }

    /**
     * Open one of the classic event logs: Application, System, Security.
     *
     * @param string $sourceName Log name, e.g. "Application".
     *
     * @return CData|null Log handle, or null when it could not be opened.
     */
    public function openEventLog(string $sourceName = 'Application'): ?CData
    {
        $handle = $this->advapi32->OpenEventLogW(null, $this->encodeWideArgument($sourceName));

        return $handle ?: null;
    }

    /**
     * Close a handle from openEventLog().
     *
     * @param CData $hEventLog Log handle.
     *
     * @return bool
     */
    public function closeEventLog(CData $hEventLog): bool
    {
        return (bool) $this->advapi32->CloseEventLog($hEventLog);
    }

    /**
     * How many records the log currently holds.
     *
     * @param CData $hEventLog Log handle.
     *
     * @return int|false
     */
    public function getEventLogRecordCount(CData $hEventLog): int|false
    {
        $count = $this->advapi32->new('DWORD');

        if (!$this->advapi32->GetNumberOfEventLogRecords($hEventLog, FFI::addr($count))) {
            return false;
        }

        return (int) $count->cdata;
    }

    /**
     * The record number of the oldest entry still in the log.
     *
     * Record numbers are not reset when the log wraps, so this is the floor a
     * seek can start from.
     *
     * @param CData $hEventLog Log handle.
     *
     * @return int|false
     */
    public function getOldestEventLogRecord(CData $hEventLog): int|false
    {
        $oldest = $this->advapi32->new('DWORD');

        if (!$this->advapi32->GetOldestEventLogRecord($hEventLog, FFI::addr($oldest))) {
            return false;
        }

        return (int) $oldest->cdata;
    }

    /**
     * Read entries from an event log, newest first.
     *
     * EVENTLOGRECORD is a header followed by a variable-length tail, so records
     * are walked by their own Length field rather than by a fixed stride.
     *
     * eventType: 1 error, 2 warning, 4 information, 8 audit success,
     * 16 audit failure.
     *
     * @param CData $hEventLog Log handle from openEventLog().
     * @param int   $maxRecords Stop after this many.
     *
     * @return array<int, array{recordNumber: int, eventId: int, eventType: int, eventCategory: int, timeGenerated: int, timeWritten: int, source: string, strings: array<int, string>}>|false
     */
    public function readEventLogEntries(CData $hEventLog, int $maxRecords = 50): array|false
    {
        // EVENTLOG_SEQUENTIAL_READ | EVENTLOG_BACKWARDS_READ
        $flags = 0x0001 | 0x0008;

        $bufferSize = 0x10000;
        $buffer = $this->advapi32->new('char[' . $bufferSize . ']');
        $read = $this->advapi32->new('DWORD');
        $minimumNeeded = $this->advapi32->new('DWORD');

        $records = [];

        while (count($records) < $maxRecords) {
            $ok = $this->advapi32->ReadEventLogW(
                $hEventLog,
                $flags,
                0,
                $buffer,
                $bufferSize,
                FFI::addr($read),
                FFI::addr($minimumNeeded)
            );

            if (!$ok) {
                // Anything other than "nothing left" is a real failure, but the
                // records already gathered are still good, so they are returned.
                break;
            }

            $available = (int) $read->cdata;
            $offset = 0;

            while ($offset < $available && count($records) < $maxRecords) {
                // Interior pointers are formed by taking the address of an
                // element. Adding to a cast pointer offsets correctly too, but
                // its static type comes out as a number rather than a pointer.
                $record = $this->advapi32->cast('EVENTLOGRECORD*', FFI::addr($buffer[$offset]));
                $length = (int) $record->Length;

                if ($length <= 0 || $offset + $length > $available) {
                    break;
                }

                $headerSize = FFI::sizeof($record[0]);
                $stringOffset = (int) $record->StringOffset;

                $records[] = [
                    'recordNumber' => (int) $record->RecordNumber,
                    'eventId' => (int) $record->EventID & 0xFFFF,
                    'eventType' => (int) $record->EventType,
                    'eventCategory' => (int) $record->EventCategory,
                    'timeGenerated' => (int) $record->TimeGenerated,
                    'timeWritten' => (int) $record->TimeWritten,
                    // The source name is a wide string sitting immediately
                    // after the fixed header.
                    // Both readers stop at a terminator, and the record is
                    // supposed to carry one. The ceilings are what the buffer
                    // actually has left, so a truncated record cannot walk off
                    // the end of the allocation looking for it.
                    'source' => $offset + $headerSize < $available
                        ? $this->readWideString(
                            FFI::addr($buffer[$offset + $headerSize]),
                            intdiv($available - $offset - $headerSize, 2)
                        )
                        : '',
                    'strings' => $stringOffset > 0 && $offset + $stringOffset < $available
                        ? $this->readWideStringSequence(
                            FFI::addr($buffer[$offset + $stringOffset]),
                            (int) $record->NumStrings,
                            intdiv($available - $offset - $stringOffset, 2)
                        )
                        : [],
                ];

                $offset += $length;
            }

            if ($offset === 0) {
                break;
            }
        }

        return $records;
    }

    /**
     * Write a copy of an event log to a file.
     *
     * @param CData  $hEventLog  Log handle.
     * @param string $backupPath Destination; it must not already exist.
     *
     * @return bool
     */
    public function backupEventLog(CData $hEventLog, string $backupPath): bool
    {
        return (bool) $this->advapi32->BackupEventLogW($hEventLog, $this->encodeWideArgument($backupPath));
    }

    /**
     * Empty an event log, optionally saving it first.
     *
     * Without a backup path the records are gone; the log is the only copy.
     *
     * @param CData       $hEventLog  Log handle opened with clear rights.
     * @param string|null $backupPath Where to save the records first, or null to discard them.
     *
     * @return bool
     */
    public function clearEventLog(CData $hEventLog, ?string $backupPath = null): bool
    {
        return (bool) $this->advapi32->ClearEventLogW(
            $hEventLog,
            $backupPath === null ? null : $this->encodeWideArgument($backupPath)
        );
    }

    /**
     * Read a null-terminated wide string from a pointer the OS filled in.
     *
     * decodeWideBuffer() reads a buffer this class allocated and whose length
     * it therefore knows. This reads one the OS placed somewhere inside a
     * larger structure, where the terminator is all there is to go on, so it
     * carries its own ceiling rather than running off the end of the mapping.
     *
     * @param CData|null $pointer  Pointer to UTF-16LE characters, or null.
     * @param int        $maxChars Refuse to read past this many characters.
     *
     * @return string Empty when the pointer is null.
     */
    private function readWideString(?CData $pointer, int $maxChars = 4096): string
    {
        if ($pointer === null) {
            return '';
        }

        $characters = $this->advapi32->cast('unsigned short*', $pointer);
        $bytes = '';

        for ($index = 0; $index < $maxChars; $index++) {
            $unit = (int) $characters[$index];

            if ($unit === 0) {
                break;
            }

            $bytes .= pack('v', $unit & 0xFFFF);
        }

        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
    }

    /**
     * Read a run of consecutive null-terminated wide strings.
     *
     * Event log insertion strings are packed one after another, each ending in
     * its own terminator, with only NumStrings to say how many there are.
     *
     * @param CData|null $pointer  Start of the first string.
     * @param int        $count    How many to read.
     * @param int        $maxChars Refuse to read past this many characters.
     *
     * @return array<int, string>
     */
    private function readWideStringSequence(?CData $pointer, int $count, int $maxChars = 8192): array
    {
        if ($pointer === null || $count <= 0 || $maxChars <= 0) {
            return [];
        }

        $characters = $this->advapi32->cast('unsigned short*', $pointer);
        $strings = [];
        $bytes = '';
        $index = 0;

        while (count($strings) < $count && $index < $maxChars) {
            $unit = (int) $characters[$index];
            $index++;

            if ($unit === 0) {
                $strings[] = mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
                $bytes = '';
                continue;
            }

            $bytes .= pack('v', $unit & 0xFFFF);
        }

        return $strings;
    }

    /**
     * Open a serial port for reading and writing.
     *
     * COM10 and above are not valid DOS device names, so the \\.\ prefix is
     * applied to every port rather than only the ones that need it - COM1
     * accepts it too, and a caller should not have to know where the boundary
     * is.
     *
     * A serial port cannot be shared: it is opened with no sharing mode and a
     * second attempt fails until the first handle is closed.
     *
     * @param string $port Port name, e.g. "COM3".
     *
     * @return CData|null Handle, or null when the port could not be opened.
     */
    public function openSerialPort(string $port): ?CData
    {
        $name = str_starts_with($port, '\\\\') ? $port : '\\\\.\\' . $port;

        // GENERIC_READ | GENERIC_WRITE, no sharing, OPEN_EXISTING.
        $handle = $this->kernel32->CreateFileW(
            $this->encodeWideArgument($name),
            0x80000000 | 0x40000000,
            0,
            null,
            3,
            0,
            null
        );

        // CreateFileW signals failure with INVALID_HANDLE_VALUE, which is -1
        // cast to a pointer - not null.
        if (!$handle || $this->handleValue($handle) === -1) {
            return null;
        }

        return $handle;
    }

    /**
     * The current line settings of an open serial port.
     *
     * parity: 0 none, 1 odd, 2 even, 3 mark, 4 space.
     * stopBits: 0 one, 1 one and a half, 2 two.
     *
     * @param CData $handle Handle from openSerialPort().
     *
     * @return array{baudRate: int, byteSize: int, parity: int, stopBits: int, rtsControl: int, dtrControl: int}|false
     */
    public function getSerialPortConfig(CData $handle): array|false
    {
        $dcb = $this->kernel32->new('DCB');
        $dcb->DCBlength = FFI::sizeof($dcb);

        if (!$this->kernel32->GetCommState($handle, FFI::addr($dcb))) {
            return false;
        }

        return [
            'baudRate' => (int) $dcb->BaudRate,
            'byteSize' => (int) $dcb->ByteSize,
            'parity' => (int) $dcb->Parity,
            'stopBits' => (int) $dcb->StopBits,
            'rtsControl' => (int) $dcb->fRtsControl,
            'dtrControl' => (int) $dcb->fDtrControl,
        ];
    }

    /**
     * Set the line settings of an open serial port.
     *
     * The block is read back first and then edited, because a DCB written from
     * scratch leaves every flow-control flag at zero, which is not the same as
     * leaving them alone.
     *
     * @param CData $handle   Handle from openSerialPort().
     * @param int   $baudRate 9600, 115200 and so on.
     * @param int   $byteSize Data bits, usually 8.
     * @param int   $parity   0 none, 1 odd, 2 even, 3 mark, 4 space.
     * @param int   $stopBits 0 one, 1 one and a half, 2 two.
     *
     * @return bool
     */
    public function configureSerialPort(
        CData $handle,
        int $baudRate = 9600,
        int $byteSize = 8,
        int $parity = 0,
        int $stopBits = 0
    ): bool {
        $dcb = $this->kernel32->new('DCB');
        $dcb->DCBlength = FFI::sizeof($dcb);

        if (!$this->kernel32->GetCommState($handle, FFI::addr($dcb))) {
            return false;
        }

        $dcb->BaudRate = $baudRate;
        $dcb->ByteSize = $byteSize;
        $dcb->Parity = $parity;
        $dcb->StopBits = $stopBits;
        // fBinary is required to be 1 on Windows; there is no text mode.
        $dcb->fBinary = 1;
        $dcb->fParity = $parity === 0 ? 0 : 1;

        return (bool) $this->kernel32->SetCommState($handle, FFI::addr($dcb));
    }

    /**
     * How long reads and writes on a serial port are allowed to block.
     *
     * All zeroes means a read blocks until at least one byte arrives, which
     * hangs a single-threaded caller on a silent line. A read interval alone is
     * the usual "return whatever is there" setting.
     *
     * @param CData $handle                   Handle from openSerialPort().
     * @param int   $readIntervalMillis       Gap between bytes that ends a read.
     * @param int   $readTotalMultiplier      Per byte requested.
     * @param int   $readTotalConstant        Flat addition to every read.
     * @param int   $writeTotalMultiplier     Per byte written.
     * @param int   $writeTotalConstant       Flat addition to every write.
     *
     * @return bool
     */
    public function setSerialTimeouts(
        CData $handle,
        int $readIntervalMillis = 50,
        int $readTotalMultiplier = 10,
        int $readTotalConstant = 100,
        int $writeTotalMultiplier = 10,
        int $writeTotalConstant = 100
    ): bool {
        $timeouts = $this->kernel32->new('COMMTIMEOUTS');
        $timeouts->ReadIntervalTimeout = $readIntervalMillis;
        $timeouts->ReadTotalTimeoutMultiplier = $readTotalMultiplier;
        $timeouts->ReadTotalTimeoutConstant = $readTotalConstant;
        $timeouts->WriteTotalTimeoutMultiplier = $writeTotalMultiplier;
        $timeouts->WriteTotalTimeoutConstant = $writeTotalConstant;

        return (bool) $this->kernel32->SetCommTimeouts($handle, FFI::addr($timeouts));
    }

    /**
     * The timeouts currently in force on a serial port.
     *
     * @param CData $handle Handle from openSerialPort().
     *
     * @return array{readInterval: int, readTotalMultiplier: int, readTotalConstant: int, writeTotalMultiplier: int, writeTotalConstant: int}|false
     */
    public function getSerialTimeouts(CData $handle): array|false
    {
        $timeouts = $this->kernel32->new('COMMTIMEOUTS');

        if (!$this->kernel32->GetCommTimeouts($handle, FFI::addr($timeouts))) {
            return false;
        }

        return [
            'readInterval' => (int) $timeouts->ReadIntervalTimeout,
            'readTotalMultiplier' => (int) $timeouts->ReadTotalTimeoutMultiplier,
            'readTotalConstant' => (int) $timeouts->ReadTotalTimeoutConstant,
            'writeTotalMultiplier' => (int) $timeouts->WriteTotalTimeoutMultiplier,
            'writeTotalConstant' => (int) $timeouts->WriteTotalTimeoutConstant,
        ];
    }

    /**
     * Set the size of the driver's send and receive queues.
     *
     * @param CData $handle       Handle from openSerialPort().
     * @param int   $inputQueue   Receive queue in bytes.
     * @param int   $outputQueue  Send queue in bytes.
     *
     * @return bool
     */
    public function setSerialQueueSizes(CData $handle, int $inputQueue = 4096, int $outputQueue = 4096): bool
    {
        return (bool) $this->kernel32->SetupComm($handle, $inputQueue, $outputQueue);
    }

    /**
     * Write bytes to an open serial port.
     *
     * @param CData  $handle Handle from openSerialPort().
     * @param string $data   Bytes to send.
     *
     * @return int|false Bytes actually written.
     */
    public function writeSerialPort(CData $handle, string $data): int|false
    {
        $length = strlen($data);

        if ($length === 0) {
            return 0;
        }

        $buffer = $this->kernel32->new('char[' . $length . ']');
        FFI::memcpy($buffer, $data, $length);

        $written = $this->kernel32->new('DWORD');

        if (!$this->kernel32->WriteFile($handle, $buffer, $length, FFI::addr($written), null)) {
            return false;
        }

        return (int) $written->cdata;
    }

    /**
     * Read whatever the serial port has, up to a ceiling.
     *
     * How long this blocks is set by setSerialTimeouts(), not by this call.
     *
     * @param CData $handle   Handle from openSerialPort().
     * @param int   $maxBytes Ceiling on one read.
     *
     * @return string|false The bytes read, empty when the line was silent.
     */
    public function readSerialPort(CData $handle, int $maxBytes = 4096): string|false
    {
        if ($maxBytes <= 0) {
            return '';
        }

        $buffer = $this->kernel32->new('char[' . $maxBytes . ']');
        $read = $this->kernel32->new('DWORD');

        if (!$this->kernel32->ReadFile($handle, $buffer, $maxBytes, FFI::addr($read), null)) {
            return false;
        }

        $length = (int) $read->cdata;

        return $length > 0 ? FFI::string($buffer, $length) : '';
    }

    /**
     * Discard what is queued on a serial port, abort what is in flight, or both.
     *
     * @param CData $handle Handle from openSerialPort().
     * @param int   $flags  PURGE_TXABORT 1, PURGE_RXABORT 2, PURGE_TXCLEAR 4,
     *                      PURGE_RXCLEAR 8; the default clears both queues.
     *
     * @return bool
     */
    public function purgeSerialPort(CData $handle, int $flags = 0x0C): bool
    {
        return (bool) $this->kernel32->PurgeComm($handle, $flags);
    }

    /**
     * How many bytes are sitting in the serial driver's queues, and what went
     * wrong since this was last asked.
     *
     * ClearCommError is the only way to read the queue depths, and it clears
     * the error state as a side effect, which is where its name comes from.
     *
     * @param CData $handle Handle from openSerialPort().
     *
     * @return array{errors: int, inQueue: int, outQueue: int}|false
     */
    public function getSerialQueueStatus(CData $handle): array|false
    {
        $errors = $this->kernel32->new('DWORD');
        $status = $this->kernel32->new('COMSTAT');

        if (!$this->kernel32->ClearCommError($handle, FFI::addr($errors), FFI::addr($status))) {
            return false;
        }

        return [
            'errors' => (int) $errors->cdata,
            'inQueue' => (int) $status->cbInQue,
            'outQueue' => (int) $status->cbOutQue,
        ];
    }

    /**
     * The state of the serial port's incoming control lines.
     *
     * @param CData $handle Handle from openSerialPort().
     *
     * @return array{cts: bool, dsr: bool, ring: bool, rlsd: bool}|false
     */
    public function getSerialModemStatus(CData $handle): array|false
    {
        $state = $this->kernel32->new('DWORD');

        if (!$this->kernel32->GetCommModemStatus($handle, FFI::addr($state))) {
            return false;
        }

        $bits = (int) $state->cdata;

        return [
            'cts' => ($bits & 0x0010) !== 0,
            'dsr' => ($bits & 0x0020) !== 0,
            'ring' => ($bits & 0x0040) !== 0,
            'rlsd' => ($bits & 0x0080) !== 0,
        ];
    }

    /**
     * Drive one of the serial port's outgoing control lines.
     *
     * @param CData $handle   Handle from openSerialPort().
     * @param int   $function SETXOFF 1, SETXON 2, SETRTS 3, CLRRTS 4, SETDTR 5,
     *                        CLRDTR 6, SETBREAK 8, CLRBREAK 9.
     *
     * @return bool
     */
    public function escapeSerialFunction(CData $handle, int $function): bool
    {
        return (bool) $this->kernel32->EscapeCommFunction($handle, $function);
    }

    /**
     * The machine's time zone, its offsets, and when it changes over.
     *
     * Bias is minutes to add to local time to get UTC, so a zone ahead of UTC
     * has a negative bias. The effective offset is the bias plus whichever of
     * standardBias or daylightBias applies, which is what "current" reports.
     *
     * @return array{
     *  bias: int,
     *  standardName: string,
     *  standardBias: int,
     *  daylightName: string,
     *  daylightBias: int,
     *  current: string,
     *  currentBias: int
     * }|false
     */
    public function getTimeZoneInformation(): array|false
    {
        $zone = $this->kernel32->new('TIME_ZONE_INFORMATION');

        // TIME_ZONE_ID_INVALID is 0xFFFFFFFF; 0 unknown, 1 standard, 2 daylight.
        $result = (int) $this->kernel32->GetTimeZoneInformation(FFI::addr($zone));

        if ($result === -1 || $result === 0xFFFFFFFF) {
            return false;
        }

        $bias = $this->toSignedLong((int) $zone->Bias);
        $standardBias = $this->toSignedLong((int) $zone->StandardBias);
        $daylightBias = $this->toSignedLong((int) $zone->DaylightBias);

        $current = match ($result) {
            1 => 'standard',
            2 => 'daylight',
            default => 'unknown',
        };

        return [
            'bias' => $bias,
            'standardName' => parent::wideToPhp($zone->StandardName, 32),
            'standardBias' => $standardBias,
            'daylightName' => parent::wideToPhp($zone->DaylightName, 32),
            'daylightBias' => $daylightBias,
            'current' => $current,
            'currentBias' => $bias + ($result === 2 ? $daylightBias : $standardBias),
        ];
    }

    /**
     * The locale the current user is configured for, as a BCP 47 name.
     *
     * @return string|false e.g. "ko-KR".
     */
    public function getUserLocaleName(): string|false
    {
        // LOCALE_NAME_MAX_LENGTH
        $buffer = $this->kernel32->new('WCHAR[85]');
        $length = (int) $this->kernel32->GetUserDefaultLocaleName($buffer, 85);

        if ($length <= 0) {
            return false;
        }

        // The count includes the terminator.
        return parent::wideToPhp($buffer, $length - 1);
    }

    /**
     * The machine's locale, which is not necessarily the user's.
     *
     * @return string|false e.g. "en-US".
     */
    public function getSystemLocaleName(): string|false
    {
        $buffer = $this->kernel32->new('WCHAR[85]');
        $length = (int) $this->kernel32->GetSystemDefaultLocaleName($buffer, 85);

        if ($length <= 0) {
            return false;
        }

        return parent::wideToPhp($buffer, $length - 1);
    }

    /**
     * One field of a locale's configuration.
     *
     * @param int         $type   An LOCALE_S* constant: 0x5C currency symbol,
     *                            0x1D decimal separator, 0x1F list separator,
     *                            0x1F5 short date pattern, 0x1003 ISO country.
     * @param string|null $locale BCP 47 name, or null for the user's own.
     *
     * @return string|false
     */
    public function getLocaleInfo(int $type, ?string $locale = null): string|false
    {
        $name = $locale === null ? null : $this->encodeWideArgument($locale);
        $buffer = $this->kernel32->new('WCHAR[256]');

        $length = (int) $this->kernel32->GetLocaleInfoEx($name, $type, $buffer, 256);

        if ($length <= 0) {
            return false;
        }

        return parent::wideToPhp($buffer, $length - 1);
    }

    /**
     * The country or region the user has selected, as a GEOID.
     *
     * This is a separate setting from the locale: a user can be in Japan with
     * their display language set to English.
     *
     * @return int|false
     */
    public function getUserGeoId(): int|false
    {
        // GEOCLASS_NATION
        $geoId = $this->toSignedLong((int) $this->kernel32->GetUserGeoID(16));

        // GEOID_NOT_AVAILABLE
        return $geoId === -1 ? false : $geoId;
    }

    /**
     * One field of a country or region, by GEOID.
     *
     * @param int $geoId   Value from getUserGeoId().
     * @param int $geoType GEO_ISO2 4, GEO_ISO3 5, GEO_FRIENDLYNAME 8,
     *                     GEO_OFFICIALNAME 9.
     *
     * @return string|false
     */
    public function getGeoInfo(int $geoId, int $geoType = 4): string|false
    {
        $buffer = $this->kernel32->new('WCHAR[256]');

        $length = (int) $this->kernel32->GetGeoInfoW($geoId, $geoType, $buffer, 256, 0);

        if ($length <= 0) {
            return false;
        }

        return parent::wideToPhp($buffer, $length - 1);
    }

    /**
     * The numeric value a HANDLE carries.
     *
     * Casting a pointer to an integer type does not do this: FFI reads the
     * memory the pointer refers to, so INVALID_HANDLE_VALUE would be
     * dereferenced rather than compared. Copying the pointer variable's own
     * bytes is what reads the value itself.
     *
     * @param CData $handle Any handle-typed CData.
     *
     * @return int The handle as a signed 64-bit integer; -1 is INVALID_HANDLE_VALUE.
     */
    private function handleValue(CData $handle): int
    {
        $value = $this->kernel32->new('int64_t');
        FFI::memcpy(FFI::addr($value), FFI::addr($handle), 8);

        return (int) $value->cdata;
    }

    /**
     * Reinterpret a 32-bit value read from C as signed.
     *
     * LONG fields come back through FFI as the unsigned number the bits spell,
     * so a time zone bias of -540 arrives as 4294966756. Every negative value
     * in these structures would otherwise read as a large positive one.
     *
     * @param int $value Value as FFI reported it.
     *
     * @return int
     */
    private function toSignedLong(int $value): int
    {
        $masked = $value & 0xFFFFFFFF;

        return $masked >= 0x80000000 ? $masked - 0x100000000 : $masked;
    }

    /**
     * Every network adapter the machine has, with its addresses.
     *
     * type is an IANA interface type: 6 ethernet, 71 wireless, 24 loopback,
     * 53 virtual, 131 tunnel.
     *
     * @return array<int, array{name: string, description: string, index: int, type: int, mac: string, dhcp: bool, addresses: array<int, array{ip: string, mask: string}>, gateway: string, dhcpServer: string}>|false
     */
    public function getNetworkAdapters(): array|false
    {
        // FFI::new() zeroes what it allocates, which is what the sizing call
        // needs to see.
        $size = $this->iphlpapi->new('ULONG');

        // The first call is expected to fail with ERROR_BUFFER_OVERFLOW and
        // report the size it wants.
        $this->iphlpapi->GetAdaptersInfo(null, FFI::addr($size));

        $wanted = (int) $size->cdata;

        if ($wanted <= 0) {
            return [];
        }

        $buffer = $this->iphlpapi->new('char[' . $wanted . ']');

        if ((int) $this->iphlpapi->GetAdaptersInfo(
            $this->iphlpapi->cast('IP_ADAPTER_INFO*', FFI::addr($buffer[0])),
            FFI::addr($size)
        ) !== 0) {
            return false;
        }

        $adapters = [];
        $adapter = $this->iphlpapi->cast('IP_ADAPTER_INFO*', FFI::addr($buffer[0]));

        // The list is chained through Next, which arrives as PHP null at the
        // end rather than as a null pointer.
        while ($adapter !== null) {
            $mac = [];

            for ($index = 0; $index < (int) $adapter->AddressLength; $index++) {
                $mac[] = sprintf('%02X', (int) $adapter->Address[$index]);
            }

            $addresses = [];
            $entry = FFI::addr($adapter->IpAddressList);

            while ($entry !== null) {
                $addresses[] = [
                    'ip' => $this->readNarrowString($entry->IpAddress->String),
                    'mask' => $this->readNarrowString($entry->IpMask->String),
                ];

                $entry = $entry->Next;
            }

            $adapters[] = [
                'name' => $this->readNarrowString($adapter->AdapterName),
                'description' => $this->readNarrowString($adapter->Description),
                'index' => (int) $adapter->Index,
                'type' => (int) $adapter->Type,
                'mac' => implode('-', $mac),
                'dhcp' => (int) $adapter->DhcpEnabled !== 0,
                'addresses' => $addresses,
                'gateway' => $this->readNarrowString($adapter->GatewayList->IpAddress->String),
                'dhcpServer' => $this->readNarrowString($adapter->DhcpServer->IpAddress->String),
            ];

            $adapter = $adapter->Next;
        }

        return $adapters;
    }

    /**
     * How many interfaces the IP stack counts, virtual ones included.
     *
     * @return int|false
     */
    public function getInterfaceCount(): int|false
    {
        $count = $this->iphlpapi->new('DWORD');

        if ((int) $this->iphlpapi->GetNumberOfInterfaces(FFI::addr($count)) !== 0) {
            return false;
        }

        return (int) $count->cdata;
    }

    /**
     * The interface the stack would route a given address through.
     *
     * @param string $destination Dotted-quad IPv4 address.
     *
     * @return int|false The interface index, matching getNetworkAdapters()' index.
     */
    public function getBestInterfaceFor(string $destination): int|false
    {
        $address = $this->ipv4ToNetworkOrder($destination);

        if ($address === false) {
            return false;
        }

        $index = $this->iphlpapi->new('DWORD');

        if ((int) $this->iphlpapi->GetBestInterface($address, FFI::addr($index)) !== 0) {
            return false;
        }

        return (int) $index->cdata;
    }

    /**
     * Send one ICMP echo request and wait for the reply.
     *
     * This is a raw ICMP echo, not a shell out to ping.exe: no process is
     * spawned, and the round trip time is the one the stack measured.
     *
     * status is an IP_STATUS: 0 success, 11002 destination net unreachable,
     * 11003 destination host unreachable, 11010 request timed out.
     *
     * @param string $destination   Dotted-quad IPv4 address.
     * @param int    $timeoutMillis How long to wait for the reply.
     * @param string $payload       Bytes to echo back.
     *
     * @return array{status: int, roundTripMillis: int, bytes: int}|false
     */
    public function ping(string $destination, int $timeoutMillis = 1000, string $payload = 'onetone'): array|false
    {
        $address = $this->ipv4ToNetworkOrder($destination);

        if ($address === false) {
            return false;
        }

        $handle = $this->iphlpapi->IcmpCreateFile();

        if ($handle === null || $this->handleValue($handle) === -1) {
            return false;
        }

        try {
            $length = max(1, strlen($payload));
            $request = $this->iphlpapi->new('char[' . $length . ']');
            FFI::memcpy($request, $payload, strlen($payload));

            // The reply is the fixed header, then the echoed data, then room
            // for the options the documentation requires be allowed for.
            $replySize = FFI::sizeof($this->iphlpapi->new('ICMP_ECHO_REPLY')) + $length + 8;
            $reply = $this->iphlpapi->new('char[' . $replySize . ']');

            $replies = (int) $this->iphlpapi->IcmpSendEcho(
                $handle,
                $address,
                FFI::addr($request[0]),
                $length,
                null,
                FFI::addr($reply[0]),
                $replySize,
                $timeoutMillis
            );

            if ($replies <= 0) {
                return false;
            }

            $echo = $this->iphlpapi->cast('ICMP_ECHO_REPLY*', FFI::addr($reply[0]));

            return [
                'status' => (int) $echo->Status,
                'roundTripMillis' => (int) $echo->RoundTripTime,
                'bytes' => (int) $echo->DataSize,
            ];
        } finally {
            $this->iphlpapi->IcmpCloseHandle($handle);
        }
    }

    /**
     * Every TCP connection, with the process that owns it.
     *
     * state: 1 closed, 2 listen, 3 syn sent, 4 syn received, 5 established,
     * 6 fin wait 1, 7 fin wait 2, 8 close wait, 9 closing, 10 last ack,
     * 11 time wait, 12 delete TCB.
     *
     * @return array<int, array{localAddress: string, localPort: int, remoteAddress: string, remotePort: int, state: int, processId: int}>|false
     */
    public function getTcpConnections(): array|false
    {
        // TCP_TABLE_OWNER_PID_ALL = 5, AF_INET = 2.
        return $this->readConnectionTable(
            'GetExtendedTcpTable',
            'MIB_TCPTABLE_OWNER_PID',
            'MIB_TCPROW_OWNER_PID',
            5,
            fn (CData $row): array => [
                'localAddress' => $this->networkOrderToIpv4((int) $row->dwLocalAddr),
                'localPort' => $this->portFromNetworkOrder((int) $row->dwLocalPort),
                'remoteAddress' => $this->networkOrderToIpv4((int) $row->dwRemoteAddr),
                'remotePort' => $this->portFromNetworkOrder((int) $row->dwRemotePort),
                'state' => (int) $row->dwState,
                'processId' => (int) $row->dwOwningPid,
            ]
        );
    }

    /**
     * Every UDP endpoint, with the process that owns it.
     *
     * @return array<int, array{localAddress: string, localPort: int, processId: int}>|false
     */
    public function getUdpEndpoints(): array|false
    {
        // UDP_TABLE_OWNER_PID = 1, AF_INET = 2.
        return $this->readConnectionTable(
            'GetExtendedUdpTable',
            'MIB_UDPTABLE_OWNER_PID',
            'MIB_UDPROW_OWNER_PID',
            1,
            fn (CData $row): array => [
                'localAddress' => $this->networkOrderToIpv4((int) $row->dwLocalAddr),
                'localPort' => $this->portFromNetworkOrder((int) $row->dwLocalPort),
                'processId' => (int) $row->dwOwningPid,
            ]
        );
    }

    /**
     * The ARP cache: which hardware address answers for which IPv4 address.
     *
     * type: 1 other, 2 invalid, 3 dynamic, 4 static.
     *
     * @return array<int, array{interfaceIndex: int, address: string, mac: string, type: int}>|false
     */
    public function getArpTable(): array|false
    {
        // FFI::new() zeroes what it allocates, which is what the sizing call
        // needs to see.
        $size = $this->iphlpapi->new('ULONG');

        $this->iphlpapi->GetIpNetTable(null, FFI::addr($size), 0);

        $wanted = (int) $size->cdata;

        if ($wanted <= 0) {
            return [];
        }

        $buffer = $this->iphlpapi->new('char[' . $wanted . ']');

        if ((int) $this->iphlpapi->GetIpNetTable(FFI::addr($buffer[0]), FFI::addr($size), 0) !== 0) {
            return false;
        }

        $table = $this->iphlpapi->cast('MIB_IPNETTABLE*', FFI::addr($buffer[0]));
        $entries = [];

        foreach ($this->rowsOf((int) $table->dwNumEntries, $buffer, 'MIB_IPNETTABLE', 'MIB_IPNETROW') as $row) {
            $mac = [];

            for ($byte = 0; $byte < (int) $row->dwPhysAddrLen; $byte++) {
                $mac[] = sprintf('%02X', (int) $row->bPhysAddr[$byte]);
            }

            $entries[] = [
                'interfaceIndex' => (int) $row->dwIndex,
                'address' => $this->networkOrderToIpv4((int) $row->dwAddr),
                'mac' => implode('-', $mac),
                'type' => (int) $row->dwType,
            ];
        }

        return $entries;
    }

    /**
     * Ask the network for the hardware address behind an IPv4 address.
     *
     * Unlike getArpTable() this sends a request, so it answers for a host that
     * is not in the cache yet - and only for one on the local segment.
     *
     * @param string $address Dotted-quad IPv4 address.
     *
     * @return string|false The MAC as AA-BB-CC-DD-EE-FF.
     */
    public function resolveMacAddress(string $address): string|false
    {
        $destination = $this->ipv4ToNetworkOrder($address);

        if ($destination === false) {
            return false;
        }

        $mac = $this->iphlpapi->new('char[8]');
        $length = $this->iphlpapi->new('ULONG');
        $length->cdata = 8;

        if ((int) $this->iphlpapi->SendARP($destination, 0, FFI::addr($mac[0]), FFI::addr($length)) !== 0) {
            return false;
        }

        // SendARP writes back how many bytes the address actually is; the
        // buffer's eight is only the room offered.
        $written = min(8, max(0, (int) $length->cdata));
        $raw = FFI::string($this->iphlpapi->cast('char*', FFI::addr($mac[0])), $written);

        if ($raw === '') {
            return false;
        }

        return implode('-', array_map(
            static fn (string $byte): string => sprintf('%02X', ord($byte)),
            str_split($raw)
        ));
    }

    /**
     * Read one of the two extended connection tables.
     *
     * Both are sized by a first call that is meant to fail, then filled; both
     * are a count followed by an inline array. Only the row type differs.
     *
     * The mapper turns each row into a plain array here, while the buffer is
     * still alive. Handing the row CData back to the caller instead would give
     * them pointers into a buffer this method has already dropped - which is
     * exactly what happened, and read as a UDP local address that changed from
     * one run to the next while the port beside it stayed right.
     *
     * @param string   $function   GetExtendedTcpTable or GetExtendedUdpTable.
     * @param string   $tableType  The table struct's name.
     * @param string   $rowType    The row struct's name.
     * @param int      $tableClass The TCP_TABLE_CLASS or UDP_TABLE_CLASS wanted.
     * @param callable $map        Turns one row into the array to return.
     *
     * @return array<int, array<string, mixed>>|false
     */
    private function readConnectionTable(string $function, string $tableType, string $rowType, int $tableClass, callable $map): array|false
    {
        // FFI::new() zeroes what it allocates, which is what the sizing call
        // needs to see.
        $size = $this->iphlpapi->new('DWORD');

        // AF_INET = 2.
        $this->iphlpapi->$function(null, FFI::addr($size), 0, 2, $tableClass, 0);

        $wanted = (int) $size->cdata;

        if ($wanted <= 0) {
            return [];
        }

        $buffer = $this->iphlpapi->new('char[' . $wanted . ']');

        if ((int) $this->iphlpapi->$function(FFI::addr($buffer[0]), FFI::addr($size), 0, 2, $tableClass, 0) !== 0) {
            return false;
        }

        $table = $this->iphlpapi->cast($tableType . '*', FFI::addr($buffer[0]));
        $mapped = [];

        foreach ($this->rowsOf((int) $table->dwNumEntries, $buffer, $tableType, $rowType) as $row) {
            $mapped[] = $map($row);
        }

        return $mapped;
    }

    /**
     * The rows of a "count, then the rows" table.
     *
     * Windows declares the array as one element and writes as many as it has.
     * FFI enforces the declared bound, so the rows are reached through a
     * pointer placed where the array starts rather than by indexing it. Where
     * that is comes from the two struct sizes, not from a hard-coded offset.
     *
     * @param int    $count     Value of the table's count field.
     * @param CData  $buffer    The buffer the table was written into.
     * @param string $tableType The table struct's name.
     * @param string $rowType   The row struct's name.
     *
     * @return array<int, CData>
     */
    private function rowsOf(int $count, CData $buffer, string $tableType, string $rowType): array
    {
        if ($count <= 0) {
            return [];
        }

        $offset = FFI::sizeof($this->iphlpapi->new($tableType)) - FFI::sizeof($this->iphlpapi->new($rowType));
        $first = $this->iphlpapi->cast($rowType . '*', FFI::addr($buffer[$offset]));

        $rows = [];

        for ($index = 0; $index < $count; $index++) {
            $rows[] = $first[$index];
        }

        return $rows;
    }

    /**
     * Read a null-terminated char array that the OS filled in.
     *
     * @param CData $characters A char[] inside a struct.
     *
     * @return string
     */
    private function readNarrowString(CData $characters): string
    {
        return FFI::string($this->iphlpapi->cast('char*', FFI::addr($characters[0])));
    }

    /**
     * A dotted-quad address as the 32 bits the IP helper functions expect.
     *
     * @param string $address Dotted-quad IPv4 address.
     *
     * @return int|false Network byte order, which is what these take.
     */
    private function ipv4ToNetworkOrder(string $address): int|false
    {
        $packed = @inet_pton($address);

        if ($packed === false || strlen($packed) !== 4) {
            return false;
        }

        // The bytes are already in network order; they only need reading as one
        // little-endian word, because that is how the C side stores them.
        $parts = unpack('V', $packed);

        return $parts === false ? false : (int) $parts[1];
    }

    /**
     * The inverse of ipv4ToNetworkOrder().
     *
     * @param int $address Network byte order.
     *
     * @return string Dotted-quad.
     */
    private function networkOrderToIpv4(int $address): string
    {
        return sprintf(
            '%d.%d.%d.%d',
            $address & 0xFF,
            ($address >> 8) & 0xFF,
            ($address >> 16) & 0xFF,
            ($address >> 24) & 0xFF
        );
    }

    /**
     * A port as the connection tables report it.
     *
     * They store it in network byte order in the low half of a DWORD, so the
     * two bytes have to be swapped back.
     *
     * @param int $port As read from the table.
     *
     * @return int
     */
    private function portFromNetworkOrder(int $port): int
    {
        return (($port & 0xFF) << 8) | (($port >> 8) & 0xFF);
    }

    /**
     * Assert HRESULT success and throw on failure.
     *
     * @param int $hr
     * @param string $ctx
     *
     * @return void
     */
    private function assertHr(int $hr, string $ctx): void
    {
        if ($hr < 0) {
            throw new RuntimeException(sprintf('%s failed: HRESULT 0x%08X', $ctx, $hr & 0xFFFFFFFF));
        }
    }

    #endregion
}
