<?php
/* Template Name: Ingreso personalizado */
get_header();

$login_error = isset( $_GET['login'] ) && $_GET['login'] === 'failed';
?>
<main class="login">
  <div class="login__container">
    <div class="login__image">
      <?php
      bsc_responsive_theme_image(
          'images/signin_signup/bsc_signin_cover.png',
          'Imagen de fondo de ingreso',
          [
              'loading' => 'eager',
          ],
          '(max-width: 768px) 0px, 50vw'
      );
      ?>
    </div>

    <div class="login__form">
      <div class="form__image">
        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>" alt="Decoraci&oacute;n arco&iacute;ris login" />
      </div>

      <div class="form__title">
        <h1 class="form__heading bsc__title">&iexcl;Iniciar sesi&oacute;n!</h1>
      </div>

      <div class="form__fields">
        <?php if ( $login_error ) : ?>
          <div class="form__error">Credenciales inv&aacute;lidas. Intenta de nuevo.</div>
        <?php endif; ?>

        <form name="loginform" id="loginform" action="<?php echo esc_url( wp_login_url() ); ?>" method="post" class="form" novalidate>
          <div class="form__field form__field--username">
            <label for="user_login" class="form__label"><strong>Correo o usuario</strong></label>
            <input type="text" name="log" id="user_login" class="form__input" required />
            <div class="form__error-msg" id="error_user_login"></div>
          </div>

          <div class="form__field form__field--password">
            <label for="user_pass" class="form__label"><strong>Contrase&ntilde;a</strong></label>
            <input type="password" name="pwd" id="user_pass" class="form__input" required />
            <div class="form__error-msg" id="error_user_pass"></div>
          </div>

          <div class="form__field form__field--remember-me">
            <label class="form__checkbox-label">
              <input name="rememberme" type="checkbox" id="rememberme" class="form__checkbox" value="forever" />
              Recordar mis datos
            </label>
          </div>

          <div class="form__field form__field--submit">
            <input type="submit" name="wp-submit" id="wp-submit" class="bsc__button bsc__button--auth-cta form__submit form__submit--auth-cta btn btn--primary" value="Iniciar sesi&oacute;n" />
            <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/mi-cuenta/' ) ); ?>" />
          </div>

          <div class="form__links">
            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="form__link">&iquest;Olvidaste tu contrase&ntilde;a?</a>
          </div>

          <hr class="form__divider">

          <div class="form__field form__field--submit">
            <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>" class="bsc__button bsc__button--auth-cta form__submit form__submit--auth-cta btn btn--primary">Crear mi cuenta</a>
          </div>

          <div class="form__links">
            <a class="form__link no-link">&iquest;No tienes cuenta? &Uacute;nete a BSC</a>
          </div>

          <br>
        </form>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>
