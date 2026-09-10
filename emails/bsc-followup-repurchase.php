<?php
/**
 * Product repurchase follow-up email.
 *
 * Variables:
 * - $customer_name (string)
 * - $products (array)
 * - $inactivity_days (int)
 * - $shop_url (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = sanitize_text_field( (string) ( $customer_name ?? 'Bubble Lover' ) );
$products         = is_array( $products ?? null ) ? $products : array();
$inactivity_days  = max( 1, (int) ( $inactivity_days ?? 90 ) );
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$email_title      = '¡ Tenemos nuevas opciones para tu rutina!';
$email_hero       = 'bsc-email-hero-smile-pink.png';
$email_hero_width = 225;
$email_preheader  = 'Seleccionamos productos disponibles inspirados en tu última compra.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%1$s</strong>, ya pasaron al menos %2$d días desde tu última compra. Seleccionamos productos coreanos<br> para complementar tu rutina :)',
		esc_html( $customer_name ),
		$inactivity_days
	),
	24,
	30
);
?>
				<tr>
					<td align="center" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding:0 44px 22px;">
						Recomendaciones disponibles para ti:
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
