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
$items            = is_array( $items ?? null ) ? $items : array();
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
		'Hola <strong>%s</strong>, guardamos los productos que estabas mirando para que puedas terminar tu compra cuando quieras. Tu rutina coreana todavía puede llegar a casa :)',
		esc_html( $customer_name )
	),
	24,
	24
);
?>
<?php if ( ! empty( $items ) ) : ?>
				<tr>
					<td align="center" class="bsc-email-component-pad" style="padding:0 66px 20px;">
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
							<?php foreach ( array_slice( $items, 0, 4 ) as $item ) : ?>
								<?php
								if ( ! is_array( $item ) ) {
									continue; }

								$image_url = (string) ( $item['image_url'] ?? '' );
								if ( function_exists( 'bsc_email_publicize_image_url' ) ) {
									$image_url = bsc_email_publicize_image_url( $image_url );
								}
								?>
								<tr>
									<td width="72" style="padding:0 14px 12px 0;">
										<?php if ( '' !== $image_url ) : ?>
											<img src="<?php echo esc_url( $image_url ); ?>" width="64" alt="" style="border:0;display:block;height:auto;width:64px;">
										<?php endif; ?>
									</td>
									<td align="left" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>letter-spacing:.5px;padding:0 0 12px;">
										<strong><?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?></strong><br>
										Cantidad: <?php echo esc_html( (string) ( $item['quantity'] ?? 1 ) ); ?>
									</td>
									<td align="right" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>font-weight:800;letter-spacing:.5px;padding:0 0 12px;white-space:nowrap;">
										<?php echo esc_html( (string) ( $item['price_html'] ?? '' ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
							<?php if ( '' !== $total_html ) : ?>
								<tr>
									<td colspan="3" align="right" class="bsc-email-section-title" style="border-top:1px solid #303030;color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding-top:14px;">
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
	array(
		array(
			'url'       => $recover_url,
			'label'     => '¡ Recuperar mi carrito !',
			'variant'   => 'pink',
			'min_width' => 250,
		),
		array(
			'url'       => $shop_url,
			'label'     => '¡ Seguir mirando !',
			'variant'   => 'dark',
			'min_width' => 210,
		),
	),
	4,
	34
);

require __DIR__ . '/bsc-email-footer.php';
