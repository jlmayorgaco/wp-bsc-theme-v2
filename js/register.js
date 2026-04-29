(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('registerform');

    if (!form) {
      return;
    }

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
