<?php
/**
 * Product repurchase follow-up email.
 *
 * Variables:
 * - $customer_name (string)
 * - $products (array)
 * - $shop_url (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = sanitize_text_field( (string) ( $customer_name ?? 'Bubble Lover' ) );
$products         = is_array( $products ?? null ) ? $products : array();
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$email_title      = '¡ Tu rutina puede estar por acabarse!';
$email_hero       = 'bsc-email-hero-smile-pink.png';
$email_hero_width = 225;
$email_preheader  = 'Algunos productos de tu rutina coreana pueden estar por acabarse.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, algunos productos de tu rutina coreana<br>probablemente ya estén por acabarse. Mantener la constancia hace<br>toooda la diferencia :)',
		esc_html( $customer_name )
	),
	24,
	30
);
?>
				<tr>
					<td align="center" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:22px;font-weight:900;letter-spacing:.7px;line-height:27px;padding:0 44px 22px;">
						Tus últimos productos k-Beauty:
					</td>
				</tr>
<?php
bsc_email_render_product_grid( $products );
bsc_email_render_button_row(
	array(
		array(
			'url'       => $shop_url,
			'label'     => '¡ Ver toda la tienda !',
			'variant'   => 'dark',
			'min_width' => 250,
		),
	),
	4,
	42
);

require __DIR__ . '/bsc-email-footer.php';
