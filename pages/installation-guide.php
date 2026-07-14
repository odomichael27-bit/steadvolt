<?php
// ============================================================
//  STEADVOLT — Installation Guide
//  File: pages/installation-guide.php
// ============================================================
require_once dirname(__DIR__) . '/includes/functions.php';
$page_title = 'Installation Guide';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero-sm"><div class="container"><h1><i class="fas fa-tools"></i> Installation Guide</h1><p>Step-by-step guidance for your solar and energy system setup.</p></div></div>

<section class="section-pad"><div class="container" style="max-width:860px">

  <!-- Safety Notice -->
  <div style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:var(--radius-lg);padding:20px 24px;margin-bottom:36px;display:flex;gap:14px;align-items:flex-start">
    <i class="fas fa-exclamation-triangle" style="color:#F59E0B;font-size:1.4rem;flex-shrink:0;margin-top:2px"></i>
    <div>
      <strong>Safety First!</strong>
      <p style="margin:4px 0 0;font-size:.88rem;color:#78350F">Solar and inverter systems involve high voltages. Always hire a certified electrician for DC/AC wiring. Improper installation may void your warranty and pose serious safety risks. Our team is available to assist — call us before you begin.</p>
    </div>
  </div>

  <?php
  $guides = [
    ['fa-sun', 'Solar Panel Installation', [
      'Site Assessment — Identify the best roof or ground location with maximum sun exposure (south-facing in Nigeria). Avoid shading from trees or buildings.',
      'Mount the Racking — Install the mounting rails securely onto the roof using appropriate anchors for your roof type (tile, metal, flat concrete).',
      'Install Panels — Attach the panels to the rails and tighten all clamps to the torque specification in your panel manual.',
      'Wire the Array — Connect panels in series or parallel per your system design. Use UV-rated DC solar cable only. Never exceed your inverter/MPPT input voltage.',
      'Connect to Charge Controller/Inverter — Run cabling to your MPPT charge controller or solar inverter. Observe correct polarity (+/-).',
      'System Test — Check open-circuit voltage with a multimeter before connecting to the battery. Compare with specification. Commission the system.',
    ]],
    ['fa-battery-full', 'Battery Installation', [
      'Choose a Cool, Ventilated Location — Batteries perform best at 20–25°C. Avoid direct sunlight, sealed rooms, and flammable materials.',
      'Lithium Batteries: Upright Only — Install in an upright position. Do not stack unless the manufacturer explicitly allows it.',
      'AGM/GEL Batteries: Any Orientation — These can be installed on their side if space requires, but upright is preferred.',
      'Connect in Series or Parallel — Follow your system voltage and capacity design. Always connect batteries of the same brand, age, and capacity.',
      'Fuse Each Battery String — Install an appropriately rated fuse or circuit breaker between the battery bank and inverter/charger.',
      'Commission Slowly — Charge the bank to 100% before first use. Check voltage, temperature, and BMS status indicators.',
    ]],
    ['fa-bolt', 'Inverter Installation', [
      'Select Location — Install in a cool, dry, ventilated space. Allow at least 30cm clearance around the inverter for airflow.',
      'Wall-Mount Securely — Most inverters must be wall-mounted upright. Use the provided brackets and appropriate wall anchors.',
      'DC Wiring — Connect the battery cables to the inverter DC terminals. Use cable rated for the inverter\'s maximum DC current.',
      'AC Wiring — Connect the inverter AC output to your distribution board via a dedicated circuit breaker. Use a licensed electrician for all AC work.',
      'Solar Input — If your inverter has MPPT inputs, connect the solar array as per step 5 of the solar guide.',
      'Power On and Configure — Switch on, access the inverter settings panel, and configure battery type, capacity, charge voltage, and priority mode.',
    ]],
    ['fa-video', 'Camera / CCTV Installation', [
      'Plan Camera Positions — Cover all entry points, driveways, and vulnerable areas. Cameras should be 2.5–3m high for best coverage.',
      'Run Cables — For wired systems, run CAT5/6 (for IP) or RG59 (for analog) cables from each camera location to the NVR/DVR. Use conduit for outdoor runs.',
      'Mount Cameras — Drill and plug the mounting surface. Attach the bracket and camera. Adjust the angle before tightening.',
      'Connect to NVR/DVR — Plug network cables into the NVR switch ports (PoE) or connect BNC cables to the DVR channels.',
      'Power Up and Configure — Power on the NVR/DVR, follow the setup wizard, set date/time, recording schedules, and motion detection zones.',
      'Remote Access — Install the manufacturer app on your smartphone, scan the QR code from the device settings, and set a strong password for remote viewing.',
    ]],
  ];
  foreach ($guides as $g):
  ?>
  <div class="profile-card" style="margin-bottom:24px">
    <h2 style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
      <span style="width:44px;height:44px;border-radius:var(--radius);background:var(--green-light);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas <?= $g[0] ?>"></i></span>
      <?= $g[1] ?>
    </h2>
    <ol style="padding-left:20px;display:flex;flex-direction:column;gap:12px">
      <?php foreach ($g[2] as $step): ?>
      <li style="font-size:.9rem;color:var(--gray-700);line-height:1.75"><?= $step ?></li>
      <?php endforeach; ?>
    </ol>
  </div>
  <?php endforeach; ?>

  <div style="background:var(--green-light);border-radius:var(--radius-lg);padding:28px;text-align:center">
    <i class="fas fa-headset" style="font-size:2rem;color:var(--green);display:block;margin-bottom:12px"></i>
    <h3 style="margin-bottom:8px">Need On-Site Help?</h3>
    <p style="color:var(--gray-600);margin-bottom:20px">Our certified engineers are available for on-site installation in Lagos, Abuja, and Port Harcourt.</p>
    <a href="<?= BASE_URL ?>/pages/contact.php" class="btn btn-primary"><i class="fas fa-envelope"></i> Book an Engineer</a>
  </div>
</div></section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
