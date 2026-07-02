<?php
/**
 * Password changed confirmation email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $account_url (string)
 * - $reset_url (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$display_name     = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$account_url      = (string) ( $account_url ?? bsc_email_account_url() );
$reset_url        = (string) ( $reset_url ?? wp_lostpassword_url() );
$email_title      = '¡ Tu contraseña fue actualizada !';
$email_hero       = 'bsc-email-hero-lock.png';
$email_hero_width = 260;
$email_preheader  = 'Confirmamos que la contraseña de tu cuenta BSC fue cambiada.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, te confirmamos que la contraseña de tu cuenta BSC<br>fue cambiada correctamente. Si hiciste este cambio, no tienes que hacer nada más :)',
		esc_html( $display_name )
	),
	24,
	22
);
?>
				<tr>
					<td align="center" style="padding:0 54px 28px;">
						<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="382" class="bsc-email-fluid" style="border:2px solid #303030;border-radius:7px;max-width:382px;width:382px;">
							<tr>
								<td style="padding:16px 20px 16px;">
									<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td width="35" valign="top" style="padding:1px 10px 0 0;">
												<?php bsc_email_render_asset_img( 'bsc-email-coupon-check-pink.png', 30, '' ); ?>
											</td>
											<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:18px;font-weight:900;letter-spacing:.5px;line-height:23px;">
												Cambio confirmado
												<div style="color:#363636;font-size:14px;font-weight:400;letter-spacing:.3px;line-height:20px;margin-top:6px;">
													Tu acceso quedó protegido con la nueva contraseña.
												</div>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?php
bsc_email_render_message(
	sprintf(
		'Si no reconoces este cambio, <a href="%s" style="border-bottom:1px solid #303030;color:#303030;font-weight:900;text-decoration:none;">restablece tu contraseña</a> de inmediato y contáctanos por whatsapp para ayudarte a revisar tu cuenta.',
		esc_url( $reset_url )
	),
	0,
	20
);

bsc_email_render_button_row(
	array(
		array(
			'url'       => $account_url,
			'label'     => '¡ Ir a mis datos !',
			'variant'   => 'pink',
			'min_width' => 210,
		),
	),
	0,
	40
);

require __DIR__ . '/bsc-email-footer.php';
