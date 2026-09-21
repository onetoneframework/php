BOOL AllocConsole(void);
BOOL Beep(DWORD dwFreq, DWORD dwDuration);
BOOL CloseConsoleHandle(HANDLE handle);
BOOL CloseHandle(HANDLE hObject);
BOOL CreateDirectoryA(LPCSTR lpPathName, LPSECURITY_ATTRIBUTES lpSecurityAttributes);
BOOL CreateDirectoryW(LPCWSTR lpPathName, LPSECURITY_ATTRIBUTES lpSecurityAttributes);
BOOL CreatePipe(DWORD phReadPipe, DWORD phWritePipe, DWORD lpPipeAttributes, DWORD nSize);
BOOL CreateProcessA(LPCSTR lpApplicationName, LPCSTR lpCommandLine, void *lpProcessAttributes, void *lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, void *lpEnvironment, LPCSTR lpCurrentDirectory, void *lpStartupInfo, void *lpProcessInformation);
BOOL CreateProcessW(LPCWSTR lpApplicationName, LPWSTR lpCommandLine, LPSECURITY_ATTRIBUTES lpProcessAttributes, LPSECURITY_ATTRIBUTES lpThreadAttributes, BOOL bInheritHandles, DWORD dwCreationFlags, LPVOID lpEnvironment, LPCWSTR lpCurrentDirectory, LPSTARTUPINFOW lpStartupInfo, LPPROCESS_INFORMATION lpProcessInformation);
BOOL DebugActiveProcess(DWORD dwProcessId);
BOOL DebugActiveProcessStop(DWORD dwProcessId);
BOOL DeleteFileA(LPCSTR lpFileName);
BOOL DeleteFileW(LPCWSTR lpFileName);
BOOL DeviceIoControl(HANDLE hDevice, DWORD dwIoControlCode, LPVOID lpInBuffer, DWORD nInBufferSize, LPVOID lpOutBuffer, DWORD nOutBufferSize, LPDWORD lpBytesReturned, LPOVERLAPPED lpOverlapped);
BOOL DisconnectNamedPipe(DWORD hNamedPipe);
BOOL DnsHostnameToComputerNameA(LPCSTR hostname, LPSTR computername, LPDWORD size);
BOOL DnsHostnameToComputerNameW(LPCWSTR hostname, LPWSTR computername, LPDWORD size);
BOOL DuplicateHandle(DWORD hSourceProcessHandle, DWORD hSourceHandle, DWORD hTargetProcessHandle, DWORD lpTargetHandle, DWORD dwDesiredAccess, DWORD bInheritHandle, DWORD dwOptions);
BOOL EnumSystemCodePagesA( CODEPAGE_ENUMPROCA proc, DWORD flags );
BOOL FindClose(HANDLE hFindFile);
BOOL FindCloseChangeNotification(HANDLE hChangeHandle);
BOOL FindNextFileA(void *hFindFile, WIN32_FIND_DATAA *lpFindFileData);
BOOL FindNextVolumeA(HANDLE hFindVolume, LPTSTR lpszVolumeName, DWORD cchBufferLength);
BOOL FindVolumeClose(HANDLE hFindVolume);
BOOL FlushFileBuffers(HANDLE hFile);
BOOL GetBinaryType(LPCSTR lpApplicationName, LPDWORD lpBinaryType);
BOOL GetBinaryTypeW(LPCWSTR lpApplicationName, LPDWORD lpBinaryType);
BOOL GetConsoleFontInfo(HANDLE hConsole, BOOL maximize, DWORD numfonts, CONSOLE_FONT_INFO *info);
BOOL GetConsoleKeyboardLayoutNameA(LPSTR layoutName);
BOOL GetConsoleKeyboardLayoutNameW(LPWSTR layoutName);
BOOL GetConsoleMode(HANDLE hConsoleHandle, LPDWORD lpMode);
BOOL GetConsoleScreenBufferInfo(HANDLE hConsoleOutput, CONSOLE_SCREEN_BUFFER_INFO *lpConsoleScreenBufferInfo);
BOOL GetCPInfoExA( UINT codepage, DWORD dwFlags, LPCPINFOEXA cpinfo );
BOOL GetDiskFreeSpaceExA(LPCSTR lpDirectoryName, PULARGE_INTEGER lpFreeBytesAvailable, PULARGE_INTEGER lpTotalNumberOfBytes, PULARGE_INTEGER lpTotalNumberOfFreeBytes);
BOOL GetExitCodeThread(HANDLE hThread, void *lpExitCode);
BOOL GetNumberOfConsoleInputEvents(HANDLE hConsoleInput, LPDWORD lpcNumberOfEvents);
BOOL GetQueuedCompletionStatus(HANDLE CompletionPort, LPDWORD lpNumberOfBytesTransferred, PULONG_PTR lpCompletionKey, LPOVERLAPPED *lpOverlapped, DWORD dwMilliseconds);
BOOL GetStringTypeExA( LCID locale, DWORD type, LPCSTR src, INT count, LPWORD chartype );
BOOL GetSystemPowerStatus(SYSTEM_POWER_STATUS *lpSystemPowerStatus);
BOOL GetSystemRegistryQuota(PDWORD pdwQuotaAllowed, PDWORD pdwQuotaUsed);
BOOL GlobalAlloc(DWORD NumberOfHeaps, PHANDLE ProcessHeaps);
BOOL GlobalLock(DWORD hMem);
BOOL GlobalSize(BOOL hMem);
BOOL GlobalUnlock(DWORD hMem);
BOOL IsWow64Process(HANDLE hProcess, PBOOL Wow64Process);
BOOL Module32First(HANDLE hSnapshot, void *moduleEntryPtr);
BOOL Module32Next(HANDLE hSnapshot, void *moduleEntryPtr);
BOOL Process32First(HANDLE hSnapshot, PROCESSENTRY32 processEntryPtr);
BOOL Process32Next(HANDLE hSnapshot, PROCESSENTRY32 processEntryPtr);
BOOL ReadConsoleInputA(HANDLE hConsoleInput, PINPUT_RECORD lpBuffer, DWORD nLength, LPDWORD lpNumberOfEventsRead);
BOOL ReadConsoleInputW(HANDLE hConsoleInput, PINPUT_RECORD lpBuffer, DWORD nLength, LPDWORD lpNumberOfEventsRead);
BOOL ReadDirectoryChangesW(HANDLE hDirectory, LPVOID lpBuffer, DWORD nBufferLength, BOOL bWatchSubtree, DWORD dwNotifyFilter, LPDWORD lpBytesReturned, LPOVERLAPPED lpOverlapped, LPOVERLAPPED_COMPLETION_ROUTINE lpCompletionRoutine);
BOOL ReadFile(HANDLE hFile, LPVOID lpBuffer, DWORD nNumberOfBytesToRead, DWORD *lpNumberOfBytesRead, LPVOID lpOverlapped);
BOOL ReadProcessMemory(HANDLE hProcess, uint64_t lpBaseAddress, void *lpBuffer, SIZE_T nSize, uint64_t lpNumberOfBytesRead);
BOOL ReleaseMutex(HANDLE hMutex);
BOOL RtlMoveMemory(BOOL pDest, BOOL pSource, BOOL l);
BOOL SetConsoleCursorPosition(HANDLE hConsoleOutput, COORD dwCursorPosition);
BOOL SetConsoleFont(HANDLE hConsole, DWORD index);
BOOL SetConsoleIcon(HICON icon);
BOOL SetConsoleKeyShortcuts(BOOL set, BYTE keys, VOID *a, DWORD b);
BOOL SetConsoleMode(HANDLE hConsoleHandle, DWORD dwMode);
BOOL SetConsoleOutputCP(UINT wCodePageID);
BOOL SetLocaleInfoA(LCID lcid, LCTYPE lctype, LPCSTR data);
BOOL SetProcessWorkingSetSize(HANDLE hProcess, SIZE_T dwMinimumWorkingSetSize, SIZE_T dwMaximumWorkingSetSize);
BOOL SetWaitableTimer(HANDLE hTimer, LARGE_INTEGER* lpDueTime, LONG lPeriod, void* pfnCompletionRoutine, void* lpArgToCompletionRoutine, BOOL fResume);
BOOL TerminateProcess(HANDLE hProcess, UINT uExitCode);
BOOL VerifyConsoleIoHandle(HANDLE handle);
BOOL VirtualFreeEx(HANDLE hProcess, uint64_t lpAddress, SIZE_T dwSize, DWORD dwFreeType);
BOOL WriteFile(HANDLE hFile, LPCVOID lpBuffer, DWORD nNumberOfBytesToWrite, LPDWORD lpNumberOfBytesWritten, LPOVERLAPPED lpOverlapped);
BOOL WriteProcessMemory(HANDLE hProcess, uint64_t lpBaseAddress, void *lpBuffer, SIZE_T nSize, uint64_t lpNumberOfBytesWritten);
DWORD GetCompressedFileSizeW(LPCTSTR lpFileName, LPDWORD lpFileSizeHigh);
DWORD GetCurrentDirectoryA(DWORD nBufferLength, LPSTR lpBuffer);
DWORD GetCurrentProcessId();
DWORD GetCurrentThreadId();
DWORD GetFileAttributesW(LPCWSTR lpFileName);
DWORD GetFullPathNameW(LPCTSTR lpFileName, DWORD nBufferLength, LPTSTR lpBuffer, LPTSTR *lpFilePart);
DWORD GetLastError();
DWORD GetLogicalDrives();
DWORD GetModuleFileNameW(HANDLE hModule, wchar_t *lpFilename, DWORD nSize);
DWORD GetNumberOfConsoleFonts(void);
DWORD GetPriorityClass(HANDLE hProcess);
DWORD GetProcessHeaps(DWORD NumberOfHeaps, PHANDLE ProcessHeaps);
DWORD GetTickCount(VOID);
DWORD GetVersion(VOID);
DWORD HeapSize(HANDLE hHeap, DWORD dwFlags, LPCVOID lpMem);
DWORD WaitForSingleObject(HANDLE hHandle, DWORD dwMilliseconds); // Synchapi.h
HANDLE CreateFileA(LPCSTR lpFileName, DWORD dwDesiredAccess, DWORD dwShareMode, LPVOID lpSecurityAttributes, DWORD dwCreationDisposition, DWORD dwFlagsAndAttributes, HANDLE hTemplateFile);
HANDLE CreateIoCompletionPort(HANDLE FileHandle, HANDLE ExistingCompletionPort, ULONG_PTR CompletionKey, DWORD NumberOfConcurrentThreads);
HANDLE CreateMutexA(void *lpMutexAttributes, BOOL bInitialOwner, const char *lpName);
HANDLE CreateRemoteThread(HANDLE hProcess, uint64_t lpThreadAttributes, SIZE_T dwStackSize, uint64_t lpStartAddress, uint64_t lpParameter, DWORD dwCreationFlags, uint64_t lpThreadId);
HANDLE CreateRemoteThreadEx(HANDLE hProcess, LPSECURITY_ATTRIBUTES lpThreadAttributes, SIZE_T dwStackSize, LPTHREAD_START_ROUTINE lpStartAddress, LPVOID lpParameter, DWORD dwCreationFlags, LPPROC_THREAD_ATTRIBUTE_LIST lpAttributeList, LPDWORD lpThreadId);
HANDLE CreateToolhelp32Snapshot(DWORD dwFlags, DWORD th32ProcessID);
HANDLE CreateWaitableTimerW(void* lpTimerAttributes, BOOL bManualReset, void* lpTimerName);
HANDLE DuplicateConsoleHandle(HANDLE handle, DWORD access, BOOL inherit, DWORD options);
HANDLE FindFirstVolumeMountPointW(LPTSTR lpszRootPathName, LPTSTR lpszVolumeMountPoint, DWORD cchBufferLength);
HANDLE GetConsoleInputWaitHandle(void);
HANDLE GetCurrentProcess();
HANDLE GetCurrentThread(VOID);
HANDLE GetProcessHeap(VOID);
HANDLE GetStdHandle(DWORD nStdHandle);
HANDLE OpenConsoleW(LPCWSTR name, DWORD access, BOOL inherit, DWORD creation);
HANDLE OpenProcess(DWORD dwDesiredAccess, BOOL bInheritHandle, DWORD dwProcessId);
HINSTANCE GetModuleHandleA(const char *lpModuleName);
HINSTANCE GetModuleHandleW(const wchar_t *lpModuleName);
HINSTANCE GetModuleHandleW(LPCWSTR lpModuleName);
HMODULE LoadLibraryA(const char *lpLibFileName);
HWND GetConsoleWindow();
HANDLE CreateFileW(LPCWSTR lpFileName, DWORD dwDesiredAccess, DWORD dwShareMode, LPVOID lpSecurityAttributes, DWORD dwCreationDisposition, DWORD dwFlagsAndAttributes, HANDLE hTemplateFile);
INT FoldStringA(DWORD dwFlags, LPCSTR src, INT srclen, LPSTR dst, INT dstlen);
int FreeLibrary(HMODULE hLibModule);
int GetComputerNameA(char *lpBuffer, int *lpnSize);
int GetSystemDefaultLocaleName(LPWSTR lpLocaleName, int cchLocaleName);
int GetThreadPriority(HANDLE hThread);
int MultiByteToWideChar(UINT CodePage, DWORD dwFlags, const char *lpMultiByteStr, int cbMultiByte, WCHAR *lpWideCharStr, int cchWideChar);
int NormalizeString(NORM_FORM NormForm, LPCWSTR lpSrcString, int cwSrcLength, LPWSTR lpDstString, int cwDstLength);
int RegCloseKey(void *hKey);
int RegEnumKeyExA(void *hKey, unsigned long dwIndex, char *lpName, unsigned long *lpcchName, void *lpReserved, char *lpClass, unsigned long *lpcchClass, void *lpftLastWriteTime);
int RegOpenKeyExA(void *hKey, const char *lpSubKey, unsigned int ulOptions, int samDesired, void **phkResult);
int RegQueryValueExA(void *hKey, const char *lpValueName, void *lpReserved, int *lpType, void *lpData, int *lpcbData);
LANGID GetThreadUILanguage(void);
LANGID GetUserDefaultLangID();
long GetProcessId(long hWnd);
long GetWindowsDirectoryA(LPSTR lpBuffer, long nSize);
//LPCSTR FormatMessageA(DWORD dwFlags, LPCVOID lpSource, DWORD dwMessageId, DWORD dwLanguageId, LPSTR lpBuffer, DWORD nSize, va_list *Arguments);
unsigned long FormatMessageA(unsigned long, void*, unsigned long, unsigned long, char*, unsigned long, void*);
unsigned long FormatMessageW(unsigned long, void*, unsigned long, unsigned long, wchar_t*, unsigned long, void*);
//DWORD FormatMessageW(DWORD dwFlags, LPCVOID lpSource, DWORD dwMessageId, DWORD dwLanguageId, LPTSTR lpBuffer, DWORD nSize, va_list *Arguments);

LPVOID GetEnvironmentStrings(VOID);
SIZE_T GetFileSize(HANDLE hFile, DWORD *lpFileSizeHigh);
static BOOL process_attach( HMODULE module );
static HANDLE create_file_OF( LPCSTR path, INT mode );
static void copy_startup_info(void);
UINT GetACP(VOID);
UINT GetErrorMode(void);
UINT GetOEMCP(void);
uint64_t VirtualAllocEx(HANDLE hProcess, uint64_t lpAddress, SIZE_T dwSize, DWORD flAllocationType, DWORD flProtect);
void *FindFirstFileA(const char *lpFileName, WIN32_FIND_DATAA *lpFindFileData);
void *GetProcAddress(HMODULE hModule, const char *lpProcName);
VOID ExitProcess(UINT uExitCode);
void GetLocalTime(LPSYSTEMTIME lpSystemTime);
VOID GetStartupInfoA( LPSTARTUPINFOA info );
void GetSystemInfo(SYSTEM_INFO* lpSystemInfo);
void QueryFullProcessImageNameA(HANDLE hProc, int n, char* buffer, DWORD* buffer_size);
VOID Sleep(DWORD dwMilliseconds);
BOOL Process32FirstW(HANDLE hSnapshot, PROCESSENTRY32W* lppe);
BOOL Process32NextW(HANDLE hSnapshot, PROCESSENTRY32W* lppe);
int GlobalMemoryStatusEx(MEMORYSTATUSEX*);
SIZE_T VirtualQueryEx(HANDLE hProcess, LPCVOID lpAddress,PMEMORY_BASIC_INFORMATION lpBuffer, SIZE_T dwLength);

BOOL GetSystemTimes(FILETIME* lpIdleTime, FILETIME* lpKernelTime, FILETIME* lpUserTime);

BOOL GetFirmwareType(LPDWORD FirmwareType);

BOOL GetProductInfo(DWORD dwOSMajorVersion, DWORD dwOSMinorVersion, DWORD dwSpMajorVersion, DWORD dwSpMinorVersion, LPDWORD pdwReturnedProductType);

BOOL GetVolumeInformationW(wchar_t* lpRootPathName, wchar_t* lpVolumeNameBuffer, DWORD nVolumeNameSize, LPDWORD lpVolumeSerialNumber, LPDWORD lpMaximumComponentLength, LPDWORD lpFileSystemFlags, wchar_t* lpFileSystemNameBuffer, DWORD nFileSystemNameSize);

/* Declarations for calls WindowsAPI already makes. */
LONG CompareFileTime(FILETIME* lpFileTime1, FILETIME* lpFileTime2);

BOOL ConnectNamedPipe(HANDLE hNamedPipe, OVERLAPPED* lpOverlapped);

BOOL CopyFileA(LPCSTR lpExistingFileName, LPCSTR lpNewFileName, BOOL bFailIfExists);

HANDLE CreateEventA(SECURITY_ATTRIBUTES* lpEventAttributes, BOOL bManualReset, BOOL bInitialState, LPCSTR lpName);

HANDLE CreateFileMappingA(HANDLE hFile, SECURITY_ATTRIBUTES* lpFileMappingAttributes, DWORD flProtect, DWORD dwMaximumSizeHigh, DWORD dwMaximumSizeLow, LPCSTR lpName);

HANDLE CreateNamedPipeA(LPCSTR lpName, DWORD dwOpenMode, DWORD dwPipeMode, DWORD nMaxInstances, DWORD nOutBufferSize, DWORD nInBufferSize, DWORD nDefaultTimeOut, SECURITY_ATTRIBUTES* lpSecurityAttributes);

HANDLE CreateSemaphoreA(SECURITY_ATTRIBUTES* lpSemaphoreAttributes, LONG lInitialCount, LONG lMaximumCount, LPCSTR lpName);

DWORD ExpandEnvironmentStringsA(LPCSTR lpSrc, LPSTR lpDst, DWORD nSize);

BOOL FileTimeToLocalFileTime(FILETIME* lpFileTime, FILETIME* lpLocalFileTime);

BOOL FileTimeToSystemTime(FILETIME* lpFileTime, SYSTEMTIME* lpSystemTime);

BOOL FillConsoleOutputCharacterA(HANDLE hConsoleOutput, char cCharacter, DWORD nLength, COORD dwWriteCoord, LPDWORD lpNumberOfCharsWritten);

BOOL FlushViewOfFile(LPCVOID lpBaseAddress, SIZE_T dwNumberOfBytesToFlush);

BOOL FreeConsole();

BOOL GetComputerNameExA(int NameType, LPSTR lpBuffer, LPDWORD nSize);

DWORD GetConsoleTitleA(LPSTR lpConsoleTitle, DWORD nSize);

UINT GetDriveTypeA(LPCSTR lpRootPathName);

DWORD GetEnvironmentVariableA(LPCSTR lpName, LPSTR lpBuffer, DWORD nSize);

BOOL GetExitCodeProcess(HANDLE hProcess, LPDWORD lpExitCode);

DWORD GetFileAttributesA(LPCSTR lpFileName);

BOOL GetFileTime(HANDLE hFile, FILETIME* lpCreationTime, FILETIME* lpLastAccessTime, FILETIME* lpLastWriteTime);

DWORD GetLongPathNameA(LPCSTR lpszShortPath, LPSTR lpszLongPath, DWORD cchBuffer);

DWORD GetModuleFileNameA(HMODULE hModule, LPSTR lpFilename, DWORD nSize);

BOOL GetProcessAffinityMask(HANDLE hProcess, PULONG_PTR lpProcessAffinityMask, PULONG_PTR lpSystemAffinityMask);

BOOL GetProcessTimes(HANDLE hProcess, FILETIME* lpCreationTime, FILETIME* lpExitTime, FILETIME* lpKernelTime, FILETIME* lpUserTime);

DWORD GetShortPathNameA(LPCSTR lpszLongPath, LPSTR lpszShortPath, DWORD cchBuffer);

UINT GetSystemDirectoryA(LPSTR lpBuffer, UINT uSize);

void GetSystemTime(SYSTEMTIME* lpSystemTime);

void GetSystemTimeAsFileTime(FILETIME* lpSystemTimeAsFileTime);

UINT GetTempFileNameA(LPCSTR lpPathName, LPCSTR lpPrefixString, UINT uUnique, LPSTR lpTempFileName);

DWORD GetTempPathA(DWORD nBufferLength, LPSTR lpBuffer);

DWORD GetTempPathW(DWORD nBufferLength, LPTSTR lpBuffer);

ULONGLONG GetTickCount64();

HGLOBAL GlobalFree(HGLOBAL hMem);

LPVOID HeapAlloc(HANDLE hHeap, DWORD dwFlags, SIZE_T dwBytes);

BOOL HeapFree(HANDLE hHeap, DWORD dwFlags, LPVOID lpMem);

BOOL LockFile(HANDLE hFile, DWORD dwFileOffsetLow, DWORD dwFileOffsetHigh, DWORD nNumberOfBytesToLockLow, DWORD nNumberOfBytesToLockHigh);

LPVOID MapViewOfFile(HANDLE hFileMappingObject, DWORD dwDesiredAccess, DWORD dwFileOffsetHigh, DWORD dwFileOffsetLow, SIZE_T dwNumberOfBytesToMap);

BOOL MoveFileA(LPCSTR lpExistingFileName, LPCSTR lpNewFileName);

BOOL QueryPerformanceCounter(LARGE_INTEGER* lpPerformanceCount);

BOOL QueryPerformanceFrequency(LARGE_INTEGER* lpFrequency);

BOOL ReleaseSemaphore(HANDLE hSemaphore, LONG lReleaseCount, PLONG lpPreviousCount);

BOOL RemoveDirectoryA(LPCSTR lpPathName);

BOOL ResetEvent(HANDLE hEvent);

DWORD ResumeThread(HANDLE hThread);

BOOL SetConsoleScreenBufferSize(HANDLE hConsoleOutput, COORD dwSize);

BOOL SetConsoleTextAttribute(HANDLE hConsoleOutput, WORD wAttributes);

BOOL SetConsoleTitleA(LPCSTR lpConsoleTitle);

BOOL SetCurrentDirectoryA(LPCSTR lpPathName);

BOOL SetEndOfFile(HANDLE hFile);

BOOL SetEnvironmentVariableA(LPCSTR lpName, LPCSTR lpValue);

BOOL SetEvent(HANDLE hEvent);

BOOL SetFileAttributesA(LPCSTR lpFileName, DWORD dwFileAttributes);

BOOL SetPriorityClass(HANDLE hProcess, DWORD dwPriorityClass);

BOOL SetProcessAffinityMask(HANDLE hProcess, DWORD_PTR dwProcessAffinityMask);

BOOL SetThreadPriority(HANDLE hThread, int nPriority);

DWORD SuspendThread(HANDLE hThread);

BOOL SwitchToThread();

BOOL SystemTimeToFileTime(SYSTEMTIME* lpSystemTime, FILETIME* lpFileTime);

BOOL TerminateThread(HANDLE hThread, DWORD dwExitCode);

BOOL UnlockFile(HANDLE hFile, DWORD dwFileOffsetLow, DWORD dwFileOffsetHigh, DWORD nNumberOfBytesToUnlockLow, DWORD nNumberOfBytesToUnlockHigh);

BOOL UnmapViewOfFile(LPCVOID lpBaseAddress);

LPVOID VirtualAlloc(LPVOID lpAddress, SIZE_T dwSize, DWORD flAllocationType, DWORD flProtect);

BOOL VirtualFree(LPVOID lpAddress, SIZE_T dwSize, DWORD dwFreeType);

BOOL VirtualProtect(LPVOID lpAddress, SIZE_T dwSize, DWORD flNewProtect, PDWORD lpflOldProtect);

DWORD WaitForMultipleObjects(DWORD nCount, PHANDLE lpHandles, BOOL bWaitAll, DWORD dwMilliseconds);

BOOL WaitNamedPipeA(LPCSTR lpNamedPipeName, DWORD nTimeOut);

BOOL WriteConsoleA(HANDLE hConsoleOutput, LPCVOID lpBuffer, DWORD nNumberOfCharsToWrite, LPDWORD lpNumberOfCharsWritten, LPVOID lpReserved);


DWORD SetThreadExecutionState(DWORD esFlags);

BOOL GetProcessHandleCount(HANDLE hProcess, PDWORD pdwHandleCount);

HANDLE CreateJobObjectW(LPVOID lpJobAttributes, wchar_t* lpName);

BOOL AssignProcessToJobObject(HANDLE hJob, HANDLE hProcess);

BOOL TerminateJobObject(HANDLE hJob, UINT uExitCode);

BOOL GetCommState(HANDLE hFile, LPDCB lpDCB);

BOOL SetCommState(HANDLE hFile, LPDCB lpDCB);

BOOL GetCommTimeouts(HANDLE hFile, LPCOMMTIMEOUTS lpCommTimeouts);

BOOL SetCommTimeouts(HANDLE hFile, LPCOMMTIMEOUTS lpCommTimeouts);

BOOL SetupComm(HANDLE hFile, DWORD dwInQueue, DWORD dwOutQueue);

BOOL PurgeComm(HANDLE hFile, DWORD dwFlags);

BOOL ClearCommError(HANDLE hFile, LPDWORD lpErrors, LPCOMSTAT lpStat);

BOOL GetCommModemStatus(HANDLE hFile, LPDWORD lpModemStat);

BOOL EscapeCommFunction(HANDLE hFile, DWORD dwFunc);

DWORD GetTimeZoneInformation(LPTIME_ZONE_INFORMATION lpTimeZoneInformation);

BOOL SystemTimeToTzSpecificLocalTime(LPTIME_ZONE_INFORMATION lpTimeZoneInformation, LPSYSTEMTIME lpUniversalTime, LPSYSTEMTIME lpLocalTime);

int GetUserDefaultLocaleName(LPWSTR lpLocaleName, int cchLocaleName);

int GetLocaleInfoEx(LPCWSTR lpLocaleName, LCTYPE LCType, LPWSTR lpLCData, int cchData);

GEOID GetUserGeoID(GEOCLASS GeoClass);

int GetGeoInfoW(GEOID Location, GEOTYPE GeoType, LPWSTR lpGeoData, int cchData, LANGID LangId);
