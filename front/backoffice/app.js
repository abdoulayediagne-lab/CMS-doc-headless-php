(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";
  const TOKEN_KEY = "cms_access_token";

  const loginView = document.getElementById("view-login");
  const registerView = document.getElementById("view-register");
  const dashboardView = document.getElementById("view-dashboard");
  const usersView = document.getElementById("view-users");

  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");
  const authStatus = document.getElementById("auth-status");
  const registerStatus = document.getElementById("register-status");
  const usersStatus = document.getElementById("users-status");
  const usersBody = document.getElementById("users-body");
  const currentUserBadge = document.getElementById("current-user");

  const loadUsersBtn = document.getElementById("load-users-btn");
  const logoutBtn = document.getElementById("logout-btn");

  let currentUser = null;

  function setAlert(element, variant, message) {
    element.className = "alert alert--" + variant;
    element.textContent = message;
  }

  function setRoute(route) {
    const normalized = route.startsWith("#/") ? route : "#/" + route.replace(/^\//, "");
    if (window.location.hash !== normalized) {
      window.location.hash = normalized;
      return;
    }
    renderRoute();
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

  async function api(path, options) {
    const token = getToken();
    const headers = Object.assign({ "Content-Type": "application/json" }, (options && options.headers) || {});
    if (token) {
      headers.Authorization = "Bearer " + token;
    }

    const response = await fetch(API_BASE + path, Object.assign({}, options, { headers }));
    const body = await response.text();
    let payload = {};
    try {
      payload = body ? JSON.parse(body) : {};
    } catch (e) {
      payload = { raw: body };
    }

    if (!response.ok) {
      const message = payload.error || ("HTTP " + response.status);
      throw new Error(message);
    }

    return payload;
  }

  function renderRoute() {
    const hash = window.location.hash || "#/login";

    loginView.hidden = true;
    registerView.hidden = true;
    dashboardView.hidden = true;
    usersView.hidden = true;

    if (hash === "#/register") {
      registerView.hidden = false;
      return;
    }

    if (hash === "#/dashboard") {
      dashboardView.hidden = false;
      usersView.hidden = false;
      return;
    }

    loginView.hidden = false;
  }

  function renderUsers(users) {
    if (!Array.isArray(users) || users.length === 0) {
      usersBody.innerHTML = "";
      setAlert(usersStatus, "warning", "Aucun utilisateur.");
      return;
    }

    usersBody.innerHTML = users
      .map(function (user) {
        return (
          "<tr>" +
          "<td>" + user.id + "</td>" +
          "<td>" + user.username + "</td>" +
          "<td>" + user.email + "</td>" +
          "<td>" + user.role + "</td>" +
          "<td>" + (user.is_active ? "oui" : "non") + "</td>" +
          "</tr>"
        );
      })
      .join("");

    setAlert(usersStatus, "success", users.length + " utilisateur(s) charge(s).");
  }

  async function refreshSession() {
    const token = getToken();
    if (!token) {
      currentUser = null;
      currentUserBadge.textContent = "Non connecte";
      setAlert(authStatus, "info", "Non connecte.");
      return;
    }

    try {
      const payload = await api("/me", { method: "GET" });
      const user = payload.user || payload || {};
      currentUser = user;
      currentUserBadge.textContent = (user.username || user.email || "Utilisateur") + " - " + (user.role || "?");
      setAlert(authStatus, "success", "Connecte: " + (user.email || "inconnu") + " (" + (user.role || "?") + ")");

      if (window.location.hash !== "#/dashboard") {
        setRoute("#/dashboard");
      }
    } catch (error) {
      clearToken();
      currentUser = null;
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
        body: JSON.stringify({ username: username, email: email, password: password }),
      });

      registerForm.reset();
      setAlert(registerStatus, "success", "Inscription reussie, connecte-toi maintenant.");
      setRoute("#/login");
    } catch (error) {
      setAlert(registerStatus, "danger", "Inscription echouee: " + error.message);
    }
  });

  loadUsersBtn.addEventListener("click", async function () {
    try {
      setAlert(usersStatus, "info", "Chargement des utilisateurs...");
      const payload = await api("/admin/users?page=1&limit=20", { method: "GET" });
      renderUsers(payload.data || []);
    } catch (error) {
      usersBody.innerHTML = "";
      setAlert(usersStatus, "danger", "Impossible de charger: " + error.message);
    }
  });

  logoutBtn.addEventListener("click", async function () {
    try {
      await api("/auth/logout", { method: "POST" });
    } catch (error) {
      // Logout API can fail if token expired, we still clear local state.
    }

    clearToken();
    currentUser = null;
    currentUserBadge.textContent = "Non connecte";
    usersBody.innerHTML = "";
    setAlert(authStatus, "info", "Deconnecte.");
    setAlert(registerStatus, "info", "Remplis le formulaire pour creer ton compte.");
    setAlert(usersStatus, "info", "Connecte-toi en admin pour lister les utilisateurs.");
    setRoute("#/login");
  });

  window.addEventListener("hashchange", renderRoute);

  renderRoute();
  refreshSession();
})();
