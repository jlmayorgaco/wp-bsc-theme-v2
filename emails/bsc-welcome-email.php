<?php
/**
 * Welcome email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $account_url (string)
 * - $shop_url (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$display_name     = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$account_url      = (string) ( $account_url ?? bsc_email_account_url() );
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$email_title      = '¡ Bienvenido Bubble Lover !';
$email_hero       = 'bsc-email-hero-smile-pink.png';
$email_hero_width = 293;
$email_preheader  = 'Tu cuenta BSC ya está activa.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, ya haces parte de Bubble Skin Care.<br>Desde ahora tendrás acceso a tus pedidos, bubble points<br>y todo lo necesario para seguir construyendo tu rutina coreana con<br>nosotros. ¡ Gracias por confiar en BSC :) !',
		esc_html( $display_name )
	),
	24,
	38
);
?>
				<tr>
					<td align="center" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:32px;font-weight:400;letter-spacing:8px;line-height:36px;padding:0 0 14px;">
						♡♡♡
					</td>
				</tr>
				<tr>
					<td align="center" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:17px;font-weight:900;letter-spacing:.8px;line-height:23px;padding:0 44px 34px;">
						¡Tu cuenta ya esta activa!
					</td>
				</tr>
<?php
bsc_email_render_button_row(
	array(
		array(
			'url'       => $account_url,
			'label'     => '¡ Completar mi perfil !',
			'variant'   => 'pink',
			'min_width' => 220,
		),
		array(
			'url'       => $shop_url,
			'label'     => '¡ Ir a la tienda !',
			'variant'   => 'dark',
			'min_width' => 180,
		),
	),
	0,
	62
);

require __DIR__ . '/bsc-email-footer.php';
