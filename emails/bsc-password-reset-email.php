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

$display_name = bsc_email_name_from_user( $user ?? null, 'Bubble Lover' );
$reset_url    = (string) ( $reset_url ?? wp_lostpassword_url() );
if ( function_exists( 'bsc_email_publicize_url' ) ) {
	$reset_url = bsc_email_publicize_url( $reset_url );
}
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
	26,
	0
);

bsc_email_render_button_row(
	array(
		array(
			'url'                => $reset_url,
			'label'              => '¡ Cambiar mi contraseña !',
			'variant'            => 'dark',
			'min_width'          => 310,
			'padding_horizontal' => 24,
		),
	),
	0,
	24
);
?>
				<tr>
					<td align="center" class="bsc-email-mobile-pad" style="padding:0 54px 38px;">
						<p class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>margin:0 0 2px;text-align:center;">Si el botón no abre...</p>
						<p class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>margin:0;text-align:center;">copia este enlace en tu navegador:</p>
						<p class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>letter-spacing:.4px;margin:22px 0 0;text-align:center;word-break:break-all;">
							<a href="<?php echo esc_url( $reset_url ); ?>" style="color:#303030;text-decoration:none;"><?php echo esc_html( $reset_url ); ?></a>
						</p>
					</td>
				</tr>
<?php require __DIR__ . '/bsc-email-footer.php'; ?>
