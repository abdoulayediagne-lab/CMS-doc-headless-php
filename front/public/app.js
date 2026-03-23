(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";

  const statusEl = document.getElementById("status");
  const gridEl = document.getElementById("docs-grid");

  function setStatus(message, variant) {
    statusEl.className = "alert alert--" + (variant || "info");
    statusEl.textContent = message;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function renderDocs(items) {
    if (!Array.isArray(items) || items.length === 0) {
      gridEl.innerHTML = "";
      setStatus("Aucun document public pour le moment.", "warning");
      return;
    }

    const html = items
      .map(function (doc) {
        const title = escapeHtml(doc.title || "Sans titre");
        const excerpt = escapeHtml((doc.content || "").slice(0, 140));
        const slug = escapeHtml(doc.slug || "");
        return (
          '<article class="card" aria-label="Document ' +
          title +
          '">' +
          '<div class="card__header"><h3 class="card__title">' +
          title +
          "</h3></div>" +
          '<div class="card__body"><p>' +
          excerpt +
          "...</p></div>" +
          '<div class="card__footer"><small class="text-muted">slug: ' +
          slug +
          "</small></div>" +
          "</article>"
        );
      })
      .join("");

    gridEl.innerHTML = html;
    setStatus(items.length + " document(s) charge(s).", "success");
  }

  async function loadDocuments() {
    try {
      const response = await fetch(API_BASE + "/public/documents?limit=12&page=1", {
        headers: { "Content-Type": "application/json" },
      });

      if (!response.ok) {
        throw new Error("Erreur API public/documents: " + response.status);
      }

      const payload = await response.json();
      renderDocs(payload.data || []);
    } catch (error) {
      gridEl.innerHTML = "";
      setStatus(error.message, "danger");
    }
  }

  loadDocuments();
})();
