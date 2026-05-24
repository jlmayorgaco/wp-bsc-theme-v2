<?php
/**
 * Shared BSC email design helpers.
 *
 * The rendered emails use table-based HTML and inline styles because several
 * email clients strip external CSS or only partially support modern layout.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_email_asset_url' ) ) {
	function bsc_email_asset_url( string $filename ): string {
		return get_template_directory_uri() . '/images/emails/' . ltrim( $filename, '/\\' );
	}
}

if ( ! function_exists( 'bsc_email_shop_url' ) ) {
	function bsc_email_shop_url(): string {
		if ( function_exists( 'bsc_get_email_shop_url' ) ) {
			return bsc_get_email_shop_url();
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$shop_url = wc_get_page_permalink( 'shop' );
			if ( $shop_url ) {
				return $shop_url;
			}
		}

		return home_url( '/shop/' );
	}
}

if ( ! function_exists( 'bsc_email_account_url' ) ) {
	function bsc_email_account_url(): string {
		if ( function_exists( 'bsc_get_email_account_url' ) ) {
			return bsc_get_email_account_url();
		}

		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$account_url = wc_get_page_permalink( 'myaccount' );
			if ( $account_url ) {
				return $account_url;
			}
		}

		return home_url( '/mi-cuenta/' );
	}
}

if ( ! function_exists( 'bsc_email_name_from_user' ) ) {
	function bsc_email_name_from_user( $user, string $fallback = 'Bubble Lover' ): string {
		if ( $user instanceof WP_User ) {
			$name = trim( (string) $user->first_name );
			if ( '' === $name ) {
				$name = trim( (string) $user->display_name );
			}
			return '' !== $name ? $name : $fallback;
		}

		return $fallback;
	}
}

if ( ! function_exists( 'bsc_email_name_from_order' ) ) {
	function bsc_email_name_from_order( $order, string $fallback = 'Bubble Lover' ): string {
		if ( $order instanceof WC_Order ) {
			$name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
			return '' !== $name ? $name : $fallback;
		}

		return $fallback;
	}
}

if ( ! function_exists( 'bsc_email_money' ) ) {
	function bsc_email_money( $amount ): string {
		if ( function_exists( 'wc_price' ) ) {
			return wp_strip_all_tags( wc_price( (float) $amount ) );
		}

		return '$' . number_format_i18n( (float) $amount, 0 );
	}
}

if ( ! function_exists( 'bsc_email_supported_html' ) ) {
	function bsc_email_supported_html( string $html ): string {
		return wp_kses(
			$html,
			array(
				'a'      => array(
					'href'   => true,
					'target' => true,
					'style'  => true,
				),
				'br'     => array(),
				'strong' => array(),
				'em'     => array(),
				'span'   => array(
					'style' => true,
				),
			)
		);
	}
}

if ( ! function_exists( 'bsc_email_render_button' ) ) {
	function bsc_email_render_button( string $url, string $label, string $variant = 'dark', int $min_width = 0 ): void {
		$background      = 'pink' === $variant ? '#f4b5c7' : ( 'blue' === $variant ? '#cceff7' : '#303030' );
		$color           = '#303030' === $background ? '#ffffff' : '#303030';
		$min_width_style = $min_width > 0 ? 'min-width:' . absint( $min_width ) . 'px;' : '';
		?>
		<a href="<?php echo esc_url( $url ); ?>"
			style="background:<?php echo esc_attr( $background ); ?>;border-radius:24px;color:<?php echo esc_attr( $color ); ?>;display:inline-block;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:800;letter-spacing:2px;line-height:20px;mso-padding-alt:0;padding:12px 28px;text-align:center;text-decoration:none;<?php echo esc_attr( $min_width_style ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_button_row' ) ) {
	function bsc_email_render_button_row( array $buttons, int $padding_top = 18, int $padding_bottom = 28 ): void {
		?>
		<tr>
			<td align="center" style="padding:<?php echo esc_attr( $padding_top ); ?>px 46px <?php echo esc_attr( $padding_bottom ); ?>px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
					<tr>
						<?php foreach ( $buttons as $index => $button ) : ?>
							<td align="center" style="padding:0 6px 10px;">
								<?php
								bsc_email_render_button(
									(string) ( $button['url'] ?? home_url( '/' ) ),
									(string) ( $button['label'] ?? '' ),
									(string) ( $button['variant'] ?? 'dark' ),
									(int) ( $button['min_width'] ?? 0 )
								);
								?>
							</td>
						<?php endforeach; ?>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_message' ) ) {
	function bsc_email_render_message( string $message, int $padding_top = 20, int $padding_bottom = 24 ): void {
		?>
		<tr>
			<td align="center" style="padding:<?php echo esc_attr( $padding_top ); ?>px 54px <?php echo esc_attr( $padding_bottom ); ?>px;">
				<div style="color:#363636;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:400;letter-spacing:.8px;line-height:1.48;text-align:center;">
					<?php echo wp_kses_post( bsc_email_supported_html( $message ) ); ?>
				</div>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_status_bar' ) ) {
	function bsc_email_render_status_bar( string $status, int $percent, string $left_label, string $right_label, string $fill = '#f4b5c7' ): void {
		$percent = max( 0, min( 100, $percent ) );
		?>
		<tr>
			<td align="center" style="padding:12px 66px 34px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td align="left" style="color:#363636;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.7px;line-height:22px;padding:0 0 12px;">
							<strong>Estado:</strong> <?php echo esc_html( $status ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #343434;border-radius:8px;height:16px;">
								<tr>
									<td width="<?php echo esc_attr( $percent ); ?>%" style="background:<?php echo esc_attr( $fill ); ?>;border-radius:8px;height:14px;font-size:0;line-height:0;">&nbsp;</td>
									<td width="<?php echo esc_attr( 100 - $percent ); ?>%" style="font-size:0;line-height:0;">&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding-top:8px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:800;letter-spacing:.8px;">|</td>
									<td align="right" style="color:#a5a5a5;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;">|</td>
								</tr>
								<tr>
									<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:800;letter-spacing:.8px;"><?php echo esc_html( $left_label ); ?></td>
									<td align="right" style="color:#a5a5a5;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;"><?php echo esc_html( $right_label ); ?></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_coupon' ) ) {
	function bsc_email_render_coupon( string $code, string $headline, string $meta, string $accent = '#f4b5c7' ): void {
		?>
		<tr>
			<td align="center" style="padding:0 52px 24px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="370" style="border:1px solid #303030;border-radius:5px;">
					<tr>
						<td style="padding:12px 18px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:13px;font-weight:800;letter-spacing:.4px;line-height:17px;">
										<span style="color:#c84f75;">&#10003;</span> <?php echo esc_html( $code ); ?>
									</td>
									<td rowspan="3" align="right" width="84" style="font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">
										<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="right">
											<tr>
												<td align="center" valign="middle" width="64" height="64" style="background:<?php echo esc_attr( $accent ); ?>;border:1px solid #303030;border-radius:32px;color:#303030;font-size:31px;line-height:64px;">:)</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:26px;font-weight:900;letter-spacing:.4px;line-height:31px;padding-top:2px;">
										<?php echo esc_html( $headline ); ?>
									</td>
								</tr>
								<tr>
									<td style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:9px;font-weight:800;letter-spacing:.2px;line-height:13px;padding-top:4px;">
										<span style="background:#f4b5c7;border-radius:7px;padding:3px 7px;">Activo</span>
										<?php echo esc_html( $meta ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_tracking_ticket' ) ) {
	function bsc_email_render_tracking_ticket( string $tracking_code, string $tracking_link ): void {
		if ( '' === $tracking_code && '' === $tracking_link ) {
			return;
		}

		$url = '' !== $tracking_link ? $tracking_link : '#';
		?>
		<tr>
			<td align="center" style="padding:2px 54px 28px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="380" style="border:1px solid #303030;border-radius:5px;">
					<tr>
						<td style="padding:14px 18px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:18px;font-weight:800;letter-spacing:.4px;line-height:21px;">
										<span style="color:#c84f75;">&#10003;</span> Pedido enviado!
									</td>
								</tr>
								<tr>
									<td style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:30px;font-weight:900;letter-spacing:1px;line-height:36px;">
										<?php echo esc_html( $tracking_code ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding-top:6px;">
										<a href="<?php echo esc_url( $url ); ?>" style="background:#f4b5c7;border-radius:12px;color:#303030;display:inline-block;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:12px;font-weight:900;letter-spacing:.7px;line-height:16px;padding:5px 12px;text-decoration:none;">Rastrear mi pedido</a>
									</td>
								</tr>
							</table>
						</td>
						<td align="center" width="108" style="border-left:1px solid #303030;color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:42px;line-height:52px;padding:12px 8px;">&#9825;</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_order_summary' ) ) {
	function bsc_email_render_order_summary( WC_Order $order ): void {
		?>
		<tr>
			<td align="center" style="padding:0 66px 28px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td colspan="2" align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:900;letter-spacing:.8px;line-height:21px;padding:0 0 22px;">
							Resumen de tu pedido:
						</td>
					</tr>
					<?php foreach ( $order->get_items() as $item ) : ?>
						<tr>
							<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;line-height:23px;padding:0 0 2px;">
								<?php echo esc_html( $item->get_quantity() . ' X ' . $item->get_name() ); ?>
							</td>
							<td align="right" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;line-height:23px;padding:0 0 2px;white-space:nowrap;">
								<?php echo esc_html( bsc_email_money( $item->get_total() ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( $order->get_items( 'shipping' ) as $shipping_item ) : ?>
						<tr>
							<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;line-height:23px;padding:0 0 2px;">
								<?php echo esc_html( $shipping_item->get_name() ); ?>
							</td>
							<td align="right" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;letter-spacing:.8px;line-height:23px;padding:0 0 2px;white-space:nowrap;">
								<?php echo esc_html( bsc_email_money( $shipping_item->get_total() ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td colspan="2" style="border-bottom:1px solid #303030;font-size:0;line-height:0;padding-top:14px;">&nbsp;</td>
					</tr>
					<tr>
						<td align="left" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:17px;font-weight:900;letter-spacing:.8px;line-height:24px;padding-top:16px;">
							Total
						</td>
						<td align="right" style="color:#303030;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:17px;font-weight:900;letter-spacing:.8px;line-height:24px;padding-top:16px;white-space:nowrap;">
							<?php echo esc_html( wp_strip_all_tags( $order->get_formatted_order_total() ) ); ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_product_grid' ) ) {
	function bsc_email_render_product_grid( array $products ): void {
		if ( empty( $products ) ) {
			return;
		}

		$products = array_slice( $products, 0, 3 );
		?>
		<tr>
			<td align="center" style="padding:2px 48px 22px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<?php foreach ( $products as $product ) : ?>
							<?php
							$name      = (string) ( $product['name'] ?? '' );
							$url       = (string) ( $product['url'] ?? bsc_email_shop_url() );
							$image_url = (string) ( $product['image_url'] ?? '' );
							?>
							<td align="center" valign="top" width="33.333%" style="padding:0 9px 12px;">
								<a href="<?php echo esc_url( $url ); ?>" style="display:block;text-decoration:none;">
									<?php if ( '' !== $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" width="104" alt="<?php echo esc_attr( $name ); ?>" style="border:0;display:block;height:auto;margin:0 auto 10px;max-width:104px;width:104px;">
									<?php else : ?>
										<span style="background:#f4f4f4;display:block;height:104px;margin:0 auto 10px;width:104px;">&nbsp;</span>
									<?php endif; ?>
									<span style="color:#303030;display:block;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:10px;font-weight:900;letter-spacing:.2px;line-height:13px;min-height:28px;text-align:center;"><?php echo esc_html( $name ); ?></span>
								</a>
								<a href="<?php echo esc_url( $url ); ?>" style="background:#cceff7;border-radius:12px;color:#303030;display:inline-block;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:10px;font-weight:900;letter-spacing:.8px;line-height:13px;padding:5px 13px;text-align:center;text-decoration:none;">¡ Re stock !</a>
							</td>
						<?php endforeach; ?>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}
