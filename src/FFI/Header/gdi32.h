BOOL Arc(HDC hdc, int x1, int y1, int x2, int y2, int x3, int y3, int x4, int y4);
BOOL CancelDC(HDC hdc);
BOOL CreateScalableFontResourceA(DWORD fdwHidden, LPCSTR lpszFont, LPCSTR lpszFile, LPCSTR lpszPath);
BOOL CreateScalableFontResourceW(DWORD fdwHidden, LPCWSTR lpszFont, LPCWSTR lpszFile, LPCWSTR lpszPath);
bool GdiAlphaBlend(HDC hdcDest, int nXOriginDest, int nYOriginDest, int nWidthDest, int nHeightDest, HDC hdcSrc, int nXOriginSrc, int nYOriginSrc, int nWidthSrc, int nHeightSrc, BLENDFUNCTION blendFunction);
BOOL GetTextExtentPoint32W(HDC hdc, const WCHAR* lpString, INT c, SIZE* psizl);
BOOL GetTextMetricsW(HDC hdc, TEXTMETRICW* lptm);
BOOL Rectangle(HDC hdc, int left, int top, int right, int bottom);
BOOL SetPixelV(HDC hdc, int x, int y, COLORREF color);
BOOL TextOutA(HDC hdc, int x, int y, LPCSTR lpString, int c);
BOOL TextOutW(HDC hdc, INT x, INT y, LPCWSTR lpString, INT c);
COLORREF GetPixel(HDC hdc, int x, int y);
COLORREF SetTextColor(HDC hdc, COLORREF crColor);
HBITMAP CreateBitmap(int nWidth, int nHeight, UINT nPlanes, UINT nBitCount, VOID *lpBits);
HBITMAP CreateBitmapIndirect(BITMAP *pbm);
HBITMAP CreateCompatibleBitmap(HDC hdc, int cx, int cy);
HBITMAP CreateDIBitmap(HDC hdc, BITMAPINFOHEADER *pbmih, DWORD flInit, VOID *pjBits, BITMAPINFO *pbmi, UINT iUsage);
HBITMAP CreateDiscardableBitmap(HDC hdc, int cx, int cy);
HBRUSH CreateDIBPatternBrush(HGLOBAL h, UINT iUsage);
HBRUSH CreateDIBPatternBrushPt(VOID *lpPackedDIB, UINT iUsage);
HBRUSH CreatePatternBrush(HBITMAP hbm);
HBRUSH CreateSolidBrush(DWORD crColor);
HDC CreateCompatibleDC(HDC hdc);
HFONT CreateFontIndirectA(LOGFONTA *lplf);
HFONT CreateFontIndirectW(LOGFONTW *lplf);
HGDIOBJ CreateFontW(int cHeight, int cWidth, int cEscapement, int cOrientation, int cWeight, DWORD bItalic, DWORD bUnderline, DWORD bStrikeOut, DWORD iCharSet, DWORD iOutPrecision, DWORD iClipPrecision, DWORD iQuality, DWORD iPitchAndFamily, const WCHAR* pszFaceName);
HGDIOBJ GetStockObject(int fnObject);
HPEN CreatePen(int iStyle, int cWidth, COLORREF color);
HRGN CreateEllipticRgn(int x1, int y1, int x2, int y2);
HRGN CreateEllipticRgnIndirect(RECT *lprect);
HRGN CreateRectRgn(int x1, int y1, int x2, int y2);
HRGN CreateRectRgnIndirect(RECT *lprect);
HRGN CreateRoundRectRgn(int x1, int y1, int x2, int y2, int w, int h);
int BitBlt(HDC hdcDest, int xDest, int yDest, int nWidth, int nHeight, HDC hdcSrc, int xSrc, int ySrc, int dwRop);
int DeleteDC(HDC hdc);
int DeleteObject(HBITMAP hBitmap);
int GetDeviceCaps(HDC hdc, int nIndex);
int GetDIBits(HDC hdc, HBITMAP hbmp, int uStartScan, int cScanLines, void *lpvBits, void *lpbmi, int usage);
int GetObjectA(void* hObject, int cbBuffer, void* lpvObject);
int GetObjectW(HGDIOBJ h, int c, void* pv);
int LineTo(HDC hdc, int x, int y);
int MoveToEx(HDC hdc, int x, int y, LPPOINT lpPoint);
int SelectObject(HDC hdc, HBITMAP hBitmap);
int SetBkColor(void* hdc, int color);

HFONT CreateFontA(int cHeight, int cWidth, int cEscapement, int cOrientation, int cWeight, DWORD bItalic, DWORD bUnderline, DWORD bStrikeOut, DWORD iCharSet, DWORD iOutPrecision, DWORD iClipPrecision, DWORD iQuality, DWORD iPitchAndFamily, LPCSTR pszFaceName);

BOOL Ellipse(HANDLE hdc, int left, int top, int right, int bottom);

BOOL GetTextExtentPoint32A(HANDLE hdc, LPCSTR lpString, int c, PVOID psizl);

BOOL RoundRect(HANDLE hdc, int left, int top, int right, int bottom, int width, int height);

int SetBkMode(HANDLE hdc, int mode);
