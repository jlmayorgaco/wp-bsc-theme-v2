document.addEventListener('DOMContentLoaded', () => {
  const headers = document.querySelectorAll('.tab__header');
  const contents = document.querySelectorAll('.tab__content');

  headers.forEach(header => {
    header.addEventListener('click', () => {
      const tabName = header.dataset.tabName;

      // Prevent re-triggering if already active
      if (header.classList.contains('active')) return;

      // Deactivate all headers
      headers.forEach(h => h.classList.remove('active'));

      // Hide all tab content gracefully
      contents.forEach(content => {
        content.classList.remove('active');
        content.classList.remove('fade-in');
      });

      // Activate selected tab
      header.classList.add('active');
      const targetContent = document.querySelector(`.tab__content[data-tab-name="${tabName}"]`);
      targetContent.classList.add('active');

      // Slight delay to ensure transition is picked up
      requestAnimationFrame(() => {
        targetContent.classList.add('fade-in');
      });

      // BSC-012: smooth scroll to products area with sticky header offset
      const tabsContent = document.querySelector('.tabs__content');
      if (tabsContent) {
        const mobileHeader = document.getElementById('mobileHeader');
        const desktopHeader = document.querySelector('.bsc__header--desktop');
        const headerHeight = (mobileHeader && mobileHeader.offsetHeight > 0)
          ? mobileHeader.offsetHeight
          : (desktopHeader ? desktopHeader.offsetHeight : 0);
        const targetY = tabsContent.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
        window.scrollTo({ top: targetY, behavior: 'smooth' });
      }
    });
  });

  // Optional: preload hover images if you're using image src switching
  document.querySelectorAll('.tab__icon').forEach(img => {
    const normalSrc = img.dataset.srcNormal;
    const hoverSrc = img.dataset.srcHover;

    if (normalSrc && hoverSrc) {
      img.src = normalSrc;

      // Preload hover image
      const preload = new Image();
      preload.src = hoverSrc;

      img.addEventListener('mouseover', () => img.src = hoverSrc);
      img.addEventListener('mouseout', () => img.src = normalSrc);
    }
  });
});
