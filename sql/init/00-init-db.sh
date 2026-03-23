#!/bin/sh
set -eu

echo "==> Running SQL migrations"
for migration in /docker-entrypoint-initdb.d/migrations/0*.sql; do
  echo "-> ${migration}"
  psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f "$migration"
done

echo "==> Seeding base accounts and sample data"
psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB" -f /docker-entrypoint-initdb.d/seed.sql

echo "==> Database initialization complete"
