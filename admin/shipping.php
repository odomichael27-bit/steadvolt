<?php
// ============================================================
//  STEADVOLT — Admin Shipping Zones
//  File: admin/shipping.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Shipping Zones';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

// ---- SAVE --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin','Invalid request.','error'); redirect('/admin/shipping.php'); }

    $p_action  = post('p_action');
    $row_id    = (int)post('row_id');
    $zone_name = trim(post('zone_name'));
    $states    = trim(post('states'));
    $fee       = (float)post('fee');
    $min_days  = (int)post('min_days', 1);
    $max_days  = (int)post('max_days', 7);

    if (!$zone_name || $fee < 0) {
        flash('admin','Zone name and fee are required.','error');
        redirect('/admin/shipping.php');
    }

    if ($p_action === 'add') {
        DB::query(
            "INSERT INTO shipping_zones (zone_name, states, fee, min_days, max_days) VALUES (?,?,?,?,?)",
            [$zone_name, $states, $fee, $min_days, $max_days]
        );
        flash('admin','Shipping zone added!','success');
    } else {
        DB::query(
            "UPDATE shipping_zones SET zone_name=?, states=?, fee=?, min_days=?, max_days=? WHERE id=?",
            [$zone_name, $states, $fee, $min_days, $max_days, $row_id]
        );
        flash('admin','Zone updated.','success');
    }
    redirect('/admin/shipping.php');
}

// ---- DELETE ------------------------------------------------
if ($action === 'delete' && $edit_id) {
    DB::query("DELETE FROM shipping_zones WHERE id=?", [$edit_id]);
    flash('admin','Zone deleted.','success');
    redirect('/admin/shipping.php');
}

$edit_row = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM shipping_zones WHERE id=?", [$edit_id]);
}

$zones = DB::all("SELECT * FROM shipping_zones ORDER BY fee ASC");

$all_states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta',
    'Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi',
    'Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers',
    'Sokoto','Taraba','Yobe','Zamfara'];

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1>Shipping Zones</h1>
      <p>Set delivery fees per state/region across Nigeria.</p>
    </div>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Zone</a>
  </div>

  <?= flash_html('admin') ?>

  <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
  <div class="admin-card" style="max-width:680px">
    <h2><?= $action === 'add' ? 'Add Shipping Zone' : 'Edit Zone' ?></h2>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
      <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>

      <div class="form-row-2">
        <div class="form-group">
          <label>Zone Name *</label>
          <div class="input-wrap">
            <i class="fas fa-map input-icon"></i>
            <input type="text" name="zone_name" required value="<?= clean($edit_row['zone_name'] ?? '') ?>" placeholder="e.g. South West"/>
          </div>
        </div>
        <div class="form-group">
          <label>Shipping Fee (₦) *</label>
          <div class="input-wrap">
            <i class="fas fa-naira-sign input-icon"></i>
            <input type="number" name="fee" required min="0" step="50" value="<?= $edit_row['fee'] ?? '' ?>" placeholder="e.g. 4000"/>
          </div>
          <p class="hint">Set to 0 for free shipping in this zone.</p>
        </div>
      </div>

      <div class="form-group">
        <label>States Covered (comma-separated)</label>
        <div class="input-wrap">
          <i class="fas fa-map-marker-alt input-icon"></i>
          <input type="text" name="states" value="<?= clean($edit_row['states'] ?? '') ?>"
                 placeholder="e.g. Lagos,Ogun,Oyo"/>
        </div>
        <p class="hint">Leave blank to use as a fallback/default zone. Exact state names: <?= implode(', ', $all_states) ?></p>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Min Delivery Days</label>
          <div class="input-wrap"><input type="number" name="min_days" min="1" value="<?= $edit_row['min_days'] ?? 1 ?>"/></div>
        </div>
        <div class="form-group">
          <label>Max Delivery Days</label>
          <div class="input-wrap"><input type="number" name="max_days" min="1" value="<?= $edit_row['max_days'] ?? 7 ?>"/></div>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Zone</button>
        <a href="<?= BASE_URL ?>/admin/shipping.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="admin-info-box">
      <i class="fas fa-info-circle"></i>
      <div>Shipping fees are automatically calculated at checkout based on the customer's selected state.
      The zone with a matching state name is used. If no match is found, the zone with the highest fee is used as a fallback.</div>
    </div>
    <div class="table-wrap"><table class="data-table">
      <thead>
        <tr><th>Zone</th><th>States</th><th>Fee</th><th>Delivery Time</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($zones as $z): ?>
        <tr>
          <td><strong><?= clean($z['zone_name']) ?></strong></td>
          <td><span style="font-size:.82rem;color:#6b7280"><?= $z['states'] ? clean($z['states']) : '<em>All / Fallback</em>' ?></span></td>
          <td><?= $z['fee'] == 0 ? '<span class="text-green font-weight:700">Free</span>' : money($z['fee']) ?></td>
          <td><?= $z['min_days'] ?>–<?= $z['max_days'] ?> days</td>
          <td class="td-actions">
            <a href="?action=edit&id=<?= $z['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></a>
            <a href="?action=delete&id=<?= $z['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this shipping zone?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($zones)): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:32px">No shipping zones configured.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
