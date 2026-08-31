<?php
/**
 * Birthday email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $shop_url (string)
 * - $coupon_code (string optional)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$display_name     = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$coupon_code      = sanitize_text_field( (string) ( $coupon_code ?? get_option( 'bsc_birthday_coupon_code', 'BSC_SC5RBW2D' ) ) );
$email_title      = '¡ Feliz cumpleañooos !';
$email_hero       = 'bsc-email-hero-cake-complete.png';
$email_hero_width = 275;
$email_preheader  = 'Tienes un regalito de cumpleaños en BSC.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, Gracias por hacer parte de Bubbles y dejarnos acompañarte en tu rutina coreana un año más. Esperamos que tengas un día demasiado lindo, glowy y lleno de K-beauty :)',
		esc_html( $display_name )
	),
	24,
	24,
	0,
	306
);
?>
				<tr>
					<td align="center" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding:0 44px 16px;">
						Tienes un regalito de cumpleaños...
					</td>
				</tr>
<?php
bsc_email_render_coupon( $coupon_code, '10% OFF en BSC', 'Válido por 1 semana | Usos restantes: 1', '#f4b5c7', 'smile', 1, 53, 'standard', 345, true );
bsc_email_render_button_row(
	array(
		array(
			'url'       => $shop_url,
			'label'     => '¡ Redimir mi descuento !',
			'variant'   => 'dark',
			'min_width' => 260,
		),
	),
	0,
	28
);

require __DIR__ . '/bsc-email-footer.php';
