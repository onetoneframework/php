BOOL DestroyPhysicalMonitors(DWORD dwPhysicalMonitorArraySize, PPHYSICAL_MONITOR pPhysicalMonitorArray);
BOOL GetMonitorBrightness(HANDLE hMonitor, PDWORD pdwMinimumBrightness, PDWORD pdwCurrentBrightness, PDWORD pdwMaximumBrightness);
BOOL GetMonitorCapabilities(HANDLE  hMonitor, LPDWORD  pdwMonitorCapabilities, LPDWORD  pdwSupportedColorTemperature);  
BOOL GetMonitorContrast(HANDLE hMonitor, LPDWORD pdwMinimumContrast, LPDWORD pdwCurrentContrast, LPDWORD pdwMaximumContrast); 
BOOL GetNumberOfPhysicalMonitorsFromHMONITOR(HMONITOR hMonitor, PDWORD pdwNumberOfPhysicalMonitors);
BOOL GetPhysicalMonitorsFromHMONITOR(HMONITOR hMonitor, DWORD dwPhysicalMonitorArraySize, PHYSICAL_MONITOR* pPhysicalMonitorArray);
BOOL SetMonitorBrightness(HANDLE hMonitor, DWORD  dwNewBrightness);
BOOL SetMonitorContrast(HANDLE hMonitor, DWORD dwNewContrast);  
