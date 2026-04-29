(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('loginform');
    var userLogin = document.getElementById('user_login');
    var userPass = document.getElementById('user_pass');

    if (!form || !userLogin || !userPass) {
      return;
    }

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
