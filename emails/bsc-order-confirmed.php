<?php
/**
 * Order confirmed email template.
 *
 * Variables:
 * - $order (WC_Order)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = bsc_email_name_from_order( $order ?? null, 'Bubble Lover' );
$order_number     = $order instanceof WC_Order ? $order->get_order_number() : '';
$email_title      = '¡ Gracias por tu compra !';
$email_hero       = 'bsc-email-hero-rainbow.png';
$email_hero_width = 295;
$email_preheader  = sprintf( 'Recibimos tu pedido #%s.', $order_number );

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, recibimos tu pedido <strong>#%s</strong> y estamos preparándolo para enviarlo lo antes posible. Te notificaremos cuando haya sido enviado con su respectivo número de seguimiento :)',
		esc_html( $customer_name ),
		esc_html( $order_number )
	),
	24,
	18
);

bsc_email_render_status_bar( 'Pago confirmado', 55, '¡Recibido!', '¡Enviado!', '#f4b5c7' );

if ( $order instanceof WC_Order ) {
	require __DIR__ . '/bsc-order-items-table.php';
}

bsc_email_render_button_row(
	array(
		array(
			'url'       => bsc_email_shop_url(),
			'label'     => '¡ Ir a la tienda !',
			'variant'   => 'dark',
			'min_width' => 210,
		),
	),
	2,
	34
);

require __DIR__ . '/bsc-email-footer.php';
