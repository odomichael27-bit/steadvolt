<?php
// ============================================================
//  STEADVOLT — Admin Categories Manager
//  File: admin/categories.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Categories';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin','Invalid request.','error'); redirect('/admin/categories.php'); }

    $p_action   = post('p_action');
    $row_id     = (int)post('row_id');
    $name       = trim(post('name'));
    $slug       = slugify($name);
    $description= trim(post('description'));
    $icon       = trim(post('icon')) ?: 'fa-box';
    $sort_order = (int)post('sort_order', 0);

    if (!$name) { flash('admin','Category name is required.','error'); redirect('/admin/categories.php'); }

    if ($p_action === 'add') {
        // Ensure unique slug
        $existing = DB::val("SELECT id FROM categories WHERE slug=?", [$slug]);
        if ($existing) $slug .= '-' . time();
        DB::query("INSERT INTO categories (name, slug, description, icon, sort_order) VALUES (?,?,?,?,?)",
            [$name, $slug, $description, $icon, $sort_order]);
        flash('admin','Category added!','success');
    } else {
        DB::query("UPDATE categories SET name=?, description=?, icon=?, sort_order=? WHERE id=?",
            [$name, $description, $icon, $sort_order, $row_id]);
        flash('admin','Category updated.','success');
    }
    redirect('/admin/categories.php');
}

if ($action === 'delete' && $edit_id) {
    $count = DB::val("SELECT COUNT(*) FROM products WHERE category_id=?", [$edit_id]);
    if ($count > 0) { flash('admin',"Cannot delete: $count product(s) use this category. Reassign them first.",'error'); redirect('/admin/categories.php'); }
    DB::query("DELETE FROM categories WHERE id=?", [$edit_id]);
    flash('admin','Category deleted.','success');
    redirect('/admin/categories.php');
}

$edit_row  = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM categories WHERE id=?", [$edit_id]);
}

$categories = DB::all(
    "SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id=c.id) AS product_count
     FROM categories c ORDER BY c.sort_order ASC, c.name ASC"
);

// Common Font Awesome 5 icons for energy/tech products
$fa_icons = ['fa-sun','fa-battery-full','fa-video','fa-bolt','fa-plug','fa-tools','fa-microchip',
             'fa-solar-panel','fa-laptop','fa-broadcast-tower','fa-power-off','fa-charging-station'];

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div><h1>Categories</h1><p>Organise your products into categories.</p></div>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Category</a>
  </div>

  <?= flash_html('admin') ?>

  <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
  <div class="admin-card" style="max-width:580px">
    <h2><?= $action === 'add' ? 'Add Category' : 'Edit Category' ?></h2>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
      <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>

      <div class="form-group">
        <label>Category Name *</label>
        <div class="input-wrap"><i class="fas fa-tags input-icon"></i>
          <input type="text" name="name" required value="<?= clean($edit_row['name'] ?? '') ?>" placeholder="e.g. Solar Systems"/>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Brief category description for the homepage…"><?= clean($edit_row['description'] ?? '') ?></textarea>
      </div>
      <div class="form-row-2">
        <div class="form-group">
          <label>Font Awesome 5 Icon</label>
          <div class="input-wrap"><i class="fas fa-icons input-icon"></i>
            <input type="text" name="icon" value="<?= clean($edit_row['icon'] ?? 'fa-box') ?>" placeholder="fa-sun" id="iconInput"/>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
            <?php foreach ($fa_icons as $ic): ?>
            <button type="button" onclick="document.getElementById('iconInput').value='<?= $ic ?>'; document.getElementById('iconPreview').className='fas <?= $ic ?>';"
                    style="padding:8px;border:1px solid #e5e7eb;border-radius:6px;background:#f9fafb;cursor:pointer;font-size:1.1rem;color:#1B4D3E" title="<?= $ic ?>">
              <i class="fas <?= $ic ?>"></i>
            </button>
            <?php endforeach; ?>
          </div>
          <p class="hint">Preview: <i id="iconPreview" class="fas <?= clean($edit_row['icon'] ?? 'fa-box') ?>"></i></p>
        </div>
        <div class="form-group">
          <label>Sort Order</label>
          <div class="input-wrap"><input type="number" name="sort_order" min="0" value="<?= $edit_row['sort_order'] ?? 0 ?>"/></div>
          <p class="hint">Lower numbers appear first.</p>
        </div>
      </div>
      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Products</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
          <td><i class="fas <?= clean($cat['icon']) ?>" style="font-size:1.2rem;color:#00A86B"></i></td>
          <td><strong><?= clean($cat['name']) ?></strong></td>
          <td><code style="font-size:.78rem;background:#f3f4f6;padding:2px 6px;border-radius:4px"><?= clean($cat['slug']) ?></code></td>
          <td><?= $cat['product_count'] ?></td>
          <td><?= $cat['sort_order'] ?></td>
          <td class="td-actions">
            <a href="?action=edit&id=<?= $cat['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></a>
            <a href="?action=delete&id=<?= $cat['id'] ?>" class="btn btn-danger btn-xs"
               onclick="return confirm('Delete category \'<?= clean($cat['name']) ?>\'?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>

<script>
document.getElementById('iconInput')?.addEventListener('input', function() {
  document.getElementById('iconPreview').className = 'fas ' + this.value;
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
