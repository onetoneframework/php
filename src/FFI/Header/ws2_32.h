typedef unsigned int SOCKET;
typedef unsigned short u_short;
typedef unsigned int u_int;
typedef unsigned long u_long;
typedef unsigned char u_char;

typedef struct {
    u_short sin_family;
    u_short sin_port;
    struct {
        u_long s_addr;
    } sin_addr;
    char sin_zero[8];
} SOCKADDR_IN;

typedef struct {
    unsigned short wVersion;
    unsigned short wHighVersion;
    char szDescription[257];
    char szSystemStatus[129];
    unsigned short iMaxSockets;
    unsigned short iMaxUdpDg;
    char* lpVendorInfo;
} WSADATA;

int WSACleanup(void);
int WSAStartup(u_short wVersionRequested, WSADATA *lpWSAData);
int connect(SOCKET s, const SOCKADDR_IN *name, int namelen);
SOCKET socket(int af, int type, int protocol);
int bind(SOCKET s, const SOCKADDR_IN *name, int namelen);
int listen(SOCKET s, int backlog);
SOCKET accept(SOCKET s, void *addr, int *addrlen);
int closesocket(SOCKET s);
int send(SOCKET s, const char *buf, int len, int flags);
int recv(SOCKET s, char *buf, int len, int flags);
unsigned short htons(unsigned short hostshort);
unsigned long inet_addr(const char *cp);

/* Declarations for calls WindowsAPI already makes. */
int WSAGetLastError();

int gethostname(char* name, int namelen);

int getsockopt(SOCKET s, int level, int optname, char* optval, int* optlen);

unsigned long htonl(unsigned long hostlong);

unsigned long ntohl(unsigned long netlong);

unsigned short ntohs(unsigned short netshort);

int setsockopt(SOCKET s, int level, int optname, const char* optval, int optlen);

int shutdown(SOCKET s, int how);
