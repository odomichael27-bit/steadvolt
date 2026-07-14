<?php
// ============================================================
//  STEADVOLT — Shop Page
//  File: shop.php
// ============================================================
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Shop All Products';
$meta_desc  = 'Browse all solar panels, batteries, cameras, and inverters at SteadVolt Energy.';

// Filters from GET
$cat_slug   = get_param('cat');
$brand_id   = (int)get_param('brand');
$min_price  = (float)get_param('min', 0);
$max_price  = (float)get_param('max', 9999999);
$sort       = get_param('sort', 'newest');
$search     = get_param('q');
$page_num   = max(1, (int)get_param('page', 1));
$per_page   = 12;

// Build WHERE
$where  = ['p.is_active = 1'];
$params = [];

if ($cat_slug) {
    $where[]  = 'c.slug = ?';
    $params[] = $cat_slug;
}
if ($brand_id) {
    $where[]  = 'p.brand_id = ?';
    $params[] = $brand_id;
}
if ($min_price > 0) {
    $where[]  = 'p.price >= ?';
    $params[] = $min_price;
}
if ($max_price < 9999999) {
    $where[]  = 'p.price <= ?';
    $params[] = $max_price;
}
if ($search) {
    $where[]  = '(p.name LIKE ? OR p.short_desc LIKE ? OR b.name LIKE ?)';
    $term     = '%' . $search . '%';
    $params[] = $term; $params[] = $term; $params[] = $term;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$order_sql = match($sort) {
    'price_asc'  => 'ORDER BY p.price ASC',
    'price_desc' => 'ORDER BY p.price DESC',
    'name'       => 'ORDER BY p.name ASC',
    'rating'     => 'ORDER BY avg_rating DESC',
    default      => 'ORDER BY p.is_featured DESC, p.created_at DESC',
};

// Count
$total = (int)DB::val(
    "SELECT COUNT(DISTINCT p.id)
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN brands b ON b.id = p.brand_id
     {$where_sql}",
    $params
);

$pager  = paginate($total, $per_page, $page_num);

// Products
$products = DB::all(
    "SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.stock, p.is_featured,
            c.name AS category, c.slug AS cat_slug,
            b.name AS brand,
            pi.filename AS image,
            CASE WHEN p.rating_override IS NOT NULL THEN p.rating_override ELSE COALESCE(AVG(r.rating),0) END AS avg_rating,
            CASE WHEN p.rating_override IS NOT NULL THEN COALESCE(p.rating_override_count, COUNT(DISTINCT r.id)) ELSE COUNT(DISTINCT r.id) END AS review_count
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN brands b ON b.id = p.brand_id
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
     LEFT JOIN reviews r ON r.product_id = p.id AND r.is_approved = 1
     {$where_sql}
     GROUP BY p.id
     {$order_sql}
     LIMIT {$per_page} OFFSET {$pager['offset']}",
    $params
);

$categories = DB::all("SELECT * FROM categories ORDER BY sort_order");
$brands     = DB::all("SELECT DISTINCT b.id, b.name FROM brands b JOIN products p ON p.brand_id=b.id WHERE p.is_active=1 ORDER BY b.name");
$current_cat= $cat_slug ? DB::row("SELECT * FROM categories WHERE slug=?", [$cat_slug]) : null;

require_once __DIR__ . '/includes/header.php';
?>

<div class="shop-page">
  <div class="container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
      <a href="<?= BASE_URL ?>/index.php"><i class="fas fa-home"></i></a>
      <i class="fas fa-chevron-right"></i>
      <span><?= $current_cat ? clean($current_cat['name']) : 'All Products' ?></span>
    </nav>

    <div class="shop-layout">
      <!-- SIDEBAR -->
      <aside class="shop-sidebar">
        <button class="sidebar-toggle-close"><i class="fas fa-times"></i></button>

        <!-- Search -->
        <div class="sidebar-section">
          <h4>Search</h4>
          <form method="GET" action="">
            <?php if ($cat_slug): ?><input type="hidden" name="cat" value="<?= clean($cat_slug) ?>"/><?php endif; ?>
            <div class="sidebar-search">
              <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Search products…"/>
              <button type="submit"><i class="fas fa-search"></i></button>
            </div>
          </form>
        </div>

        <!-- Categories -->
        <div class="sidebar-section">
          <h4>Categories</h4>
          <ul class="sidebar-list">
            <li><a href="<?= BASE_URL ?>/shop.php" class="<?= !$cat_slug ? 'active' : '' ?>">All Products</a></li>
            <?php foreach ($categories as $cat): ?>
            <li>
              <a href="<?= BASE_URL ?>/shop.php?cat=<?= clean($cat['slug']) ?>"
                 class="<?= $cat_slug === $cat['slug'] ? 'active' : '' ?>">
                <i class="fas <?= clean($cat['icon']) ?>"></i> <?= clean($cat['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Price Range -->
        <div class="sidebar-section">
          <h4>Price Range</h4>
          <form method="GET" action="" id="priceForm">
            <?php if ($cat_slug):  ?><input type="hidden" name="cat"   value="<?= clean($cat_slug) ?>"/><?php endif; ?>
            <?php if ($brand_id):  ?><input type="hidden" name="brand" value="<?= $brand_id ?>"/><?php endif; ?>
            <?php if ($search):    ?><input type="hidden" name="q"     value="<?= clean($search) ?>"/><?php endif; ?>
            <div class="price-range-inputs">
              <div class="input-wrap">
                <i class="fas fa-naira-sign input-icon"></i>
                <input type="number" name="min" value="<?= $min_price ?: '' ?>" placeholder="Min" min="0"/>
              </div>
              <span>–</span>
              <div class="input-wrap">
                <i class="fas fa-naira-sign input-icon"></i>
                <input type="number" name="max" value="<?= ($max_price < 9999999) ? $max_price : '' ?>" placeholder="Max" min="0"/>
              </div>
            </div>
            <button type="submit" class="btn btn-outline btn-sm btn-block">Apply</button>
          </form>
        </div>

        <!-- Brands -->
        <?php if (!empty($brands)): ?>
        <div class="sidebar-section">
          <h4>Brands</h4>
          <ul class="sidebar-list">
            <?php foreach ($brands as $b): ?>
            <li>
              <a href="<?= BASE_URL ?>/shop.php?brand=<?= $b['id'] ?><?= $cat_slug ? '&cat='.$cat_slug : '' ?>"
                 class="<?= $brand_id === (int)$b['id'] ? 'active' : '' ?>">
                <?= clean($b['name']) ?>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
      </aside>

      <!-- MAIN -->
      <main class="shop-main">
        <!-- Top bar -->
        <div class="shop-topbar">
          <div class="shop-result-info">
            <button class="sidebar-toggle btn btn-outline btn-sm"><i class="fas fa-sliders-h"></i> Filter</button>
            <span><?= number_format($total) ?> product<?= $total !== 1 ? 's' : '' ?> found<?= $search ? ' for "' . clean($search) . '"' : '' ?></span>
          </div>
          <div class="shop-sort">
            <label><i class="fas fa-sort"></i></label>
            <select id="sortSelect" onchange="location.href=this.value">
              <?php
              $sorts = ['newest'=>'Newest First','price_asc'=>'Price: Low to High','price_desc'=>'Price: High to Low','name'=>'Name A–Z','rating'=>'Top Rated'];
              foreach ($sorts as $val => $label):
                $url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['sort'=>$val,'page'=>1]));
              ?>
              <option value="<?= clean($url) ?>" <?= $sort===$val?'selected':'' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          <h3>No products found</h3>
          <p>Try adjusting your filters or search term.</p>
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-primary">Clear Filters</a>
        </div>
        <?php else: ?>
        <div class="products-grid">
          <?php foreach ($products as $p): ?>
          <div class="product-card">
            <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>" class="product-img-wrap">
              <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                <?= discount_pct($p['compare_price'], $p['price']) ?>
              <?php endif; ?>
              <?php if ($p['stock'] == 0): ?>
                <span class="badge badge-out">Out of Stock</span>
              <?php elseif ($p['stock'] <= 5): ?>
                <span class="badge badge-low">Only <?= $p['stock'] ?> left</span>
              <?php endif; ?>
              <?php if ($p['image']): ?>
                <img src="<?= product_img_url($p['image']) ?>" alt="<?= clean($p['name']) ?>" loading="lazy"/>
              <?php else: ?>
                <div class="product-img-placeholder"><i class="fas fa-box-open"></i></div>
              <?php endif; ?>
            </a>
            <div class="product-info">
              <span class="product-brand"><?= clean($p['brand'] ?? $p['category'] ?? '') ?></span>
              <h3 class="product-name">
                <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>"><?= clean($p['name']) ?></a>
              </h3>
              <div class="product-rating">
                <?= star_rating((float)$p['avg_rating'], false) ?>
                <span class="rating-count">(<?= (int)$p['review_count'] ?>)</span>
              </div>
              <div class="product-price-row">
                <div class="product-prices">
                  <span class="price-current"><?= money($p['price']) ?></span>
                  <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                  <span class="price-old"><?= money($p['compare_price']) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (is_admin()): ?>
                <span class="out-of-stock-btn" title="Shopping is disabled for admin accounts"><i class="fas fa-eye"></i></span>
                <?php elseif ($p['stock'] > 0): ?>
                <button class="add-cart-btn" data-id="<?= $p['id'] ?>" title="Add to cart">
                  <i class="fas fa-shopping-cart"></i>
                </button>
                <?php else: ?>
                <span class="out-of-stock-btn"><i class="fas fa-times-circle"></i></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($pager['total_pages'] > 1): ?>
        <nav class="pagination">
          <?php if ($pager['has_prev']): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pager['current'] - 1])) ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = max(1, $pager['current']-2); $i <= min($pager['total_pages'], $pager['current']+2); $i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
             class="page-btn <?= $i === $pager['current'] ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pager['has_next']): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pager['current'] + 1])) ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
      </main>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
