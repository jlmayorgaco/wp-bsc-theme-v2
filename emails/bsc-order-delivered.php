<?php
/**
 * Order delivered email template.
 *
 * Variables:
 * - $order (WC_Order)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = bsc_email_name_from_order( $order ?? null, 'Bubble Lover' );
$order_number     = $order instanceof WC_Order ? $order->get_order_number() : '';
$email_title      = '¡ Tu pedido fue entregado !';
$email_hero       = 'bsc-email-hero-smile-yellow.png';
$email_hero_width = 210;
$email_preheader  = sprintf( 'Tu pedido #%s fue entregado.', $order_number );

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, tu pedido <strong>#%s</strong> fue marcado como entregado.<br>Esperamos que ames tus nuevos productos K-Beauty tanto como nosotras<br>y que sigan acompañando tu rutina coreana :)',
		esc_html( $customer_name ),
		esc_html( $order_number )
	),
	24,
	18
);

bsc_email_render_status_bar( 'Entregado', 100, '¡Recibido!', '¡Entregado!', '#fff2bd' );

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
