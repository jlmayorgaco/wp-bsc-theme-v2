<?php
/**
 * Conversion dashboard powered by existing BSC metrics.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Conversion {
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_admin_menu(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'bsc-dashboard',
			'Conversion BSC',
			'Conversion',
			'manage_woocommerce',
			'bsc-conversion',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-conversion' !== $page ) {
			return;
		}

		if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
			bsc_enqueue_admin_ui_assets();
		}

		wp_enqueue_style(
			'bsc-growth-admin',
			get_template_directory_uri() . '/admin/bsc-growth.css',
			array( 'bsc-admin-ui' ),
			BSC_Growth_Plugin::admin_css_version()
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
		}

		$range   = self::get_range();
		$summary = function_exists( 'bsc_metrics_get_summary' )
			? bsc_metrics_get_summary( $range['start_date'], $range['end_date'] )
			: self::empty_summary();

		$counters = $summary['counters'];
		$funnel   = self::build_funnel( $counters );
		?>
		<div class="wrap bsc-growth-admin">
			<h1>Conversion BSC</h1>
			<p class="bsc-growth-admin__intro">Embudo de producto a compra, busquedas sin resultado y abandono para priorizar mejoras de venta.</p>

			<form class="bsc-growth-admin__toolbar" method="get">
				<input type="hidden" name="page" value="bsc-conversion">
				<label>
					<span>Rango</span>
					<select name="range">
						<option value="today" <?php selected( $range['key'], 'today' ); ?>>Hoy</option>
						<option value="7d" <?php selected( $range['key'], '7d' ); ?>>Ultimos 7 dias</option>
						<option value="30d" <?php selected( $range['key'], '30d' ); ?>>Ultimos 30 dias</option>
					</select>
				</label>
				<button class="button button-primary" type="submit">Aplicar</button>
			</form>

			<section class="bsc-growth-admin__card">
				<h2>Embudo</h2>
				<div class="bsc-growth-admin__funnel">
					<?php foreach ( $funnel as $step ) : ?>
						<div class="bsc-growth-admin__funnel-step">
							<span><?php echo esc_html( $step['label'] ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $step['value'] ) ); ?></strong>
							<small><?php echo esc_html( $step['rate'] ); ?></small>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="bsc-growth-admin__metrics-grid">
				<?php self::render_metric_card( 'Add to cart / producto', self::format_rate( $counters['add_to_cart'], $counters['view_item'] ) ); ?>
				<?php self::render_metric_card( 'Checkout / carrito', self::format_rate( $counters['begin_checkout'], $counters['view_cart'] ) ); ?>
				<?php self::render_metric_card( 'Compra / checkout', self::format_rate( $counters['purchase'], $counters['begin_checkout'] ) ); ?>
				<?php self::render_metric_card( 'Ingresos medidos', wp_strip_all_tags( BSC_Growth_Plugin::price_html( (float) $counters['purchase_revenue'] ) ) ); ?>
			</div>

			<div class="bsc-growth-admin__layout">
				<section class="bsc-growth-admin__card">
					<h2>Productos con mas senales</h2>
					<table class="widefat striped bsc-growth-admin__table">
						<thead>
							<tr>
								<th>Producto</th>
								<th>Vistas</th>
								<th>Add to cart</th>
								<th>Compras</th>
								<th>Ingresos</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $summary['top_products'] ) ) : ?>
								<tr><td colspan="5">Sin datos en este rango.</td></tr>
							<?php endif; ?>
							<?php foreach ( $summary['top_products'] as $product ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( $product['edit_url'] ); ?>"><?php echo esc_html( $product['title'] ); ?></a></td>
									<td><?php echo esc_html( number_format_i18n( (int) $product['views'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $product['add_to_cart'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $product['purchases'] ) ); ?></td>
									<td><?php echo wp_kses_post( BSC_Growth_Plugin::price_html( (float) $product['revenue'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<section class="bsc-growth-admin__card">
					<h2>Busquedas sin resultado</h2>
					<table class="widefat striped bsc-growth-admin__table">
						<thead>
							<tr>
								<th>Busqueda</th>
								<th>Veces</th>
								<th>Ultima vez</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $summary['no_result_searches'] ) ) : ?>
								<tr><td colspan="3">Sin busquedas perdidas.</td></tr>
							<?php endif; ?>
							<?php foreach ( $summary['no_result_searches'] as $search ) : ?>
								<tr>
									<td><?php echo esc_html( $search['query'] ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $search['no_results'] ) ); ?></td>
									<td><?php echo esc_html( (string) $search['last_at'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			</div>

			<section class="bsc-growth-admin__card">
				<h2>Carritos abandonados</h2>
				<div class="bsc-growth-admin__summary-grid">
					<?php foreach ( $summary['abandoned_snapshot'] as $label => $value ) : ?>
						<div><strong><?php echo esc_html( number_format_i18n( (int) $value ) ); ?></strong><span><?php echo esc_html( self::abandoned_label( (string) $label ) ); ?></span></div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php
	}

	private static function build_funnel( array $counters ): array {
		return array(
			array(
				'label' => 'Visitas producto',
				'value' => (int) $counters['view_item'],
				'rate'  => 'Base',
			),
			array(
				'label' => 'Add to cart',
				'value' => (int) $counters['add_to_cart'],
				'rate'  => self::format_rate( $counters['add_to_cart'], $counters['view_item'] ),
			),
			array(
				'label' => 'Carrito',
				'value' => (int) $counters['view_cart'],
				'rate'  => self::format_rate( $counters['view_cart'], $counters['add_to_cart'] ),
			),
			array(
				'label' => 'Checkout',
				'value' => (int) $counters['begin_checkout'],
				'rate'  => self::format_rate( $counters['begin_checkout'], $counters['view_cart'] ),
			),
			array(
				'label' => 'Compra',
				'value' => (int) $counters['purchase'],
				'rate'  => self::format_rate( $counters['purchase'], $counters['begin_checkout'] ),
			),
		);
	}

	private static function render_metric_card( string $label, string $value ): void {
		?>
		<div class="bsc-growth-admin__metric">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	private static function get_range(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only report filter.
		$key = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '7d';

		if ( ! in_array( $key, array( 'today', '7d', '30d' ), true ) ) {
			$key = '7d';
		}

		$now   = current_time( 'timestamp' );
		$today = wp_date( 'Y-m-d', $now );
		$start = $today;

		if ( '7d' === $key ) {
			$start = wp_date( 'Y-m-d', strtotime( '-6 days', $now ) );
		} elseif ( '30d' === $key ) {
			$start = wp_date( 'Y-m-d', strtotime( '-29 days', $now ) );
		}

		return array(
			'key'        => $key,
			'start_date' => $start,
			'end_date'   => $today,
		);
	}

	private static function empty_summary(): array {
		return array(
			'counters'           => function_exists( 'bsc_metrics_empty_counters' ) ? bsc_metrics_empty_counters() : array_fill_keys( array( 'view_item', 'view_cart', 'begin_checkout', 'add_to_cart', 'purchase', 'purchase_revenue' ), 0 ),
			'top_products'       => array(),
			'no_result_searches' => array(),
			'abandoned_snapshot' => array(
				'active'    => 0,
				'reminded'  => 0,
				'recovered' => 0,
				'converted' => 0,
			),
		);
	}

	private static function format_rate( $numerator, $denominator ): string {
		$rate = function_exists( 'bsc_metrics_rate' )
			? bsc_metrics_rate( (float) $numerator, (float) $denominator )
			: ( (float) $denominator > 0 ? round( ( (float) $numerator / (float) $denominator ) * 100, 1 ) : 0.0 );

		return number_format_i18n( $rate, 1 ) . '%';
	}

	private static function abandoned_label( string $key ): string {
		$labels = array(
			'active'    => 'Activos',
			'reminded'  => 'Recordados',
			'recovered' => 'Recuperados',
			'converted' => 'Convertidos',
		);

		return $labels[ $key ] ?? $key;
	}
}
