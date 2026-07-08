<?php
/**
 * Registration welcome screen.
 *
 * Template Name: Bienvenida registro BSC
 *
 * @package BSC2
 */

get_header();

$shop_url = home_url( '/shop/' );

if ( function_exists( 'bsc_get_email_shop_url' ) ) {
	$shop_url = bsc_get_email_shop_url();
} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
	$wc_shop_url = wc_get_page_permalink( 'shop' );

	if ( $wc_shop_url ) {
		$shop_url = $wc_shop_url;
	}
}
?>

<main id="primary" class="bsc bsc-register-welcome" aria-labelledby="bsc-register-welcome-title">
	<section class="bsc-register-welcome__content">
		<img
			class="bsc-register-welcome__rainbow"
			src="<?php echo esc_url( get_template_directory_uri() . '/images/signin_signup/bsc_signin_rainbow.png' ); ?>"
			alt=""
			aria-hidden="true"
			loading="eager"
			decoding="async"
		>

		<h1 id="bsc-register-welcome-title" class="bsc-register-welcome__title">&iexcl;Bienvenido Bubble lover!</h1>
		<div class="bsc-register-welcome__divider" aria-hidden="true">~~~</div>

		<p class="bsc-register-welcome__copy">
			Ahora podr&aacute;s disfrutar de todos los beneficios que tenemos para ti, como
			<strong>acumular puntos</strong> con cada compra que hagas en
			<strong>bubbleskincare.com</strong>, tener un
			<strong>registro de tus pedidos</strong> y ser el/la primero/a en conocer
			<strong>nuestras promociones, nuevos productos y tendencias</strong> en el
			mundo del k-beauty !!!
		</p>

		<a class="bsc-register-welcome__button bsc__button" href="<?php echo esc_url( $shop_url ); ?>">
			&iexcl; Quiero visitar la tienda !
		</a>
	</section>
</main>

<?php
get_footer();
