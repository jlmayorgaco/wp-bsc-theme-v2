<?php
defined( 'ABSPATH' ) || exit;

class BSC_Checkout_Cart {

	protected $cart_items;

	public function __construct() {
		$this->cart_items = WC()->cart->get_cart();
	}

	public function render(): void {
		echo '<section class="bsc bsc__checkout-cart">';
		echo '  <div class="checkout-cart__container">';
		echo '    <ul class="checkout-cart__items">';
		foreach ($this->cart_items as $key => $item) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_cart_item() returns escaped checkout item markup.
			echo $this->render_cart_item( $key, $item );
		}
		echo '    </ul>';
		echo '  </div>';
		echo '</section>';
	}

	protected function render_cart_item( string $key, array $item ): string {
		$_product = wc_get_product( $item['product_id'] );
		if (!$_product) {
			return '';
		}

		$name     = $_product->get_name();
		$quantity = $item['quantity'];
		// I-7: use wc_get_price_to_display() to respect sale prices and tax settings
		$unit_price  = (float) wc_get_price_to_display( $_product );
		$price       = wc_price( $unit_price );
		$price_total = wc_price( $quantity * $unit_price );
		$image       = $_product->get_image( 'woocommerce_thumbnail' );

		$link = get_permalink( $_product->get_id() );

		[$brand_name, $brand_link] = $this->get_brand_data( $_product );

		ob_start();
		?>
		<li class="checkout-cart__item" data-product_id="<?php echo esc_attr( $_product->get_id() ); ?>" data-item-key="<?php echo esc_attr( $key ); ?>">
			<div class="item__col col1">
				<div class="item__picture">
					<?php echo wp_kses_post( $image ); ?>
					<label><span><?php echo esc_html( $quantity ); ?></span></label>
				</div>
			</div>
			<div class="item__col col2">
				<div class="row">
					<div class="col_name_brand">
						<h5 class="item__name">
							<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a>
						</h5>
						<h5 class="item__brand">
							<a href="<?php echo esc_url( $brand_link ); ?>"><?php echo esc_html( $brand_name ); ?></a>
						</h5>
					</div>
					<div class="col_total">
						<h5 class="item__total"><?php echo wp_kses_post( $price_total ); ?></h5>
					</div>
				</div>
				<div class="row row_action_buttons bsc-checkout-cart--controls" data-product_id="<?php echo esc_attr( $_product->get_id() ); ?>" data-item-key="<?php echo esc_attr( $key ); ?>">
					<button type="button" class="bsc__qty-minus quantity-btn decrease"><span>-</span></button>
					<button type="button" class="bsc__qty-plus quantity-btn increase"><span>+</span></button>
					<button type="button" class="delete-btn"><span>x</span></button>
					<span class="bsc__qty-value bsc__qty-value--hidden"><?php echo esc_html( $quantity ); ?></span>
				</div>
			</div>
		</li>
		<?php
		return ob_get_clean();
	}

	protected function get_brand_data( $product ): array {
		$brand      = $product->get_attribute( 'brand' ) ?: 'Sin marca';
		$categories = get_the_terms( $product->get_id(), 'product_cat' );

		if (!is_wp_error( $categories ) && !empty( $categories )) {
			foreach ($categories as $cat) {
				if (strpos( $cat->slug, '-marca' ) !== false) {
					return array( $cat->name, get_term_link( $cat ) );
				}
			}
		}

		return array( $brand, '#' );
	}
}
