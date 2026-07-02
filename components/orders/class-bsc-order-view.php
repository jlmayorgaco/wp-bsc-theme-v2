<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/components/orders/order-progress-bar.php';

class BSC_Order_View {
	private WC_Order $order;
	private array $page_classes = array( 'bsc__page--thankyou', 'bsc__page--view-order' );
	private ?string $logo_src   = null;
	private string $logo_alt    = 'Bubble Skin Care';
	private string $title       = '';
	private string $message     = '';
	private array $actions      = array();
	private string $variant     = 'view-order';

	public function __construct( WC_Order $order ) {
		$this->order = $order;
	}

	public function set_variant( string $variant ): void {
		if ( 'thankyou' === $variant ) {
			$this->variant = 'thankyou';
		}
	}

	public function set_page_classes( array $page_classes ): void {
		$sanitized = array_values(
			array_filter(
				array_map( 'sanitize_html_class', $page_classes )
			)
		);

		if ( ! empty( $sanitized ) ) {
			$this->page_classes = $sanitized;
		}
	}

	public function set_logo( string $logo_src, string $logo_alt = 'Bubble Skin Care' ): void {
		$this->logo_src = $logo_src;
		$this->logo_alt = $logo_alt;
	}

	public function set_heading( string $title, string $message = '' ): void {
		$this->title   = $title;
		$this->message = $message;
	}

	public function set_actions( array $actions ): void {
		$this->actions = array_values( $actions );
	}

	public function render(): void {
		?>
		<main class="bsc bsc__page <?php echo esc_attr( implode( ' ', $this->page_classes ) ); ?>">
			<div class="bsc__container bsc__thankyou-container">
			<?php $this->render_header(); ?>
			<?php $this->render_overview(); ?>

			<div class="bsc__order-review product-details-and-shipping">
				<div class="bsc__order-review product-details"<?php echo 'thankyou' === $this->variant ? '' : ' id="bsc-order-items"'; ?>>
				<?php $this->render_items(); ?>
				<?php $this->render_summary(); ?>
				</div>

				<div class="bsc__order-review shipping-details">
				<?php $this->render_shipping_details(); ?>
				</div>
			</div>

			<?php $this->render_actions(); ?>
			</div>
		</main>
		<?php
	}

	private function render_header(): void {
		if ( ! $this->logo_src && '' === $this->title && '' === $this->message ) {
			return;
		}

		if ( $this->logo_src ) :
			?>
			<img
				class="bsc__thankyou-logo"
				src="<?php echo esc_url( $this->logo_src ); ?>"
				alt="<?php echo esc_attr( $this->logo_alt ); ?>"
			/>
			<?php
		endif;

		if ( '' !== $this->title ) :
			?>
			<h1 class="bsc__title bsc__title--centered"><?php echo esc_html( $this->title ); ?></h1>
			<?php
		endif;

		if ( '' !== $this->message ) :
			?>
			<p class="bsc__thankyou-message"><?php echo wp_kses_post( $this->message ); ?></p>
			<?php
		endif;
	}

	private function render_overview(): void {
		$order_item_class    = 'bsc__order-overview__item';
		$date_item_class     = 'bsc__order-overview__item';
		$progress_item_class = 'bsc__order-overview__item bsc__order-overview__item--progress';

		if ( 'thankyou' === $this->variant ) {
			$order_item_class    .= ' bsc__order-overview__item--order';
			$date_item_class     .= ' bsc__order-overview__item--date';
			$progress_item_class .= ' bsc__order-overview__item--progress-fixed';
		}
		?>
		<ul class="bsc__order-overview">
			<li class="<?php echo esc_attr( $order_item_class ); ?>">
			<div class="bsc__order-overview__title">N&uacute;mero de orden:</div>
			<div class="bsc__order-overview__content">#<?php echo esc_html( $this->order->get_order_number() ); ?></div>
			</li>
			<li class="<?php echo esc_attr( $date_item_class ); ?>">
			<div class="bsc__order-overview__title">Fecha:</div>
			<div class="bsc__order-overview__content"><?php echo esc_html( wc_format_datetime( $this->order->get_date_created() ) ); ?></div>
			</li>
			<li class="<?php echo esc_attr( $progress_item_class ); ?>">
			<div class="bsc__order-overview__title">Estado:</div>
			<div class="bsc__order-overview__content">
				<?php
				$bar = new BSC_Order_Progress_Bar();
				$bar->setStatus( bsc_map_order_to_bar( $this->order ) );
				$bar->render();
				?>
			</div>
			</li>
		</ul>
		<?php
	}

	private function render_items(): void {
		foreach ( $this->order->get_items() as $item ) {
			$product       = $item->get_product();
			$product_name  = $item->get_name();
			$product_qty   = $item->get_quantity();
			$product_total = $item->get_total();
			$product_price = wc_price( $product_total );
			$product_link  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
			$brand_data    = array(
				'name' => '',
				'link' => '',
			);
			$thumbnail_url = get_template_directory_uri() . '/images/product-placeholder-wp.jpg';

			if ( $product instanceof WC_Product ) {
				$product_link  = get_permalink( $product->get_id() );
				$brand_data    = $this->get_brand_data( $product );
				$thumbnail_url = $this->get_thumbnail_url( $product );
			}
			?>
			<div class="bsc__order-review__item">
				<a href="<?php echo esc_url( $product_link ); ?>" class="bsc__order-review__image-link">
				<img
					src="<?php echo esc_url( $thumbnail_url ); ?>"
					alt="<?php echo esc_attr( $product_name ); ?>"
					class="bsc__order-review__image"
					loading="lazy"
					decoding="async"
				/>
				<div class="bsc__order-review__badge"><span><?php echo esc_html( $product_qty ); ?></span></div>
				</a>

				<div class="bsc__order-review__info">
				<div class="bsc__order-review__name">
					<p>
					<a href="<?php echo esc_url( $product_link ); ?>"><?php echo esc_html( $product_name ); ?></a>
					<strong>x<?php echo esc_html( $product_qty ); ?></strong>
					</p>
					<?php if ( $brand_data['name'] ) : ?>
					<p class="bsc__order-review__brand">
						<a href="<?php echo esc_url( $brand_data['link'] ); ?>"><?php echo esc_html( $brand_data['name'] ); ?></a>
					</p>
					<?php endif; ?>
				</div>

				<div class="bsc__order-review__price"><?php echo wp_kses_post( $product_price ); ?></div>
				</div>
			</div>
			<?php
		}
	}

	private function render_summary(): void {
		$summary_nquantity = $this->order->get_item_count();
		$summary_total     = $this->order->get_subtotal();
		$summary_discount  = $this->order->get_discount_total();
		$summary_shipping  = $this->order->get_shipping_total();
		$grand_total       = $summary_total + $summary_shipping - $summary_discount;
		?>
		<div class="bsc__order-summary">
			<div class="summary__row summary__quantity-items">
			<div class="summary__title"><?php echo esc_html( $summary_nquantity ); ?> productos</div>
			<div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_total ) ); ?></div>
			</div>

			<div class="summary__row summary__total-discounts">
			<div class="summary__title">descuento adicional</div>
			<div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_discount ) ); ?></div>
			</div>

			<div class="summary__row summary__shipping-cost">
			<div class="summary__title">env&iacute;o</div>
			<div class="summary__content"><?php echo wp_kses_post( wc_price( $summary_shipping ) ); ?></div>
			</div>

			<div class="summary__divider"></div>

			<h1 class="bsc__order-summary__total">Total <strong><?php echo wp_kses_post( wc_price( $grand_total ) ); ?></strong></h1>
		</div>
		<?php
	}

	private function render_shipping_details(): void {
		$bubble_points = $this->get_bubble_points_value();
		?>
		<h2 class="shipping-details__title">Datos de <strong>entrega</strong></h2>
		<ul class="shipping-details__list">
			<?php foreach ( $this->get_shipping_rows() as $row ) : ?>
				<?php
				if ( 'thankyou' === $this->variant && 'Documento' === $row['label'] && '' === trim( (string) $row['value'] ) ) {
					continue;
				}

				$item_class = 'thankyou' === $this->variant ? ' class="shipping-details__item"' : '';
				?>
			<li<?php echo wp_kses_post( $item_class ); ?>><strong><?php echo esc_html( $row['label'] ); ?>:</strong> <?php echo esc_html( $row['value'] ); ?></li>
			<?php endforeach; ?>
		</ul>

		<?php if ( 'thankyou' !== $this->variant || $bubble_points > 0 ) : ?>
			<hr class="shipping-details__divider">
			<p class="shipping-details__subtitle"><?php echo 'thankyou' === $this->variant ? 'Puntos generados en esta compra' : 'Bubble Points generados en esta compra'; ?></p>
			<div class="bsc__points">
			<img
				class="bsc__points__icon"
				src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_checkout_points.png"
				alt="Bubble Points"
				<?php echo 'thankyou' === $this->variant ? 'width="48" height="48" loading="lazy"' : ''; ?>
			>
			<h3 class="bsc__points__text">¡ <strong><?php echo esc_html( $bubble_points ); ?></strong> Bubble Points !</h3>
			</div>
		<?php endif; ?>
		<?php
	}

	private function render_actions(): void {
		if ( empty( $this->actions ) ) {
			return;
		}
		?>
		<div class="bsc__thankyou-actions">
			<?php foreach ( $this->actions as $action ) : ?>
				<?php
				$label = isset( $action['label'] ) ? (string) $action['label'] : '';
				$url   = isset( $action['url'] ) ? (string) $action['url'] : '';

				if ( '' === $label || '' === $url ) {
					continue;
				}

				$class = 'bsc__button';

				if ( ! empty( $action['secondary'] ) ) {
					$class .= ' bsc__button--secondary';
				}
				?>
			<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( $url ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private function get_brand_data( WC_Product $product ): array {
		$brand      = $product->get_attribute( 'brand' ) ?: 'Sin marca';
		$categories = get_the_terms( $product->get_id(), 'product_cat' );

		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			foreach ( $categories as $cat ) {
				if ( false !== strpos( $cat->slug, '-marca' ) ) {
					return array(
						'name' => $cat->name,
						'link' => get_term_link( $cat ),
					);
				}
			}
		}

		return array(
			'name' => $brand,
			'link' => '#',
		);
	}

	private function get_thumbnail_url( WC_Product $product ): string {
		if ( $product->get_image_id() ) {
			$image = wp_get_attachment_image_src( $product->get_image_id(), 'woocommerce_thumbnail' );

			if ( is_array( $image ) && ! empty( $image[0] ) ) {
				return $image[0];
			}
		}

		return get_template_directory_uri() . '/images/product-placeholder-wp.jpg';
	}

	private function get_shipping_rows(): array {
		$shipping_name    = trim( $this->order->get_shipping_first_name() . ' ' . $this->order->get_shipping_last_name() );
		$shipping_city    = (string) $this->order->get_shipping_city();
		$shipping_address = trim( $this->order->get_shipping_address_1() . ' ' . $this->order->get_shipping_address_2() );

		if ( 'thankyou' === $this->variant ) {
			if ( '' === $shipping_name ) {
				$shipping_name = trim( $this->order->get_billing_first_name() . ' ' . $this->order->get_billing_last_name() );
			}

			if ( '' === $shipping_city ) {
				$shipping_city = (string) $this->order->get_billing_city();
			}

			if ( '' === $shipping_address ) {
				$shipping_address = trim( $this->order->get_billing_address_1() . ' ' . $this->order->get_billing_address_2() );
			}
		}

		return array(
			array(
				'label' => 'Nombre',
				'value' => $shipping_name,
			),
			array(
				'label' => 'Documento',
				'value' => (string) $this->order->get_meta( '_billing_cedula' ),
			),
			array(
				'label' => 'Ciudad',
				'value' => $shipping_city,
			),
			array(
				'label' => 'Dirección',
				'value' => $shipping_address,
			),
			array(
				'label' => 'Teléfono',
				'value' => (string) $this->order->get_billing_phone(),
			),
		);
	}

	private function get_bubble_points_value(): int {
		if ( 'thankyou' === $this->variant ) {
			return function_exists( 'bsc_get_order_bubble_points_balance' )
				? (int) bsc_get_order_bubble_points_balance( $this->order )
				: max( 0, (int) floor( (float) $this->order->get_total() / 1000 ) );
		}

		return function_exists( 'bsc_get_order_bubble_points_earned' )
			? (int) bsc_get_order_bubble_points_earned( $this->order )
			: max( 0, (int) floor( (float) $this->order->get_total() / 1000 ) );
	}
}
