<?php
defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/checkout-review-summary-helpers.php';

class BSC_Checkout_Review_Summary {

	protected $cart;

	public function __construct() {
		$this->cart = WC()->cart;
	}

	public function render(): void {
		bsc_recalculate_checkout_totals();

		$summary        = bsc_get_checkout_summary_payload();
		$cart_count     = (int) $summary['cart_count'];
		$item_label     = html_entity_decode( '&iacute;tem', ENT_QUOTES, 'UTF-8' );
		$shipping_label = html_entity_decode( 'Env&iacute;o', ENT_QUOTES, 'UTF-8' );
		$count_label    = sprintf(
			'Subtotal (<span id="review-summary__cart-count">%1$d</span> %2$s%3$s)',
			$cart_count,
			$item_label,
			$cart_count !== 1 ? 's' : ''
		);

		echo '<div class="bsc bsc__review-summary" id="bsc-review-summary">';
		echo '  <div class="review-summary__container">';
		$this->render_free_shipping_progress();

		$this->render_row(
			$count_label,
			$summary['subtotal_html'],
			'review-summary__subtotal'
		);

		$this->render_row(
			'Subtotal con descuento',
			$summary['subtotal_discounted_html'],
			'review-summary__subtotal-discounted'
		);

		$this->render_row(
			$shipping_label,
			$summary['shipping_total_html'],
			'review-summary__shipping'
		);

		$this->render_row(
			'Total',
			'<strong>' . $summary['cart_total_html'] . '</strong>',
			'review-summary__total',
			true
		);

		echo '  </div>';
		echo '</div>';
	}

	protected function render_row( string $label, string $value, string $id, bool $highlight = false ): void {
		$row_class = 'review-summary__row' . ( $highlight ? ' review-summary__row--total' : '' );

		echo '<div class="' . esc_attr( $row_class ) . '">';
		echo '  <div class="review-summary__label">' . wp_kses_post( $label ) . '</div>';
		echo '  <div class="review-summary__value" id="' . esc_attr( $id ) . '">' . wp_kses_post( $value ) . '</div>';
		echo '</div>';
	}

	protected function render_free_shipping_progress(): void {
		$progress = function_exists( 'bsc_get_free_shipping_progress_payload' )
			? bsc_get_free_shipping_progress_payload()
			: array();

		if ( empty( $progress ) || (float) ( $progress['threshold'] ?? 0 ) <= 0 ) {
			return;
		}

		$percent = max( 0, min( 100, (int) ( $progress['percent'] ?? 0 ) ) );
		$bucket  = (int) ( round( $percent / 10 ) * 10 );
		$class   = ! empty( $progress['qualified'] )
			? 'review-summary__shipping-progress is-qualified shipping-progress--' . $bucket
			: 'review-summary__shipping-progress shipping-progress--' . $bucket;

		echo '<div class="' . esc_attr( $class ) . '">';
		echo '  <div class="shipping-progress__label">' . esc_html( (string) ( $progress['message'] ?? '' ) ) . '</div>';
		echo '  <div class="shipping-progress__track" aria-hidden="true"><span></span></div>';
		echo '</div>';
	}
}
