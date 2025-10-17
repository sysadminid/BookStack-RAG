

{{-- This is a placeholder template file provided as a --}}
{{-- convenience to users of the visual theme system. --}}

@auth
  @php($nonce = $cspNonce ?? '')

  {{-- Optional: extra guard to never show on login/register/password pages --}}
  @if (!request()->routeIs('login*') && !request()->routeIs('register*') && !request()->routeIs('password*'))

  <footer>
    <div id="ragfx-root"></div>

    <script nonce="{{ $nonce }}">
    (() => {
      const root = document.getElementById("ragfx-root");
      if (!root) return;

      const shadow = root.attachShadow({ mode: "open" });

      // Define your API endpoint here
      const API = "/rag/search";

      // Create styles (add the same CSP nonce so style-src passes)
      const style = document.createElement("style");
      if ("{{ $nonce }}") style.setAttribute("nonce", "{{ $nonce }}");
      style.textContent = `
        :host { all: initial; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes typewriter {
            from { width: 0; }
            to { width: 100%; }
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 2147483000;
            width: 64px;
            height: 64px;
            border-radius: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: slideUp 0.5s ease-out;
            overflow: hidden;
        }

        .fab::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .fab:hover::before {
            left: 100%;
        }

        .fab:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 48px rgba(102, 126, 234, 0.5);
        }

        .fab:active {
            transform: translateY(-2px) scale(1.02);
        }

        .fab-icon {
            width: 28px;
            height: 28px;
            transition: transform 0.3s ease;
        }

        .fab:hover .fab-icon {
            transform: rotate(10deg) scale(1.1);
        }

        /* Overlay */
        .overlay {
            position: fixed;
            inset: 0;
            z-index: 2147482999;
            background: rgba(0, 0, 0, 0);
            backdrop-filter: blur(0px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .overlay.show {
            opacity: 1;
            pointer-events: auto;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
        }

        /* Main Container */
        .container {
            position: fixed;
            inset: 0;
            z-index: 2147483001;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            padding: 20px;
        }

        .panel {
            width: min(900px, 95vw);
            max-height: 85vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(40px) scale(0.9);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .overlay.show ~ .container .panel {
            transform: translateY(0) scale(1);
            opacity: 1;
            pointer-events: auto;
        }

        /* Header */
        .header {
            padding: 24px 28px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            border-bottom: 1px solid rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideUp 0.5s ease-out 0.1s both;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font: 700 18px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }

        .brand-icon {
            font-size: 24px;
            animation: pulse 2s infinite;
        }

        .badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: shimmer 3s infinite linear;
            background-size: 1000px 100%;
        }

        .close-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(15, 23, 42, 0.05);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #64748b;
        }

        .close-btn:hover {
            background: rgba(15, 23, 42, 0.1);
            transform: rotate(90deg);
            color: #334155;
        }

        /* Search Section */
        .search-section {
            padding: 32px 28px 24px;
            animation: slideUp 0.5s ease-out 0.2s both;
        }

        .search-box {
            position: relative;
            display: flex;
            gap: 12px;
        }

        .search-input-wrapper {
            flex: 1;
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .search-input {
            width: 100%;
            padding: 18px 24px 18px 56px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font: 500 16px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .search-input:focus ~ .search-icon {
            color: #667eea;
        }

        .search-btn {
            padding: 0 28px;
            border-radius: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font: 700 15px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            transition: all 0.3s ease;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
        }

        .search-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .search-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .search-hint {
            margin-top: 12px;
            color: #94a3b8;
            font: 13px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .kbd {
            background: linear-gradient(to bottom, #f8fafc, #e2e8f0);
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 2px 8px;
            font: 600 11px/1.5 'SF Mono', 'Monaco', 'Inconsolata', monospace;
            box-shadow: 0 2px 0 #cbd5e1;
            color: #475569;
        }

        /* Results Section */
        .results {
            flex: 1;
            overflow-y: auto;
            padding: 0 28px 24px;
            min-height: 0;
        }

        .results::-webkit-scrollbar {
            width: 8px;
        }

        .results::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 4px;
        }

        .results::-webkit-scrollbar-thumb {
            background: rgba(102, 126, 234, 0.3);
            border-radius: 4px;
        }

        .results::-webkit-scrollbar-thumb:hover {
            background: rgba(102, 126, 234, 0.5);
        }

        .answer-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
            border: 1px solid rgba(102, 126, 234, 0.15);
            border-radius: 16px;
            padding: 24px;
            animation: slideUp 0.5s ease-out;
            position: relative;
            overflow: hidden;
        }

        .answer-card::before {
            content: '✨';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 20px;
            opacity: 0.3;
        }

        .answer-content {
            color: #1e293b;
            font: 15px/1.7 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .answer-content h1, .answer-content h2, .answer-content h3,
        .answer-content h4, .answer-content h5, .answer-content h6 {
            margin: 16px 0 8px;
            font-weight: 700;
            color: #0f172a;
        }

        .answer-content p {
            margin: 12px 0;
        }

        .answer-content code {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 6px;
            padding: 2px 6px;
            font: 14px/1.5 'SF Mono', 'Monaco', 'Inconsolata', monospace;
            color: #6366f1;
        }

        .answer-content pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 12px;
            overflow-x: auto;
            margin: 16px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .answer-content pre code {
            background: none;
            border: none;
            color: inherit;
            padding: 0;
        }

        .answer-content strong {
            color: #0f172a;
            font-weight: 600;
        }

        .answer-content em {
            color: #475569;
        }

        .answer-content ul, .answer-content ol {
            margin: 12px 0;
            padding-left: 24px;
        }

        .answer-content li {
            margin: 6px 0;
        }

        .answer-content a {
            color: #667eea;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s ease;
        }

        .answer-content a:hover {
            border-bottom-color: #667eea;
        }

        /* Sources */
        .sources {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px dashed rgba(102, 126, 234, 0.2);
            animation: slideUp 0.5s ease-out 0.2s both;
        }

        .sources-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font: 700 14px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #475569;
            margin-bottom: 12px;
        }

        .sources-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .source-item {
            display: flex;
            align-items: start;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            animation: slideUp 0.3s ease-out both;
            text-decoration: none;
            color: inherit;
        }

        .source-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            transform: translateX(4px);
        }

        .source-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font: 700 11px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .source-content {
            flex: 1;
            font: 13px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #475569;
        }

        .source-content:hover {
            color: #667eea;
        }

        /* Loading State */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            flex-direction: column;
            gap: 16px;
            animation: fadeIn 0.3s ease-out;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid rgba(102, 126, 234, 0.1);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        .loading-text {
            color: #64748b;
            font: 14px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* Error State */
        .error {
            background: rgba(239, 68, 68, 0.05);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 16px;
            padding: 20px;
            color: #dc2626;
            font: 14px/1.5 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            animation: slideUp 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .error-icon {
            font-size: 20px;
        }

        /* Footer */
        .footer {
            padding: 16px 28px;
            border-top: 1px solid rgba(102, 126, 234, 0.1);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #64748b;
            font: 12px/1 -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            animation: slideUp 0.5s ease-out 0.3s both;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #10b981;
            animation: pulse 2s infinite;
        }

        /* Mobile Responsive */
        @media (max-width: 640px) {
            .fab {
            right: 16px;
            bottom: 16px;
            width: 56px;
            height: 56px;
            }

            .panel {
            width: 100vw;
            max-height: 100vh;
            border-radius: 0;
            }

            .header {
            padding: 20px;
            }

            .search-section {
            padding: 24px 20px 20px;
            }

            .search-box {
            flex-direction: column;
            }

            .search-btn {
            width: 100%;
            padding: 16px;
            justify-content: center;
            }

            .results {
            padding: 0 20px 20px;
            }
        }
      `;
      shadow.appendChild(style);

      // Structure
      const overlay = document.createElement("div");
      overlay.className = "overlay";

      const container = document.createElement("div");
      container.className = "container";

      const panel = document.createElement("div");
      panel.className = "panel";
      panel.innerHTML = `
        <div class="header">
        <div class="brand">
            <span class="brand-icon">📚</span>
            <span>BookStack</span>
            <span class="badge">AI Search</span>
        </div>
        <button class="close-btn" id="rag-close" title="Close (Esc)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        </div>

        <div class="search-section">
        <div class="search-box">
            <div class="search-input-wrapper">
            <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
                id="rag-q"
                class="search-input"
                placeholder="Ask anything about your wiki..."
                autocomplete="off"
            />
            </div>
            <button id="rag-go" class="search-btn">
            <span>Search</span>
            <span>🚀</span>
            </button>
        </div>
        <div class="search-hint">
            💡 Get AI-powered answers with sources • Press <span class="kbd">Ctrl+K</span> or <span class="kbd">⌘K</span> to open
        </div>
        </div>

        <div id="rag-results" class="results"></div>

        <div class="footer">
        <div class="status">
            <span class="status-dot"></span>
            <span id="rag-status">Ready</span>
        </div>
        <div>
            <span class="kbd">Esc</span> to close
        </div>
        </div>
      `;

      container.appendChild(panel);

      const fab = document.createElement("button");
      fab.className = "fab";
      fab.setAttribute("title", "AI Search (Ctrl+K)");
      fab.innerHTML = `
          <svg class="fab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
      `;

      shadow.appendChild(fab);
      shadow.appendChild(overlay);
      shadow.appendChild(container);

      const $ = (sel) => panel.querySelector(sel);
      const qEl = $("#rag-q");
      const goEl = $("#rag-go");
      const resultsEl = $("#rag-results");
      const statusEl = $("#rag-status");
      const closeBtn = $("#rag-close");

      const open = () => {
          overlay.classList.add("show");
          setTimeout(() => qEl.focus(), 100);
      };

      const close = () => {
          overlay.classList.remove("show");
          setTimeout(() => {
          resultsEl.innerHTML = "";
          qEl.value = "";
          statusEl.textContent = "Ready";
          }, 400);
      };

      fab.addEventListener("click", open);
      closeBtn.addEventListener("click", close);
      overlay.addEventListener("click", (e) => {
          if (e.target === overlay) close();
      });

      window.addEventListener("keydown", (e) => {
          const mac = /Mac|iPhone|iPad/.test(navigator.platform);
          if ((mac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === "k") {
          e.preventDefault();
          open();
          }
          if (overlay.classList.contains("show") && e.key === "Escape") {
          e.preventDefault();
          close();
          }
      });

      qEl.addEventListener("keydown", (e) => {
          if (e.key === "Enter") {
          e.preventDefault();
          performSearch();
          }
      });

      const renderMarkdown = (text) => {
          let s = (text || "").replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));

          s = s.replace(/```([\s\S]*?)```/g, (_,code) =>
          `<pre><code>${code.replace(/</g,"&lt;")}</code></pre>`
          );

          s = s.replace(/`([^`]+)`/g, '<code>$1</code>');

          s = s.replace(/^###### (.*)$/gm,'<h6>$1</h6>')
              .replace(/^##### (.*)$/gm,'<h5>$1</h5>')
              .replace(/^#### (.*)$/gm,'<h4>$1</h4>')
              .replace(/^### (.*)$/gm,'<h3>$1</h3>')
              .replace(/^## (.*)$/gm,'<h2>$1</h2>')
              .replace(/^# (.*)$/gm,'<h1>$1</h1>');

          s = s.replace(/\*\*([^*]+)\*\*/g,'<strong>$1</strong>')
              .replace(/\*([^*]+)\*/g,'<em>$1</em>')
              .replace(/__([^_]+)__/g,'<strong>$1</strong>')
              .replace(/_([^_]+)_/g,'<em>$1</em>');

          s = s.replace(/\[(\d+)\]\((https?:\/\/[^\s)]+)\)/g,
          '<a href="$2" target="_blank" rel="noopener" style="color:#667eea;text-decoration:none;font-weight:600">[$1]</a>'
          );

          s = s.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
            '<a href="$2" target="_blank" rel="noopener">$1</a>'
          );

          s = s.replace(/^\s*-\s+(.*)$/gm,'<li>$1</li>')
              .replace(/(<li>[\s\S]*?<\/li>)/g,'<ul>$1</ul>');

          s = s.replace(/^(?!<h\d|<ul|<pre|<p|<blockquote|<li)(.+)$/gm,'<p>$1</p>');

          return s;
      };

      const renderResults = (answer, sources = []) => {
          const answerHtml = `
          <div class="answer-card">
              <div class="answer-content">
              ${renderMarkdown(answer)}
              </div>
          </div>
          `;

          const sourcesHtml = sources.length > 0 ? `
          <div class="sources">
              <div class="sources-header">
              <span>📌</span>
              <span>Sources</span>
              </div>
              <div class="sources-list">
              ${sources.map((source, i) => {
                  const parts = String(source).split(" · ");
                  const url = parts[parts.length - 1] || "#";
                  const title = parts.slice(0, -1).join(" · ") || source;
                  return `
                  <a href="${url}" target="_blank" rel="noopener" class="source-item" style="animation-delay: ${i * 0.05}s">
                      <span class="source-number">${i + 1}</span>
                      <span class="source-content">${escapeHtml(title)}</span>
                  </a>
                  `;
              }).join("")}
              </div>
          </div>
          ` : "";

          resultsEl.innerHTML = answerHtml + sourcesHtml;
      };

      const renderLoading = () => {
          resultsEl.innerHTML = `
          <div class="loading">
              <div class="spinner"></div>
              <div class="loading-text">Searching through your knowledge base...</div>
          </div>
          `;
      };

      const renderError = (error) => {
          resultsEl.innerHTML = `
          <div class="error">
              <span class="error-icon">⚠️</span>
              <span>Error: ${escapeHtml(String(error))}</span>
          </div>
          `;
      };

      const escapeHtml = (text) =>
          (text || "").replace(/[&<>"']/g, c =>
          ({ "&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;" }[c])
          );

      const performSearch = async () => {
          const query = qEl.value.trim();
          if (!query) return;

          goEl.disabled = true;
          statusEl.textContent = "Searching...";
          renderLoading();

          // Optional: infer book slug from current URL
          const filters = {};
          try {
          const path = location.pathname.split("/").filter(Boolean);
          const iBooks = path.indexOf("books");
          if (iBooks >= 0 && path[iBooks + 1]) {
              filters.book = path[iBooks + 1];
          }
          } catch {}

          try {
          const response = await fetch(API, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ query, k: 3, filters })
          });

          if (!response.ok) {
              throw new Error(`HTTP ${response.status}`);
          }

          const data = await response.json();
          renderResults(data.answer || "No answer found", data.sources || []);
          statusEl.textContent = "Ready";
          } catch (error) {
          renderError(error);
          statusEl.textContent = "Error";
          } finally {
          goEl.disabled = false;
          }
      };

      // Attach search button click
      goEl.addEventListener("click", performSearch);
    })();
    </script>
  </footer>
  @endif
@endauth

