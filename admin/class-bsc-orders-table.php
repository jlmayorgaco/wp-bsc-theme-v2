<?php
/**
 * BSC-031: Admin orders table - extends WP_List_Table.
 * Columns: checkbox, order #, date, customer, items, total, status (inline), tracking (inline).
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSC_Admin_Orders_Table extends WP_List_Table {

	private const PER_PAGE = 25;

	private array $query_args;

	public const STATUS_OPTIONS = array(
		'wc-pending'    => 'Pendiente',
		'wc-on-hold'    => 'En espera',
		'wc-processing' => 'Recibido',
		'wc-preparing'  => 'En preparación',
		'wc-shipped'    => 'Enviado',
		'wc-completed'  => 'Terminado',
		'wc-cancelled'  => 'Cancelado',
		'wc-refunded'   => 'Reembolsado',
	);

	public function __construct( array $query_args = array() ) {
		parent::__construct(
			array(
				'singular' => 'pedido',
				'plural'   => 'pedidos',
				'ajax'     => false,
			)
		);

		$this->query_args = $query_args;
	}

	public function get_columns(): array {
		return array(
			'cb'       => '<input type="checkbox">',
			'order_id' => 'Pedido',
			'date'     => 'Fecha',
			'customer' => 'Cliente',
			'city'     => 'Ciudad',
			'items'    => 'Productos',
			'total'    => 'Total',
			'status'   => 'Estado',
			'tracking' => 'Guía de envío',
		);
	}

	public function get_sortable_columns(): array {
		return array(
			'order_id' => array( 'ID', true ),
			'date'     => array( 'date', true ),
		);
	}

	protected function get_bulk_actions(): array {
		return array(
			'export_csv'         => 'Exportar CSV',
			'print_packing'      => 'Vista de empaque',
			'print_order_labels' => 'Imprimir con datos (PDF)',
		);
	}

	public function prepare_items(): void {
		$current_page = $this->get_pagenum();

		$args = array_merge(
			array(
				'limit'    => self::PER_PAGE,
				'paged'    => $current_page,
				'orderby'  => 'date',
				'order'    => 'DESC',
				'paginate' => true,
			),
			$this->query_args
		);

		$result = wc_get_orders( $args );

		$this->items = $result->orders ?? array();

		$this->set_pagination_args(
			array(
				'total_items' => $result->total ?? 0,
				'per_page'    => self::PER_PAGE,
				'total_pages' => $result->max_num_pages ?? 1,
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
			'order_id',
		);
	}

	public function column_cb( $item ): string {
		/** @var WC_Order $item */
		return sprintf(
			'<input type="checkbox" name="order_ids[]" value="%d">',
			$item->get_id()
		);
	}

	public function column_order_id( $item ): string {
		/** @var WC_Order $item */
		return sprintf(
			'<a href="%s" target="_blank"><strong>#%s</strong></a>',
			esc_url( $item->get_edit_order_url() ),
			esc_html( $item->get_order_number() )
		);
	}

	public function column_date( $item ): string {
		/** @var WC_Order $item */
		$date = $item->get_date_created();

		if ( ! $date ) {
			return '—';
		}

		$html  = esc_html( $date->date_i18n( 'd M Y H:i' ) );
		$html .= $this->order_age_badge( $item );

		return $html;
	}

	public function column_city( $item ): string {
		/** @var WC_Order $item */
		$city = $item->get_shipping_city() ?: $item->get_billing_city();

		return esc_html( $city ?: '—' );
	}

	public function column_customer( $item ): string {
		/** @var WC_Order $item */
		$name  = trim( $item->get_billing_first_name() . ' ' . $item->get_billing_last_name() );
		$email = $item->get_billing_email();
		$phone = $item->get_billing_phone();
		$out   = esc_html( $name ?: '—' );

		if ( $email ) {
			$out .= '<br><small class="bsc-admin-orders__meta">' . esc_html( $email ) . '</small>';
		}

		if ( $phone ) {
			$out .= '<br><small class="bsc-admin-orders__meta">' . esc_html( $phone ) . '</small>';
		}

		return $out;
	}

	public function column_items( $item ): string {
		/** @var WC_Order $item */
		$lines = array();

		foreach ( $item->get_items() as $line_item ) {
			$lines[] = esc_html( $line_item->get_quantity() . '× ' . $line_item->get_name() );
		}

		return $lines ? implode( '<br>', $lines ) : '—';
	}

	public function column_total( $item ): string {
		/** @var WC_Order $item */
		return wp_kses_post( $item->get_formatted_order_total() );
	}

	public function column_status( $item ): string {
		/** @var WC_Order $item */
		$raw_status = $item->get_status();
		$current    = $this->normalize_status_select_value( $raw_status );
		$html       = $this->status_badge( $raw_status );

		$html .= '<div class="bsc-status-control" data-order-id="' . esc_attr( $item->get_id() ) . '">';
		$html .= '<select class="bsc-status-select bsc-status-select--inline" data-order-id="' . esc_attr( $item->get_id() ) . '" data-original-status="' . esc_attr( $current ) . '">';

		foreach ( self::STATUS_OPTIONS as $key => $label ) {
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $current, $key, false ),
				esc_html( $label )
			);
		}

		$html .= '</select>';
		$html .= '<button type="button" class="button button-small bsc-status-save" data-order-id="' . esc_attr( $item->get_id() ) . '" disabled>Guardar</button>';
		$html .= '<span class="bsc-status-pending" aria-hidden="true">Guardar cambios</span>';
		$html .= '</div>';

		return $html;
	}

	private function normalize_status_select_value( string $status ): string {
		$clean_status = preg_replace( '/^wc-/', '', $status );
		$status_key   = 'wc-' . $clean_status;

		if ( isset( self::STATUS_OPTIONS[ $status_key ] ) ) {
			return $status_key;
		}

		return 'wc-processing';
	}

	private function order_age_badge( WC_Order $order ): string {
		$open_statuses = array( 'pending', 'on-hold', 'processing', 'preparing' );

		if ( ! in_array( $order->get_status(), $open_statuses, true ) ) {
			return '';
		}

		$date = $order->get_date_created();

		if ( ! $date ) {
			return '';
		}

		$created_timestamp = (int) $date->getTimestamp();
		$current_timestamp = time();
		$age_days          = max( 0, (int) floor( ( $current_timestamp - $created_timestamp ) / DAY_IN_SECONDS ) );
		$label             = 'Hoy';
		$class             = 'fresh';

		if ( 1 === $age_days ) {
			$label = '1 día';
			$class = 'warning';
		} elseif ( $age_days >= 2 ) {
			$label = sprintf( '%d días', $age_days );
			$class = $age_days >= 3 ? 'critical' : 'warning';
		}

		return sprintf(
			'<br><span class="bsc-order-age-badge bsc-order-age-badge--%s">%s</span>',
			esc_attr( $class ),
			esc_html( $label )
		);
	}

	private function status_badge( string $status ): string {
		$clean  = preg_replace( '/^wc-/', '', $status );
		$labels = array(
			'pending'    => 'Pendiente',
			'on-hold'    => 'En espera',
			'processing' => 'Recibido',
			'preparing'  => 'En preparación',
			'shipped'    => 'Enviado',
			'completed'  => 'Terminado',
			'cancelled'  => 'Cancelado',
			'failed'     => 'Fallido',
			'refunded'   => 'Reembolsado',
		);
		$label  = $labels[ $clean ] ?? ucfirst( $clean );

		return sprintf(
			'<span class="bsc-order-badge bsc-order-badge--%s">%s</span>',
			esc_attr( $clean ),
			esc_html( $label )
		);
	}

	public function column_tracking( $item ): string {
		/** @var WC_Order $item */
		$id   = $item->get_id();
		$code = esc_attr( get_post_meta( $id, '_bsc_tracking_code', true ) );
		$link = esc_attr( get_post_meta( $id, '_bsc_tracking_link', true ) );

		return sprintf(
			'<input type="text" class="bsc-tracking-code" data-order-id="%1$d" value="%2$s" placeholder="Código de guía">'
			. '<input type="text" class="bsc-tracking-link" data-order-id="%1$d" value="%3$s" placeholder="URL de seguimiento">'
			. '<span class="bsc-saved-indicator bsc-saved-indicator--tracking">✓ Guardado</span>',
			$id,
			$code,
			$link
		);
	}

	public function column_default( $item, $column_name ): string {
		return '—';
	}

	public function single_row( $item ): void {
		/** @var WC_Order $item */
		echo '<tr data-order-id="' . esc_attr( $item->get_id() ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	public function no_items(): void {
		echo '<td colspan="9" class="bsc-admin-orders__empty">No hay pedidos para mostrar.</td>';
	}
}
