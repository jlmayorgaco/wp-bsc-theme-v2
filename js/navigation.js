document.querySelectorAll('.bsc__menu-nav--button').forEach((btn) => {
  const targetId = btn.dataset.target;
  const targetMenu = document.getElementById(targetId);
  if (!targetMenu) return;

  let hideTimeout;

  const showMenu = () => {
    clearTimeout(hideTimeout);
    targetMenu.hidden = false;
    targetMenu.classList.add('visible');
    btn.setAttribute('aria-expanded', 'true');
  };

  const hideMenu = () => {
    targetMenu.classList.remove('visible');
    targetMenu.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  };

  const delayedHideMenu = () => {
    hideTimeout = setTimeout(() => {
      const isHoveringBtn = btn.matches(':hover');
      const isHoveringMenu = targetMenu.matches(':hover');
      const hasFocus = btn.matches(':focus-visible, :focus') || targetMenu.contains(document.activeElement);
      if (!isHoveringBtn && !isHoveringMenu && !hasFocus) {
        hideMenu();
      }
    }, 200); // Adjust delay if needed
  };

  // Show on hover
  btn.addEventListener('mouseenter', showMenu);
  targetMenu.addEventListener('mouseenter', showMenu);

  // Delay hide if mouse leaves both button and menu
  btn.addEventListener('mouseleave', delayedHideMenu);
  targetMenu.addEventListener('mouseleave', delayedHideMenu);

  btn.addEventListener('focus', showMenu);
  btn.addEventListener('click', (event) => {
    event.preventDefault();
    if (targetMenu.classList.contains('visible')) {
      hideMenu();
    } else {
      showMenu();
    }
  });

  targetMenu.addEventListener('focusout', delayedHideMenu);

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && targetMenu.classList.contains('visible')) {
      hideMenu();
      btn.focus();
    }
  });
});



document.addEventListener('DOMContentLoaded', () => {
  const profileBtn = document.getElementById('profile-button');
  const dropdown = document.getElementById('profile-dropdown');

  if (!profileBtn || !dropdown) return;

  let hoverTimeout;

  const showDropdown = () => {
    clearTimeout(hoverTimeout);
    dropdown.classList.add('visible');
    profileBtn.setAttribute('aria-expanded', 'true');
  };

  const hideDropdown = () => {
    dropdown.classList.remove('visible');
    profileBtn.setAttribute('aria-expanded', 'false');
  };

  // Show dropdown on hover
  profileBtn.addEventListener('mouseenter', showDropdown);

  dropdown.addEventListener('mouseenter', showDropdown);

  // Hide dropdown when leaving both
  profileBtn.addEventListener('mouseleave', () => {
    hoverTimeout = setTimeout(() => {
      if (!dropdown.contains(document.activeElement)) {
        hideDropdown();
      }
    }, 200); // slight delay to allow moving between btn and dropdown
  });

  dropdown.addEventListener('mouseleave', () => {
    hoverTimeout = setTimeout(() => {
      if (document.activeElement !== profileBtn && !dropdown.contains(document.activeElement)) {
        hideDropdown();
      }
    }, 200);
  });

  profileBtn.addEventListener('focus', showDropdown);

  dropdown.addEventListener('focusout', () => {
    window.setTimeout(() => {
      if (document.activeElement !== profileBtn && !dropdown.contains(document.activeElement)) {
        hideDropdown();
      }
    }, 0);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && dropdown.classList.contains('visible')) {
      hideDropdown();
      profileBtn.focus();
    }
  });
});
