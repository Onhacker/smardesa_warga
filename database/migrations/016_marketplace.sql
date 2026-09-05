-- Pasar Digital. Produk dimiliki oleh kampung/desa asalnya, tetapi produk
-- berstatus published dapat ditemukan warga dari seluruh wilayah.
-- Jalankan setelah schema.sql pada database PWA warga.
-- Migrasi ini aman dijalankan ulang.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS marketplace_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_marketplace_categories_active (is_active, sort_order, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO marketplace_categories (slug, name, sort_order, is_active) VALUES
  ('makanan-minuman', 'Makanan & Minuman', 10, 1),
  ('hasil-tani', 'Hasil Tani', 20, 1),
  ('kerajinan', 'Kerajinan', 30, 1),
  ('jasa', 'Jasa', 40, 1),
  ('lainnya', 'Lainnya', 90, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS marketplace_stores (
  id CHAR(36) NOT NULL PRIMARY KEY,
  village_id CHAR(36) NOT NULL,
  owner_user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  description VARCHAR(1000) NULL,
  whatsapp VARCHAR(30) NULL,
  phone VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  logo_path VARCHAR(600) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_marketplace_store_owner (village_id, owner_user_id),
  KEY idx_marketplace_store_village (village_id, is_active, updated_at),
  CONSTRAINT fk_marketplace_store_village FOREIGN KEY (village_id) REFERENCES village_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_store_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_products (
  id CHAR(36) NOT NULL PRIMARY KEY,
  village_id CHAR(36) NOT NULL,
  store_id CHAR(36) NOT NULL,
  seller_user_id BIGINT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT NULL,
  price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  stock INT UNSIGNED NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_marketplace_product_listing (village_id, status, updated_at),
  KEY idx_marketplace_product_public_listing (status, updated_at),
  KEY idx_marketplace_product_category (village_id, category_id, status),
  KEY idx_marketplace_product_store (store_id, status, updated_at),
  KEY idx_marketplace_product_seller (seller_user_id, status, updated_at),
  CONSTRAINT fk_marketplace_product_village FOREIGN KEY (village_id) REFERENCES village_tenants(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_product_store FOREIGN KEY (store_id) REFERENCES marketplace_stores(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_product_seller FOREIGN KEY (seller_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_marketplace_product_category FOREIGN KEY (category_id) REFERENCES marketplace_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_product_images (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id CHAR(36) NOT NULL,
  original_name VARCHAR(180) NOT NULL,
  stored_name VARCHAR(220) NOT NULL,
  storage_path VARCHAR(600) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  is_cover TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_marketplace_image_product (product_id, sort_order, id),
  CONSTRAINT fk_marketplace_image_product FOREIGN KEY (product_id) REFERENCES marketplace_products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
