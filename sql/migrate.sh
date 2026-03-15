#!/bin/bash
# Script d'exécution des migrations
# Usage : ./migrate.sh [--seed]

set -e

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-cms_db}"
DB_USER="${DB_USER:-user}"

echo "=== Migration de la base de données CMS Wiki ==="
echo "Host: $DB_HOST | Port: $DB_PORT | DB: $DB_NAME | User: $DB_USER"
echo ""

# Exécuter les migrations dans l'ordre
for migration in sql/migrations/0*.sql; do
    echo ">> Exécution de $migration..."
    PGPASSWORD="${DB_PASSWORD:-password}" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$migration"
    echo "   OK"
done

echo ""
echo "=== Migrations terminées ==="

# Seeder optionnel
if [ "$1" = "--seed" ]; then
    echo ""
    echo ">> Exécution du seeder..."
    PGPASSWORD="${DB_PASSWORD:-password}" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "sql/seeders/seed.sql"
    echo "   OK"
    echo ""
    echo "=== Seeder terminé ==="
fi