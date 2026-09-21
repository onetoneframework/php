NTSTATUS NtQuerySystemInformation(int SystemInformationClass, PVOID SystemInformation, ULONG SystemInformationLength, PULONG ReturnLength);
NTSTATUS RtlCompressBuffer(USHORT CompressionFormatAndEngine, PUCHAR UncompressedBuffer, ULONG UncompressedBufferSize, PUCHAR CompressedBuffer, ULONG CompressedBufferSize, ULONG UncompressedChunkSize, PULONG FinalCompressedSize, PVOID WorkSpace);
NTSTATUS RtlDecompressBuffer(USHORT CompressionFormat, PUCHAR UncompressedBuffer, ULONG UncompressedBufferSize, PUCHAR CompressedBuffer, ULONG CompressedBufferSize, PULONG FinalUncompressedSize);
NTSTATUS RtlGetCompressionWorkSpaceSize(USHORT CompressionFormatAndEngine, PULONG CompressBufferWorkSpaceSize, PULONG CompressFragmentWorkSpaceSize);  

NTSTATUS RtlGetVersion(RTL_OSVERSIONINFOW* lpVersionInformation);

/* Declarations for calls WindowsAPI already makes. */
NTSTATUS NtResumeProcess(HANDLE ProcessHandle);

NTSTATUS NtSuspendProcess(HANDLE ProcessHandle);
