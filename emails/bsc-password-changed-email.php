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

$display_name = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$account_url  = (string) ( $account_url ?? bsc_email_account_url() );
$reset_url    = (string) ( $reset_url ?? wp_lostpassword_url() );
if ( function_exists( 'bsc_email_publicize_url' ) ) {
	$account_url = bsc_email_publicize_url( $account_url );
	$reset_url   = bsc_email_publicize_url( $reset_url );
}
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
	22,
	0,
	283
);
?>
				<tr>
					<td align="center" class="bsc-email-component-pad" style="padding:0 54px 14px;">
						<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="470" class="bsc-email-fluid bsc-email-password-card" style="border:1px solid #303030;border-radius:7px;max-width:470px;width:470px;">
							<tr>
								<td style="padding:16px 24px;">
									<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
										<tr>
											<td width="53" valign="top" class="bsc-email-password-card-icon" style="padding:1px 0 0;">
												<?php bsc_email_render_asset_img( 'bsc-email-coupon-check-pink.png', 53, '' ); ?>
											</td>
											<td align="left" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>">
												Cambio confirmado
												<div class="bsc-email-body-copy" style="color:#363636;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>margin-top:6px;">
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
	20,
	0,
	283
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
