-- Perluasan kategori Pasar Dapulik untuk instalasi yang sudah menjalankan
-- 016_marketplace.sql. Aman dijalankan ulang.
SET NAMES utf8mb4;

INSERT INTO marketplace_categories (slug, name, sort_order, is_active) VALUES
  ('makanan-minuman', 'Makanan & Minuman', 10, 1),
  ('hasil-tani', 'Hasil Tani', 20, 1),
  ('peternakan-perikanan', 'Peternakan & Perikanan', 25, 1),
  ('kerajinan', 'Kerajinan', 30, 1),
  ('pakaian-aksesori', 'Pakaian & Aksesori', 35, 1),
  ('jasa', 'Jasa', 40, 1),
  ('kebutuhan-rumah-tangga', 'Kebutuhan Rumah Tangga', 45, 1),
  ('elektronik-aksesori', 'Elektronik & Aksesori', 50, 1),
  ('peralatan-bahan-bangunan', 'Peralatan & Bahan Bangunan', 55, 1),
  ('pendidikan-buku', 'Pendidikan & Buku', 60, 1),
  ('tanaman-bibit', 'Tanaman & Bibit', 65, 1),
  ('otomotif-suku-cadang', 'Otomotif & Suku Cadang', 70, 1),
  ('perawatan-pribadi', 'Perawatan Pribadi', 75, 1),
  ('lainnya', 'Lainnya', 90, 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  sort_order = VALUES(sort_order),
  is_active = VALUES(is_active);
