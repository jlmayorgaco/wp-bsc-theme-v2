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
		$url = get_template_directory_uri() . '/images/emails/' . ltrim( $filename, '/\\' );

		return function_exists( 'bsc_email_publicize_image_url' ) ? bsc_email_publicize_image_url( $url ) : $url;
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
				return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $shop_url ) : $shop_url;
			}
		}

		$shop_url = home_url( '/shop/' );

		return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $shop_url ) : $shop_url;
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
				return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $account_url ) : $account_url;
			}
		}

		$account_url = home_url( '/mi-cuenta/' );

		return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $account_url ) : $account_url;
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

if ( ! function_exists( 'bsc_email_typography_style' ) ) {
	/**
	 * Return the shared inline typography for a semantic email text role.
	 *
	 * Keeping these values in one place prevents individual templates from
	 * drifting to different font sizes in email clients.
	 */
	function bsc_email_typography_style( string $role ): string {
		$styles = array(
			'title'         => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:24px;font-weight:900;letter-spacing:2px;line-height:30px;",
			'body'          => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:12px;font-weight:400;letter-spacing:.8px;line-height:1.48;",
			'section-title' => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:18px;font-weight:900;letter-spacing:.7px;line-height:24px;",
			'button'        => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:16px;font-weight:800;letter-spacing:2px;line-height:20px;",
			'display'       => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:32px;font-weight:900;letter-spacing:.8px;line-height:38px;",
			'caption'       => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:11px;font-weight:400;letter-spacing:.2px;line-height:13px;",
			'caption-strong' => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:12px;font-weight:900;letter-spacing:.2px;line-height:16px;",
			'decoration'    => "font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;font-size:22px;font-weight:400;line-height:22px;",
		);

		return $styles[ $role ] ?? $styles['body'];
	}
}

if ( ! function_exists( 'bsc_email_render_asset_img' ) ) {
	function bsc_email_render_asset_img( string $filename, int $width, string $alt = '', string $extra_style = '' ): void {
		$width = max( 1, $width );
		?>
		<img src="<?php echo esc_url( bsc_email_asset_url( $filename ) ); ?>" width="<?php echo esc_attr( $width ); ?>" alt="<?php echo esc_attr( $alt ); ?>" style="border:0;display:block;height:auto;max-width:<?php echo esc_attr( $width ); ?>px;width:<?php echo esc_attr( $width ); ?>px;<?php echo esc_attr( $extra_style ); ?>">
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_button' ) ) {
	function bsc_email_render_button( string $url, string $label, string $variant = 'dark', int $min_width = 0, int $padding_horizontal = 28 ): void {
		if ( function_exists( 'bsc_email_publicize_url' ) ) {
			$url = bsc_email_publicize_url( $url );
		}

		$background         = 'pink' === $variant ? '#f4b5c7' : ( 'blue' === $variant ? '#cceff7' : '#303030' );
		$color              = '#303030' === $background ? '#ffffff' : '#303030';
		$min_width_style    = $min_width > 0 ? 'min-width:' . absint( $min_width ) . 'px;' : '';
		$padding_horizontal = max( 16, min( 40, $padding_horizontal ) );
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="bsc-email-button"
			style="background:<?php echo esc_attr( $background ); ?>;border-radius:24px;box-sizing:border-box;color:<?php echo esc_attr( $color ); ?>;display:inline-block;<?php echo esc_attr( bsc_email_typography_style( 'button' ) ); ?>mso-padding-alt:0;padding:12px <?php echo esc_attr( $padding_horizontal ); ?>px;text-align:center;text-decoration:none;white-space:nowrap;<?php echo esc_attr( $min_width_style ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php
	}
}

if ( ! function_exists( 'bsc_email_render_button_row' ) ) {
	function bsc_email_render_button_row( array $buttons, int $padding_top = 18, int $padding_bottom = 28 ): void {
		?>
		<tr>
			<td align="center" class="bsc-email-button-row" style="padding:<?php echo esc_attr( $padding_top ); ?>px 46px <?php echo esc_attr( $padding_bottom ); ?>px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" class="bsc-email-button-table">
					<tr>
						<?php foreach ( $buttons as $index => $button ) : ?>
							<td align="center" class="bsc-email-button-cell" style="padding:0 6px 10px;">
								<?php
								bsc_email_render_button(
									(string) ( $button['url'] ?? home_url( '/' ) ),
									(string) ( $button['label'] ?? '' ),
									(string) ( $button['variant'] ?? 'dark' ),
									(int) ( $button['min_width'] ?? 0 ),
									(int) ( $button['padding_horizontal'] ?? 28 )
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
	function bsc_email_render_message( string $message, int $padding_top = 20, int $padding_bottom = 24, int $padding_horizontal = 0, int $max_width = 0 ): void {
		$padding_horizontal = max( 0, $padding_horizontal );
		$max_width          = max( 0, $max_width );
		?>
		<tr>
			<td align="center" style="padding:<?php echo esc_attr( $padding_top ); ?>px <?php echo esc_attr( $padding_horizontal ); ?>px <?php echo esc_attr( $padding_bottom ); ?>px;">
				<?php if ( $max_width > 0 ) : ?>
					<table role="presentation" width="<?php echo esc_attr( $max_width ); ?>" cellpadding="0" cellspacing="0" border="0" align="center" class="bsc-email-copy-frame" style="border-collapse:collapse;margin:0 auto;max-width:<?php echo esc_attr( $max_width ); ?>px;width:<?php echo esc_attr( $max_width ); ?>px;">
						<tr>
							<td align="center" class="bsc-email-body-copy" style="color:#363636;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>text-align:center;">
								<?php echo wp_kses_post( bsc_email_supported_html( $message ) ); ?>
							</td>
						</tr>
					</table>
				<?php else : ?>
					<div class="bsc-email-body-copy" style="color:#363636;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>text-align:center;">
						<?php echo wp_kses_post( bsc_email_supported_html( $message ) ); ?>
					</div>
				<?php endif; ?>
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
			<td align="center" class="bsc-email-component-pad" style="padding:12px 66px 34px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td align="left" class="bsc-email-body-copy" style="color:#363636;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>padding:0 0 12px;">
							<strong>Estado:</strong> <?php echo esc_html( $status ); ?>
						</td>
					</tr>
					<tr>
						<td>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #343434;border-radius:8px;height:16px;">
								<tr>
									<td width="<?php echo esc_attr( $percent ); ?>%" class="bsc-email-spacer" style="background:<?php echo esc_attr( $fill ); ?>;border-radius:8px;height:14px;font-size:0;line-height:0;">&nbsp;</td>
									<td width="<?php echo esc_attr( 100 - $percent ); ?>%" class="bsc-email-spacer" style="font-size:0;line-height:0;">&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding-top:8px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td align="left" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>font-weight:800;">|</td>
									<td align="right" class="bsc-email-body-copy" style="color:#a5a5a5;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>">|</td>
								</tr>
								<tr>
									<td align="left" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>font-weight:800;"><?php echo esc_html( $left_label ); ?></td>
									<td align="right" class="bsc-email-body-copy" style="color:#a5a5a5;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>"><?php echo esc_html( $right_label ); ?></td>
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
	function bsc_email_render_coupon( string $code, string $headline, string $meta, string $accent = '#f4b5c7', string $graphic = 'smile', int $border_width = 2, int $check_width = 27, string $typography_variant = 'display', int $card_width = 382, bool $fixed_card_width = false ): void {
		$graphic_file      = 'heart' === $graphic ? 'bsc-email-coupon-heart-pink.png' : 'bsc-email-coupon-smile-pink.png';
		$graphic_width     = 'heart' === $graphic ? 136 : 104;
		$border_width      = max( 1, min( 4, $border_width ) );
		$check_width       = max( 20, min( 80, $check_width ) );
		$check_cell_width  = max( 30, $check_width );
		$is_standard_type  = 'standard' === $typography_variant;
		$headline_class    = $is_standard_type ? 'bsc-email-section-title' : 'bsc-email-display';
		$headline_role     = $is_standard_type ? 'section-title' : 'display';
		$meta_class        = $is_standard_type ? 'bsc-email-body-copy' : 'bsc-email-caption-strong';
		$meta_role         = $is_standard_type ? 'body' : 'caption-strong';
		$card_width        = max( 280, min( 600, $card_width ) );
		$card_class        = $fixed_card_width ? 'bsc-email-coupon-card bsc-email-coupon-card--fixed' : 'bsc-email-fluid bsc-email-coupon-card';
		$card_pad_class    = $fixed_card_width ? ' bsc-email-fixed-card-pad' : '';
		$important         = $fixed_card_width ? '!important' : '';
		$fixed_layout      = $fixed_card_width ? 'table-layout:fixed!important;' : '';
		$card_width_style  = sprintf( 'max-width:%1$dpx%2$s;width:%1$dpx%2$s;%3$s', $card_width, $important, $fixed_layout );
		?>
		<tr>
			<td align="center" class="bsc-email-component-pad<?php echo esc_attr( $card_pad_class ); ?>" style="padding:0 52px 26px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="<?php echo esc_attr( $card_width ); ?>" class="<?php echo esc_attr( $card_class ); ?>" style="border:<?php echo esc_attr( $border_width ); ?>px solid #303030;border-radius:7px;<?php echo esc_attr( $card_width_style ); ?>">
					<tr>
						<td style="padding:16px 16px 15px 18px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="padding:0;">
										<table role="presentation" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td width="<?php echo esc_attr( $check_cell_width ); ?>" class="bsc-email-coupon-check" style="padding:0 8px 0 0;">
													<?php bsc_email_render_asset_img( 'bsc-email-coupon-check-pink.png', $check_width, '' ); ?>
												</td>
												<td class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>white-space:nowrap;">
													<?php echo esc_html( $code ); ?>
												</td>
											</tr>
										</table>
									</td>
									<td rowspan="3" align="right" valign="middle" width="132" class="bsc-email-coupon-graphic" style="font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;padding-left:8px;">
										<?php bsc_email_render_asset_img( $graphic_file, $graphic_width, '' ); ?>
									</td>
								</tr>
								<tr>
									<td class="<?php echo esc_attr( $headline_class ); ?> bsc-email-coupon-headline" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( $headline_role ) ); ?>padding-top:3px;">
										<?php echo esc_html( $headline ); ?>
									</td>
								</tr>
								<tr>
									<td class="<?php echo esc_attr( $meta_class ); ?> bsc-email-coupon-meta" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( $meta_role ) ); ?>padding-top:10px;">
										<span style="background:<?php echo esc_attr( $accent ); ?>;border:1px solid #303030;border-radius:10px;display:inline-block;padding:3px 9px;">Activo</span>
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
		if ( function_exists( 'bsc_email_publicize_url' ) ) {
			$url = bsc_email_publicize_url( $url );
		}
		?>
		<tr>
			<td align="center" class="bsc-email-component-pad" style="padding:2px 54px 30px;">
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="382" class="bsc-email-fluid bsc-email-tracking-card" style="border:1px solid #303030;border-radius:7px;max-width:382px;width:382px;">
					<tr>
						<td width="218" class="bsc-email-tracking-content" style="padding:16px 18px;">
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td style="padding:0;">
										<table role="presentation" cellpadding="0" cellspacing="0" border="0">
											<tr>
												<td width="34" style="padding:0 8px 0 0;">
													<?php bsc_email_render_asset_img( 'bsc-email-coupon-check-pink.png', 30, '' ); ?>
												</td>
												<td class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>white-space:nowrap;">
													Pedido enviado!
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr>
									<td class="bsc-email-display" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'display' ) ); ?>padding-top:1px;white-space:nowrap;">
										<?php echo esc_html( $tracking_code ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:6px 5px 0 0;">
										<a href="<?php echo esc_url( $url ); ?>" class="bsc-email-button bsc-email-tracking-button" style="background:#f4b5c7;border:1px solid #303030;border-radius:14px;color:#303030;display:inline-block;<?php echo esc_attr( bsc_email_typography_style( 'button' ) ); ?>padding:6px 14px;text-decoration:none;">Rastrear mi pedido</a>
									</td>
								</tr>
							</table>
						</td>
						<td align="right" valign="middle" width="156" class="bsc-email-ticket-graphic" style="padding:8px 14px 8px 0;">
							<?php bsc_email_render_asset_img( 'bsc-email-tracking-products.png', 148, '' ); ?>
						</td>
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
			<td align="center" class="bsc-email-component-pad" style="padding:0 66px 28px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<td colspan="2" align="left" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding:0 0 22px;">
							Resumen de tu pedido:
						</td>
					</tr>
					<?php foreach ( $order->get_items() as $item ) : ?>
						<tr>
							<td align="left" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>padding:0 0 2px;">
								<?php echo esc_html( $item->get_quantity() . ' X ' . $item->get_name() ); ?>
							</td>
							<td align="right" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>padding:0 0 2px;white-space:nowrap;">
								<?php echo esc_html( bsc_email_money( $item->get_total() ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( $order->get_items( 'shipping' ) as $shipping_item ) : ?>
						<tr>
							<td align="left" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>padding:0 0 2px;">
								<?php echo esc_html( $shipping_item->get_name() ); ?>
							</td>
							<td align="right" class="bsc-email-body-copy" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>padding:0 0 2px;white-space:nowrap;">
								<?php echo esc_html( bsc_email_money( $shipping_item->get_total() ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td colspan="2" class="bsc-email-spacer" style="border-bottom:1px solid #303030;font-size:0;line-height:0;padding-top:14px;">&nbsp;</td>
					</tr>
					<tr>
						<td align="left" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding-top:16px;">
							Total
						</td>
						<td align="right" class="bsc-email-section-title" style="color:#303030;<?php echo esc_attr( bsc_email_typography_style( 'section-title' ) ); ?>padding-top:16px;white-space:nowrap;">
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
			<td align="center" class="bsc-email-component-pad" style="padding:6px 34px 26px;">
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
					<tr>
						<?php foreach ( $products as $product ) : ?>
							<?php
							$name      = (string) ( $product['name'] ?? '' );
							$url       = (string) ( $product['url'] ?? bsc_email_shop_url() );
							$image_url = (string) ( $product['image_url'] ?? '' );
							if ( function_exists( 'bsc_email_publicize_url' ) ) {
								$url = bsc_email_publicize_url( $url );
							}
							if ( function_exists( 'bsc_email_publicize_image_url' ) ) {
								$image_url = bsc_email_publicize_image_url( $image_url );
							}
							?>
							<td align="center" valign="top" width="33.333%" class="bsc-email-product-cell" style="padding:0 12px 12px;">
								<a href="<?php echo esc_url( $url ); ?>" style="display:block;text-decoration:none;">
									<?php if ( '' !== $image_url ) : ?>
										<img src="<?php echo esc_url( $image_url ); ?>" width="170" alt="<?php echo esc_attr( $name ); ?>" style="border:0;display:block;height:auto;margin:0 auto 14px;max-width:170px;width:170px;">
									<?php else : ?>
										<span style="background:#f4f4f4;display:block;height:170px;margin:0 auto 14px;width:170px;">&nbsp;</span>
									<?php endif; ?>
									<span class="bsc-email-body-copy" style="color:#303030;display:block;<?php echo esc_attr( bsc_email_typography_style( 'body' ) ); ?>font-weight:900;min-height:38px;text-align:center;"><?php echo esc_html( $name ); ?></span>
								</a>
								<a href="<?php echo esc_url( $url ); ?>" class="bsc-email-button" style="background:#cceff7;border-radius:18px;color:#303030;display:inline-block;<?php echo esc_attr( bsc_email_typography_style( 'button' ) ); ?>margin-top:14px;padding:8px 28px;text-align:center;text-decoration:none;white-space:nowrap;">¡ Re stock !</a>
							</td>
						<?php endforeach; ?>
					</tr>
				</table>
			</td>
		</tr>
		<?php
	}
}
