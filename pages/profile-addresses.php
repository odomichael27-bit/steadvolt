<?php
// ============================================================
//  STEADVOLT — Profile Addresses
//  File: pages/profile-addresses.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_login('/pages/login.php?redirect=/pages/profile-addresses.php');

$user_id    = auth_user()['id'];
$page_title = 'My Addresses';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

// ---- SAVE --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('addresses','Invalid request.','error'); redirect('/pages/profile-addresses.php'); }

    $p_action = post('p_action');
    $row_id   = (int)post('row_id');
    $label    = trim(post('label')) ?: 'Home';
    $full_name= trim(post('full_name'));
    $phone    = trim(post('phone'));
    $address  = trim(post('address_line'));
    $city     = trim(post('city'));
    $state    = trim(post('state'));
    $default  = (int)post('is_default');

    if (!$full_name || !$address || !$city || !$state) {
        flash('addresses','Please fill in all required fields.','error');
        redirect('/pages/profile-addresses.php?action=' . ($row_id ? 'edit&id='.$row_id : 'add'));
    }

    if ($default) {
        DB::query("UPDATE addresses SET is_default=0 WHERE user_id=?", [$user_id]);
    }

    if ($p_action === 'add') {
        // First address is always default
        $is_first = !DB::val("SELECT id FROM addresses WHERE user_id=?", [$user_id]);
        DB::query(
            "INSERT INTO addresses (user_id, label, full_name, phone, address_line, city, state, is_default) VALUES (?,?,?,?,?,?,?,?)",
            [$user_id, $label, $full_name, $phone, $address, $city, $state, $default || $is_first ? 1 : 0]
        );
        flash('addresses','Address added!','success');
    } else {
        DB::query(
            "UPDATE addresses SET label=?, full_name=?, phone=?, address_line=?, city=?, state=?, is_default=? WHERE id=? AND user_id=?",
            [$label, $full_name, $phone, $address, $city, $state, $default, $row_id, $user_id]
        );
        flash('addresses','Address updated.','success');
    }
    redirect('/pages/profile-addresses.php');
}

// ---- DELETE ------------------------------------------------
if ($action === 'delete' && $edit_id) {
    DB::query("DELETE FROM addresses WHERE id=? AND user_id=?", [$edit_id, $user_id]);
    flash('addresses','Address deleted.','success');
    redirect('/pages/profile-addresses.php');
}

// ---- SET DEFAULT -------------------------------------------
if ($action === 'default' && $edit_id) {
    DB::query("UPDATE addresses SET is_default=0 WHERE user_id=?", [$user_id]);
    DB::query("UPDATE addresses SET is_default=1 WHERE id=? AND user_id=?", [$edit_id, $user_id]);
    redirect('/pages/profile-addresses.php');
}

$edit_row  = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM addresses WHERE id=? AND user_id=?", [$edit_id, $user_id]);
}

$addresses = DB::all("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id ASC", [$user_id]);

$states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta',
    'Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina','Kebbi',
    'Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers',
    'Sokoto','Taraba','Yobe','Zamfara'];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="profile-page">
  <div class="container">
    <div class="profile-layout">
      <?php include dirname(__DIR__) . '/includes/profile-nav.php'; ?>

      <main class="profile-main">
        <?= flash_html('addresses') ?>

        <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
        <!-- ADD / EDIT FORM -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><?= $action === 'add' ? '<i class="fas fa-plus-circle"></i> Add Address' : '<i class="fas fa-edit"></i> Edit Address' ?></h2>
            <a href="<?= BASE_URL ?>/pages/profile-addresses.php" class="btn btn-outline btn-sm">Cancel</a>
          </div>
          <form method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
            <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>

            <div class="form-row-2">
              <div class="form-group">
                <label>Address Label</label>
                <div class="input-wrap"><i class="fas fa-home input-icon"></i>
                  <input type="text" name="label" value="<?= clean($edit_row['label'] ?? 'Home') ?>" placeholder="e.g. Home, Office"/>
                </div>
              </div>
              <div class="form-group">
                <label>Full Name *</label>
                <div class="input-wrap"><i class="fas fa-user input-icon"></i>
                  <input type="text" name="full_name" required value="<?= clean($edit_row['full_name'] ?? '') ?>" placeholder="Recipient's full name"/>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Phone Number</label>
              <div class="input-wrap"><i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" value="<?= clean($edit_row['phone'] ?? '') ?>" placeholder="+234 800 0000000"/>
              </div>
            </div>

            <div class="form-group">
              <label>Street Address *</label>
              <div class="input-wrap"><i class="fas fa-map-marker-alt input-icon"></i>
                <input type="text" name="address_line" required value="<?= clean($edit_row['address_line'] ?? '') ?>" placeholder="House number, street, area"/>
              </div>
            </div>

            <div class="form-row-2">
              <div class="form-group">
                <label>City *</label>
                <div class="input-wrap"><i class="fas fa-city input-icon"></i>
                  <input type="text" name="city" required value="<?= clean($edit_row['city'] ?? '') ?>" placeholder="e.g. Victoria Island"/>
                </div>
              </div>
              <div class="form-group">
                <label>State *</label>
                <div class="input-wrap"><i class="fas fa-map input-icon"></i>
                  <select name="state" required>
                    <option value="">— Select State —</option>
                    <?php foreach ($states as $s): ?>
                    <option value="<?= $s ?>" <?= ($edit_row['state'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="toggle-label">
                <input type="checkbox" name="is_default" value="1" <?= ($edit_row['is_default'] ?? 0) ? 'checked' : '' ?>/>
                Set as default delivery address
              </label>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Address</button>
          </form>
        </div>

        <?php else: ?>
        <!-- ADDRESS LIST -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-map-marker-alt"></i> My Addresses</h2>
            <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New</a>
          </div>

          <?php if (empty($addresses)): ?>
          <div class="empty-state small">
            <i class="fas fa-map-marked-alt"></i>
            <h3>No saved addresses</h3>
            <p>Add a delivery address to speed up checkout.</p>
            <a href="?action=add" class="btn btn-primary btn-sm">Add Address</a>
          </div>
          <?php else: ?>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            <?php foreach ($addresses as $addr): ?>
            <div class="address-display-card <?= $addr['is_default'] ? 'default-address' : '' ?>">
              <div class="adc-header">
                <span class="adc-label"><i class="fas fa-<?= $addr['label'] === 'Office' ? 'building' : 'home' ?>"></i> <?= clean($addr['label']) ?></span>
                <?php if ($addr['is_default']): ?>
                <span class="badge" style="background:#D1FAE5;color:#065F46"><i class="fas fa-check"></i> Default</span>
                <?php endif; ?>
              </div>
              <p class="adc-name"><strong><?= clean($addr['full_name']) ?></strong></p>
              <?php if ($addr['phone']): ?><p class="adc-detail"><i class="fas fa-phone"></i> <?= clean($addr['phone']) ?></p><?php endif; ?>
              <p class="adc-detail"><i class="fas fa-map-marker-alt"></i> <?= clean($addr['address_line']) ?>, <?= clean($addr['city']) ?>, <?= clean($addr['state']) ?></p>
              <div class="adc-actions">
                <a href="?action=edit&id=<?= $addr['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i> Edit</a>
                <?php if (!$addr['is_default']): ?>
                <a href="?action=default&id=<?= $addr['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-star"></i> Set Default</a>
                <a href="?action=delete&id=<?= $addr['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this address?')"><i class="fas fa-trash"></i></a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </main>
    </div>
  </div>
</div>

<style>
.address-display-card { background:var(--off-white); border:2px solid var(--gray-200); border-radius:var(--radius-lg); padding:20px; }
.address-display-card.default-address { border-color:var(--green); background:var(--green-light); }
.adc-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.adc-label { font-size:.82rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--gray-500); display:flex; align-items:center; gap:6px; }
.adc-name { margin-bottom:6px; }
.adc-detail { font-size:.85rem; color:var(--gray-600); display:flex; align-items:flex-start; gap:8px; margin-bottom:5px; }
.adc-detail i { color:var(--green); margin-top:3px; width:14px; flex-shrink:0; }
.adc-actions { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid var(--gray-200); }
</style>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
