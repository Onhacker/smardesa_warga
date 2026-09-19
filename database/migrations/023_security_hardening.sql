-- Security hardening for central session revocation and registration abuse
-- controls. Target: the Hostinger MariaDB runtime used by this application.
-- Safe to run repeatedly; no existing account or session data is deleted.
SET NAMES utf8mb4;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS session_version BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER password_hash;

CREATE TABLE IF NOT EXISTS registration_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identity_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_registration_identity (identity_hash, attempted_at),
  KEY idx_registration_ip (ip_address, attempted_at),
  KEY idx_registration_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Converge indexes as well when this table came from an earlier draft of the
-- migration. ADD INDEX IF NOT EXISTS is supported by the target MariaDB.
ALTER TABLE registration_attempts
  ADD INDEX IF NOT EXISTS idx_registration_identity (identity_hash, attempted_at),
  ADD INDEX IF NOT EXISTS idx_registration_ip (ip_address, attempted_at),
  ADD INDEX IF NOT EXISTS idx_registration_time (attempted_at);
