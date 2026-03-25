(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";

  const titleEl = document.getElementById("doc-title");
  const statusEl = document.getElementById("doc-status");
  const metaEl = document.getElementById("doc-meta");
  const tagsEl = document.getElementById("doc-tags");
  const contentEl = document.getElementById("doc-content");
  const alertEl = document.getElementById("doc-alert");

  function setAlert(message, variant) {
    alertEl.className = "alert alert--" + (variant || "info");
    alertEl.textContent = message;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getSlug() {
    const params = new URLSearchParams(window.location.search);
    return (params.get("slug") || "").trim();
  }

  function renderDocument(doc) {
    titleEl.textContent = doc.title || "Sans titre";
    statusEl.textContent = doc.status || "published";
    statusEl.className = "badge badge--" + (doc.status || "published");

    const published = doc.published_at ? new Date(doc.published_at).toLocaleString("fr-FR") : "-";
    const updated = doc.updated_at ? new Date(doc.updated_at).toLocaleString("fr-FR") : "-";
    metaEl.textContent = "Slug: " + (doc.slug || "-") + " | Publié: " + published + " | Mise à jour: " + updated;

    tagsEl.innerHTML = Array.isArray(doc.tags)
      ? doc.tags
          .map(function (tag) {
            return '<span class="badge badge--primary">' + escapeHtml(tag) + "</span>";
          })
          .join(" ")
      : "";

    const safeText = escapeHtml(doc.content || "").replace(/\n/g, "<br>");
    contentEl.innerHTML = safeText || "<p class=\"text-muted\">Contenu vide.</p>";

    setAlert("Document chargé avec succès.", "success");
    document.title = (doc.title || "Document") + " - CMS Documentation";
  }

  async function loadDocument() {
    const slug = getSlug();
    if (!slug) {
      setAlert("Aucun slug fourni dans l'URL.", "danger");
      return;
    }

    try {
      const response = await fetch(API_BASE + "/public/documents/" + encodeURIComponent(slug), {
        headers: { "Content-Type": "application/json" },
      });

      if (!response.ok) {
        const payload = await response.json().catch(function () {
          return {};
        });
        throw new Error(payload.error || "HTTP " + response.status);
      }

      const payload = await response.json();
      renderDocument(payload || {});
    } catch (error) {
      setAlert("Impossible de charger le document: " + error.message, "danger");
      titleEl.textContent = "Document introuvable";
      statusEl.textContent = "Erreur";
      statusEl.className = "badge badge--danger";
      metaEl.textContent = "";
      tagsEl.innerHTML = "";
      contentEl.innerHTML = "";
    }
  }

  loadDocument();
})();
