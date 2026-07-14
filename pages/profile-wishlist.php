<?php
// ============================================================
//  STEADVOLT — Profile Wishlist
//  File: pages/profile-wishlist.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
require_login('/pages/login.php?redirect=/pages/profile-wishlist.php');

$user_id    = auth_user()['id'];
$page_title = 'My Wishlist';

$wishlist = DB::all(
    "SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.stock,
            pi.filename AS image, c.name AS category
     FROM wishlist w
     JOIN products p ON p.id = w.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
     WHERE w.user_id = ? AND p.is_active = 1
     ORDER BY w.added_at DESC",
    [$user_id]
);

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="profile-page">
  <div class="container">
    <div class="profile-layout">
      <?php include dirname(__DIR__) . '/includes/profile-nav.php'; ?>

      <main class="profile-main">
        <div class="profile-card">
          <div class="profile-card-header">
            <h2><i class="fas fa-heart"></i> My Wishlist (<?= count($wishlist) ?>)</h2>
            <?php if (!empty($wishlist)): ?>
            <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline btn-sm">Continue Shopping</a>
            <?php endif; ?>
          </div>

          <?php if (empty($wishlist)): ?>
          <div class="empty-state small">
            <i class="far fa-heart"></i>
            <h3>Your wishlist is empty</h3>
            <p>Browse products and click the heart icon to save items for later.</p>
            <a href="<?= BASE_URL ?>/shop.php" class="btn btn-primary"><i class="fas fa-store"></i> Browse Products</a>
          </div>
          <?php else: ?>
          <div class="products-grid">
            <?php foreach ($wishlist as $p): ?>
            <div class="product-card" id="wishItem<?= $p['id'] ?>">
              <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>" class="product-img-wrap">
                <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                  <?= discount_pct($p['compare_price'], $p['price']) ?>
                <?php endif; ?>
                <?php if ($p['image']): ?>
                  <img src="<?= product_img_url($p['image']) ?>" alt="<?= clean($p['name']) ?>" loading="lazy"/>
                <?php else: ?>
                  <div class="product-img-placeholder"><i class="fas fa-box-open"></i></div>
                <?php endif; ?>
              </a>
              <div class="product-info">
                <span class="product-brand"><?= clean($p['category'] ?? '') ?></span>
                <h3 class="product-name">
                  <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>"><?= clean($p['name']) ?></a>
                </h3>
                <div class="product-price-row">
                  <div class="product-prices">
                    <span class="price-current"><?= money($p['price']) ?></span>
                    <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                    <span class="price-old"><?= money($p['compare_price']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px">
                  <?php if ($p['stock'] > 0): ?>
                  <button class="btn btn-primary btn-sm add-cart-btn" data-id="<?= $p['id'] ?>" style="flex:1;justify-content:center">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                  </button>
                  <?php else: ?>
                  <span class="btn btn-outline btn-sm" style="flex:1;justify-content:center;opacity:.5;cursor:not-allowed">Out of Stock</span>
                  <?php endif; ?>
                  <button class="btn btn-outline btn-sm wishlist-btn in-wishlist" data-id="<?= $p['id'] ?>" title="Remove from wishlist">
                    <i class="fas fa-heart text-danger"></i>
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
