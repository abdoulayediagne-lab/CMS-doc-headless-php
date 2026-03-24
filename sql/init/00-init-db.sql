\echo ==> Running SQL migrations
\i /docker-entrypoint-initdb.d/migrations/001_create_users.sql
\i /docker-entrypoint-initdb.d/migrations/002_create_sections.sql
\i /docker-entrypoint-initdb.d/migrations/003_create_documents.sql
\i /docker-entrypoint-initdb.d/migrations/004_create_document_versions.sql
\i /docker-entrypoint-initdb.d/migrations/005_create_tags.sql
\i /docker-entrypoint-initdb.d/migrations/006_create_media.sql
\i /docker-entrypoint-initdb.d/migrations/007_create_audit_log.sql
\i /docker-entrypoint-initdb.d/migrations/008_create_page_views.sql

\echo ==> Seeding base accounts and sample data
\i /docker-entrypoint-initdb.d/seeders/seed.sql

\echo ==> Database initialization complete
