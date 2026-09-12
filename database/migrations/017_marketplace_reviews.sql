-- Rating dan komentar produk Pasar Dapulik.
-- Jalankan setelah migrations/016_marketplace.sql.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS marketplace_product_reviews (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id CHAR(36) NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  reviewer_name VARCHAR(160) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment VARCHAR(1000) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_marketplace_review_user (product_id, user_id),
  KEY idx_marketplace_review_product (product_id, status, created_at),
  CONSTRAINT fk_marketplace_review_product FOREIGN KEY (product_id) REFERENCES marketplace_products(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_review_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_marketplace_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
