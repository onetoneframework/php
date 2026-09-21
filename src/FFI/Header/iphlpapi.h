/* IP Helper: adapters, ICMP echo, the connection tables and ARP.
   Bound after windows.h, so the shared typedefs are in scope. */

typedef struct _IP_ADDRESS_STRING {
	char String[16];
} IP_ADDRESS_STRING, IP_MASK_STRING;

typedef struct _IP_ADDR_STRING {
	struct _IP_ADDR_STRING* Next;
	IP_ADDRESS_STRING IpAddress;
	IP_MASK_STRING IpMask;
	DWORD Context;
} IP_ADDR_STRING, *PIP_ADDR_STRING;

typedef struct _IP_ADAPTER_INFO {
	struct _IP_ADAPTER_INFO* Next;
	DWORD ComboIndex;
	char AdapterName[260];
	char Description[132];
	UINT AddressLength;
	BYTE Address[8];
	DWORD Index;
	UINT Type;
	UINT DhcpEnabled;
	PIP_ADDR_STRING CurrentIpAddress;
	IP_ADDR_STRING IpAddressList;
	IP_ADDR_STRING GatewayList;
	IP_ADDR_STRING DhcpServer;
	int HaveWins;
	IP_ADDR_STRING PrimaryWinsServer;
	IP_ADDR_STRING SecondaryWinsServer;
	/* time_t, which is 64-bit on x64. As long these were four bytes each and
	   GetAdaptersInfo wrote eight past the end of every entry. */
	long long LeaseObtained;
	long long LeaseExpires;
} IP_ADAPTER_INFO, *PIP_ADAPTER_INFO;

/* The reply ICMP writes after the echo data. Padded to the documented layout;
   IcmpSendEcho needs room for this plus the reply data plus 8 bytes. */
typedef struct _ICMP_ECHO_REPLY {
	DWORD Address;
	DWORD Status;
	DWORD RoundTripTime;
	WORD DataSize;
	WORD Reserved;
	LPVOID Data;
	unsigned char Options[8];
} ICMP_ECHO_REPLY, *PICMP_ECHO_REPLY;

typedef struct _IP_OPTION_INFORMATION {
	BYTE Ttl;
	BYTE Tos;
	BYTE Flags;
	BYTE OptionsSize;
	LPVOID OptionsData;
} IP_OPTION_INFORMATION, *PIP_OPTION_INFORMATION;

typedef struct _MIB_TCPROW_OWNER_PID {
	DWORD dwState;
	DWORD dwLocalAddr;
	DWORD dwLocalPort;
	DWORD dwRemoteAddr;
	DWORD dwRemotePort;
	DWORD dwOwningPid;
} MIB_TCPROW_OWNER_PID;

typedef struct _MIB_TCPTABLE_OWNER_PID {
	DWORD dwNumEntries;
	MIB_TCPROW_OWNER_PID table[1];
} MIB_TCPTABLE_OWNER_PID;

typedef struct _MIB_UDPROW_OWNER_PID {
	DWORD dwLocalAddr;
	DWORD dwLocalPort;
	DWORD dwOwningPid;
} MIB_UDPROW_OWNER_PID;

typedef struct _MIB_UDPTABLE_OWNER_PID {
	DWORD dwNumEntries;
	MIB_UDPROW_OWNER_PID table[1];
} MIB_UDPTABLE_OWNER_PID;

typedef struct _MIB_IPNETROW {
	DWORD dwIndex;
	DWORD dwPhysAddrLen;
	BYTE bPhysAddr[8];
	DWORD dwAddr;
	DWORD dwType;
} MIB_IPNETROW;

typedef struct _MIB_IPNETTABLE {
	DWORD dwNumEntries;
	MIB_IPNETROW table[1];
} MIB_IPNETTABLE;

DWORD GetAdaptersInfo(PIP_ADAPTER_INFO AdapterInfo, PULONG SizePointer);
DWORD GetNumberOfInterfaces(PDWORD pdwNumIf);
DWORD GetBestInterface(DWORD dwDestAddr, PDWORD pdwBestIfIndex);
/* Win32 declares the order flags BOOL. This binding typedefs BOOL as bool*,
   so a declaration using it would demand a pointer where the callee reads a
   four-byte int; these say int, which is what BOOL actually is. */
DWORD GetExtendedTcpTable(LPVOID pTcpTable, PDWORD pdwSize, int bOrder, ULONG ulAf, int TableClass, ULONG Reserved);
DWORD GetExtendedUdpTable(LPVOID pUdpTable, PDWORD pdwSize, int bOrder, ULONG ulAf, int TableClass, ULONG Reserved);
DWORD GetIpNetTable(LPVOID IpNetTable, PULONG SizePointer, int Order);
DWORD SendARP(DWORD DestIP, DWORD SrcIP, LPVOID pMacAddr, PULONG PhyAddrLen);
HANDLE IcmpCreateFile(VOID);
int IcmpCloseHandle(HANDLE IcmpHandle);
DWORD IcmpSendEcho(HANDLE IcmpHandle, DWORD DestinationAddress, LPVOID RequestData, WORD RequestSize, PIP_OPTION_INFORMATION RequestOptions, LPVOID ReplyBuffer, DWORD ReplySize, DWORD Timeout);
