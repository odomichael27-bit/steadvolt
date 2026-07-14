<?php
// ============================================================
//  STEADVOLT — Profile Settings
//  File: pages/profile-settings.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_login('/pages/login.php?redirect=/pages/profile-settings.php');

$user_id   = auth_user()['id'];
$user      = DB::row("SELECT * FROM users WHERE id=?", [$user_id]);
$page_title = 'Account Settings';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('settings', 'Invalid request.', 'error'); redirect('/pages/profile-settings.php'); }

    $action = post('action');

    if ($action === 'update_profile') {
        $first  = ucfirst(strtolower(trim(post('first_name'))));
        $last   = ucfirst(strtolower(trim(post('last_name'))));
        $phone  = trim(post('phone'));

        if (!$first || !$last) {
            flash('settings', 'Name cannot be empty.', 'error');
            redirect('/pages/profile-settings.php');
        }

        // Handle avatar upload
        $avatar = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $file     = $_FILES['avatar'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $allowed)) {
                flash('settings', 'Avatar must be JPG, PNG, or WEBP.', 'error');
                redirect('/pages/profile-settings.php');
            }
            if ($file['size'] > 2 * 1024 * 1024) {
                flash('settings', 'Avatar file too large (max 2MB).', 'error');
                redirect('/pages/profile-settings.php');
            }
            $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $dest     = UPLOADS_PATH . '/avatars/' . $new_name;
            if (!is_dir(UPLOADS_PATH . '/avatars')) mkdir(UPLOADS_PATH . '/avatars', 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // Delete old avatar
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

        // Refresh session
        $updated = DB::row("SELECT * FROM users WHERE id=?", [$user_id]);
        login_user($updated);

        flash('settings', 'Profile updated successfully!', 'success');
        redirect('/pages/profile-settings.php');
    }

    if ($action === 'change_password') {
        $current = post('current_password');
        $new_pw  = post('new_password');
        $confirm = post('confirm_password');

        if (!password_verify($current, $user['password_hash'])) {
            flash('settings', 'Current password is incorrect.', 'error');
            redirect('/pages/profile-settings.php');
        }
        if (strlen($new_pw) < 8) {
            flash('settings', 'New password must be at least 8 characters.', 'error');
            redirect('/pages/profile-settings.php');
        }
        if ($new_pw !== $confirm) {
            flash('settings', 'New passwords do not match.', 'error');
            redirect('/pages/profile-settings.php');
        }

        $hash = password_hash($new_pw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        DB::query("UPDATE users SET password_hash=? WHERE id=?", [$hash, $user_id]);
        flash('settings', 'Password updated successfully!', 'success');
        redirect('/pages/profile-settings.php');
    }
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="profile-page">
  <div class="container">
    <div class="profile-layout">
      <?php include dirname(__DIR__) . '/includes/profile-nav.php'; ?>

      <main class="profile-main">
        <?= flash_html('settings') ?>

        <!-- UPDATE PROFILE -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-user-edit"></i> Personal Information</h2>
          </div>

          <form method="POST" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile"/>

            <!-- Avatar -->
            <div class="avatar-upload-section">
              <div class="avatar-preview">
                <?php if ($user['avatar']): ?>
                  <img src="<?= avatar_url($user['avatar']) ?>" id="avatarPreview" alt="Avatar"/>
                <?php else: ?>
                  <div class="avatar-initials" id="avatarInitials">
                    <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="avatar-upload-controls">
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
                <div class="input-wrap">
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" name="first_name" value="<?= clean($user['first_name']) ?>" required maxlength="80"/>
                </div>
              </div>
              <div class="form-group">
                <label>Last Name *</label>
                <div class="input-wrap">
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" name="last_name" value="<?= clean($user['last_name']) ?>" required maxlength="80"/>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Email Address</label>
              <div class="input-wrap">
                <i class="fas fa-envelope input-icon"></i>
                <input type="email" value="<?= clean($user['email']) ?>" disabled class="input-disabled"/>
              </div>
              <p class="hint"><i class="fas fa-info-circle"></i> Email cannot be changed. Contact support if needed.</p>
            </div>

            <div class="form-group">
              <label>Phone Number</label>
              <div class="input-wrap">
                <i class="fas fa-phone input-icon"></i>
                <input type="tel" name="phone" value="<?= clean($user['phone'] ?? '') ?>" maxlength="20" placeholder="+234 800 0000000"/>
              </div>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
          </form>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-lock"></i> Change Password</h2>
          </div>
          <form method="POST" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password"/>
            <div class="form-group">
              <label>Current Password *</label>
              <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="current_password" required/>
                <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
              </div>
            </div>
            <div class="form-group">
              <label>New Password *</label>
              <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="new_password" id="newPw" required minlength="8"/>
                <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
              </div>
              <div class="pw-strength-bar"><div id="pwBar"></div></div>
            </div>
            <div class="form-group">
              <label>Confirm New Password *</label>
              <div class="input-wrap">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="confirm_password" required/>
                <button type="button" class="toggle-pw"><i class="fas fa-eye"></i></button>
              </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
          </form>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
