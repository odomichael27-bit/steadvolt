<?php
// ============================================================
//  STEADVOLT — Cart & Checkout Page
//  File: cart.php
// ============================================================
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Your Cart';
$items      = cart_items();
$subtotal   = cart_total();
$user       = auth_user();

// Saved addresses for logged-in user
$addresses  = $user ? DB::all("SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC", [$user['id']]) : [];

// Nigerian states list
$states = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno','Cross River','Delta',
           'Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina',
           'Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun','Oyo',
           'Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];

$paystack_pub = DB::setting('paystack_public_key');

require_once __DIR__ . '/includes/header.php';
?>

<?= flash_html('order') ?>

<div class="cart-page">
  <div class="container">
    <h1 class="page-title"><i class="fas fa-shopping-cart"></i> Your Cart</h1>

    <?php if (is_admin()): ?>
    <div class="admin-cart-notice">
      <i class="fas fa-info-circle"></i>
      Admin accounts can browse products but can't add items to the cart or place orders. Log in as a customer to test the checkout flow.
    </div>
    <?php endif; ?>

    <?php if (empty($items)): ?>
    <!-- Empty cart -->
    <div class="empty-state large">
      <i class="fas fa-shopping-cart"></i>
      <h2>Your cart is empty</h2>
      <p>Looks like you haven't added anything yet. Explore our products and find something you love!</p>
      <a href="<?= BASE_URL ?>/shop.php" class="btn btn-primary"><i class="fas fa-store"></i> Continue Shopping</a>
    </div>

    <?php else: ?>
    <div class="cart-layout">

      <!-- LEFT: Cart Items -->
      <div class="cart-items-col">
        <div class="cart-items-header">
          <span><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in cart</span>
          <button class="link-btn text-danger" id="clearCartBtn"><i class="fas fa-trash"></i> Clear cart</button>
        </div>

        <div id="cartItemsList">
          <?php foreach ($items as $item): ?>
          <div class="cart-item" data-id="<?= $item['id'] ?>">
            <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($item['slug']) ?>" class="cart-item-img">
              <?php if ($item['image']): ?>
                <img src="<?= product_img_url($item['image']) ?>" alt="<?= clean($item['name']) ?>"/>
              <?php else: ?>
                <div class="product-img-placeholder small"><i class="fas fa-box-open"></i></div>
              <?php endif; ?>
            </a>
            <div class="cart-item-info">
              <a href="<?= BASE_URL ?>/pages/product-detail.php?slug=<?= clean($item['slug']) ?>" class="cart-item-name"><?= clean($item['name']) ?></a>
              <div class="cart-item-price"><?= money($item['price']) ?> each</div>
              <div class="cart-item-controls">
                <div class="qty-selector">
                  <button class="qty-minus cart-qty-btn" data-id="<?= $item['id'] ?>" data-action="dec" aria-label="Decrease"><i class="fas fa-minus"></i></button>
                  <input type="number" class="qty-val cart-qty-input" data-id="<?= $item['id'] ?>" value="<?= $item['qty'] ?>" min="1" max="99"/>
                  <button class="qty-plus cart-qty-btn" data-id="<?= $item['id'] ?>" data-action="inc" aria-label="Increase"><i class="fas fa-plus"></i></button>
                </div>
                <button class="link-btn text-danger remove-cart-item" data-id="<?= $item['id'] ?>">
                  <i class="fas fa-trash-alt"></i> Remove
                </button>
              </div>
            </div>
            <div class="cart-item-total" id="itemTotal<?= $item['id'] ?>"><?= money($item['total']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="cart-continue">
          <a href="<?= BASE_URL ?>/shop.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
        </div>

        <!-- Checkout form (only shown when user clicks Proceed) -->
        <?php if ($user && !is_admin()): ?>
        <div id="checkoutFormSection" class="checkout-form-section hidden">
          <h2><i class="fas fa-map-marker-alt"></i> Delivery Details</h2>

          <?php if (!empty($addresses)): ?>
          <div class="saved-addresses">
            <p><strong>Use a saved address:</strong></p>
            <div class="address-cards">
              <?php foreach ($addresses as $addr): ?>
              <div class="address-card <?= $addr['is_default'] ? 'selected' : '' ?>"
                   data-name="<?= clean($addr['full_name']) ?>"
                   data-phone="<?= clean($addr['phone']) ?>"
                   data-address="<?= clean($addr['address_line']) ?>"
                   data-city="<?= clean($addr['city']) ?>"
                   data-state="<?= clean($addr['state']) ?>">
                <strong><?= clean($addr['label']) ?></strong>
                <span><?= clean($addr['full_name']) ?></span>
                <span><?= clean($addr['address_line']) ?>, <?= clean($addr['city']) ?>, <?= clean($addr['state']) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <form id="checkoutForm">
            <?= csrf_field() ?>
            <div class="form-row-2">
              <div class="form-group">
                <label>Full Name *</label>
                <div class="input-wrap">
                  <i class="fas fa-user input-icon"></i>
                  <input type="text" name="ship_name" required value="<?= clean($user['first_name'] . ' ' . $user['last_name']) ?>"/>
                </div>
              </div>
              <div class="form-group">
                <label>Phone *</label>
                <div class="input-wrap">
                  <i class="fas fa-phone input-icon"></i>
                  <input type="tel" name="ship_phone" required value="<?= clean($user['phone'] ?? '') ?>"/>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Delivery Address *</label>
              <div class="input-wrap">
                <i class="fas fa-map-marker-alt input-icon"></i>
                <input type="text" name="ship_address" required placeholder="Street address, house number"/>
              </div>
            </div>
            <div class="form-row-2">
              <div class="form-group">
                <label>City *</label>
                <div class="input-wrap">
                  <i class="fas fa-city input-icon"></i>
                  <input type="text" name="ship_city" required placeholder="e.g. Lagos Island"/>
                </div>
              </div>
              <div class="form-group">
                <label>State *</label>
                <div class="input-wrap">
                  <i class="fas fa-map input-icon"></i>
                  <select name="ship_state" id="shipState" required>
                    <option value="">— Select State —</option>
                    <?php foreach ($states as $s): ?>
                    <option value="<?= $s ?>"><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>Order Notes (optional)</label>
              <textarea name="notes" rows="2" placeholder="Any special instructions for delivery…"></textarea>
            </div>
            <div class="form-group">
              <label>Coupon Code</label>
              <div class="coupon-row">
                <div class="input-wrap" style="flex:1">
                  <i class="fas fa-tag input-icon"></i>
                  <input type="text" name="coupon_code" id="couponInput" placeholder="Enter coupon code"/>
                </div>
                <button type="button" class="btn btn-outline" id="applyCouponBtn">Apply</button>
              </div>
              <div id="couponMsg"></div>
            </div>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <!-- RIGHT: Order Summary -->
      <div class="cart-summary-col">
        <div class="order-summary">
          <h2>Order Summary</h2>

          <div class="summary-row">
            <span>Subtotal</span>
            <span id="summarySubtotal"><?= money($subtotal) ?></span>
          </div>
          <div class="summary-row" id="shippingRow">
            <span>Shipping</span>
            <span id="summaryShipping">—</span>
          </div>
          <div class="summary-row discount-row hidden" id="discountRow">
            <span><i class="fas fa-tag"></i> Discount</span>
            <span id="summaryDiscount" class="text-green">—</span>
          </div>
          <div class="summary-divider"></div>
          <div class="summary-row total-row">
            <strong>Total</strong>
            <strong id="summaryTotal"><?= money($subtotal) ?></strong>
          </div>

          <div class="summary-note">
            <i class="fas fa-truck"></i>
            <?php $free_min = (float)DB::setting('free_shipping_min', '150000'); ?>
            <?php if ($subtotal >= $free_min): ?>
              <span class="text-green">You qualify for free shipping!</span>
            <?php else: ?>
              Add <?= money($free_min - $subtotal) ?> more for free shipping.
            <?php endif; ?>
          </div>

          <?php if (!$user): ?>
          <!-- Guest: prompt login -->
          <div class="checkout-login-prompt">
            <i class="fas fa-lock"></i>
            <p>You need to be signed in to complete your purchase.</p>
            <a href="<?= BASE_URL ?>/pages/login.php?redirect=/cart.php" class="btn btn-primary btn-block">
              <i class="fas fa-sign-in-alt"></i> Sign In to Checkout
            </a>
            <a href="<?= BASE_URL ?>/pages/signup.php?redirect=/cart.php" class="btn btn-outline btn-block">
              <i class="fas fa-user-plus"></i> Create Account
            </a>
          </div>
          <?php elseif (is_admin()): ?>
          <div class="checkout-login-prompt">
            <i class="fas fa-eye"></i>
            <p>Shopping is disabled for admin accounts.</p>
          </div>
          <?php else: ?>
          <!-- Logged in: proceed to checkout -->
          <button class="btn btn-primary btn-block" id="proceedBtn">
            <i class="fas fa-credit-card"></i> Proceed to Checkout
          </button>
          <button class="btn btn-primary btn-block hidden" id="payNowBtn">
            <i class="fas fa-lock"></i> Pay <?= money($subtotal) ?> Securely
          </button>
          <?php endif; ?>

          <div class="summary-trust">
            <span><i class="fas fa-lock"></i> SSL Secured</span>
            <span><i class="fas fa-shield-alt"></i> Paystack</span>
            <span><i class="fas fa-undo"></i> 30-Day Returns</span>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($user && $paystack_pub && !is_admin()): ?>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
const PAYSTACK_PUBLIC_KEY = '<?= clean($paystack_pub) ?>';
const USER_EMAIL = '<?= clean($user['email']) ?>';
const BASE_URL   = '<?= BASE_URL ?>';
const CART_SUBTOTAL = <?= $subtotal ?>;
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
