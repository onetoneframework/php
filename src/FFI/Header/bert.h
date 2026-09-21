typedef struct bert_ctx bert_ctx;
bert_ctx* bert_load_from_file(const char* fname);
void bert_free(bert_ctx* ctx);
int32_t bert_n_embd(bert_ctx* ctx);
int32_t bert_n_max_tokens(bert_ctx* ctx);
void bert_encode(bert_ctx* ctx, int32_t n_threads, const char* texts, float* embeddings);
void bert_free_float(float* ptr);
