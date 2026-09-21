BOOL DeleteUrlCacheEntryA(LPCSTR lpszUrlName);
BOOL DeleteUrlCacheEntryW(LPCWSTR lpszUrlName);
BOOL InternetCloseHandle(HINTERNET hInternet);
BOOL InternetGetConnectedState(DWORD *lpdwFlags, DWORD dwReserved);
BOOL InternetReadFile(HINTERNET hFile, LPVOID lpBuffer, DWORD dwNumberOfBytesToRead, LPDWORD lpdwNumberOfBytesRead);
HINTERNET InternetOpenA(LPCSTR lpszAgent, DWORD dwAccessType, LPCSTR lpszProxyName, LPCSTR lpszProxyBypass, DWORD dwFlags);
HINTERNET InternetOpenUrlA(HINTERNET hInternet, LPCSTR lpszUrl, LPCSTR lpszHeaders, DWORD dwHeadersLength, DWORD dwFlags, DWORD_PTR dwContext);
