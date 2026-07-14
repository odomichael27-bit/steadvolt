/* ============================================================
   STEADYVOLT ENERGY — Main Frontend JavaScript
   File: assets/js/main.js
============================================================ */

'use strict';

// ── UTILITIES ───────────────────────────────────────────────
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

function postForm(url, data) {
  const body = new URLSearchParams(data);
  return fetch(url, { method: 'POST', body }).then(r => r.json());
}

function getCSRF() {
  const el = document.querySelector('[name="vp_csrf"]');
  return el ? el.value : '';
}

function toast(msg, type = 'success', duration = 3500) {
  let container = $('#toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';
    document.body.appendChild(container);
  }
  const colors = { success: '#00A86B', error: '#EF4444', info: '#3B82F6', warning: '#F59E0B' };
  const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
  const t = document.createElement('div');
  t.style.cssText = `background:#fff;border:1px solid #e5e7eb;border-left:4px solid ${colors[type] || colors.info};border-radius:10px;padding:14px 18px;font-size:.88rem;box-shadow:0 8px 24px rgba(0,0,0,.12);display:flex;align-items:center;gap:10px;min-width:260px;max-width:360px;pointer-events:all;animation:slideIn .25s ease;transition:opacity .3s;`;
  t.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${colors[type]};flex-shrink:0"></i><span>${msg}</span><button onclick="this.closest('div').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:1.1rem;line-height:1">&times;</button>`;
  container.appendChild(t);
  setTimeout(() => { t.style.opacity = '0'; }, duration);
  setTimeout(() => t.remove(), duration + 300);
}

if (!document.getElementById('vpAnimStyles')) {
  const s = document.createElement('style');
  s.id = 'vpAnimStyles';
  s.textContent = '@keyframes slideIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:none}}';
  document.head.appendChild(s);
}

function updateCartBadge(count) {
  document.querySelectorAll('#cartBadge,.cart-badge').forEach(el => {
    el.textContent = count;
    el.style.display = count > 0 ? 'flex' : 'none';
  });
}

function formatMoney(amount) {
  return '\u20A6' + parseFloat(amount).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ── ADD TO CART (product cards) ──────────────────────────────
document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.add-cart-btn');
  if (!btn || btn.classList.contains('add-cart-btn-detail')) return;
  const pid = btn.dataset.id;
  if (!pid) return;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  try {
    const res = await postForm(BASE_URL + '/api/cart.php', { action: 'add', product_id: pid, qty: 1, vp_csrf: getCSRF() });
    if (res.ok) {
      updateCartBadge(res.count);
      toast(res.msg || 'Added to cart!', 'success');
      btn.innerHTML = '<i class="fas fa-check"></i>';
      setTimeout(() => { btn.innerHTML = '<i class="fas fa-shopping-cart"></i>'; btn.disabled = false; }, 1500);
    } else {
      toast(res.msg || 'Could not add to cart.', 'error');
      btn.innerHTML = '<i class="fas fa-shopping-cart"></i>';
      btn.disabled = false;
    }
  } catch { toast('Network error.', 'error'); btn.innerHTML = '<i class="fas fa-shopping-cart"></i>'; btn.disabled = false; }
});

// ── ADD TO CART (detail page) ────────────────────────────────
(function () {
  const detailBtn = document.querySelector('.add-cart-btn-detail');
  if (!detailBtn) return;
  detailBtn.addEventListener('click', async function () {
    const pid = this.dataset.id;
    const qty = parseInt(document.getElementById('productQty')?.value || 1);
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding\u2026';
    const res = await postForm(BASE_URL + '/api/cart.php', { action: 'add', product_id: pid, qty, vp_csrf: getCSRF() });
    if (res.ok) {
      updateCartBadge(res.count);
      toast(qty + ' item(s) added to cart!', 'success');
      this.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
      setTimeout(() => { this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart'; this.disabled = false; }, 2000);
    } else {
      toast(res.msg || 'Could not add.', 'error');
      this.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
      this.disabled = false;
    }
  });
})();

// ── PRODUCT QTY BUTTONS (detail) ────────────────────────────
document.addEventListener('click', function (e) {
  if (e.target.closest('.cart-qty-btn')) return;
  const qtyInput = document.getElementById('productQty');
  if (!qtyInput) return;
  const max = parseInt(qtyInput.max) || 99;
  if (e.target.closest('.qty-minus')) qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
  if (e.target.closest('.qty-plus')) qtyInput.value = Math.min(max, parseInt(qtyInput.value) + 1);
});

// ── CART PAGE ────────────────────────────────────────────────
(function () {
  if (!document.getElementById('cartItemsList')) return;
  let debounceTimer;
  let appliedDiscount = 0;

  // Qty buttons
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.cart-qty-btn');
    if (!btn) return;
    const pid = btn.dataset.id;
    const input = document.querySelector('.cart-qty-input[data-id="' + pid + '"]');
    if (!input) return;
    let qty = parseInt(input.value) || 1;
    if (btn.dataset.action === 'dec') qty = Math.max(0, qty - 1);
    if (btn.dataset.action === 'inc') qty = Math.min(99, qty + 1);
    input.value = qty;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => updateCartItem(pid, qty), 400);
  });

  document.addEventListener('change', function (e) {
    const input = e.target.closest('.cart-qty-input');
    if (!input) return;
    const pid = input.dataset.id;
    const qty = Math.max(0, parseInt(input.value) || 0);
    input.value = qty;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => updateCartItem(pid, qty), 400);
  });

  async function updateCartItem(pid, qty) {
    const res = await postForm(BASE_URL + '/api/cart.php', { action: 'update', product_id: pid, qty, vp_csrf: getCSRF() });
    if (res.ok) {
      updateCartBadge(res.count);
      const subEl = document.getElementById('summarySubtotal');
      if (subEl) subEl.textContent = res.subtotal;
      recalcTotal();
      if (qty === 0) {
        const row = document.querySelector('.cart-item[data-id="' + pid + '"]');
        if (row) { row.style.transition = 'opacity .3s'; row.style.opacity = '0'; setTimeout(() => { row.remove(); if (res.count === 0) location.reload(); }, 300); }
      }
      if (res.items) {
        const item = res.items.find(i => i.id == pid);
        const totEl = document.getElementById('itemTotal' + pid);
        if (totEl && item) totEl.textContent = formatMoney(item.total);
      }
    }
  }

  // Remove item
  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.remove-cart-item');
    if (!btn) return;
    const pid = btn.dataset.id;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const res = await postForm(BASE_URL + '/api/cart.php', { action: 'remove', product_id: pid, vp_csrf: getCSRF() });
    if (res.ok) {
      updateCartBadge(res.count);
      const row = document.querySelector('.cart-item[data-id="' + pid + '"]');
      if (row) { row.style.transition = 'opacity .3s'; row.style.opacity = '0'; setTimeout(() => { row.remove(); if (res.count === 0) location.reload(); }, 300); }
      const subEl = document.getElementById('summarySubtotal');
      if (subEl) subEl.textContent = res.subtotal;
      recalcTotal();
    }
  });

  document.getElementById('clearCartBtn')?.addEventListener('click', async function () {
    if (!confirm('Clear your entire cart?')) return;
    await postForm(BASE_URL + '/api/cart.php', { action: 'clear', vp_csrf: getCSRF() });
    location.reload();
  });

  function recalcTotal() {
    const sub = parseFloat((document.getElementById('summarySubtotal')?.textContent || '0').replace(/[^\d.]/g, '')) || 0;
    const ship = parseFloat((document.getElementById('summaryShipping')?.textContent || '0').replace(/[^\d.]/g, '')) || 0;
    const disc = appliedDiscount || 0;
    const total = Math.max(0, sub + ship - disc);
    const el = document.getElementById('summaryTotal');
    if (el) el.textContent = formatMoney(total);
    const payBtn = document.getElementById('payNowBtn');
    if (payBtn) payBtn.innerHTML = '<i class="fas fa-lock"></i> Pay ' + formatMoney(total) + ' Securely';
    return total;
  }

  // Shipping fee on state change
  document.getElementById('shipState')?.addEventListener('change', async function () {
    const res = await postForm(BASE_URL + '/api/payment.php', { action: 'get_shipping', state: this.value, vp_csrf: getCSRF() });
    if (res.fee !== undefined) {
      const el = document.getElementById('summaryShipping');
      if (el) el.textContent = res.fee === 0 ? 'Free' : formatMoney(res.fee);
      recalcTotal();
    }
  });

  // Coupon
  document.getElementById('applyCouponBtn')?.addEventListener('click', async function () {
    const code = document.getElementById('couponInput')?.value?.trim();
    if (!code) { toast('Enter a coupon code.', 'warning'); return; }
    this.disabled = true; this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const sub = parseFloat((document.getElementById('summarySubtotal')?.textContent || '0').replace(/[^\d.]/g, '')) || 0;
    const res = await postForm(BASE_URL + '/api/payment.php', { action: 'check_coupon', code, subtotal: sub, vp_csrf: getCSRF() });
    this.disabled = false; this.textContent = 'Apply';
    const msgEl = document.getElementById('couponMsg');
    if (res.ok) {
      appliedDiscount = res.discount;
      if (msgEl) msgEl.innerHTML = '<span style="color:#00A86B;font-size:.83rem"><i class="fas fa-check-circle"></i> ' + res.msg + '</span>';
      const dr = document.getElementById('discountRow');
      if (dr) dr.classList.remove('hidden');
      const de = document.getElementById('summaryDiscount');
      if (de) de.textContent = '-' + res.display;
      recalcTotal();
    } else {
      appliedDiscount = 0;
      if (msgEl) msgEl.innerHTML = '<span style="color:#ef4444;font-size:.83rem"><i class="fas fa-times-circle"></i> ' + res.msg + '</span>';
    }
  });

  // Saved address cards
  document.querySelectorAll('.address-card').forEach(card => {
    card.addEventListener('click', function () {
      document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
      this.classList.add('selected');
      const f = document.getElementById('checkoutForm');
      if (!f) return;
      const set = (n, v) => { const el = f.querySelector('[name="' + n + '"]'); if (el) el.value = v; };
      set('ship_name', this.dataset.name);
      set('ship_phone', this.dataset.phone);
      set('ship_address', this.dataset.address);
      set('ship_city', this.dataset.city);
      const ss = f.querySelector('[name="ship_state"]');
      if (ss) { Array.from(ss.options).forEach(o => { o.selected = o.value === this.dataset.state; }); ss.dispatchEvent(new Event('change')); }
    });
  });

  // Proceed to checkout
  document.getElementById('proceedBtn')?.addEventListener('click', function () {
    const section = document.getElementById('checkoutFormSection');
    if (section) {
      section.classList.remove('hidden');
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
      this.classList.add('hidden');
      document.getElementById('payNowBtn')?.classList.remove('hidden');
      recalcTotal();
    }
  });

  // Pay Now
  document.getElementById('payNowBtn')?.addEventListener('click', async function () {
    const form = document.getElementById('checkoutForm');
    if (!form) return;
    const required = ['ship_name', 'ship_phone', 'ship_address', 'ship_city', 'ship_state'];
    let valid = true;
    required.forEach(name => {
      const el = form.querySelector('[name="' + name + '"]');
      if (!el || !el.value.trim()) { el?.classList.add('input-error'); valid = false; }
      else el?.classList.remove('input-error');
    });
    if (!valid) { toast('Please fill in all delivery details.', 'error'); return; }
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initialising\u2026';
    const data = Object.fromEntries(new FormData(form));
    data.vp_csrf = getCSRF();
    try {
      const res = await postForm(BASE_URL + '/api/payment.php?action=initialize', data);
      if (res.ok && res.authorization_url) {
        window.location.href = res.authorization_url;
      } else {
        toast(res.msg || 'Payment failed. Please try again.', 'error');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-lock"></i> Pay ' + (document.getElementById('summaryTotal')?.textContent || '') + ' Securely';
      }
    } catch { toast('Network error. Please try again.', 'error'); this.disabled = false; this.innerHTML = '<i class="fas fa-lock"></i> Retry Payment'; }
  });
})();

// ── WISHLIST ─────────────────────────────────────────────────
document.addEventListener('click', async function (e) {
  const btn = e.target.closest('.wishlist-btn');
  if (!btn) return;
  const pid = btn.dataset.id;
  btn.disabled = true;
  const res = await postForm(BASE_URL + '/api/wishlist.php', { action: 'toggle', product_id: pid, vp_csrf: getCSRF() });
  btn.disabled = false;
  if (res.ok) {
    if (res.action === 'added') { btn.classList.add('in-wishlist'); btn.querySelector('i')?.classList.replace('far', 'fas'); toast('Added to wishlist!', 'success'); }
    else { btn.classList.remove('in-wishlist'); btn.querySelector('i')?.classList.replace('fas', 'far'); toast('Removed from wishlist.', 'info'); }
  } else if (res.login) {
    window.location.href = BASE_URL + '/pages/login.php?redirect=' + encodeURIComponent(location.pathname);
  } else { toast(res.msg || 'Error.', 'error'); }
});

// ── MOBILE NAV ───────────────────────────────────────────────
(function () {
  const mobileNav = document.querySelector('.mobile-nav');
  const overlay = document.querySelector('.nav-overlay');
  const hamburger = document.querySelector('.hamburger');
  const closeBtn = mobileNav?.querySelector('.close-btn');
  const open = () => { mobileNav?.classList.add('open'); overlay?.classList.add('visible'); document.body.style.overflow = 'hidden'; };
  const close = () => { mobileNav?.classList.remove('open'); overlay?.classList.remove('visible'); document.body.style.overflow = ''; };
  hamburger?.addEventListener('click', open);
  closeBtn?.addEventListener('click', close);
  overlay?.addEventListener('click', close);
})();

// ── SHOP SIDEBAR MOBILE ──────────────────────────────────────
(function () {
  const sidebar = document.querySelector('.shop-sidebar');
  const toggleBtn = document.querySelector('.sidebar-toggle');
  const closeBtn = sidebar?.querySelector('.sidebar-toggle-close');
  toggleBtn?.addEventListener('click', () => sidebar?.classList.add('open'));
  closeBtn?.addEventListener('click', () => sidebar?.classList.remove('open'));
  document.addEventListener('click', function (e) {
    if (sidebar?.classList.contains('open') && !sidebar.contains(e.target) && !toggleBtn?.contains(e.target)) sidebar.classList.remove('open');
  });
})();

// ── PRODUCT TAB PANELS ──────────────────────────────────────
document.querySelectorAll('.product-tab-btns .tab-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    document.querySelectorAll('.product-tab-btns .tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + this.dataset.tab)?.classList.add('active');
  });
});

// ── HOME PRODUCT FILTER TABS ─────────────────────────────────
(function () {
  const tabBtns = document.querySelectorAll('.products-tabs .tab-btn');
  if (!tabBtns.length) return;
  tabBtns.forEach(btn => {
    btn.addEventListener('click', function () {
      tabBtns.forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      const filter = this.dataset.filter;
      document.querySelectorAll('#productsGrid .product-card').forEach(card => {
        card.style.display = (filter === 'all' || card.dataset.cat === filter) ? '' : 'none';
      });
    });
  });
})();

// ── LIVE SEARCH ──────────────────────────────────────────────
(function () {
  const input = document.getElementById('siteSearch');
  const dropdown = document.getElementById('searchDropdown');
  if (!input || !dropdown) return;
  let debounce;
  input.addEventListener('input', function () {
    clearTimeout(debounce);
    const q = this.value.trim();
    if (q.length < 2) { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; return; }
    debounce = setTimeout(async () => {
      try {
        const res = await fetch(BASE_URL + '/api/search.php?q=' + encodeURIComponent(q));
        const data = await res.json();
        if (!data.results?.length) {
          dropdown.innerHTML = '<div style="padding:16px;text-align:center;font-size:.85rem;color:#9ca3af">No results for "<strong>' + q + '</strong>"</div>';
        } else {
          dropdown.innerHTML = data.results.map(p =>
            '<a href="' + BASE_URL + '/pages/product-detail.php?slug=' + p.slug + '" class="search-result-item">' +
            (p.image ? '<img src="' + BASE_URL + '/uploads/products/' + p.image + '" alt=""/>' : '<div style="width:40px;height:40px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#d1d5db"><i class="fas fa-box-open"></i></div>') +
            '<div><div class="sri-name">' + p.name + '</div><div class="sri-price">' + formatMoney(p.price) + '</div></div></a>'
          ).join('');
        }
        dropdown.classList.remove('hidden');
      } catch { /* silent */ }
    }, 280);
  });
  document.addEventListener('click', e => { if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden'); });
  input.addEventListener('keydown', e => {
    if (e.key === 'Escape') dropdown.classList.add('hidden');
    if (e.key === 'Enter') { const first = dropdown.querySelector('.search-result-item'); if (first) window.location.href = first.href; }
  });
})();

// ── REVIEWS SLIDER ───────────────────────────────────────────
// Now powered by Swiper.js (see $extra_js in index.php) — shows 3 cards,
// autoplays, and pauses on hover natively. Nothing to do here.


// ── STAR RATING PICKER ──────────────────────────────────────
(function () {
  const picker = document.getElementById('starPicker');
  if (!picker) return;
  const stars = picker.querySelectorAll('i');
  const input = document.getElementById('ratingInput');
  stars.forEach((star, i) => {
    star.addEventListener('mouseenter', () => stars.forEach((s, j) => s.className = j <= i ? 'fas fa-star' : 'far fa-star'));
    star.addEventListener('mouseleave', () => { const v = parseInt(input?.value || 0); stars.forEach((s, j) => s.className = j < v ? 'fas fa-star' : 'far fa-star'); });
    star.addEventListener('click', () => { if (input) input.value = i + 1; stars.forEach((s, j) => { s.className = j <= i ? 'fas fa-star' : 'far fa-star'; j <= i ? s.classList.add('active') : s.classList.remove('active'); }); });
  });
})();

// ── OTP INPUT BOXES ──────────────────────────────────────────
(function () {
  const digits = document.querySelectorAll('.otp-digit');
  if (!digits.length) return;
  const hidden = document.getElementById('otpHidden');
  digits.forEach((el, i) => {
    el.addEventListener('input', function () {
      this.value = this.value.replace(/\D/, '').slice(0, 1);
      if (this.value && i < digits.length - 1) digits[i + 1].focus();
      if (hidden) hidden.value = Array.from(digits).map(d => d.value).join('');
    });
    el.addEventListener('keydown', function (e) { if (e.key === 'Backspace' && !this.value && i > 0) digits[i - 1].focus(); });
    el.addEventListener('paste', function (e) {
      e.preventDefault();
      const p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      p.split('').forEach((ch, j) => { if (digits[j]) digits[j].value = ch; });
      if (hidden) hidden.value = p;
      (digits[p.length] || digits[digits.length - 1]).focus();
    });
  });

  const resendBtn = document.getElementById('resendOtp');
  const timerEl = document.getElementById('resendTimer');
  if (resendBtn) {
    let cd = 60;
    let interval = setInterval(() => {
      cd--;
      if (timerEl) timerEl.textContent = '(' + cd + 's)';
      if (cd <= 0) { clearInterval(interval); resendBtn.disabled = false; if (timerEl) timerEl.textContent = ''; }
    }, 1000);
    resendBtn.disabled = true;
    resendBtn.addEventListener('click', async function () {
      this.disabled = true;
      const res = await postForm(BASE_URL + '/api/auth.php', { action: 'fp_resend_otp', vp_csrf: getCSRF() });
      if (res.ok) { toast('New OTP sent to your email.', 'success'); cd = 60; interval = setInterval(() => { cd--; if (timerEl) timerEl.textContent = '(' + cd + 's)'; if (cd <= 0) { clearInterval(interval); this.disabled = false; if (timerEl) timerEl.textContent = ''; } }, 1000); }
      else { toast('Could not resend OTP.', 'error'); this.disabled = false; }
    });
  }
})();

// ── PASSWORD STRENGTH ────────────────────────────────────────
(function () {
  const pw = document.getElementById('signupPw') || document.getElementById('newPw');
  const bar = document.getElementById('pwBar');
  const label = document.getElementById('pwStrengthLabel');
  if (!pw || !bar) return;
  const levels = [{ re: /.{8,}/, s: 1 }, { re: /[A-Z]/, s: 1 }, { re: /[0-9]/, s: 1 }, { re: /[^A-Za-z0-9]/, s: 1 }];
  const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#00A86B'];
  const texts = ['', 'Weak', 'Fair', 'Good', 'Strong'];
  pw.addEventListener('input', function () {
    const score = levels.reduce((a, l) => a + (l.re.test(this.value) ? l.s : 0), 0);
    bar.style.width = (score * 25) + '%'; bar.style.background = colors[score] || '';
    if (label) { label.textContent = this.value ? texts[score] : ''; label.style.color = colors[score]; }
  });
})();

// ── TOGGLE PASSWORD VISIBILITY ───────────────────────────────
document.addEventListener('click', function (e) {
  const btn = e.target.closest('.toggle-pw');
  if (!btn) return;
  const input = btn.closest('.input-wrap')?.querySelector('input');
  if (!input) return;
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  const icon = btn.querySelector('i');
  if (icon) { icon.classList.toggle('fa-eye', !isHidden); icon.classList.toggle('fa-eye-slash', isHidden); }
});

// ── AVATAR PREVIEW ───────────────────────────────────────────
(function () {
  const input = document.getElementById('avatarInput');
  const preview = document.getElementById('avatarPreview') || document.getElementById('avatarInitials');
  if (!input || !preview) return;
  input.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (file.size > 2097152) { toast('Image too large. Max 2MB.', 'error'); return; }
    const reader = new FileReader();
    reader.onload = e => {
      if (preview.tagName === 'IMG') { preview.src = e.target.result; }
      else { const img = document.createElement('img'); img.src = e.target.result; img.style.cssText = 'width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #00A86B'; preview.replaceWith(img); }
    };
    reader.readAsDataURL(file);
  });
})();

// ── CHATBOT ──────────────────────────────────────────────────
(function () {
  const toggle = document.getElementById('chatbotToggle');
  const box = document.getElementById('chatbotBox');
  const closeBtn = document.getElementById('chatbotClose');
  const input = document.getElementById('chatbotInput');
  const sendBtn = document.getElementById('chatbotSend');
  const messages = document.getElementById('chatbotMessages');
  const unread = document.getElementById('chatbotUnread');
  if (!toggle || !box) return;
  let isOpen = false, welcomeSent = false;

  function appendMsg(text, role) {
    const div = document.createElement('div');
    div.className = 'chat-msg ' + role;
    div.innerHTML = text;
    messages?.appendChild(div);
    if (messages) messages.scrollTop = messages.scrollHeight;
  }
  function showTyping() {
    const el = document.createElement('div');
    el.className = 'chat-typing'; el.id = 'chatTyping';
    el.innerHTML = '<span></span><span></span><span></span>';
    messages?.appendChild(el);
    if (messages) messages.scrollTop = messages.scrollHeight;
  }
  function hideTyping() { document.getElementById('chatTyping')?.remove(); }

  function openChat() {
    box.classList.remove('hidden');
    toggle.querySelector('.chatbot-icon-open')?.classList.add('hidden');
    toggle.querySelector('.chatbot-icon-close')?.classList.remove('hidden');
    unread?.classList.add('hidden');
    isOpen = true;
    if (!welcomeSent && typeof CHATBOT_WELCOME !== 'undefined') { appendMsg(CHATBOT_WELCOME, 'bot'); welcomeSent = true; }
    input?.focus();
  }
  function closeChat() {
    box.classList.add('hidden');
    toggle.querySelector('.chatbot-icon-open')?.classList.remove('hidden');
    toggle.querySelector('.chatbot-icon-close')?.classList.add('hidden');
    isOpen = false;
  }

  toggle.addEventListener('click', () => isOpen ? closeChat() : openChat());
  closeBtn?.addEventListener('click', closeChat);

  async function sendMessage() {
    const text = input?.value?.trim();
    if (!text) return;
    if (input) input.value = '';
    appendMsg(text, 'user');
    showTyping();
    try {
      const res = await postForm(BASE_URL + '/api/chatbot.php', { message: text, vp_csrf: getCSRF() });
      hideTyping();
      appendMsg(res.reply || "Sorry, I couldn't understand that. Please contact us directly!", 'bot');
    } catch { hideTyping(); appendMsg('Connection error. Please try again.', 'bot'); }
  }

  sendBtn?.addEventListener('click', sendMessage);
  input?.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
  setTimeout(() => { if (!isOpen) unread?.classList.remove('hidden'); }, 3000);
})();

// ── USER DROPDOWN ────────────────────────────────────────────
(function () {
  const dropdown = document.querySelector('.nav-user-dropdown');
  if (!dropdown) return;
  dropdown.querySelector('.user-toggle')?.addEventListener('click', function (e) { e.stopPropagation(); dropdown.classList.toggle('open'); });
  document.addEventListener('click', () => dropdown.classList.remove('open'));
})();

// ── GALLERY ZOOM ─────────────────────────────────────────────
(function () {
  const mainImg = document.getElementById('mainImage');
  if (!mainImg) return;
  mainImg.addEventListener('click', function () {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.92);display:flex;align-items:center;justify-content:center;cursor:zoom-out;';
    const img = document.createElement('img');
    img.src = this.src; img.style.cssText = 'max-width:90vw;max-height:90vh;border-radius:12px;object-fit:contain;';
    ov.appendChild(img); ov.addEventListener('click', () => ov.remove()); document.body.appendChild(ov);
  });
})();

// ── AUTO-DISMISS ALERTS ──────────────────────────────────────
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 500); }, 5000);
});

// ── INIT ─────────────────────────────────────────────────────
updateCartBadge(typeof CART_COUNT !== 'undefined' ? CART_COUNT : 0);
