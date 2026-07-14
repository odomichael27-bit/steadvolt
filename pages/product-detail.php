<?php
// ============================================================
//  STEADVOLT — Product Detail Page
//  File: pages/product-detail.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';

$slug = get_param('slug');
if (!$slug) redirect('/shop.php');

$product = DB::row(
    "SELECT p.*, c.name AS category, c.slug AS cat_slug, b.name AS brand
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN brands b ON b.id = p.brand_id
     WHERE p.slug = ? AND p.is_active = 1",
    [$slug]
);
if (!$product) redirect('/shop.php');

$page_title = $product['name'];
$meta_desc  = $product['meta_desc'] ?: $product['short_desc'];

// Images
$images = DB::all("SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, sort_order ASC", [$product['id']]);

// Specs
$specs = DB::all("SELECT * FROM product_specs WHERE product_id=? ORDER BY sort_order ASC", [$product['id']]);

// Reviews
$reviews = DB::all(
    "SELECT r.*, u.first_name, u.last_name, u.avatar
     FROM reviews r
     LEFT JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ? AND r.is_approved = 1
     ORDER BY r.created_at DESC",
    [$product['id']]
);
$avg_rating          = count($reviews) ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$review_display_count = count($reviews);

// Admin manual rating override — takes precedence over the real review average
if ($product['rating_override'] !== null) {
    $avg_rating            = (float)$product['rating_override'];
    $review_display_count  = $product['rating_override_count'] !== null
        ? (int)$product['rating_override_count']
        : count($reviews);
}

// Related products
$related = DB::all(
    "SELECT p.id, p.name, p.slug, p.price, pi.filename AS image
     FROM products p
     LEFT JOIN product_images pi ON pi.product_id=p.id AND pi.is_primary=1
     WHERE p.category_id=? AND p.id != ? AND p.is_active=1
     LIMIT 4",
    [$product['category_id'], $product['id']]
);

// Wishlist check
$in_wishlist = false;
if (is_logged_in()) {
    $in_wishlist = (bool)DB::val(
        "SELECT id FROM wishlist WHERE user_id=? AND product_id=?",
        [auth_user()['id'], $product['id']]
    );
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="product-detail-page">
  <div class="container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
      <a href="<?= BASE_URL ?>/index.php"><i class="fas fa-home"></i></a>
      <i class="fas fa-chevron-right"></i>
      <a href="<?= BASE_URL ?>/shop.php">Shop</a>
      <i class="fas fa-chevron-right"></i>
      <?php if ($product['cat_slug']): ?>
      <a href="<?= BASE_URL ?>/shop.php?cat=<?= clean($product['cat_slug']) ?>"><?= clean($product['category']) ?></a>
      <i class="fas fa-chevron-right"></i>
      <?php endif; ?>
      <span><?= clean($product['name']) ?></span>
    </nav>

    <!-- PRODUCT MAIN -->
    <div class="product-detail-grid">

      <!-- Images -->
      <div class="product-gallery">
        <div class="gallery-main" id="galleryMain">
          <?php if (!empty($images)): ?>
          <img src="<?= product_img_url($images[0]['filename']) ?>" alt="<?= clean($product['name']) ?>" id="mainImage"/>
          <?php else: ?>
          <div class="product-img-placeholder large"><i class="fas fa-box-open"></i></div>
          <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($images as $img): ?>
          <img src="<?= product_img_url($img['filename']) ?>"
               alt="thumbnail"
               class="gallery-thumb <?= $img['is_primary'] ? 'active' : '' ?>"
               onclick="document.getElementById('mainImage').src=this.src; document.querySelectorAll('.gallery-thumb').forEach(t=>t.classList.remove('active')); this.classList.add('active')"/>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div class="product-detail-info">
        <span class="product-brand"><?= clean($product['brand'] ?? $product['category'] ?? '') ?></span>
        <h1 class="product-detail-name"><?= clean($product['name']) ?></h1>

        <!-- Rating -->
        <div class="product-rating-row">
          <?= star_rating($avg_rating) ?>
          <span class="rating-link"><?= $review_display_count ?> review<?= $review_display_count !== 1 ? 's' : '' ?></span>
          <?php if ($product['stock'] > 0): ?>
          <span class="stock-badge in-stock"><i class="fas fa-check-circle"></i> In Stock (<?= $product['stock'] ?> available)</span>
          <?php else: ?>
          <span class="stock-badge out-stock"><i class="fas fa-times-circle"></i> Out of Stock</span>
          <?php endif; ?>
        </div>

        <!-- Price -->
        <div class="product-detail-prices">
          <span class="detail-price-current"><?= money($product['price']) ?></span>
          <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
          <span class="detail-price-old"><?= money($product['compare_price']) ?></span>
          <?= discount_pct($product['compare_price'], $product['price']) ?>
          <?php endif; ?>
        </div>

        <!-- Short desc -->
        <?php if ($product['short_desc']): ?>
        <p class="product-short-desc"><?= clean($product['short_desc']) ?></p>
        <?php endif; ?>

        <!-- SKU -->
        <?php if ($product['sku']): ?>
        <p class="product-sku"><strong>SKU:</strong> <?= clean($product['sku']) ?></p>
        <?php endif; ?>

        <!-- Quantity & actions -->
        <?php if (is_admin()): ?>
        <div class="product-actions">
          <span class="admin-view-only-note"><i class="fas fa-eye"></i> You're viewing this as admin — shopping is disabled for admin accounts.</span>
        </div>
        <?php elseif ($product['stock'] > 0): ?>
        <div class="product-actions">
          <div class="qty-selector">
            <button class="qty-minus" aria-label="Decrease"><i class="fas fa-minus"></i></button>
            <input type="number" class="qty-val" value="1" min="1" max="<?= $product['stock'] ?>" id="productQty"/>
            <button class="qty-plus" aria-label="Increase"><i class="fas fa-plus"></i></button>
          </div>
          <button class="btn btn-primary add-cart-btn-detail" data-id="<?= $product['id'] ?>">
            <i class="fas fa-shopping-cart"></i> Add to Cart
          </button>
          <button class="btn btn-outline wishlist-btn <?= $in_wishlist ? 'in-wishlist' : '' ?>"
                  data-id="<?= $product['id'] ?>" title="<?= $in_wishlist ? 'Remove from wishlist' : 'Add to wishlist' ?>">
            <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i>
          </button>
        </div>
        <?php else: ?>
        <div class="product-actions">
          <button class="btn btn-outline" disabled><i class="fas fa-times-circle"></i> Out of Stock</button>
          <button class="btn btn-outline wishlist-btn <?= $in_wishlist ? 'in-wishlist' : '' ?>" data-id="<?= $product['id'] ?>">
            <i class="<?= $in_wishlist ? 'fas' : 'far' ?> fa-heart"></i> Wishlist
          </button>
        </div>
        <?php endif; ?>

        <!-- Trust badges -->
        <div class="product-trust-badges">
          <div class="trust-badge"><i class="fas fa-shield-alt"></i> Warranty Included</div>
          <div class="trust-badge"><i class="fas fa-truck"></i> Nationwide Delivery</div>
          <div class="trust-badge"><i class="fas fa-undo"></i> 30-Day Returns</div>
          <div class="trust-badge"><i class="fas fa-lock"></i> Secure Checkout</div>
        </div>

        <!-- Specs -->
        <?php if (!empty($specs)): ?>
        <div class="product-specs">
          <h3>Specifications</h3>
          <table class="specs-table">
            <?php foreach ($specs as $spec): ?>
            <tr>
              <th><?= clean($spec['spec_key']) ?></th>
              <td><?= clean($spec['spec_value']) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- TABS: Description / Reviews -->
    <div class="product-tabs-section">
      <div class="product-tab-btns">
        <button class="tab-btn active" data-tab="description">Description</button>
        <button class="tab-btn" data-tab="reviews">Reviews (<?= $review_display_count ?>)</button>
      </div>

      <!-- Description -->
      <div class="tab-panel active" id="tab-description">
        <?php if ($product['description']): ?>
          <div class="product-description"><?= $product['description'] /* Already HTML from admin */ ?></div>
        <?php else: ?>
          <p class="no-content">No description available for this product.</p>
        <?php endif; ?>
      </div>

      <!-- Reviews -->
      <div class="tab-panel" id="tab-reviews">
        <?= flash_html('review') ?>

        <!-- Review summary -->
        <?php if ($review_display_count > 0): ?>
        <div class="reviews-summary">
          <div class="reviews-avg">
            <span class="avg-num"><?= number_format($avg_rating, 1) ?></span>
            <?= star_rating($avg_rating, false) ?>
            <span><?= $review_display_count ?> reviews</span>
          </div>
        </div>
        <?php endif; ?>

        <!-- Review list -->
        <div class="reviews-list">
          <?php foreach ($reviews as $rev): ?>
          <div class="review-item">
            <div class="review-item-header">
              <?php if ($rev['avatar']): ?>
                <img src="<?= avatar_url($rev['avatar']) ?>" class="review-avatar" alt="avatar"/>
              <?php else: ?>
                <div class="review-avatar-placeholder"><?= strtoupper(substr($rev['first_name'] ?? $rev['guest_name'] ?? 'U', 0, 2)) ?></div>
              <?php endif; ?>
              <div>
                <strong><?= clean($rev['first_name'] ? $rev['first_name'] . ' ' . $rev['last_name'] : $rev['guest_name']) ?></strong>
                <?php if (!empty($rev['is_staff'])): ?>
                  <span class="staff-review-badge"><i class="fas fa-shield-alt"></i> SteadVolt Team</span>
                <?php endif; ?>
                <?= star_rating($rev['rating'], false) ?>
              </div>
              <span class="review-date"><?= time_ago($rev['created_at']) ?></span>
            </div>
            <?php if ($rev['title']): ?>
              <p class="review-title"><strong><?= clean($rev['title']) ?></strong></p>
            <?php endif; ?>
            <p class="review-body"><?= clean($rev['body']) ?></p>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Write review -->
        <div class="write-review">
          <h3>Write a Review</h3>
          <?php if (is_logged_in()): ?>
          <form method="POST" action="<?= BASE_URL ?>/api/reviews.php" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>"/>
            <input type="hidden" name="redirect" value="<?= clean($_SERVER['REQUEST_URI']) ?>"/>
            <div class="form-group">
              <label>Your Rating</label>
              <div class="star-picker" id="starPicker">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="far fa-star" data-val="<?= $i ?>"></i>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="ratingInput" required/>
            </div>
            <div class="form-group">
              <label>Review Title (optional)</label>
              <div class="input-wrap"><input type="text" name="title" placeholder="Summarize your experience" maxlength="200"/></div>
            </div>
            <div class="form-group">
              <label>Your Review</label>
              <textarea name="body" rows="4" placeholder="Tell others about your experience with this product…" required maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Review</button>
          </form>
          <?php else: ?>
          <p class="auth-note"><a href="<?= BASE_URL ?>/pages/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"><i class="fas fa-sign-in-alt"></i> Sign in</a> to write a review.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RELATED PRODUCTS -->
    <?php if (!empty($related)): ?>
    <section class="related-products">
      <h2 class="section-title">Related Products</h2>
      <div class="products-grid">
        <?php foreach ($related as $rp): ?>
        <div class="product-card">
          <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($rp['slug']) ?>" class="product-img-wrap">
            <?php if ($rp['image']): ?>
              <img src="<?= product_img_url($rp['image']) ?>" alt="<?= clean($rp['name']) ?>" loading="lazy"/>
            <?php else: ?>
              <div class="product-img-placeholder"><i class="fas fa-box-open"></i></div>
            <?php endif; ?>
          </a>
          <div class="product-info">
            <h3 class="product-name"><a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($rp['slug']) ?>"><?= clean($rp['name']) ?></a></h3>
            <div class="product-price-row">
              <span class="price-current"><?= money($rp['price']) ?></span>
              <?php if (!is_admin()): ?>
              <button class="add-cart-btn" data-id="<?= $rp['id'] ?>"><i class="fas fa-shopping-cart"></i></button>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
