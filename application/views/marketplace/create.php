<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$categories = isset($categories) && is_array($categories) ? $categories : array();
$product = isset($product) && is_array($product) ? $product : array();
$editMode = !empty($editMode);
$values = isset($formValues) && is_array($formValues) ? $formValues : array();
$values = array_merge(array(
    'category_id' => $product['category_id'] ?? '',
    'name' => $product['name'] ?? '',
    'price' => isset($product['price']) ? (float) $product['price'] : '',
    'stock' => $product['stock'] ?? '',
    'description' => $product['description'] ?? ''
), $values);
$errors = isset($fieldErrors) && is_array($fieldErrors) ? $fieldErrors : array();
$value = static function ($key, $fallback = '') use ($values) { return isset($values[$key]) ? (string) $values[$key] : (string) $fallback; };
$error = static function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
?>

<div class="marketplace-page marketplace-form-page">
    <section class="market-hero market-hero-compact" aria-labelledby="market-create-title">
        <div class="market-hero-copy"><p class="market-eyebrow color-white">PASAR DIGITAL</p><h1 id="market-create-title">Jual Produk</h1><span class="color-white">Bagikan produk unggulan Anda kepada warga.</span></div>
        <span class="market-hero-icon color-white" aria-hidden="true"><i class="fa fa-camera color-white"></i></span>
    </section>

    <section class="card card-style market-form-card">
        <div class="content">
            <?php if (!empty($errors['form'])): ?><div class="market-form-alert" role="alert"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><?= e($errors['form']) ?></div><?php endif; ?>
            <form method="post" action="<?= site_url('pasar/simpan') ?>" enctype="multipart/form-data" data-market-product-form data-disable-submit>
                <?= csrf_field() ?>
                <?php if ($editMode && !empty($product['id'])): ?><input type="hidden" name="product_id" value="<?= e($product['id']) ?>"><?php endif; ?>
                <div class="market-form-section-title"><span class="color-white">1</span><div><h2>Informasi produk</h2><p>Lengkapi informasi dasar yang akan dilihat warga.</p></div></div>
                <label class="market-form-field" for="market-product-category"><span>Kategori</span><select id="market-product-category" name="category_id" required><option value="">Pilih kategori</option><?php foreach ($categories as $category): ?><?php $categoryId = (string) ($category['id'] ?? ($category['slug'] ?? '')); ?><option value="<?= e($categoryId) ?>" <?= $value('category_id') === $categoryId ? 'selected' : '' ?>><?= e($category['name'] ?? ($category['label'] ?? $categoryId)) ?></option><?php endforeach; ?></select><?php if ($error('category_id')): ?><small class="market-form-error"><?= e($error('category_id')) ?></small><?php endif; ?></label>
                <label class="market-form-field" for="market-product-name"><span>Nama produk</span><input type="text" id="market-product-name" name="name" value="<?= e($value('name')) ?>" required maxlength="180" placeholder="Contoh: Keripik keladi khas kampung"><?php if ($error('name')): ?><small class="market-form-error"><?= e($error('name')) ?></small><?php endif; ?></label>
                <label class="market-form-field" for="market-product-price"><span>Harga (Rupiah)</span><span class="market-money-input"><b>Rp</b><input type="text" id="market-product-price" name="price" value="<?= e($value('price')) ?>" required maxlength="17" inputmode="numeric" autocomplete="off" placeholder="Contoh: 200.000" data-market-price></span><?php if ($error('price')): ?><small class="market-form-error"><?= e($error('price')) ?></small><?php endif; ?></label>
                <label class="market-form-field" for="market-product-stock"><span>Stok <em>(opsional)</em></span><input type="number" id="market-product-stock" name="stock" value="<?= e($value('stock')) ?>" min="0" max="4294967295" step="1" inputmode="numeric" placeholder="Kosongkan jika tidak dibatasi"></label>
                <label class="market-form-field" for="market-product-description"><span>Deskripsi produk <em>(opsional)</em></span><textarea id="market-product-description" name="description" rows="4" maxlength="5000" placeholder="Jelaskan ukuran, bahan, rasa, atau informasi penting lainnya."><?= e($value('description')) ?></textarea></label>

                <div class="market-form-section-title market-form-section-gap"><span class="color-white">2</span><div><h2>Foto produk</h2><p>Tambahkan beberapa foto agar produk lebih menarik.</p></div></div>
                <label class="market-upload-zone" for="market-product-images"><i class="fa fa-images" aria-hidden="true"></i><strong>Pilih beberapa foto produk</strong><span>JPG, PNG, atau WEBP · maksimal 6 foto · 5 MB per foto</span></label>
                <input class="market-file-input" type="file" id="market-product-images" name="product_images[]" accept="image/jpeg,image/png,image/webp" multiple data-market-images <?= $editMode ? '' : 'required' ?>>
                <div class="market-image-preview" data-market-image-preview aria-live="polite">
                    <?php if ($editMode && !empty($product['images']) && is_array($product['images'])): ?>
                        <?php foreach ($product['images'] as $index => $image): ?><?php $previewUrl = $image['thumbnail_url'] ?? ($image['url'] ?? ''); ?><?php if ($previewUrl !== ''): ?><figure data-index="<?= (int) $index + 1 ?>"><img src="<?= e($previewUrl) ?>" alt="Foto produk <?= (int) $index + 1 ?>" loading="lazy"></figure><?php endif; ?><?php endforeach; ?>
                    <?php else: ?><span>Belum ada foto dipilih.</span><?php endif; ?>
                </div>
                <?php if ($error('images')): ?><small class="market-form-error market-form-error-block"><?= e($error('images')) ?></small><?php endif; ?>
                <div class="market-form-actions"><a href="<?= site_url('pasar/tokoku') ?>" class="btn btn-s market-form-cancel">Batal</a><button type="submit" class="btn btn-s market-form-submit"><i class="fa fa-cloud-upload-alt color-white" aria-hidden="true"></i><span class="color-white"><?= $editMode ? 'Simpan perubahan' : 'Terbitkan produk' ?></span></button></div>
            </form>
        </div>
    </section>
</div>
