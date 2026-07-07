(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('loginform');
    var userLogin = document.getElementById('user_login');
    var userPass = document.getElementById('user_pass');
    var passwordToggles = document.querySelectorAll('.bsc-password-toggle');

    if (!form || !userLogin || !userPass) {
      return;
    }

    initPasswordToggles(passwordToggles);

    form.addEventListener('submit', function (event) {
      var hasError = false;

      clearError(userLogin, 'error_user_login');
      clearError(userPass, 'error_user_pass');

      if (userLogin.value.trim() === '') {
        showError(userLogin, 'error_user_login', 'Por favor ingresa tu correo o usuario.');
        hasError = true;
      }

      if (userPass.value.trim() === '') {
        showError(userPass, 'error_user_pass', 'Por favor ingresa tu contraseña.');
        hasError = true;
      }

      if (hasError) {
        event.preventDefault();
      }
    });

    function initPasswordToggles(toggles) {
      var index = 0;

      for (index = 0; index < toggles.length; index++) {
        bindPasswordToggle(toggles[index]);
      }
    }

    function bindPasswordToggle(button) {
      var targetId = button.getAttribute('data-target');
      var passwordInput = targetId ? document.getElementById(targetId) : null;
      var icon = button.querySelector('i');
      var label = button.querySelector('.bsc-password-toggle__label');

      if (!passwordInput || !icon || !label) {
        return;
      }

      button.addEventListener('click', function () {
        var isVisible = passwordInput.type === 'text';

        if (isVisible) {
          passwordInput.type = 'password';
        } else {
          passwordInput.type = 'text';
        }

        updatePasswordToggle(button, !isVisible);
      });

      updatePasswordToggle(button, false);
    }

    function updatePasswordToggle(button, isVisible) {
      var icon = button.querySelector('i');
      var label = button.querySelector('.bsc-password-toggle__label');
      var ariaLabel = isVisible ? 'Ocultar contrase\u00f1a.' : 'Mostrar contrase\u00f1a.';

      if (!icon || !label) {
        return;
      }

      button.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
      button.setAttribute('aria-label', ariaLabel);
      icon.className = isVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
      label.textContent = ariaLabel;
    }

    function showError(input, errorId, message) {
      input.classList.add('form__input--invalid');

      var errorDiv = document.getElementById(errorId);

      if (!errorDiv) {
        return;
      }

      errorDiv.textContent = message;
      errorDiv.style.display = 'block';
    }

    function clearError(input, errorId) {
      input.classList.remove('form__input--invalid');

      var errorDiv = document.getElementById(errorId);

      if (!errorDiv) {
        return;
      }

      errorDiv.textContent = '';
      errorDiv.style.display = 'none';
    }
  });
})();
