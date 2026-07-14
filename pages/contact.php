<?php
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Contact Us';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { flash('contact','Invalid request.','error'); redirect('/pages/contact.php'); }
    $name=trim(post('name')); $email=strtolower(trim(post('email'))); $phone=trim(post('phone')); $subject=trim(post('subject')); $message=trim(post('message'));
    if (!$name || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$message) { flash('contact','Please fill in all required fields.','error'); redirect('/pages/contact.php'); }
    DB::query("INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)",[$name,$email,$phone,$subject,$message]);
    $ae = DB::setting('site_email');
    if ($ae) { require_once dirname(__DIR__).'/includes/mailer.php'; $h="<h2>New Contact</h2><p><b>From:</b> $name &lt;$email&gt;</p><p><b>Phone:</b> $phone</p><p><b>Subject:</b> $subject</p><hr><p>".nl2br(htmlspecialchars($message))."</p>"; Mailer::send($ae,DB::setting('site_name'),'New Contact: '.($subject?:'No subject'),$h); }
    flash('contact','Thank you! Your message has been sent. We will reply within 24 hours.','success');
    redirect('/pages/contact.php');
}
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-hero-sm"><div class="container"><h1><i class="fas fa-envelope"></i> Contact Us</h1><p>We are here to help. Reach out by any channel below.</p></div></div>
<section class="section-pad"><div class="container"><div class="contact-layout">
  <div class="contact-info-col">
    <h2>Get in Touch</h2>
    <p style="color:var(--gray-500);margin-bottom:28px;font-size:.95rem">Whether you need help choosing a solar system, want a bulk quote, or need support — our team is ready.</p>
    <div class="contact-info-cards">
      <div class="contact-info-card"><div class="contact-info-icon"><i class="fas fa-phone-alt"></i></div><div><strong>Phone / WhatsApp</strong><a href="tel:<?=preg_replace('/\s/','',DB::setting('site_phone'))?>"><?=clean(DB::setting('site_phone'))?></a><span>Mon–Sat 8am–7pm | Sun 10am–4pm</span></div></div>
      <div class="contact-info-card"><div class="contact-info-icon"><i class="fas fa-envelope"></i></div><div><strong>Email</strong><a href="mailto:<?=clean(DB::setting('site_email'))?>"><?=clean(DB::setting('site_email'))?></a><span>Reply within 2 business hours</span></div></div>
      <div class="contact-info-card"><div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div><div><strong>Head Office</strong><span><?=clean(DB::setting('site_address'))?></span></div></div>
      <div class="contact-info-card"><div class="contact-info-icon" style="background:#25D366;color:#fff"><i class="fab fa-whatsapp"></i></div><div><strong>WhatsApp</strong><a href="https://wa.me/<?=preg_replace('/\D/','',DB::setting('site_whatsapp'))?>?text=Hello+SteadVolt" target="_blank">Chat with us instantly</a></div></div>
    </div>
    <div class="contact-faq-links">
      <h3>Quick Links</h3>
      <a href="<?=BASE_URL?>/pages/track-order.php"><i class="fas fa-truck"></i> Track My Order</a>
      <a href="<?=BASE_URL?>/pages/faq.php"><i class="fas fa-question-circle"></i> FAQ</a>
      <a href="<?=BASE_URL?>/pages/returns-warranty.php"><i class="fas fa-undo"></i> Returns &amp; Warranty</a>
    </div>
  </div>
  <div class="contact-form-col">
    <div class="profile-card">
      <h2 style="margin-bottom:6px">Send a Message</h2>
      <p style="color:var(--gray-500);font-size:.88rem;margin-bottom:24px">We reply within 24 hours.</p>
      <?= flash_html('contact') ?>
      <form method="POST" novalidate>
        <?= csrf_field() ?>
        <div class="form-row-2">
          <div class="form-group"><label>Full Name *</label><div class="input-wrap"><i class="fas fa-user input-icon"></i><input type="text" name="name" required placeholder="e.g. Emeka Okonkwo" maxlength="160"/></div></div>
          <div class="form-group"><label>Email *</label><div class="input-wrap"><i class="fas fa-envelope input-icon"></i><input type="email" name="email" required placeholder="you@example.com"/></div></div>
        </div>
        <div class="form-row-2">
          <div class="form-group"><label>Phone</label><div class="input-wrap"><i class="fas fa-phone input-icon"></i><input type="tel" name="phone" placeholder="+234 800 0000000"/></div></div>
          <div class="form-group"><label>Subject</label><div class="input-wrap"><i class="fas fa-tag input-icon"></i><input type="text" name="subject" placeholder="e.g. Solar quote" maxlength="200"/></div></div>
        </div>
        <div class="form-group"><label>Message *</label><textarea name="message" rows="6" required placeholder="How can we help you?" maxlength="3000"></textarea></div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px"><i class="fas fa-paper-plane"></i> Send Message</button>
      </form>
    </div>
  </div>
</div></div></section>
<style>.contact-layout{display:grid;grid-template-columns:1fr 1fr;gap:48px}.contact-info-cards{display:flex;flex-direction:column;gap:14px;margin-bottom:28px}.contact-info-card{display:flex;align-items:flex-start;gap:14px;padding:14px;background:var(--off-white);border-radius:var(--radius)}.contact-info-icon{width:44px;height:44px;border-radius:10px;background:var(--green-light);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}.contact-info-card strong{display:block;font-size:.88rem;margin-bottom:3px}.contact-info-card a,.contact-info-card span{display:block;font-size:.83rem;color:var(--gray-600)}.contact-info-card a{color:var(--green)}.contact-faq-links h3{font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;color:var(--gray-500);margin-bottom:10px}.contact-faq-links a{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--gray-100);font-size:.88rem;color:var(--gray-700)}.contact-faq-links a:hover{color:var(--green)}.contact-faq-links i{color:var(--green);width:18px}@media(max-width:900px){.contact-layout{grid-template-columns:1fr}}</style>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
