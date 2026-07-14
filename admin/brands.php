<?php
// ============================================================
//  STEADVOLT — Admin Brands Manager
//  File: admin/brands.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Brands';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin','Invalid request.','error'); redirect('/admin/brands.php'); }

    $p_action = post('p_action');
    $row_id   = (int)post('row_id');
    $name     = trim(post('name'));
    $slug     = slugify($name);

    if (!$name) { flash('admin','Brand name is required.','error'); redirect('/admin/brands.php'); }

    if ($p_action === 'add') {
        if (DB::val("SELECT id FROM brands WHERE slug=?",[$slug])) $slug .= '-'.time();
        DB::query("INSERT INTO brands (name, slug) VALUES (?,?)", [$name, $slug]);
        flash('admin','Brand added!','success');
    } else {
        DB::query("UPDATE brands SET name=? WHERE id=?", [$name, $row_id]);
        flash('admin','Brand updated.','success');
    }
    redirect('/admin/brands.php');
}

if ($action === 'delete' && $edit_id) {
    $count = DB::val("SELECT COUNT(*) FROM products WHERE brand_id=?",[$edit_id]);
    if ($count > 0) { flash('admin',"Cannot delete: $count product(s) use this brand.",'error'); redirect('/admin/brands.php'); }
    DB::query("DELETE FROM brands WHERE id=?", [$edit_id]);
    flash('admin','Brand deleted.','success');
    redirect('/admin/brands.php');
}

$edit_row = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM brands WHERE id=?", [$edit_id]);
}

$brands = DB::all(
    "SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id=b.id) AS product_count
     FROM brands b ORDER BY b.name ASC"
);

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div><h1>Brands</h1><p>Manage product brands and manufacturers.</p></div>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Brand</a>
  </div>

  <?= flash_html('admin') ?>

  <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
  <div class="admin-card" style="max-width:480px">
    <h2><?= $action === 'add' ? 'Add Brand' : 'Edit Brand' ?></h2>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
      <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>
      <div class="form-group">
        <label>Brand Name *</label>
        <div class="input-wrap"><i class="fas fa-trademark input-icon"></i>
          <input type="text" name="name" required value="<?= clean($edit_row['name'] ?? '') ?>" placeholder="e.g. Jinko Solar" autofocus/>
        </div>
      </div>
      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <a href="<?= BASE_URL ?>/admin/brands.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($brands as $b): ?>
        <tr>
          <td><strong><?= clean($b['name']) ?></strong></td>
          <td><code style="font-size:.78rem;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= clean($b['slug']) ?></code></td>
          <td><?= $b['product_count'] ?></td>
          <td class="td-actions">
            <a href="?action=edit&id=<?= $b['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></a>
            <a href="?action=delete&id=<?= $b['id'] ?>" class="btn btn-danger btn-xs"
               onclick="return confirm('Delete brand \'<?= clean($b['name']) ?>\'?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($brands)): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:32px">No brands yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
