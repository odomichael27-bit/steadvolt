<?php
// ============================================================
//  STEADVOLT — Admin Chatbot Q&A Manager
//  File: admin/chatbot.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Chatbot Replies';
$action     = get_param('action');
$edit_id    = (int)get_param('id');

// ---- SAVE --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/chatbot.php'); }

    $p_action  = post('p_action');
    $row_id    = (int)post('row_id');
    $keywords  = trim(post('keywords'));
    $response  = trim(post('response'));
    $priority  = (int)post('priority', 5);
    $is_active = (int)post('is_active', 1);

    if (!$keywords || !$response) {
        flash('admin', 'Keywords and response are required.', 'error');
        redirect('/admin/chatbot.php?action=' . ($row_id ? 'edit&id='.$row_id : 'add'));
    }

    if ($p_action === 'add') {
        DB::query(
            "INSERT INTO chatbot_responses (keywords, response, priority, is_active) VALUES (?,?,?,?)",
            [$keywords, $response, $priority, $is_active]
        );
        flash('admin', 'Chatbot reply added!', 'success');
    } else {
        DB::query(
            "UPDATE chatbot_responses SET keywords=?, response=?, priority=?, is_active=? WHERE id=?",
            [$keywords, $response, $priority, $is_active, $row_id]
        );
        flash('admin', 'Reply updated.', 'success');
    }
    redirect('/admin/chatbot.php');
}

// ---- DELETE ------------------------------------------------
if ($action === 'delete' && $edit_id) {
    DB::query("DELETE FROM chatbot_responses WHERE id=?", [$edit_id]);
    flash('admin', 'Reply deleted.', 'success');
    redirect('/admin/chatbot.php');
}

// ---- TOGGLE ACTIVE -----------------------------------------
if ($action === 'toggle' && $edit_id) {
    DB::query("UPDATE chatbot_responses SET is_active = 1-is_active WHERE id=?", [$edit_id]);
    redirect('/admin/chatbot.php');
}

$edit_row = null;
if (($action === 'edit' || $action === 'add') && ($edit_id || $action === 'add')) {
    if ($edit_id) $edit_row = DB::row("SELECT * FROM chatbot_responses WHERE id=?", [$edit_id]);
}

$responses = DB::all("SELECT * FROM chatbot_responses ORDER BY priority DESC, id ASC");

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <div>
      <h1>Chatbot Replies</h1>
      <p>Manage the keyword-triggered replies for the built-in customer chatbot.</p>
    </div>
    <a href="?action=add" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Reply</a>
  </div>

  <?= flash_html('admin') ?>

  <!-- HOW IT WORKS -->
  <div class="admin-info-box">
    <i class="fas fa-info-circle"></i>
    <div>
      <strong>How the chatbot works:</strong> When a customer sends a message, the chatbot scans their message for
      any <em>keyword</em> from your list. The first match (highest priority wins) gets its response sent back.
      Keywords are <strong>comma-separated</strong> and case-insensitive. You can use basic HTML in responses (links, bold, line breaks).
    </div>
  </div>

  <?php if ($action === 'add' || ($action === 'edit' && $edit_row !== null)): ?>
  <!-- FORM -->
  <div class="admin-card" style="max-width:760px">
    <h2><?= $action === 'add' ? 'Add New Reply' : 'Edit Reply' ?></h2>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="p_action" value="<?= $action === 'add' ? 'add' : 'update' ?>"/>
      <?php if ($edit_row): ?><input type="hidden" name="row_id" value="<?= $edit_row['id'] ?>"/><?php endif; ?>

      <div class="form-group">
        <label>Trigger Keywords <span class="text-danger">*</span></label>
        <div class="input-wrap"><i class="fas fa-key input-icon"></i>
          <input type="text" name="keywords" required
                 value="<?= clean($edit_row['keywords'] ?? '') ?>"
                 placeholder="hello, hi, hey, good morning"/>
        </div>
        <p class="hint">Comma-separated. Example: <code>solar, solar panel, panel</code></p>
      </div>

      <div class="form-group">
        <label>Response <span class="text-danger">*</span></label>
        <textarea name="response" rows="6" required
                  placeholder="Hi! Welcome to SteadVolt Energy 👋 …"><?= clean($edit_row['response'] ?? '') ?></textarea>
        <p class="hint">HTML allowed: <code>&lt;a href=&quot;/shop.php&quot;&gt;Shop&lt;/a&gt;</code>, <code>&lt;strong&gt;</code>, <code>&lt;br&gt;</code></p>
      </div>

      <div class="form-row-2">
        <div class="form-group">
          <label>Priority (higher = matched first)</label>
          <div class="input-wrap"><input type="number" name="priority" min="0" max="100" value="<?= $edit_row['priority'] ?? 5 ?>"/></div>
        </div>
        <div class="form-group" style="margin-top:28px">
          <label class="toggle-label">
            <input type="checkbox" name="is_active" value="1" <?= !isset($edit_row) || $edit_row['is_active'] ? 'checked' : '' ?>/> Active
          </label>
        </div>
      </div>

      <div style="display:flex;gap:12px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Reply</button>
        <a href="<?= BASE_URL ?>/admin/chatbot.php" class="btn btn-outline">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- LIST -->
  <div class="admin-card">
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Priority</th><th>Keywords</th><th>Response Preview</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($responses as $r): ?>
          <tr class="<?= !$r['is_active'] ? 'row-inactive' : '' ?>">
            <td class="text-center"><strong><?= $r['priority'] ?></strong></td>
            <td>
              <?php foreach (explode(',', $r['keywords']) as $kw): ?>
              <span class="badge badge-keyword"><?= clean(trim($kw)) ?></span>
              <?php endforeach; ?>
            </td>
            <td class="td-preview"><?= clean(substr(strip_tags($r['response']), 0, 100)) ?>…</td>
            <td>
              <a href="?action=toggle&id=<?= $r['id'] ?>" title="Toggle active">
                <?php if ($r['is_active']): ?>
                <span class="status-pill status-active">Active</span>
                <?php else: ?>
                <span class="status-pill status-inactive">Inactive</span>
                <?php endif; ?>
              </a>
            </td>
            <td class="td-actions">
              <a href="?action=edit&id=<?= $r['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-edit"></i></a>
              <a href="?action=delete&id=<?= $r['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this reply?')"><i class="fas fa-trash"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($responses)): ?>
          <tr><td colspan="5" class="text-center text-muted" style="padding:32px">No chatbot replies configured yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
