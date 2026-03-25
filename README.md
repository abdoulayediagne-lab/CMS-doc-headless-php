# CMS-doc-headless-php

CMS headless developpé from scratch en PHP (framework maison), avec:

- API backend (PHP + Apache)
- base PostgreSQL 18
- front public statique
- backoffice statique
- compilation SCSS en continu

## Choix des images Docker (version + pourquoi)

| Service | Image | Pourquoi |
|---|---|---|
| API PHP | `php:8.4-apache` | Version recente de PHP, module Apache integre, simple à lancer |
| Base de données | `postgres:18` | Respect strict de la contrainte : PostgreSQL 18 uniquement. |
| Build SCSS | `node:22-alpine` | Image légère, suffisante pour installer et exécuter `sass --watch`. |
| Front public | `nginx:alpine` | Serveur web statique léger et fiable. |
| Backoffice | `nginx:alpine` | Même logique: service statique dédié, simple à maintenir. |

## Prérequis

- Docker Desktop (ou Docker Engine + Compose v2)
- Ports libres: `8080`, `5173`, `5174`, `5432`

## Démarrage rapide

```bash
docker compose up -d --build
```

Au démarrage du service PHP, `composer install` est exécuté automatiquement pour générer `app/vendor/autoload.php`.

Au premier démarrage (base vide), PostgreSQL exécute automatiquement:

- les migrations `sql/migrations/0*.sql`
- le seeder `sql/seeders/seed.sql`

## URLs utiles

- API backend: `http://localhost:8080`
- Front public: `http://localhost:5173`
- Backoffice: `http://localhost:5174`
- PostgreSQL: `localhost:5432`

## Comptes de test

- `admin@cms-wiki.fr` / `password123`
- `editeur@cms-wiki.fr` / `password123`
- `auteur@cms-wiki.fr` / `password123`
- `lecteur@cms-wiki.fr` / `password123`

## Commandes utiles

Lancer / rebuild:

```bash
docker compose up -d --build
```

Voir les logs:

```bash
docker compose logs -f
```

Arreter:

```bash
docker compose down
```

Réinitialiser totalement la base (volume inclus):

```bash
docker compose down -v
docker compose up -d --build
```

## Base de données et contrainte pedagogique

Le projet est configuré pour PostgreSQL 18 (`postgres:18`) et ne doit pas être valide avec un autre SGBD.

Si tu vois des erreurs SQL du type `relation "users" does not exist` ou `relation "documents" does not exist`, applique la réinitialisation complète ci-dessus.

## Notes de production

Pour la soutenance, le minimum attendu est:

- code propre sur GitHub
- application démarrable de facon reproductible
- idéalement une URL de demonstration publique
