BOOL ShellAboutA(HWND,LPCSTR,LPCSTR,HICON);
BOOL ShellAboutW(HWND,LPCWSTR,LPCWSTR,HICON);
BOOL ShellExecuteExW(LPSHELLEXECUTEINFOW pExecInfo);
BOOL SHGetNewLinkInfoA(LPCSTR,LPCSTR,LPSTR,BOOL*,UINT);
BOOL SHGetNewLinkInfoW(LPCWSTR,LPCWSTR,LPWSTR,BOOL*,UINT);
DWORD DoEnvironmentSubstA(LPSTR, UINT);
DWORD DoEnvironmentSubstW(LPWSTR, UINT);
HICON ExtractAssociatedIconA(HINSTANCE,LPSTR,LPWORD);
HICON ExtractAssociatedIconExA(HINSTANCE,LPSTR,LPWORD,LPWORD);
HICON ExtractAssociatedIconExW(HINSTANCE,LPWSTR,LPWORD,LPWORD);
HICON ExtractAssociatedIconW(HINSTANCE,LPWSTR,LPWORD);
HICON ExtractIconA(HINSTANCE,LPCSTR,UINT);
HICON ExtractIconW(HINSTANCE,LPCWSTR,UINT);
HINSTANCE FindExecutableA(LPCSTR,LPCSTR,LPSTR);
HINSTANCE FindExecutableW(LPCWSTR,LPCWSTR,LPWSTR);
HINSTANCE ShellExecuteA(HWND hwnd, LPCSTR lpOperation, LPCSTR lpFile, LPCSTR lpParameters, LPCSTR lpDirectory, int nShowCmd);
HINSTANCE ShellExecuteW(HWND hwnd, LPCWSTR lpOperation, LPCWSTR lpFile, LPCWSTR lpParameters, LPCWSTR lpDirectory, INT nShowCmd);
HRESULT Shell_NotifyIconGetRect(const NOTIFYICONIDENTIFIER* identifier, RECT* iconLocation);
HRESULT SHEmptyRecycleBinA(HWND,LPCSTR,DWORD);
HRESULT SHEmptyRecycleBinW(HWND,LPCWSTR,DWORD);
HRESULT SHEnumerateUnreadMailAccountsW(HKEY,DWORD,LPWSTR,INT);
HRESULT SHGetPropertyStoreForWindow(HWND,REFIID,void **);
HRESULT SHGetStockIconInfo(SHSTOCKICONID, UINT, SHSTOCKICONINFO*);
HRESULT SHQueryUserNotificationState(QUERY_USER_NOTIFICATION_STATE*);
int Shell_NotifyIconA(DWORD dwMessage, NOTIFYICONDATAA *lpdata);
int Shell_NotifyIconW(int dwMessage, NOTIFYICONDATAW *lpData);
int ShellExecuteExA(SHELLEXECUTEINFOA*);
int ShellMessageBoxA(HINSTANCE,HWND,LPCSTR,LPCSTR,UINT,...);
int ShellMessageBoxW(HINSTANCE,HWND,LPCWSTR,LPCWSTR,UINT,...);
LPWSTR* CommandLineToArgvW(LPCWSTR,int*);
UINT ExtractIconExA(LPCSTR,INT,HICON*,HICON*,UINT);
UINT ExtractIconExW(LPCWSTR,INT,HICON*,HICON*,UINT);

/* Declarations for calls WindowsAPI already makes. */
BOOL IsUserAnAdmin();

PVOID SHBrowseForFolderA(PVOID lpbi);

BOOL SHGetPathFromIDListA(PVOID pidl, LPSTR pszPath);

HRESULT SHQueryRecycleBinW(wchar_t* pszRootPath, LPSHQUERYRBINFO pSHQueryRBInfo);

typedef struct _browseinfoA {
	HWND hwndOwner;
	LPVOID pidlRoot;
	LPSTR pszDisplayName;
	LPSTR lpszTitle;
	UINT ulFlags;
	LPVOID lpfn;
	LPARAM lParam;
	int iImage;
} BROWSEINFOA, *LPBROWSEINFOA;
