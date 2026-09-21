BOOL AbortSystemShutdownA( LPSTR lpMachineName );
BOOL AbortSystemShutdownW( LPWSTR lpMachineName );
BOOL AllocateAndInitializeSid(PSID_IDENTIFIER_AUTHORITY pIdentifierAuthority, BYTE nSubAuthorityCount, DWORD nSubAuthority0, DWORD nSubAuthority1, DWORD nSubAuthority2, DWORD nSubAuthority3, DWORD nSubAuthority4, DWORD nSubAuthority5, DWORD nSubAuthority6, DWORD nSubAuthority7, PSID *pSid);
BOOL CheckTokenMembership(HANDLE TokenHandle, PSID SidToCheck, BOOL *IsMember);
BOOL GetCurrentHwProfileA(LPHW_PROFILE_INFOA pInfo);
BOOL GetCurrentHwProfileW(LPHW_PROFILE_INFOW pInfo);
BOOL GetUserNameW(LPWSTR name, LPDWORD size);
BOOL ImpersonateLoggedOnUser(HANDLE hToken);
BOOL InitiateSystemShutdownA( LPSTR lpMachineName, LPSTR lpMessage, DWORD dwTimeout, BOOL bForceAppsClosed, BOOL bRebootAfterShutdown );
BOOL InitiateSystemShutdownExA( LPSTR lpMachineName, LPSTR lpMessage, DWORD dwTimeout, BOOL bForceAppsClosed, BOOL bRebootAfterShutdown, DWORD dwReason);
BOOL InitiateSystemShutdownExW( LPWSTR lpMachineName, LPWSTR lpMessage,DWORD dwTimeout, BOOL bForceAppsClosed, BOOL bRebootAfterShutdown, DWORD dwReason);
BOOL InitiateSystemShutdownW( LPWSTR lpMachineName, LPWSTR lpMessage, DWORD dwTimeout, BOOL bForceAppsClosed, BOOL bRebootAfterShutdown );
BOOL IsTextUnicode( LPCVOID buf, INT len, LPINT flags );
BOOL LogonUserA( LPCSTR lpszUsername, LPCSTR lpszDomain, LPCSTR lpszPassword, DWORD dwLogonType, DWORD dwLogonProvider, PHANDLE phToken );
BOOL LogonUserA(const char *lpszUsername, const char *lpszDomain, const char *lpszPassword, DWORD dwLogonType, DWORD dwLogonProvider, PHANDLE phToken);      
BOOL LogonUserW( LPCWSTR lpszUsername, LPCWSTR lpszDomain, LPCWSTR lpszPassword, DWORD dwLogonType, DWORD dwLogonProvider, PHANDLE phToken );
BOOL ReadEventLogA(HANDLE hEventLog, DWORD dwReadFlags, DWORD dwRecordOffset, LPVOID lpBuffer, DWORD nNumberOfBytesToRead, DWORD* pnBytesRead, DWORD* pnMinNumberOfBytesNeeded);  
BOOL RevertToSelf();
DWORD CommandLineFromMsiDescriptor( WCHAR *szDescriptor,WCHAR *szCommandLine, DWORD *pcchCommandLine );
DWORD InitiateShutdownA(char *name, char *message, DWORD seconds, DWORD flags, DWORD reason);
DWORD InitiateShutdownW(WCHAR *name, WCHAR *message, DWORD seconds, DWORD flags, DWORD reason);
HKEY RegOpenKeyExW(HKEY hKey, LPCWSTR lpSubKey, DWORD ulOptions, REGSAM samDesired, PHKEY phkResult); // Unicode
LONG RegEnumValueW(HKEY hKey, DWORD dwIndex, WCHAR* lpValueName, DWORD* lpcchValueName, DWORD* lpReserved, DWORD* lpType, unsigned char* lpData, DWORD* lpcbData);
int GetUserNameA(char* lpBuffer, int* lpnSize);
int RegEnumKeyExA(HKEY hKey, DWORD dwIndex, LPSTR lpName, DWORD *lpcName, void* lpReserved, LPSTR lpClass, DWORD *lpcClass, void* lpftLastWriteTime);
int RegOpenKeyExA(int hKey, LPCSTR lpSubKey, DWORD ulOptions, DWORD samDesired, HKEY *phkResult); // ANSI
int RegQueryValueExA(HKEY hKey, LPCSTR lpValueName, void* lpReserved, DWORD* lpType, unsigned char* lpData, DWORD* lpcbData);
LONG RegCloseKey(HKEY hKey);
LONG RegSetValueExA(HKEY hKey, LPCSTR lpValueName, DWORD Reserved, DWORD dwType, const BYTE* lpData, DWORD cbData); // ANSI
LONG RegSetValueExW(HKEY hKey, LPCWSTR lpValueName, DWORD Reserved, DWORD dwType, const BYTE* lpData, DWORD cbData); // Unicode
ULONG PerfCloseQueryHandle( HANDLE query );
ULONG PerfCloseQueryHandle( HANDLE query );
ULONG PerfOpenQueryHandle( const WCHAR *machine, HANDLE *query );
ULONG PerfOpenQueryHandle( const WCHAR *machine, HANDLE *query );
void RegisterWaitChainCOMCallback(PCOGETCALLSTATE call_state_cb, PCOGETACTIVATIONSTATE activation_state_cb);
void* FreeSid(PSID pSid);
ULONG PerfAddCounters( HANDLE query, PERF_COUNTER_IDENTIFIER *id, DWORD size );
ULONG PerfQueryCounterData( HANDLE query, PERF_DATA_HEADER *data, DWORD data_size, DWORD *size_needed );
BOOL OpenProcessToken(HANDLE ProcessHandle, DWORD DesiredAccess, PHANDLE TokenHandle);
BOOL GetTokenInformation(HANDLE TokenHandle, TOKEN_INFORMATION_CLASS TokenInformationClass, LPVOID TokenInformation, DWORD TokenInformationLength, PDWORD ReturnLength);

/* Declarations for calls WindowsAPI already makes. */
BOOL AdjustTokenPrivileges(HANDLE TokenHandle, BOOL DisableAllPrivileges, PVOID NewState, DWORD BufferLength, PVOID PreviousState, PDWORD ReturnLength);

BOOL CloseServiceHandle(SC_HANDLE hSCObject);

BOOL ControlService(SC_HANDLE hService, DWORD dwControl, PVOID lpServiceStatus);

BOOL CryptAcquireContextA(PVOID phProv, LPCSTR szContainer, LPCSTR szProvider, DWORD dwProvType, DWORD dwFlags);

BOOL CryptGenRandom(ULONG_PTR hProv, DWORD dwLen, PBYTE pbBuffer);

BOOL CryptReleaseContext(ULONG_PTR hProv, DWORD dwFlags);

BOOL LookupPrivilegeValueA(LPCSTR lpSystemName, LPCSTR lpName, PVOID lpLuid);

SC_HANDLE OpenSCManagerA(LPCSTR lpMachineName, LPCSTR lpDatabaseName, DWORD dwDesiredAccess);

SC_HANDLE OpenServiceA(SC_HANDLE hSCManager, LPCSTR lpServiceName, DWORD dwDesiredAccess);

BOOL QueryServiceStatus(SC_HANDLE hService, PVOID lpServiceStatus);

LONG RegCreateKeyExA(HKEY hKey, LPCSTR lpSubKey, DWORD Reserved, LPSTR lpClass, DWORD dwOptions, REGSAM samDesired, SECURITY_ATTRIBUTES* lpSecurityAttributes, PHKEY phkResult, LPDWORD lpdwDisposition);

LONG RegDeleteKeyA(HKEY hKey, LPCSTR lpSubKey);

LONG RegDeleteValueA(HKEY hKey, LPCSTR lpValueName);

LONG RegEnumValueA(HKEY hKey, DWORD dwIndex, LPSTR lpValueName, LPDWORD lpcchValueName, LPDWORD lpReserved, LPDWORD lpType, LPBYTE lpData, LPDWORD lpcbData);

BOOL StartServiceA(SC_HANDLE hService, DWORD dwNumServiceArgs, PVOID lpServiceArgVectors);

BOOL EnumServicesStatusExW(SC_HANDLE hSCManager, int InfoLevel, DWORD dwServiceType, DWORD dwServiceState, LPVOID lpServices, DWORD cbBufSize, LPDWORD pcbBytesNeeded, LPDWORD lpServicesReturned, LPDWORD lpResumeHandle, LPCWSTR pszGroupName);

BOOL QueryServiceConfigW(SC_HANDLE hService, LPVOID lpServiceConfig, DWORD cbBufSize, LPDWORD pcbBytesNeeded);

BOOL QueryServiceConfig2W(SC_HANDLE hService, DWORD dwInfoLevel, LPVOID lpBuffer, DWORD cbBufSize, LPDWORD pcbBytesNeeded);

BOOL QueryServiceStatusEx(SC_HANDLE hService, int InfoLevel, LPVOID lpBuffer, DWORD cbBufSize, LPDWORD pcbBytesNeeded);

BOOL ChangeServiceConfigW(SC_HANDLE hService, DWORD dwServiceType, DWORD dwStartType, DWORD dwErrorControl, LPCWSTR lpBinaryPathName, LPCWSTR lpLoadOrderGroup, LPDWORD lpdwTagId, LPCWSTR lpDependencies, LPCWSTR lpServiceStartName, LPCWSTR lpPassword, LPCWSTR lpDisplayName);

BOOL DeleteService(SC_HANDLE hService);

HANDLE OpenEventLogW(LPCWSTR lpUNCServerName, LPCWSTR lpSourceName);

BOOL CloseEventLog(HANDLE hEventLog);

BOOL GetNumberOfEventLogRecords(HANDLE hEventLog, PDWORD NumberOfRecords);

BOOL GetOldestEventLogRecord(HANDLE hEventLog, PDWORD OldestRecord);

BOOL ReadEventLogW(HANDLE hEventLog, DWORD dwReadFlags, DWORD dwRecordOffset, LPVOID lpBuffer, DWORD nNumberOfBytesToRead, PDWORD pnBytesRead, PDWORD pnMinNumberOfBytesNeeded);

BOOL BackupEventLogW(HANDLE hEventLog, LPCWSTR lpBackupFileName);

BOOL ClearEventLogW(HANDLE hEventLog, LPCWSTR lpBackupFileName);
