<?php
/**
 * Shipping notification email template.
 *
 * Variables:
 * - $order (WC_Order)
 * - $tracking_code (string)
 * - $tracking_link (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = bsc_email_name_from_order( $order ?? null, 'Bubble Lover' );
$order_number     = $order instanceof WC_Order ? $order->get_order_number() : '';
$tracking_code    = sanitize_text_field( (string) ( $tracking_code ?? '' ) );
$tracking_link    = esc_url_raw( (string) ( $tracking_link ?? '' ) );
$email_title      = '¡ Tu pedido esta en camino !';
$email_hero       = 'bsc-email-hero-smile-yellow.png';
$email_hero_width = 225;
$email_preheader  = sprintf( 'Tu pedido #%s ya está en camino.', $order_number );

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, tu pedido <strong>#%s</strong> pronto estará llegando!Lo preparamos con muchísimo cariño para que tu experiencia se sienta única desde el momento en que lo recibes! <strong>Gracias por confiar en BSC y permitirnos acompañarte en tu rutina coreana :)</strong>A continuación encontrarás tu <strong>guía con número de seguimiento</strong> para rastrear tu pedido:',
		esc_html( $customer_name ),
		esc_html( $order_number )
	),
	24,
	22
);

bsc_email_render_tracking_ticket( $tracking_code, $tracking_link );

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
