<?php
/**
 * Shared BSC email footer.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$email_whatsapp_url = function_exists( 'bsc_get_email_whatsapp_url' )
	? bsc_get_email_whatsapp_url()
	: 'https://wa.me/573156922859';
?>
				<tr>
					<td align="center" class="bsc-email-mobile-pad" style="padding:24px 44px 8px;">
						<p style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:400;letter-spacing:.5px;line-height:23px;margin:0;text-align:center;">
							Si tienes alguna pregunta contáctanos a través de nuestro
							<a href="<?php echo esc_url( $email_whatsapp_url ); ?>" style="border-bottom:1px solid #303030;color:#303030;font-weight:900;text-decoration:none;">whatsapp</a>
						</p>
					</td>
				</tr>
				<tr>
					<td align="center" style="padding:12px 0 54px;">
						<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
							<tr>
								<td align="center" style="padding:0 10px 0 0;">
									<img src="<?php echo esc_url( get_template_directory_uri() . '/images/BSC_COMING_SOON_SOCIAL_INSTAGRAM.png' ); ?>" width="22" alt="Instagram" style="border:0;display:block;height:auto;width:22px;">
								</td>
								<td align="center" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:22px;line-height:22px;padding:0 12px;">|</td>
								<td align="center" style="padding:0 18px 0 0;">
									<img src="<?php echo esc_url( get_template_directory_uri() . '/images/BSC_COMING_SOON_SOCIAL_TIKTOK.png' ); ?>" width="22" alt="TikTok" style="border:0;display:block;height:auto;width:22px;">
								</td>
								<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:11px;letter-spacing:.2px;line-height:13px;">
									<strong style="font-size:12px;">© <?php echo esc_html( wp_date( 'Y' ) ); ?> BSC | Bubbles Skin Care</strong><br>
									Todos los derechos reservados
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
