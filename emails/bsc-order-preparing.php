<?php
/**
 * Order preparing email template.
 *
 * Variables:
 * - $order (WC_Order)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = bsc_email_name_from_order( $order ?? null, 'Bubble Lover' );
$order_number     = $order instanceof WC_Order ? $order->get_order_number() : '';
$email_title      = '¡ Estamos preparando tu pedido !';
$email_hero       = 'bsc-email-hero-rainbow.png';
$email_hero_width = 260;
$email_preheader  = sprintf( 'Estamos preparando tu pedido #%s.', $order_number );

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, ya estamos preparando tu pedido <strong>#%s</strong>.<br>Lo alistaremos con mucho cuidado y te avisaremos cuando salga<br>con su número de seguimiento :)',
		esc_html( $customer_name ),
		esc_html( $order_number )
	),
	24,
	18
);

bsc_email_render_status_bar( 'En preparación', 70, '¡Recibido!', '¡Enviado!', '#f4b5c7' );

if ( $order instanceof WC_Order ) {
	require __DIR__ . '/bsc-order-items-table.php';
}

bsc_email_render_button_row(
	[
		[
			'url'       => bsc_email_shop_url(),
			'label'     => '¡ Ir a la tienda !',
			'variant'   => 'dark',
			'min_width' => 210,
		],
	],
	2,
	34
);

require __DIR__ . '/bsc-email-footer.php';
