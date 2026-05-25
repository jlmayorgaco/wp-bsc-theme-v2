<?php
/**
 * Account-facing repurchase recommendations.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Repurchase {
	public static function register_hooks(): void {
		add_action( 'woocommerce_account_dashboard', array( __CLASS__, 'render_account_panel' ), 35 );
	}

	public static function render_account_panel(): void {
		if ( ! is_user_logged_in() || ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$items = self::get_customer_items( get_current_user_id(), '', 3 );

		if ( empty( $items ) ) {
			return;
		}

		?>
		<section class="bsc-growth-repurchase" aria-labelledby="bsc-growth-repurchase-title">
			<div class="bsc-growth-repurchase__header">
				<h2 id="bsc-growth-repurchase-title">Reponer rutina</h2>
				<a href="<?php echo esc_url( BSC_Growth_Plugin::shop_url() ); ?>">Ver tienda</a>
			</div>
			<div class="bsc-growth-repurchase__list">
				<?php foreach ( $items as $item ) : ?>
					<article class="bsc-growth-repurchase__item">
						<a href="<?php echo esc_url( $item['url'] ); ?>" class="bsc-growth-repurchase__image">
							<img src="<?php echo esc_url( $item['image'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>">
						</a>
						<div>
							<h3><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['name'] ); ?></a></h3>
							<p><?php echo esc_html( $item['message'] ); ?></p>
						</div>
						<a class="bsc__button bsc-growth-repurchase__button" href="<?php echo esc_url( $item['add_to_cart_url'] ); ?>">Reponer</a>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	public static function get_customer_items( int $user_id, string $email = '', int $limit = 5 ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$args = array(
			'limit'   => 20,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array( 'completed', 'processing', 'shipped' ),
		);

		if ( $user_id > 0 ) {
			$args['customer_id'] = $user_id;
		} elseif ( is_email( $email ) ) {
			$args['billing_email'] = $email;
		} else {
			return array();
		}

		$orders = BSC_Growth_Plugin::orders( $args );
		$latest = array();

		foreach ( $orders as $order ) {
			$created = $order->get_date_created();

			if ( ! $created ) {
				continue;
			}

			foreach ( $order->get_items( 'line_item' ) as $line_item ) {
				$product_id = (int) $line_item->get_product_id();

				if ( $product_id <= 0 || isset( $latest[ $product_id ] ) ) {
					continue;
				}

				$latest[ $product_id ] = $created->getTimestamp();
			}
		}

		$items = array();
		$now   = current_time( 'timestamp' );

		foreach ( $latest as $product_id => $ordered_at ) {
			$product = wc_get_product( $product_id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				continue;
			}

			$days      = self::get_product_repurchase_days( $product_id );
			$due_at    = $ordered_at + ( $days * DAY_IN_SECONDS );
			$days_left = (int) ceil( ( $due_at - $now ) / DAY_IN_SECONDS );

			if ( $days_left > 14 ) {
				continue;
			}

			$image_id = $product->get_image_id();
			$items[]  = array(
				'product_id'       => $product_id,
				'name'             => wp_strip_all_tags( $product->get_name() ),
				'url'              => get_permalink( $product_id ),
				'image'            => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : BSC_Growth_Plugin::placeholder_image(),
				'add_to_cart_url'  => $product->add_to_cart_url(),
				'due_at'           => $due_at,
				'days_left'        => $days_left,
				'message'          => self::format_due_message( $days_left ),
				'repurchase_days'  => $days,
				'last_ordered_at'  => $ordered_at,
			);
		}

		usort(
			$items,
			static fn( array $a, array $b ): int => $a['due_at'] <=> $b['due_at']
		);

		return array_slice( $items, 0, $limit );
	}

	private static function get_product_repurchase_days( int $product_id ): int {
		if ( function_exists( 'bsc_get_product_repurchase_days' ) ) {
			return max( 1, (int) bsc_get_product_repurchase_days( $product_id ) );
		}

		$days = (int) get_post_meta( $product_id, '_bsc_repurchase_days', true );

		return $days > 0 ? $days : 45;
	}

	private static function format_due_message( int $days_left ): string {
		if ( $days_left <= 0 ) {
			return 'Puede que ya sea momento de reponerlo.';
		}

		if ( 1 === $days_left ) {
			return 'Se estima que se acaba manana.';
		}

		return sprintf( 'Se estima recompra en %d dias.', $days_left );
	}
}
