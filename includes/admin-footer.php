<?php
// ============================================================
//  STEADVOLT — Admin Footer
//  File: includes/admin-footer.php
// ============================================================
?>
  </div><!-- .admin-main -->
</div><!-- .admin-shell -->

<script>
const BASE_URL = '<?= BASE_URL ?>';
// Admin sidebar toggle
const sidebar  = document.getElementById('adminSidebar');
const overlay  = document.getElementById('sidebarOverlay');
const hamburger = document.getElementById('adminHamburger');
hamburger?.addEventListener('click', () => {
  sidebar.classList.toggle('open');
  overlay.classList.toggle('open');
});
overlay?.addEventListener('click', () => {
  sidebar.classList.remove('open');
  overlay.classList.remove('open');
});
</script>
<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
<?php if (isset($extra_js)) echo $extra_js; ?>
</body>
</html>
