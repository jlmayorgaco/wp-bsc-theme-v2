<?php
/**
 * Abandoned cart recovery email.
 *
 * Variables:
 * - $customer_name
 * - $items
 * - $total_html
 * - $recover_url
 * - $shop_url
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

$customer_name    = sanitize_text_field( (string) ( $customer_name ?? 'Bubble Lover' ) );
$items            = is_array( $items ?? null ) ? $items : [];
$recover_url      = (string) ( $recover_url ?? bsc_email_shop_url() );
$shop_url         = (string) ( $shop_url ?? bsc_email_shop_url() );
$total_html       = sanitize_text_field( (string) ( $total_html ?? '' ) );
$email_title      = '¡ Tu carrito BSC te espera !';
$email_hero       = 'bsc-email-hero-rainbow.png';
$email_hero_width = 255;
$email_preheader  = 'Guardamos los productos de tu carrito para que puedas terminar tu compra.';

require __DIR__ . '/bsc-email-header.php';

bsc_email_render_message(
	sprintf(
		'Hola <strong>%s</strong>, guardamos los productos que estabas mirando<br>para que puedas terminar tu compra cuando quieras.<br>Tu rutina coreana todavía puede llegar a casa :)',
		esc_html( $customer_name )
	),
	24,
	24
);
?>
<?php if ( ! empty( $items ) ) : ?>
				<tr>
					<td align="center" style="padding:0 66px 20px;">
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
							<?php foreach ( array_slice( $items, 0, 4 ) as $item ) : ?>
								<?php if ( ! is_array( $item ) ) { continue; } ?>
								<tr>
									<td width="72" style="padding:0 14px 12px 0;">
										<?php if ( ! empty( $item['image_url'] ) ) : ?>
											<img src="<?php echo esc_url( (string) $item['image_url'] ); ?>" width="64" alt="" style="border:0;display:block;height:auto;width:64px;">
										<?php endif; ?>
									</td>
									<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:14px;letter-spacing:.5px;line-height:19px;padding:0 0 12px;">
										<strong><?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?></strong><br>
										Cantidad: <?php echo esc_html( (string) ( $item['quantity'] ?? 1 ) ); ?>
									</td>
									<td align="right" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:14px;font-weight:800;letter-spacing:.5px;line-height:19px;padding:0 0 12px;white-space:nowrap;">
										<?php echo esc_html( (string) ( $item['price_html'] ?? '' ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ( '' !== $total_html ) : ?>
								<tr>
									<td colspan="3" align="right" style="border-top:1px solid #303030;color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:15px;font-weight:900;letter-spacing:.5px;line-height:21px;padding-top:14px;">
										Total aproximado: <?php echo esc_html( $total_html ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
<?php endif; ?>
<?php
bsc_email_render_button_row(
	[
		[
			'url'       => $recover_url,
			'label'     => '¡ Recuperar mi carrito !',
			'variant'   => 'pink',
			'min_width' => 250,
		],
		[
			'url'       => $shop_url,
			'label'     => '¡ Seguir mirando !',
			'variant'   => 'dark',
			'min_width' => 210,
		],
	],
	4,
	34
);

require __DIR__ . '/bsc-email-footer.php';
