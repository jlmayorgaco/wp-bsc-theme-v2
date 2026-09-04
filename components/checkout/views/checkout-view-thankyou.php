<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/components/orders/class-bsc-order-view.php';

$order_id = apply_filters( 'woocommerce_thankyou_order_id', absint( get_query_var( 'order-received' ) ) );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- WooCommerce thank-you page validates the order key from the URL.
$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

if ( ! $order_id ) {
	echo '<p class="bsc__message-error">No se encontr&oacute; la orden. Por favor contacta soporte.</p>';
	return;
}

$order = wc_get_order( $order_id );

if ( ! $order || $order->get_order_key() !== $order_key ) {
	echo '<p class="bsc__message-error">Orden inv&aacute;lida o no encontrada.</p>';
	return;
}

$order_view = new BSC_Order_View( $order );
$order_view->set_variant( 'thankyou' );
$order_view->set_page_classes( array( 'bsc__page--thankyou' ) );
$order_view->set_logo(
	get_template_directory_uri() . '/images/bsc_rainbow.png',
	'Bubble Skin Care'
);
$confirmation_copy = bsc_get_order_confirmation_copy( $order->get_status(), (string) $order->get_order_number() );
$order_view->set_heading(
	$confirmation_copy['title'],
	$confirmation_copy['message']
);
$order_actions = array(
	array(
		'label' => 'Volver al inicio',
		'url'   => home_url( '/' ),
	),
	array(
		'label'     => 'Ver mis pedidos',
		'url'       => function_exists( 'wc_get_endpoint_url' )
			? wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) )
			: home_url( '/mi-cuenta/orders/' ),
		'secondary' => true,
	),
);

if ( $order->needs_payment() ) {
	array_unshift(
		$order_actions,
		array(
			'label' => 'Completar pago',
			'url'   => $order->get_checkout_payment_url(),
		)
	);
}

$order_view->set_actions( $order_actions );
$order_view->render();
