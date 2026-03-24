# CMS-doc-headless-php

## Demarrage rapide

```bash
docker compose up -d --build
```

Au premier demarrage (base vide), PostgreSQL execute automatiquement:

- les migrations `sql/migrations/0*.sql`
- le seeder `sql/seeders/seed.sql`

Comptes de test:

- **admin@cms.fr** / password123 (Administrateur - Accès complet)
- **editeur@cms.fr** / password123 (Éditeur - Peut publier et modifier)
- **auteur@cms.fr** / password123 (Auteur - Peut créer des brouillons)
- **lecteur@cms.fr** / password123 (Lecteur - Accès lecture seule)

## Reinitialiser la base (si necessaire)

```bash
docker compose down -v
docker compose up -d --build
```