document.querySelectorAll('.bsc__menu-nav--button').forEach((btn) => {
  const targetId = btn.dataset.target;
  const targetMenu = document.getElementById(targetId);
  if (!targetMenu) return;

  let hideTimeout;

  const showMenu = () => {
    clearTimeout(hideTimeout);
    targetMenu.classList.add('visible');
  };

  const delayedHideMenu = () => {
    hideTimeout = setTimeout(() => {
      const isHoveringBtn = btn.matches(':hover');
      const isHoveringMenu = targetMenu.matches(':hover');
      if (!isHoveringBtn && !isHoveringMenu) {
        targetMenu.classList.remove('visible');
      }
    }, 200); // Adjust delay if needed
  };

  // Show on hover
  btn.addEventListener('mouseenter', showMenu);
  targetMenu.addEventListener('mouseenter', showMenu);

  // Delay hide if mouse leaves both button and menu
  btn.addEventListener('mouseleave', delayedHideMenu);
  targetMenu.addEventListener('mouseleave', delayedHideMenu);
});



document.addEventListener('DOMContentLoaded', () => {
  const profileBtn = document.getElementById('profile-button');
  const dropdown = document.getElementById('profile-dropdown');

  let hoverTimeout;

  // Show dropdown on hover
  profileBtn.addEventListener('mouseenter', () => {
    clearTimeout(hoverTimeout);
    dropdown.classList.add('visible');
  });

  dropdown.addEventListener('mouseenter', () => {
    clearTimeout(hoverTimeout);
    dropdown.classList.add('visible');
  });

  // Hide dropdown when leaving both
  profileBtn.addEventListener('mouseleave', () => {
    hoverTimeout = setTimeout(() => {
      dropdown.classList.remove('visible');
    }, 200); // slight delay to allow moving between btn and dropdown
  });

  dropdown.addEventListener('mouseleave', () => {
    hoverTimeout = setTimeout(() => {
      dropdown.classList.remove('visible');
    }, 200);
  });
});
