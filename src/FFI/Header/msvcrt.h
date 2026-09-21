FILE *fopen(const char *filename, const char *mode);
int fclose(FILE *stream);
long ftell(FILE *stream);
size_t fread(void *ptr, size_t size, size_t count, FILE *stream);
size_t fseek(FILE *stream, long offset, int origin);
