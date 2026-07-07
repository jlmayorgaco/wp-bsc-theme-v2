(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('registerform');
    var passwordToggles = document.querySelectorAll('.bsc-password-toggle');

    if (!form) {
      return;
    }

    initPasswordToggles(passwordToggles);

    form.addEventListener('submit', function (event) {
      var hasError = false;
      var fields = [
        { id: 'nombres', message: 'Por favor ingresa tu nombre completo.' },
        { id: 'email', message: 'Por favor ingresa un correo válido.' },
        { id: 'password', message: 'Por favor ingresa una contraseña.' },
      ];

      fields.forEach(function (field) {
        var input = document.getElementById(field.id);

        if (!input) {
          return;
        }

        clearError(input, 'error_' + field.id);

        if (input.value.trim() === '') {
          showError(input, 'error_' + field.id, field.message);
          hasError = true;
          return;
        }

        if (field.id === 'email') {
          var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

          if (!emailRegex.test(input.value.trim())) {
            showError(input, 'error_' + field.id, 'El correo no tiene un formato válido.');
            hasError = true;
          }
        }
      });

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
      input.classList.add('bsc__input--invalid');

      var errorDiv = document.getElementById(errorId);

      if (!errorDiv) {
        return;
      }

      errorDiv.textContent = message;
      errorDiv.style.display = 'block';
    }

    function clearError(input, errorId) {
      input.classList.remove('bsc__input--invalid');

      var errorDiv = document.getElementById(errorId);

      if (!errorDiv) {
        return;
      }

      errorDiv.textContent = '';
      errorDiv.style.display = 'none';
    }
  });
})();
