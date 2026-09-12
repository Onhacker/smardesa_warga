-- One optional, tenant-scoped attachment for each village announcement.
-- Files are stored outside the public document root; this table only keeps
-- metadata needed by the authenticated streaming endpoint.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS warga_announcement_attachments (
  id CHAR(36) NOT NULL PRIMARY KEY,
  announcement_id CHAR(36) NOT NULL,
  village_id CHAR(36) NOT NULL,
  original_name VARCHAR(180) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(1024) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_announcement_attachment (announcement_id),
  KEY idx_announcement_attachment_village (announcement_id, village_id),
  CONSTRAINT fk_announcement_attachment_announcement
    FOREIGN KEY (announcement_id) REFERENCES warga_announcements(id) ON DELETE CASCADE,
  CONSTRAINT fk_announcement_attachment_village
    FOREIGN KEY (village_id) REFERENCES village_tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
