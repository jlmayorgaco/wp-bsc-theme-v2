<?php
if (!defined( 'ABSPATH' )) {
	exit;
}

require_once get_template_directory() . '/components/orders/order-progress-bar.php';

class BSC_Orders_Table {
	protected $customer_orders;
	protected $current_page = 1;
	protected $button_class = 'bsc__button';

	public function set_customer_orders( $customer_orders ) {
		$this->customer_orders = $customer_orders;
	}

	public function set_current_page( $page ) {
		$this->current_page = max( 1, intval( $page ) );
	}

	public function set_button_class( $class ) {
		$this->button_class = sanitize_html_class( $class );
	}

	public function render() {
		if (empty( $this->customer_orders ) || empty( $this->customer_orders->orders )) {
			echo '<p class="bsc__orders-empty">No has realizado pedidos aún.</p>';
			return;
		}
		?>
		<div class="bsc bsc__orders">

			<!-- Desktop / tablet table -->
			<div class="bsc__orders-table-wrap">
				<table class="bsc__orders-table woocommerce-orders-table shop_table responsive">
					<thead>
						<tr>
							<th class="bsc__orders-header-order-number">Número de Orden</th>
							<th class="bsc__orders-header-order-date">Fecha</th>
							<th class="bsc__orders-header-status">Estado</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ($this->customer_orders->orders as $customer_order) :
							$order = wc_get_order( $customer_order );
							if (!$order) {
								continue;
							}
							?>
							<tr class="bsc__orders-row status-<?php echo esc_attr( $order->get_status() ); ?>">
								<td class="bsc__orders-cell-order-number" data-title="Número de Orden">
									<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
										#<?php echo esc_html( $order->get_order_number() ); ?>
									</a>
								</td>

								<td class="bsc__orders-cell-order-date" data-title="Fecha">
									<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
										<?php echo esc_html( $this->format_order_date( $order ) ); ?>
									</time>
								</td>

								<td class="bsc__orders-cell-status" data-title="Estado">
									<?php $this->render_progress_bar( $order ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Mobile cards -->
			<div class="bsc__orders-cards">
				<?php
				foreach ($this->customer_orders->orders as $customer_order) :
					$order = wc_get_order( $customer_order );
					if (!$order) {
						continue;
					}
					?>
					<article class="bsc__orders-card status-<?php echo esc_attr( $order->get_status() ); ?>">
						<div class="bsc__orders-card-row">
							<span class="bsc__orders-card-label">Número de Orden</span>
							<div class="bsc__orders-card-value bsc__orders-card-value--order-number">
								<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
									#<?php echo esc_html( $order->get_order_number() ); ?>
								</a>
							</div>
						</div>

						<div class="bsc__orders-card-row">
							<span class="bsc__orders-card-label">Fecha</span>
							<div class="bsc__orders-card-value">
								<time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>">
									<?php echo esc_html( $this->format_order_date( $order ) ); ?>
								</time>
							</div>
						</div>

						<div class="bsc__orders-card-row bsc__orders-card-row--status">
							<span class="bsc__orders-card-label">Estado</span>
							<div class="bsc__orders-card-value bsc__orders-card-value--status">
								<?php $this->render_progress_bar( $order ); ?>
							</div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ($this->customer_orders->max_num_pages > 1) : ?>
				<div class="bsc__orders-pagination">
					<?php if ($this->current_page > 1) : ?>
						<a
							class="<?php echo esc_attr( $this->button_class ); ?> bsc__orders-prev"
							href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $this->current_page - 1 ) ); ?>"
						>
							<?php esc_html_e( 'Anterior', 'woocommerce' ); ?>
						</a>
					<?php endif; ?>

					<?php if ($this->current_page < $this->customer_orders->max_num_pages) : ?>
						<a
							class="<?php echo esc_attr( $this->button_class ); ?> bsc__orders-next"
							href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $this->current_page + 1 ) ); ?>"
						>
							<?php esc_html_e( 'Siguiente', 'woocommerce' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	protected function format_order_date( $order ) {
		$date_created = $order->get_date_created();

		if (!$date_created) {
			return '';
		}

		$timestamp = $date_created->getTimestamp();

		$meses = array(
			'January'   => 'enero',
			'February'  => 'febrero',
			'March'     => 'marzo',
			'April'     => 'abril',
			'May'       => 'mayo',
			'June'      => 'junio',
			'July'      => 'julio',
			'August'    => 'agosto',
			'September' => 'septiembre',
			'October'   => 'octubre',
			'November'  => 'noviembre',
			'December'  => 'diciembre',
		);

		$fecha_en = date( 'j F Y', $timestamp );

		return strtr( $fecha_en, $meses );
	}

	protected function render_progress_bar( $order ) {
		$progress = new BSC_Order_Progress_Bar(
			bsc_map_order_to_bar( $order )
		);
		$progress->setDisplayMode( BSC_Order_Progress_Bar::DISPLAY_COMPACT );
		$progress->render();
	}
}
?>
