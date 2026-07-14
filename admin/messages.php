<?php
// ============================================================
//  STEADVOLT — Admin Contact Messages
//  File: admin/messages.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Messages';
$view_id    = (int)get_param('view');
$action     = get_param('action');
$id         = (int)get_param('id');

if ($action === 'delete' && $id) {
    DB::query("DELETE FROM contact_messages WHERE id=?", [$id]);
    flash('admin', 'Message deleted.', 'success');
    redirect('/admin/messages.php');
}
if ($action === 'read' && $id) {
    DB::query("UPDATE contact_messages SET is_read=1 WHERE id=?", [$id]);
    redirect('/admin/messages.php?view=' . $id);
}

// Mark as read when viewing
if ($view_id) {
    DB::query("UPDATE contact_messages SET is_read=1 WHERE id=?", [$view_id]);
    $message = DB::row("SELECT * FROM contact_messages WHERE id=?", [$view_id]);
    if (!$message) { flash('admin','Message not found.','error'); redirect('/admin/messages.php'); }
}

$page_num = max(1,(int)get_param('page',1));
$per_page = 20;
$filter   = get_param('filter','all');
$where    = $filter === 'unread' ? 'is_read=0' : '1=1';
$total    = (int)DB::val("SELECT COUNT(*) FROM contact_messages WHERE $where");
$pager    = paginate($total, $per_page, $page_num);
$messages = DB::all("SELECT * FROM contact_messages WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET {$pager['offset']}");
$unread   = (int)DB::val("SELECT COUNT(*) FROM contact_messages WHERE is_read=0");

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

<?php if ($view_id && isset($message)): ?>
<div class="admin-page-header">
  <div>
    <h1>Message from <?= clean($message['name']) ?></h1>
    <p><?= date('D, M j Y \a\t H:i', strtotime($message['created_at'])) ?></p>
  </div>
  <div style="display:flex;gap:8px">
    <a href="mailto:<?= clean($message['email']) ?>?subject=Re: <?= urlencode($message['subject'] ?: 'Your Enquiry') ?>" class="btn btn-primary btn-sm"><i class="fas fa-reply"></i> Reply via Email</a>
    <?php if ($message['phone']): ?>
    <a href="https://wa.me/<?= preg_replace('/\D/', '', $message['phone']) ?>" target="_blank" class="btn btn-whatsapp btn-sm"><i class="fab fa-whatsapp"></i> WhatsApp</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/admin/messages.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</div>

<div class="admin-card" style="max-width:760px">
  <table style="width:100%;border-collapse:collapse;font-size:.9rem;margin-bottom:20px">
    <tr><td style="padding:8px 0;width:120px;color:#6b7280"><strong>From</strong></td><td><?= clean($message['name']) ?></td></tr>
    <tr><td style="padding:8px 0;color:#6b7280"><strong>Email</strong></td><td><a href="mailto:<?= clean($message['email']) ?>"><?= clean($message['email']) ?></a></td></tr>
    <tr><td style="padding:8px 0;color:#6b7280"><strong>Phone</strong></td><td><?= clean($message['phone'] ?? '—') ?></td></tr>
    <tr><td style="padding:8px 0;color:#6b7280"><strong>Subject</strong></td><td><?= clean($message['subject'] ?? '(No subject)') ?></td></tr>
  </table>
  <div style="background:#f9fafb;border-radius:10px;padding:20px;font-size:.92rem;line-height:1.75;color:#374151">
    <?= nl2br(clean($message['message'])) ?>
  </div>
  <div style="margin-top:16px;padding-top:16px;border-top:1px solid #f3f4f6">
    <a href="?action=delete&id=<?= $message['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i> Delete</a>
  </div>
</div>

<?php else: ?>
<div class="admin-page-header">
  <h1>Messages <?php if ($unread > 0): ?><span style="background:#EF4444;color:#fff;font-size:.7rem;padding:3px 8px;border-radius:99px;font-weight:700;margin-left:8px"><?= $unread ?> new</span><?php endif; ?></h1>
</div>

<div class="admin-status-tabs">
  <a href="?" class="<?= $filter==='all'?'active':'' ?>">All <span><?= $total ?></span></a>
  <a href="?filter=unread" class="<?= $filter==='unread'?'active':'' ?>">Unread <span><?= $unread ?></span></a>
</div>

<div class="admin-card">
  <?php if (empty($messages)): ?>
  <div class="empty-state-sm"><i class="fas fa-envelope" style="font-size:2rem;color:#D1D5DB;display:block;margin-bottom:8px"></i>No messages found.</div>
  <?php else: ?>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th></th><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($messages as $msg): ?>
    <tr style="<?= !$msg['is_read'] ? 'font-weight:600;background:#fafffe' : '' ?>">
      <td><?= !$msg['is_read'] ? '<span style="width:8px;height:8px;background:#00A86B;border-radius:50%;display:inline-block"></span>' : '' ?></td>
      <td><?= clean($msg['name']) ?></td>
      <td><?= clean($msg['email']) ?></td>
      <td><?= clean($msg['subject'] ?: '(No subject)') ?></td>
      <td><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></td>
      <td style="white-space:nowrap">
        <a href="?view=<?= $msg['id'] ?>" class="btn btn-outline btn-xs"><i class="fas fa-eye"></i> View</a>
        <a href="?action=delete&id=<?= $msg['id'] ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pager['total_pages'] > 1): ?>
  <nav class="pagination pagination-admin">
    <?php for ($i=1;$i<=$pager['total_pages'];$i++): ?>
    <a href="?filter=<?=$filter?>&page=<?=$i?>" class="page-btn <?=$i===$pager['current']?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
