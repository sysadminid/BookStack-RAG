# BookStack RAG

A **Retrieval-Augmented Generation (RAG)** system for [BookStack](https://www.bookstackapp.com/) that provides AI-powered search with **citations** and **deep links** back to source pages.

## ✨ Features

- 🔍 **Hybrid Search**: Combines semantic (vector) and keyword (BM25) search using Reciprocal Rank Fusion (RRF)
- 🤖 **Azure OpenAI Integration**: Generates answers with inline citations like [1], [2] that link directly to source pages
- 📚 **Deep Linking**: Every citation is a clickable link to the exact BookStack page
- 🎯 **Contextual Filtering**: Filter by book, chapter, or tags
- ⚡ **Incremental Indexing**: Supports full and delta updates based on `updated_at` timestamps
- 🔄 **Re-ranking**: Optional cross-encoder re-ranking for improved relevance
- 🎨 **Modern Widget**: Beautiful, responsive chat interface with Shadow DOM isolation
- 🔒 **API Key Protection**: Secure REST API with token authentication

## 🏗️ Architecture

```
┌─────────────┐
│  BookStack  │
│   MySQL DB  │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│  Ingestion Pipeline │
│ • HTML → Text       │
│ • Token Chunking    │
│ • Embeddings        │
│ • BM25 Index        │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐       ┌──────────────┐
│   Vector Store      │◄──────┤ Hybrid Search│
│   (ChromaDB)        │       │ • Semantic   │
└─────────────────────┘       │ • Keyword    │
                              │ • RRF Fusion │
┌─────────────────────┐       │ • Re-ranking │
│  BM25 Index         │◄──────┤              │
│  (Whoosh)           │       └──────┬───────┘
└─────────────────────┘              │
                                     ▼
                              ┌──────────────┐
                              │ Azure OpenAI │
                              │ • LLM Answer │
                              │ • Citations  │
                              └──────┬───────┘
                                     │
                                     ▼
                              ┌──────────────┐
                              │  FastAPI     │
                              │  REST API    │
                              └──────┬───────┘
                                     │
                                     ▼
                              ┌──────────────┐
                              │  Web Widget  │
                              │  (Shadow DOM)│
                              └──────────────┘
```

### API
- `src/api/main.py`: FastAPI server with endpoints:
  - POST /retrieve: Raw hybrid search results
  - POST /search: AI answer with citations (main endpoint)
  - POST /ingest/reindex: Trigger full or incremental reindex


### CLI
- `cli.py`: Typer-based CLI for manual indexing:
  ```
  python cli.py ingest-full
  python cli.py ingest-incremental "2025-01-01T00:00:00"
  ```


### Widget
- `sidecar-widget/base-body-end.blade.php`: BookStack theme template that injects a Shadow DOM-isolated search widget with:
  - Floating action button (FAB)
  - Ctrl+K / ⌘K global shortcut
  - Modern gradient UI
  - Markdown rendering with clickable citations
  - Mobile-responsive design


## 🚀 Installation

1. Clone and Install Dependencies
    ```
    git clone <repository-url>
    cd bookstack-rag
    pip install -r requirements.txt
    ```

2. Configure Environment<br>
    Fill `.env` with your credentials:
    ```
    # MySQL (BookStack)
    BOOKSTACK_DB_HOST=localhost
    BOOKSTACK_DB_USER=bookstack
    BOOKSTACK_DB_PASS=secret
    BOOKSTACK_DB_NAME=bookstack

    # BookStack URL base for deep links
    BOOKSTACK_BASE_URL=https://wiki.example.com

    # Azure OpenAI
    AZURE_OPENAI_ENDPOINT=https://<resource>.openai.azure.com/
    AZURE_OPENAI_API_KEY=<key>
    AZURE_OPENAI_DEPLOYMENT=gpt-4
    AZURE_OPENAI_API_VERSION=2024-02-15-preview

    # Vector DB
    CHROMA_DIR=./.chroma

    # API
    API_KEY=your-secure-random-key
    CORS_ORIGINS=*

    # Models
    EMBED_MODEL=sentence-transformers/all-MiniLM-L6-v2
    RERANKER_MODEL=cross-encoder/ms-marco-MiniLM-L-6-v2
    ```

3. Initial Indexing
    ```
    python cli.py ingest-full
    ```

4. Start API Server
    ```
    uvicorn src.api.main:app --host 0.0.0.0 --port 8000
    ```

5. Deploy Widget to BookStack
    1. Copy `sidecar-widget/base-body-end.blade.php` to your BookStack theme's, usually in `resources/views/layouts` directory.
    2. Update the API constant in the script to point to your FastAPI endpoint:
    ```
    const API = "https://your-api.example.com/rag/search";
    ```
    3. Clear BookStack cache: `php artisan cache:clear`.

## 🔧 Usage

### Web Widget
- Click the floating search button or press Ctrl+K (⌘K on Mac)
- Type your question
- Receive an AI-generated answer with numbered citations
Click citation numbers like [1], [2] to jump to - source pages
- Widget auto-filters by current book when browsing BookStack

### REST API
**Search with AI Answer**
```
curl -X POST https://your-api.example.com/search \
  -H "X-API-Key: your-secure-random-key" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "How do I deploy to production?",
    "k": 6,
    "filters": {
      "book": "devops-handbook",
      "tag": "deployment"
    }
  }'
```
Response:
```
{
  "answer": "To deploy to production, follow these steps:\n\n1. Run tests [1]\n2. Build artifacts [2]\n3. Deploy with blue-green strategy [3]\n\nEnsure monitoring is active [1].",
  "sources": [
    "Deployment Guide · DevOps Handbook · https://wiki.example.com/books/devops-handbook/page/deployment",
    "CI/CD Pipeline · DevOps Handbook · https://wiki.example.com/books/devops-handbook/page/cicd",
    "Blue-Green Deployments · DevOps Handbook · https://wiki.example.com/books/devops-handbook/page/blue-green"
  ]
}
```

**Raw Retrieval (No LLM)**
```
curl -X POST https://your-api.example.com/retrieve \
  -H "X-API-Key: your-secure-random-key" \
  -H "Content-Type: application/json" \
  -d '{"query": "kubernetes", "k": 3}'
```

**Trigger Reindex**
```
# Full reindex
curl -X POST https://your-api.example.com/ingest/reindex \
  -H "X-API-Key: your-secure-random-key" \
  -H "Content-Type: application/json" \
  -d '{"mode": "full"}'

# Incremental (pages updated after timestamp)
curl -X POST https://your-api.example.com/ingest/reindex \
  -H "X-API-Key: your-secure-random-key" \
  -H "Content-Type: application/json" \
  -d '{
    "mode": "incremental",
    "updated_after": "2025-01-01T00:00:00"
  }'
```


## 🧪 Models
- Embeddings: `sentence-transformers/all-MiniLM-L6-v2` (384-dim, multilingual-capable)
- Re-ranker: `cross-encoder/ms-marco-MiniLM-L-6-v2` (optional)
- LLM: Azure OpenAI (configurable deployment)
Swap models by changing `EMBED_MODEL` and `RERANKER_MODEL` in `.env`.


## 🤝 Contributing
- Fork the repository
- Create a feature branch
- Make your changes
- Test with pytest (add tests as needed)
- Submit a pull request


## ⚖️ License
This project is provided under the [MIT License](https://github.com/sysadminid/BookStack-RAG/blob/main/LICENSE).

The libraries and dependencies used in this project are provided under their own respective licenses.

### Core Dependencies

- [BookStack](https://github.com/BookStackApp/BookStack) - MIT License
- [ChromaDB](https://github.com/chroma-core/chroma) - Apache 2.0 License
- [Sentence-Transformers](https://github.com/UKPLab/sentence-transformers) - Apache 2.0 License
- [FastAPI](https://github.com/tiangolo/fastapi) - MIT License
- [Whoosh](https://github.com/mchaput/whoosh) - BSD License
- [Azure OpenAI Python SDK](https://github.com/openai/openai-python) - MIT License


## 🙏 Acknowledgments
- BookStack for the excellent wiki platform
- ChromaDB for vector storage
- Sentence-Transformers for embeddings
- Whoosh for BM25 search


## 📧 Support
For issues or questions:
- Open an issue on GitHub
- Check existing documentation in code comments
- Review `main.py` for API examples
---
**Built with ❤️ for better knowledge discovery**

