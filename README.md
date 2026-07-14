# ⚡ SteadyVolt Energy — Full-Stack PHP E-Commerce

A complete, production-ready e-commerce platform built in PHP for a Nigerian energy products store. Every feature is real and fully wired — no demos, no placeholders.

---

## 📋 Feature Summary

| Feature | Status |
|---|---|
| Product catalogue with categories, brands, specs, multi-image | ✅ |
| Cart (session-based, works without login) | ✅ |
| Guest browse + login required at checkout | ✅ |
| User registration, login, logout | ✅ |
| Forgot password with OTP email (PHPMailer) | ✅ |
| Profile: avatar upload, name, phone, password change | ✅ |
| Saved addresses | ✅ |
| Wishlist | ✅ |
| Order history & tracking | ✅ |
| **Paystack** payment (₦ NGN, card, bank transfer, USSD) | ✅ |
| Paystack webhook (auto-confirm orders) | ✅ |
| Coupon / discount codes | ✅ |
| Shipping zones by Nigerian state | ✅ |
| **Built-in chatbot** (keyword Q&A, managed from admin) | ✅ |
| **Smartsupp** live chat integration | ✅ |
| **Google Reviews** pulled live via Places API (cached) | ✅ |
| Live search (AJAX, no page reload) | ✅ |
| Product reviews (approve/reject from admin) | ✅ |
| Admin dashboard with revenue charts | ✅ |
| Admin: products, orders, customers, reviews, messages | ✅ |
| Admin: all settings in one place (Paystack, SMTP, Google, Smartsupp) | ✅ |
| Order confirmation + welcome emails | ✅ |
| WhatsApp float button | ✅ |
| Responsive mobile design | ✅ |

---

## 🚀 Installation Guide

### 1. Requirements

- PHP **8.1+**
- MySQL **8.0+** or MariaDB **10.5+**
- Apache with `mod_rewrite` enabled (or Nginx)
- Composer
- SSL certificate (required for Paystack live mode)

---

### 2. Upload Files

Upload all project files to your web root. For local testing, any of these work out of the box:

- **XAMPP/WAMP/MAMP**: copy the `steadyvolt/` folder into `htdocs/` (or `www/`), then visit `http://localhost/steadyvolt/`
- **PHP built-in server**: `cd steadyvolt && php -S localhost:8000`, then visit `http://localhost:8000/`
  > Note: the built-in server does **not** read `.htaccess`, so the security/caching rules in that file won't apply locally — this is normal and only matters once you deploy to real Apache hosting.

```
steadyvolt/
├── admin/
├── api/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── admin.css
│   └── js/
│       ├── main.js
│       └── admin.js
├── includes/
├── pages/
├── uploads/
│   ├── products/     ← product images (writable)
│   └── avatars/      ← user avatars (writable)
├── vendor/           ← created by Composer
├── .htaccess
├── composer.json
├── database.sql
├── index.php
├── shop.php
└── cart.php
```

---

### 3. Create the Database

```bash
mysql -u root -p
```
```sql
CREATE DATABASE steadyvolt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```
```bash
mysql -u root -p steadyvolt < database.sql
```

This imports all tables and seeds:
- All settings (empty keys to fill in)
- Product categories & shipping zones
- Default chatbot responses
- Default admin user (see step 6)

---

### 4. Install PHPMailer

```bash
cd /path/to/steadyvolt
composer install
```

---

### 5. Configure the Application

Edit `includes/config.php`:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'steadyvolt');
define('DB_USER', 'your_db_user');     // 'root' by default — fine for XAMPP/WAMP/MAMP
define('DB_PASS', 'your_db_password'); // '' by default — fine for XAMPP/WAMP/MAMP
```

> ✅ **You do NOT need to set a domain or BASE_URL.** It's auto-detected from
> the request, so the site works immediately on `http://localhost/steadyvolt/`,
> `http://localhost:8000/`, or any live domain — no editing required.
> If you ever need to force a fixed URL (e.g. behind a reverse proxy), set
> `$force_base_url` near the top of `includes/config.php`.
>
> `APP_DEBUG` is also auto-detected: it turns on automatically for
> `localhost`, `127.0.0.1`, and `*.test`/`*.local` hosts (so you see real PHP
> errors while developing), and turns off automatically on any other domain.

---

### 6. Set Directory Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/avatars/
```

---

### 7. First Login — Admin Panel

Visit: `http://localhost/steadyvolt/admin/dashboard.php` (adjust the path to match your local folder name, or use your live domain once deployed)

| Field | Value |
|---|---|
| Email | `admin@steadyvolt.ng` |
| Password | `Admin@SteadyVolt1` |

> ⚠️ **Change the admin password immediately** after your first login via **Admin → Account Settings**.

---

### 8. Configure All Settings via Admin Panel

Go to **Admin → Store Settings** and fill in:

#### Paystack (Payment)
1. Sign up at [paystack.com](https://paystack.com)
2. Go to Settings → API Keys
3. Copy your **Public Key** and **Secret Key**
4. Set your webhook URL in Paystack dashboard:
   ```
   https://yourdomain.com/api/payment.php?action=webhook
   ```
   (Paystack webhooks require a publicly reachable HTTPS URL — this only works once
   your site is live on a real domain. Webhooks can't reach `localhost`, but
   payments still work fine locally via the redirect-based verify flow.)

#### Email / SMTP (for OTP & order emails)
- **Gmail users:** Use an [App Password](https://myaccount.google.com/apppasswords) (not your main password)
- Host: `smtp.gmail.com` | Port: `587`
- Other providers: Use their SMTP credentials
- **Testing without SMTP set up?** That's fine — registration, login, and
  checkout all work normally even with no SMTP configured. Welcome/order
  emails just won't send (silently logged, never blocks the user). For the
  **forgot-password OTP flow specifically**, if SMTP isn't configured the
  6-digit OTP is shown directly on screen in a dev banner (only while
  `APP_DEBUG` is on, i.e. on localhost) so you can still test it end-to-end.

#### Google Reviews
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project → enable **Places API**
3. Create an API key (restrict to your domain)
4. Find your Google Place ID at [developers.google.com/maps/documentation/places/web-service/place-id](https://developers.google.com/maps/documentation/places/web-service/place-id)
5. Paste both into Admin → Store Settings
6. Click **Refresh Reviews** on the dashboard

#### Smartsupp (Live Chat)
1. Sign up at [smartsupp.com](https://smartsupp.com)
2. Go to Settings → Chat Code → copy your key
3. Paste in Admin → Store Settings → Smartsupp Key

---

### 9. Set Up Google Reviews Cron Job

To keep reviews auto-refreshed, add a cron job:

```bash
# Refresh Google reviews every 12 hours
0 */12 * * * php /var/www/html/steadyvolt/api/google_reviews.php >> /dev/null 2>&1
```

---

### 10. Configure Products

1. Go to **Admin → Categories** — edit names/icons as needed
2. Go to **Admin → Products → Add Product**
3. Upload images, set price, stock, category, specs
4. Mark important products as **Featured** to show on homepage

---

### 11. Configure Chatbot

Go to **Admin → Chatbot Replies** to:
- Edit existing keyword triggers
- Add new Q&A pairs
- Set priority (higher = matched first)
- Enable/disable individual replies

---

## 🔐 Security Checklist (Before Going Live)

- [ ] Set `APP_DEBUG` to `false` in `config.php`
- [ ] Change default admin password
- [ ] Use HTTPS (SSL certificate)
- [ ] Set strong DB password
- [ ] Restrict Paystack API key to your domain
- [ ] Restrict Google API key to your domain
- [ ] Set correct file permissions on `uploads/`
- [ ] Keep `vendor/` and `includes/` out of public web root (`.htaccess` handles this)

---

## 📁 Key Files Reference

| File | Purpose |
|---|---|
| `includes/config.php` | DB credentials, base URL, debug flag |
| `includes/db.php` | PDO database singleton |
| `includes/functions.php` | Auth, cart, helpers, formatting |
| `includes/mailer.php` | PHPMailer wrapper, OTP, order emails |
| `includes/header.php` | Site-wide header & nav |
| `includes/footer.php` | Site-wide footer, chatbot, Smartsupp |
| `api/auth.php` | Login, register, logout, OTP flow |
| `api/cart.php` | Cart AJAX API |
| `api/payment.php` | Paystack initialize, verify, webhook |
| `api/chatbot.php` | Chatbot AJAX API |
| `api/google_reviews.php` | Sync Google reviews to DB cache |
| `api/search.php` | Live search AJAX |
| `api/reviews.php` | Submit product review |
| `api/wishlist.php` | Wishlist toggle |
| `admin/settings.php` | All store settings UI |
| `admin/products.php` | Product CRUD with image upload |
| `admin/orders.php` | Order management |
| `admin/chatbot.php` | Chatbot Q&A management |
| `database.sql` | Full DB schema + seed data |

---

## 💳 Payment Flow

```
Customer adds items → Cart → Login required → Fill delivery details
→ Enter coupon (optional) → Click "Pay Securely"
→ Paystack checkout page (card / bank transfer / USSD)
→ Paystack redirects back to /api/payment.php?action=verify
→ Order marked as PAID → Confirmation email sent → Cart cleared
```

Paystack also sends a **webhook** (`/api/payment.php?action=webhook`) which double-confirms payment even if the redirect fails.

---

## 📧 OTP Password Reset Flow

```
Click "Forgot Password" → Enter email
→ 6-digit OTP sent via email (expires in 15 min, configurable)
→ Enter OTP on-screen digit-by-digit → Verified
→ Set new password → Done
```

---

## 🤖 Chatbot Logic

The built-in chatbot:
1. Takes the user's message and lowercases it
2. Scans all active `chatbot_responses` rows (sorted by priority DESC)
3. First keyword match wins → sends that response
4. If no match, searches products by name
5. If still no match, falls back to contact info

All responses can contain HTML (links, bold, line breaks).

---

## 📞 Support / Customisation

For any questions about this codebase, contact your developer or reach SteadyVolt support at the configured store email.

---

*Built with PHP 8.1, MySQL, Paystack, PHPMailer, Google Places API, Smartsupp, Font Awesome 5*
