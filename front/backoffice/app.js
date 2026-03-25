(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";
  const PUBLIC_FRONT_URL = window.PUBLIC_FRONT_URL || "http://localhost:5173";
  const TOKEN_KEY = "cms_access_token";
  const DOCUMENT_STATUSES = ["draft", "review", "published", "archived"];

  const loginView = document.getElementById("view-login");
  const registerView = document.getElementById("view-register");
  const dashboardView = document.getElementById("view-dashboard");
  const documentsView = document.getElementById("view-documents");

  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");
  const documentFilterForm = document.getElementById("documents-filter-form");
  const documentForm = document.getElementById("document-form");
  const tagCreateForm = document.getElementById("tag-create-form");
  const sectionCreateForm = document.getElementById("section-create-form");

  const authStatus = document.getElementById("auth-status");
  const registerStatus = document.getElementById("register-status");
  const dashboardAlert = document.getElementById("dashboard-alert");
  const dashboardIntro = document.getElementById("dashboard-intro");
  const dashboardRolePanel = document.getElementById("dashboard-role-panel");
  const dashboardRoleTitle = document.getElementById("dashboard-role-title");
  const dashboardRoleDescription = document.getElementById(
    "dashboard-role-description",
  );
  const dashboardOpenDocuments = document.getElementById(
    "dashboard-open-documents",
  );
  const dashboardOpenPublic = document.getElementById("dashboard-open-public");
  const dashboardQuickCreateForm = document.getElementById(
    "dashboard-quick-create-form",
  );
  const dashboardQuickCreateStatus = document.getElementById(
    "dashboard-quick-create-status",
  );
  const quickDocumentTitle = document.getElementById("quick-document-title");
  const quickDocumentSlug = document.getElementById("quick-document-slug");
  const quickDocumentStatus = document.getElementById("quick-document-status");
  const quickDocumentTags = document.getElementById("quick-document-tags");
  const quickDocumentContent = document.getElementById(
    "quick-document-content",
  );
  const documentsStatus = document.getElementById("documents-status");
  const adminStatus = document.getElementById("admin-status");
  const documentFormStatus = document.getElementById("document-form-status");
  const adminUsersCount = document.getElementById("admin-users-count");
  const adminDocumentsCount = document.getElementById("admin-documents-count");

  const currentUserBadge = document.getElementById("current-user");
  const dashboardAdmin = document.getElementById("dashboard-admin");

  const usersBody = document.getElementById("users-body");
  const tagsBody = document.getElementById("tags-body");
  const sectionsBody = document.getElementById("sections-body");
  const logsBody = document.getElementById("logs-body");
  const documentsBody = document.getElementById("documents-body");

  const docSearchInput = document.getElementById("doc-search");
  const docStatusFilter = document.getElementById("doc-status-filter");
  const docSectionFilter = document.getElementById("doc-section-filter");
  const docTagFilter = document.getElementById("doc-tag-filter");

  const refreshDocumentsBtn = document.getElementById("refresh-documents-btn");
  const loadUsersBtn = document.getElementById("load-users-btn");
  const loadTagsBtn = document.getElementById("load-tags-btn");
  const loadSectionsBtn = document.getElementById("load-sections-btn");
  const loadLogsBtn = document.getElementById("load-logs-btn");
  const logoutBtn = document.getElementById("logout-btn");

  const navLogin = document.getElementById("nav-login");
  const navRegister = document.getElementById("nav-register");
  const navDashboard = document.getElementById("nav-dashboard");
  const navDocuments = document.getElementById("nav-documents");

  const documentDetail = document.getElementById("document-detail");
  const documentDetailTitle = document.getElementById("document-detail-title");
  const documentDetailMeta = document.getElementById("document-detail-meta");
  const documentDetailContent = document.getElementById(
    "document-detail-content",
  );

  const documentEditor = document.getElementById("document-editor");
  const documentEditorTitle = document.getElementById("document-editor-title");
  const documentEditorReset = document.getElementById("document-editor-reset");
  const documentIdInput = document.getElementById("document-id");
  const documentTitleInput = document.getElementById("document-title");
  const documentSlugInput = document.getElementById("document-slug");
  const documentContentInput = document.getElementById("document-content");
  const documentStatusInput = document.getElementById("document-status");
  const documentSectionInput = document.getElementById("document-section");
  const documentTagsInput = document.getElementById("document-tags");
  const documentMetaTitleInput = document.getElementById("document-meta-title");
  const documentMetaDescriptionInput = document.getElementById(
    "document-meta-description",
  );

  let currentUser = null;
  let knownTags = [];
  let knownSections = [];

  function setAlert(element, variant, message) {
    if (!element) {
      return;
    }
    element.className = "alert alert--" + variant;
    element.textContent = message;
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/\"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getToken() {
    return localStorage.getItem(TOKEN_KEY);
  }

  function setToken(token) {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    }
  }

  function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
  }

  function isAuthenticated() {
    return !!currentUser;
  }

  function isAdmin() {
    return isAuthenticated() && currentUser.role === "admin";
  }

  function canCreateDocuments() {
    return (
      isAuthenticated() &&
      (currentUser.role === "admin" || currentUser.role === "editor")
    );
  }

  function canUseDocumentEditor() {
    return (
      isAuthenticated() &&
      ["admin", "editor", "author"].indexOf(currentUser.role) >= 0
    );
  }

  function canEditDocument(doc) {
    if (!currentUser) {
      return false;
    }
    if (currentUser.role === "admin") {
      return true;
    }
    return (
      ["editor", "author"].indexOf(currentUser.role) >= 0 &&
      Number(doc.author_id) === Number(currentUser.id)
    );
  }

  function setRoute(route) {
    const normalized = route.startsWith("#/")
      ? route
      : "#/" + route.replace(/^\//, "");
    if (window.location.hash !== normalized) {
      window.location.hash = normalized;
      return;
    }
    renderRoute();
  }

  async function api(path, options) {
    const token = getToken();
    const headers = Object.assign(
      { "Content-Type": "application/json" },
      (options && options.headers) || {},
    );
    if (token) {
      headers.Authorization = "Bearer " + token;
    }

    const response = await fetch(
      API_BASE + path,
      Object.assign({}, options, { headers }),
    );
    const text = await response.text();
    let payload = {};

    try {
      payload = text ? JSON.parse(text) : {};
    } catch (error) {
      payload = { raw: text };
    }

    if (!response.ok) {
      throw new Error(payload.error || "HTTP " + response.status);
    }

    return payload;
  }

  function updateNavigation() {
    const loggedIn = isAuthenticated();

    navLogin.hidden = loggedIn;
    navRegister.hidden = loggedIn;
    navDashboard.hidden = !loggedIn;
    navDocuments.hidden = !loggedIn;
    logoutBtn.hidden = !loggedIn;

    // Enforce visibility even if a CSS rule overrides the [hidden] attribute.
    navLogin.style.display = loggedIn ? "none" : "inline-flex";
    navRegister.style.display = loggedIn ? "none" : "inline-flex";
    navDashboard.style.display = loggedIn ? "inline-flex" : "none";
    navDocuments.style.display = loggedIn ? "inline-flex" : "none";
    logoutBtn.style.display = loggedIn ? "inline-flex" : "none";
  }

  function renderRoute() {
    let hash = window.location.hash || "#/login";

    if (
      !isAuthenticated() &&
      (hash === "#/dashboard" || hash === "#/documents")
    ) {
      hash = "#/login";
      window.location.hash = hash;
    }

    loginView.hidden = true;
    registerView.hidden = true;
    dashboardView.hidden = true;
    documentsView.hidden = true;

    if (hash === "#/register") {
      registerView.hidden = false;
      return;
    }

    if (hash === "#/dashboard") {
      dashboardView.hidden = false;
      if (isAdmin()) {
        loadUsers();
        loadTags();
        loadSections();
      }
      return;
    }

    if (hash === "#/documents") {
      documentsView.hidden = false;
      if (isAuthenticated()) {
        loadDocuments();
      }
      return;
    }

    loginView.hidden = false;
  }

  function renderDashboard() {
    if (!currentUser) {
      dashboardIntro.textContent = "Connecte-toi pour charger ton espace.";
      setAlert(dashboardAlert, "info", "Selectionne une vue via le menu.");
      dashboardAdmin.hidden = true;
      dashboardRolePanel.hidden = true;
      return;
    }

    dashboardIntro.textContent =
      "Bienvenue " +
      (currentUser.username || currentUser.email || "utilisateur") +
      " (" +
      currentUser.role +
      ").";
    currentUserBadge.textContent =
      (currentUser.username || currentUser.email || "utilisateur") +
      " - " +
      currentUser.role;
    dashboardOpenPublic.href = PUBLIC_FRONT_URL;

    dashboardRolePanel.hidden = false;
    dashboardOpenDocuments.hidden = false;
    dashboardOpenPublic.hidden = true;
    dashboardQuickCreateForm.hidden = true;
    dashboardQuickCreateStatus.hidden = true;

    if (isAdmin()) {
      dashboardAdmin.hidden = false;
      setAlert(
        dashboardAlert,
        "success",
        "Tu as acces aux outils d'administration ci-dessous.",
      );
      setAlert(adminStatus, "info", "Gestion admin active.");
      dashboardRoleTitle.textContent = "Actions rapides";
      dashboardRoleDescription.textContent =
        "Tu peux gerer le backoffice et creer rapidement un document.";
      dashboardQuickCreateForm.hidden = false;
      dashboardQuickCreateStatus.hidden = false;
      setAlert(
        dashboardQuickCreateStatus,
        "info",
        "Formulaire rapide actif pour creer un document.",
      );
      loadAdminStats();
    } else if (currentUser.role === "editor") {
      dashboardAdmin.hidden = true;
      setAlert(
        dashboardAlert,
        "info",
        "Tu peux gerer tes documents depuis ce dashboard ou l'onglet Documents.",
      );
      dashboardRoleTitle.textContent = "Creation rapide";
      dashboardRoleDescription.textContent =
        "Creer un document ici, puis gere la liste detaillee dans l'onglet Documents.";
      dashboardQuickCreateForm.hidden = false;
      dashboardQuickCreateStatus.hidden = false;
      setAlert(
        dashboardQuickCreateStatus,
        "info",
        "Tu peux creer des documents et modifier les tiens.",
      );
    } else if (currentUser.role === "author") {
      dashboardAdmin.hidden = true;
      setAlert(
        dashboardAlert,
        "info",
        "Role auteur: edition limitee aux documents dont tu es proprietaire.",
      );
      dashboardRoleTitle.textContent = "Navigation auteur";
      dashboardRoleDescription.textContent =
        "Ce role ne cree pas de nouveau document ici. Utilise l'onglet Documents pour consulter le contenu disponible.";
    } else {
      dashboardAdmin.hidden = true;
      setAlert(
        dashboardAlert,
        "info",
        "Compte lecteur: acces en lecture uniquement.",
      );
      dashboardRoleTitle.textContent = "Acces lecture";
      dashboardRoleDescription.textContent =
        "Tu n'as pas de droits CRUD dans le backoffice. Ouvre la vitrine publique pour lire les documents.";
      dashboardOpenDocuments.hidden = true;
      dashboardOpenPublic.hidden = false;
    }
  }

  async function saveDocument(payload, documentId) {
    if (documentId) {
      await api("/documents/" + documentId, {
        method: "PUT",
        body: JSON.stringify(payload),
      });
      return;
    }

    await api("/documents", {
      method: "POST",
      body: JSON.stringify(payload),
    });
  }

  function renderTaxonomySelectors() {
    docSectionFilter.innerHTML = ['<option value="">Toutes</option>']
      .concat(
        knownSections.map(function (section) {
          return (
            '<option value="' +
            section.id +
            '">' +
            escapeHtml(section.name || section.slug || "#" + section.id) +
            "</option>"
          );
        }),
      )
      .join("");

    documentSectionInput.innerHTML = ['<option value="">Aucune</option>']
      .concat(
        knownSections.map(function (section) {
          return (
            '<option value="' +
            section.id +
            '">' +
            escapeHtml(section.name || section.slug || "#" + section.id) +
            "</option>"
          );
        }),
      )
      .join("");

    docTagFilter.innerHTML = ['<option value="">Tous</option>']
      .concat(
        knownTags.map(function (tag) {
          return (
            '<option value="' +
            escapeHtml(tag.slug) +
            '">' +
            escapeHtml(tag.name || tag.slug) +
            "</option>"
          );
        }),
      )
      .join("");
  }

  async function loadPublicTaxonomies() {
    try {
      const [sectionsPayload, tagsPayload] = await Promise.all([
        api("/public/sections?limit=200&page=1", { method: "GET" }),
        api("/public/tags?limit=200&page=1", { method: "GET" }),
      ]);
      knownSections = sectionsPayload.data || [];
      knownTags = tagsPayload.data || [];
      renderTaxonomySelectors();
    } catch (error) {
      // Keep UI usable even if taxonomies are unavailable.
    }
  }

  function statusBadge(status) {
    const safe = DOCUMENT_STATUSES.indexOf(status) >= 0 ? status : "info";
    return (
      '<span class="badge badge--' +
      safe +
      '">' +
      escapeHtml(status || "-") +
      "</span>"
    );
  }

  function parseTags(value) {
    if (!value) {
      return [];
    }

    return value
      .split(/[\n,]/)
      .map(function (tag) {
        return tag.trim().toLowerCase();
      })
      .filter(function (tag) {
        return tag.length > 0;
      });
  }

  function renderDocuments(items) {
    if (!Array.isArray(items) || items.length === 0) {
      documentsBody.innerHTML = "";
      setAlert(documentsStatus, "warning", "Aucun document pour ce filtre.");
      return;
    }

    documentsBody.innerHTML = items
      .map(function (doc) {
        const tags =
          Array.isArray(doc.tags) && doc.tags.length > 0
            ? doc.tags.join(", ")
            : "-";
        const actions = [
          '<button class="btn btn--outline btn--sm" type="button" data-action="view" data-id="' +
            doc.id +
            '">Lire</button>',
        ];

        if (canEditDocument(doc)) {
          actions.push(
            '<button class="btn btn--secondary btn--sm" type="button" data-action="edit" data-id="' +
              doc.id +
              '">Modifier</button>',
          );
        }

        if (isAdmin()) {
          actions.push(
            '<button class="btn btn--danger btn--sm" type="button" data-action="delete" data-id="' +
              doc.id +
              '">Supprimer</button>',
          );
        }

        return (
          "<tr>" +
          "<td>" +
          doc.id +
          "</td>" +
          "<td>" +
          escapeHtml(doc.title || "-") +
          "</td>" +
          "<td>" +
          escapeHtml(doc.author_name || "-") +
          "</td>" +
          "<td>" +
          statusBadge(doc.status) +
          "</td>" +
          "<td>" +
          escapeHtml(tags) +
          "</td>" +
          '<td class="backoffice-page__row-actions">' +
          actions.join(" ") +
          "</td>" +
          "</tr>"
        );
      })
      .join("");

    setAlert(
      documentsStatus,
      "success",
      items.length + " document(s) charge(s).",
    );
  }

  async function loadDocumentDetail(id) {
    try {
      const payload = await api("/documents/" + id, { method: "GET" });
      documentDetailTitle.textContent = payload.title || "Sans titre";
      documentDetailMeta.textContent =
        "Slug: " +
        (payload.slug || "-") +
        " | Statut: " +
        (payload.status || "-") +
        " | MAJ: " +
        (payload.updated_at || "-");
      documentDetailContent.innerHTML = escapeHtml(
        payload.content || "",
      ).replace(/\n/g, "<br>");
      documentDetail.hidden = false;
    } catch (error) {
      setAlert(
        documentsStatus,
        "danger",
        "Impossible de charger le detail: " + error.message,
      );
    }
  }

  function resetDocumentForm() {
    documentIdInput.value = "";
    documentForm.reset();
    documentStatusInput.value = "draft";
    documentEditorTitle.textContent = "Nouveau document";
    setAlert(
      documentFormStatus,
      "info",
      "Creation reservee aux editeurs/admins. Les auteurs peuvent modifier leurs documents existants.",
    );
  }

  async function openDocumentForEdit(id) {
    try {
      const payload = await api("/documents/" + id, { method: "GET" });
      if (!canEditDocument(payload)) {
        setAlert(
          documentsStatus,
          "warning",
          "Tu ne peux modifier que tes documents.",
        );
        return;
      }

      documentIdInput.value = payload.id;
      documentTitleInput.value = payload.title || "";
      documentSlugInput.value = payload.slug || "";
      documentContentInput.value = payload.content || "";
      documentStatusInput.value = payload.status || "draft";
      documentSectionInput.value = payload.section_id || "";
      documentTagsInput.value = Array.isArray(payload.tags)
        ? payload.tags.join(", ")
        : "";
      documentMetaTitleInput.value = payload.meta_title || "";
      documentMetaDescriptionInput.value = payload.meta_description || "";
      documentEditorTitle.textContent = "Edition document #" + payload.id;
      setAlert(
        documentFormStatus,
        "info",
        "Document charge dans le formulaire.",
      );
      documentEditor.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (error) {
      setAlert(
        documentsStatus,
        "danger",
        "Impossible de charger le document: " + error.message,
      );
    }
  }

  async function loadDocuments() {
    if (!isAuthenticated()) {
      return;
    }

    try {
      setAlert(documentsStatus, "info", "Chargement des documents...");
      const params = new URLSearchParams({ page: "1", limit: "50" });

      if (docSearchInput.value.trim()) {
        params.set("q", docSearchInput.value.trim());
      }
      if (docStatusFilter.value) {
        params.set("status", docStatusFilter.value);
      }
      if (docSectionFilter.value) {
        params.set("section_id", docSectionFilter.value);
      }
      if (docTagFilter.value) {
        params.set("tag", docTagFilter.value);
      }

      const payload = await api("/documents?" + params.toString(), {
        method: "GET",
      });
      renderDocuments(payload.data || []);
    } catch (error) {
      documentsBody.innerHTML = "";
      setAlert(
        documentsStatus,
        "danger",
        "Impossible de charger: " + error.message,
      );
    }
  }

  function renderUsers(users) {
    if (!Array.isArray(users) || users.length === 0) {
      usersBody.innerHTML = "";
      setAlert(adminStatus, "warning", "Aucun utilisateur.");
      return;
    }

    usersBody.innerHTML = users
      .map(function (user) {
        return (
          "<tr>" +
          "<td>" +
          user.id +
          "</td>" +
          "<td>" +
          escapeHtml(user.username) +
          "</td>" +
          "<td>" +
          escapeHtml(user.email) +
          "</td>" +
          '<td><select class="form__select" data-user-role="' +
          user.id +
          '">' +
          '<option value="reader"' +
          (user.role === "reader" ? " selected" : "") +
          ">reader</option>" +
          '<option value="author"' +
          (user.role === "author" ? " selected" : "") +
          ">author</option>" +
          '<option value="editor"' +
          (user.role === "editor" ? " selected" : "") +
          ">editor</option>" +
          '<option value="admin"' +
          (user.role === "admin" ? " selected" : "") +
          ">admin</option>" +
          "</select></td>" +
          '<td><input type="checkbox" data-user-active="' +
          user.id +
          '"' +
          (user.is_active ? " checked" : "") +
          " /></td>" +
          '<td class="backoffice-page__row-actions"><button class="btn btn--secondary btn--sm" type="button" data-action="save-user" data-id="' +
          user.id +
          '">Sauver</button><button class="btn btn--danger btn--sm" type="button" data-action="delete-user" data-id="' +
          user.id +
          '">Supprimer</button></td>' +
          "</tr>"
        );
      })
      .join("");

    setAlert(
      adminStatus,
      "success",
      users.length + " utilisateur(s) charge(s).",
    );
  }

  async function loadUsers() {
    if (!isAdmin()) {
      return;
    }
    const payload = await api("/admin/users?page=1&limit=50", {
      method: "GET",
    });
    renderUsers(payload.data || []);
  }

  async function loadAdminStats() {
    if (!isAdmin()) {
      if (adminUsersCount) {
        adminUsersCount.textContent = "-";
      }
      if (adminDocumentsCount) {
        adminDocumentsCount.textContent = "-";
      }
      return;
    }

    try {
      const [usersPayload, documentsPayload] = await Promise.all([
        api("/admin/users?page=1&limit=1", { method: "GET" }),
        api("/documents?page=1&limit=1", { method: "GET" }),
      ]);

      if (adminUsersCount) {
        adminUsersCount.textContent = String(
          (usersPayload.pagination && usersPayload.pagination.total) || 0,
        );
      }
      if (adminDocumentsCount) {
        adminDocumentsCount.textContent = String(
          (documentsPayload.pagination && documentsPayload.pagination.total) ||
            0,
        );
      }
    } catch (error) {
      if (adminUsersCount) {
        adminUsersCount.textContent = "-";
      }
      if (adminDocumentsCount) {
        adminDocumentsCount.textContent = "-";
      }
    }
  }

  function renderTags(tags) {
    tagsBody.innerHTML = (tags || [])
      .map(function (tag) {
        return (
          "<tr>" +
          "<td>" +
          tag.id +
          "</td>" +
          '<td><input class="form__input" data-tag-name="' +
          tag.id +
          '" value="' +
          escapeHtml(tag.name || "") +
          '" /></td>' +
          '<td><input class="form__input" data-tag-slug="' +
          tag.id +
          '" value="' +
          escapeHtml(tag.slug || "") +
          '" /></td>' +
          '<td class="backoffice-page__row-actions">' +
          '<button class="btn btn--secondary btn--sm" type="button" data-action="save-tag" data-id="' +
          tag.id +
          '">Sauver</button> ' +
          '<button class="btn btn--danger btn--sm" type="button" data-action="delete-tag" data-id="' +
          tag.id +
          '">Supprimer</button>' +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  async function loadTags() {
    if (!isAdmin()) {
      return;
    }
    const payload = await api("/admin/tags?page=1&limit=100", {
      method: "GET",
    });
    knownTags = payload.data || [];
    renderTags(knownTags);
    renderTaxonomySelectors();
  }

  function renderSections(sections) {
    sectionsBody.innerHTML = (sections || [])
      .map(function (section) {
        return (
          "<tr>" +
          "<td>" +
          section.id +
          "</td>" +
          '<td><input class="form__input" data-section-name="' +
          section.id +
          '" value="' +
          escapeHtml(section.name || "") +
          '" /></td>' +
          '<td><input class="form__input" data-section-slug="' +
          section.id +
          '" value="' +
          escapeHtml(section.slug || "") +
          '" /></td>' +
          '<td><input class="form__input" type="number" min="1" data-section-parent="' +
          section.id +
          '" value="' +
          (section.parent_id || "") +
          '" /></td>' +
          '<td class="backoffice-page__row-actions">' +
          '<button class="btn btn--secondary btn--sm" type="button" data-action="save-section" data-id="' +
          section.id +
          '">Sauver</button> ' +
          '<button class="btn btn--danger btn--sm" type="button" data-action="delete-section" data-id="' +
          section.id +
          '">Supprimer</button>' +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  async function loadSections() {
    if (!isAdmin()) {
      return;
    }
    const payload = await api("/admin/sections?page=1&limit=100", {
      method: "GET",
    });
    knownSections = payload.data || [];
    renderSections(knownSections);
    renderTaxonomySelectors();
  }

  function renderLogs(logs) {
    logsBody.innerHTML = (logs || [])
      .map(function (log) {
        return (
          "<tr>" +
          "<td>" +
          log.id +
          "</td>" +
          "<td>" +
          escapeHtml(log.action || "-") +
          "</td>" +
          "<td>" +
          escapeHtml(log.entity_type || "-") +
          " #" +
          escapeHtml(log.entity_id || "-") +
          "</td>" +
          "<td>" +
          escapeHtml(log.user_id || "-") +
          "</td>" +
          "<td>" +
          escapeHtml(log.created_at || "-") +
          "</td>" +
          "</tr>"
        );
      })
      .join("");
  }

  async function loadLogs() {
    if (!isAdmin()) {
      return;
    }
    const payload = await api("/admin/audit-logs?page=1&limit=50", {
      method: "GET",
    });
    renderLogs(payload.data || []);
  }

  async function refreshSession() {
    const token = getToken();

    if (!token) {
      currentUser = null;
      updateNavigation();
      renderDashboard();
      currentUserBadge.textContent = "Non connecte";
      setAlert(authStatus, "info", "Non connecte.");
      return;
    }

    try {
      const payload = await api("/me", { method: "GET" });
      currentUser = payload.user || payload || null;

      updateNavigation();
      renderDashboard();
      documentEditor.hidden = !canUseDocumentEditor();
      setAlert(authStatus, "success", "Connecte.");

      if (
        !window.location.hash ||
        window.location.hash === "#/login" ||
        window.location.hash === "#/register"
      ) {
        setRoute("#/dashboard");
      }

      await loadPublicTaxonomies();
    } catch (error) {
      clearToken();
      currentUser = null;
      updateNavigation();
      renderDashboard();
      currentUserBadge.textContent = "Non connecte";
      setAlert(authStatus, "danger", "Session invalide: " + error.message);
      setRoute("#/login");
    }
  }

  loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    if (!email || !password) {
      setAlert(authStatus, "warning", "Email et mot de passe obligatoires.");
      return;
    }

    try {
      setAlert(authStatus, "info", "Connexion en cours...");
      const payload = await api("/auth/login", {
        method: "POST",
        body: JSON.stringify({ email: email, password: password }),
      });

      setToken(payload.access_token);
      await refreshSession();
      setRoute("#/dashboard");
    } catch (error) {
      setAlert(authStatus, "danger", "Connexion echouee: " + error.message);
    }
  });

  registerForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const username = document.getElementById("register-username").value.trim();
    const email = document.getElementById("register-email").value.trim();
    const password = document.getElementById("register-password").value;

    if (!username || !email || !password) {
      setAlert(registerStatus, "warning", "Tous les champs sont obligatoires.");
      return;
    }

    try {
      setAlert(registerStatus, "info", "Inscription en cours...");
      await api("/auth/register", {
        method: "POST",
        body: JSON.stringify({
          username: username,
          email: email,
          password: password,
        }),
      });

      registerForm.reset();
      setAlert(
        registerStatus,
        "success",
        "Inscription reussie, connecte-toi maintenant.",
      );
      setRoute("#/login");
    } catch (error) {
      setAlert(
        registerStatus,
        "danger",
        "Inscription echouee: " + error.message,
      );
    }
  });

  refreshDocumentsBtn.addEventListener("click", function () {
    loadDocuments();
  });

  documentFilterForm.addEventListener("submit", function (event) {
    event.preventDefault();
    loadDocuments();
  });

  documentsBody.addEventListener("click", async function (event) {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const action = button.getAttribute("data-action");
    const id = Number(button.getAttribute("data-id"));

    if (!id) {
      return;
    }

    if (action === "view") {
      loadDocumentDetail(id);
      return;
    }

    if (action === "edit") {
      openDocumentForEdit(id);
      return;
    }

    if (action === "delete") {
      if (!isAdmin()) {
        return;
      }
      if (!window.confirm("Supprimer le document #" + id + " ?")) {
        return;
      }

      try {
        await api("/documents/" + id, { method: "DELETE" });
        setAlert(documentsStatus, "success", "Document supprime.");
        loadDocuments();
      } catch (error) {
        setAlert(
          documentsStatus,
          "danger",
          "Suppression impossible: " + error.message,
        );
      }
    }
  });

  documentEditorReset.addEventListener("click", function () {
    resetDocumentForm();
  });

  documentForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const payload = {
      title: documentTitleInput.value.trim(),
      slug: documentSlugInput.value.trim() || undefined,
      content: documentContentInput.value,
      status: documentStatusInput.value,
      section_id: documentSectionInput.value
        ? Number(documentSectionInput.value)
        : null,
      tags: parseTags(documentTagsInput.value),
      meta_title: documentMetaTitleInput.value.trim() || null,
      meta_description: documentMetaDescriptionInput.value.trim() || null,
    };

    if (!payload.title) {
      setAlert(documentFormStatus, "warning", "Le titre est obligatoire.");
      return;
    }

    const documentId = documentIdInput.value
      ? Number(documentIdInput.value)
      : null;

    if (!documentId && !canCreateDocuments()) {
      setAlert(
        documentFormStatus,
        "warning",
        "Creation reservee aux editeurs/admins.",
      );
      return;
    }

    if (!documentId && !canUseDocumentEditor()) {
      setAlert(
        documentFormStatus,
        "warning",
        "Tu n'as pas les droits pour modifier un document.",
      );
      return;
    }

    try {
      setAlert(documentFormStatus, "info", "Enregistrement en cours...");

      await saveDocument(payload, documentId);

      setAlert(documentFormStatus, "success", "Document enregistre.");
      resetDocumentForm();
      loadDocuments();
    } catch (error) {
      setAlert(
        documentFormStatus,
        "danger",
        "Enregistrement impossible: " + error.message,
      );
    }
  });

  loadUsersBtn.addEventListener("click", async function () {
    try {
      setAlert(adminStatus, "info", "Chargement des utilisateurs...");
      await loadUsers();
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Impossible de charger: " + error.message,
      );
    }
  });

  usersBody.addEventListener("click", async function (event) {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const action = button.getAttribute("data-action");
    const userId = Number(button.getAttribute("data-id"));

    if (action === "save-user") {
      const roleInput = usersBody.querySelector(
        '[data-user-role="' + userId + '"]',
      );
      const activeInput = usersBody.querySelector(
        '[data-user-active="' + userId + '"]',
      );

      try {
        await api("/admin/users/" + userId, {
          method: "PATCH",
          body: JSON.stringify({
            role: roleInput ? roleInput.value : "reader",
            is_active: !!(activeInput && activeInput.checked),
          }),
        });
        setAlert(
          adminStatus,
          "success",
          "Utilisateur #" + userId + " mis a jour.",
        );
        await loadUsers();
        await loadAdminStats();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Mise a jour utilisateur impossible: " + error.message,
        );
      }
      return;
    }

    if (action === "delete-user") {
      if (!window.confirm("Supprimer l'utilisateur #" + userId + " ?")) {
        return;
      }

      try {
        await api("/admin/users/" + userId, {
          method: "DELETE",
        });
        setAlert(
          adminStatus,
          "success",
          "Utilisateur #" + userId + " supprime.",
        );
        await loadUsers();
        await loadAdminStats();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Suppression utilisateur impossible: " + error.message,
        );
      }
    }
  });

  loadTagsBtn.addEventListener("click", async function () {
    try {
      setAlert(adminStatus, "info", "Chargement des tags...");
      await loadTags();
      setAlert(adminStatus, "success", "Tags charges.");
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Impossible de charger les tags: " + error.message,
      );
    }
  });

  tagCreateForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const name = document.getElementById("tag-create-name").value.trim();
    const slug = document.getElementById("tag-create-slug").value.trim();

    if (!name) {
      setAlert(adminStatus, "warning", "Le nom du tag est obligatoire.");
      return;
    }

    try {
      await api("/admin/tags", {
        method: "POST",
        body: JSON.stringify({ name: name, slug: slug || undefined }),
      });
      tagCreateForm.reset();
      setAlert(adminStatus, "success", "Tag cree.");
      loadTags();
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Creation tag impossible: " + error.message,
      );
    }
  });

  tagsBody.addEventListener("click", async function (event) {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const action = button.getAttribute("data-action");
    const id = Number(button.getAttribute("data-id"));

    if (action === "save-tag") {
      const nameInput = tagsBody.querySelector('[data-tag-name="' + id + '"]');
      const slugInput = tagsBody.querySelector('[data-tag-slug="' + id + '"]');
      try {
        await api("/admin/tags/" + id, {
          method: "PATCH",
          body: JSON.stringify({
            name: nameInput ? nameInput.value.trim() : "",
            slug: slugInput ? slugInput.value.trim() : "",
          }),
        });
        setAlert(adminStatus, "success", "Tag #" + id + " mis a jour.");
        loadTags();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Mise a jour tag impossible: " + error.message,
        );
      }
      return;
    }

    if (action === "delete-tag") {
      if (!window.confirm("Supprimer le tag #" + id + " ?")) {
        return;
      }
      try {
        await api("/admin/tags/" + id, { method: "DELETE" });
        setAlert(adminStatus, "success", "Tag supprime.");
        loadTags();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Suppression tag impossible: " + error.message,
        );
      }
    }
  });

  loadSectionsBtn.addEventListener("click", async function () {
    try {
      setAlert(adminStatus, "info", "Chargement des sections...");
      await loadSections();
      setAlert(adminStatus, "success", "Sections chargees.");
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Impossible de charger les sections: " + error.message,
      );
    }
  });

  sectionCreateForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const name = document.getElementById("section-create-name").value.trim();
    const slug = document.getElementById("section-create-slug").value.trim();
    const parent = document
      .getElementById("section-create-parent")
      .value.trim();

    if (!name) {
      setAlert(adminStatus, "warning", "Le nom de section est obligatoire.");
      return;
    }

    try {
      await api("/admin/sections", {
        method: "POST",
        body: JSON.stringify({
          name: name,
          slug: slug || undefined,
          parent_id: parent ? Number(parent) : null,
        }),
      });
      sectionCreateForm.reset();
      setAlert(adminStatus, "success", "Section creee.");
      loadSections();
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Creation section impossible: " + error.message,
      );
    }
  });

  sectionsBody.addEventListener("click", async function (event) {
    const button = event.target.closest("button[data-action]");
    if (!button) {
      return;
    }

    const action = button.getAttribute("data-action");
    const id = Number(button.getAttribute("data-id"));

    if (action === "save-section") {
      const nameInput = sectionsBody.querySelector(
        '[data-section-name="' + id + '"]',
      );
      const slugInput = sectionsBody.querySelector(
        '[data-section-slug="' + id + '"]',
      );
      const parentInput = sectionsBody.querySelector(
        '[data-section-parent="' + id + '"]',
      );
      try {
        await api("/admin/sections/" + id, {
          method: "PATCH",
          body: JSON.stringify({
            name: nameInput ? nameInput.value.trim() : "",
            slug: slugInput ? slugInput.value.trim() : "",
            parent_id:
              parentInput && parentInput.value
                ? Number(parentInput.value)
                : null,
          }),
        });
        setAlert(adminStatus, "success", "Section #" + id + " mise a jour.");
        loadSections();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Mise a jour section impossible: " + error.message,
        );
      }
      return;
    }

    if (action === "delete-section") {
      if (!window.confirm("Supprimer la section #" + id + " ?")) {
        return;
      }
      try {
        await api("/admin/sections/" + id, { method: "DELETE" });
        setAlert(adminStatus, "success", "Section supprimee.");
        loadSections();
      } catch (error) {
        setAlert(
          adminStatus,
          "danger",
          "Suppression section impossible: " + error.message,
        );
      }
    }
  });

  loadLogsBtn.addEventListener("click", async function () {
    try {
      setAlert(adminStatus, "info", "Chargement des logs...");
      await loadLogs();
      setAlert(adminStatus, "success", "Logs charges.");
    } catch (error) {
      setAlert(
        adminStatus,
        "danger",
        "Impossible de charger les logs: " + error.message,
      );
    }
  });

  dashboardQuickCreateForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    if (!canCreateDocuments()) {
      setAlert(
        dashboardQuickCreateStatus,
        "warning",
        "Creation reservee aux editeurs/admins.",
      );
      return;
    }

    const payload = {
      title: quickDocumentTitle.value.trim(),
      slug: quickDocumentSlug.value.trim() || undefined,
      content: quickDocumentContent.value,
      status: quickDocumentStatus.value,
      tags: parseTags(quickDocumentTags.value),
    };

    if (!payload.title) {
      setAlert(
        dashboardQuickCreateStatus,
        "warning",
        "Le titre est obligatoire.",
      );
      return;
    }

    try {
      setAlert(dashboardQuickCreateStatus, "info", "Creation en cours...");
      await saveDocument(payload, null);
      dashboardQuickCreateForm.reset();
      quickDocumentStatus.value = "draft";
      setAlert(
        dashboardQuickCreateStatus,
        "success",
        "Document cree. Tu peux le retrouver dans l'onglet Documents.",
      );
      loadDocuments();
    } catch (error) {
      setAlert(
        dashboardQuickCreateStatus,
        "danger",
        "Creation impossible: " + error.message,
      );
    }
  });

  dashboardOpenDocuments.addEventListener("click", function () {
    setRoute("#/documents");
  });

  logoutBtn.addEventListener("click", async function () {
    try {
      await api("/auth/logout", { method: "POST" });
    } catch (error) {
      // Local cleanup still happens if token is already invalid.
    }

    clearToken();
    currentUser = null;
    updateNavigation();
    renderDashboard();
    resetDocumentForm();

    usersBody.innerHTML = "";
    tagsBody.innerHTML = "";
    sectionsBody.innerHTML = "";
    logsBody.innerHTML = "";
    documentsBody.innerHTML = "";
    currentUserBadge.textContent = "Non connecte";

    setAlert(authStatus, "info", "Deconnecte.");
    setAlert(
      registerStatus,
      "info",
      "Remplis le formulaire pour creer ton compte.",
    );
    setAlert(documentsStatus, "info", "Charge les documents.");
    setAlert(adminStatus, "info", "Zone admin.");
    if (dashboardQuickCreateStatus) {
      setAlert(
        dashboardQuickCreateStatus,
        "info",
        "Formulaire de creation rapide.",
      );
    }

    setRoute("#/login");
  });

  window.addEventListener("hashchange", renderRoute);

  updateNavigation();
  renderRoute();
  resetDocumentForm();
  refreshSession();
})();
