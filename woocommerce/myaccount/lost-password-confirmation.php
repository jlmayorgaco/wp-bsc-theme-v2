<?php
/**
 * Lost password confirmation text.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/lost-password-confirmation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.9.0
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="login login--lost-password" aria-labelledby="bsc-lost-password-confirmation-title">
	<div class="login__container">
		<div class="login__image">
			<?php
			bsc_responsive_theme_image(
				'images/signin_signup/bsc_signin_cover.png',
				'Imagen de fondo de correo enviado',
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
				<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>" alt="Decoraci&oacute;n arco&iacute;ris correo enviado" />
			</div>

			<div class="form__title">
				<h1 id="bsc-lost-password-confirmation-title" class="form__heading bsc__title">Revisa tu correo</h1>
			</div>

			<div class="form__fields">
				<?php do_action( 'woocommerce_before_lost_password_confirmation_message' ); ?>

				<div class="form__notice form__notice--success" role="status">
					Te enviamos un enlace para restablecer tu contrase&ntilde;a.
				</div>

				<p class="form__copy">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_lost_password_confirmation_message', 'Puede tardar unos minutos en aparecer en tu bandeja de entrada. Si no lo ves, revisa spam o promociones antes de solicitar otro enlace.' ) ); ?>
				</p>

				<div class="form__links">
					<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>" class="form__link">Volver a iniciar sesi&oacute;n</a>
				</div>

				<?php do_action( 'woocommerce_after_lost_password_confirmation_message' ); ?>
			</div>
		</div>
	</div>
</section>
