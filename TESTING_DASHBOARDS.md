# 📊 Guide de Test des Dashboards

## Vue d'ensemble

Le système dispose de 4 dashboards différents, un pour chaque rôle d'utilisateur :
- **Admin** : Vue d'ensemble du système
- **Éditeur** : Gestion des documents à publier
- **Auteur** : Ses propres documents
- **Lecteur** : Accès à la documentation publique

## Méthode 1️⃣: Via Terminal avec CURL

### Étape 1: Login et récupérer le token

```bash
# Pour l'administrateur
TOKEN_ADMIN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@cms.fr","password":"password123"}' | jq -r '.access_token')

echo "Token: $TOKEN_ADMIN"
```

### Étape 2: Appeler le dashboard

```bash
curl -X GET http://localhost:8080/dashboard \
  -H "Authorization: Bearer $TOKEN_ADMIN" \
  -H "Content-Type: application/json" | jq '.'
```

---

## Méthode 2️⃣: Script automatisé

### Utiliser le script fourni

```bash
# Rendre le script exécutable
chmod +x test-dashboards.sh

# Tester TOUS les dashboards
./test-dashboards.sh all

# Tester un dashboard spécifique
./test-dashboards.sh admin
./test-dashboards.sh editor
./test-dashboards.sh author
./test-dashboards.sh reader

# Tester l'accès non authentifié
./test-dashboards.sh unauthorized
```

---

## Méthode 3️⃣: Tester manuellement chaque rôle

### Dashboard Admin

```bash
# Login
ADMIN_TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@cms.fr","password":"password123"}' | jq -r '.access_token')

# Dashboard
curl -s -X GET http://localhost:8080/dashboard \
  -H "Authorization: Bearer $ADMIN_TOKEN" | jq '.'
```

**Réponse attendue:**
```json
{
  "user": {
    "id": 1,
    "username": "admin",
    "email": "admin@cms.fr",
    "role": "admin"
  },
  "stats": {
    "totalUsers": 4,
    "totalDocuments": 4,
    "totalSections": 6,
    "totalTags": 5,
    "publishedDocuments": 2,
    "draftDocuments": 1,
    "reviewDocuments": 1
  },
  "actions": [
    {
      "label": "Gérer les utilisateurs",
      "href": "/admin/users",
      "icon": "👥"
    },
    ...
  ]
}
```

---

### Dashboard Éditeur

```bash
# Login
EDITOR_TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"editeur@cms.fr","password":"password123"}' | jq -r '.access_token')

# Dashboard
curl -s -X GET http://localhost:8080/dashboard \
  -H "Authorization: Bearer $EDITOR_TOKEN" | jq '.'
```

**Réponse attendue:**
```json
{
  "user": {
    "id": 2,
    "username": "editeur1",
    "email": "editeur@cms.fr",
    "role": "editor"
  },
  "stats": {
    "documentsTotal": 4,
    "documentsInReview": 1,
    "documentsPublished": 2
  },
  "actions": [
    {
      "label": "Voir les documents à réviser",
      "href": "/documents?status=review",
      "icon": "✏️"
    },
    ...
  ]
}
```

---

### Dashboard Auteur

```bash
# Login
AUTHOR_TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"auteur@cms.fr","password":"password123"}' | jq -r '.access_token')

# Dashboard
curl -s -X GET http://localhost:8080/dashboard \
  -H "Authorization: Bearer $AUTHOR_TOKEN" | jq '.'
```

**Réponse attendue:**
```json
{
  "user": {
    "id": 3,
    "username": "auteur1",
    "email": "auteur@cms.fr",
    "role": "author"
  },
  "stats": {
    "myDocuments": 4,
    "myDrafts": 1,
    "myPublished": 2,
    "myInReview": 1
  },
  "actions": [
    {
      "label": "Voir mes brouillons",
      "href": "/documents?status=draft",
      "icon": "📝"
    },
    ...
  ]
}
```

---

### Dashboard Lecteur

```bash
# Login
READER_TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"lecteur@cms.fr","password":"password123"}' | jq -r '.access_token')

# Dashboard
curl -s -X GET http://localhost:8080/dashboard \
  -H "Authorization: Bearer $READER_TOKEN" | jq '.'
```

**Réponse attendue:**
```json
{
  "user": {
    "id": 4,
    "username": "lecteur1",
    "email": "lecteur@cms.fr",
    "role": "reader"
  },
  "stats": {
    "publicDocuments": 2,
    "favoriteDocuments": 0
  },
  "actions": [
    {
      "label": "Parcourir la documentation",
      "href": "/public/documents",
      "icon": "🔍"
    },
    ...
  ]
}
```

---

## Méthode 4️⃣: Postman (Interface Graphique)

### Étape 1: Récupérer le token

1. Ouvre Postman
2. Crée une requête **POST**: `http://localhost:8080/auth/login`
3. Onglet **Body** → **raw** → **JSON**:
   ```json
   {
     "email": "admin@cms.fr",
     "password": "password123"
   }
   ```
4. Click **SEND**
5. Copie la valeur de `access_token` dans la réponse

### Étape 2: Utiliser le token

1. Crée une nouvelle requête **GET**: `http://localhost:8080/dashboard`
2. Onglet **Headers**, ajoute:
   - **Key**: `Authorization`
   - **Value**: `Bearer [COLLE_TON_TOKEN_ICI]`
3. Click **SEND**
4. Vois la réponse JSON du dashboard

---

## Tests de Validation

### ✅ Ce qui doit fonctionner

- [x] Login avec email/password pour chaque rôle
- [x] Récupération du token JWT
- [x] Appel du dashboard avec authentification
- [x] Réponse JSON avec `stats`, `user`, `actions`
- [x] Nombres correct de documents/sections/tags

### ❌ Ce qui doit échouer

```bash
# Sans token
curl http://localhost:8080/dashboard
# → Réponse: {"error":"missing bearer token"}

# Avec token invalide
curl -H "Authorization: Bearer invalid-token" \
  http://localhost:8080/dashboard
# → Réponse: {"error":"invalid token"}
```

---

## Dépannage

### Token non obtenu

```bash
# Vérifier les logs du conteneur
docker logs php-framework-php

# Vérifier que l'API répond
curl http://localhost:8080/

# Vérifier les comptes de test
docker exec php-framework-postgres psql -U postgres -d cms_db \
  -c "SELECT id, email, role FROM users;"
```

### Erreur 500 sur le dashboard

```bash
# Vérifier les logs PHP
docker logs php-framework-php | tail -50

# Vérifier la route
curl http://localhost:8080/dashboard -H "Authorization: Bearer $TOKEN" -v
```

### Token rejeté

```bash
# Token peut-être expiré (60 minutes)
# Récupère un nouveau token avec login

# Vérifier le format
echo $TOKEN | jq -R 'split(".") | .[1] | @base64d | fromjson'
```

---

## Résumé des URLs

| Rôle | Login | Dashboard |
|------|-------|-----------|
| Admin | `POST /auth/login` | `GET /dashboard` |
| Éditeur | `POST /auth/login` | `GET /dashboard` |
| Auteur | `POST /auth/login` | `GET /dashboard` |
| Lecteur | `POST /auth/login` | `GET /dashboard` |

---

## Vérification rapide

```bash
#!/bin/bash
# Copie et colle ce script dans le terminal

echo "Testing all dashboards..."

for EMAIL in "admin@cms.fr" "editeur@cms.fr" "auteur@cms.fr" "lecteur@cms.fr"; do
  echo -e "\n=== Testing $EMAIL ==="
  TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"password123\"}" | jq -r '.access_token')
  
  echo "Token: ${TOKEN:0:20}..."
  
  curl -s -X GET http://localhost:8080/dashboard \
    -H "Authorization: Bearer $TOKEN" | jq '.stats'
done
```

---

## Notes

- Les dashboards retournent des **stats différentes** selon le rôle
- L'authentification est **obligatoire** (JWT Bearer token)
- Les données de test sont **pré-chargées** à la première exécution
- Les tokens expirent après **3600 secondes** (1 heure)
- Les réponses sont en **JSON** (utilise `jq` pour les formatter)
