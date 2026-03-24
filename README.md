# CMS-doc-headless-php

## Demarrage rapide

```bash
docker compose up -d --build
```

Au premier demarrage (base vide), PostgreSQL execute automatiquement:

- les migrations `sql/migrations/0*.sql`
- le seeder `sql/seeders/seed.sql`

Comptes de test:

- admin@cms-wiki.fr / password123
- editeur@cms-wiki.fr / password123
- auteur@cms-wiki.fr / password123
- lecteur@cms-wiki.fr / password123

## Reinitialiser la base (si necessaire)

```bash
docker compose down -v
docker compose up -d --build
```