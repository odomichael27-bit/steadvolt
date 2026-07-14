<?php
// ============================================================
//  STEADVOLT — Email Service (uses PHPMailer via Composer)
//  File: includes/mailer.php
//  Run: composer require phpmailer/phpmailer
// ============================================================
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

// Auto-load Composer if present
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

class Mailer {

    /**
     * Send an email using settings from DB.
     * Fails gracefully (returns false, logs a message) if PHPMailer hasn't
     * been installed yet via `composer install` — so registration, checkout,
     * and contact forms keep working even before email is set up.
     */
    public static function send(
        string $to_email,
        string $to_name,
        string $subject,
        string $html_body,
        string $text_body = ''
    ): bool {
        if (!class_exists(PHPMailer::class)) {
            error_log('SteadVolt Mailer: PHPMailer not installed. Run "composer install" to enable emails (OTP, order confirmations, etc). Email skipped: ' . $subject);
            return false;
        }

        $host      = DB::setting('smtp_host',       'smtp.gmail.com');
        $port      = (int)DB::setting('smtp_port',  '587');
        $user      = DB::setting('smtp_user',       '');
        $pass      = DB::setting('smtp_pass',       '');
        $from_name = DB::setting('smtp_from_name',  'SteadVolt Energy');
        $from_email= DB::setting('smtp_from_email', 'noreply@steadvolt.ng');

        if (empty($user) || empty($pass)) {
            error_log('SteadVolt Mailer: SMTP credentials not configured.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host        = $host;
            $mail->SMTPAuth    = true;
            $mail->Username    = $user;
            $mail->Password    = $pass;
            $mail->SMTPSecure  = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port        = $port;
            $mail->CharSet     = 'UTF-8';

            $mail->setFrom($from_email, $from_name);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo($from_email, $from_name);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            $mail->AltBody = $text_body ?: strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html_body));

            $mail->send();
            return true;
        } catch (MailException $e) {
            error_log('SteadVolt Mailer error: ' . $e->getMessage());
            return false;
        }
    }

    // ---- OTP Password Reset ---------------------------------
    public static function sendOtp(string $email, string $name): array {
        // Invalidate previous OTPs
        DB::query("UPDATE password_resets SET used=1 WHERE email=?", [$email]);

        $otp     = generate_otp();
        $expiry  = (int)DB::setting('otp_expiry_minutes', '15');
        $expires = date('Y-m-d H:i:s', time() + $expiry * 60);

        DB::query(
            "INSERT INTO password_resets (email, otp, expires_at) VALUES (?,?,?)",
            [$email, $otp, $expires]
        );

        $site  = DB::setting('site_name', 'SteadVolt Energy');
        $html  = self::otpTemplate($name, $otp, $expiry, $site);
        $ok    = self::send($email, $name, 'Your Password Reset OTP — ' . $site, $html);

        return ['sent' => $ok, 'otp' => $otp]; // otp only for dev logging; never expose to client
    }

    public static function verifyOtp(string $email, string $otp): bool {
        $row = DB::row(
            "SELECT id FROM password_resets
             WHERE email=? AND otp=? AND used=0 AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1",
            [$email, $otp]
        );
        if ($row) {
            DB::query("UPDATE password_resets SET used=1 WHERE id=?", [$row['id']]);
            return true;
        }
        return false;
    }

    // ---- Order Confirmation ---------------------------------
    public static function sendOrderConfirmation(array $order, array $items): bool {
        $email = $order['user_email'] ?? $order['guest_email'] ?? '';
        $name  = $order['user_name']  ?? $order['guest_name']  ?? 'Customer';
        if (!$email) return false;

        $site = DB::setting('site_name', 'SteadVolt Energy');
        $html = self::orderTemplate($order, $items, $site);
        return self::send($email, $name, 'Order Confirmed #' . $order['order_number'] . ' — ' . $site, $html);
    }

    // ---- Welcome email -------------------------------------
    public static function sendWelcome(string $email, string $name): bool {
        $site = DB::setting('site_name', 'SteadVolt Energy');
        $html = self::welcomeTemplate($name, $site);
        return self::send($email, $name, 'Welcome to ' . $site . '!', $html);
    }

    // ---- Email Templates -----------------------------------
    private static function emailWrapper(string $content, string $site): string {
        $green = '#00A86B'; $forest = '#1B4D3E';
        return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body{margin:0;padding:0;background:#f4f7f5;font-family:Inter,Arial,sans-serif;color:#2D3748}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.header{background:{$forest};padding:28px 32px;text-align:center}
.header .logo{display:inline-flex;align-items:center;gap:10px;color:#fff;font-size:1.4rem;font-weight:800}
.header .logo span{color:{$green}}
.body{padding:32px}
.btn{display:inline-block;background:{$green};color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;margin:16px 0}
.footer{background:#f4f7f5;padding:20px 32px;text-align:center;font-size:0.8rem;color:#718096}
</style></head><body>
<div class="wrap">
<div class="header"><div class="logo">⚡ Volt<span>Peak</span></div></div>
<div class="body">{$content}</div>
<div class="footer">© {year} {$site} · <a href="mailto:hello@steadvolt.ng" style="color:{$green}">hello@steadvolt.ng</a><br>14 Adeola Odeku Street, Victoria Island, Lagos</div>
</div></body></html>
HTML;
    }

    private static function otpTemplate(string $name, string $otp, int $expiry, string $site): string {
        $digits = '';
        foreach (str_split($otp) as $d) {
            $digits .= "<span style='display:inline-block;background:#f4f7f5;border:2px solid #e2e8f0;border-radius:8px;width:40px;height:50px;line-height:50px;text-align:center;font-size:1.6rem;font-weight:800;color:#1B4D3E;margin:3px'>{$d}</span>";
        }
        $content = <<<HTML
<h2 style="margin:0 0 8px;color:#1B4D3E">Password Reset OTP</h2>
<p>Hi {$name},</p>
<p>Use the 6-digit OTP below to reset your SteadVolt password. It expires in <strong>{$expiry} minutes</strong>.</p>
<div style="text-align:center;margin:28px 0">{$digits}</div>
<p>If you did not request this, you can safely ignore this email.</p>
<p style="font-size:0.85rem;color:#718096">For security, never share this code with anyone — SteadVolt will never ask for it.</p>
HTML;
        return self::emailWrapper($content, $site);
    }

    private static function orderTemplate(array $order, array $items, string $site): string {
        $rows = '';
        foreach ($items as $it) {
            $rows .= "<tr>
              <td style='padding:10px 0;border-bottom:1px solid #e2e8f0'>{$it['product_name']} × {$it['qty']}</td>
              <td style='padding:10px 0;border-bottom:1px solid #e2e8f0;text-align:right'>₦" . number_format($it['total_price'],2) . "</td>
            </tr>";
        }
        $total = '₦' . number_format($order['total'], 2);
        $num   = $order['order_number'];
        $url   = BASE_URL . '/pages/track-order.php?order=' . $num;
        $content = <<<HTML
<h2 style="color:#1B4D3E">Order Confirmed! 🎉</h2>
<p>Thank you for your purchase. We've received your order and will begin processing it shortly.</p>
<p><strong>Order Number:</strong> {$num}</p>
<table width="100%" style="border-collapse:collapse">{$rows}
<tr><td colspan="2" style="padding:12px 0"><strong>Total: {$total}</strong></td></tr>
</table>
<p style="text-align:center"><a href="{$url}" class="btn">Track Your Order</a></p>
<p>Questions? Reply to this email or WhatsApp us: +234 800 8658732</p>
HTML;
        return self::emailWrapper($content, $site);
    }

    private static function welcomeTemplate(string $name, string $site): string {
        $url = BASE_URL . '/shop.php';
        $content = <<<HTML
<h2 style="color:#1B4D3E">Welcome to {$site}! ⚡</h2>
<p>Hi {$name},</p>
<p>Your account has been created successfully. We're Nigeria's trusted source for solar energy, batteries, inverters, and smart cameras.</p>
<p style="text-align:center"><a href="{$url}" class="btn">Start Shopping</a></p>
<p>If you have any questions, our team is available 24/7.</p>
HTML;
        return self::emailWrapper($content, $site);
    }
}
