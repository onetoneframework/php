HRESULT  CLSIDFromString(const wchar_t* lpsz, GUID* pclsid);
HRESULT  IIDFromString(const wchar_t* lpsz, GUID* piid);
HRESULT CoCreateInstance( void* rclsid, void* pUnkOuter, DWORD dwClsContext, void* riid, void** ppv);
//HRESULT CoCreateInstance(const uint8_t* rclsid, void* pUnkOuter, uint32_t dwClsContext, const uint8_t* riid, void** ppv);
HRESULT CoInitialize(void* pvReserved);
HRESULT CoInitializeEx(LPVOID, DWORD);
HRESULT CoUninitialize();

//HRESULT CoCreateInstance(void* rclsid, void* pUnkOuter, DWORD dwClsContext, void* riid, void** ppv);
//HRESULT SpVoice_Speak(void* pVoice, const wchar_t* pwcText, DWORD dwFlags, void* pullStreamNumber);
//HRESULT SpVoice_Release(void* pVoice);
//HRESULT CoCreateInstance(const GUID *rclsid, void *pUnkOuter, unsigned long dwClsContext, const GUID *riid, void **ppv);
//void    CoUninitialize(void);
//HRESULT CoCreateInstance(const GUID*, LPVOID, DWORD, const GUID*, LPVOID*);

//HRESULT CoCreateInstance(void* rclsid, void* pUnkOuter, DWORD dwClsContext, void* riid, void** ppv);
//HRESULT SpVoice_Speak(void* pVoice, const wchar_t* pwcText, DWORD dwFlags, void* pullStreamNumber);
//HRESULT SpVoice_Release(void* pVoice);
//HRESULT CoCreateInstance(const GUID *rclsid, void *pUnkOuter, unsigned long dwClsContext, const GUID *riid, void **ppv);
//void    CoUninitialize(void);
//HRESULT CoCreateInstance(const GUID*, LPVOID, DWORD, const GUID*, LPVOID*);

/* Declarations for calls WindowsAPI already makes. */
void CoTaskMemFree(LPVOID pv);
