<?php
/* Template Name: Registro personalizado */
get_header();

$registration_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
  // BSC-043: CSRF protection
  if ( ! isset($_POST['bsc_register_nonce']) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['bsc_register_nonce'])), 'bsc_register_action' ) ) {
    wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
  }

  $nombres   = sanitize_text_field(wp_unslash($_POST['nombres'] ?? ''));
  $email     = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  $password  = $_POST['password'] ?? '';

  if (email_exists($email)) {
    $registration_error = 'Este correo ya está registrado. Intenta iniciar sesión.';
  } elseif (empty($nombres) || empty($email) || empty($password)) {
    $registration_error = 'Por favor completa todos los campos.';
  } else {
    $name_parts = explode(' ', $nombres, 2);
    $first_name = $name_parts[0];
    $last_name  = isset($name_parts[1]) ? $name_parts[1] : '';

    $user_id = wp_insert_user([
      'user_login' => $email,
      'user_pass'  => $password,
      'user_email' => $email,
      'first_name' => $first_name,
      'last_name'  => $last_name,
      'role'       => 'customer',
    ]);

    if (!is_wp_error($user_id)) {
      wp_set_current_user($user_id);
      wp_set_auth_cookie($user_id, true);
      do_action('wp_login', $email, get_user_by('ID', $user_id));
      wp_redirect(home_url('/registro-familia-bubbles/'));
      exit;
    } else {
      $registration_error = 'Hubo un error al crear la cuenta. Intenta con otro correo.';
    }
  }
}
?>

<main class="bsc__auth">
  <div class="bsc__container">
    <div class="bsc__form-image">
      <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/signin_signup/bsc_signup_cover.png" alt="Imagen de fondo de registro" />
    </div>

    <div class="bsc__form-container">
      <div class="bsc__form-logo">
        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/signin_signup/bsc_signin_rainbow.png" alt="Decoración arcoíris registro" />
      </div>

      <h1 class="bsc__form-title">¡Quiero ser parte de BSC!</h1>

      <div class="bsc__form-fields">
        <?php if (!empty($registration_error)) : ?>
          <div class="bsc__error-global"><?php echo esc_html($registration_error); ?></div>
        <?php endif; ?>

        <form name="registerform" id="registerform" method="post" class="form" novalidate>
          <?php wp_nonce_field('bsc_register_action', 'bsc_register_nonce'); ?>
          <div class="bsc__form-field">
            <label for="nombres" class="bsc__label"><strong>Nombres</strong> y Apellidos</label>
            <input type="text" name="nombres" id="nombres" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_nombres"></div>
          </div>

          <div class="bsc__form-field">
            <label for="email" class="bsc__label"><strong>Correo</strong> electrónico</label>
            <input type="email" name="email" id="email" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_email"></div>
          </div>

          <div class="bsc__form-field">
            <label for="password" class="bsc__label"><strong>Contraseña</strong></label>
            <input type="password" name="password" id="password" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_password"></div>
          </div>

          <div class="bsc__form-field bsc__form-field--submit bsc__form-field--auth-offset">
            <input type="submit" id="register-submit" class="bsc__button bsc__button--auth-cta" value="¡Unirme a Bubbles!"/>
          </div>

          <hr class="bsc__auth-divider">

          <div class="bsc__form-field bsc__form-field--submit">
            <a href="<?php echo esc_url(home_url('/login/')); ?>" class="bsc__button bsc__button--auth-cta">
              ¡ Ingresar a mi cuenta !
            </a>
          </div>

          <div class="bsc__form-links">
            <a class="bsc__form-link no-link">¿Ya tienes cuenta? Ingresa a BSC</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerform");

  form.addEventListener("submit", function (e) {
    let hasError = false;

    const fields = [
      { id: "nombres", msg: "Por favor ingresa tu nombre completo." },
      { id: "email", msg: "Por favor ingresa un correo válido." },
      { id: "password", msg: "Por favor ingresa una contraseña." }
    ];

    fields.forEach(field => {
      const input = document.getElementById(field.id);
      clearError(input, `error_${field.id}`);

      if (input.value.trim() === "") {
        showError(input, `error_${field.id}`, field.msg);
        hasError = true;
      }

      if (field.id === "email" && input.value.trim() !== "") {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value.trim())) {
          showError(input, `error_${field.id}`, "El correo no tiene un formato válido.");
          hasError = true;
        }
      }
    });

    if (hasError) e.preventDefault();
  });

  function showError(input, errorId, message) {
    input.classList.add("bsc__input--invalid");
    const errorDiv = document.getElementById(errorId);
    errorDiv.textContent = message;
    errorDiv.style.display = "block";
  }

  function clearError(input, errorId) {
    input.classList.remove("bsc__input--invalid");
    const errorDiv = document.getElementById(errorId);
    errorDiv.textContent = "";
    errorDiv.style.display = "none";
  }
});
</script>

<?php get_footer(); ?>

