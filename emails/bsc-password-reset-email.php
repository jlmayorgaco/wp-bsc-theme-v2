<?php
/**
 * Password reset email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $reset_url (string)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$display_name     = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$reset_url        = (string) ( $reset_url ?? wp_lostpassword_url() );
$email_title      = '¡ Recupera tu contraseña !';
$email_hero       = 'bsc-email-hero-lock.png';
$email_hero_width = 260;
$email_preheader  = 'Crea una nueva contraseña para tu cuenta BSC.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, recibimos una solicitud para restablecer<br>la contraseña de tu cuenta BSC. Si fuiste tú, puedes crear<br>una nueva desde el botón de abajo de forma segura :)',
		esc_html( $display_name )
	),
	24,
	26
);

bsc_email_render_button_row(
	array(
		array(
			'url'       => $reset_url,
			'label'     => '¡ Cambiar mi contraseña !',
			'variant'   => 'dark',
			'min_width' => 310,
		),
	),
	0,
	24
);
?>
				<tr>
					<td align="center" class="bsc-email-mobile-pad" style="padding:0 54px 38px;">
						<p style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:22px;font-weight:900;letter-spacing:1px;line-height:28px;margin:0 0 2px;text-align:center;">Si el botón no abre...</p>
						<p style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:15px;font-weight:400;letter-spacing:.7px;line-height:22px;margin:0;text-align:center;">copia este enlace en tu navegador:</p>
						<p style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:15px;font-weight:400;letter-spacing:.4px;line-height:21px;margin:22px 0 0;text-align:center;word-break:break-all;">
							<a href="<?php echo esc_url( $reset_url ); ?>" style="color:#303030;text-decoration:none;"><?php echo esc_html( $reset_url ); ?></a>
						</p>
					</td>
				</tr>
<?php require __DIR__ . '/bsc-email-footer.php'; ?>
