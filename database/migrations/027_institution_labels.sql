-- Salinan schema bersama API/PWA untuk label bentuk wilayah per tenant.
SET NAMES utf8mb4;

ALTER TABLE app_public_branding
  ADD COLUMN IF NOT EXISTS bentuk_lembaga VARCHAR(100) NOT NULL DEFAULT 'Desa' AFTER tagline,
  ADD COLUMN IF NOT EXISTS bentuk_kecamatan VARCHAR(100) NOT NULL DEFAULT 'Kecamatan' AFTER bentuk_lembaga;

UPDATE app_public_branding
SET bentuk_lembaga = 'Desa'
WHERE bentuk_lembaga IS NULL OR TRIM(bentuk_lembaga) = '';

UPDATE app_public_branding
SET bentuk_kecamatan = 'Kecamatan'
WHERE bentuk_kecamatan IS NULL OR TRIM(bentuk_kecamatan) = '';
