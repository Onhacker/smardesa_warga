-- Passkey/WebAuthn, local PIN, and long-lived trusted-device sessions.
-- Safe to run repeatedly on the MariaDB runtime used by the PWA.
SET NAMES utf8mb4;

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS login_pin_hash VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS login_pin_failed_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER login_pin_hash,
  ADD COLUMN IF NOT EXISTS login_pin_locked_until DATETIME NULL AFTER login_pin_failed_count,
  ADD COLUMN IF NOT EXISTS session_version BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER login_pin_locked_until;

CREATE TABLE IF NOT EXISTS warga_passkey_credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  credential_id VARCHAR(1024) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  public_key_pem TEXT NOT NULL,
  signature_counter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  aaguid CHAR(32) NULL,
  label VARCHAR(120) NOT NULL DEFAULT '',
  transports VARCHAR(120) NULL,
  last_used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_warga_passkey_credential (credential_id),
  KEY idx_warga_passkey_user (user_id, revoked_at),
  CONSTRAINT fk_warga_passkey_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS warga_login_tokens (
  selector CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  session_version BIGINT UNSIGNED NOT NULL DEFAULT 1,
  token_hash CHAR(64) NOT NULL,
  auth_method VARCHAR(20) NOT NULL,
  expires_at DATETIME NOT NULL,
  last_used_at DATETIME NULL,
  revoked_at DATETIME NULL,
  user_agent VARCHAR(255) NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_warga_login_token_user (user_id, revoked_at, expires_at),
  KEY idx_warga_login_token_expiry (expires_at),
  CONSTRAINT fk_warga_login_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE warga_login_tokens
  ADD COLUMN IF NOT EXISTS session_version BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER user_id;

-- Converge the credential collation when this migration was previously tested
-- from an earlier draft. WebAuthn credential IDs are case-sensitive base64url.
ALTER TABLE warga_passkey_credentials
  MODIFY credential_id VARCHAR(1024) CHARACTER SET ascii COLLATE ascii_bin NOT NULL;

ALTER TABLE warga_login_tokens
  MODIFY selector CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL;
