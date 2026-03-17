/**
 * BSC Mobile Menu — toggle sidebar open/close.
 * Extracted from header.php inline script for cacheability.
 */
(function () {
  'use strict';

  var menuToggleBtn = document.getElementById('mobileMenuToggle');
  var sidebar       = document.getElementById('mobileSidebar');
  var body          = document.body;

  if (!menuToggleBtn || !sidebar) return;

  function setMobileMenuState(isOpen) {
    sidebar.classList.toggle('is-open', isOpen);
    body.classList.toggle('mobile-menu-open', isOpen);
    menuToggleBtn.setAttribute('aria-expanded', String(isOpen));
    menuToggleBtn.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');
  }

  function toggleMobileMenu() {
    setMobileMenuState(!sidebar.classList.contains('is-open'));
  }

  menuToggleBtn.addEventListener('click', toggleMobileMenu);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
      setMobileMenuState(false);
    }
  });

  // Close menu when clicking the overlay (outside sidebar)
  document.addEventListener('click', function (e) {
    if (
      sidebar.classList.contains('is-open') &&
      !sidebar.contains(e.target) &&
      !menuToggleBtn.contains(e.target)
    ) {
      setMobileMenuState(false);
    }
  });
}());
