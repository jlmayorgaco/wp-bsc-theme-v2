<?php
/**
 * Shared BSC email footer.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$email_whatsapp_url  = function_exists( 'bsc_get_email_whatsapp_url' )
	? bsc_get_email_whatsapp_url()
	: 'https://wa.me/573156922859';
$email_instagram_url = function_exists( 'bsc_get_email_instagram_url' )
	? bsc_get_email_instagram_url()
	: 'https://www.instagram.com/bubbles.skincare?igsi=em1zNmw0Z2pjMDlu';
$email_tiktok_url    = function_exists( 'bsc_get_email_tiktok_url' )
	? bsc_get_email_tiktok_url()
	: 'https://www.tiktok.com/@bubblesskincare?_r=1&_t=ZS-99BnmyXTB7C';
?>
				<tr>
					<td align="center" class="bsc-email-mobile-pad" style="padding:24px 44px 8px;">
						<p class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>margin:0;text-align:center;">
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
									<a href="<?php echo esc_url( $email_instagram_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram" style="display:block;text-decoration:none;">
										<img src="<?php echo esc_url( bsc_email_asset_url( 'bsc-email-social-instagram.png' ) ); ?>" width="22" alt="Instagram" style="border:0;display:block;height:auto;width:22px;">
									</a>
								</td>
								<td align="center" class="bsc-email-decoration" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'decoration' ) ); ?>padding:0 12px;">|</td>
								<td align="center" style="padding:0 18px 0 0;">
									<a href="<?php echo esc_url( $email_tiktok_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok" style="display:block;text-decoration:none;">
										<img src="<?php echo esc_url( bsc_email_asset_url( 'bsc-email-social-tiktok.png' ) ); ?>" width="22" alt="TikTok" style="border:0;display:block;height:auto;width:22px;">
									</a>
								</td>
								<td align="left" class="bsc-email-caption" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'caption' ) ); ?>">
									<strong class="bsc-email-caption-strong" style="<?php echo esc_attr( bsc_email_typography_style( 'caption-strong' ) ); ?>">© <?php echo esc_html( wp_date( 'Y' ) ); ?> BSC | Bubbles Skin Care</strong><br>
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
