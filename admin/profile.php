<?php
// ============================================================
//  STEADVOLT — Admin's Own Profile
//  File: admin/profile.php
//  Lets the logged-in admin update their name, phone, profile
//  picture and password — mirrors pages/profile-settings.php
//  but for the admin account, inside the admin layout.
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'My Profile';
$user_id    = auth_user()['id'];
$user       = DB::row("SELECT * FROM users WHERE id=?", [$user_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/profile.php'); }

    $form_action = post('form_action');

    if ($form_action === 'update_profile') {
        $first = ucfirst(strtolower(trim(post('first_name'))));
        $last  = ucfirst(strtolower(trim(post('last_name'))));
        $phone = trim(post('phone'));

        if (!$first || !$last) {
            flash('admin', 'Name cannot be empty.', 'error');
            redirect('/admin/profile.php');
        }

        // Handle avatar upload
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $file    = $_FILES['avatar'];
            $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (!in_array($ext, $allowed)) {
                flash('admin', 'Avatar must be JPG, PNG, or WEBP.', 'error');
                redirect('/admin/profile.php');
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('admin', 'Avatar file too large (max 2MB).', 'error');
                redirect('/admin/profile.php');
            }
            $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest     = UPLOADS_PATH . '/avatars/' . $new_name;
            if (!is_dir(UPLOADS_PATH . '/avatars')) mkdir(UPLOADS_PATH . '/avatars', 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if ($avatar && file_exists(UPLOADS_PATH . '/avatars/' . $avatar)) {
                    @unlink(UPLOADS_PATH . '/avatars/' . $avatar);
                }
                $avatar = $new_name;
            }
        }

        DB::query(
            "UPDATE users SET first_name=?, last_name=?, phone=?, avatar=? WHERE id=?",
            [$first, $last, $phone, $avatar, $user_id]
        );

        // Refresh session so the new name/avatar show immediately
        $updated = DB::row("SELECT * FROM users WHERE id=?", [$user_id]);
        login_user($updated);

        flash('admin', 'Profile updated successfully!', 'success');
        redirect('/admin/profile.php');
    }

    if ($form_action === 'change_password') {
        $current = post('current_password');
        $new_pw  = post('new_password');
        $confirm = post('confirm_password');

        if (!password_verify($current, $user['password_hash'])) {
            flash('admin', 'Current password is incorrect.', 'error');
            redirect('/admin/profile.php');
        }
        if (strlen($new_pw) < 8) {
            flash('admin', 'New password must be at least 8 characters.', 'error');
            redirect('/admin/profile.php');
        }
        if ($new_pw !== $confirm) {
            flash('admin', 'New passwords do not match.', 'error');
            redirect('/admin/profile.php');
        }

        $hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        DB::query("UPDATE users SET password_hash=? WHERE id=?", [$hash, $user_id]);
        flash('admin', 'Password updated successfully!', 'success');
        redirect('/admin/profile.php');
    }
}

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <h1>My Profile</h1>
  </div>

  <?= flash_html('admin') ?>

  <div class="admin-card">
    <h3><i class="fas fa-user-edit"></i> Personal Information</h3>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="update_profile"/>

      <div class="admin-profile-avatar-row">
        <?php if ($user['avatar']): ?>
          <img src="<?= avatar_url($user['avatar']) ?>" id="avatarPreview" alt="Avatar"/>
        <?php else: ?>
          <div class="avatar-initials" id="avatarInitials">
            <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div>
          <label for="avatarInput" class="btn btn-outline btn-sm">
            <i class="fas fa-camera"></i> Change Photo
          </label>
          <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden"/>
          <p class="hint">JPG, PNG or WEBP. Max 2MB.</p>
        </div>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>First Name *</label>
          <div class="input-wrap"><i class="fas fa-user input-icon"></i>
            <input type="text" name="first_name" value="<?= clean($user['first_name']) ?>" required maxlength="80"/>
          </div>
        </div>
        <div class="form-group">
          <label>Last Name *</label>
          <div class="input-wrap"><i class="fas fa-user input-icon"></i>
            <input type="text" name="last_name" value="<?= clean($user['last_name']) ?>" required maxlength="80"/>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label>Email Address</label>
        <div class="input-wrap"><i class="fas fa-envelope input-icon"></i>
          <input type="email" value="<?= clean($user['email']) ?>" disabled class="input-disabled"/>
        </div>
        <p class="hint"><i class="fas fa-info-circle"></i> Email cannot be changed here. Update it directly in the database if needed.</p>
      </div>

      <div class="form-group">
        <label>Phone Number</label>
        <div class="input-wrap"><i class="fas fa-phone input-icon"></i>
          <input type="tel" name="phone" value="<?= clean($user['phone'] ?? '') ?>" maxlength="20" placeholder="+234 800 0000000"/>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
    </form>
  </div>

  <div class="admin-card">
    <h3><i class="fas fa-lock"></i> Change Password</h3>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="change_password"/>
      <div class="form-group">
        <label>Current Password *</label>
        <div class="input-wrap"><i class="fas fa-lock input-icon"></i>
          <input type="password" name="current_password" required/>
        </div>
      </div>
      <div class="form-group">
        <label>New Password *</label>
        <div class="input-wrap"><i class="fas fa-lock input-icon"></i>
          <input type="password" name="new_password" required minlength="8"/>
        </div>
      </div>
      <div class="form-group">
        <label>Confirm New Password *</label>
        <div class="input-wrap"><i class="fas fa-lock input-icon"></i>
          <input type="password" name="confirm_password" required/>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
    </form>
  </div>
</div>

<script>
document.getElementById('avatarInput')?.addEventListener('change', function () {
  if (!this.files || !this.files[0]) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    let img = document.getElementById('avatarPreview');
    if (!img) {
      const placeholder = document.getElementById('avatarInitials');
      img = document.createElement('img');
      img.id = 'avatarPreview';
      placeholder.replaceWith(img);
    }
    img.src = e.target.result;
  };
  reader.readAsDataURL(this.files[0]);
});
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
