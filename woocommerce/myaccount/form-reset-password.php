<?php
/**
 * Lost password reset form.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-reset-password.php.
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

<section class="login login--lost-password" aria-labelledby="bsc-reset-password-title">
	<div class="login__container">
		<div class="login__image">
			<?php
			bsc_responsive_theme_image(
				'images/signin_signup/bsc_signin_cover.png',
				'Imagen de fondo de nueva contrase&ntilde;a',
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
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>" alt="Decoraci&oacute;n arco&iacute;ris nueva contrase&ntilde;a" />
			</div>

			<div class="form__title">
				<h1 id="bsc-reset-password-title" class="form__heading bsc__title">Crear nueva contrase&ntilde;a</h1>
			</div>

			<div class="form__fields">
				<?php do_action( 'woocommerce_before_reset_password_form' ); ?>

				<form method="post" class="woocommerce-ResetPassword lost_reset_password form form--lost-password" novalidate>
					<p id="bsc-reset-password-help" class="form__copy">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_reset_password_message', 'Escribe una nueva contrase&ntilde;a segura para volver a ingresar a tu cuenta.' ) ); ?>
					</p>

					<div class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first form__field">
						<label for="password_1" class="form__label"><strong>Nueva contrase&ntilde;a</strong>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Requerido</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form__input" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" aria-describedby="bsc-reset-password-help" />
					</div>

					<div class="woocommerce-form-row woocommerce-form-row--last form-row form-row-last form__field">
						<label for="password_2" class="form__label"><strong>Confirmar contrase&ntilde;a</strong>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text">Requerido</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text form__input" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" />
					</div>

					<input type="hidden" name="reset_key" value="<?php echo esc_attr( $args['key'] ); ?>" />
					<input type="hidden" name="reset_login" value="<?php echo esc_attr( $args['login'] ); ?>" />

					<?php do_action( 'woocommerce_resetpassword_form' ); ?>

					<div class="woocommerce-form-row form-row form__field form__field--submit">
						<input type="hidden" name="wc_reset_password" value="true" />
						<button type="submit" class="woocommerce-Button button bsc__button bsc__button--auth-cta form__submit form__submit--auth-cta btn btn--primary" value="Guardar contrase&ntilde;a">Guardar contrase&ntilde;a</button>
					</div>

					<?php wp_nonce_field( 'reset_password', 'woocommerce-reset-password-nonce' ); ?>
				</form>

				<div class="form__links">
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="form__link">Volver a iniciar sesi&oacute;n</a>
				</div>

				<?php do_action( 'woocommerce_after_reset_password_form' ); ?>
			</div>
		</div>
	</div>
</section>
