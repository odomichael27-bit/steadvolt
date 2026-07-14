<?php
// ============================================================
//  STEADVOLT — Admin Products Manager
//  File: admin/products.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Products';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

// ---- SAVE (add or update) -----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/products.php'); }

    $p_action   = post('p_action');
    $prod_id    = (int)post('product_id');
    $name       = trim(post('name'));
    $category_id= (int)post('category_id') ?: null;
    $brand_id   = (int)post('brand_id') ?: null;
    $price      = (float)post('price');
    $comp_price = (float)post('compare_price') ?: null;
    $sku        = trim(post('sku')) ?: null;
    $stock      = (int)post('stock');
    $low_alert  = (int)post('low_stock_alert', 5);
    $short_desc = trim(post('short_desc'));
    $description= trim(post('description'));
    $is_featured= (int)post('is_featured');
    $is_active  = (int)post('is_active', 1);
    $meta_title = trim(post('meta_title'));
    $meta_desc  = trim(post('meta_desc'));
    $slug       = slugify($name);

    // Manual star-rating override
    $rating_override_raw = trim(post('rating_override'));
    $rating_override      = $rating_override_raw === '' ? null : max(0, min(5, round((float)$rating_override_raw * 2) / 2));
    $rating_override_count_raw = trim(post('rating_override_count'));
    $rating_override_count = ($rating_override === null || $rating_override_count_raw === '') ? null : max(0, (int)$rating_override_count_raw);

    if (!$name || $price <= 0) {
        flash('admin', 'Name and price are required.', 'error');
        redirect('/admin/products.php?action=' . ($prod_id ? 'edit&id='.$prod_id : 'add'));
    }

    // Ensure slug uniqueness
    $existing = DB::val("SELECT id FROM products WHERE slug=? AND id!=?", [$slug, $prod_id]);
    if ($existing) $slug .= '-' . time();

    if ($p_action === 'add') {
        $prod_id = DB::insert(
            "INSERT INTO products (category_id, brand_id, name, slug, short_desc, description, sku, price,
             compare_price, stock, low_stock_alert, is_featured, is_active, meta_title, meta_desc,
             rating_override, rating_override_count)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$category_id, $brand_id, $name, $slug, $short_desc, $description, $sku, $price,
             $comp_price, $stock, $low_alert, $is_featured, $is_active, $meta_title, $meta_desc,
             $rating_override, $rating_override_count]
        );
        flash('admin', 'Product added! Now upload images and add specs.', 'success');
    } else {
        DB::query(
            "UPDATE products SET category_id=?, brand_id=?, name=?, slug=?, short_desc=?, description=?,
             sku=?, price=?, compare_price=?, stock=?, low_stock_alert=?, is_featured=?, is_active=?,
             meta_title=?, meta_desc=?, rating_override=?, rating_override_count=? WHERE id=?",
            [$category_id, $brand_id, $name, $slug, $short_desc, $description, $sku, $price,
             $comp_price, $stock, $low_alert, $is_featured, $is_active, $meta_title, $meta_desc,
             $rating_override, $rating_override_count, $prod_id]
        );
        flash('admin', 'Product updated.', 'success');
    }

    // Handle image uploads
    if (!empty($_FILES['images']['name'][0])) {
        $upload_dir = UPLOADS_PATH . '/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $is_first_image = ($p_action === 'add');
        foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;
            $ext  = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
            if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) continue;
            $fname = 'prod_' . $prod_id . '_' . time() . '_' . $i . '.' . $ext;
            if (move_uploaded_file($tmp, $upload_dir . $fname)) {
                DB::query(
                    "INSERT INTO product_images (product_id, filename, is_primary, sort_order) VALUES (?,?,?,?)",
                    [$prod_id, $fname, ($is_first_image && $i === 0) ? 1 : 0, $i]
                );
                if ($is_first_image && $i === 0) $is_first_image = false;
            }
        }
    }

    // Handle specs (posted as JSON string)
    $specs_json = post('specs_json');
    if ($specs_json) {
        $specs = json_decode($specs_json, true);
        DB::query("DELETE FROM product_specs WHERE product_id=?", [$prod_id]);
        if (is_array($specs)) {
            foreach ($specs as $i => $spec) {
                if (!empty($spec['key'])) {
                    DB::query("INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order) VALUES (?,?,?,?)",
                        [$prod_id, $spec['key'], $spec['val'], $i]);
                }
            }
        }
    }

    redirect('/admin/products.php?action=edit&id=' . $prod_id);
}

// ---- DELETE ------------------------------------------------
if ($action === 'delete' && $edit_id) {
    // Delete images from disk
    $imgs = DB::all("SELECT filename FROM product_images WHERE product_id=?", [$edit_id]);
    foreach ($imgs as $img) {
        @unlink(UPLOADS_PATH . '/products/' . $img['filename']);
    }
    DB::query("DELETE FROM products WHERE id=?", [$edit_id]);
    flash('admin', 'Product deleted.', 'success');
    redirect('/admin/products.php');
}

// ---- SET PRIMARY IMAGE -------------------------------------
if ($action === 'set_primary' && $edit_id) {
    $img_id  = (int)get_param('img');
    $prod_id = (int)get_param('product');
    DB::query("UPDATE product_images SET is_primary=0 WHERE product_id=?", [$prod_id]);
    DB::query("UPDATE product_images SET is_primary=1 WHERE id=?", [$img_id]);
    redirect('/admin/products.php?action=edit&id=' . $prod_id);
}

// ---- DELETE IMAGE ------------------------------------------
if ($action === 'del_img' && $edit_id) {
    $img = DB::row("SELECT * FROM product_images WHERE id=?", [$edit_id]);
    if ($img) {
        @unlink(UPLOADS_PATH . '/products/' . $img['filename']);
        DB::query("DELETE FROM product_images WHERE id=?", [$edit_id]);
    }
    redirect('/admin/products.php?action=edit&id=' . get_param('product'));
}

// ---- DATA for forms ----------------------------------------
$categories = DB::all("SELECT id, name FROM categories ORDER BY sort_order");
$brands     = DB::all("SELECT id, name FROM brands ORDER BY name");

$edit_product = null;
$edit_images  = [];
$edit_specs   = [];
if (($action === 'edit' || $action === 'add') && $edit_id) {
    $edit_product = DB::row("SELECT * FROM products WHERE id=?", [$edit_id]);
    $edit_images  = DB::all("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order", [$edit_id]);
    $edit_specs   = DB::all("SELECT * FROM product_specs WHERE product_id=? ORDER BY sort_order", [$edit_id]);
}

// ---- PRODUCT LIST filters ----------------------------------
$filter      = get_param('filter');
$search      = get_param('q');
$page_num    = max(1, (int)get_param('page', 1));
$per_page    = 20;
$where       = ['1=1'];
$params      = [];

if ($search) { $where[] = 'p.name LIKE ?'; $params[] = '%'.$search.'%'; }
if ($filter === 'low_stock') { $where[] = 'p.stock <= p.low_stock_alert AND p.is_active=1'; }
if ($filter === 'inactive')  { $where[] = 'p.is_active = 0'; }

$where_sql = implode(' AND ', $where);
$total = (int)DB::val("SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE {$where_sql}", $params);
$pager = paginate($total, $per_page, $page_num);
$products = DB::all(
    "SELECT p.*, c.name AS category, pi.filename AS image
     FROM products p
     LEFT JOIN categories c ON c.id=p.category_id
     LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
     WHERE {$where_sql}
     ORDER BY p.updated_at DESC
     LIMIT {$per_page} OFFSET {$pager['offset']}",
    $params
);

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

<?php if ($action === 'add' || ($action === 'edit' && $edit_product)): ?>
<!-- ====================================================
     ADD / EDIT FORM
===================================================== -->
<div class="admin-page-header">
  <h1><?= $action === 'add' ? 'Add New Product' : 'Edit: ' . clean($edit_product['name']) ?></h1>
  <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Products</a>
</div>

<form method="POST" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
  <?php if ($edit_product): ?><input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>"/><?php endif; ?>

  <div class="product-edit-grid">
    <!-- LEFT -->
    <div class="product-edit-main">

      <div class="admin-card">
        <h3>Basic Information</h3>
        <div class="form-group">
          <label>Product Name *</label>
          <div class="input-wrap"><i class="fas fa-box input-icon"></i>
            <input type="text" name="name" required value="<?= clean($edit_product['name'] ?? '') ?>" placeholder="e.g. Jinko 400W Mono PERC Solar Panel"/>
          </div>
        </div>
        <div class="form-group">
          <label>Short Description</label>
          <textarea name="short_desc" rows="2" maxlength="500" placeholder="Brief 1-2 line description shown on product cards"><?= clean($edit_product['short_desc'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Full Description (HTML supported)</label>
          <textarea name="description" rows="10" placeholder="Full product details, features, compatibility notes…" id="descEditor"><?= $edit_product['description'] ?? '' ?></textarea>
          <p class="hint"><i class="fas fa-info-circle"></i> This is rendered as raw HTML on the product page — use the live preview below to check it before saving.</p>
        </div>
        <div class="form-group">
          <div class="desc-preview-toggle-row">
            <label style="margin:0">Live Preview</label>
            <button type="button" class="btn btn-outline btn-xs" id="toggleDescPreview"><i class="fas fa-eye"></i> Show Preview</button>
          </div>
          <div id="descPreviewWrap" class="desc-preview-wrap hidden">
            <iframe id="descPreviewFrame" class="desc-preview-frame" title="Description preview"></iframe>
          </div>
        </div>
      </div>

      <!-- Images -->
      <div class="admin-card">
        <h3>Product Images</h3>
        <?php if (!empty($edit_images)): ?>
        <div class="product-images-grid">
          <?php foreach ($edit_images as $img): ?>
          <div class="product-img-thumb-admin <?= $img['is_primary'] ? 'primary' : '' ?>">
            <img src="<?= product_img_url($img['filename']) ?>" alt="product image"/>
            <div class="img-thumb-actions">
              <?php if (!$img['is_primary']): ?>
              <a href="?action=set_primary&id=<?= $img['id'] ?>&product=<?= $edit_product['id'] ?>" class="btn btn-xs btn-outline" title="Set as primary">
                <i class="fas fa-star"></i>
              </a>
              <?php else: ?>
              <span class="badge-primary-img"><i class="fas fa-star"></i> Main</span>
              <?php endif; ?>
              <a href="?action=del_img&id=<?= $img['id'] ?>&product=<?= $edit_product['id'] ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this image?')" title="Delete">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="form-group" style="margin-top:16px">
          <label>Upload Images (JPG/PNG/WEBP, max 5MB each)</label>
          <input type="file" name="images[]" multiple accept="image/*" class="file-input"/>
          <p class="hint">First uploaded image becomes the primary image for new products.</p>
        </div>
      </div>

      <!-- Specs -->
      <div class="admin-card">
        <h3>Specifications</h3>
        <div id="specsEditor">
          <?php foreach ($edit_specs as $spec): ?>
          <div class="spec-row">
            <input type="text" class="spec-key" placeholder="e.g. Wattage" value="<?= clean($spec['spec_key']) ?>"/>
            <input type="text" class="spec-val" placeholder="e.g. 400W" value="<?= clean($spec['spec_value']) ?>"/>
            <button type="button" class="btn btn-xs btn-danger remove-spec"><i class="fas fa-times"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="addSpecRow"><i class="fas fa-plus"></i> Add Spec</button>
        <input type="hidden" name="specs_json" id="specsJson"/>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="product-edit-sidebar">
      <div class="admin-card">
        <h3>Pricing & Stock</h3>
        <div class="form-group">
          <label>Price (₦) *</label>
          <div class="input-wrap"><i class="fas fa-naira-sign input-icon"></i><input type="number" name="price" step="0.01" min="0" required value="<?= $edit_product['price'] ?? '' ?>"/></div>
        </div>
        <div class="form-group">
          <label>Compare Price (₦) <small>— shown as strikethrough</small></label>
          <div class="input-wrap"><i class="fas fa-naira-sign input-icon"></i><input type="number" name="compare_price" step="0.01" min="0" value="<?= $edit_product['compare_price'] ?? '' ?>"/></div>
        </div>
        <div class="form-group">
          <label>SKU / Product Code</label>
          <div class="input-wrap"><i class="fas fa-barcode input-icon"></i><input type="text" name="sku" value="<?= clean($edit_product['sku'] ?? '') ?>" placeholder="e.g. JK-400W-MONO"/></div>
        </div>
        <div class="form-group">
          <label>Stock Quantity *</label>
          <div class="input-wrap"><i class="fas fa-cubes input-icon"></i><input type="number" name="stock" min="0" required value="<?= $edit_product['stock'] ?? 0 ?>"/></div>
        </div>
        <div class="form-group">
          <label>Low Stock Alert Below</label>
          <div class="input-wrap"><input type="number" name="low_stock_alert" min="0" value="<?= $edit_product['low_stock_alert'] ?? 5 ?>"/></div>
        </div>
      </div>

      <div class="admin-card">
        <h3>Catalogue</h3>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($edit_product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= clean($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Brand</label>
          <select name="brand_id">
            <option value="">— None —</option>
            <?php foreach ($brands as $b): ?>
            <option value="<?= $b['id'] ?>" <?= ($edit_product['brand_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= clean($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="toggle-label">
            <input type="checkbox" name="is_featured" value="1" <?= !empty($edit_product['is_featured']) ? 'checked' : '' ?>/> Featured Product
          </label>
        </div>
        <div class="form-group">
          <label class="toggle-label">
            <input type="checkbox" name="is_active" value="1" <?= ($edit_product['is_active'] ?? 1) ? 'checked' : '' ?>/> Active (visible to customers)
          </label>
        </div>
      </div>

      <div class="admin-card">
        <h3><i class="fas fa-star"></i> Star Rating</h3>
        <div class="form-group">
          <label>Manual Rating Override</label>
          <select name="rating_override">
            <option value="">— Auto (based on customer reviews) —</option>
            <?php
            $current_override = $edit_product['rating_override'] ?? null;
            for ($r = 5.0; $r >= 0.5; $r -= 0.5):
            ?>
            <option value="<?= $r ?>" <?= ($current_override !== null && abs((float)$current_override - $r) < 0.01) ? 'selected' : '' ?>><?= number_format($r, 1) ?> stars</option>
            <?php endfor; ?>
          </select>
          <p class="hint"><i class="fas fa-info-circle"></i> Overrides the star rating shown on the shop and product page. Leave as "Auto" to show the real average from approved customer reviews.</p>
        </div>
        <div class="form-group">
          <label>Displayed Review Count <small>(optional)</small></label>
          <div class="input-wrap"><input type="number" name="rating_override_count" min="0" value="<?= clean((string)($edit_product['rating_override_count'] ?? '')) ?>" placeholder="Leave blank to show the real review count"/></div>
        </div>
      </div>

      <div class="admin-card">
        <h3>SEO</h3>
        <div class="form-group">
          <label>Meta Title</label>
          <input type="text" name="meta_title" value="<?= clean($edit_product['meta_title'] ?? '') ?>" maxlength="255" placeholder="Leave blank to use product name"/>
        </div>
        <div class="form-group">
          <label>Meta Description</label>
          <textarea name="meta_desc" rows="3" maxlength="500" placeholder="Brief SEO description"><?= clean($edit_product['meta_desc'] ?? '') ?></textarea>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg">
        <i class="fas fa-save"></i> <?= $action === 'add' ? 'Add Product' : 'Save Changes' ?>
      </button>

      <?php if ($edit_product): ?>
      <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($edit_product['slug']) ?>" target="_blank" class="btn btn-outline btn-block">
        <i class="fas fa-eye"></i> Preview Product
      </a>
      <a href="?action=delete&id=<?= $edit_product['id'] ?>" class="btn btn-danger btn-block" onclick="return confirm('Permanently delete this product?')">
        <i class="fas fa-trash"></i> Delete Product
      </a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php else: ?>
<!-- ====================================================
     PRODUCT LIST
===================================================== -->
<div class="admin-page-header">
  <h1>Products <small>(<?= number_format($total) ?>)</small></h1>
  <div style="display:flex;gap:8px">
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Product</a>
  </div>
</div>

<!-- Search & filters -->
<div class="admin-filters">
  <form method="GET" class="filter-form">
    <div class="input-wrap" style="flex:1;max-width:320px">
      <i class="fas fa-search input-icon"></i>
      <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Search products…"/>
    </div>
    <select name="filter" onchange="this.form.submit()">
      <option value="">All Products</option>
      <option value="low_stock" <?= $filter==='low_stock'?'selected':'' ?>>Low Stock</option>
      <option value="inactive"  <?= $filter==='inactive' ?'selected':'' ?>>Inactive</option>
    </select>
    <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i> Search</button>
    <?php if ($search || $filter): ?>
    <a href="?" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="admin-card">
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr class="<?= !$p['is_active'] ? 'row-inactive' : '' ?>">
          <td class="td-img">
            <?php if ($p['image']): ?>
            <img src="<?= product_img_url($p['image']) ?>" alt="product" class="table-product-img"/>
            <?php else: ?>
            <div class="table-product-img no-img"><i class="fas fa-box-open"></i></div>
            <?php endif; ?>
          </td>
          <td>
            <strong><?= clean($p['name']) ?></strong>
            <?php if ($p['sku']): ?><br><small class="text-muted"><?= clean($p['sku']) ?></small><?php endif; ?>
          </td>
          <td><?= clean($p['category'] ?? '—') ?></td>
          <td>
            <?= money($p['price']) ?>
            <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
            <br><small class="price-old"><?= money($p['compare_price']) ?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="stock-bar-wrap">
              <span class="stock-num <?= $p['stock'] <= $p['low_stock_alert'] ? 'text-danger' : '' ?>"><?= $p['stock'] ?></span>
              <?php if ($p['stock'] <= $p['low_stock_alert'] && $p['stock'] > 0): ?>
              <span class="badge badge-warn">Low</span>
              <?php elseif ($p['stock'] == 0): ?>
              <span class="badge badge-out">Out</span>
              <?php endif; ?>
            </span>
          </td>
          <td>
            <?php if ($p['is_active']): ?>
            <span class="status-pill status-active"><i class="fas fa-circle"></i> Active</span>
            <?php else: ?>
            <span class="status-pill status-inactive"><i class="fas fa-circle"></i> Draft</span>
            <?php endif; ?>
            <?php if ($p['is_featured']): ?>
            <span class="badge badge-featured"><i class="fas fa-star"></i></span>
            <?php endif; ?>
          </td>
          <td class="td-actions">
            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-outline btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
            <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>" target="_blank" class="btn btn-outline btn-xs" title="Preview"><i class="fas fa-eye"></i></a>
            <a href="?action=delete&id=<?= $p['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this product?')" title="Delete"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($pager['total_pages'] > 1): ?>
  <nav class="pagination pagination-admin">
    <?php for ($i = 1; $i <= $pager['total_pages']; $i++): ?>
    <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?><?= $filter ? '&filter='.$filter : '' ?>"
       class="page-btn <?= $i === $pager['current'] ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
// Specs editor
const specsEditor = document.getElementById('specsEditor');
const specsJson   = document.getElementById('specsJson');

document.getElementById('addSpecRow')?.addEventListener('click', () => {
  const row = document.createElement('div');
  row.className = 'spec-row';
  row.innerHTML = `<input type="text" class="spec-key" placeholder="e.g. Weight"/>
    <input type="text" class="spec-val" placeholder="e.g. 22kg"/>
    <button type="button" class="btn btn-xs btn-danger remove-spec"><i class="fas fa-times"></i></button>`;
  specsEditor.appendChild(row);
});

document.addEventListener('click', e => {
  if (e.target.closest('.remove-spec')) e.target.closest('.spec-row').remove();
});

// Serialize specs to JSON before submit
document.querySelector('form')?.addEventListener('submit', () => {
  const rows = document.querySelectorAll('.spec-row');
  const specs = [];
  rows.forEach(r => {
    const key = r.querySelector('.spec-key').value.trim();
    const val = r.querySelector('.spec-val').value.trim();
    if (key) specs.push({ key, val });
  });
  if (specsJson) specsJson.value = JSON.stringify(specs);
});

// Avatar/image preview
document.querySelector('.file-input')?.addEventListener('change', function(){
  // Nothing needed — server handles
});

// ---- Full Description live preview ----------------------------
(function () {
  const textarea    = document.getElementById('descEditor');
  const toggleBtn    = document.getElementById('toggleDescPreview');
  const previewWrap  = document.getElementById('descPreviewWrap');
  const previewFrame = document.getElementById('descPreviewFrame');
  if (!textarea || !toggleBtn || !previewFrame) return;

  const baseUrl = <?= json_encode(BASE_URL) ?>;

  function renderPreview() {
    const html = textarea.value || '<p class="no-content" style="color:#94a3b8;font-style:italic">Nothing typed yet…</p>';
    previewFrame.srcdoc =
      '<!DOCTYPE html><html><head><meta charset="UTF-8">' +
      '<base href="' + baseUrl + '/">' +
      '<link rel="stylesheet" href="' + baseUrl + '/fontawesome5/css/all.css">' +
      '<link rel="stylesheet" href="' + baseUrl + '/assets/css/style.css">' +
      '<style>body{margin:0;padding:16px;background:#fff;font-family:Inter,sans-serif}</style>' +
      '</head><body><div class="product-description">' + html + '</div></body></html>';
  }

  let debounceTimer;
  textarea.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(renderPreview, 300);
  });

  toggleBtn.addEventListener('click', () => {
    const isHidden = previewWrap.classList.contains('hidden');
    if (isHidden) {
      renderPreview();
      previewWrap.classList.remove('hidden');
      toggleBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Preview';
    } else {
      previewWrap.classList.add('hidden');
      toggleBtn.innerHTML = '<i class="fas fa-eye"></i> Show Preview';
    }
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
