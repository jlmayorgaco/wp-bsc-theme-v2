document.addEventListener('DOMContentLoaded', () => {
  const headers = document.querySelectorAll('.tab__header');
  const contents = document.querySelectorAll('.tab__content');

  document.querySelectorAll('.tabs__header').forEach(list => {
    list.setAttribute('role', 'tablist');
    list.setAttribute('aria-label', 'Favoritos por categoría');
  });
  headers.forEach((header, index) => {
    const panel = Array.from(contents).find(content => content.dataset.tabName === header.dataset.tabName);
    if (!panel) return;
    const selected = header.classList.contains('active');
    header.id = `bsc-tab-${index}`;
    panel.id = `bsc-panel-${index}`;
    header.setAttribute('role', 'tab');
    header.setAttribute('aria-controls', panel.id);
    header.setAttribute('aria-selected', String(selected));
    header.tabIndex = selected ? 0 : -1;
    panel.setAttribute('role', 'tabpanel');
    panel.setAttribute('aria-labelledby', header.id);
    panel.hidden = !selected;
    header.addEventListener('keydown', event => {
      let next = index;
      if (event.key === 'ArrowRight') next = (index + 1) % headers.length;
      else if (event.key === 'ArrowLeft') next = (index + headers.length - 1) % headers.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = headers.length - 1;
      else return;
      event.preventDefault();
      headers[next].focus();
      headers[next].click();
    });
  });

  headers.forEach(header => {
    header.addEventListener('click', () => {
      const tabName = header.dataset.tabName;

      // Prevent re-triggering if already active
      if (header.classList.contains('active')) return;

      // Deactivate all headers
      headers.forEach(h => {
        h.classList.remove('active');
        h.setAttribute('aria-selected', 'false');
        h.tabIndex = -1;
      });

      // Hide all tab content gracefully
      contents.forEach(content => {
        content.classList.remove('active');
        content.classList.remove('fade-in');
        content.hidden = true;
      });

      // Activate selected tab
      header.classList.add('active');
      header.setAttribute('aria-selected', 'true');
      header.tabIndex = 0;
      const targetContent = document.querySelector(`.tab__content[data-tab-name="${tabName}"]`);
      if (!targetContent) return;
      targetContent.hidden = false;
      targetContent.classList.add('active');
      targetContent.querySelectorAll('.swiper').forEach(element => element.swiper?.update());

      // Slight delay to ensure transition is picked up
      requestAnimationFrame(() => {
        targetContent.classList.add('fade-in');
      });

      // BSC-012: smooth scroll to products area — mobile only
      // On desktop the tab content is already visible above the fold
      if (window.matchMedia('(max-width: 768px)').matches && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const tabsContent = document.querySelector('.tabs__content');
        if (tabsContent) {
          const mobileHeader = document.getElementById('mobileHeader');
          const headerHeight = mobileHeader ? mobileHeader.offsetHeight : 0;
          const targetY = tabsContent.getBoundingClientRect().top + window.scrollY - headerHeight - 16;
          window.scrollTo({ top: targetY, behavior: 'smooth' });
        }
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
