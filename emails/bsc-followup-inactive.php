<?php
/**
 * Customer inactivity follow-up email.
 *
 * Variables:
 * - $customer_name (string)
 * - $shop_url (string)
 * - $account_url (string)
 * - $coupon_code (string optional)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = sanitize_text_field( (string) ( $customer_name ?? 'Bubble Lover' ) );
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$coupon_code      = sanitize_text_field( (string) ( $coupon_code ?? get_option( 'bsc_inactive_coupon_code', 'BSC_SC5RBW2D' ) ) );
$email_title      = '¡ Te extrañamooos !';
$email_hero       = 'bsc-email-hero-smile-pink.png';
$email_hero_width = 225;
$email_preheader  = 'Tenemos un regalito especial para volver al glow coreano.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, hora de volver al glow coreano! :)<br>¿Se te acabó algún paso? ¿Necesitas re-stock de tus favoritos?<br>¿O quieres descubrir nuevos productos coreanos de entrega inmediata?<br>¡Encuentra los mejores productos coreanos en Bubbles!',
		esc_html( $customer_name )
	),
	24,
	24
);
?>
				<tr>
					<td align="center" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:900;letter-spacing:.7px;line-height:22px;padding:0 44px 16px;">
						Tienes un regalito especial...
					</td>
				</tr>
<?php
bsc_email_render_coupon( $coupon_code, 'Envío gratis!', 'Válido por 1 mes | Usos restantes: 1', '#f4b5c7' );
bsc_email_render_button_row(
	[
		[
			'url'       => $shop_url,
			'label'     => '¡ Redimir mi descuento !',
			'variant'   => 'dark',
			'min_width' => 260,
		],
	],
	0,
	30
);

require __DIR__ . '/bsc-email-footer.php';
