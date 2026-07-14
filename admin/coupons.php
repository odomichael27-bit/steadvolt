<?php
// ============================================================
//  STEADVOLT — Admin Coupons Manager
//  File: admin/coupons.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Coupons';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

// ---- SAVE --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/coupons.php'); }

    $p_action = post('p_action');
    $row_id   = (int)post('row_id');
    $code     = strtoupper(trim(post('code')));
    $type     = post('type') === 'fixed' ? 'fixed' : 'percent';
    $value    = (float)post('value');
    $min      = (float)post('min_order');
    $max_uses = (int)post('max_uses');
    $expires  = trim(post('expires_at')) ?: null;
    $active   = (int)post('is_active', 1);

    if (!$code || $value <= 0) {
        flash('admin', 'Coupon code and value are required.', 'error');
        redirect('/admin/coupons.php?action=' . ($row_id ? 'edit&id='.$row_id : 'add'));
    }

    if ($p_action === 'add') {
        // Check duplicate
        if (DB::val("SELECT id FROM coupons WHERE code=?", [$code])) {
            flash('admin', 'Coupon code already exists.', 'error');
            redirect('/admin/coupons.php?action=add');
        }
        DB::query(
            "INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at, is_active) VALUES (?,?,?,?,?,?,?)",
            [$code, $type, $value, $min, $max_uses, $expires, $active]
        );
        flash('admin', 'Coupon created!', 'success');
    } else {
        DB::query(
            "UPDATE coupons SET code=?, type=?, value=?, min_order=?, max_uses=?, expires_at=?, is_active=? WHERE id=?",
            [$code, $type, $value, $min, $max_uses, $expires, $active, $row_id]
        );
        flash('admin', 'Coupon updated.', 'success');
    }
    redirect('/admin/coupons.php');
}

// ---- DELETE ------------------------------------------------
if ($action === 'delete' && $edit_id) {
    DB::query("DELETE FROM coupons WHERE id=?", [$edit_id]);
    flash('admin', 'Coupon deleted.', 'success');
    redirect('/admin/coupons.php');
}

// ---- TOGGLE ------------------------------------------------
if ($action === 'toggle' && $edit_id) {
    DB::query("UPDATE coupons SET is_active = 1-is_active WHERE id=?", [$edit_id]);
    redirect('/admin/coupons.php');
}

$edit_row = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM coupons WHERE id=?", [$edit_id]);
}

$coupons = DB::all("SELECT * FROM coupons ORDER BY created_at DESC");

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1>Coupons</h1>
      <p>Create discount codes for customers.</p>
    </div>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Coupon</a>
  </div>

  <?= flash_html('admin') ?>

  <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
  <div class="admin-card" style="max-width:680px">
    <h2><?= $action === 'add' ? 'New Coupon' : 'Edit Coupon' ?></h2>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
      <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>

      <div class="form-row-2">
        <div class="form-group">
          <label>Coupon Code *</label>
          <div class="input-wrap">
            <i class="fas fa-tag input-icon"></i>
            <input type="text" name="code" required value="<?= clean($edit_row['code'] ?? '') ?>"
                   placeholder="e.g. SOLAR20" style="text-transform:uppercase" maxlength="50"/>
          </div>
        </div>
        <div class="form-group">
          <label>Discount Type *</label>
          <div class="input-wrap">
            <i class="fas fa-percent input-icon"></i>
            <select name="type">
              <option value="percent" <?= ($edit_row['type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Percentage (%)</option>
              <option value="fixed"   <?= ($edit_row['type'] ?? '')           === 'fixed'   ? 'selected' : '' ?>>Fixed Amount (₦)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Discount Value *</label>
          <div class="input-wrap">
            <i class="fas fa-naira-sign input-icon"></i>
            <input type="number" name="value" required min="0" step="0.01" value="<?= $edit_row['value'] ?? '' ?>" placeholder="e.g. 20 for 20% or 5000 for ₦5,000"/>
          </div>
        </div>
        <div class="form-group">
          <label>Minimum Order (₦)</label>
          <div class="input-wrap">
            <i class="fas fa-naira-sign input-icon"></i>
            <input type="number" name="min_order" min="0" value="<?= $edit_row['min_order'] ?? 0 ?>" placeholder="0 = no minimum"/>
          </div>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Max Uses (0 = unlimited)</label>
          <div class="input-wrap">
            <i class="fas fa-users input-icon"></i>
            <input type="number" name="max_uses" min="0" value="<?= $edit_row['max_uses'] ?? 0 ?>"/>
          </div>
          <?php if ($edit_row && $edit_row['used_count'] > 0): ?>
          <p class="hint">Used <?= $edit_row['used_count'] ?> time(s) so far.</p>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label>Expiry Date (optional)</label>
          <div class="input-wrap">
            <i class="fas fa-calendar input-icon"></i>
            <input type="datetime-local" name="expires_at"
                   value="<?= $edit_row['expires_at'] ? date('Y-m-d\TH:i', strtotime($edit_row['expires_at'])) : '' ?>"/>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label class="toggle-label">
          <input type="checkbox" name="is_active" value="1" <?= !isset($edit_row) || $edit_row['is_active'] ? 'checked' : '' ?>/> Active
        </label>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Coupon</button>
        <a href="<?= BASE_URL ?>/admin/coupons.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <?php if (empty($coupons)): ?>
    <p class="empty-state-sm">No coupons yet. <a href="?action=add">Create one →</a></p>
    <?php else: ?>
    <div class="table-wrap"><table class="data-table">
      <thead>
        <tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Uses</th><th>Expires</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($coupons as $c): ?>
        <?php
          $expired = $c['expires_at'] && strtotime($c['expires_at']) < time();
          $maxed   = $c['max_uses'] > 0 && $c['used_count'] >= $c['max_uses'];
        ?>
        <tr class="<?= !$c['is_active'] || $expired || $maxed ? 'row-inactive' : '' ?>">
          <td><strong><?= clean($c['code']) ?></strong></td>
          <td><?= $c['type'] === 'percent' ? 'Percentage' : 'Fixed' ?></td>
          <td><?= $c['type'] === 'percent' ? $c['value'] . '%' : money($c['value']) ?></td>
          <td><?= $c['min_order'] > 0 ? money($c['min_order']) : 'None' ?></td>
          <td>
            <?= $c['used_count'] ?>
            <?= $c['max_uses'] > 0 ? ' / ' . $c['max_uses'] : '' ?>
          </td>
          <td>
            <?php if ($c['expires_at']): ?>
              <span class="<?= $expired ? 'text-danger' : '' ?>"><?= date('M j, Y', strtotime($c['expires_at'])) ?></span>
            <?php else: ?>
              <span class="text-muted">Never</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($expired): ?>
              <span class="status-pill status-cancelled">Expired</span>
            <?php elseif ($maxed): ?>
              <span class="status-pill status-cancelled">Exhausted</span>
            <?php else: ?>
              <a href="?action=toggle&id=<?= $c['id'] ?>">
                <span class="status-pill status-<?= $c['is_active'] ? 'active' : 'inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span>
              </a>
            <?php endif; ?>
          </td>
          <td class="td-actions">
            <a href="?action=edit&id=<?= $c['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></a>
            <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this coupon?')"><i class="fas fa-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
