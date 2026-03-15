-- Migration 008 : Création de la table page_views
-- Analytics & KPI (bonus) : tracking des vues par document

CREATE TABLE page_views (
    id SERIAL PRIMARY KEY,
    document_id INT NOT NULL REFERENCES documents(id) ON DELETE CASCADE,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    referer VARCHAR(500) DEFAULT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_views_document ON page_views(document_id);
CREATE INDEX idx_views_date ON page_views(viewed_at);
CREATE INDEX idx_views_stats ON page_views(document_id, viewed_at);