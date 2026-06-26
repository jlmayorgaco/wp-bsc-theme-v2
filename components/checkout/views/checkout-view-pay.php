<?php
defined( 'ABSPATH' ) || exit;
?>
<main class="bsc bsc__page bsc__page--order-pay">
	<div class="bsc__container bsc__container--centered">
	<img class="bsc__checkout-logo" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_checkout_logo.svg" alt="Bubble Skin Care">
	<h1 class="bsc__title"><strong>Completa</strong> tu pago</h1>
	<div class="bsc__order-pay-form">
		<?php if ( class_exists( 'WC_Shortcode_Checkout' ) ) : ?>
			<div class="woocommerce">
				<?php WC_Shortcode_Checkout::output( array() ); ?>
			</div>
		<?php else : ?>
			<?php wc_print_notice( __( 'WooCommerce no esta disponible para completar este pago.', 'bsc-2-0' ), 'error' ); ?>
		<?php endif; ?>
	</div>
	</div>
</main>
