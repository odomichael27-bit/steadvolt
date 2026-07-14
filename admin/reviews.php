<?php
// ============================================================
//  STEADVOLT — Admin Reviews Manager
//  File: admin/reviews.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();

$page_title = 'Reviews';
$action     = get_param('action');
$id         = (int)get_param('id');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('form_action') === 'add_staff_review') {
    if (!csrf_verify()) { flash('admin', 'Invalid request.', 'error'); redirect('/admin/reviews.php'); }

    $rp_id   = (int)post('product_id');
    $rating  = (int)post('rating');
    $title   = trim(post('title'));
    $body    = trim(post('body'));
    $author  = trim(post('author_name')) ?: 'SteadVolt Team';

    if (!$rp_id || $rating < 1 || $rating > 5 || !$body) {
        flash('admin', 'Please choose a product, a rating, and write the review text.', 'error');
        redirect('/admin/reviews.php?filter=staff');
    }

    $product_exists = DB::val("SELECT id FROM products WHERE id=?", [$rp_id]);
    if (!$product_exists) {
        flash('admin', 'Product not found.', 'error');
        redirect('/admin/reviews.php?filter=staff');
    }

    // Staff-written reviews are auto-approved and clearly flagged as is_staff=1
    // so the storefront can label them "SteadVolt Team" rather than presenting
    // them as an ordinary customer submission.
    DB::query(
        "INSERT INTO reviews (product_id, user_id, guest_name, rating, title, body, is_approved, is_staff)
         VALUES (?,NULL,?,?,?,?,1,1)",
        [$rp_id, $author, $rating, $title, $body]
    );

    flash('admin', 'Staff review added and published.', 'success');
    redirect('/admin/reviews.php?filter=staff');
}

if ($action === 'approve' && $id) {
    DB::query("UPDATE reviews SET is_approved=1 WHERE id=?", [$id]);
    flash('admin', 'Review approved.', 'success');
    redirect('/admin/reviews.php');
}
if ($action === 'reject' && $id) {
    DB::query("DELETE FROM reviews WHERE id=?", [$id]);
    flash('admin', 'Review deleted.', 'success');
    redirect('/admin/reviews.php');
}

$filter    = get_param('filter', 'pending');
$page_num  = max(1, (int)get_param('page', 1));
$per_page  = 20;
$where     = match($filter) {
    'pending'  => 'r.is_approved=0',
    'approved' => 'r.is_approved=1',
    'staff'    => 'r.is_staff=1',
    default    => '1=1',
};

$total   = (int)DB::val("SELECT COUNT(*) FROM reviews r WHERE {$where}");
$pager   = paginate($total, $per_page, $page_num);
$reviews = DB::all(
    "SELECT r.*, p.name AS product_name, p.slug AS product_slug,
            u.first_name, u.last_name, u.email AS user_email
     FROM reviews r
     LEFT JOIN products p ON p.id = r.product_id
     LEFT JOIN users u ON u.id = r.user_id
     WHERE {$where}
     ORDER BY r.created_at DESC
     LIMIT {$per_page} OFFSET {$pager['offset']}"
);

$counts = [
    'pending'  => DB::val("SELECT COUNT(*) FROM reviews WHERE is_approved=0"),
    'approved' => DB::val("SELECT COUNT(*) FROM reviews WHERE is_approved=1"),
    'staff'    => DB::val("SELECT COUNT(*) FROM reviews WHERE is_staff=1"),
];

$all_products = DB::all("SELECT id, name FROM products ORDER BY name");

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">
  <div class="admin-page-header">
    <h1>Product Reviews</h1>
  </div>

  <?= flash_html('admin') ?>

  <!-- Add a staff-written review -->
  <div class="admin-card">
    <h3><i class="fas fa-pen"></i> Add a Staff Review</h3>
    <p class="hint">
      Write a review yourself for a product (e.g. based on internal testing, a customer's verbal feedback, or a
      testimonial received by phone/email). It publishes immediately and is always labeled
      <span class="staff-review-badge"><i class="fas fa-shield-alt"></i> SteadVolt Team</span> on the site so
      customers can tell it wasn't submitted by another shopper.
    </p>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="add_staff_review"/>
      <div class="form-row-2">
        <div class="form-group">
          <label>Product *</label>
          <select name="product_id" required>
            <option value="">— Select a product —</option>
            <?php foreach ($all_products as $ap): ?>
            <option value="<?= $ap['id'] ?>"><?= clean($ap['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Display Name</label>
          <div class="input-wrap"><input type="text" name="author_name" placeholder="SteadVolt Team" maxlength="100"/></div>
          <p class="hint">Shown as the reviewer name. Defaults to "SteadVolt Team".</p>
        </div>
      </div>
      <div class="form-group">
        <label>Rating *</label>
        <div class="admin-star-picker" id="staffStarPicker">
          <?php for ($i = 1; $i <= 5; $i++): ?>
          <i class="far fa-star" data-val="<?= $i ?>"></i>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="staffRatingInput" required/>
      </div>
      <div class="form-group">
        <label>Title (optional)</label>
        <div class="input-wrap"><input type="text" name="title" maxlength="200" placeholder="Summarize the review"/></div>
      </div>
      <div class="form-group">
        <label>Review Text *</label>
        <textarea name="body" rows="4" maxlength="2000" required placeholder="Write the review…"></textarea>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Publish Review</button>
    </form>
  </div>

  <div class="admin-status-tabs">
    <a href="?filter=pending"  class="<?= $filter==='pending' ?'active':'' ?>">Pending <span><?= $counts['pending'] ?></span></a>
    <a href="?filter=approved" class="<?= $filter==='approved'?'active':'' ?>">Approved <span><?= $counts['approved'] ?></span></a>
    <a href="?filter=staff"    class="<?= $filter==='staff'   ?'active':'' ?>">Staff <span><?= $counts['staff'] ?></span></a>
    <a href="?filter=all"      class="<?= $filter==='all'     ?'active':'' ?>">All</a>
  </div>

  <div class="admin-card">
    <?php if (empty($reviews)): ?>
    <div class="empty-state small"><i class="fas fa-star"></i><p>No reviews in this category.</p></div>
    <?php else: ?>
    <?php foreach ($reviews as $rev): ?>
    <div class="review-admin-item <?= !$rev['is_approved'] ? 'review-pending' : '' ?>">
      <div class="review-admin-header">
        <div>
          <strong><?= clean($rev['first_name'] ? $rev['first_name'].' '.$rev['last_name'] : ($rev['guest_name'] ?? 'Guest')) ?></strong>
          <?php if ($rev['is_staff']): ?>
            <span class="staff-review-badge"><i class="fas fa-shield-alt"></i> SteadVolt Team</span>
          <?php endif; ?>
          <?php if ($rev['user_email']): ?>
            <small class="text-muted"> — <?= clean($rev['user_email']) ?></small>
          <?php endif; ?>
          <div><?= star_rating($rev['rating'], false) ?> &bull; <?= date('M j, Y', strtotime($rev['created_at'])) ?></div>
          <div><i class="fas fa-box"></i>
            <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($rev['product_slug'] ?? '') ?>" target="_blank">
              <?= clean($rev['product_name'] ?? '') ?>
            </a>
          </div>
        </div>
        <div class="review-admin-actions">
          <?php if (!$rev['is_approved']): ?>
          <a href="?action=approve&id=<?= $rev['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Approve</a>
          <?php else: ?>
          <span class="status-pill status-active">Approved</span>
          <?php endif; ?>
          <a href="?action=reject&id=<?= $rev['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this review?')"><i class="fas fa-trash"></i> Delete</a>
        </div>
      </div>
      <?php if ($rev['title']): ?><p class="review-title-admin"><strong><?= clean($rev['title']) ?></strong></p><?php endif; ?>
      <p class="review-body-admin"><?= clean($rev['body']) ?></p>
    </div>
    <?php endforeach; ?>
    <?php if ($pager['total_pages'] > 1): ?>
    <nav class="pagination pagination-admin">
      <?php for ($i=1;$i<=$pager['total_pages'];$i++): ?>
      <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="page-btn <?= $i===$pager['current']?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
// Staff review star picker
(function () {
  const picker = document.getElementById('staffStarPicker');
  const input  = document.getElementById('staffRatingInput');
  if (!picker || !input) return;
  const stars = picker.querySelectorAll('i');
  picker.addEventListener('click', (e) => {
    const star = e.target.closest('i');
    if (!star) return;
    const val = parseInt(star.dataset.val, 10);
    input.value = val;
    stars.forEach(s => {
      const active = parseInt(s.dataset.val, 10) <= val;
      s.classList.toggle('fas', active);
      s.classList.toggle('far', !active);
      s.classList.toggle('active', active);
    });
  });
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
