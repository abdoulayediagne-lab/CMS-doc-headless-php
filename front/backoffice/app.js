(function () {
  const API_BASE = window.API_BASE_URL || "http://localhost:8080";
  const TOKEN_KEY = "cms_access_token";

  const loginForm = document.getElementById("login-form");
  const authStatus = document.getElementById("auth-status");
  const usersStatus = document.getElementById("users-status");
  const usersBody = document.getElementById("users-body");
  const loadUsersBtn = document.getElementById("load-users-btn");
  const logoutBtn = document.getElementById("logout-btn");

  function setAlert(element, variant, message) {
    element.className = "alert alert--" + variant;
    element.textContent = message;
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
      setAlert(authStatus, "info", "Non connecte.");
      return;
    }

    try {
      const payload = await api("/me", { method: "GET" });
      const user = payload.user || payload || {};
      setAlert(authStatus, "success", "Connecte: " + (user.email || "inconnu") + " (" + (user.role || "?") + ")");
    } catch (error) {
      clearToken();
      setAlert(authStatus, "danger", "Session invalide: " + error.message);
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
    } catch (error) {
      setAlert(authStatus, "danger", "Connexion echouee: " + error.message);
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
    usersBody.innerHTML = "";
    setAlert(authStatus, "info", "Deconnecte.");
    setAlert(usersStatus, "info", "Connecte-toi en admin pour lister les utilisateurs.");
  });

  refreshSession();
})();
