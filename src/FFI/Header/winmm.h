bool PlaySoundA(const char* pszSound, void* hmod, unsigned int fdwSound);
MMRESULT midiOutClose(HMIDIOUT hmo);
MMRESULT midiOutOpen(HMIDIOUT* lphmo, unsigned int uDeviceID, unsigned long dwCallback, unsigned long dwInstance, unsigned long dwFlags);
MMRESULT midiOutShortMsg(HMIDIOUT hmo, unsigned int dwMsg);
MMRESULT waveOutClose(HWAVEOUT hwo);
MMRESULT waveOutGetVolume(HWAVEOUT hwo, LPDWORD pdwVolume);
MMRESULT waveOutOpen(LPHWAVEOUT phwo, UINT uDeviceID, LPCWAVEFORMATEX pwfx, DWORD_PTR dwCallback, DWORD_PTR dwInstance, DWORD fdwOpen);
MMRESULT waveOutSetVolume(HMIXEROBJ hwo, DWORD dwVolume);
unsigned int midiInGetDevCapsA(unsigned int, MIDIINCAPSA*, unsigned int);
unsigned int midiInGetNumDevs();
unsigned int midiOutGetNumDevs();
unsigned int midiOutGetDevCapsA(unsigned int, MIDIOUTCAPSA*, unsigned int);
unsigned int waveOutGetNumDevs();
unsigned int waveOutGetDevCapsA(unsigned int, WAVEOUTCAPSA*, unsigned int);
unsigned int waveInGetNumDevs();
unsigned int waveInGetDevCapsA(unsigned int, WAVEINCAPSA*, unsigned int);
unsigned int waveOutGetDevCapsW(unsigned int, WAVEOUTCAPSW*, unsigned int);
unsigned int waveInGetDevCapsW(unsigned int, WAVEINCAPSW*, unsigned int);
unsigned int midiOutGetDevCapsW(unsigned int, MIDIOUTCAPSW*, unsigned int);
unsigned int midiInGetDevCapsW(unsigned int, MIDIINCAPSW*, unsigned int);

DWORD timeGetTime(VOID);

MMRESULT timeBeginPeriod(UINT uPeriod);

MMRESULT timeEndPeriod(UINT uPeriod);

MMRESULT mciSendStringW(wchar_t* lpszCommand, wchar_t* lpszReturnString, UINT cchReturn, HWND hwndCallback);
