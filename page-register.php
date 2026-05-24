<?php
/* Template Name: Registro personalizado */
get_header();

$registration_error = '';

if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['email'] ) ) {
	if ( ! isset( $_POST['bsc_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_register_nonce'] ) ), 'bsc_register_action' ) ) {
		wp_die( esc_html( html_entity_decode( 'Solicitud no v&aacute;lida.', ENT_QUOTES, 'UTF-8' ) ) );
	}

	if ( ! empty( $_POST['bsc_company'] ) ) {
		wp_die( esc_html( html_entity_decode( 'Solicitud no v&aacute;lida.', ENT_QUOTES, 'UTF-8' ) ) );
	}

	if ( function_exists( 'bsc_rate_limit_passed' ) && ! bsc_rate_limit_passed( 'register', 5, 15 * MINUTE_IN_SECONDS ) ) {
		$registration_error = 'Demasiados intentos. Espera unos minutos antes de crear una cuenta.';
	}

	$nombres  = sanitize_text_field( wp_unslash( $_POST['nombres'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$password = is_string( $_POST['password'] ?? null ) ? (string) wp_unslash( $_POST['password'] ) : '';

	if ( ! empty( $registration_error ) ) {
		// Keep the rate-limit message.
	} elseif ( ! is_email( $email ) ) {
		$registration_error = 'Ingresa un correo electronico valido.';
	} elseif ( email_exists( $email ) ) {
		$registration_error = 'Este correo ya est&aacute; registrado. Intenta iniciar sesi&oacute;n.';
	} elseif ( empty( $nombres ) || empty( $email ) || empty( $password ) ) {
		$registration_error = 'Por favor completa todos los campos.';
	} elseif ( strlen( $password ) < 10 ) {
		$registration_error = 'La contrasena debe tener al menos 10 caracteres.';
	} else {
		$name_parts = explode( ' ', $nombres, 2 );
		$first_name = $name_parts[0];
		$last_name  = $name_parts[1] ?? '';

		$user_id = wp_insert_user(
			[
				'user_login' => $email,
				'user_pass'  => $password,
				'user_email' => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'role'       => 'customer',
			]
		);

		if ( ! is_wp_error( $user_id ) ) {
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id, true );
			do_action( 'wp_login', $email, get_user_by( 'ID', $user_id ) );
			wp_safe_redirect( home_url( '/registro-familia-bubbles/' ) );
			exit;
		}

		$registration_error = 'Hubo un error al crear la cuenta. Intenta con otro correo.';
	}
}
?>

<main class="bsc__auth">
  <div class="bsc__container">
    <div class="bsc__form-image">
      <?php
      bsc_responsive_theme_image(
          'images/signin_signup/bsc_signup_cover.png',
          'Imagen de fondo de registro',
          [
              'loading' => 'eager',
          ],
          '(max-width: 768px) 0px, 50vw'
      );
      ?>
    </div>

    <div class="bsc__form-container">
      <div class="bsc__form-logo">
        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>" alt="Decoraci&oacute;n arco&iacute;ris registro" />
      </div>

      <h1 class="bsc__form-title">&iexcl;Quiero ser parte de BSC!</h1>

      <div class="bsc__form-fields">
        <?php if ( ! empty( $registration_error ) ) : ?>
          <div class="bsc__error-global"><?php echo esc_html( html_entity_decode( $registration_error, ENT_QUOTES, 'UTF-8' ) ); ?></div>
        <?php endif; ?>

        <form name="registerform" id="registerform" method="post" class="form" novalidate>
          <?php wp_nonce_field( 'bsc_register_action', 'bsc_register_nonce' ); ?>
          <input type="text" name="bsc_company" value="" tabindex="-1" autocomplete="off" class="screen-reader-text" aria-hidden="true" />
          <div class="bsc__form-field">
            <label for="nombres" class="bsc__label"><strong>Nombres</strong> y Apellidos</label>
            <input type="text" name="nombres" id="nombres" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_nombres"></div>
          </div>

          <div class="bsc__form-field">
            <label for="email" class="bsc__label"><strong>Correo</strong> electr&oacute;nico</label>
            <input type="email" name="email" id="email" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_email"></div>
          </div>

          <div class="bsc__form-field">
            <label for="password" class="bsc__label"><strong>Contrase&ntilde;a</strong></label>
            <input type="password" name="password" id="password" class="bsc__input" required />
            <div class="bsc__error-msg" id="error_password"></div>
          </div>

          <div class="bsc__form-field bsc__form-field--submit bsc__form-field--auth-offset">
            <input type="submit" id="register-submit" class="bsc__button bsc__button--auth-cta" value="&iexcl;Unirme a Bubbles!" />
          </div>

          <hr class="bsc__auth-divider">

          <div class="bsc__form-field bsc__form-field--submit">
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="bsc__button bsc__button--auth-cta">
              &iexcl;Ingresar a mi cuenta!
            </a>
          </div>

          <div class="bsc__form-links">
            <a class="bsc__form-link no-link">&iquest;Ya tienes cuenta? Ingresa a BSC</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
