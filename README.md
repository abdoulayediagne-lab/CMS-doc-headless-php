# CMS-doc-headless-php

## Demarrage rapide

```bash
docker compose up -d --build
```

Au demarrage du service PHP, `composer install` est execute automatiquement pour generer `app/vendor/autoload.php`.

Au premier demarrage (base vide), PostgreSQL execute automatiquement:

- les migrations `sql/migrations/0*.sql`
- le seeder `sql/seeders/seed.sql`

Comptes de test:

- admin@cms-wiki.fr / password123
- editeur@cms-wiki.fr / password123
- auteur@cms-wiki.fr / password123
- lecteur@cms-wiki.fr / password123

## Reinitialiser la base (si necessaire)

Si tu vois des erreurs SQL du type `relation \"users\" does not exist` ou `relation \"documents\" does not exist`, reinitialise le volume PostgreSQL:

```bash
docker compose down -v
docker compose up -d --build
```
