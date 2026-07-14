-- ============================================================
--  STEADYVOLT ENERGY — Complete Database Schema
--  MySQL 8.0+ / MariaDB 10.5+
-- ============================================================

CREATE DATABASE IF NOT EXISTS steadyvolt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE steadyvolt;

-- -------------------------------------------------------
-- USERS
-- -------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  email         VARCHAR(191) NOT NULL UNIQUE,
  phone         VARCHAR(20),
  password_hash VARCHAR(255) NOT NULL,
  avatar        VARCHAR(255) DEFAULT NULL,
  role          ENUM('customer','admin') DEFAULT 'customer',
  is_active     TINYINT(1) DEFAULT 1,
  email_verified TINYINT(1) DEFAULT 0,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PASSWORD RESET OTP
-- -------------------------------------------------------
CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(191) NOT NULL,
  otp        VARCHAR(6)   NOT NULL,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   DEFAULT 0,
  created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email)
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ADDRESSES
-- -------------------------------------------------------
CREATE TABLE addresses (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  label        VARCHAR(50) DEFAULT 'Home',
  full_name    VARCHAR(160),
  phone        VARCHAR(20),
  address_line VARCHAR(255),
  city         VARCHAR(100),
  state        VARCHAR(100),
  is_default   TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- CATEGORIES
-- -------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  description TEXT,
  icon        VARCHAR(50) DEFAULT 'fa-box',
  sort_order  INT DEFAULT 0
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- BRANDS
-- -------------------------------------------------------
CREATE TABLE brands (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PRODUCTS
-- -------------------------------------------------------
CREATE TABLE products (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id     INT UNSIGNED,
  brand_id        INT UNSIGNED,
  name            VARCHAR(255) NOT NULL,
  slug            VARCHAR(280) NOT NULL UNIQUE,
  short_desc      VARCHAR(500),
  description     LONGTEXT,
  sku             VARCHAR(80) UNIQUE,
  price           DECIMAL(12,2) NOT NULL,
  compare_price   DECIMAL(12,2) DEFAULT NULL,
  stock           INT DEFAULT 0,
  low_stock_alert INT DEFAULT 5,
  weight_kg       DECIMAL(6,2) DEFAULT 0,
  is_featured     TINYINT(1) DEFAULT 0,
  is_active       TINYINT(1) DEFAULT 1,
  meta_title      VARCHAR(255),
  meta_desc       VARCHAR(500),
  rating_override       DECIMAL(2,1) DEFAULT NULL COMMENT 'Admin-set star rating shown instead of the review average. NULL = use real reviews.',
  rating_override_count INT UNSIGNED DEFAULT NULL COMMENT 'Optional display count shown next to the override rating.',
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (brand_id)    REFERENCES brands(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PRODUCT IMAGES
-- -------------------------------------------------------
CREATE TABLE product_images (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  filename   VARCHAR(255) NOT NULL,
  is_primary TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PRODUCT SPECS (key-value)
-- -------------------------------------------------------
CREATE TABLE product_specs (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  spec_key   VARCHAR(100) NOT NULL,
  spec_value VARCHAR(255) NOT NULL,
  sort_order INT DEFAULT 0,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- WISHLIST
-- -------------------------------------------------------
CREATE TABLE wishlist (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  added_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wish (user_id, product_id),
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDERS
-- -------------------------------------------------------
CREATE TABLE orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number     VARCHAR(20) NOT NULL UNIQUE,
  user_id          INT UNSIGNED DEFAULT NULL,
  guest_email      VARCHAR(191) DEFAULT NULL,
  guest_name       VARCHAR(160) DEFAULT NULL,
  guest_phone      VARCHAR(20)  DEFAULT NULL,
  status           ENUM('pending','paid','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  subtotal         DECIMAL(12,2) NOT NULL,
  shipping_fee     DECIMAL(12,2) DEFAULT 0,
  discount_amount  DECIMAL(12,2) DEFAULT 0,
  total            DECIMAL(12,2) NOT NULL,
  coupon_code      VARCHAR(50) DEFAULT NULL,
  -- Shipping address snapshot
  ship_name        VARCHAR(160),
  ship_phone       VARCHAR(20),
  ship_address     VARCHAR(255),
  ship_city        VARCHAR(100),
  ship_state       VARCHAR(100),
  -- Payment
  payment_method   VARCHAR(50) DEFAULT 'paystack',
  payment_ref      VARCHAR(200) DEFAULT NULL,
  payment_status   ENUM('unpaid','paid','failed','refunded') DEFAULT 'unpaid',
  paid_at          DATETIME DEFAULT NULL,
  -- Tracking
  tracking_number  VARCHAR(100) DEFAULT NULL,
  shipped_at       DATETIME DEFAULT NULL,
  delivered_at     DATETIME DEFAULT NULL,
  notes            TEXT,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- ORDER ITEMS
-- -------------------------------------------------------
CREATE TABLE order_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED DEFAULT NULL,
  product_name VARCHAR(255) NOT NULL,
  product_sku  VARCHAR(80),
  product_img  VARCHAR(255),
  qty          INT NOT NULL DEFAULT 1,
  unit_price   DECIMAL(12,2) NOT NULL,
  total_price  DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- PRODUCT REVIEWS
-- -------------------------------------------------------
CREATE TABLE reviews (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id   INT UNSIGNED NOT NULL,
  user_id      INT UNSIGNED DEFAULT NULL,
  guest_name   VARCHAR(100) DEFAULT NULL,
  rating       TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  title        VARCHAR(200),
  body         TEXT,
  is_approved  TINYINT(1) DEFAULT 0,
  is_staff     TINYINT(1) DEFAULT 0 COMMENT '1 = written manually by an admin (shown with a "SteadyVolt Team" badge), not a real customer submission',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- COUPONS
-- -------------------------------------------------------
CREATE TABLE coupons (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(50) NOT NULL UNIQUE,
  type          ENUM('percent','fixed') DEFAULT 'percent',
  value         DECIMAL(10,2) NOT NULL,
  min_order     DECIMAL(12,2) DEFAULT 0,
  max_uses      INT DEFAULT 0 COMMENT '0 = unlimited',
  used_count    INT DEFAULT 0,
  expires_at    DATETIME DEFAULT NULL,
  is_active     TINYINT(1) DEFAULT 1,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- CHATBOT Q&A
-- -------------------------------------------------------
CREATE TABLE chatbot_responses (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  keywords TEXT NOT NULL COMMENT 'comma-separated trigger words',
  response TEXT NOT NULL,
  priority INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- SITE SETTINGS (key-value store)
-- -------------------------------------------------------
CREATE TABLE settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value TEXT,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- GOOGLE REVIEWS CACHE
-- -------------------------------------------------------
CREATE TABLE google_reviews_cache (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  review_data LONGTEXT NOT NULL,
  cached_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- CONTACT MESSAGES
-- -------------------------------------------------------
CREATE TABLE contact_messages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(160),
  email      VARCHAR(191),
  phone      VARCHAR(20),
  subject    VARCHAR(200),
  message    TEXT,
  is_read    TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- SHIPPING ZONES
-- -------------------------------------------------------
CREATE TABLE shipping_zones (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  zone_name  VARCHAR(100) NOT NULL,
  states     TEXT COMMENT 'comma-separated state names',
  fee        DECIMAL(10,2) NOT NULL,
  min_days   INT DEFAULT 1,
  max_days   INT DEFAULT 5
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- DEFAULT DATA
-- -------------------------------------------------------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name',         'SteadyVolt Energy'),
('site_tagline',      'Power Your Future with Sustainable Technology'),
('site_email',        'hello@steadyvolt.ng'),
('site_phone',        '+234 800 8658732'),
('site_address',      '14 Adeola Odeku Street, Victoria Island, Lagos'),
('site_whatsapp',     '+2348008658732'),
('currency_symbol',   '₦'),
('currency_code',     'NGN'),
('free_shipping_min', '150000'),
('paystack_public_key', ''),
('paystack_secret_key', ''),
('google_places_api_key', ''),
('google_place_id',   ''),
('smartsupp_key',     ''),
('smtp_host',         'smtp.gmail.com'),
('smtp_port',         '587'),
('smtp_user',         ''),
('smtp_pass',         ''),
('smtp_from_name',    'SteadyVolt Energy'),
('smtp_from_email',   'noreply@steadyvolt.ng'),
('otp_expiry_minutes','15'),
('announcement_bar',  'Free shipping on orders over ₦150,000 | Shop Now'),
('facebook_url',      ''),
('instagram_url',     ''),
('twitter_url',       ''),
('whatsapp_url',      ''),
('chatbot_welcome',   'Hi! Welcome to SteadyVolt Energy 👋 How can I help you today?'),
('tax_rate',          '0'),
('maintenance_mode',  '0'),
('google_reviews_cache_hours', '12');

INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Solar Systems',      'solar',     'Complete solar panels, kits and accessories', 'fa-sun',         1),
('Deep Cycle Batteries','batteries', 'Lithium, AGM & GEL batteries',               'fa-battery-full',2),
('Smart Cameras',      'cameras',   'IP, CCTV & AI surveillance cameras',          'fa-video',       3),
('Power Inverters',    'inverters', 'Pure sine wave & modified inverters',          'fa-bolt',        4),
('Accessories',        'accessories','Cables, mounts, charge controllers',          'fa-plug',        5);

INSERT INTO shipping_zones (zone_name, states, fee, min_days, max_days) VALUES
('Lagos', 'Lagos', 2500, 1, 2),
('South West', 'Ogun,Oyo,Osun,Ekiti,Ondo', 4000, 2, 4),
('South East', 'Enugu,Anambra,Imo,Abia,Ebonyi', 5000, 3, 5),
('South South', 'Rivers,Delta,Bayelsa,Akwa Ibom,Cross River,Edo', 5500, 3, 6),
('North Central', 'FCT,Benue,Kogi,Kwara,Nassarawa,Niger,Plateau', 6000, 3, 7),
('North West', 'Kano,Kaduna,Katsina,Sokoto,Jigawa,Zamfara,Kebbi', 7000, 4, 8),
('North East', 'Adamawa,Bauchi,Borno,Gombe,Taraba,Yobe', 7500, 5, 9);

INSERT INTO chatbot_responses (keywords, response, priority) VALUES
('hello,hi,hey,good morning,good afternoon,good evening', 'Hello! Welcome to SteadyVolt Energy 🌿 How can I help you today? You can ask me about our products, pricing, delivery, or warranty.', 10),
('solar,panel,solar panel,solar system', 'We stock premium solar panels from top brands — mono-crystalline and poly-crystalline from 100W to 400W per panel. Complete kits also available! Visit our <a href="/solar.php">Solar page</a> to explore.', 8),
('battery,lithium,agm,gel,deep cycle', 'Our battery range includes Lithium, AGM, and GEL deep-cycle batteries from 100Ah to 200Ah. Perfect for home and commercial backup. See our <a href="/batteries.php">Batteries page</a>.', 8),
('camera,cctv,surveillance,security camera', 'We offer IP cameras, CCTV kits, solar-powered cameras, 4G cameras and AI surveillance systems. Check our <a href="/cameras.php">Cameras page</a>.', 8),
('inverter,power inverter,sine wave', 'We carry pure sine wave and modified sine wave inverters from 1kVA to 10kVA. Great for homes and businesses. Visit <a href="/shop.php">our shop</a>.', 8),
('price,cost,how much,pricing', 'Our prices are competitive and in Nigerian Naira (₦). All prices are on our product pages. We also offer bulk discounts — contact us via WhatsApp for a quote!', 7),
('delivery,shipping,how long', 'We deliver nationwide! Lagos: 1-2 days | South West: 2-4 days | Other regions: 3-8 days. Free shipping on orders above ₦150,000.', 7),
('warranty,guarantee', 'All our products come with manufacturer warranties — Solar panels: 25 years performance, 10 years product. Batteries: 2-3 years. Cameras: 1-2 years. Inverters: 1-2 years.', 7),
('payment,pay,how to pay,paystack', 'We accept payment via Paystack (debit/credit card, bank transfer, USSD). Your payment is 100% secure and encrypted.', 6),
('return,refund,exchange', 'We have a 30-day return policy for defective products. Contact us within 30 days of delivery with your order number and reason.', 6),
('contact,phone,email,whatsapp,reach you', 'You can reach us via:\n📞 Phone/WhatsApp: +234 800 8658732\n📧 Email: hello@steadyvolt.ng\n📍 Address: 14 Adeola Odeku Street, VI, Lagos\n⏰ Hours: Mon-Sat 8am-7pm, Sun 10am-4pm', 5),
('track,order status,where is my order', 'You can track your order on the <a href="/pages/track-order.php">Track Order page</a>. You need your order number which was sent to your email.', 5),
('account,login,register,sign up', 'You can browse and add to cart without an account. To checkout, simply <a href="/pages/login.php">sign in</a> or <a href="/pages/signup.php">create a free account</a> — it only takes 30 seconds!', 5),
('installation,install,setup', 'We provide an installation guide on our website. We also have certified engineers available for on-site installation in Lagos, Abuja, and Port Harcourt.', 4),
('thank,thanks,okay,ok,great', 'You are welcome! 😊 Is there anything else I can help you with?', 3),
('bye,goodbye,see you', 'Thank you for visiting SteadyVolt Energy! Have a great day ☀️', 3);

-- Create default admin user (password: Admin@SteadyVolt1)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role, is_active, email_verified)
VALUES ('Admin', 'SteadyVolt', 'admin@steadyvolt.ng', '+2348001234567',
  '$2b$12$wu3OTYH8TRpfWH8SK.kL8OGWfsIktkTi2faWQfs1JacOkMjvnhVna', 'admin', 1, 1);
-- Note: password hash above is bcrypt of 'Admin@SteadyVolt1' — CHANGE AFTER FIRST LOGIN
