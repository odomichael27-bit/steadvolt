<?php
// ============================================================
//  STEADVOLT — Home Page
//  File: index.php
// ============================================================
require_once __DIR__ . '/includes/functions.php';

$page_title = DB::setting('site_tagline', 'Power Your Future');
$meta_desc  = 'SteadVolt Energy — Nigeria\'s #1 source for solar panels, inverters, batteries, and smart cameras. Delivered nationwide.';

// Featured products (up to 12)
$featured = DB::all(
    "SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.is_featured,
            c.name AS category, c.slug AS cat_slug,
            b.name AS brand,
            pi.filename AS image,
            CASE WHEN p.rating_override IS NOT NULL THEN p.rating_override ELSE COALESCE(AVG(r.rating),0) END AS avg_rating,
            CASE WHEN p.rating_override IS NOT NULL THEN COALESCE(p.rating_override_count, COUNT(r.id)) ELSE COUNT(r.id) END AS review_count
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN brands b ON b.id = p.brand_id
     LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
     LEFT JOIN reviews r ON r.product_id = p.id AND r.is_approved = 1
     WHERE p.is_active = 1
     GROUP BY p.id
     ORDER BY p.is_featured DESC, p.created_at DESC
     LIMIT 12"
);

// Google Reviews (from cache)
$google_reviews = [];
$cache_row = DB::row("SELECT review_data, cached_at FROM google_reviews_cache ORDER BY id DESC LIMIT 1");
$cache_hours = (int)DB::setting('google_reviews_cache_hours', '12');
if ($cache_row && (time() - strtotime($cache_row['cached_at'])) < $cache_hours * 3600) {
    $google_reviews = json_decode($cache_row['review_data'], true) ?? [];
}

// Categories
$categories = DB::all("SELECT * FROM categories WHERE 1 ORDER BY sort_order ASC");

// Swiper.js (CDN) — used for the Google Reviews slider below. See the
// markup + init script right after that section for the full setup.
$extra_css = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>';

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div class="hero-content">
        <div class="hero-eyebrow">
          <span></span> Nigeria's #1 Clean Energy Store
        </div>
        <h1>Power Your Future with <em>Sustainable</em> Technology</h1>
        <p>Premium solar panels, inverters, deep-cycle batteries, smart cameras, and power accessories — delivered to your door.</p>
        <div class="hero-btns">
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-primary">
            <i class="fas fa-shopping-cart"></i> Shop Electronics
          </a>
          <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-outline btn-outline-white">
            <i class="fas fa-file-invoice"></i> Get a Quote
          </a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat"><strong>5,000+</strong><span>Products Sold</span></div>
          <div class="hero-stat"><strong>98%</strong><span>Satisfaction Rate</span></div>
          <div class="hero-stat"><strong>24/7</strong><span>Support</span></div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="hero-icon-card">
          <i class="fas fa-solar-panel hero-big-icon"></i>
        </div>
        <div class="hero-float-badge badge-1">
          <div class="badge-icon"><i class="fas fa-check-circle"></i></div>
          <div><strong>Tier-1 Certified</strong><span>All products</span></div>
        </div>
        <div class="hero-float-badge badge-2">
          <div class="badge-icon"><i class="fas fa-shield-alt"></i></div>
          <div><strong>5-Year Warranty</strong><span>Nationwide cover</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="categories section-pad">
  <div class="container">
    <div class="section-header">
      <span class="tag">Browse by Category</span>
      <h2 class="section-title">Everything You Need for Clean Power</h2>
      <p class="section-sub">From solar generation to smart security — we stock the best brands at competitive prices.</p>
    </div>
    <div class="categories-grid lazy">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= BASE_URL ?>/shop.php?cat=<?= clean($cat['slug']) ?>" class="cat-card">
        <div class="cat-icon"><i class="fas <?= clean($cat['icon']) ?>"></i></div>
        <h3><?= clean($cat['name']) ?></h3>
        <p><?= clean($cat['description']) ?></p>
        <span class="cat-link">Explore <i class="fas fa-arrow-right"></i></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="products section-pad bg-off-white">
  <div class="container">
    <div class="products-header">
      <div>
        <span class="tag">Top Sellers</span>
        <h2 class="section-title">Featured Products</h2>
      </div>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline">View All Products</a>
    </div>

    <!-- Tab filter -->
    <div class="products-tabs">
      <button class="tab-btn active" data-filter="all">All</button>
      <?php foreach ($categories as $cat): ?>
      <button class="tab-btn" data-filter="<?= clean($cat['slug']) ?>"><?= clean($cat['name']) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="products-grid" id="productsGrid">
      <?php foreach ($featured as $p): ?>
      <div class="product-card lazy" data-cat="<?= clean($p['cat_slug'] ?? 'all') ?>">
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
          <span class="product-brand"><?= clean($p['brand'] ?? $p['category'] ?? '') ?></span>
          <h3 class="product-name"><a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($p['slug']) ?>"><?= clean($p['name']) ?></a></h3>
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
            <?php else: ?>
            <button class="add-cart-btn" data-id="<?= $p['id'] ?>" title="Add to cart">
              <i class="fas fa-shopping-cart"></i>
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- WHY STEADVOLT -->
<section class="why-us section-pad">
  <div class="container">
    <div class="section-header centered">
      <span class="tag">Why SteadVolt</span>
      <h2 class="section-title">Built Around Your Power Needs</h2>
    </div>
    <div class="why-grid">
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-leaf"></i></div>
        <h3>Eco-Friendly Technology</h3>
        <p>Every product is selected for efficiency, durability, and positive environmental impact.</p>
      </div>
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-headset"></i></div>
        <h3>24/7 Expert Support</h3>
        <p>Our certified engineers are available round the clock by phone, chat, or email.</p>
      </div>
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-shield-alt"></i></div>
        <h3>Nationwide Warranty</h3>
        <p>Every purchase is backed by a 2–5 year warranty with fast replacement service across Nigeria.</p>
      </div>
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-truck"></i></div>
        <h3>Fast Nationwide Delivery</h3>
        <p>Lagos: 1–2 days. Other states: 3–8 days. Free shipping on orders above ₦150,000.</p>
      </div>
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-certificate"></i></div>
        <h3>Tier-1 Certified Products</h3>
        <p>All our solar and energy products meet international quality certifications.</p>
      </div>
      <div class="why-card">
        <div class="why-icon"><i class="fas fa-lock"></i></div>
        <h3>Secure Payments</h3>
        <p>Pay safely via Paystack — card, bank transfer, or USSD. 100% encrypted.</p>
      </div>
    </div>
  </div>
</section>

<!-- GOOGLE REVIEWS -->
<section class="reviews-section section-pad bg-off-white">
  <div class="container">
    <div class="section-header centered">
      <span class="tag">Customer Reviews</span>
      <h2 class="section-title">Trusted by Thousands Across Nigeria</h2>
      <?php
      $api_key  = DB::setting('google_places_api_key', '');
      $place_id = DB::setting('google_place_id', '');
      if ($api_key && $place_id):
      ?>
      <a href="https://search.google.com/local/reviews?placeid=<?= clean($place_id) ?>" target="_blank" class="google-reviews-link">
        <i class="fab fa-google"></i> View all Google Reviews
      </a>
      <?php endif; ?>
    </div>

    <div class="reviews-slider-wrap">
      <div class="swiper reviewsSwiper" id="reviewsSlider">
        <div class="swiper-wrapper">
          <?php if (!empty($google_reviews)): ?>
            <?php foreach (array_slice($google_reviews, 0, 8) as $rev): ?>
            <div class="swiper-slide">
              <div class="review-card">
                <div class="review-stars"><?= star_rating($rev['rating'] ?? 5, false) ?></div>
                <p class="review-text"><?= clean($rev['text'] ?? '') ?></p>
                <div class="review-author">
                  <?php if (!empty($rev['profile_photo_url'])): ?>
                    <img src="<?= clean($rev['profile_photo_url']) ?>" alt="<?= clean($rev['author_name'] ?? '') ?>" class="review-avatar" loading="lazy"/>
                  <?php else: ?>
                    <div class="review-avatar-placeholder"><?= strtoupper(substr($rev['author_name'] ?? 'U', 0, 2)) ?></div>
                  <?php endif; ?>
                  <div>
                    <strong><?= clean($rev['author_name'] ?? 'Google User') ?></strong>
                    <span><?= clean($rev['relative_time_description'] ?? '') ?></span>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- Fallback static reviews when Google not configured -->
            <div class="swiper-slide">
              <div class="review-card">
                <div class="review-stars"><?= star_rating(5, false) ?></div>
                <p class="review-text">"We installed a 10kW solar system for our factory. Power bills dropped by 80% and the installation team was incredibly professional."</p>
                <div class="review-author">
                  <div class="review-avatar-placeholder">AO</div>
                  <div><strong>Akin Olawale</strong><span>Ibadan, Oyo State</span></div>
                </div>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="review-card">
                <div class="review-stars"><?= star_rating(5, false) ?></div>
                <p class="review-text">"Ordered 8 Hikvision cameras. Next-day delivery to Abuja, every unit fully packaged. The support team walked me through setup — excellent service!"</p>
                <div class="review-author">
                  <div class="review-avatar-placeholder">FC</div>
                  <div><strong>Fatima Chukwu</strong><span>Abuja, FCT</span></div>
                </div>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="review-card">
                <div class="review-stars"><?= star_rating(5, false) ?></div>
                <p class="review-text">"The lithium batteries have been running our clinic for 14 months with zero issues. SteadVolt is the most reliable energy supplier we've worked with."</p>
                <div class="review-author">
                  <div class="review-avatar-placeholder">DE</div>
                  <div><strong>Dr. Emeka Okonkwo</strong><span>Enugu, Enugu State</span></div>
                </div>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="review-card">
                <div class="review-stars"><?= star_rating(5, false) ?></div>
                <p class="review-text">"Bought a complete solar kit. The team helped me size the system correctly. Installation was smooth and everything works perfectly. Highly recommended!"</p>
                <div class="review-author">
                  <div class="review-avatar-placeholder">TN</div>
                  <div><strong>Tunde Nwachukwu</strong><span>Port Harcourt, Rivers State</span></div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="slider-controls">
        <button id="revPrev" class="slider-btn"><i class="fas fa-chevron-left"></i></button>
        <div class="slider-dots swiper-pagination" id="reviewDots"></div>
        <button id="revNext" class="slider-btn"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
(function () {
  function initReviewsSwiper() {
    if (typeof Swiper === 'undefined') {
      // CDN failed to load — cards fall back to a static responsive grid
      // (see .reviewsSwiper .swiper-wrapper in style.css), so just hide the
      // now-nonfunctional prev/next/dots controls instead of leaving dead
      // buttons on screen.
      document.querySelector('.reviews-slider-wrap .slider-controls')?.classList.add('hidden');
      return;
    }
    new Swiper('.reviewsSwiper', {
      slidesPerView: 1,
      spaceBetween: 24,
      loop: true,
      grabCursor: true,
      autoplay: { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
      pagination: { el: '#reviewDots', clickable: true },
      navigation: { nextEl: '#revNext', prevEl: '#revPrev' },
      breakpoints: {
        640:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 }
      }
    });
  }
  // The <script src> above is synchronous (no defer/async), so by the time
  // this runs, Swiper is already defined — but guard with DOMContentLoaded
  // too in case script execution order ever changes.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initReviewsSwiper);
  } else {
    initReviewsSwiper();
  }
})();
</script>

<!-- CTA BANNER -->
<section class="cta-banner">
  <div class="container">
    <div class="cta-content">
      <h2>Ready to Switch to Clean Energy?</h2>
      <p>Talk to our certified engineers and get a free energy consultation today.</p>
      <div class="cta-btns">
        <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-white">
          <i class="fas fa-comments"></i> Get Free Consultation
        </a>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', DB::setting('site_whatsapp')) ?>" target="_blank" class="btn btn-whatsapp">
          <i class="fab fa-whatsapp"></i> Chat on WhatsApp
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
