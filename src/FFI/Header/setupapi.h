BOOL SetupDiDestroyDeviceInfoList(HDEVINFO DeviceInfoSet);
BOOL SetupDiEnumDeviceInfo(HDEVINFO DeviceInfoSet, DWORD MemberIndex, SP_DEVINFO_DATA* DeviceInfoData);
BOOL SetupDiEnumDeviceInterfaces(HDEVINFO DeviceInfoSet, PSP_DEVINFO_DATA DeviceInfoData, const GUID* InterfaceClassGuid, DWORD MemberIndex, PSP_DEVICE_INTERFACE_DATA DeviceInterfaceData);
BOOL SetupDiGetDeviceInterfaceDetailW(HDEVINFO DeviceInfoSet, PSP_DEVICE_INTERFACE_DATA DeviceInterfaceData, PSP_DEVICE_INTERFACE_DETAIL_DATA_W DeviceInterfaceDetailData, DWORD DeviceInterfaceDetailDataSize, PDWORD RequiredSize, PSP_DEVINFO_DATA DeviceInfoData);
BOOL SetupDiGetDeviceRegistryPropertyA(HDEVINFO DeviceInfoSet, SP_DEVINFO_DATA *DeviceInfoData, DWORD Property, DWORD *PropertyRegDataType, unsigned char *PropertyBuffer, DWORD PropertyBufferSize, DWORD *RequiredSize);
BOOL SetupDiGetDeviceRegistryPropertyW(HDEVINFO DeviceInfoSet, PSP_DEVINFO_DATA DeviceInfoData, DWORD Property, PDWORD PropertyRegDataType, PBYTE PropertyBuffer, DWORD PropertyBufferSize, PDWORD RequiredSize);
HDEVINFO SetupDiGetClassDevsA(const void* ClassGuid, const char* Enumerator, void* hwndParent, unsigned int Flags);
HDEVINFO SetupDiGetClassDevsW(const GUID* ClassGuid, PCWSTR Enumerator, HWND hwndParent, DWORD Flags);
//int SetupDiEnumDeviceInfo(HDEVINFO DeviceInfoSet, unsigned int MemberIndex, SP_DEVINFO_DATA *DeviceInfoData);
