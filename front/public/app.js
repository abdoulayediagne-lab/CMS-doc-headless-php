(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";

  const statusEl = document.getElementById("status");
  const gridEl = document.getElementById("docs-grid");
  const searchForm = document.getElementById("search-form");
  const searchInput = document.getElementById("search-input");
  const sectionFilter = document.getElementById("section-filter");
  const tagFilter = document.getElementById("tag-filter");
  const cookieBanner = document.getElementById("cookie-banner");
  const cookieAcceptBtn = document.getElementById("cookie-accept");
  const cookieRejectBtn = document.getElementById("cookie-reject");

  const COOKIE_CONSENT_KEY = "cms_cookie_consent";

  let searchQuery = "";
  let selectedSectionId = "";
  let selectedTagSlug = "";

  function flattenSections(sections) {
    const flat = [];

    function walk(items) {
      if (!Array.isArray(items)) {
        return;
      }

      items.forEach(function (section) {
        if (!section || typeof section !== "object") {
          return;
        }

        flat.push({
          id: Number(section.id),
          name: section.name || section.slug || "Section",
        });

        if (Array.isArray(section.children) && section.children.length > 0) {
          walk(section.children);
        }
      });
    }

    walk(sections);
    return flat.filter(function (section) {
      return Number.isFinite(section.id) && section.id > 0;
    });
  }

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
        const section = escapeHtml(doc.section_name || doc.section_slug || "Sans section");
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
          "...</p><p><small class=\"text-muted\">Section: " +
          section +
          "</small></p><div class=\"public-page__tags\">" +
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

  function renderFilterOptions(sections, tags) {
    if (sectionFilter) {
      sectionFilter.innerHTML = ['<option value="">Toutes les sections</option>']
        .concat(
          sections.map(function (section) {
            return (
              '<option value="' +
              section.id +
              '">' +
              escapeHtml(section.name) +
              "</option>"
            );
          }),
        )
        .join("");
      sectionFilter.value = selectedSectionId;
    }

    if (tagFilter) {
      tagFilter.innerHTML = ['<option value="">Tous les tags</option>']
        .concat(
          tags.map(function (tag) {
            return (
              '<option value="' +
              escapeHtml(tag.slug || "") +
              '">' +
              escapeHtml(tag.name || tag.slug || "tag") +
              "</option>"
            );
          }),
        )
        .join("");
      tagFilter.value = selectedTagSlug;
    }
  }

  async function loadTaxonomies() {
    try {
      const response = await fetch(API_BASE + "/public/taxonomies", {
        headers: { "Content-Type": "application/json" },
      });

      if (!response.ok) {
        throw new Error("Erreur API public/taxonomies: " + response.status);
      }

      const payload = await response.json();
      const sections = flattenSections(payload.sections || []);
      const tags = Array.isArray(payload.tags) ? payload.tags : [];
      renderFilterOptions(sections, tags);
    } catch (error) {
      // Keep search usable even if taxonomy filters are unavailable.
    }
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
      const params = new URLSearchParams({
        limit: "12",
        page: "1",
      });

      if (searchQuery) {
        params.set("q", searchQuery);
      }
      if (selectedSectionId) {
        params.set("section_id", selectedSectionId);
      }
      if (selectedTagSlug) {
        params.set("tag", selectedTagSlug);
      }

      const response = await fetch(API_BASE + "/public/documents?" + params.toString(), {
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
      selectedSectionId = sectionFilter && sectionFilter.value ? sectionFilter.value : "";
      selectedTagSlug = tagFilter && tagFilter.value ? tagFilter.value : "";
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
  loadTaxonomies();
  loadDocuments();
})();
