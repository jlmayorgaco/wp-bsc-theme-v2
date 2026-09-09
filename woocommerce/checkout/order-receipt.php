<?php
/**
 * Checkout Order Receipt Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/order-receipt.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="bsc__order-pay-heading"><?php esc_html_e( 'Resumen de tu pedido', 'bsc-2-0' ); ?></h2>
<ul class="order_details">
	<li class="order">
		<span><?php esc_html_e( 'Número de pedido', 'bsc-2-0' ); ?></span>
		<strong><?php echo esc_html( $order->get_order_number() ); ?></strong>
	</li>
	<li class="date">
		<span><?php esc_html_e( 'Fecha', 'bsc-2-0' ); ?></span>
		<?php $bsc_order_date = $order->get_date_created(); ?>
		<strong><?php echo esc_html( $bsc_order_date ? bsc_format_date_es( $bsc_order_date ) : '' ); ?></strong>
	</li>
	<?php if ( $order->get_payment_method_title() ) : ?>
	<li class="method">
		<span><?php esc_html_e( 'Medio de pago', 'bsc-2-0' ); ?></span>
		<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
	</li>
	<?php endif; ?>
	<li class="total">
		<span><?php esc_html_e( 'Total a pagar', 'bsc-2-0' ); ?></span>
		<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
	</li>
</ul>

<?php do_action( 'woocommerce_receipt_' . $order->get_payment_method(), $order->get_id() ); ?>

<div class="clear"></div>
