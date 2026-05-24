<?php
/**
 * Order cancelled email template.
 *
 * Variables:
 * - $order (WC_Order)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = bsc_email_name_from_order( $order ?? null, 'Bubble Lover' );
$order_number     = $order instanceof WC_Order ? $order->get_order_number() : '';
$email_title      = '¡ Tu pedido fue cancelado !';
$email_hero       = 'bsc-email-hero-sad-blue.png';
$email_hero_width = 153;
$email_preheader  = sprintf( 'Tu pedido #%s fue cancelado.', $order_number );
$support_url      = function_exists( 'bsc_get_email_whatsapp_url' ) ? bsc_get_email_whatsapp_url() : 'https://wa.me/573156922859';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, tu pedido <strong>#%s</strong> fue cancelado.<br>Si crees que fue un error o deseas ayuda para finalizar tu compra,<br>nuestro equipo estará feliz de ayudarte :)',
		esc_html( $customer_name ),
		esc_html( $order_number )
	),
	24,
	18
);

bsc_email_render_status_bar( 'Cancelado', 100, '¡Cancelado!', '¡Enviado!', '#d9d9d9' );

bsc_email_render_button_row(
	array(
		array(
			'url'       => $support_url,
			'label'     => '¡ Ir a soporte Whatsapp !',
			'variant'   => 'blue',
			'min_width' => 300,
		),
	),
	0,
	42
);

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
	0,
	34
);

require __DIR__ . '/bsc-email-footer.php';
