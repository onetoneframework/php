/* 
 * Windows API type definitions for PHP FFI
 * 
 * Note: PHP FFI does not support C preprocessor directives such as
 * #include, #if/#else/#endif, or non-integer #define macros.
 * All types must be declared directly without conditional compilation.
 */

#define FFI_LIB "C:\\Windows\\System32\\kernel32.dll"

/*struct HINSTANCE__;
typedef struct HINSTANCE__ *HINSTANCE;*/

typedef void* GpImage;
typedef void* HKEY;
typedef HKEY *PHKEY;
typedef void* HHOOK;
typedef void* HMIDIOUT;
typedef void* HANDLE;
typedef void* PSID;
typedef void* HWINEVENTHOOK;
typedef HANDLE HWND;
typedef HANDLE HMONITOR;
typedef HANDLE HMENU;
typedef HANDLE HGLOBAL;
typedef HGLOBAL HGLOBAL;
typedef HANDLE HWAVEOUT;
typedef HWAVEOUT* LPHWAVEOUT;
typedef HANDLE HINSTANCE;
typedef HANDLE HCURSOR;
typedef HANDLE SC_HANDLE;
typedef HANDLE SERVICE_STATUS_HANDLE;
typedef HANDLE WINSTA;
typedef HANDLE HSZ;
typedef HANDLE HACCEL;
typedef HANDLE HBRUSH;
typedef HANDLE HCOLORSPACE;
typedef HANDLE HCONV;
typedef HANDLE HCONVLIST;
typedef HANDLE HDDEDATA;
typedef HANDLE HDESK;
typedef HANDLE HDROP;
typedef HANDLE HDWP;
typedef HANDLE HENHMETAFILE;
typedef HANDLE HFONT;
typedef HANDLE HGDIOBJ;
typedef HANDLE HHOOK;
typedef HANDLE HKL;
typedef HANDLE HLOCAL;
typedef HANDLE HMENU;
typedef HANDLE HMETAFILE;
typedef HANDLE HPALETTE;
typedef HANDLE HPEN;
typedef HANDLE HRGN;
typedef HANDLE HRSRC;
typedef HANDLE *PHANDLE;
typedef HANDLE *LPHANDLE;
typedef void* HINTERNET;
typedef void* HBRUSH;
typedef void* HGDIOBJ;
typedef void* HMODULE;
/* Win32 VOID is void, not a pointer. As void* every "(VOID)" declaration
   below took a phantom argument, and every "VOID f(...)" returned one. */
typedef void VOID;
typedef void* LPVOID;
typedef LPVOID HWCT;
typedef void* PVOID;
typedef PVOID *PLSA_CLIENT_REQUEST;
typedef void* WNDPROC;
typedef void* HSTRING;
typedef void* IToastNotificationManager;
typedef void* IToastNotificationFactory;
typedef void* IToastNotification;
typedef void* IToastNotifier;
typedef void* IXmlDocument;
typedef void* HDEVINFO;
typedef void* GpBitmap;
typedef void* GpGraphics;
typedef void* REFIID;
typedef void* VARIANT_BOOL;
typedef void* REFPROPERTYKEY;
typedef const void* LPCVOID;

typedef bool* BOOL;
typedef BOOL *PBOOL;

typedef char* CHAR;
typedef CHAR *PCHAR;
typedef const char *LPCSTR;
typedef char *LPSTR;

typedef float FLOAT;
typedef FLOAT *PFLOAT;

typedef int LONG;
typedef LONG *PLONG;
typedef long *LPLONG;
typedef LONG NTSTATUS;
typedef int HDC;
typedef int HBITMAP;
typedef int INT_PTR;
typedef int SCODE;
typedef int *PINT, *LPINT;
typedef long KPRIORITY;
typedef long HPROPSHEETPAGE;
typedef long long LRESULT;

typedef long long ULONGLONG;
typedef ULONGLONG DWORDLONG, *PDWORDLONG;
typedef long long LONGLONG;
typedef long long LARGE_INTEGER;
typedef long long QWORD;

typedef signed char INT8;
typedef signed short INT16;
typedef signed int INT32;

typedef INT8 *PINT8;
typedef INT16 *PINT16;
typedef INT32 *PINT32;

typedef unsigned int INT;
typedef unsigned int WPARAM;
typedef unsigned int uint;
typedef unsigned int UINT;
typedef UINT *PUINT;
typedef unsigned int SOCKET;
typedef UINT MMRESULT;
typedef void* HICON;
typedef unsigned int DWORD32;
typedef DWORD32 *PDWORD32;

typedef unsigned long HKEY__;
typedef unsigned long SIZE_T;
typedef unsigned long HRESULT;
typedef unsigned long ULONG;
typedef unsigned long u_long;
typedef ULONG REGSAM;
typedef ULONG* PULONG;
typedef unsigned long DWORD_PTR;
typedef unsigned long ULONG_PTR;
typedef unsigned long ULONG_PTR, *PULONG_PTR;
typedef ULONG_PTR UINT_PTR;
typedef long long LPARAM;
typedef unsigned long WPARAM;
typedef unsigned long DWORD;
typedef DWORD* PDWORD;
typedef DWORD* LPDWORD;
typedef DWORD COLORREF;
typedef DWORD *LPCOLORREF;
typedef DWORD HMIXEROBJ;
typedef DWORD LCID, *PLCID;
typedef LONG  GEOID;
typedef DWORD GEOCLASS;
typedef DWORD GEOTYPE;
typedef DWORD LCTYPE;
typedef DWORD LGRPID;
typedef unsigned long GpStatus;
typedef unsigned long GdiplusStartupInput;
typedef unsigned long GdiplusStartupOutput;

typedef unsigned long long UINT64;
typedef unsigned long long ULARGE_INTEGER;
typedef ULARGE_INTEGER *PULARGE_INTEGER;
typedef unsigned long long *PULONGLONG;
typedef unsigned long long ULONG64;

typedef unsigned char BYTE;
typedef BYTE* PBYTE;
typedef BYTE BOOLEAN;
typedef BOOLEAN *PBOOLEAN;

typedef double DOUBLE;

typedef short SHORT;
typedef SHORT *PSHORT;

typedef unsigned short WORD;
typedef WORD LANGID;
typedef unsigned short USHORT;
typedef USHORT *PUSHORT;
typedef unsigned short WCHAR;
typedef WCHAR *PWCHAR;
typedef WCHAR* BSTR;
typedef WCHAR TCHAR;
typedef WCHAR *LPWSTR;
typedef LPWSTR PTSTR;
typedef LPWSTR LPTSTR;
typedef unsigned short ATOM;
typedef unsigned short u_short;
typedef unsigned short wchar_t;
typedef const wchar_t* LPCWSTR;
typedef wchar_t* PWSTR;
typedef wchar_t* LPCTSTR;
typedef const WCHAR* PCWSTR;
typedef unsigned short VARTYPE;

typedef const void* LPCDLGTEMPLATE;
typedef const char* LPCSTR;

typedef int *LPBOOL;
typedef unsigned char BYTE, *PBYTE, *LPBYTE;
typedef unsigned short WORD, *PWORD, *LPWORD;

typedef DWORD (*LPTHREAD_START_ROUTINE)(LPVOID);
typedef ULONG_PTR LSA_SEC_HANDLE, *PLSA_SEC_HANDLE;
typedef LPTHREAD_START_ROUTINE SEC_THREAD_START;

typedef struct _PROC_THREAD_ATTRIBUTE_LIST
*PPROC_THREAD_ATTRIBUTE_LIST, *LPPROC_THREAD_ATTRIBUTE_LIST;

/* va_list: PHP FFI does not support the 'unspecified' placeholder — use char* as a compatible stand-in */
typedef /* unspecified */ char* va_list;

typedef unsigned char UCHAR, *PUCHAR;

typedef struct {
    short X;
    short Y;
} COORD;

typedef enum _NORM_FORM {
    NormalizationOther = 0,
    NormalizationC = 0x1,  // Canonical decomposition followed by canonical composition
    NormalizationD = 0x2,  // Canonical decomposition
    NormalizationKC = 0x5, // Compatibility decomposition followed by canonical composition
    NormalizationKD = 0x6  // Compatibility decomposition
} NORM_FORM;

typedef struct {
    int x;
    int y;
    int cx;
    int cy;
} RECT;
            
typedef struct _GUID {
    unsigned long  Data1;  // 4 bytes
    unsigned short Data2;  // 2 bytes
    unsigned short Data3;  // 2 bytes
    unsigned char  Data4[8]; // 8 bytes
} GUID;
typedef const GUID* LPCGUID;

typedef struct _RASCONN {
    DWORD dwSize;
    HANDLE hRasConn;
    char szEntryName[256];
    char szDeviceType[256];
    char szDeviceName[256];
    char szPhoneNumber[256];
    char szCallbackNumber[256];
    DWORD dwError;
    HGLOBAL hReserved;
    DWORD dwRasError;
    HWND hwnd;
} RASCONN, *PRASCONN;

typedef struct {
    UINT        style;
    WNDPROC     lpfnWndProc;
    int         cbClsExtra;
    int         cbWndExtra;
    HINSTANCE   hInstance;
    void*       hIcon;
    void*       hCursor;
    void*       hbrBackground;
    const char* lpszMenuName;
    const char* lpszClassName;
    void*       hIconSm;
} WNDCLASSA;

typedef struct _FLASHWINFO {
    UINT cbSize;
    HWND hwnd;
    DWORD dwFlags;
    UINT uCount;
    DWORD dwTimeout;
} FLASHWINFO, *PFLASHWINFO;

typedef struct tagPAINTSTRUCT {
    HDC         hdc;
    BOOL        fErase;
    RECT        rcPaint;
    BOOL        fRestore;
    BOOL        fIncUpdate;
    BYTE        rgbReserved[32];
} PAINTSTRUCT, *PPAINTSTRUCT;

typedef struct tagBITMAPINFOHEADER {
    int biSize;
    int biWidth;
    int biHeight;
    short biPlanes;
    short biBitCount;
    int biCompression;
    int biSizeImage;
    int biXPelsPerMeter;
    int biYPelsPerMeter;
    int biClrUsed;
    int biClrImportant;
} BITMAPINFOHEADER;

typedef struct tagMONITORINFO {
    DWORD cbSize;      // Size of the structure, in bytes.
    RECT rcMonitor;    // The display monitor rectangle, in virtual-screen coordinates.
    RECT rcWork;       // The work area rectangle of the monitor, in virtual-screen coordinates.
    DWORD dwFlags;     // Flags that indicate the monitor's attributes.
} MONITORINFO, *LPMONITORINFO;

typedef struct tagKBDLLHOOKSTRUCT {
    DWORD vkCode;
    DWORD scanCode;
    DWORD flags;
    DWORD time;
    ULONG_PTR dwExtraInfo;
} KBDLLHOOKSTRUCT;

typedef struct tagBITMAPINFO {
    BITMAPINFOHEADER bmiHeader;
} BITMAPINFO;

typedef struct tagPOINT {
    LONG x;
    LONG y;
} POINT, *PPOINT, *LPPOINT;

typedef struct {
    unsigned char* data;
    int width;
    int height;
    int bytes_per_pixel; // Example: 3 for RGB, 4 for RGBA
} ImageBuffer;

/*typedef struct{
    HWND unnamedParam1;
    UINT unnamedParam2;
    WPARAM unnamedParam3;
    LPARAM unnamedParam4;
} DLGPROC;*/
 
typedef LRESULT (*DLGPROC)(void* hDlg, unsigned int uMsg, WPARAM wParam, LPARAM lParam);


typedef struct {
    DWORD cbSize;
    HWND  hWnd;
    UINT  uID;
    UINT  uFlags;
    UINT  uCallbackMessage;
    HICON hIcon;
    wchar_t szTip[128];
    DWORD dwState;
    DWORD dwStateMask;
    wchar_t szInfo[256];
    union {
        UINT uTimeout;
        UINT uVersion;
    } DUMMYUNIONNAME;
    wchar_t szInfoTitle[64];
    DWORD dwInfoFlags;
    GUID guidItem;
    HICON hBalloonIcon;
} NOTIFYICONDATAW;

typedef struct {
    HWND   hwnd;
    UINT   message;
    WPARAM wParam;
    LPARAM lParam;
    DWORD  time;
    int    pt_x;
    int    pt_y;
} MSG;
typedef MSG* LPMSG;

typedef struct _OVERLAPPED {
    ULONG_PTR Internal;
    ULONG_PTR InternalHigh;
    union {
        struct {
            DWORD Offset;
            DWORD OffsetHigh;
        };
        PVOID Pointer;
    };
    HANDLE hEvent;
} OVERLAPPED, *LPOVERLAPPED;

typedef struct {
    BYTE  ACLineStatus;
    BYTE  BatteryFlag;
    BYTE  BatteryLifePercent;
    BYTE  SystemStatusFlag;
    DWORD BatteryLifeTime;
    DWORD BatteryFullLifeTime;
} SYSTEM_POWER_STATUS;

typedef struct {
    int width;
    int height;
    unsigned char* data; // RGB data (e.g., width * height * 3 bytes)
} ImageData;

typedef struct tagWNDCLASSEXA {
  UINT      cbSize;
  UINT      style;
  WNDPROC   lpfnWndProc;
  int       cbClsExtra;
  int       cbWndExtra;
  HINSTANCE hInstance;
  HICON     hIcon;
  HCURSOR   hCursor;
  HBRUSH    hbrBackground;
  LPCSTR    lpszMenuName;
  LPCSTR    lpszClassName;
  HICON     hIconSm;
} WNDCLASSEXA, *PWNDCLASSEXA, *NPWNDCLASSEXA, *LPWNDCLASSEXA;

/*typedef struct tagWNDCLASSEXW {
  UINT      cbSize;
  UINT      style;
  WNDPROC   lpfnWndProc;
  int       cbClsExtra;
  int       cbWndExtra;
  HINSTANCE hInstance;
  HICON     hIcon;
  HCURSOR   hCursor;
  HBRUSH    hbrBackground;
  LPCWSTR   lpszMenuName;
  LPCWSTR   lpszClassName;
  HICON     hIconSm;
} WNDCLASSEXW, *PWNDCLASSEXW, *NPWNDCLASSEXW, *LPWNDCLASSEXW;*/

typedef LRESULT (*WNDPROC)(HWND, UINT, WPARAM, LPARAM);

typedef struct tagWNDCLASSEXW {
    unsigned int cbSize;
    unsigned int style;
    LRESULT (__stdcall *lpfnWndProc)(HWND, unsigned int, WPARAM, LPARAM);
    int cbClsExtra;
    int cbWndExtra;
    HINSTANCE hInstance;
    HICON hIcon;
    HCURSOR hCursor;
    HBRUSH hbrBackground;
    LPCWSTR lpszMenuName;
    LPCWSTR lpszClassName;
    HICON hIconSm;
} WNDCLASSEXW, *PWNDCLASSEXW;

typedef struct _PHYSICAL_MONITOR {
    HANDLE hPhysicalMonitor;
    DWORD  dwSize;
} PHYSICAL_MONITOR, *PPHYSICAL_MONITOR;

#define PHYSICAL_MONITOR_DESCRIPTION_SIZE 128

typedef struct _PHYSICAL_MONITOR_V2 {
    HANDLE hPhysicalMonitor;
    WCHAR  szPhysicalMonitorDescription[128];
} PHYSICAL_MONITOR_V2, *LPPHYSICAL_MONITOR;

typedef enum _MC_BRIGHTNESS_CURRENT_PARAMETER_ID {
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_INVALID,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_RED_GREEN_BLUE,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_RED,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_GREEN,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_BLUE,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_ALL,
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID_COUNT
} MC_BRIGHTNESS_CURRENT_PARAMETER_ID;

typedef struct _MC_BRIGHTNESS {
    MC_BRIGHTNESS_CURRENT_PARAMETER_ID ControlCode;
    DWORD CurrentValue;
    DWORD MinimumValue;
    DWORD MaximumValue;
} MC_BRIGHTNESS, *PMC_BRIGHTNESS;

typedef struct _SP_DEVINFO_DATA {
    DWORD    cbSize;
    GUID     ClassGuid;
    DWORD    DevInst;
    ULONG_PTR Reserved;
} *PSP_DEVINFO_DATA;

typedef struct {
    DWORD cbSize;
    char ClassGuid[16];
    DWORD DevInst;
    PVOID Reserved;
} SP_DEVINFO_DATA;

typedef struct _SP_DEVICE_INTERFACE_DATA {
    DWORD    cbSize;
    GUID     InterfaceClassGuid;
    DWORD    Flags;
    ULONG_PTR Reserved;
} SP_DEVICE_INTERFACE_DATA, *PSP_DEVICE_INTERFACE_DATA;

typedef struct _SP_DEVICE_INTERFACE_DETAIL_DATA_W {
    DWORD cbSize;
    WCHAR DevicePath[1]; //actually MAX_PATH but can't use define in struct.
} SP_DEVICE_INTERFACE_DETAIL_DATA_W, *PSP_DEVICE_INTERFACE_DETAIL_DATA_W;

typedef BOOL (__stdcall *LPOFNHOOKPROC)(HWND, UINT, WPARAM, LPARAM);

typedef struct _OPENFILENAMEA {
    DWORD        lStructSize;
    HWND         hwndOwner;
    HINSTANCE    hInstance;
    LPCSTR       lpstrFilter;
    LPSTR        lpstrCustomFilter;
    DWORD        nMaxCustFilter;
    DWORD        nFilterIndex;
    LPSTR        lpstrFile;
    DWORD        nMaxFile;
    LPSTR        lpstrFileTitle;
    DWORD        nMaxFileTitle;
    LPCSTR       lpstrInitialDir;
    LPCSTR       lpstrTitle;
    DWORD        Flags;
    WORD         nFileOffset;
    WORD         nFileExtension;
    LPCSTR       lpstrDefExt;
    LPARAM       lCustData;
    LPOFNHOOKPROC lpfnHook;
    LPCSTR       lpTemplateName;
    void         *pvReserved;
    DWORD        dwReserved;
    DWORD        FlagsEx;
} OPENFILENAMEA, *LPOPENFILENAMEA;

#define LF_FACESIZE 32

typedef struct tagLOGFONT {
    LONG  lfHeight;
    LONG  lfWidth;
    LONG  lfEscapement;
    LONG  lfOrientation;
    LONG  lfWeight;
    BYTE  lfItalic;
    BYTE  lfUnderline;
    BYTE  lfStrikeOut;
    BYTE  lfCharSet;
    BYTE  lfOutPrecision;
    BYTE  lfClipPrecision;
    BYTE  lfQuality;
    BYTE  lfPitchAndFamily;
    TCHAR lfFaceName[32];
} LOGFONT, *PLOGFONT, *NPLOGFONT, *LPLOGFONT;

typedef UINT_PTR (__stdcall *LPCFHOOKPROC)(HWND hdlg, UINT uiMsg, WPARAM wParam, LPARAM lParam);

typedef struct _DEVMODEW {
    WCHAR dmDeviceName[32];
    WORD  dmSpecVersion;
    WORD  dmDriverVersion;
    WORD  dmSize;
    WORD  dmDriverExtra;
    DWORD dmFields;
    long  dmOrientation;
    long  dmPaperSize;
    long  dmPaperLength;
    long  dmPaperWidth;
    long  dmScale;
    long  dmCopies;
    long  dmDefaultSource;
    long  dmPrintQuality;
    long  dmColor;
    long  dmDuplex;
    long  dmYResolution;
    long  dmTTOption;
    long  dmNup;
    DWORD dmDisplayFrequency;
    DWORD dmICMMethod;
    DWORD dmICMIntent;
    DWORD dmMediaType;
    DWORD dmDitherType;
    DWORD dmReserved1;
    DWORD dmReserved2;
    DWORD dmPanningWidth;
    DWORD dmPanningHeight;

    ULONG dmPositionX;
    ULONG dmPositionY;
    DWORD dmDisplayOrientation;  // Add this to the structure
    DWORD dmDisplayFixedOutput;
} DEVMODEW, *PDEVMODEW;

typedef struct {
    char  dmDeviceName[32];
    unsigned short dmSpecVersion;
    unsigned short dmDriverVersion;
    unsigned short dmSize;
    unsigned short dmDriverExtra;
    unsigned long  dmFields;
    short dmOrientation;
    short dmPaperSize;
    short dmPaperLength;
    short dmPaperWidth;
    short dmScale;
    short dmCopies;
    short dmDefaultSource;
    short dmPrintQuality;
    short dmColor;
    short dmDuplex;
    short dmYResolution;
    short dmTTOption;
    short dmCollate;
    char  dmFormName[32];
    unsigned short dmLogPixels;
    unsigned long  dmBitsPerPel;
    unsigned long  dmPelsWidth;
    unsigned long  dmPelsHeight;
    unsigned long  dmDisplayFlags;
    unsigned long  dmDisplayFrequency;
    unsigned long  dmICMMethod;
    unsigned long  dmICMIntent;
    unsigned long  dmMediaType;
    unsigned long  dmDitherType;
    unsigned long  dmReserved1;
    unsigned long  dmReserved2;
    unsigned long  dmPanningWidth;
    unsigned long  dmPanningHeight;
} DEVMODE;

typedef struct tagCHOOSEFONT {
    DWORD         lStructSize;
    HWND          hwndOwner;
    HDC           hDC;
    LPLOGFONT     lpLogFont;
    INT           iPointSize;
    DWORD         Flags;
    COLORREF      rgbColors;
    LPARAM        lCustData;
    LPCFHOOKPROC  lpfnHook;
    LPCTSTR       lpTemplateName;
    HINSTANCE     hInstance;
    LPTSTR        lpszStyle;
    WORD          nFontType;
    INT           nSizeMin;
    INT           nSizeMax;
} CHOOSEFONT, *LPCHOOSEFONT;

typedef struct tagOFN {
    DWORD         lStructSize;
    HWND          hwndOwner;
    HINSTANCE     hInstance;
    LPCSTR        lpstrFilter;
    LPSTR         lpstrCustomFilter;
    DWORD         nMaxCustFilter;
    DWORD         nFilterIndex;
    LPSTR         lpstrFile;
    DWORD         nMaxFile;
    LPSTR         lpstrFileTitle;
    DWORD         nMaxFileTitle;
    LPCSTR        lpstrInitialDir;
    LPCSTR        lpstrTitle;
    DWORD         Flags;
    WORD          nFileOffset;
    WORD          nFileExtension;
    LPCSTR        lpstrDefExt;
    LPARAM        lCustData;
    LPOFNHOOKPROC lpfnHook;
    LPCSTR        lpTemplateName;
    void*         pvReserved;
    DWORD         dwReserved;
    DWORD         FlagsEx;
} OPENFILENAME, *LPOPENFILENAME;

typedef struct tagMOUSEINPUT {
    DWORD dx;
    DWORD dy;
    DWORD mouseData;
    DWORD dwFlags;
    DWORD time;
    ULONG_PTR dwExtraInfo;
} MOUSEINPUT;

typedef struct tagHARDWAREINPUT {
    DWORD uMsg;
    WORD wParamL;
    WORD wParamH;
} HARDWAREINPUT;

typedef struct tagKEYBDINPUT {
    WORD wVk;
    WORD wScan;
    DWORD dwFlags;
    DWORD time;
    ULONG_PTR dwExtraInfo;
} KEYBDINPUT;

typedef struct tagINPUT {
    DWORD type;
    union {
        MOUSEINPUT    mi;
        KEYBDINPUT    ki;
        HARDWAREINPUT hi;
    } DUMMYUNIONNAME;
} INPUT;

typedef struct _SECURITY_ATTRIBUTES {
    DWORD nLength;
    LPVOID lpSecurityDescriptor;
    BOOL bInheritHandle;
} SECURITY_ATTRIBUTES, *LPSECURITY_ATTRIBUTES;

typedef struct _PROCESS_INFORMATION {
    HANDLE hProcess;
    HANDLE hThread;
    DWORD dwProcessId;
    DWORD dwThreadId;
} PROCESS_INFORMATION, *LPPROCESS_INFORMATION;

typedef struct _iobuf {
    char* _ptr;       // Current position in buffer
    int   _cnt;       // Remaining characters in buffer
    char* _base;      // Pointer to buffer
    int   _flag;      // Flags (e.g., read/write mode)
    int   _file;      // File descriptor
    int   _charbuf;   // Buffering mode
    int   _bufsiz;    // Size of buffer
    char* _tmpfname;  // Temporary filename (if any)
} FILE;

typedef struct _WINDOW_BUFFER_SIZE_RECORD {
  COORD dwSize;
} WINDOW_BUFFER_SIZE_RECORD;

typedef struct _MENU_EVENT_RECORD {
  UINT dwCommandId;
} MENU_EVENT_RECORD, *PMENU_EVENT_RECORD;

typedef struct _KEY_EVENT_RECORD {
  BOOL  bKeyDown;
  WORD  wRepeatCount;
  WORD  wVirtualKeyCode;
  WORD  wVirtualScanCode;
  union {
    WCHAR UnicodeChar;
    CHAR  AsciiChar;
  } uChar;
  DWORD dwControlKeyState;
} KEY_EVENT_RECORD;

typedef struct _MOUSE_EVENT_RECORD {
  COORD dwMousePosition;
  DWORD dwButtonState;
  DWORD dwControlKeyState;
  DWORD dwEventFlags;
} MOUSE_EVENT_RECORD;

typedef struct _FOCUS_EVENT_RECORD {
  BOOL bSetFocus;
} FOCUS_EVENT_RECORD;

typedef struct _INPUT_RECORD {
  WORD  EventType;
  union {
    KEY_EVENT_RECORD          KeyEvent;
    MOUSE_EVENT_RECORD        MouseEvent;
    WINDOW_BUFFER_SIZE_RECORD WindowBufferSizeEvent;
    MENU_EVENT_RECORD         MenuEvent;
    FOCUS_EVENT_RECORD        FocusEvent;
  } Event;
} INPUT_RECORD;

typedef INPUT_RECORD* PINPUT_RECORD;

typedef void (LPOVERLAPPED_COMPLETION_ROUTINE)(
    DWORD dwErrorCode,
    DWORD dwNumberOfBytesTransfered,
    LPOVERLAPPED lpOverlapped
);

typedef struct _FILE_NOTIFY_INFORMATION {
    DWORD NextEntryOffset;
    DWORD Action;
    DWORD FileNameLength;
    WCHAR FileName[];
} FILE_NOTIFY_INFORMATION, *PFILE_NOTIFY_INFORMATION;

typedef struct {
    void* lpVtbl;
} IUnknown;

typedef struct _SMALL_RECT {
  SHORT Left;
  SHORT Top;
  SHORT Right;
  SHORT Bottom;
} SMALL_RECT;

typedef struct _CONSOLE_SCREEN_BUFFER_INFO {
  COORD  dwSize;
  COORD  dwCursorPosition;
  WORD   wAttributes;
  SMALL_RECT srWindow;
  COORD  dwMaximumWindowSize;
} CONSOLE_SCREEN_BUFFER_INFO, *PCONSOLE_SCREEN_BUFFER_INFO;

typedef struct _SYSTEM_GEOGRAPHIC_INFORMATION {
    DOUBLE Latitude;
    DOUBLE Longitude;
} SYSTEM_GEOGRAPHIC_INFORMATION, *PSYSTEM_GEOGRAPHIC_INFORMATION;

/* Nine longs came to 36 bytes while GetSystemInfo writes 48 on x64, so every
   call overran the allocation by twelve bytes, and the field order did not
   match either - dwNumberOfProcessors sat where dwProcessorType belongs. The
   three pointer-sized members are declared as pointers so they stay the right
   width on both word sizes. */
typedef struct _SYSTEM_INFO {
    WORD wProcessorArchitecture;
    WORD wReserved;
    DWORD dwPageSize;
    LPVOID lpMinimumApplicationAddress;
    LPVOID lpMaximumApplicationAddress;
    LPVOID dwActiveProcessorMask;
    DWORD dwNumberOfProcessors;
    DWORD dwProcessorType;
    DWORD dwAllocationGranularity;
    WORD wProcessorLevel;
    WORD wProcessorRevision;
} SYSTEM_INFO, *LPSYSTEM_INFO;

typedef struct tagHW_PROFILE_INFOA {
    DWORD dwDockInfo;
    CHAR  szHwProfileGuid[39];
    CHAR  szHwProfileName[80];
} HW_PROFILE_INFOA, *LPHW_PROFILE_INFOA;

typedef struct tagHW_PROFILE_INFOW {
    DWORD dwDockInfo;
    WCHAR szHwProfileGuid[39];
    WCHAR szHwProfileName[80];
} HW_PROFILE_INFOW, *LPHW_PROFILE_INFOW;

typedef enum _SYSTEM_INFORMATION_CLASS {
    SystemTimeOfDayInformation = 3,
    SystemProcessInformation = 5,
    SystemProcessorPerformanceInformation = 8,
    SystemInterruptInformation = 23,
    SystemExceptionInformation = 33,
    SystemRegistryQuotaInformation = 37,
    SystemLookasideInformation = 45,
    SystemFileCacheInformation = 69,
    SystemPoolTagInformation = 70,
    SystemGeolocationInformation = 89,
    SystemGeolocationDynamicInformation = 90,
    SystemWakeSourceInformation = 95,
    SystemHiberFileInformation = 98,
    SystemPowerInformation = 108,
    SystemRemoteSessionInformation = 121,
        SystemModuleInformation = 167,
    SystemBatteryState = 250,
        SystemBootEnvironmentInformation = 282,
        SystemHypervisorInformation = 286,
        SystemVerifierInformation = 296,
        SystemLockInformation = 309,
        SystemEnclaveInformation = 323
} SYSTEM_INFORMATION_CLASS;

// Taskbar progress flag values
typedef enum {
    TBPF_NOPROGRESS    = 0x0,  // no progress
    TBPF_INDETERMINATE = 0x1,  // pulsing
    TBPF_NORMAL        = 0x2,  // green bar
    TBPF_ERROR         = 0x4,  // red bar
    TBPF_PAUSED        = 0x8   // yellow bar
} TBPFLAG;  // values from shobjidl_core.h

// ITaskbarList3 vtable (only up through SetProgressState/Value)
typedef struct ITaskbarList3Vtbl {
    HRESULT (__stdcall *QueryInterface)(void*, GUID*, void**);
    unsigned long (__stdcall *AddRef)(void*);
    unsigned long (__stdcall *Release)(void*);
    HRESULT (__stdcall *HrInit)(void*);
    HRESULT (__stdcall *AddTab)(void*, HWND);
    HRESULT (__stdcall *DeleteTab)(void*, HWND);
    HRESULT (__stdcall *ActivateTab)(void*, HWND);
    HRESULT (__stdcall *SetActiveAlt)(void*, HWND);
    HRESULT (__stdcall *MarkFullscreenWindow)(void*, HWND, int);
    HRESULT (__stdcall *SetProgressValue)(
        void*,
        HWND,
        ULONGLONG,    // ullCompleted
        ULONGLONG     // ullTotal
    );
    HRESULT (__stdcall *SetProgressState)(
        void*,
        HWND,
        TBPFLAG
    );
} ITaskbarList3Vtbl;

typedef struct {
    ITaskbarList3Vtbl *lpVtbl;
} ITaskbarList3;


typedef struct _LOCATION_INFO {
    DWORD dwSize;
    DWORD dwValidFields;
    struct {
        DOUBLE Latitude;
        DOUBLE Longitude;
    } GeoPosition;
} LOCATION_INFO;

typedef struct _PROPERTYKEY {
    GUID fmtid;
    DWORD pid;
} PROPERTYKEY;

typedef struct _SYSTEMTIME {
    WORD wYear;
    WORD wMonth;
    WORD wDayOfWeek;
    WORD wDay;
    WORD wHour;
    WORD wMinute;
    WORD wSecond;
    WORD wMilliseconds;
} SYSTEMTIME, *PSYSTEMTIME, *LPSYSTEMTIME;

typedef struct _FILETIME {
    DWORD dwLowDateTime;
    DWORD dwHighDateTime;
} FILETIME;

typedef struct _PROPVARIANT {
    VARTYPE vt;
    union {
        CHAR cVal;
        SHORT iVal;
        LONG lVal;
        FLOAT fltVal;
        DOUBLE dblVal;
        VARIANT_BOOL boolVal;
        SCODE scode;
        FILETIME filetime;
        LPSTR pszVal;
        LPWSTR pwszVal;
    };
} PROPVARIANT;

typedef struct _LOCATION_REPORT {
    HRESULT (__stdcall *GetSensorID)(void *pThis, GUID *pSensorID);
    HRESULT (__stdcall *GetCreationTime)(void *pThis, SYSTEMTIME *pCreationTime);
    HRESULT (__stdcall *GetValue)(void *pThis, PROPERTYKEY *pKey, PROPVARIANT *pVal);
} LOCATION_REPORT;

typedef struct _GET_MEDIA_TYPES {
    DWORD DeviceType;
    DWORD DeviceMediaInfo;
    DWORD MediaInfoCount;
    DWORD MediaInfo[1]; // Flexible array member
} GET_MEDIA_TYPES, *PGET_MEDIA_TYPES;

typedef struct _DISK_GEOMETRY {
    ULONGLONG Cylinders;
    DWORD MediaType;
    DWORD TracksPerCylinder;
    DWORD SectorsPerTrack;
    DWORD BytesPerSector;
} DISK_GEOMETRY, *PDISK_GEOMETRY;

typedef struct _DISK_GEOMETRY_EX {
    DISK_GEOMETRY Geometry;
    ULONGLONG DiskSize;
    BYTE Data[1]; // Flexible array member
} DISK_GEOMETRY_EX, *PDISK_GEOMETRY_EX;

typedef enum _STORAGE_PROTOCOL_TYPE {
    ProtocolTypeUnknown    = 0,
    ProtocolTypeScsi       = 1,
    ProtocolTypeAta        = 2,
    ProtocolTypeNvme       = 3,
    ProtocolTypeSd         = 4,
    ProtocolTypeUfs        = 5,
    ProtocolTypeProprietary= 0x7E,
    ProtocolTypeMaxReserved= 0x7F
} STORAGE_PROTOCOL_TYPE;

typedef enum _STORAGE_BUS_TYPE {
    BusTypeUnknown   = 0,
    BusTypeScsi      = 1,
    BusTypeAtapi     = 2,
    BusTypeAta       = 3,
    BusType1394      = 4,
    BusTypeSsa       = 5,
    BusTypeFibre     = 6,
    BusTypeUsb       = 7,
    BusTypeRAID      = 8,
    BusTypeiScsi     = 9,
    BusTypeSas       = 10,
    BusTypeSata      = 11,
    BusTypeSd        = 12,
    BusTypeMmc       = 13,
    BusTypeVirtual   = 14,
    BusTypeFileBackedVirtual = 15,
    BusTypeSpaces    = 16,
    BusTypeNvme      = 17,
    BusTypeSCM       = 18,
    BusTypeUfs       = 19,
    BusTypeMax       = 20
} STORAGE_BUS_TYPE;

typedef struct _STORAGE_PROTOCOL_SPECIFIC_DATA {
    STORAGE_PROTOCOL_TYPE ProtocolType;
    DWORD DataType;
    DWORD ProtocolDataRequestValue;
    DWORD ProtocolDataRequestSubValue;
    DWORD ProtocolDataOffset;
    DWORD ProtocolDataLength;
    DWORD FixedProtocolReturnData;
    DWORD Reserved[1];
} STORAGE_PROTOCOL_SPECIFIC_DATA;

typedef struct _STORAGE_PROTOCOL_DATA_DESCRIPTOR {
    DWORD Version;
    DWORD Size;
    STORAGE_PROTOCOL_SPECIFIC_DATA ProtocolSpecificData;
} STORAGE_PROTOCOL_DATA_DESCRIPTOR;

typedef struct _STORAGE_PROPERTY_QUERY {
    DWORD PropertyId;
    DWORD QueryType;
    BYTE AdditionalParameters[1];
} STORAGE_PROPERTY_QUERY, *PSTORAGE_PROPERTY_QUERY;

typedef struct _STORAGE_DEVICE_DESCRIPTOR {
    DWORD             Version;
    DWORD             Size;
    BYTE              DeviceType;
    BYTE              DeviceTypeModifier;
    BYTE              RemovableMedia;
    BYTE              CommandQueueing;
    DWORD             VendorIdOffset;
    DWORD             ProductIdOffset;
    DWORD             ProductRevisionOffset;
    DWORD             SerialNumberOffset;
    STORAGE_BUS_TYPE  BusType;
    DWORD             RawPropertiesLength;
    BYTE              RawDeviceProperties[1];
} STORAGE_DEVICE_DESCRIPTOR;

typedef struct _STORAGE_DESCRIPTOR_HEADER {
    DWORD Version;
    DWORD Size;
} STORAGE_DESCRIPTOR_HEADER, *PSTORAGE_DESCRIPTOR_HEADER;

typedef struct _STORAGE_ACCESS_ALIGNMENT_DESCRIPTOR {
    STORAGE_DESCRIPTOR_HEADER Header;
    ULONG BytesPerCacheLine;
    ULONG BytesOffsetForCacheAlignment;
    ULONG BytesPerLogicalSector;
    ULONG BytesOffsetForSectorAlignment;
} STORAGE_ACCESS_ALIGNMENT_DESCRIPTOR, *PSTORAGE_ACCESS_ALIGNMENT_DESCRIPTOR;

typedef struct _STORAGE_BUS_QUERY {
    DWORD   PortSubsystem;
    DWORD   Reserved;
} STORAGE_BUS_QUERY, *PSTORAGE_BUS_QUERY;

typedef LRESULT (*HOOKPROC)(INT nCode, WPARAM wParam, LPARAM lParam);

// ILocationReport vtable (partial)
typedef struct ILocationReportVtbl {
    // IUnknown
    HRESULT (__stdcall *QueryInterface)(LPVOID, const GUID*, LPVOID*);
    ULONG   (__stdcall *AddRef)(LPVOID);
    ULONG   (__stdcall *Release)(LPVOID);
    // ILocationReport
    HRESULT (__stdcall *GetValue)(LPVOID, const GUID*, PROPVARIANT*);
    // ... other methods omitted
} ILocationReportVtbl;

typedef struct { ILocationReportVtbl *lpVtbl; } ILocationReport;

typedef struct _LOCATION_API_INTERFACE {
    HRESULT (__stdcall *GetReport)(void *pThis, REFIID reportType, ILocationReport **report);
    HRESULT (__stdcall *RegisterForReport)(void *pThis, HWND hWnd, UINT wMsg, REFIID reportType, DWORD dwDesiredAccuracy);
    HRESULT (__stdcall *UnregisterForReport)(void *pThis, HWND hWnd, REFIID reportType);
    HRESULT (__stdcall *Open)(void *pThis, HWND hWnd, BOOL fConsentGiven);
    HRESULT (__stdcall *Close)(void *pThis);
} LOCATION_API_INTERFACE;

typedef struct {
    DWORD dwFileAttributes;
    unsigned long ftCreationTime[2];
    unsigned long ftLastAccessTime[2];
    unsigned long ftLastWriteTime[2];
    DWORD nFileSizeHigh;
    DWORD nFileSizeLow;
    DWORD dwReserved0;
    DWORD dwReserved1;
    char  cFileName[260];
    char  cAlternateFileName[14];
} WIN32_FIND_DATAA;

// ILocation COM vtable (partial)
typedef struct ILocationVtbl {
    // IUnknown methods
    HRESULT (__stdcall *QueryInterface)(LPVOID, const GUID*, LPVOID*);
    ULONG   (__stdcall *AddRef)(LPVOID);
    ULONG   (__stdcall *Release)(LPVOID);
    // ILocation methods
    HRESULT (__stdcall *RequestPermissions)(LPVOID, LPVOID, WORD, BOOL);
    HRESULT (__stdcall *GetReport)(LPVOID, const GUID*, LPVOID*);
    // ... other methods omitted
} ILocationVtbl;

typedef struct { ILocationVtbl *lpVtbl; } ILocation;

typedef struct {
    int length;
    int flags;
    int showCmd;
    POINT ptMinPosition;
    POINT ptMaxPosition;
    RECT rcNormalPosition;
} WINDOWPLACEMENT;

typedef struct tagPROCESSENTRY32 {
  DWORD     dwSize;
  DWORD     cntUsage;
  DWORD     th32ProcessID;
  ULONG_PTR th32DefaultHeapID;
  DWORD     th32ModuleID;
  DWORD     cntThreads;
  DWORD     th32ParentProcessID;
  LONG      pcPriClassBase;
  DWORD     dwFlags;
  short      szExeFile[260];
} PROCESSENTRY32;

typedef struct {
    DWORD cbSize;
    HWND  hWnd;
    UINT  uID;
    UINT  uFlags;
    UINT  uCallbackMessage;
    HICON hIcon;
    char  szTip[128];
    DWORD dwState;
    DWORD dwStateMask;
    char  szInfo[256];
    union {
        UINT uTimeout;
        UINT uVersion;
    } DUMMYUNIONNAME;
    char  szInfoTitle[64];
    DWORD dwInfoFlags;
} NOTIFYICONDATAA;

typedef struct tagBITMAP {
    LONG   bmType;        // Always zero
    LONG   bmWidth;       // Width in pixels
    LONG   bmHeight;      // Height in pixels
    LONG   bmWidthBytes;  // Bytes per scan line (word-aligned)
    WORD   bmPlanes;      // Number of color planes
    WORD   bmBitsPixel;   // Bits per pixel
    LPVOID bmBits;        // Pointer to the bitmap bits
} BITMAP, *PBITMAP;

typedef struct _SHELLEXECUTEINFOW {
    DWORD cbSize;
    ULONG_PTR fMask;
    HWND hwnd;
    LPCWSTR lpVerb;
    LPCWSTR lpFile;
    LPCWSTR lpParameters;
    LPCWSTR lpDirectory;
    int nShow;
    HANDLE hInstApp;
    void* lpIDList;
    LPCWSTR lpClass;
    HANDLE hkeyClass;
    DWORD dwHotKey;
    HANDLE hIcon;
    HANDLE hProcess;
} SHELLEXECUTEINFOW, *LPSHELLEXECUTEINFOW;

typedef struct _SID_IDENTIFIER_AUTHORITY {
    BYTE Value[6];
} SID_IDENTIFIER_AUTHORITY, *PSID_IDENTIFIER_AUTHORITY;

typedef struct {
    DWORD lStructSize;
    HWND hwndOwner;
    HWND hInstance;
    COLORREF rgbResult;
    COLORREF* lpCustColors;
    DWORD Flags;
    void* lCustData;
    void* lpfnHook;
    const WCHAR* lpTemplateName;
} CHOOSECOLORW;

typedef struct _BLENDFUNCTION {
  BYTE BlendOp;
  BYTE BlendFlags;
  BYTE SourceConstantAlpha;
  BYTE AlphaFormat;
} BLENDFUNCTION, *PBLENDFUNCTION;

typedef struct {
    u_short sin_family;
    u_short sin_port;
    struct {
        u_long s_addr;
    } sin_addr;
    char sin_zero[8];
} SOCKADDR_IN;

typedef struct {
    unsigned short wVersion;
    unsigned short wHighVersion;
    char szDescription[257];
    char szSystemStatus[129];
    unsigned short iMaxSockets;
    unsigned short iMaxUdpDg;
    char* lpVendorInfo;
} WSADATA;

typedef struct {
    DWORD Version;
    DWORD Size;
    BOOL  IncursSeekPenalty;
} DEVICE_SEEK_PENALTY_DESCRIPTOR;

typedef struct {
    BYTE Features;       // aka Features (or Error on PIO-in)
    BYTE SectorCount;
    BYTE SectorNumber;   // LBA Low
    BYTE CylinderLow;    // LBA Mid
    BYTE CylinderHigh;   // LBA High
    BYTE DeviceHead;     // Device/Head
    BYTE Command;        // Command
    BYTE Reserved;
} ATA_REGISTERS;

typedef struct {
    USHORT Length;
    USHORT AtaFlags;
    BYTE   PathId;
    BYTE   TargetId;
    BYTE   Lun;
    BYTE   ReservedAsUchar;
    DWORD  DataTransferLength;
    DWORD  TimeOutValue;
    DWORD  ReservedAsUlong;
    SIZE_T DataBufferOffset;     // ULONG_PTR
    ATA_REGISTERS PreviousTaskFile;
    ATA_REGISTERS CurrentTaskFile;
} ATA_PASS_THROUGH_EX;

typedef struct _PERF_COUNTER_IDENTIFIER {
    GUID CounterSetGuid;
    ULONG Status;
    ULONG Size;
    ULONG CounterId;
    ULONG InstanceId;
    ULONG Index;
    ULONG Reserved;
} PERF_COUNTER_IDENTIFIER, *PPERF_COUNTER_IDENTIFIER;

typedef struct _PERF_DATA_HEADER {
    ULONG dwTotalSize;
    ULONG dwNumCounters;
    LONGLONG PerfTimeStamp;
    LONGLONG PerfTime100NSec;
    LONGLONG PerfFreq;
    SYSTEMTIME SystemTime;
} PERF_DATA_HEADER, *PPERF_DATA_HEADER;

typedef struct _CONSOLE_FONT_INFO
{
    DWORD       nFont;
    COORD       dwFontSize;
} CONSOLE_FONT_INFO,*LPCONSOLE_FONT_INFO;

typedef struct _STARTUPINFOA{
        DWORD cb;		/* 00: size of struct */
        LPSTR lpReserved;	/* 04: */
        LPSTR lpDesktop;	/* 08: */
        LPSTR lpTitle;		/* 0c: */
        DWORD dwX;		/* 10: */
        DWORD dwY;		/* 14: */
        DWORD dwXSize;		/* 18: */
        DWORD dwYSize;		/* 1c: */
        DWORD dwXCountChars;	/* 20: */
        DWORD dwYCountChars;	/* 24: */
        DWORD dwFillAttribute;	/* 28: */
        DWORD dwFlags;		/* 2c: */
        WORD wShowWindow;	/* 30: */
        WORD cbReserved2;	/* 32: */
        BYTE *lpReserved2;	/* 34: */
        HANDLE hStdInput;	/* 38: */
        HANDLE hStdOutput;	/* 3c: */
        HANDLE hStdError;	/* 40: */
} STARTUPINFOA, *LPSTARTUPINFOA;

typedef struct _STARTUPINFOW{
        DWORD cb;
        LPWSTR lpReserved;
        LPWSTR lpDesktop;
        LPWSTR lpTitle;
        DWORD dwX;
        DWORD dwY;
        DWORD dwXSize;
        DWORD dwYSize;
        DWORD dwXCountChars;
        DWORD dwYCountChars;
        DWORD dwFillAttribute;
        DWORD dwFlags;
        WORD wShowWindow;
        WORD cbReserved2;
        BYTE *lpReserved2;
        HANDLE hStdInput;
        HANDLE hStdOutput;
        HANDLE hStdError;
} STARTUPINFOW, *LPSTARTUPINFOW;

typedef struct
{
    UINT MaxCharSize;
    BYTE DefaultChar[2];
    BYTE LeadByte[12];
    WCHAR UnicodeDefaultChar;
    UINT CodePage;
    CHAR CodePageName[260];
} CPINFOEXA, *LPCPINFOEXA;

typedef struct
{
    UINT MaxCharSize;
    BYTE DefaultChar[2];
    BYTE LeadByte[12];
    WCHAR UnicodeDefaultChar;
    UINT CodePage;
    WCHAR CodePageName[260];
} CPINFOEXW, *LPCPINFOEXW;

typedef struct _SHQUERYRBINFO
{
    DWORD cbSize;
    DWORDLONG i64Size;
    DWORDLONG i64NumItems;
} SHQUERYRBINFO, *LPSHQUERYRBINFO;

typedef struct _NOTIFYICONIDENTIFIER
{
    DWORD cbSize;
    HWND hWnd;
    UINT uID;
    GUID guidItem;
} NOTIFYICONIDENTIFIER, *PNOTIFYICONIDENTIFIER;

typedef struct _SHSTOCKICONINFO
{
    DWORD   cbSize;
    HICON   hIcon;
    INT     iSysImageIndex;
    INT     iIcon;
    WCHAR   szPath[260];
} SHSTOCKICONINFO;

typedef enum
{
    QUNS_NOT_PRESENT             = 1,
    QUNS_BUSY                    = 2,
    QUNS_RUNNING_D3D_FULL_SCREEN = 3,
    QUNS_PRESENTATION_MODE       = 4,
    QUNS_ACCEPTS_NOTIFICATIONS   = 5,
    QUNS_QUIET_TIME              = 6,
    QUNS_APP                     = 7
} QUERY_USER_NOTIFICATION_STATE;

typedef enum SHSTOCKICONID
{
    SIID_INVALID=-1,
    SIID_DOCNOASSOC,
    SIID_DOCASSOC,
    SIID_APPLICATION,
    SIID_FOLDER,
    SIID_FOLDEROPEN,
    SIID_DRIVE525,
    SIID_DRIVE35,
    SIID_DRIVERREMOVE,
    SIID_DRIVERFIXED,
    SIID_DRIVERNET,
    SIID_DRIVERNETDISABLE,
    SIID_DRIVERCD,
    SIID_DRIVERRAM,
    SIID_WORLD,
    /* Missing: 14 */
    SIID_SERVER = 15,
    SIID_PRINTER,
    SIID_MYNETWORK,
    /* Missing: 18 - 21 */
    SIID_FIND = 22,
    SIID_HELP,
    /* Missing: 24 - 27 */
    SIID_SHARE = 28,
    SIID_LINK,
    SIID_SLOWFILE,
    SIID_RECYCLER,
    SIID_RECYCLERFULL,
    /* Missing: 33 - 39 */
    SIID_MEDIACDAUDIO = 40,
    /* Missing: 41 - 46 */
    SIID_LOCK = 47,
    /* Missing: 48 */
    SIID_AUTOLIST = 49,
    SIID_PRINTERNET,
    SIID_SERVERSHARE,
    SIID_PRINTERFAX,
    SIID_PRINTERFAXNET,
    SIID_PRINTERFILE,
    SIID_STACK,
    SIID_MEDIASVCD,
    SIID_STUFFEDFOLDER,
    SIID_DRIVEUNKNOWN,
    SIID_DRIVEDVD,
    SIID_MEDIADVD,
    SIID_MEDIADVDRAM,
    SIID_MEDIADVDRW,
    SIID_MEDIADVDR,
    SIID_MEDIADVDROM,
    SIID_MEDIACDAUDIOPLUS,
    SIID_MEDIACDRW,
    SIID_MEDIACDR,
    SIID_MEDIACDBURN,
    SIID_MEDIABLANKCD,
    SIID_MEDIACDROM,
    SIID_AUDIOFILES,
    SIID_IMAGEFILES,
    SIID_VIDEOFILES,
    SIID_MIXEDFILES,
    SIID_FOLDERBACK,
    SIID_FOLDERFRONT,
    SIID_SHIELD,
    SIID_WARNING,
    SIID_INFO,
    SIID_ERROR,
    SIID_KEY,
    SIID_SOFTWARE,
    SIID_RENAME,
    SIID_DELETE,
    SIID_MEDIAAUDIODVD,
    SIID_MEDIAMOVIEDVD,
    SIID_MEDIAENHANCEDCD,
    SIID_MEDIAENHANCEDDVD,
    SIID_MEDIAHDDVD,
    SIID_MEDIABLUERAY,
    SIID_MEDIAVCD,
    SIID_MEDIADVDPLUSR,
    SIID_MEDIADVDPLUSRW,
    SIID_DESKTOPPC,
    SIID_MOBILEPC,
    SIID_USERS,
    SIID_MEDIASMARTMEDIA,
    SIID_MEDIACOMPACTFLASH,
    SIID_DEVICECELLPHONE,
    SIID_DEVICECAMERA,
    SIID_DEVICEVIDEOCAMERA,
    SIID_DEVICEAUDIOPLAYER,
    SIID_NETWORKCONNECT,
    SIID_INTERNET,
    SIID_ZIPFILE,
    SIID_SETTINGS,
    /* Missing: 107 - 131 */
    SIID_DRIVEHDDVD = 132,
    SIID_DRIVEBD,
    SIID_MEDIAHDDVDROM,
    SIID_MEDIAHDDVDR,
    SIID_MEDIAHDDVDRAM,
    SIID_MEDIABDROM,
    SIID_MEDIABDR,
    SIID_MEDIABDRE,
    SIID_CLUSTEREDDRIVE,
    /* Missing: 141 - 180 */
    SIID_MAX_ICONS = 181
}SHSTOCKICONID;

typedef struct tagLOGFONTA
{
    LONG   lfHeight;
    LONG   lfWidth;
    LONG   lfEscapement;
    LONG   lfOrientation;
    LONG   lfWeight;
    BYTE   lfItalic;
    BYTE   lfUnderline;
    BYTE   lfStrikeOut;
    BYTE   lfCharSet;
    BYTE   lfOutPrecision;
    BYTE   lfClipPrecision;
    BYTE   lfQuality;
    BYTE   lfPitchAndFamily;
    CHAR   lfFaceName[32];
} LOGFONTA, *PLOGFONTA, *LPLOGFONTA;

typedef struct tagPRINTPAGERANGE
{
    DWORD       nFromPage;
    DWORD       nToPage;
} PRINTPAGERANGE, *LPPRINTPAGERANGE;

typedef struct tagPDEXA
{
    DWORD               lStructSize;
    HWND                hwndOwner;
    HGLOBAL             hDevMode;
    HGLOBAL             hDevNames;
    HDC                 hDC;
    DWORD               Flags;
    DWORD               Flags2;
    DWORD               ExclusionFlags;
    DWORD               nPageRanges;
    DWORD               nMaxPageRanges;
    LPPRINTPAGERANGE    lpPageRanges;
    DWORD               nMinPage;
    DWORD               nMaxPage;
    DWORD               nCopies;
    HINSTANCE           hInstance;
    LPCSTR              lpPrintTemplateName;
    void* /*LPUNKNOWN*/ lpCallback;
    DWORD               nPropertyPages;
    HPROPSHEETPAGE*     lphPropertyPages;
    DWORD               nStartPage;
    DWORD               dwResultAction;
} PRINTDLGEXA, *LPPRINTDLGEXA;

typedef struct tagLOGFONTW
{
    LONG   lfHeight;
    LONG   lfWidth;
    LONG   lfEscapement;
    LONG   lfOrientation;
    LONG   lfWeight;
    BYTE   lfItalic;
    BYTE   lfUnderline;
    BYTE   lfStrikeOut;
    BYTE   lfCharSet;
    BYTE   lfOutPrecision;
    BYTE   lfClipPrecision;
    BYTE   lfQuality;
    BYTE   lfPitchAndFamily;
    WCHAR  lfFaceName[32];
} LOGFONTW, *PLOGFONTW, *LPLOGFONTW;

typedef UINT_PTR (*LPPRINTHOOKPROC) (HWND, UINT, WPARAM, LPARAM);
typedef UINT_PTR (*LPSETUPHOOKPROC) (HWND, UINT, WPARAM, LPARAM);

typedef struct tagPDW
{
    DWORD            lStructSize;
    HWND           hwndOwner;
    HGLOBAL        hDevMode;
    HGLOBAL        hDevNames;
    HDC            hDC;
    DWORD            Flags;
    WORD             nFromPage;
    WORD             nToPage;
    WORD             nMinPage;
    WORD             nMaxPage;
    WORD             nCopies;
    HINSTANCE      hInstance;
    LPARAM           lCustData;
    LPPRINTHOOKPROC  lpfnPrintHook;
    LPSETUPHOOKPROC  lpfnSetupHook;
    LPCWSTR          lpPrintTemplateName;
    LPCWSTR          lpSetupTemplateName;
    HGLOBAL        hPrintTemplate;
    HGLOBAL        hSetupTemplate;
} PRINTDLGW, *LPPRINTDLGW;

typedef struct tagPDA
{
    DWORD            lStructSize;
    HWND           hwndOwner;
    HGLOBAL        hDevMode;
    HGLOBAL        hDevNames;
    HDC            hDC;
    DWORD            Flags;
    WORD             nFromPage;
    WORD             nToPage;
    WORD             nMinPage;
    WORD             nMaxPage;
    WORD             nCopies;
    HINSTANCE      hInstance;
    LPARAM           lCustData;
    LPPRINTHOOKPROC  lpfnPrintHook;
    LPSETUPHOOKPROC  lpfnSetupHook;
    LPCSTR           lpPrintTemplateName;
    LPCSTR           lpSetupTemplateName;
    HGLOBAL        hPrintTemplate;
    HGLOBAL        hSetupTemplate;
} PRINTDLGA, *LPPRINTDLGA;

typedef struct tagCHOOSEFONTW
{
	UINT  	lStructSize;
	HWND 		hwndOwner;
	HDC  		hDC;
	LPLOGFONTW    lpLogFont;
	INT		iPointSize;
	DWORD		Flags;
	COLORREF	rgbColors;
	LPARAM		lCustData;
	LPCFHOOKPROC 	lpfnHook;
	LPCWSTR		lpTemplateName;
	HINSTANCE	hInstance;
	LPWSTR		lpszStyle;
	WORD		nFontType;
	WORD	___MISSING_ALIGNMENT__;
	INT   	nSizeMin;
	INT		nSizeMax;
} CHOOSEFONTW, *LPCHOOSEFONTW;

typedef UINT (*LPPAGEPAINTHOOK)( HWND, UINT, WPARAM, LPARAM );
typedef UINT (*LPPAGESETUPHOOK)( HWND, UINT, WPARAM, LPARAM );

typedef struct tagPSDW
{
	DWORD		lStructSize;
	HWND		hwndOwner;
	HGLOBAL	hDevMode;
	HGLOBAL	hDevNames;
	DWORD		Flags;
	POINT		ptPaperSize;
	RECT		rtMinMargin;
	RECT		rtMargin;
	HINSTANCE	hInstance;
	LPARAM		lCustData;
	LPPAGESETUPHOOK	lpfnPageSetupHook;
	LPPAGEPAINTHOOK	lpfnPagePaintHook;
	LPCWSTR		lpPageSetupTemplateName;
	HGLOBAL	hPageSetupTemplate;
} PAGESETUPDLGW,*LPPAGESETUPDLGW;

typedef struct tagCHOOSEFONTA
{
	UINT  	lStructSize;
	HWND 		hwndOwner;
	HDC  		hDC;
	LPLOGFONTA    lpLogFont;
	INT		iPointSize;
	DWORD		Flags;
	COLORREF	rgbColors;
	LPARAM		lCustData;
	LPCFHOOKPROC 	lpfnHook;
	LPCSTR		lpTemplateName;
	HINSTANCE	hInstance;
	LPSTR		lpszStyle;
	WORD		nFontType;
	WORD	___MISSING_ALIGNMENT__;
	INT   	nSizeMin;
	INT		nSizeMax;
} CHOOSEFONTA, *LPCHOOSEFONTA;

typedef struct tagPSDA
{
	DWORD		lStructSize;
	HWND		hwndOwner;
	HGLOBAL	hDevMode;
	HGLOBAL	hDevNames;
	DWORD		Flags;
	POINT		ptPaperSize;
	RECT		rtMinMargin;
	RECT		rtMargin;
	HINSTANCE	hInstance;
	LPARAM		lCustData;
	LPPAGESETUPHOOK	lpfnPageSetupHook;
	LPPAGEPAINTHOOK	lpfnPagePaintHook;
	LPCSTR		lpPageSetupTemplateName;
	HGLOBAL	hPageSetupTemplate;
} PAGESETUPDLGA,*LPPAGESETUPDLGA;

typedef struct tagOFNW {
	DWORD		lStructSize;
	HWND		hwndOwner;
	HINSTANCE	hInstance;
	LPCWSTR		lpstrFilter;
	LPWSTR		lpstrCustomFilter;
	DWORD		nMaxCustFilter;
	DWORD		nFilterIndex;
	LPWSTR		lpstrFile;
	DWORD		nMaxFile;
	LPWSTR		lpstrFileTitle;
	DWORD		nMaxFileTitle;
	LPCWSTR		lpstrInitialDir;
	LPCWSTR		lpstrTitle;
	DWORD		Flags;
	WORD		nFileOffset;
	WORD		nFileExtension;
	LPCWSTR		lpstrDefExt;
	LPARAM		lCustData;
	LPOFNHOOKPROC	lpfnHook;
	LPCWSTR		lpTemplateName;
        void           *pvReserved;
        DWORD           dwReserved;
        DWORD           FlagsEx;
} OPENFILENAMEW,*LPOPENFILENAMEW;

typedef UINT_PTR (*LPFRHOOKPROC)(HWND,UINT,WPARAM,LPARAM);

typedef struct {
	DWORD		lStructSize;
	HWND		hwndOwner;
	HINSTANCE	hInstance;
	DWORD		Flags;
	LPWSTR		lpstrFindWhat;
	LPWSTR		lpstrReplaceWith;
	WORD		wFindWhatLen;
	WORD 		wReplaceWithLen;
	LPARAM 		lCustData;
        LPFRHOOKPROC    lpfnHook;
	LPCWSTR		lpTemplateName;
} FINDREPLACEW, *LPFINDREPLACEW;

typedef struct {
	DWORD		lStructSize;
	HWND		hwndOwner;
	HINSTANCE	hInstance;
	DWORD		Flags;
	LPSTR		lpstrFindWhat;
	LPSTR		lpstrReplaceWith;
	WORD		wFindWhatLen;
	WORD 		wReplaceWithLen;
	LPARAM 		lCustData;
        LPFRHOOKPROC    lpfnHook;
	LPCSTR 		lpTemplateName;
} FINDREPLACEA, *LPFINDREPLACEA;

typedef struct tagPDEXW
{
    DWORD               lStructSize;
    HWND                hwndOwner;
    HGLOBAL             hDevMode;
    HGLOBAL             hDevNames;
    HDC                 hDC;
    DWORD               Flags;
    DWORD               Flags2;
    DWORD               ExclusionFlags;
    DWORD               nPageRanges;
    DWORD               nMaxPageRanges;
    LPPRINTPAGERANGE    lpPageRanges;
    DWORD               nMinPage;
    DWORD               nMaxPage;
    DWORD               nCopies;
    HINSTANCE           hInstance;
    LPCWSTR             lpPrintTemplateName;
    void* /*LPUNKNOWN*/ lpCallback;
    DWORD               nPropertyPages;
    HPROPSHEETPAGE*     lphPropertyPages;
    DWORD               nStartPage;
    DWORD               dwResultAction;
} PRINTDLGEXW, *LPPRINTDLGEXW;

typedef struct _MEMORYSTATUSEX {
  DWORD     dwLength;
  DWORD     dwMemoryLoad;
  DWORDLONG ullTotalPhys;
  DWORDLONG ullAvailPhys;
  DWORDLONG ullTotalPageFile;
  DWORDLONG ullAvailPageFile;
  DWORDLONG ullTotalVirtual;
  DWORDLONG ullAvailVirtual;
  DWORDLONG ullAvailExtendedVirtual;
} MEMORYSTATUSEX, *LPMEMORYSTATUSEX;

typedef struct {
    WORD  wFormatTag;
    WORD  nChannels;
    DWORD nSamplesPerSec;
    DWORD nAvgBytesPerSec;
    WORD  nBlockAlign;
    WORD  wBitsPerSample;
    WORD  cbSize;
} WAVEFORMATEX;

/* WINEVENTPROC — callback signature for SetWinEventHook */
typedef void (*WINEVENTPROC)(
    HWINEVENTHOOK hWinEventHook,
    DWORD event,
    HWND hwnd,
    LONG idObject,
    LONG idChild,
    DWORD idEventThread,
    DWORD dwmsEventTime
);

/*
    * PROCESSENTRY32W — 64-bit layout:
    *   dwSize       (4)
    *   cntUsage     (4)
    *   th32ProcessID(4)
    *   [4 pad]          ← natural alignment before ULONG_PTR (8 bytes)
    *   th32DefaultHeapID(8)
    *   th32ModuleID (4)
    *   cntThreads   (4)
    *   th32ParentPID(4)
    *   pcPriClassBase(4)
    *   dwFlags      (4)
    *   szExeFile    (520)
    */
typedef struct {
    DWORD    dwSize;
    DWORD    cntUsage;
    DWORD    th32ProcessID;
    uint64_t th32DefaultHeapID;
    DWORD    th32ModuleID;
    DWORD    cntThreads;
    DWORD    th32ParentProcessID;
    int32_t  pcPriClassBase;
    DWORD    dwFlags;
    WCHAR    szExeFile[260];
} PROCESSENTRY32W;

typedef const WAVEFORMATEX* LPCWAVEFORMATEX;

typedef BOOL (*WNDENUMPROC)(HWND, LPARAM);
typedef BOOL (*CODEPAGE_ENUMPROCA)(LPSTR);

typedef void* IActivationFactory;
typedef void* IAsyncOperation;
typedef void* IGeolocator;
typedef void* IGeoposition;
typedef void* IPosition;

typedef UINT (*fnMsiProvideComponentFromDescriptor)(LPCWSTR,LPWSTR,DWORD*,DWORD*);
typedef HRESULT (*PCOGETCALLSTATE)(int,PULONG);
typedef HRESULT (*PCOGETACTIVATIONSTATE)(GUID,DWORD,DWORD*);

typedef HRESULT (*pfnGetDefaultAudioEndpoint)(void* self, uint32_t dataFlow, uint32_t role, void** ppDevice);
typedef HRESULT (*pfnActivate)(void* self, const uint8_t* iid, uint32_t dwClsCtx, void* pActivationParams, void** ppInterface);
typedef HRESULT (*pfnGetMasterVolumeLevelScalar)(void* self, float* pfLevel);
typedef ULONG   (*pfnRelease)(void* self);
typedef HRESULT (*pfnSetMasterVolumeLevelScalar)(void* self, float fLevel, const uint8_t* pguidEventContext);

typedef struct tagMSLLHOOKSTRUCT {
    POINT     pt;
    DWORD     mouseData;
    DWORD     flags;
    DWORD     time;
    ULONG_PTR dwExtraInfo;
} MSLLHOOKSTRUCT;

typedef struct _IDEREGS {
    BYTE bFeaturesReg;
    BYTE bSectorCountReg;
    BYTE bSectorNumberReg;
    BYTE bCylLowReg;
    BYTE bCylHighReg;
    BYTE bDriveHeadReg;
    BYTE bCommandReg;
    BYTE bReserved;
} IDEREGS;

typedef struct _SENDCMDINPARAMS {
    DWORD  cBufferSize;
    IDEREGS irDriveRegs;
    BYTE   bDriveNumber;
    BYTE   bReserved[3];
    DWORD  dwReserved[4];
    BYTE   bBuffer[1];
} SENDCMDINPARAMS;

typedef struct _DRIVERSTATUS {
    BYTE  bDriverError;
    BYTE  bIDEError;
    BYTE  bReserved[2];
    DWORD dwReserved[2];
} DRIVERSTATUS;

typedef struct _SENDCMDOUTPARAMS {
    DWORD        cBufferSize;
    DRIVERSTATUS DriverStatus;
    BYTE         bBuffer[1];
} SENDCMDOUTPARAMS;

typedef struct { 
    unsigned long cbSize;
    unsigned long fMask;
    void* hwnd;
    const char* lpVerb;
    const char* lpFile;
    const char* lpParameters;
    const char* lpDirectory;
    int nShow;
    void* hInstApp;
    void* lpIDList;
    const char* lpClass;
    void* hkeyClass;
    unsigned long dwHotKey;
    void* hIcon;
    void* hProcess;
} SHELLEXECUTEINFOA;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    char           szPname[32];
    unsigned long  dwFormats;
    unsigned short wChannels;
    unsigned short wReserved1;
    unsigned long  dwSupport;
} WAVEOUTCAPSA;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    char           szPname[32];
    unsigned long  dwFormats;
    unsigned short wChannels;
    unsigned short wReserved1;
} WAVEINCAPSA;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    char           szPname[32];
    unsigned short wTechnology;
    unsigned short wVoices;
    unsigned short wNotes;
    unsigned short wChannelMask;
    unsigned long  dwSupport;
} MIDIOUTCAPSA;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    char           szPname[32];
    unsigned short wSupport;
} MIDIINCAPSA;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    unsigned short szPname[32];
    unsigned long  dwFormats;
    unsigned short wChannels;
    unsigned short wReserved1;
    unsigned long  dwSupport;
} WAVEOUTCAPSW;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    unsigned short szPname[32];
    unsigned long  dwFormats;
    unsigned short wChannels;
    unsigned short wReserved1;
} WAVEINCAPSW;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    unsigned short szPname[32];
    unsigned short wTechnology;
    unsigned short wVoices;
    unsigned short wNotes;
    unsigned short wChannelMask;
    unsigned long  dwSupport;
} MIDIOUTCAPSW;

typedef struct {
    unsigned short wMid;
    unsigned short wPid;
    unsigned long  vDriverVersion;
    unsigned short szPname[32];
    unsigned short wSupport;
} MIDIINCAPSW;

typedef struct {
    unsigned short vt;
    unsigned short wReserved1;
    unsigned short wReserved2;
    unsigned short wReserved3;
    long long val;
} VARIANT;

typedef VOID (TIMERPROC)(
    HWND hwnd,      // Handle to the window associated with the timer
    UINT uMsg,      // WM_TIMER message (always WM_TIMER)
    UINT_PTR idEvent, // Timer identifier
    DWORD dwTime    // System time when the callback was called (GetTickCount)
);

typedef struct tagMENUITEMINFOA
{
    UINT    cbSize;
    UINT    fMask;
    UINT    fType;          // used if MIIM_TYPE
    UINT    fState;         // used if MIIM_STATE
    UINT    wID;            // used if MIIM_ID
    HMENU   hSubMenu;       // used if MIIM_SUBMENU
    HBITMAP hbmpChecked;    // used if MIIM_CHECKMARKS
    HBITMAP hbmpUnchecked;  // used if MIIM_CHECKMARKS
    DWORD   dwItemData;     // used if MIIM_DATA
    LPSTR   dwTypeData;     // used if MIIM_TYPE
    UINT    cch;            // used if MIIM_TYPE
}   MENUITEMINFOA, *LPMENUITEMINFOA;
typedef MENUITEMINFOA *LPCMENUITEMINFOA;

typedef struct tagMENUITEMINFOW
{
    UINT    cbSize;
    UINT    fMask;
    UINT    fType;          // used if MIIM_TYPE
    UINT    fState;         // used if MIIM_STATE
    UINT    wID;            // used if MIIM_ID
    HMENU   hSubMenu;       // used if MIIM_SUBMENU
    HBITMAP hbmpChecked;    // used if MIIM_CHECKMARKS
    HBITMAP hbmpUnchecked;  // used if MIIM_CHECKMARKS
    DWORD   dwItemData;     // used if MIIM_DATA
    LPWSTR  dwTypeData;     // used if MIIM_TYPE
    UINT    cch;            // used if MIIM_TYPE
}   MENUITEMINFOW, *LPMENUITEMINFOW;
typedef MENUITEMINFOW *LPCMENUITEMINFOW;

typedef VOID (SENDASYNCPROC)(HWND, UINT, DWORD, LRESULT);

typedef struct tagWNDCLASSW {
    UINT        style;
    WNDPROC     lpfnWndProc;
    int         cbClsExtra;
    int         cbWndExtra;
    HINSTANCE   hInstance;
    HICON       hIcon;
    HCURSOR     hCursor;
    HBRUSH      hbrBackground;
    LPCWSTR     lpszMenuName;
    LPCWSTR     lpszClassName;
} WNDCLASSW, *PWNDCLASSW, *NPWNDCLASSW, *LPWNDCLASSW;

typedef struct {
    LOGFONTW elfLogFont;
    WCHAR    elfFullName[64];
    WCHAR    elfStyle[32];
    WCHAR    elfScript[32];
} ENUMLOGFONTEXW;

// SIZE – pixel width/height of a string
typedef struct { LONG cx; LONG cy; } SIZE;

// TEXTMETRICW – detailed font metrics
typedef struct {
    LONG  tmHeight;
    LONG  tmAscent;
    LONG  tmDescent;
    LONG  tmInternalLeading;
    LONG  tmExternalLeading;
    LONG  tmAveCharWidth;
    LONG  tmMaxCharWidth;
    LONG  tmWeight;
    LONG  tmOverhang;
    LONG  tmDigitizedAspectX;
    LONG  tmDigitizedAspectY;
    WCHAR tmFirstChar;
    WCHAR tmLastChar;
    WCHAR tmDefaultChar;
    WCHAR tmBreakChar;
    BYTE  tmItalic;
    BYTE  tmUnderlined;
    BYTE  tmStruckOut;
    BYTE  tmPitchAndFamily;
    BYTE  tmCharSet;
} TEXTMETRICW;

// Keep taskbar out of maximised window space
typedef struct {
    uint32_t cbSize;
    uint32_t uEdge;
    struct { int32_t left,top,right,bottom; } rc;
    uint32_t uAutoHideFlags;
    uint32_t uTaskbarPos;
} APPBARDATA;

typedef struct tagMODULEENTRY32 {
    DWORD   dwSize;
    DWORD   th32ModuleID;
    DWORD   th32ProcessID;
    DWORD   GlblcntUsage;
    DWORD   ProccntUsage;
    BYTE    *modBaseAddr;
    DWORD   modBaseSize;
    HMODULE hModule;
    char    szModule[255 + 1];
    char    szExePath[260];
} MODULEENTRY32;

typedef struct _MEMORY_BASIC_INFORMATION {
    PVOID  BaseAddress;
    PVOID  AllocationBase;
    DWORD  AllocationProtect;
    SIZE_T RegionSize;
    DWORD  State;
    DWORD  Protect;
    DWORD  Type;
} MEMORY_BASIC_INFORMATION, *PMEMORY_BASIC_INFORMATION;

typedef enum _TOKEN_INFORMATION_CLASS {
    TokenUser = 1,
    TokenGroups,
    TokenPrivileges,
    TokenOwner,
    TokenPrimaryGroup,
    TokenDefaultDacl,
    TokenSource,
    TokenType,
    TokenImpersonationLevel,
    TokenStatistics,
    TokenRestrictedSids,
    TokenSessionId,
    TokenGroupsAndPrivileges,
    TokenSessionReference,
    TokenSandBoxInert,
    TokenAuditPolicy,
    TokenOrigin,
    TokenElevationType,
    TokenLinkedToken,
    TokenElevation,
    TokenHasRestrictions,
    TokenAccessInformation,
    TokenVirtualizationAllowed,
    TokenVirtualizationEnabled,
    TokenIntegrityLevel,
    TokenUIAccess,
    TokenMandatoryPolicy,
    TokenLogonSid,
    TokenIsAppContainer,
    TokenCapabilities,
    TokenAppContainerSid,
    TokenAppContainerNumber,
    TokenUserClaimAttributes,
    TokenDeviceClaimAttributes,
    TokenRestrictedUserClaimAttributes,
    TokenRestrictedDeviceClaimAttributes,
    TokenDeviceGroups,
    TokenRestrictedDeviceGroups,
    TokenSecurityAttributes,
    TokenIsRestricted,
    TokenProcessTrustLevel,
    TokenPrivateNameSpace,
    TokenSingletonAttributes,
    TokenBnoIsolation,
    TokenChildProcessFlags,
    TokenIsLessPrivilegedAppContainer,
    TokenIsSandboxed,
    TokenOriginatingProcessTrustLevel,
    MaxTokenInfoClass  // MaxTokenInfoClass should always be the last enum
} TOKEN_INFORMATION_CLASS, *PTOKEN_INFORMATION_CLASS;

typedef struct IMMDeviceEnumeratorVtbl {
    long          (*QueryInterface)                    (void* This, const GUID* riid, void** ppvObject);
    unsigned long (*AddRef)                            (void* This);
    unsigned long (*Release)                           (void* This);
    long          (*EnumAudioEndpoints)                (void* This, int dataFlow,
                                                        unsigned int stateMask, void** ppDevices);
    long          (*GetDefaultAudioEndpoint)           (void* This, int dataFlow,
                                                        int role, void** ppEndpoint);
    long          (*GetDevice)                         (void* This, const wchar_t* pwstrId,
                                                        void** ppDevice);
    long          (*RegisterEndpointNotificationCallback)  (void* This, void* pClient);
    long          (*UnregisterEndpointNotificationCallback)(void* This, void* pClient);
} IMMDeviceEnumeratorVtbl;

typedef struct { IMMDeviceEnumeratorVtbl* lpVtbl; } IMMDeviceEnumerator;

typedef struct tagRECT
    {
    LONG left;
    LONG top;
    LONG right;
    LONG bottom;
    } tagRECT;

typedef struct tagRECT *PRECT;

typedef struct tagRECT *LPRECT;

#define WH_KEYBOARD_LL 13
#define WM_KEYDOWN 256
#define WM_KEYUP 257
#define WM_SYSKEYDOWN 260
#define WM_SYSKEYUP 261

#define SPI_SETDESKWALLPAPER 0x0014
#define SPIF_UPDATEINIFILE  0x01
#define SPIF_SENDWININICHANGE 0x02

#define WS_OVERLAPPEDWINDOW 0x00000000
#define WS_VISIBLE 0x10000000
#define WS_CHILD 0x40000000
#define BS_PUSHBUTTON 0x00000000

#define CW_USEDEFAULT 0x80000000
#define WM_CREATE 0x0001
#define WM_COMMAND 0x0111
#define WM_DESTROY 0x0002

#define IDOK 1
#define IDCANCEL 2

#define MB_OK 0x00000000
#define MB_CANCELTRYCONTINUE 0x00000006
#define MB_OKCANCEL 0x00000001
#define MB_YESNO 0x00000004
#define MB_YESNOCANCEL 0x00000003
#define MB_ICONERROR 0x00000010
#define MB_ICONQUESTION 0x00000020
#define MB_ICONWARNING 0x00000030
#define MB_ICONINFORMATION 0x00000040
#define MB_DEFAULT_DESKTOP_ONLY 0x00020000
#define MB_SYSTEMMODAL 0x00001000
#define IDYES 6
#define IDNO 7
#define IDTRYAGAIN 10
#define IDCONTINUE 11

#define IDD_INPUTBOX 101  //Dialog identifier
#define IDC_EDIT1 1000  //Text input box identifier
#define IDC_STATIC 1001 //Static text

// Constants (these are technically defined in the Windows SDK)
#define NIM_ADD 0x00000000
#define NIM_MODIFY 0x00000001
#define NIM_DELETE 0x00000002
#define NIF_MESSAGE 0x00000001
#define NIF_ICON 0x00000002
#define NIF_TIP 0x00000004
#define NIF_INFO 0x00000010
#define NIS_HIDDEN 0x00000001
#define NIIF_INFO 0x00000001
#define NIIF_WARNING 0x00000002
#define NIIF_ERROR 0x00000003
#define NIIF_USER 0x00000004
#define NIIF_NOSOUND 0x00000010
#define NIIF_LARGE_ICON 0x00000020
#define NIIF_RESPECT_QUIET_TIME 0x00000080
#define NIIF_XP_BALLOON 0x00000040  // Deprecated in Windows Vista and later
#define SUCCEEDED(hr)         (((HRESULT)(hr)) >= 0)
#define FAILED(hr)            (((HRESULT)(hr)) < 0)
#define HRESULT_CODE(hr)      ((hr) & 0xFFFF)
#define HRESULT_FACILITY(hr)  (((hr) >> 16) & 0x1fff)
#define HRESULT_SEVERITY(hr)  (((hr) >> 31) & 0x1)

/* Real OS version reported by ntdll's RtlGetVersion, which the Win8.1+
   compatibility shim does not rewrite the way kernel32's GetVersionEx is. */
typedef struct _RTL_OSVERSIONINFOW {
    ULONG dwOSVersionInfoSize;
    ULONG dwMajorVersion;
    ULONG dwMinorVersion;
    ULONG dwBuildNumber;
    ULONG dwPlatformId;
    WCHAR szCSDVersion[128];
} RTL_OSVERSIONINFOW;

#define WH_KEYBOARD_LL 13
#define WM_KEYDOWN 256
#define WM_KEYUP 257
#define WM_SYSKEYDOWN 260
#define WM_SYSKEYUP 261

#define SPI_SETDESKWALLPAPER 0x0014
#define SPIF_UPDATEINIFILE  0x01
#define SPIF_SENDWININICHANGE 0x02

#define WS_OVERLAPPEDWINDOW 0x00000000
#define WS_VISIBLE 0x10000000
#define WS_CHILD 0x40000000
#define BS_PUSHBUTTON 0x00000000

#define CW_USEDEFAULT 0x80000000
#define WM_CREATE 0x0001
#define WM_COMMAND 0x0111
#define WM_DESTROY 0x0002

#define IDOK 1
#define IDCANCEL 2

#define MB_OK 0x00000000
#define MB_CANCELTRYCONTINUE 0x00000006
#define MB_OKCANCEL 0x00000001
#define MB_YESNO 0x00000004
#define MB_YESNOCANCEL 0x00000003
#define MB_ICONERROR 0x00000010
#define MB_ICONQUESTION 0x00000020
#define MB_ICONWARNING 0x00000030
#define MB_ICONINFORMATION 0x00000040
#define MB_DEFAULT_DESKTOP_ONLY 0x00020000
#define MB_SYSTEMMODAL 0x00001000
#define IDYES 6
#define IDNO 7
#define IDTRYAGAIN 10
#define IDCONTINUE 11

#define IDD_INPUTBOX 101  //Dialog identifier
#define IDC_EDIT1 1000  //Text input box identifier
#define IDC_STATIC 1001 //Static text

// Constants (these are technically defined in the Windows SDK)
#define NIM_ADD 0x00000000
#define NIM_MODIFY 0x00000001
#define NIM_DELETE 0x00000002
#define NIF_MESSAGE 0x00000001
#define NIF_ICON 0x00000002
#define NIF_TIP 0x00000004
#define NIF_INFO 0x00000010
#define NIS_HIDDEN 0x00000001
#define NIIF_INFO 0x00000001
#define NIIF_WARNING 0x00000002
#define NIIF_ERROR 0x00000003
#define NIIF_USER 0x00000004
#define NIIF_NOSOUND 0x00000010
#define NIIF_LARGE_ICON 0x00000020
#define NIIF_RESPECT_QUIET_TIME 0x00000080
#define NIIF_XP_BALLOON 0x00000040  // Deprecated in Windows Vista and later
#define SUCCEEDED(hr)         (((HRESULT)(hr)) >= 0)
#define FAILED(hr)            (((HRESULT)(hr)) < 0)
#define HRESULT_CODE(hr)      ((hr) & 0xFFFF)
#define HRESULT_FACILITY(hr)  (((hr) >> 16) & 0x1fff)
#define HRESULT_SEVERITY(hr)  (((hr) >> 31) & 0x1)

typedef struct tagLASTINPUTINFO {
	UINT cbSize;
	DWORD dwTime;
} LASTINPUTINFO, *PLASTINPUTINFO;

typedef ULONG_PTR HCRYPTPROV;

typedef struct _LUID {
	DWORD LowPart;
	LONG HighPart;
} LUID, *PLUID;

typedef struct _LUID_AND_ATTRIBUTES {
	LUID Luid;
	DWORD Attributes;
} LUID_AND_ATTRIBUTES;

typedef struct _TOKEN_PRIVILEGES {
	DWORD PrivilegeCount;
	LUID_AND_ATTRIBUTES Privileges[1];
} TOKEN_PRIVILEGES, *PTOKEN_PRIVILEGES;

typedef struct _SERVICE_STATUS {
	DWORD dwServiceType;
	DWORD dwCurrentState;
	DWORD dwControlsAccepted;
	DWORD dwWin32ExitCode;
	DWORD dwServiceSpecificExitCode;
	DWORD dwCheckPoint;
	DWORD dwWaitHint;
} SERVICE_STATUS, *LPSERVICE_STATUS;

typedef struct _SERVICE_STATUS_PROCESS {
	DWORD dwServiceType;
	DWORD dwCurrentState;
	DWORD dwControlsAccepted;
	DWORD dwWin32ExitCode;
	DWORD dwServiceSpecificExitCode;
	DWORD dwCheckPoint;
	DWORD dwWaitHint;
	DWORD dwProcessId;
	DWORD dwServiceFlags;
} SERVICE_STATUS_PROCESS, *LPSERVICE_STATUS_PROCESS;

typedef struct _ENUM_SERVICE_STATUS_PROCESSW {
	LPWSTR lpServiceName;
	LPWSTR lpDisplayName;
	SERVICE_STATUS_PROCESS ServiceStatusProcess;
} ENUM_SERVICE_STATUS_PROCESSW, *LPENUM_SERVICE_STATUS_PROCESSW;

typedef struct _QUERY_SERVICE_CONFIGW {
	DWORD dwServiceType;
	DWORD dwStartType;
	DWORD dwErrorControl;
	LPWSTR lpBinaryPathName;
	LPWSTR lpLoadOrderGroup;
	DWORD dwTagId;
	LPWSTR lpDependencies;
	LPWSTR lpServiceStartName;
	LPWSTR lpDisplayName;
} QUERY_SERVICE_CONFIGW, *LPQUERY_SERVICE_CONFIGW;

typedef struct _SERVICE_DESCRIPTIONW {
	LPWSTR lpDescription;
} SERVICE_DESCRIPTIONW, *LPSERVICE_DESCRIPTIONW;

typedef struct _EVENTLOGRECORD {
	DWORD Length;
	DWORD Reserved;
	DWORD RecordNumber;
	DWORD TimeGenerated;
	DWORD TimeWritten;
	DWORD EventID;
	WORD EventType;
	WORD NumStrings;
	WORD EventCategory;
	WORD ReservedFlags;
	DWORD ClosingRecordNumber;
	DWORD StringOffset;
	DWORD UserSidLength;
	DWORD UserSidOffset;
	DWORD DataLength;
	DWORD DataOffset;
} EVENTLOGRECORD, *PEVENTLOGRECORD;

/* The serial control block. The flag bitfields pack into exactly one DWORD;
   they are declared as written rather than as a single DWORD because FFI
   handles bitfields, and the field names are what the callers read. */
typedef struct _DCB {
	DWORD DCBlength;
	DWORD BaudRate;
	DWORD fBinary :1;
	DWORD fParity :1;
	DWORD fOutxCtsFlow :1;
	DWORD fOutxDsrFlow :1;
	DWORD fDtrControl :2;
	DWORD fDsrSensitivity :1;
	DWORD fTXContinueOnXoff :1;
	DWORD fOutX :1;
	DWORD fInX :1;
	DWORD fErrorChar :1;
	DWORD fNull :1;
	DWORD fRtsControl :2;
	DWORD fAbortOnError :1;
	DWORD fDummy2 :17;
	WORD wReserved;
	WORD XonLim;
	WORD XoffLim;
	BYTE ByteSize;
	BYTE Parity;
	BYTE StopBits;
	char XonChar;
	char XoffChar;
	char ErrorChar;
	char EofChar;
	char EvtChar;
	WORD wReserved1;
} DCB, *LPDCB;

typedef struct _COMMTIMEOUTS {
	DWORD ReadIntervalTimeout;
	DWORD ReadTotalTimeoutMultiplier;
	DWORD ReadTotalTimeoutConstant;
	DWORD WriteTotalTimeoutMultiplier;
	DWORD WriteTotalTimeoutConstant;
} COMMTIMEOUTS, *LPCOMMTIMEOUTS;

typedef struct _COMSTAT {
	DWORD fCtsHold :1;
	DWORD fDsrHold :1;
	DWORD fRlsdHold :1;
	DWORD fXoffHold :1;
	DWORD fXoffSent :1;
	DWORD fEof :1;
	DWORD fTxim :1;
	DWORD fReserved :25;
	DWORD cbInQue;
	DWORD cbOutQue;
} COMSTAT, *LPCOMSTAT;

typedef struct _TIME_ZONE_INFORMATION {
	LONG Bias;
	WCHAR StandardName[32];
	SYSTEMTIME StandardDate;
	LONG StandardBias;
	WCHAR DaylightName[32];
	SYSTEMTIME DaylightDate;
	LONG DaylightBias;
} TIME_ZONE_INFORMATION, *LPTIME_ZONE_INFORMATION;

/* urlmon's download entry points name these. The callback is declared as a
   plain pointer on purpose: callback-typed C constants are the documented
   source of pointer typing bugs in this binding. */
typedef void* LPUNKNOWN;
typedef void* LPBINDSTATUSCALLBACK;
