<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="login login--lost-password" aria-labelledby="bsc-lost-password-title">
	<div class="login__container">
		<div class="login__image">
			<?php
			bsc_responsive_theme_image(
				'images/signin_signup/bsc_signin_cover.png',
				'Imagen de fondo de recuperaci&oacute;n de contrase&ntilde;a',
				array(
					'loading'       => 'eager',
					'fetchpriority' => 'high',
				),
				'(max-width: 768px) 0px, 50vw'
			);
			?>
		</div>

		<div class="login__form">
			<div class="form__image">
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>" alt="Decoraci&oacute;n arco&iacute;ris recuperaci&oacute;n" />
			</div>

			<div class="form__title">
				<h1 id="bsc-lost-password-title" class="form__heading bsc__title">Recuperar contrase&ntilde;a</h1>
			</div>

			<div class="form__fields">
				<?php do_action( 'woocommerce_before_lost_password_form' ); ?>

				<form method="post" class="woocommerce-ResetPassword lost_reset_password form form--lost-password" novalidate>
					<p id="bsc-lost-password-help" class="form__copy">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_lost_password_message', 'Ingresa tu correo electr&oacute;nico o usuario y te enviaremos un enlace para crear una nueva contrase&ntilde;a.' ) ); ?>
					</p>

					<div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first form__field">
						<label for="user_login" class="form__label"><strong>Correo</strong> o usuario&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Requerido</span></label>
						<input class="woocommerce-Input woocommerce-Input--text input-text form__input" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true" aria-describedby="bsc-lost-password-help" />
					</div>

					<?php do_action( 'woocommerce_lostpassword_form' ); ?>

					<div class="woocommerce-form-row form-row form__field form__field--submit">
						<input type="hidden" name="wc_reset_password" value="true" />
						<button type="submit" class="woocommerce-Button button bsc__button bsc__button--auth-cta form__submit form__submit--auth-cta btn btn--primary" value="Enviar enlace">Enviar enlace</button>
					</div>

					<?php wp_nonce_field( 'lost_password', 'woocommerce-lost-password-nonce' ); ?>
				</form>

				<div class="form__links">
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="form__link">&iquest;Recordaste tu contrase&ntilde;a? Inicia sesi&oacute;n</a>
				</div>

				<?php do_action( 'woocommerce_after_lost_password_form' ); ?>
			</div>
		</div>
	</div>
</section>
