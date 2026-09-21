HRESULT CLSIDFromString(const wchar_t* lpsz, GUID* pclsid);
HRESULT CoCreateInstance(GUID* rclsid, void* pUnkOuter, DWORD dwClsContext, GUID* riid, void** ppv);
HRESULT CoInitializeEx(void *pvReserved, unsigned long dwCoInit);
HRESULT IIDFromString(const wchar_t* lpsz, GUID* piid);
HRESULT RoActivateInstance(HSTRING activatableClassId, void **instance);
HRESULT WindowsCreateString(const char *sourceString, unsigned int length, HSTRING *string);
HRESULT WindowsDeleteString(HSTRING string);
void CoUninitialize();
