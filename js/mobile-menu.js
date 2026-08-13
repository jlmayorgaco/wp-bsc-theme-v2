/**
 * BSC Mobile Menu — toggle sidebar open/close.
 * Extracted from header.php inline script for cacheability.
 */
(function () {
  'use strict';

  var menuToggleBtn = document.getElementById('mobileMenuToggle');
  var mobileHeader  = document.getElementById('mobileHeader');
  var sidebar       = document.getElementById('mobileSidebar');
  var body          = document.body;
  var root          = document.documentElement;
  var offsetFrameId = 0;

  if (!menuToggleBtn || !mobileHeader || !sidebar) return;

  function syncMobileOverlayTop() {
    offsetFrameId = 0;

    var headerBottom = Math.max(0, Math.ceil(mobileHeader.getBoundingClientRect().bottom));
    root.style.setProperty('--header-mobile-overlay-top', headerBottom + 'px');
  }

  function requestOverlayTopSync() {
    if (offsetFrameId) return;

    offsetFrameId = window.requestAnimationFrame(syncMobileOverlayTop);
  }

  function setMobileMenuState(isOpen) {
    sidebar.classList.toggle('is-open', isOpen);
    body.classList.toggle('mobile-menu-open', isOpen);
    sidebar.setAttribute('aria-hidden', String(!isOpen));
    sidebar.inert = !isOpen;
    menuToggleBtn.setAttribute('aria-expanded', String(isOpen));
    menuToggleBtn.setAttribute('aria-label', isOpen ? 'Cerrar menú' : 'Abrir menú');

    requestOverlayTopSync();
  }

  function toggleMobileMenu() {
    setMobileMenuState(!sidebar.classList.contains('is-open'));
  }

  menuToggleBtn.addEventListener('click', toggleMobileMenu);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
      setMobileMenuState(false);
      menuToggleBtn.focus();
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

  window.addEventListener('scroll', requestOverlayTopSync, { passive: true });
  window.addEventListener('resize', function () {
    if (window.getComputedStyle(mobileHeader).display === 'none') {
      setMobileMenuState(false);
    }

    requestOverlayTopSync();
  }, { passive: true });
  window.addEventListener('orientationchange', requestOverlayTopSync);
  window.addEventListener('load', requestOverlayTopSync);

  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', requestOverlayTopSync);
  }

  if ('ResizeObserver' in window) {
    var headerResizeObserver = new ResizeObserver(requestOverlayTopSync);
    headerResizeObserver.observe(mobileHeader);

    var adminBar = document.getElementById('wpadminbar');
    if (adminBar) headerResizeObserver.observe(adminBar);
  }

  syncMobileOverlayTop();
}());
