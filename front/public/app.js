(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";

  const statusEl = document.getElementById("status");
  const gridEl = document.getElementById("docs-grid");
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");
  const cookieBanner = document.getElementById("cookie-banner");
  const cookieAcceptBtn = document.getElementById("cookie-accept");
  const cookieRejectBtn = document.getElementById("cookie-reject");

  const COOKIE_CONSENT_KEY = "cms_cookie_consent";

  let searchQuery = "";

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
        const author = escapeHtml(doc.author_name || "Equipe Docs");
        const status = escapeHtml(doc.status || "published");
        const tags = Array.isArray(doc.tags)
          ? doc.tags.map(function (tag) {
              return '<span class="badge badge--primary">' + escapeHtml(tag) + "</span>";
            }).join(" ")
          : "";
        return (
          '<article class="card public-page__doc" aria-label="Document ' +
          title +
          '"><a class="public-page__doc-link" href="./document.html?slug=' +
          encodeURIComponent(doc.slug || "") +
          '">' +
          '<div class="card__header"><h3 class="card__title">' +
          title +
          '</h3><span class="badge badge--published">' +
          status +
          "</span></div>" +
          '<div class="card__body"><p>' +
          excerpt +
          "...</p><div class=\"public-page__tags\">" +
          tags +
          "</div></div>" +
          '<div class="card__footer"><small class="text-muted">slug: ' +
          slug +
          " - auteur: " +
          author +
          "</small></div></a></article>"
        );
      })
      .join("");

    gridEl.innerHTML = html;
    setStatus(items.length + " document(s) charge(s).", "success");
  }

  function getConsent() {
    try {
      return localStorage.getItem(COOKIE_CONSENT_KEY);
    } catch (error) {
      return null;
    }
  }

  function setConsent(value) {
    try {
      localStorage.setItem(COOKIE_CONSENT_KEY, value);
    } catch (error) {
      // Ignore storage errors on restricted browsers.
    }
  }

  function updateCookieBanner() {
    if (!cookieBanner) {
      return;
    }

    const consent = getConsent();
    const mustDisplay = consent !== "accepted" && consent !== "rejected";
    cookieBanner.hidden = !mustDisplay;
  }

  async function loadDocuments() {
    try {
      const queryPart = searchQuery ? "&q=" + encodeURIComponent(searchQuery) : "";
      const response = await fetch(API_BASE + "/public/documents?limit=12&page=1" + queryPart, {
        headers: { "Content-Type": "application/json" },
      });

      if (!response.ok) {
        throw new Error("Erreur API public/documents: " + response.status);
      }

      const payload = await response.json();
      renderDocs(payload.data || []);
    } catch (error) {
      gridEl.innerHTML = "";
      setStatus("Impossible de charger les documents: " + error.message, "danger");
    }
  }

  if (searchForm) {
    searchForm.addEventListener("submit", function (event) {
      event.preventDefault();
      searchQuery = (searchInput && searchInput.value ? searchInput.value : "").trim();
      loadDocuments();
    });
  }

  if (cookieAcceptBtn) {
    cookieAcceptBtn.addEventListener("click", function () {
      setConsent("accepted");
      updateCookieBanner();
    });
  }

  if (cookieRejectBtn) {
    cookieRejectBtn.addEventListener("click", function () {
      setConsent("rejected");
      updateCookieBanner();
    });
  }

  updateCookieBanner();
  loadDocuments();
})();
