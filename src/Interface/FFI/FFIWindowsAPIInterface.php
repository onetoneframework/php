<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

use FFI;
use FFI\CData;
use FFI\CType;

/**
 * FFI Windows API Interface
 *
 * Defines the contract for Windows API operations through FFI (Foreign Function Interface).
 * Provides methods for interacting with Windows system APIs, including window management,
 * file operations, network operations, and system information.
 */
interface FFIWindowsAPIInterface
{
    public static function cast(CType|string $type, CData|int|float|bool|null $ptr): ?CData;

    public static function new(CType|string $type, bool $owned = true, bool $persistent = false): ?CData;

    /**
     * Close an existing socket.
     */
    public function closesocket(mixed $s): int;

    /**
     * Terminate use of the Winsock 2 DLL.
     */
    public function WSACleanup(): int;
    /**
     * Return the number of waveform-audio output devices present in the system.
     */
    public function waveOutGetNumDevs(): int;
    /**
     * Return the number of waveform-audio input devices present in the system.
     */
    public function waveInGetNumDevs(): int;
    public function waveOutGetDevCapsA(int $uDeviceID, mixed $pwoc, int $cbwoc): mixed;
    public function waveInGetDevCapsA(int $uDeviceID, mixed $pwic, int $cbwic): mixed;
    public function midiOutGetDevCapsA(int $uDeviceID, mixed $pmoc, int $cbmoc): mixed;
    public function midiInGetDevCapsA(int $uDeviceID, mixed $pmic, int $cbmic): mixed;
    public function waveOutGetDevCapsW(int $uDeviceID, mixed $pwoc, int $cbwoc): mixed;
    public function waveInGetDevCapsW(int $uDeviceID, mixed $pwic, int $cbwic): mixed;
    public function midiOutGetDevCapsW(int $uDeviceID, mixed $pmoc, int $cbmoc): mixed;
    public function midiInGetDevCapsW(int $uDeviceID, mixed $pmic, int $cbmic): mixed;
    /**
     * Return the number of MIDI output devices present in the system.
     */
    public function midiOutGetNumDevs(): int;
    /**
     * Return the number of MIDI input devices present in the system.
     */
    public function midiInGetNumDevs(): int;
    /**
     * Perform an action on a file, returning an error value if the action fails (ANSI).
     */
    public function ShellExecuteExA(mixed $pExecInfo): bool;
    /**
     * Format a message string from a message definition (UTF-16).
     */
    public function FormatMessageW(int $dwFlags, mixed $lpSource, int $dwMessageId, int $dwLanguageId, mixed $lpBuffer, int $nSize, mixed $Arguments): int;

    /**
     * Receive data from a connected socket.
     */
    public function recv(mixed $s, mixed $buf, int $len, int $flags): int;

    /**
     * Send data on a connected socket.
     */
    public function send(mixed $s, mixed $buf, int $len, int $flags): int;

    /**
     * Establish a connection to a specified socket.
     */
    public function connect(mixed $s, mixed $name, int $namelen): int;

    /**
     * Retrieve the user name associated with the current thread (ANSI).
     */
    public function GetUserNameA(mixed $lpBuffer, FFI\CData|int $lpnSize): int;

    /**
     * Retrieve the NetBIOS name of the local computer (ANSI).
     */
    public function GetComputerNameA(mixed $lpBuffer, FFI\CData|int $lpnSize): int;

    /**
     * Retrieve the position of the mouse cursor, in screen coordinates.
     */
    public function GetCursorPos(mixed $lpPoint): bool;

    /**
     * Add a rectangle to the specified window's update region.
     */
    public function InvalidateRect(mixed $hWnd, mixed $lpRect, bool|int $bErase): int;

    /**
     * Retrieve information about a graphic object (logical pen, brush, bitmap, etc.).
     */
    public function GetObjectA(mixed $hObject, int $cbBuffer, mixed $lpvObject): int;

    /**
     * Retrieve the show state and the restored/minimized/maximized positions of a window.
     */
    public function GetWindowPlacement(mixed $hWnd, mixed $lpwndpl = null): bool;

    /**
     * Retrieve the connected state of the local system.
     */
    public function InternetGetConnectedState(CData $lpdwFlags, int $dwReserved): bool;

    /**
     * Determine whether a key is up or down at the time the function is called.
     */
    public function GetAsyncKeyState(int $vKey): int;

    /**
     * Update the specified rectangle or region in a window's client area.
     */
    public function RedrawWindow(mixed $hWnd, mixed $lprcUpdate, mixed $hrgnUpdate, int $flags): bool;

    /**
     * Load an image from the given file into a GDI+ image object.
     */
    public function GdipLoadImageFromFile(CData|string $filename, mixed $image): int;

    /**
     * Initiate use of the Winsock 2 DLL by the calling process.
     */
    public function WSAStartup(mixed $wVersionRequired, mixed $lpWSAData): int;

    /**
     * Associate a local address with a socket.
     */
    public function bind(mixed $socket, mixed $name, null|FFI\CData|int $namelen): int;

    /**
     * Place a socket in a state where it listens for incoming connections.
     */
    public function listen(mixed $socket, int $backlog): int;

    /**
     * Accept an incoming connection on a listening socket.
     */
    public function accept(mixed $socket, mixed $addr = null, null|FFI\CData|int $addrlen = null): int;

    /**
     * Create a Windows HBITMAP from a GDI+ Bitmap.
     */
    public function GdipCreateHBITMAPFromBitmap(mixed $GpBitmap, mixed $HBITMAP, CData|int $background): int;

    /**
     * Take a snapshot of the specified processes and the heaps, modules, and threads used by those processes.
     */
    public function CreateToolhelp32Snapshot(int $dwFlags, int $th32ProcessID): mixed;

    /**
     * Get the device context (DC) for the desktop window
     * @param mixed $hWnd
     */
    public function GetWindowDC(mixed $hWnd): mixed;
    /**
     * Get the desktop window handle
     */
    public function GetDesktopWindow(): mixed;
    /**
     * Write data to the specified file or input/output (I/O) device.
     *
     * @param mixed $hFile Handle to the file or I/O device.
     * @param mixed $lpBuffer Pointer to the buffer containing the data to be written.
     * @param int $nNumberOfBytesToWrite Number of bytes to be written.
     * @param mixed $lpNumberOfBytesWritten Pointer to the variable that receives the number of bytes written.
     * @param mixed $lpOverlapped Pointer to an OVERLAPPED structure; NULL for synchronous I/O.
     */
    public function WriteFile(mixed $hFile, mixed $lpBuffer, int $nNumberOfBytesToWrite, mixed $lpNumberOfBytesWritten, mixed $lpOverlapped): bool;
    /**
     * Retrieve device-specific information about a given device (DPI, color planes, etc.).
     */
    public function GetDeviceCaps(mixed $hWnd, int $flag): int;
    /**
     * Create a memory device context (DC) compatible with the specified device.
     */
    public function CreateCompatibleDC(mixed $hWnd): mixed;
    /**
     * Create a bitmap compatible with the device associated with the specified device context.
     */
    public function CreateCompatibleBitmap(mixed $hWnd, int $width, int $height): mixed;
    /**
     * Select a GDI object (bitmap, pen, brush, font, region) into the specified device context.
     */
    public function SelectObject(mixed $hdc, mixed $hBitmap): mixed;
    /**
     * Create or open a file or I/O device (ANSI).
     *
     * @param mixed $lpFileName Path to the file to be opened or created.
     * @param int $dwDesiredAccess Requested access mode (GENERIC_READ, GENERIC_WRITE, ...).
     * @param int $dwShareMode Requested sharing mode; 0 means exclusive access.
     * @param mixed $lpSecurityAttributes Optional SECURITY_ATTRIBUTES pointer; NULL for default.
     * @param int $dwCreationDisposition Action to take on files that exist or do not exist.
     * @param int $dwFlagsAndAttributes File attribute and flag values.
     * @param mixed $hTemplateFile Optional template file handle.
     */
    public function CreateFileA(mixed $lpFileName, int $dwDesiredAccess, int $dwShareMode, mixed $lpSecurityAttributes, int $dwCreationDisposition, int $dwFlagsAndAttributes, mixed $hTemplateFile): mixed;
    /**
     * Retrieve a handle to the display monitor that has the largest area of intersection with the window.
     */
    public function MonitorFromWindow(mixed $hWnd, int $dwFlags): mixed;
    /**
     * Retrieve the number of physical monitors associated with an HMONITOR.
     */
    public function GetNumberOfPhysicalMonitorsFromHMONITOR(mixed $hMonitor, mixed $pdwNumberOfPhysicalMonitors): int;
    /**
     * Register a window class for subsequent use in CreateWindow / CreateWindowEx (UTF-16).
     */
    public function RegisterClassExW(mixed $lpwcx): int;
    /**
     * Map a character string to a UTF-16 (wide character) string.
     */
    public function MultiByteToWideChar(int $CodePage, int $dwFlags, mixed $lpMultiByteStr, int $cbMultiByte, mixed $lpWideCharStr, int $cchWideChar): int;
    /**
     * Perform a bit-block transfer of color data from a source DC to a destination DC.
     */
    public function BitBlt(mixed $hdc, int $xDest, int $yDest, int $nWidth, int $nHeight, mixed $hdcSrc, int $xSrc, int $ySrc, int $dwRop): bool;
    /**
     * Close an open object handle.
     */
    public function CloseHandle(mixed $hWnd): bool;
    /**
     * Set the cursor position in the specified console screen buffer.
     */
    public function SetConsoleCursorPosition(mixed $hConsoleOutput, mixed $dwCursorPosition): bool;
    /**
     * Create an overlapped, pop-up, or child window with an extended window style (ANSI).
     */
    public function CreateWindowExA(int $dwExStyle, mixed $lpClassName, mixed $lpWindowName, int $dwStyle, int $x, int $y, int $nWidth, int $nHeight, mixed $hWndParent, mixed $hMenu, mixed $hInstance, mixed $lpParam): mixed;
    /**
     * Open an existing local process object.
     */
    public function OpenProcess(int $dwDesiredAccess, FFI\CData|bool|int $bInheritHandle, FFI\CData|int $dwProcessId): mixed;
    /**
     * Synthesize a mouse motion or button event.
     */
    public function mouse_event(int $dwFlags, int $dx, int $dy, int $dwData, int $dwExtraInfo): void;
    /**
     * Synthesize a keystroke event.
     */
    public function keybd_event(int $bVk, int $bScan, int $dwFlags, int $dwExtraInfo): void;
    /**
     * Retrieve the specified system metric or system configuration setting.
     */
    public function GetSystemMetrics(int $nIndex): int;
    /**
     * Retrieve or set the value of one of the system-wide parameters (UTF-16 variant).
     */
    public function SystemParametersInfoW(int $uiAction, int $uiParam, mixed $pvParam, int $fWinIni): bool;
    /**
     * Change the position and dimensions of the specified window.
     */
    public function MoveWindow(mixed $hWnd, int $x, int $y, int $nWidth, int $nHeight, FFI\CData|int|bool|null $bRepaint = 0): bool;
    /**
     * Display a modal dialog box containing a system icon, a set of buttons, and a short message (ANSI).
     */
    public function MessageBoxA(mixed $hWnd, string $lpText, string $lpCaption, FFI\CData|int|null $flags = 0): int;
    /**
     * Retrieve the current volume level of the specified waveform-audio output device.
     */
    public function waveOutGetVolume(mixed $hWnd, FFI\CData $pdwVolume): int;
    /**
     * Log the user off, shut down, or restart the system.
     */
    public function ExitWindowsEx(int $uFlags, int $dwReason): bool;
    /**
     * Terminate the specified process and all of its threads.
     */
    public function TerminateProcess(mixed $hProcess, int $uExitCode): bool;
    /**
     * Retrieve a bitmask of currently available disk drives.
     */
    public function GetLogicalDrives(): int;
    /**
     * Return the language identifier for the current user locale (LCID).
     */
    public function GetUserDefaultLangID(): int;
    /**
     * Retrieve the window handle used by the console associated with the calling process.
     */
    public function GetConsoleWindow(): int;
    /**
     * Set the pixel at the given coordinates to the closest approximation of the specified color (verify-less).
     */
    public function SetPixelV(mixed $hdc, int $x, int $y, int $color): bool;
    /**
     * Open the specified registry key (UTF-16).
     */
    public function RegOpenKeyExW(mixed $hKey, mixed $lpSubKey, int $ulOptions, int $samDesired, mixed $phkResult): int;
    /**
     * Retrieve the process identifier of the specified process.
     */
    public function GetProcessId(mixed $hWnd): int;
    /**
     * Read data from the specified file or input/output (I/O) device.
     *
     * @param mixed $hFile File handle.
     * @param mixed $lpBuffer Pointer to the buffer that receives the data.
     * @param int $nNumberOfBytesToRead Number of bytes to read.
     * @param mixed $lpNumberOfBytesRead Pointer that receives the number of bytes read.
     * @param mixed $lpOverlapped Pointer to an OVERLAPPED structure; NULL for synchronous I/O.
     */
    public function ReadFile(mixed $hFile, mixed $lpBuffer, int $nNumberOfBytesToRead, mixed $lpNumberOfBytesRead, mixed $lpOverlapped): int;
    /**
     * Retrieve the size of the specified file, in bytes.
     */
    public function GetFileSize(mixed $hFile, mixed $lpFileSizeHigh): int;
    /**
     * Create or open a file or I/O device (UTF-16).
     *
     * @param mixed $lpFileName Path to the file (wide string pointer).
     * @param int $dwDesiredAccess Requested access mode.
     * @param int $dwShareMode Sharing mode (0 = exclusive).
     * @param mixed $lpSecurityAttributes Optional SECURITY_ATTRIBUTES pointer.
     * @param int $dwCreationDisposition Action to take on files that do or do not exist.
     * @param int $dwFlagsAndAttributes File attributes and flags.
     * @param mixed $hTemplateFile Optional template file handle.
     */
    public function CreateFileW(mixed $lpFileName, int $dwDesiredAccess, int $dwShareMode, mixed $lpSecurityAttributes, int $dwCreationDisposition, int $dwFlagsAndAttributes, mixed $hTemplateFile): mixed;
    /**
     * Retrieve the calling thread's last-error code value.
     */
    public function GetLastError(): int;
    /**
     * Retrieve the identifier of the thread that created the specified window and, optionally, its process.
     */
    public function GetWindowThreadProcessId(mixed $hWnd, mixed $lpdwProcessId): int;
    /**
     * Enumerate all top-level windows on the screen by calling an application-defined callback.
     */
    public function EnumWindows(mixed $lpEnumFunc, mixed $lParam): bool;
    /**
     * Change the text of the specified window's title bar (UTF-16).
     */
    public function SetWindowTextW(mixed $hWnd, mixed $lpString): int;
    /**
     * Generate simple tones on the speaker.
     */
    public function Beep(int $dwFreq, int $dwDuration): bool;
    /**
     * Play a WAVE sound specified by a file name or resource (ANSI).
     */
    public function PlaySoundA(null|string $pszSound, mixed $hmod, int $fdwSound): bool;
    /**
     * Set the specified window's show state.
     */
    public function ShowWindow(null|string|FFI\CData $hWnd, int $nCmdShow): bool;
    /**
     * Retrieve a module handle for the specified module (ANSI).
     */
    public function GetModuleHandleA(mixed $lpModuleName): mixed;
    /**
     * Update the client area of the specified window by sending a WM_PAINT message.
     */
    public function UpdateWindow(null|CData|string $hWnd): bool;
    /**
     * Perform an operation on a specified file or URL (UTF-16).
     */
    public function ShellExecuteW(mixed $hwnd, mixed $lpOperation, mixed $lpFile, mixed $lpParameters, mixed $lpDirectory, int $showCmd): mixed;
    /**
     * Read data from a handle opened by InternetOpenUrl, FtpOpenFile, or HttpOpenRequest.
     */
    public function InternetReadFile(mixed $hFile, mixed $lpBuffer, int $dwNumberOfBytesToRead, mixed $lpdwNumberOfBytesRead): bool;
    /**
     * Delete the specified device context (DC).
     */
    public function DeleteDC(mixed $hdc): bool;
    /**
     * Close a single Internet handle.
     */
    public function InternetCloseHandle(mixed $hInternet): bool;
    /**
     * Open a resource specified by a complete URL (ANSI).
     */
    public function InternetOpenUrlA(mixed $hInternet, mixed $lpszUrl, mixed $lpszHeaders, int $dwHeadersLength, int $dwFlags, mixed $dwContext): mixed;
    /**
     * Initialize an application's use of the WinINet functions (ANSI).
     */
    public function InternetOpenA(mixed $lpszAgent, int $dwAccessType, mixed $lpszProxyName, mixed $lpszProxyBypass, int $dwFlags): mixed;
    /**
     * Delete a logical pen, brush, font, bitmap, region, or palette, freeing all system resources.
     */
    public function DeleteObject(mixed $hBitmap): bool;
    /**
     * Retrieve information about the amount of space available on a disk volume (ANSI).
     */
    public function GetDiskFreeSpaceExA(mixed $lpDirectoryName, mixed $lpFreeBytesAvailable, mixed $lpTotalNumberOfBytes, mixed $lpTotalNumberOfFreeBytes): bool;
    /**
     * Release a device context (DC), freeing it for use by other applications.
     */
    public function ReleaseDC(mixed $hWnd, mixed $hdc): int;
    /**
     * Retrieve bits from the specified compatible bitmap and copy them as a DIB.
     */
    public function GetDIBits(mixed $hdc, mixed $hbmp, int $uStartScan, int $cScanLines, mixed $lpvBits, mixed $lpbmi, int $usage): int;
    /**
     * Copy the text of the specified window's title bar into a buffer (UTF-16).
     */
    public function GetWindowTextW(mixed $hWnd, mixed $lpString, int $nMaxCount): int;
    /**
     * Bring the specified window to the top of the Z-order.
     */
    public function BringWindowToTop(mixed $hWnd): bool;
    /**
     * Bring the thread that created the specified window into the foreground and activate it.
     */
    public function SetForegroundWindow(mixed $hWnd): bool;
    /**
     * Retrieve a handle to the top-level window whose class name or window name matches (ANSI).
     *
     * Tries the window title first, then the class name (which is less reliable).
     * Combining both arguments narrows the search.
     */
    public function FindWindowA(mixed $lpClassName, mixed $lpWindowName): mixed;
    /**
     * Determine whether the specified window handle identifies an existing window.
     */
    public function IsWindow(mixed $hWnd): bool;
    /**
     * Set an event hook function for a range of events.
     *
     * @param int $eventMin Lowest event value handled by the hook function.
     * @param int $eventMax Highest event value handled by the hook function.
     * @param mixed $hmodWinEventProc Module containing the hook function (NULL = out-of-context).
     * @param mixed $pfnWinEventProc Pointer to the hook function; a PHP closure routed through an internal trampoline.
     *
     * Callback signature:
     * - HWINEVENTHOOK hHook
     * - DWORD event
     * - HWND hwnd          : window whose title changed
     * - LONG idObject      : must be OBJID_WINDOW (0)
     * - LONG idChild       : must be CHILDID_SELF (0)
     * - DWORD eventThread  : source thread
     * - DWORD eventTime    : event timestamp (ms since boot)
     *
     * @param int $idProcess 0 = all threads in the process.
     * @param int $idThread 0 = all threads on the desktop.
     * @param int $type Flags controlling the hook behavior.
     */
    public function SetWinEventHook(int $eventMin, int $eventMax, mixed $hmodWinEventProc, mixed $pfnWinEventProc, int $idProcess, int $idThread, int $type): mixed;
    /**
     * Retrieve the handle to the Shell's desktop window.
     */
    public function GetShellWindow(): mixed;
    /**
     * Change an attribute of the specified window (32-bit, ANSI).
     */
    public function SetWindowLongA(mixed $hWnd, int $nIndex, mixed $dwNewLong): int;
    /**
     * Set the opacity and transparency color key of a layered window.
     */
    public function SetLayeredWindowAttributes(mixed $hWnd, int $crKey, int $bAlpha, int $dwFlags): int;
    /**
     * Flash the specified window once or continuously; it does not change the active state.
     */
    public function FlashWindowEx(CData $pfwi): bool;
    /**
     * Retrieve a handle to the foreground window.
     */
    public function GetForegroundWindow(): mixed;
    /**
     * Retrieve the dimensions of the bounding rectangle of the specified window.
     */
    public function GetWindowRect(mixed $hWnd, mixed $lpRect): bool;
    /**
     * Copy the text of the specified window's title bar into a buffer (ANSI).
     */
    public function GetWindowTextA(mixed $hWnd, mixed $lpString, int $nMaxCount): int;
    /**
     * Retrieve a module handle for the specified module (UTF-16).
     */
    public function GetModuleHandleW(mixed $lpModuleName): mixed;
    /**
     * Retrieve the power status of the system (AC line, battery, etc.).
     */
    public function GetSystemPowerStatus(mixed $lpSystemPowerStatus): bool;
    /**
     * Create an overlapped, pop-up, or child window with an extended window style (UTF-16).
     *
     * @param int $dwExStyle Extended window style of the window being created.
     * @param mixed $lpClassName Registered class name or atom.
     * @param mixed $lpWindowName Window name / title bar text.
     * @param int $dwStyle Standard window style.
     * @param int $X Initial horizontal position of the window.
     * @param int $Y Initial vertical position of the window.
     * @param int $nWidth Width, in device units.
     * @param int $nHeight Height, in device units.
     * @param mixed $hWndParent Handle to the parent or owner window.
     * @param mixed $hMenu Handle to a menu, or child-window identifier.
     * @param mixed $hInstance Handle to the instance of the module associated with the window.
     * @param mixed $lpParam Value to be passed to the window through the CREATESTRUCT structure.
     */
    public function CreateWindowExW(int $dwExStyle, mixed $lpClassName, mixed $lpWindowName, int $dwStyle, int $X, int $Y, int $nWidth, int $nHeight, mixed $hWndParent, mixed $hMenu, mixed $hInstance, mixed $lpParam): mixed;
    /**
     * Retrieve a handle to the top-level window whose class name and window name match (UTF-16).
     */
    public function FindWindowW(mixed $lpClassName, mixed $lpWindowName): mixed;
    /**
     * Retrieve the specified kind of information about the system (Ntdll).
     */
    public function NtQuerySystemInformation(int $SystemInformationClass, mixed $SystemInformation, int $SystemInformationLength, mixed $ReturnLength): int;
    /**
     * C runtime: read data from a stream (fread).
     */
    public function fread(mixed $ptr, int $size, int $count, mixed $stream): int;
    /**
     * C runtime: open a file stream (fopen).
     */
    public function fopen(mixed $filename, mixed $mode): mixed;
    /**
     * C runtime: close a file stream (fclose).
     */
    public function fclose(mixed $stream): int;
    /**
     * C runtime: set the file position indicator for a stream.
     *
     * @param int $origin SEEK_END is 2, SEEK_CUR is 1, SEEK_SET is 0 in Windows msvcrt.
     */
    public function fseek(mixed $stream, int $offset, int $origin): int;
    /**
     * C runtime: return the current position indicator of a stream.
     */
    public function ftell(mixed $stream): int;
    /**
     * Retrieve file system attributes for a specified file or directory (UTF-16).
     */
    public function GetFileAttributesW(mixed $lpFileName): int;
    /**
     * Retrieve a handle to the specified standard device (stdin, stdout, or stderr).
     */
    public function GetStdHandle(int $nStdHandle): mixed;
    /**
     * Register a window class for subsequent use in CreateWindow / CreateWindowEx (ANSI).
     */
    public function RegisterClassExA(mixed $lpwcx): int;
    /**
     * Clear a VARIANT by releasing any embedded references (COM / OLE Automation).
     */
    public function VariantClear(mixed $pvarg): int;
    /**
     * Release a BSTR string previously allocated with SysAllocString.
     */
    public function SysFreeString(mixed $bstr): void;
    /**
     * Destroy a timer previously created by SetTimer.
     */
    public function KillTimer(mixed $hWnd, int $uIDEvent): bool;
    /**
     * Create a timer with the specified time-out value.
     */
    public function SetTimer(mixed $hWnd, int $nIDEvent, int $uElapse, mixed $lpTimerFunc): int;
    /**
     * Retrieve the name of the class to which the specified window belongs (ANSI).
     */
    public function GetClassNameA(mixed $hWnd, mixed $lpClassName, int $nMaxCount): int;
    /**
     * Send a message to a window and wait until the window procedure has processed it (UTF-16).
     */
    public function SendMessageW(mixed $hWnd, int $Msg, mixed $wParam, mixed $lParam): int;
    /**
     * Retrieve a handle to a window whose class name and window name match, searching child windows (ANSI).
     */
    public function FindWindowExA(mixed $hwndParent, mixed $hwndChildAfter, mixed $lpszClass, mixed $lpszWindow): mixed;
    /**
     * Initialize a VARIANT (COM / OLE Automation).
     */
    public function VariantInit(mixed $pvarg): void;
    /**
     * Allocate a BSTR string and copy the passed-in string into it.
     */
    public function SysAllocString(mixed $psz): mixed;
    /**
     * Initialize the COM library for use by the calling thread, setting the concurrency model.
     */
    public function CoInitializeEx(mixed $pvReserved, int $dwCoInit): int;
    /**
     * Retrieve the current input mode of a console's input buffer or output mode of a console screen buffer.
     */
    public function GetConsoleMode(mixed $hConsoleHandle, mixed $lpMode): bool;
    /**
     * Set the input mode of a console's input buffer or the output mode of a console screen buffer.
     */
    public function SetConsoleMode(mixed $hConsoleHandle, int $dwMode): bool;
    /**
     * Retrieve the number of unread input records in the console's input buffer.
     */
    public function GetNumberOfConsoleInputEvents(mixed $hConsoleInput, mixed $lpcNumberOfEvents): bool;
    /**
     * Translate virtual-key messages into character messages (WM_KEYDOWN → WM_CHAR).
     */
    public function TranslateMessage(mixed $lpMsg): bool;
    /**
     * Retrieve a message from the calling thread's message queue (UTF-16).
     */
    public function GetMessageW(mixed $lpMsg, mixed $hWnd, int $wMsgFilterMin, int $wMsgFilterMax): int;
    /**
     * Destroy the specified window and all child windows.
     */
    public function DestroyWindow(mixed $hWnd): bool;
    /**
     * Add, modify, or delete an icon from the taskbar status area (UTF-16).
     */
    public function Shell_NotifyIconW(int $dwMessage, mixed $lpData): int;
    /**
     * Indicate to the system that a thread has made a request to terminate (quit).
     *
     * Safe to call from the same thread's WinEvent callback — it simply sets a flag
     * that makes GetMessageW / PeekMessageW return 0 on the next iteration. Unlike
     * PostThreadMessageW(GetCurrentThreadId(), WM_QUIT, ...), it does not need a
     * thread-ID lookup and cannot deadlock.
     */
    public function PostQuitMessage(int $nExitCode): void;
    /**
     * Call the default window procedure to provide default processing for messages (UTF-16).
     */
    public function DefWindowProcW(mixed $hWnd, int $uMsg, mixed $wParam, mixed $lParam): int;
    /**
     * Dispatch a message to a window procedure (UTF-16).
     */
    public function DispatchMessageW(mixed $lpMsg): int;
    /**
     * Load the specified cursor resource from an executable (ANSI).
     */
    public function LoadCursorA(mixed $hInstance, mixed $lpCursorName): mixed;
    /**
     * Load the specified icon resource from an executable (ANSI).
     */
    public function LoadIconA(mixed $hInstance, mixed $lpIconName): mixed;
    /**
     * Dispatch a message to a window procedure (ANSI).
     */
    public function DispatchMessageA(mixed $lpMsg): int;
    /**
     * Load a new input locale (keyboard layout) into the system (ANSI).
     */
    public function LoadKeyboardLayoutA(mixed $pwszKLID, int $Flags): mixed;
    /**
     * Set the input locale identifier for the calling thread or the current process.
     */
    public function ActivateKeyboardLayout(mixed $hkl, int $Flags): mixed;
    /**
     * Retrieve a message from the calling thread's message queue (ANSI).
     */
    public function GetMessageA(mixed $lpMsg, mixed $hWnd, int $wMsgFilterMin, int $wMsgFilterMax): int;
    /**
     * Enumerate the subkeys of the specified open registry key (ANSI).
     */
    public function RegEnumKeyExA(mixed $hKey, int $dwIndex, mixed $lpName, mixed $lpcchName, mixed $lpReserved, mixed $lpClass, mixed $lpcchClass, mixed $lpftLastWriteTime): int;
    /**
     * Close a handle to the specified registry key.
     */
    public function RegCloseKey(mixed $hKey): int;
    /**
     * Open the specified registry key (ANSI).
     */
    public function RegOpenKeyExA(mixed $hKey, mixed $lpSubKey, int $ulOptions, int $samDesired, mixed $phkResult): int;
    /**
     * Retrieve the type and data for the specified value name associated with an open registry key (ANSI).
     */
    public function RegQueryValueExA(mixed $hKey, mixed $lpValueName, mixed $lpReserved, mixed $lpType, mixed $lpData, mixed $lpcbData): int;
    /**
     * Create a menu. The menu is initially empty.
     */
    public function CreateMenu(): mixed;
    /**
     * Add, modify, or delete an icon from the taskbar status area (ANSI).
     */
    public function Shell_NotifyIconA(int $dwMessage, mixed $lpdata): bool;
    /**
     * Retrieve the coordinates of a window's client area.
     */
    public function GetClientRect(FFI\CData $hWnd, mixed $lpRect): bool;

    /**
     * Change the size, position, and Z-order of a child, pop-up, or top-level window.
     */
    public function SetWindowPos(FFI\CData $hWnd, mixed $hWndInsertAfter, int $X, int $Y, int $cx, int $cy, int $uFlags): bool;
    /**
     * Send a message to a window and wait until the window procedure has processed it (ANSI).
     */
    public function SendMessageA(FFI\CData $hWnd, int $Msg, mixed $wParam, mixed $lParam): int;

    /**
     * Load an icon, cursor, animated cursor, or bitmap (ANSI).
     */
    public function LoadImageA(null|FFI\CData $hInst, mixed $name, int $type, int $cx, int $cy, int $fuLoad): mixed;

    /**
     * Create a socket that is bound to a specific transport service provider (Winsock).
     */
    public function socket(int $af, int $type, int $protocol): mixed;
    /**
     * Convert an unsigned short from host to TCP/IP network byte order (big-endian).
     */
    public function htons(int $hostshort): int;
    /**
     * Convert a string containing an IPv4 address in dotted-decimal notation to a numeric value.
     */
    public function inet_addr(string $cp): int;
    /**
     * Translate a character to the corresponding virtual-key code and shift state.
     */
    public function VkKeyScanA(int $ch): int;
    /**
     * Play a simple sound associated with a system message box style.
     */
    public function MessageBeep(int $uType): bool;
    /**
     * Lock the current workstation's display (Win+L).
     */
    public function LockWorkStation(): bool;
    /**
     * Set the volume level of the given waveform-audio output device.
     */
    public function waveOutSetVolume(mixed $hwo, int $dwVolume): int;
    /**
     * Copy an existing file to a new one (ANSI).
     */
    public function CopyFileA(mixed $lpExistingFileName, mixed $lpNewFileName, bool|int $bFailIfExists): bool;
    /**
     * Delete a file from disk (ANSI).
     */
    public function DeleteFileA(mixed $lpFileName): bool;
    /**
     * Move (rename) an existing file or directory (ANSI).
     */
    public function MoveFileA(mixed $lpExistingFileName, mixed $lpNewFileName): bool;
    /**
     * Retrieve information about the first process encountered in a system snapshot (ANSI).
     */
    public function Process32First(mixed $hSnapshot, mixed $processEntryPtr): bool;
    /**
     * Retrieve information about the next process recorded in a system snapshot (ANSI).
     */
    public function Process32Next(mixed $hSnapshot, mixed $processEntryPtr): bool;
    /**
     * Append a new item to the end of the specified menu bar, drop-down, submenu, or shortcut menu (ANSI).
     */
    public function AppendMenuA(mixed $hMenu, int $uFlags, int $uIDNewItem, mixed $lpNewItem): bool;
    /**
     * Assign a new menu to the specified window.
     */
    public function SetMenu(mixed $hWnd, mixed $hMenu): int;
    /**
     * Create a new process and its primary thread (ANSI).
     */
    public function CreateProcessA(mixed $lpApplicationName, mixed $lpCommandLine, mixed $lpProcessAttributes, mixed $lpThreadAttributes, mixed $bInheritHandles, int $dwCreationFlags, mixed $lpEnvironment, mixed $lpCurrentDirectory, mixed $lpStartupInfo, mixed $lpProcessInformation): bool;
    /**
     * Create a drop-down menu, submenu or shortcut menu.
     */
    public function CreatePopupMenu(): mixed;
    /**
     * Retrieve a handle to the menu assigned to the specified window.
     */
    public function GetMenu(mixed $hWnd): mixed;
    /**
     * Retrieve the bounding rectangle of the specified menu item.
     */
    public function GetMenuItemRect(mixed $hWnd, mixed $hMenu, int $uItem, mixed $lprcItem): bool;
    /**
     * Display a shortcut menu at the specified location and track item selection.
     */
    public function TrackPopupMenu(mixed $hMenu, int $uFlags, int $x, int $y, int $nReserved, mixed $hWnd, mixed $prcRect): bool;
    /**
     * Clean up resources used by GDI+.
     */
    public function GdiplusShutdown(mixed $token): void;
    /**
     * Open the specified MIDI output device for playback.
     */
    public function midiOutOpen(mixed $lphmo, int $uDeviceID, mixed $dwCallback, mixed $dwInstance, int $dwFlags): int;
    /**
     * Close the specified MIDI output device.
     */
    public function midiOutClose(mixed $hmo): int;
    /**
     * Send a short MIDI message to the specified MIDI output device.
     */
    public function midiOutShortMsg(mixed $hmo, int $dwMsg): int;
    /**
     * Display the common Color selection dialog (UTF-16).
     */
    public function ChooseColorW(mixed $lpcc): int;
    /**
     * Display the common Open dialog (ANSI).
     */
    public function GetOpenFileNameA(mixed $lpofn): bool;
    /**
     * Display the common Save As dialog (ANSI).
     */
    public function GetSaveFileNameA(mixed $lpofn): bool;
    /**
     * Change the text of the specified window's title bar (ANSI).
     */
    public function SetWindowTextA(mixed $hWnd, mixed $lpString): int;
    /**
     * Retrieve the length, in characters, of the specified window's title bar text (ANSI).
     */
    public function GetWindowTextLengthA(mixed $hWnd): int;
    /**
     * Retrieve a handle to the window that contains the specified point.
     */
    public function WindowFromPoint(mixed $Point): mixed;
    /**
     * Retrieve information about one of the graphics modes for a display device (ANSI).
     */
    public function EnumDisplaySettingsA(mixed $lpszDeviceName, int $iModeNum, mixed $lpDevMode): int;
    /**
     * Change the settings of the default display device to the specified graphics mode (ANSI).
     */
    public function ChangeDisplaySettingsA(mixed $lpDevMode, int $dwFlags): int;
    /**
     * Search a directory for a file or subdirectory with a name matching a specified pattern (ANSI).
     */
    public function FindFirstFileA(mixed $lpFileName, mixed $lpFindFileData): mixed;
    /**
     * Continue a file search started by FindFirstFileA (ANSI).
     */
    public function FindNextFileA(mixed $hFindFile, mixed $lpFindFileData): bool;
    /**
     * Close a file search handle opened by FindFirstFileA / FindFirstFileW.
     */
    public function FindClose(mixed $hFindFile): bool;
    /**
     * Create a new directory (ANSI).
     */
    public function CreateDirectoryA(mixed $lpPathName, mixed $lpSecurityAttributes): bool;
    /**
     * Remove an existing empty directory (ANSI).
     */
    public function RemoveDirectoryA(mixed $lpPathName): bool;
    /**
     * Read input records (keyboard, mouse, focus, etc.) from the console input buffer (ANSI).
     */
    public function ReadConsoleInputA(mixed $hConsoleInput, mixed $lpBuffer, int $nLength, mixed $lpNumberOfEventsRead): bool;
    /**
     * Prepare the specified window for painting and fill a PAINTSTRUCT structure.
     */
    public function BeginPaint(mixed $hWnd, mixed $lpPaint): mixed;
    /**
     * Mark the end of painting in the specified window that began with BeginPaint.
     */
    public function EndPaint(mixed $hWnd, mixed $lpPaint): bool;
    /**
     * Return a device information set containing devices of a class (ANSI).
     */
    public function SetupDiGetClassDevsA(mixed $ClassGuid, mixed $Enumerator, mixed $hwndParent, int $Flags): mixed;
    /**
     * Return a SP_DEVINFO_DATA structure that specifies an element in the device information set.
     */
    public function SetupDiEnumDeviceInfo(mixed $DeviceInfoSet, int $MemberIndex, mixed $DeviceInfoData): bool;
    /**
     * Delete a device information set and free all associated memory.
     */
    public function SetupDiDestroyDeviceInfoList(mixed $DeviceInfoSet): bool;
    /**
     * Retrieve a specified Plug and Play device property (ANSI).
     */
    public function SetupDiGetDeviceRegistryPropertyA(mixed $DeviceInfoSet, mixed $DeviceInfoData, int $Property, mixed $PropertyRegDataType, mixed $PropertyBuffer, int $PropertyBufferSize, mixed $RequiredSize): bool;
    /**
     * Format a message string from a message definition (ANSI).
     */
    public function FormatMessageA(mixed $dwFlags, mixed $lpSource, int $dwMessageId, int $dwLanguageId, mixed $lpBuffer, int $nSize, mixed $Arguments): int;
    /**
     * Send a control code directly to a specified device driver, causing the driver to perform an operation.
     */
    public function DeviceIoControl(mixed $hDevice, int $dwIoControlCode, mixed $lpInBuffer, int $nInBufferSize, mixed $lpOutBuffer, int $nOutBufferSize, mixed $lpBytesReturned, mixed $lpOverlapped): bool;
    /**
     * Initialize GDI+ for the process.
     */
    public function GdiplusStartup(mixed $token, mixed $input, mixed $output): int;
    /**
     * Get the process identifier of the calling process.
     */
    public function GetCurrentProcessId(): int;
    /**
     * Determine whether the specified window is a native Unicode window.
     */
    public function IsWindowUnicode(mixed $hWnd): bool;
    /**
     * Close the specified waveform-audio output device.
     */
    public function waveOutClose(mixed $hwo): int;
    /**
     * Retrieve the identifier of the specified control.
     */
    public function GetDlgCtrlID(mixed $hWnd): int;
    /**
     * Open a waveform-audio output device for playback.
     */
    public function waveOutOpen(mixed $phwo, int $uDeviceID, mixed $pwfx, mixed $dwCallback, mixed $dwInstance, int $fdwOpen): int;
    /**
     * Initialize the COM library on the current thread (single-threaded apartment).
     */
    public function CoInitialize(mixed $pvReserved): int;
    /**
     * Create a single uninitialized object of the class associated with a specified CLSID.
     */
    public function CoCreateInstance(mixed $rclsid, mixed $pUnkOuter, int $dwClsContext, mixed $riid, mixed $ppv): int;
    /**
     * Close the COM library on the current thread, unloading DLLs loaded by this thread.
     */
    public function CoUninitialize(): void;
    /**
     * Activate the specified waitable timer.
     */
    public function SetWaitableTimer(mixed $hTimer, mixed $lpDueTime, int $lPeriod, mixed $pfnCompletionRoutine, mixed $lpArgToCompletionRoutine, mixed $fResume): bool;
    /**
     * Create or open a waitable timer object (UTF-16).
     */
    public function CreateWaitableTimerW(mixed $lpTimerAttributes, mixed $bManualReset, mixed $lpTimerName): mixed;
    /**
     * Wait until the specified object is in the signaled state or the time-out interval elapses.
     */
    public function WaitForSingleObject(mixed $hHandle, int $dwMilliseconds): int;
    /**
     * Create a new process and its primary thread (UTF-16).
     */
    public function CreateProcessW(mixed $lpApplicationName, mixed $lpCommandLine, mixed $lpProcessAttributes, mixed $lpThreadAttributes, mixed $bInheritHandles, int $dwCreationFlags, mixed $lpEnvironment, mixed $lpCurrentDirectory, mixed $lpStartupInfo, mixed $lpProcessInformation): bool;
    /**
     * Retrieve information about the first process encountered in a system snapshot (UTF-16).
     */
    public function Process32FirstW(mixed $hSnapshot, mixed $lppe): bool;
    /**
     * Retrieve information about the next process recorded in a system snapshot (UTF-16).
     */
    public function Process32NextW(mixed $hSnapshot, mixed $lppe): bool;
    /**
     * Enumerate the child windows that belong to the specified parent window.
     */
    public function EnumChildWindows(mixed $hWndParent, mixed $lpEnumFunc, mixed $lParam): bool;
    /**
     * Remove an event hook function created by SetWinEventHook.
     */
    public function UnhookWinEvent(mixed $hWinEventHook): bool;
    /**
     * Retrieve the name of the class to which the specified window belongs (UTF-16).
     */
    public function GetClassNameW(mixed $hWnd, mixed $lpString, int $nMaxCount): int;
    /**
     * Get the thread identifier of the calling thread.
     */
    public function GetCurrentThreadId(): int;
    /**
     * Install an application-defined hook procedure into a hook chain (UTF-16).
     */
    public function SetWindowsHookExW(int $idHook, callable $lpfn, mixed $hMod, int $dwThreadId): mixed;
    /**
     * Pass hook information to the next hook procedure in the current hook chain.
     */
    public function CallNextHookEx(mixed $hhk, int $nCode, mixed $wParam, mixed $lParam): int;
    /**
     * Remove a hook procedure installed in a hook chain by SetWindowsHookEx.
     */
    public function UnhookWindowsHookEx(mixed $hhk): bool;
    /**
     * Post a message to the message queue of the specified thread (UTF-16).
     */
    public function PostThreadMessageW(int $idThread, int $Msg, mixed $wParam, mixed $lParam): bool;
    /**
     * Retrieve the number of milliseconds that have elapsed since the system was started.
     */
    public function GetTickCount($v = null): int;
    /**
     * Retrieve the physical monitors associated with an HMONITOR.
     */
    public function GetPhysicalMonitorsFromHMONITOR(mixed $hMonitor, int $dwPhysicalMonitorArraySize, mixed $pPhysicalMonitorArray): bool;
    /**
     * Set the brightness of a monitor supporting DDC/CI.
     */
    public function SetMonitorBrightness(mixed $hMonitor, int $dwNewBrightness): bool;
    /**
     * Retrieve the monitor's minimum, maximum, and current brightness settings.
     */
    public function GetMonitorBrightness(mixed $hMonitor, mixed $pdwMinimumBrightness, mixed $pdwCurrentBrightness, mixed $pdwMaximumBrightness): bool;
    /**
     * Close the handles to a set of physical monitors.
     */
    public function DestroyPhysicalMonitors(int $dwPhysicalMonitorArraySize, mixed $pPhysicalMonitorArray): bool;
    /**
     * Retrieve the value of an environment variable for the current process (ANSI).
     */
    public function GetEnvironmentVariableA(mixed $lpName, mixed $lpBuffer, int $nSize): int;
    /**
     * Set the value of an environment variable for the current process (ANSI).
     */
    public function SetEnvironmentVariableA(mixed $lpName, mixed $lpValue): bool;
    /**
     * Retrieve the path of the Windows directory (ANSI).
     */
    public function GetWindowsDirectoryA(mixed $lpBuffer, int $uSize): int;
    /**
     * Retrieve the path of the system directory (ANSI).
     */
    public function GetSystemDirectoryA(mixed $lpBuffer, int $uSize): int;
    /**
     * Retrieve the path of the directory designated for temporary files (ANSI).
     */
    public function GetTempPathA(int $nBufferLength, mixed $lpBuffer): int;
    /**
     * Retrieve the current directory for the calling process (ANSI).
     */
    public function GetCurrentDirectoryA(int $nBufferLength, mixed $lpBuffer): int;
    /**
     * Change the current directory for the calling process (ANSI).
     */
    public function SetCurrentDirectoryA(mixed $lpPathName): bool;
    /**
     * Set the title for the current console window (ANSI).
     */
    public function SetConsoleTitleA(mixed $lpConsoleTitle): bool;
    /**
     * Retrieve information about the current system (processor, page size, etc.).
     */
    public function GetSystemInfo(mixed $lpSystemInfo): void;
    /**
     * Obtain information about the system's current use of both physical and virtual memory.
     */
    public function GlobalMemoryStatusEx(mixed $lpBuffer): bool;
    public function Sleep(mixed $dwMilliseconds): void;
    public function OpenClipboard(mixed $hWndNewOwner): bool;
    /**
     * Retrieve data from the clipboard in a specified format.
     */
    public function GetClipboardData(int $uFormat): mixed;
    /**
     * Close the clipboard after it has been used.
     */
    public function CloseClipboard(): bool;
    /**
     * Lock a global memory object and return a pointer to the first byte.
     */
    public function GlobalLock(mixed $hMem): mixed;
    /**
     * Decrement the lock count associated with a global memory object.
     */
    public function GlobalUnlock(mixed $hMem): bool;
    /**
     * Allocate the specified number of bytes from the global heap.
     */
    public function GlobalAlloc(int $uFlags, int $dwBytes): mixed;
    /**
     * Free the specified global memory object.
     */
    public function GlobalFree(mixed $hMem): mixed;
    /**
     * Place data on the clipboard in a specified clipboard format.
     */
    public function SetClipboardData(int $uFormat, mixed $hMem): mixed;
    /**
     * Expand environment-variable strings and replace them with the defined values (ANSI).
     */
    public function ExpandEnvironmentStringsA(mixed $lpSrc, mixed $lpDst, int $nSize): int;
    /**
     * Determine whether a disk drive is removable, fixed, CD-ROM, RAM, or network (ANSI).
     */
    public function GetDriveTypeA(mixed $lpRootPathName): int;
    /**
     * Retrieve the current local date and time.
     */
    public function GetLocalTime(mixed $lpSystemTime): void;
    /**
     * Retrieve the current system date and time in UTC.
     */
    public function GetSystemTime(mixed $lpSystemTime): void;
    /**
     * Retrieve a handle to a device context (DC) for the client area of the specified window.
     */
    public function GetDC(mixed $hWnd): mixed;
    public function GetPixel(CData $hdc, mixed $x, mixed $y): mixed;
    public function LoadLibraryA(mixed $lpLibFileName): mixed;
    public function FreeLibrary(mixed $hLibModule): int;
    /**
     * Retrieve the current double-click time for the mouse (milliseconds).
     */
    public function GetDoubleClickTime(): int;
    /**
     * Set the double-click time for the mouse (milliseconds).
     */
    public function SetDoubleClickTime(int $uInterval): bool;
    /**
     * Set the keyboard focus to the specified window.
     */
    public function SetFocus(mixed $hWnd): mixed;
    /**
     * Move the cursor to the specified screen coordinates.
     */
    public function SetCursorPos(int $X, int $Y): bool;
    /**
     * Display or hide the cursor; returns the new display count.
     */
    public function ShowCursor(mixed $bShow): int;
    /**
     * Determine the visibility state of the specified window.
     */
    public function IsWindowVisible(mixed $hWnd): bool;
    /**
     * Determine whether the specified window is enabled for mouse and keyboard input.
     */
    public function IsWindowEnabled(mixed $hWnd): bool;
    /**
     * Enable or disable mouse and keyboard input to the specified window.
     */
    public function EnableWindow(mixed $hWnd, bool|int $bEnable): bool;
    public function EmptyClipboard(): bool;
    public function GetConsoleScreenBufferInfo(mixed $hConsoleOutput, mixed $lpConsoleScreenBufferInfo): bool;

    // -----------------------------------------------------------------
    // User32 — windowing, input, dialogs, clipboard, cursor/icon APIs.
    // -----------------------------------------------------------------

    /** Display a modal dialog box containing a system icon, a set of buttons, and a short message (UTF-16). */
    public function MessageBoxW(mixed $hWnd, mixed $lpText, mixed $lpCaption, int $uType): int;
    /** Call the default window procedure to provide default processing for messages (ANSI). */
    public function DefWindowProcA(mixed $hWnd, int $uMsg, mixed $wParam, mixed $lParam): int;
    /** Dispatch incoming sent messages, check the thread's message queue for posted messages, and retrieve the message. */
    public function PeekMessageW(mixed $lpMsg, mixed $hWnd, int $wMsgFilterMin, int $wMsgFilterMax, int $wRemoveMsg): bool;
    /** Wait on a set of handles or until an input event is available. */
    public function MsgWaitForMultipleObjects(int $nCount, mixed $pHandles, mixed $fWaitAll, int $dwMilliseconds, mixed $dwWakeMask): int;
    /** Determine whether the specified window is minimized (iconic). */
    public function IsIconic(mixed $hWnd): bool;
    /** Determine whether the specified window is maximized (zoomed). */
    public function IsZoomed(mixed $hWnd): bool;
    /** Retrieve the time of the last input event. */
    public function GetLastInputInfo(mixed $plii): bool;
    /** Retrieve a handle to a control in the specified dialog box. */
    public function GetDlgItem(mixed $hDlg, int $nIDDlgItem): mixed;
    /** Retrieve the title or text associated with a control in a dialog box (UTF-16). */
    public function GetDlgItemTextW(mixed $hDlg, int $nIDDlgItem, mixed $lpString, int $cchMax): int;
    /** Destroy a modal dialog box, causing the system to end any processing for the dialog box. */
    public function EndDialog(mixed $hDlg, int $nResult): bool;
    /** Create a modal dialog box from a dialog box template in memory (UTF-16). */
    public function DialogBoxIndirectParamW(mixed $hInstance, mixed $hDialogTemplate, mixed $hWndParent, callable $lpDialogFunc, mixed $dwInitParam): int;
    /** Retrieve the status of the specified virtual key (pressed/toggled). */
    public function GetKeyState(int $nVirtKey): int;
    /** Convert the screen coordinates of a point on the screen to client-area coordinates. */
    public function ScreenToClient(mixed $hWnd, mixed $lpPoint): bool;
    /** Convert the client-area coordinates of a specified point to screen coordinates. */
    public function ClientToScreen(mixed $hWnd, mixed $lpPoint): bool;
    /** Retrieve a handle to the specified window's parent or owner. */
    public function GetParent(mixed $hWnd): mixed;
    /** Retrieve information about the specified window (32-bit value, ANSI). */
    public function GetWindowLongA(mixed $hWnd, int $nIndex): int;
    /** Define a system-wide hot key. */
    public function RegisterHotKey(mixed $hWnd, int $id, int $fsModifiers, int $vk): bool;
    /** Free a hot key previously registered by the calling thread. */
    public function UnregisterHotKey(mixed $hWnd, int $id): bool;
    /** Retrieve the number of different data formats currently on the clipboard. */
    public function CountClipboardFormats(): int;
    /** Determine whether the clipboard contains data in the specified format. */
    public function IsClipboardFormatAvailable(int $format): bool;
    /** Register a new clipboard format, which can then be used as a valid clipboard format (ANSI). */
    public function RegisterClipboardFormatA(mixed $lpszFormat): int;
    /** Destroy a cursor and frees any memory the cursor occupied. */
    public function DestroyCursor(mixed $hCursor): bool;
    /** Establish the cursor shape. */
    public function SetCursor(mixed $hCursor): mixed;
    /** Draw an icon or cursor into the specified device context performing the specified raster operations. */
    public function DrawIconEx(mixed $hdc, int $xLeft, int $yTop, mixed $hIcon, int $cxWidth, int $cyWidth, int $istepIfAniCur, mixed $hbrFlickerFreeDraw, int $diFlags): bool;
    /** Destroy an icon and free any memory the icon occupied. */
    public function DestroyIcon(mixed $hIcon): bool;

    // -----------------------------------------------------------------
    // Gdi32 — device contexts, fonts, pens, brushes and 2D drawing.
    // -----------------------------------------------------------------

    /** Create a logical font with the specified characteristics (UTF-16). */
    public function CreateFontW(int $nHeight, int $nWidth, int $nEscapement, int $nOrientation, int $fnWeight, int $fdwItalic, int $fdwUnderline, int $fdwStrikeOut, int $fdwCharSet, int $fdwOutputPrecision, int $fdwClipPrecision, int $fdwQuality, int $fdwPitchAndFamily, mixed $lpszFace): mixed;
    /** Create a logical font with the specified characteristics (ANSI). */
    public function CreateFontA(int $nHeight, int $nWidth, int $nEscapement, int $nOrientation, int $fnWeight, int $fdwItalic, int $fdwUnderline, int $fdwStrikeOut, int $fdwCharSet, int $fdwOutputPrecision, int $fdwClipPrecision, int $fdwQuality, int $fdwPitchAndFamily, mixed $lpszFace): mixed;
    /** Compute the width and height of the specified string of text using the currently selected font (UTF-16). */
    public function GetTextExtentPoint32W(mixed $hdc, mixed $lpString, int $c, mixed $lpSize): bool;
    /** Compute the width and height of the specified string of text using the currently selected font (ANSI). */
    public function GetTextExtentPoint32A(mixed $hdc, mixed $lpString, int $c, mixed $lpSize): bool;
    /** Retrieve metrics for the currently selected font in the specified device context (UTF-16). */
    public function GetTextMetricsW(mixed $hdc, mixed $lptm): bool;
    /** Create a logical pen with the specified style, width, and color. */
    public function CreatePen(int $iStyle, int $cWidth, int $color): mixed;
    /** Create a logical brush with the specified solid color. */
    public function CreateSolidBrush(int $color): mixed;
    /** Set the text color for the specified device context. */
    public function SetTextColor(mixed $hdc, mixed $color): mixed;
    /** Set the current background color for the specified device context. */
    public function SetBkColor(mixed $hdc, mixed $color): mixed;
    /** Set the background mix mode of the specified device context (transparent/opaque). */
    public function SetBkMode(mixed $hdc, int $mode): int;
    /** Draw a rectangle using the current pen and fill it using the current brush. */
    public function Rectangle(mixed $hdc, int $left, int $top, int $right, int $bottom): bool;
    /** Draw an ellipse using the current pen and fill it using the current brush. */
    public function Ellipse(mixed $hdc, int $left, int $top, int $right, int $bottom): bool;
    /** Draw a line from the current position up to, but not including, the specified point. */
    public function LineTo(mixed $hdc, int $x, int $y): bool;
    /** Update the current position to the specified point and optionally returns the previous position. */
    public function MoveToEx(mixed $hdc, int $x, int $y, mixed $lppt): bool;
    /** Write a character string at the specified location using the currently selected font (ANSI). */
    public function TextOutA(mixed $hdc, int $x, int $y, mixed $lpString, int $c): bool;
    /** Draw formatted text in the specified rectangle (ANSI). */
    public function DrawTextA(mixed $hdc, mixed $lpchText, int $cchText, mixed $lprc, int $format): int;
    /** Draw an elliptical arc. */
    public function Arc(mixed $hdc, int $x1, int $y1, int $x2, int $y2, int $x3, int $y3, int $x4, int $y4): bool;
    /** Draw a rectangle with rounded corners, outlined with the current pen and filled with the current brush. */
    public function RoundRect(mixed $hdc, int $left, int $top, int $right, int $bottom, int $width, int $height): bool;

    // -----------------------------------------------------------------
    // Kernel32 — processes, threads, files, memory, pipes, console, IPC.
    // -----------------------------------------------------------------

    /** Retrieve the current size of the specified global memory object. */
    public function GlobalSize(mixed $hMem): int;
    /** Create an anonymous pipe and return handles to the read and write ends. */
    public function CreatePipe(mixed $hReadPipe, mixed $hWritePipe, mixed $lpPipeAttributes, int $nSize): bool;
    /** Retrieve the termination status of the specified process. */
    public function GetExitCodeProcess(mixed $hProcess, mixed $lpExitCode): bool;
    /** Retrieve the current value of the performance counter. */
    public function QueryPerformanceCounter(mixed $lpPerformanceCount): bool;
    /** Retrieve the frequency of the performance counter. */
    public function QueryPerformanceFrequency(mixed $lpFrequency): bool;
    /** Retrieve a NetBIOS or DNS name associated with the local computer (ANSI). */
    public function GetComputerNameExA(int $NameType, mixed $lpBuffer, mixed $nSize): bool;
    /** Convert the specified path to its long form (ANSI). */
    public function GetLongPathNameA(mixed $lpszShortPath, mixed $lpszLongPath, int $cchBuffer): int;
    /** Retrieve the short path form of the specified input path (ANSI). */
    public function GetShortPathNameA(mixed $lpszLongPath, mixed $lpszShortPath, int $cchBuffer): int;
    /** Retrieve a pseudo handle for the current process. */
    public function GetCurrentProcess(): mixed;
    /** Determine whether the specified process is running under WOW64. */
    public function IsWow64Process(mixed $hProcess, mixed $Wow64Process): bool;
    /** Retrieve the path of the directory designated for temporary files (UTF-16). */
    public function GetTempPathW(int $nBufferLength, mixed $lpBuffer): int;
    /** Create a name for a temporary file (ANSI). */
    public function GetTempFileNameA(mixed $lpPathName, mixed $lpPrefixString, int $uUnique, mixed $lpTempFileName): int;
    /** Retrieve the fully qualified path for the file that contains the specified module (ANSI). */
    public function GetModuleFileNameA(mixed $hModule, mixed $lpFilename, int $nSize): int;
    /** Cause the calling thread to yield execution to another thread that is ready to run. */
    public function SwitchToThread(): bool;
    /** Retrieve the number of milliseconds elapsed since the system was started as a 64-bit value. */
    public function GetTickCount64(): int;
    /** Compare two FILETIME structures and return a signed value indicating their relative order. */
    public function CompareFileTime(mixed $lpFileTime1, mixed $lpFileTime2): int;
    /** Retrieve the current system date and time as a FILETIME in UTC. */
    public function GetSystemTimeAsFileTime(mixed $lpSystemTimeAsFileTime): void;
    /** Convert a file time based on UTC to a local file time. */
    public function FileTimeToLocalFileTime(mixed $lpFileTime, mixed $lpLocalFileTime): bool;
    /** Convert a file time to a system time structure. */
    public function FileTimeToSystemTime(mixed $lpFileTime, mixed $lpSystemTime): bool;
    /** Convert a system time to a file time. */
    public function SystemTimeToFileTime(mixed $lpSystemTime, mixed $lpFileTime): bool;
    /** Retrieve file system attributes for a specified file or directory (ANSI). */
    public function GetFileAttributesA(mixed $lpFileName): int;
    /** Set the attributes for a file or directory (ANSI). */
    public function SetFileAttributesA(mixed $lpFileName, int $dwFileAttributes): bool;
    /** Retrieve the date and time that a file or directory was created, last accessed, and last modified. */
    public function GetFileTime(mixed $hFile, mixed $lpCreationTime, mixed $lpLastAccessTime, mixed $lpLastWriteTime): bool;
    /** Flush the buffers of a specified file and causes all buffered data to be written to a file. */
    public function FlushFileBuffers(mixed $hFile): bool;
    /** Lock the specified file for exclusive access by the calling process. */
    public function LockFile(mixed $hFile, int $dwFileOffsetLow, int $dwFileOffsetHigh, int $nNumberOfBytesToLockLow, int $nNumberOfBytesToLockHigh): bool;
    /** Unlock a region in an open file that was previously locked by LockFile. */
    public function UnlockFile(mixed $hFile, int $dwFileOffsetLow, int $dwFileOffsetHigh, int $nNumberOfBytesToUnlockLow, int $nNumberOfBytesToUnlockHigh): bool;
    /** Set the physical file size for the specified file to the current position of the file pointer. */
    public function SetEndOfFile(mixed $hFile): bool;
    /** Create an instance of a named pipe and return a handle for subsequent pipe operations (ANSI). */
    public function CreateNamedPipeA(mixed $lpName, int $dwOpenMode, int $dwPipeMode, int $nMaxInstances, int $nOutBufferSize, int $nInBufferSize, int $nDefaultTimeOut, mixed $lpSecurityAttributes): mixed;
    /** Enable a named pipe server process to wait for a client process to connect to the pipe. */
    public function ConnectNamedPipe(mixed $hNamedPipe, mixed $lpOverlapped): bool;
    /** Disconnect the server end of a named pipe instance from a client process. */
    public function DisconnectNamedPipe(mixed $hNamedPipe): bool;
    /** Wait until either a time-out interval elapses or an instance of the specified named pipe is available (ANSI). */
    public function WaitNamedPipeA(mixed $lpNamedPipeName, int $nTimeOut): bool;
    /** Create or open a named or unnamed file mapping object for a specified file (ANSI). */
    public function CreateFileMappingA(mixed $hFile, mixed $lpFileMappingAttributes, int $flProtect, int $dwMaximumSizeHigh, int $dwMaximumSizeLow, mixed $lpName): mixed;
    /** Map a view of a file mapping into the address space of a calling process. */
    public function MapViewOfFile(mixed $hFileMappingObject, int $dwDesiredAccess, int $dwFileOffsetHigh, int $dwFileOffsetLow, int $dwNumberOfBytesToMap): mixed;
    /** Unmap a mapped view of a file from the calling process's address space. */
    public function UnmapViewOfFile(mixed $lpBaseAddress): bool;
    /** Write to the disk a byte range within a mapped view of a file. */
    public function FlushViewOfFile(mixed $lpBaseAddress, int $dwNumberOfBytesToFlush): bool;
    /** Reserve, commit, or change the state of a region of pages in the virtual address space of the calling process. */
    public function VirtualAlloc(mixed $lpAddress, int $dwSize, int $flAllocationType, int $flProtect): mixed;
    /** Release, decommit, or release and decommit a region of pages within the virtual address space of the calling process. */
    public function VirtualFree(mixed $lpAddress, int $dwSize, int $dwFreeType): bool;
    /** Change the protection on a region of committed pages in the virtual address space of the calling process. */
    public function VirtualProtect(mixed $lpAddress, int $dwSize, int $flNewProtect, mixed $lpflOldProtect): bool;
    /** Retrieve a handle to the default heap of the calling process. */
    public function GetProcessHeap(): mixed;
    /** Allocate a block of memory from a heap. */
    public function HeapAlloc(mixed $hHeap, int $dwFlags, int $dwBytes): mixed;
    /** Free a memory block allocated from a heap by HeapAlloc or HeapReAlloc. */
    public function HeapFree(mixed $hHeap, int $dwFlags, mixed $lpMem): bool;
    /** Wait until one or all of the specified objects are in the signaled state or the time-out interval elapses. */
    public function WaitForMultipleObjects(int $nCount, mixed $lpHandles, mixed $bWaitAll, int $dwMilliseconds): int;
    /** Create or open a named or unnamed mutex object (ANSI). */
    public function CreateMutexA(mixed $lpMutexAttributes, mixed $bInitialOwner, mixed $lpName): mixed;
    /** Release ownership of the specified mutex object. */
    public function ReleaseMutex(mixed $hMutex): bool;
    /** Create or open a named or unnamed event object (ANSI). */
    public function CreateEventA(mixed $lpEventAttributes, mixed $bManualReset, mixed $bInitialState, mixed $lpName): mixed;
    /** Set the specified event object to the signaled state. */
    public function SetEvent(mixed $hEvent): bool;
    /** Set the specified event object to the nonsignaled state. */
    public function ResetEvent(mixed $hEvent): bool;
    /** Create or open a named or unnamed semaphore object (ANSI). */
    public function CreateSemaphoreA(mixed $lpSemaphoreAttributes, int $lInitialCount, int $lMaximumCount, mixed $lpName): mixed;
    /** Increase the count of the specified semaphore object by a specified amount. */
    public function ReleaseSemaphore(mixed $hSemaphore, int $lReleaseCount, mixed $lpPreviousCount): bool;
    /** Suspend the specified thread. */
    public function SuspendThread(mixed $hThread): int;
    /** Decrement a thread's suspend count. When the suspend count is zero the thread is resumed. */
    public function ResumeThread(mixed $hThread): int;
    /** Terminate the specified thread. */
    public function TerminateThread(mixed $hThread, int $dwExitCode): bool;
    /** Retrieve the termination status of the specified thread. */
    public function GetExitCodeThread(mixed $hThread, mixed $lpExitCode): bool;
    /** Reserve, commit, or change the state of a region of memory within the virtual address space of a specified process. */
    public function VirtualAllocEx(mixed $hProcess, mixed $lpAddress, int $dwSize, int $flAllocationType, int $flProtect): mixed;
    /** Release, decommit, or release and decommit a region of memory within the virtual address space of a specified process. */
    public function VirtualFreeEx(mixed $hProcess, mixed $lpAddress, int $dwSize, int $dwFreeType): bool;
    /** Retrieve information about a range of pages within the virtual address space of a specified process. */
    public function VirtualQueryEx(mixed $hProcess, mixed $lpAddress, mixed $lpBuffer, int $dwLength): int;
    /** Write data to an area of memory in a specified process. */
    public function WriteProcessMemory(mixed $hProcess, mixed $lpBaseAddress, mixed $lpBuffer, int $nSize, mixed $lpNumberOfBytesWritten): bool;
    /** Read data from an area of memory in a specified process. */
    public function ReadProcessMemory(mixed $hProcess, mixed $lpBaseAddress, mixed $lpBuffer, int $nSize, mixed $lpNumberOfBytesRead): bool;
    /** Retrieve the address of an exported function or variable from the specified DLL. */
    public function GetProcAddress(mixed $hModule, mixed $lpProcName): mixed;
    /** Create a thread that runs in the virtual address space of another process. */
    public function CreateRemoteThread(mixed $hProcess, mixed $lpThreadAttributes, int $dwStackSize, mixed $lpStartAddress, mixed $lpParameter, int $dwCreationFlags, mixed $lpThreadId): mixed;
    /** Retrieve information about the first module associated with a process. */
    public function Module32First(mixed $hSnapshot, mixed $lpme): bool;
    /** Retrieve information about the next module associated with a process or thread. */
    public function Module32Next(mixed $hSnapshot, mixed $lpme): bool;
    /** Set the priority class for the specified process. */
    public function SetPriorityClass(mixed $hProcess, int $dwPriorityClass): bool;
    /** Retrieve the priority class for the specified process. */
    public function GetPriorityClass(mixed $hProcess): int;
    /** Set a processor affinity mask for the threads of the specified process. */
    public function SetProcessAffinityMask(mixed $hProcess, int $dwProcessAffinityMask): bool;
    /** Retrieve the process affinity mask for the specified process and the system affinity mask for the system. */
    public function GetProcessAffinityMask(mixed $hProcess, mixed $lpProcessAffinityMask, mixed $lpSystemAffinityMask): bool;
    /** Retrieve timing information for the specified process. */
    public function GetProcessTimes(mixed $hProcess, mixed $lpCreationTime, mixed $lpExitTime, mixed $lpKernelTime, mixed $lpUserTime): bool;
    /** Set the priority value for the specified thread. */
    public function SetThreadPriority(mixed $hThread, int $nPriority): bool;
    /** Retrieve the priority value for the specified thread. */
    public function GetThreadPriority(mixed $hThread): int;
    /** Set the attributes of characters written to the console screen buffer. */
    public function SetConsoleTextAttribute(mixed $hConsoleOutput, int $wAttributes): bool;
    /** Change the size of the specified console screen buffer. */
    public function SetConsoleScreenBufferSize(mixed $hConsoleOutput, mixed $dwSize): bool;
    /** Allocate a new console for the calling process. */
    public function AllocConsole(): bool;
    /** Detach the calling process from its console. */
    public function FreeConsole(): bool;
    /** Write a character string to a console screen buffer beginning at the current cursor location (ANSI). */
    public function WriteConsoleA(mixed $hConsoleOutput, mixed $lpBuffer, int $nNumberOfCharsToWrite, mixed $lpNumberOfCharsWritten, mixed $lpReserved): bool;
    /** Write a character to the console screen buffer a specified number of times (ANSI). */
    public function FillConsoleOutputCharacterA(mixed $hConsoleOutput, mixed $cCharacter, int $nLength, mixed $dwWriteCoord, mixed $lpNumberOfCharsWritten): bool;
    /** Retrieve the title for the current console window (ANSI). */
    public function GetConsoleTitleA(mixed $lpConsoleTitle, int $nSize): int;

    // -----------------------------------------------------------------
    // Ntdll — low-level undocumented / kernel-facing APIs.
    // -----------------------------------------------------------------

    /** Suspend all threads of a specified process (undocumented NT API). */
    public function NtSuspendProcess(mixed $ProcessHandle): int;
    /** Resume all threads of a specified process (undocumented NT API). */
    public function NtResumeProcess(mixed $ProcessHandle): int;

    // -----------------------------------------------------------------
    // Shell32 / Shlwapi — shell operations, file dialogs, namespace APIs.
    // -----------------------------------------------------------------

    /** Perform an operation on a specified file or URL (ANSI). */
    public function ShellExecuteA(mixed $hwnd, mixed $lpOperation, mixed $lpFile, mixed $lpParameters, mixed $lpDirectory, int $nShowCmd): mixed;
    /** Empty the Recycle Bin on the specified drive (ANSI). */
    public function SHEmptyRecycleBinA(mixed $hwnd, mixed $pszRootPath, int $dwFlags): int;
    /** Display a dialog box that enables the user to select a Shell folder (ANSI). */
    public function SHBrowseForFolderA(mixed $lpbi): mixed;
    /** Convert an item identifier list to a file system path (ANSI). */
    public function SHGetPathFromIDListA(mixed $pidl, mixed $pszPath): bool;
    /** Free a block of memory allocated by an OLE / COM task memory allocator. */
    public function CoTaskMemFree(mixed $pv): void;

    // -----------------------------------------------------------------
    // Advapi32 — services, registry, security, privileges, cryptography.
    // -----------------------------------------------------------------

    /** Establish a connection to the service control manager on the specified computer (ANSI). */
    public function OpenSCManagerA(mixed $lpMachineName, mixed $lpDatabaseName, int $dwDesiredAccess): mixed;
    /** Open an existing service (ANSI). */
    public function OpenServiceA(mixed $hSCManager, mixed $lpServiceName, int $dwDesiredAccess): mixed;
    /** Close a handle to a service control manager or service object. */
    public function CloseServiceHandle(mixed $hSCObject): bool;
    /** Start a service (ANSI). */
    public function StartServiceA(mixed $hService, int $dwNumServiceArgs, mixed $lpServiceArgVectors): bool;
    /** Send a control code to a service. */
    public function ControlService(mixed $hService, int $dwControl, mixed $lpServiceStatus): bool;
    /** Retrieve the current status of the specified service. */
    public function QueryServiceStatus(mixed $hService, mixed $lpServiceStatus): bool;
    /** Enumerate the values for the specified open registry key (UTF-16). */
    public function RegEnumValueW(mixed $hKey, int $dwIndex, mixed $lpValueName, mixed $lpcchValueName, mixed $lpReserved, mixed $lpType, mixed $lpData, mixed $lpcbData): int;
    /** Enumerate the values for the specified open registry key (ANSI). */
    public function RegEnumValueA(mixed $hKey, int $dwIndex, mixed $lpValueName, mixed $lpcchValueName, mixed $lpReserved, mixed $lpType, mixed $lpData, mixed $lpcbData): int;
    /** Create the specified registry key; if the key already exists, the function opens it (ANSI). */
    public function RegCreateKeyExA(mixed $hKey, mixed $lpSubKey, int $Reserved, mixed $lpClass, int $dwOptions, int $samDesired, mixed $lpSecurityAttributes, mixed $phkResult, mixed $lpdwDisposition): int;
    /** Set the data and type of a specified value under a registry key (ANSI). */
    public function RegSetValueExA(mixed $hKey, mixed $lpValueName, int $Reserved, int $dwType, mixed $lpData, int $cbData): int;
    /** Remove a named value from the specified registry key (ANSI). */
    public function RegDeleteValueA(mixed $hKey, mixed $lpValueName): int;
    /** Delete a subkey and its values (ANSI). */
    public function RegDeleteKeyA(mixed $hKey, mixed $lpSubKey): int;
    /** Open the access token associated with a process. */
    public function OpenProcessToken(mixed $ProcessHandle, int $DesiredAccess, mixed $TokenHandle): bool;
    /** Retrieve a specified type of information about an access token. */
    public function GetTokenInformation(mixed $TokenHandle, int $TokenInformationClass, mixed $TokenInformation, int $TokenInformationLength, mixed $ReturnLength): bool;
    /** Determine whether the current user has the administrator group in the token. */
    public function IsUserAnAdmin(): bool;
    /** Retrieve the locally unique identifier (LUID) used to locally represent the specified privilege name (ANSI). */
    public function LookupPrivilegeValueA(mixed $lpSystemName, mixed $lpName, mixed $lpLuid): bool;
    /** Enable or disable privileges in the specified access token. */
    public function AdjustTokenPrivileges(mixed $TokenHandle, mixed $DisableAllPrivileges, mixed $NewState, int $BufferLength, mixed $PreviousState, mixed $ReturnLength): bool;
    /** Acquire a handle to a particular key container within a particular cryptographic service provider (ANSI). */
    public function CryptAcquireContextA(mixed $phProv, mixed $szContainer, mixed $szProvider, int $dwProvType, int $dwFlags): bool;
    /** Release the handle of a cryptographic service provider (CSP) and a key container. */
    public function CryptReleaseContext(mixed $hProv, int $dwFlags): bool;
    /** Fill a buffer with cryptographically random bytes. */
    public function CryptGenRandom(mixed $hProv, int $dwLen, mixed $pbBuffer): bool;

    // -----------------------------------------------------------------
    // Power — power management and sleep/hibernate / execution state.
    // -----------------------------------------------------------------

    /** Suspend the system by either shutting power off (hibernate) or entering standby mode. */
    public function SetSuspendState(mixed $bHibernate, mixed $bForce, mixed $bWakeupEventsDisabled): bool;
    /** Inform the system that it is in use, preventing idle sleep or display timeout. */
    public function SetThreadExecutionState(int $esFlags): int;

    // -----------------------------------------------------------------
    // Winsock — sockets, byte-order conversion, error reporting.
    // -----------------------------------------------------------------

    /** Retrieve the standard host name for the local computer. */
    public function gethostname(mixed $name, int $namelen): int;
    /** Set a socket option. */
    public function setsockopt(mixed $s, int $level, int $optname, mixed $optval, int $optlen): int;
    /** Retrieve a socket option. */
    public function getsockopt(mixed $s, int $level, int $optname, mixed $optval, mixed $optlen): int;
    /** Retrieve the details of the last Windows Sockets error that occurred. */
    public function WSAGetLastError(): int;
    /** Disable sends or receives on a socket. */
    public function shutdown(mixed $s, int $how): int;
    /** Convert an unsigned long from host to TCP/IP network byte order (big-endian). */
    public function htonl(int $hostlong): int;
    /** Convert an unsigned long from TCP/IP network byte order to host byte order. */
    public function ntohl(int $netlong): int;
    /** Convert an unsigned short from TCP/IP network byte order to host byte order. */
    public function ntohs(int $netshort): int;

    // -----------------------------------------------------------------
    // Capture (avicap32) — video capture windows.
    // -----------------------------------------------------------------

    /** Create a capture window that can attach to a video capture driver (ANSI). */
    public function capCreateCaptureWindowA(mixed $lpszWindowName, int $dwStyle, int $x, int $y, int $nWidth, int $nHeight, mixed $hwndParent, int $nID): mixed;

    /** Real OS version via ntdll; returns STATUS_SUCCESS (0) on success. */
    public function RtlGetVersion(mixed $lpVersionInformation): int;

    /** System-wide idle/kernel/user times since boot. */
    public function GetSystemTimes(mixed $lpIdleTime, mixed $lpKernelTime, mixed $lpUserTime): bool;

    /** Firmware type: 1 BIOS, 2 UEFI. */
    public function GetFirmwareType(mixed $FirmwareType): bool;

    /** Windows edition (PRODUCT_* code) for the given version. */
    public function GetProductInfo(int $dwOSMajorVersion, int $dwOSMinorVersion, int $dwSpMajorVersion, int $dwSpMinorVersion, mixed $pdwReturnedProductType): bool;

    /** Volume label, serial, filesystem name and flags for a drive root. */
    public function GetVolumeInformationW(mixed $lpRootPathName, mixed $lpVolumeNameBuffer, int $nVolumeNameSize, mixed $lpVolumeSerialNumber, mixed $lpMaximumComponentLength, mixed $lpFileSystemFlags, mixed $lpFileSystemNameBuffer, int $nFileSystemNameSize): bool;

    /** Whether an executable is 32-bit, 64-bit or a legacy binary. */
    public function GetBinaryType(mixed $lpApplicationName, mixed $lpBinaryType): bool;
    /** Which subsystem an executable targets, wide path. */
    public function GetBinaryTypeW(mixed $lpApplicationName, mixed $lpBinaryType): bool;

    /** Canonical absolute path for a relative or partial path. */
    public function GetFullPathNameW(mixed $lpFileName, int $nBufferLength, mixed $lpBuffer, mixed $lpFilePart): int;

    /** On-disk size of a file in bytes (low DWORD returned, high via out-param). */
    public function GetCompressedFileSizeW(mixed $lpFileName, mixed $lpFileSizeHigh): int;

    /** DPI a specific window renders at; 96 is unscaled. */
    public function GetDpiForWindow(mixed $hWnd): int;

    /** System-wide DPI of the primary display. */
    public function GetDpiForSystem(): int;

    /** Monitor rectangles and flags. */
    public function GetMonitorInfoW(mixed $hMonitor, mixed $lpmi): bool;

    /** Next clipboard format in the enumeration, 0 when exhausted. */
    public function EnumClipboardFormats(int $format): int;

    /** Registered name of a clipboard format. */
    public function GetClipboardFormatNameW(int $format, mixed $lpszFormatName, int $cchMaxCount): int;

    /** Counter incremented whenever the clipboard contents change. */
    public function GetClipboardSequenceNumber(): int;

    /** Size and item count of the recycle bin. */
    public function SHQueryRecycleBinW(mixed $pszRootPath, mixed $pSHQueryRBInfo): int;

    /** Whether the desktop is in a state where a notification would intrude. */
    public function SHQueryUserNotificationState(mixed $pquns): int;

    /** Number of kernel handles a process holds. */
    public function GetProcessHandleCount(mixed $hProcess, mixed $pdwHandleCount): bool;

    /** Create a job object processes can be assigned to. */
    public function CreateJobObjectW(mixed $lpJobAttributes, mixed $lpName): mixed;

    /** Put a process under a job object's control. */
    public function AssignProcessToJobObject(mixed $hJob, mixed $hProcess): bool;

    /** Terminate every process in a job object. */
    public function TerminateJobObject(mixed $hJob, int $uExitCode): bool;

    /** Multimedia timer's millisecond clock since boot. */
    public function timeGetTime(): int;

    /** Request a finer system timer resolution. */
    public function timeBeginPeriod(int $uPeriod): int;

    /** Release a resolution requested by timeBeginPeriod. */
    public function timeEndPeriod(int $uPeriod): int;

    /** Send an MCI command string. */
    public function mciSendStringW(mixed $lpszCommand, mixed $lpszReturnString, int $cchReturn, mixed $hwndCallback): int;

    /** Enumerate services with their hosting process id. */
    public function EnumServicesStatusExW(mixed $hSCManager, int $InfoLevel, int $dwServiceType, int $dwServiceState, mixed $lpServices, int $cbBufSize, mixed $pcbBytesNeeded, mixed $lpServicesReturned, mixed $lpResumeHandle, mixed $pszGroupName): bool;

    /** Static configuration of a service. */
    public function QueryServiceConfigW(mixed $hService, mixed $lpServiceConfig, int $cbBufSize, mixed $pcbBytesNeeded): bool;

    /** Optional configuration blocks, such as the description. */
    public function QueryServiceConfig2W(mixed $hService, int $dwInfoLevel, mixed $lpBuffer, int $cbBufSize, mixed $pcbBytesNeeded): bool;

    /** Service status including its process id. */
    public function QueryServiceStatusEx(mixed $hService, int $InfoLevel, mixed $lpBuffer, int $cbBufSize, mixed $pcbBytesNeeded): bool;

    /** Change a service configuration in place. */
    public function ChangeServiceConfigW(mixed $hService, int $dwServiceType, int $dwStartType, int $dwErrorControl, mixed $lpBinaryPathName, mixed $lpLoadOrderGroup, mixed $lpdwTagId, mixed $lpDependencies, mixed $lpServiceStartName, mixed $lpPassword, mixed $lpDisplayName): bool;

    /** Mark a service for deletion. */
    public function DeleteService(mixed $hService): bool;

    /** Open one of the classic event logs. */
    public function OpenEventLogW(mixed $lpUNCServerName, mixed $lpSourceName): mixed;

    /** Close an event log handle. */
    public function CloseEventLog(mixed $hEventLog): bool;

    /** How many records an event log holds. */
    public function GetNumberOfEventLogRecords(mixed $hEventLog, mixed $NumberOfRecords): bool;

    /** Record number of the oldest entry still held. */
    public function GetOldestEventLogRecord(mixed $hEventLog, mixed $OldestRecord): bool;

    /** Read raw EVENTLOGRECORD entries. */
    public function ReadEventLogW(mixed $hEventLog, int $dwReadFlags, int $dwRecordOffset, mixed $lpBuffer, int $nNumberOfBytesToRead, mixed $pnBytesRead, mixed $pnMinNumberOfBytesNeeded): bool;

    /** Write a copy of an event log to a file. */
    public function BackupEventLogW(mixed $hEventLog, mixed $lpBackupFileName): bool;

    /** Empty an event log, optionally backing it up first. */
    public function ClearEventLogW(mixed $hEventLog, mixed $lpBackupFileName): bool;

    /** Read a serial port's line settings. */
    public function GetCommState(mixed $hFile, mixed $lpDCB): bool;

    /** Apply a serial port's line settings. */
    public function SetCommState(mixed $hFile, mixed $lpDCB): bool;

    /** Read a serial port's read and write timeouts. */
    public function GetCommTimeouts(mixed $hFile, mixed $lpCommTimeouts): bool;

    /** Set a serial port's read and write timeouts. */
    public function SetCommTimeouts(mixed $hFile, mixed $lpCommTimeouts): bool;

    /** Size the serial driver's send and receive queues. */
    public function SetupComm(mixed $hFile, int $dwInQueue, int $dwOutQueue): bool;

    /** Discard queued serial data or abort transfers in flight. */
    public function PurgeComm(mixed $hFile, int $dwFlags): bool;

    /** Queue depths and accumulated serial errors. */
    public function ClearCommError(mixed $hFile, mixed $lpErrors, mixed $lpStat): bool;

    /** State of the serial port's incoming control lines. */
    public function GetCommModemStatus(mixed $hFile, mixed $lpModemStat): bool;

    /** Drive one of the serial port's outgoing control lines. */
    public function EscapeCommFunction(mixed $hFile, int $dwFunc): bool;

    /** The machine's time zone, offsets and changeover dates. */
    public function GetTimeZoneInformation(mixed $lpTimeZoneInformation): int;

    /** Convert UTC to a specific zone's local time. */
    public function SystemTimeToTzSpecificLocalTime(mixed $lpTimeZoneInformation, mixed $lpUniversalTime, mixed $lpLocalTime): bool;

    /** The current user's locale as a BCP 47 name. */
    public function GetUserDefaultLocaleName(mixed $lpLocaleName, int $cchLocaleName): int;

    /** The machine's locale as a BCP 47 name. */
    public function GetSystemDefaultLocaleName(mixed $lpLocaleName, int $cchLocaleName): int;

    /** One field of a locale's configuration. */
    public function GetLocaleInfoEx(mixed $lpLocaleName, int $LCType, mixed $lpLCData, int $cchData): int;

    /** The country or region the user has selected. */
    public function GetUserGeoID(int $GeoClass): int;

    /** One field of a country or region, by GEOID. */
    public function GetGeoInfoW(int $Location, int $GeoType, mixed $lpGeoData, int $cchData, int $LangId): int;

    /** Every network adapter, chained through Next. */
    public function GetAdaptersInfo(mixed $AdapterInfo, mixed $SizePointer): int;

    /** How many interfaces the IP stack counts. */
    public function GetNumberOfInterfaces(mixed $pdwNumIf): int;

    /** The interface the stack would route an address through. */
    public function GetBestInterface(int $dwDestAddr, mixed $pdwBestIfIndex): int;

    /** TCP connections with their owning process. */
    public function GetExtendedTcpTable(mixed $pTcpTable, mixed $pdwSize, int $bOrder, int $ulAf, int $TableClass, int $Reserved): int;

    /** UDP endpoints with their owning process. */
    public function GetExtendedUdpTable(mixed $pUdpTable, mixed $pdwSize, int $bOrder, int $ulAf, int $TableClass, int $Reserved): int;

    /** The ARP cache. */
    public function GetIpNetTable(mixed $IpNetTable, mixed $SizePointer, int $Order): int;

    /** Ask the segment for the hardware address behind an IPv4 address. */
    public function SendARP(int $DestIP, int $SrcIP, mixed $pMacAddr, mixed $PhyAddrLen): int;

    /** Open a handle for sending ICMP echo requests. */
    public function IcmpCreateFile(): mixed;

    /** Close an ICMP handle. */
    public function IcmpCloseHandle(mixed $IcmpHandle): bool;

    /** Send one ICMP echo request and wait for the reply. */
    public function IcmpSendEcho(mixed $IcmpHandle, int $DestinationAddress, mixed $RequestData, int $RequestSize, mixed $RequestOptions, mixed $ReplyBuffer, int $ReplySize, int $Timeout): int;
}
