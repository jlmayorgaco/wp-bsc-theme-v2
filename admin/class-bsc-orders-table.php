<?php
/**
 * BSC-031: Admin orders table — extends WP_List_Table.
 * Columns: checkbox, order #, date, customer, items, total, status (inline), tracking (inline).
 */
defined('ABSPATH') || exit;

if ( ! class_exists('WP_List_Table') ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSC_Admin_Orders_Table extends WP_List_Table {

    private const PER_PAGE = 25;

    private array $query_args;

    public const STATUS_OPTIONS = [
        'wc-pending'   => 'Pendiente',
        'wc-on-hold'   => 'En espera',
        'wc-processing'=> 'Recibido',
        'wc-preparing' => 'En preparación',
        'wc-shipped'   => 'Enviado',
        'wc-completed' => 'Terminado',
        'wc-cancelled' => 'Cancelado',
        'wc-failed'    => 'Fallido',
        'wc-refunded'  => 'Reembolsado',
    ];

    /** Status → badge colors [ bg, text ] */
    private const STATUS_COLORS = [
        'pending'    => ['#FEF3C7', '#92400E'], // Pendiente
        'on-hold'    => ['#FDE68A', '#92400E'], // En espera
        'processing' => ['#BFDBFE', '#1E3A5F'], // Recibido
        'preparing'  => ['#C7D2FE', '#312E81'], // En preparación
        'shipped'    => ['#A7F3D0', '#064E3B'], // Enviado
        'completed'  => ['#BBF7D0', '#14532D'], // Terminado
        'cancelled'  => ['#FECACA', '#7F1D1D'], // Cancelado
        'failed'     => ['#FECACA', '#7F1D1D'], // Fallido
        'refunded'   => ['#E5E7EB', '#374151'], // Reembolsado
    ];

    public function __construct( array $query_args = [] ) {
        parent::__construct([
            'singular' => 'pedido',
            'plural'   => 'pedidos',
            'ajax'     => false,
        ]);
        $this->query_args = $query_args;
    }

    public function get_columns(): array {
        return [
            'cb'       => '<input type="checkbox">',
            'order_id' => 'Pedido',
            'date'     => 'Fecha',
            'customer' => 'Cliente',
            'city'     => 'Ciudad',
            'items'    => 'Productos',
            'total'    => 'Total',
            'status'   => 'Estado',
            'tracking' => 'Guía de envío',
        ];
    }

    public function get_sortable_columns(): array {
        return [
            'order_id' => [ 'ID', true ],
            'date'     => [ 'date', true ],
        ];
    }

    protected function get_bulk_actions(): array {
        return [
            'export_csv'         => 'Exportar CSV',
            'print_packing'      => 'Vista de empaque',
            'print_order_labels' => 'Imprimir con datos (PDF)',
        ];
    }

    public function prepare_items(): void {
        $current_page = $this->get_pagenum();

        $args = array_merge([
            'limit'    => self::PER_PAGE,
            'paged'    => $current_page,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'paginate' => true,
        ], $this->query_args);

        $result = wc_get_orders( $args );

        $this->items = $result->orders ?? [];

        $this->set_pagination_args([
            'total_items' => $result->total        ?? 0,
            'per_page'    => self::PER_PAGE,
            'total_pages' => $result->max_num_pages ?? 1,
        ]);

        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
            'order_id',
        ];
    }

    // ── Column renderers ────────────────────────────────────────────────

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
        return $date ? esc_html( $date->date_i18n( 'd M Y H:i' ) ) : '—';
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
        if ( $email ) $out .= '<br><small style="color:#666">' . esc_html( $email ) . '</small>';
        if ( $phone ) $out .= '<br><small style="color:#666">' . esc_html( $phone ) . '</small>';
        return $out;
    }

    public function column_items( $item ): string {
        /** @var WC_Order $item */
        $lines = [];
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
        $current    = 'wc-' . $raw_status;

        $badge = $this->status_badge( $raw_status );

        $html  = $badge . '<br>';
        $html .= '<select class="bsc-status-select" data-order-id="' . esc_attr( $item->get_id() ) . '" style="margin-top:4px;max-width:150px">';
        foreach ( self::STATUS_OPTIONS as $key => $label ) {
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $key ),
                selected( $current, $key, false ),
                esc_html( $label )
            );
        }
        $html .= '</select>';
        $html .= '<span class="bsc-saved-indicator" style="display:none;color:#46b450;font-weight:bold;margin-left:4px">✓</span>';
        return $html;
    }

    private function status_badge( string $status ): string {
        $clean  = preg_replace( '/^wc-/', '', $status );
        $colors = self::STATUS_COLORS[ $clean ] ?? [ '#E5E7EB', '#374151' ];
        $bsc_labels = [
            'pending'    => 'Pendiente',
            'on-hold'    => 'En espera',
            'processing' => 'Recibido',
            'preparing'  => 'En preparación',
            'shipped'    => 'Enviado',
            'completed'  => 'Terminado',
            'cancelled'  => 'Cancelado',
            'failed'     => 'Fallido',
            'refunded'   => 'Reembolsado',
        ];
        $label = $bsc_labels[ $clean ] ?? ucfirst( $clean );
        return sprintf(
            '<span class="bsc-order-badge" style="background:%s;color:%s;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap">%s</span>',
            esc_attr( $colors[0] ),
            esc_attr( $colors[1] ),
            esc_html( $label )
        );
    }

    public function column_tracking( $item ): string {
        /** @var WC_Order $item */
        $id   = $item->get_id();
        $code = esc_attr( get_post_meta( $id, '_bsc_tracking_code', true ) );
        $link = esc_attr( get_post_meta( $id, '_bsc_tracking_link', true ) );

        return sprintf(
            '<input type="text" class="bsc-tracking-code" data-order-id="%1$d"
                    value="%2$s" placeholder="Código de guía" style="width:120px;margin-bottom:4px;display:block">
             <input type="text" class="bsc-tracking-link" data-order-id="%1$d"
                    value="%3$s" placeholder="URL de seguimiento" style="width:180px;display:block">
             <span class="bsc-saved-indicator" style="display:none;color:#46b450;font-weight:bold;margin-top:3px;display:block"> ✓ Guardado</span>',
            $id, $code, $link
        );
    }

    public function column_default( $item, $column_name ): string {
        return '—';
    }

    // Add data-order-id on the <tr> for JS convenience
    public function single_row( $item ): void {
        /** @var WC_Order $item */
        echo '<tr data-order-id="' . esc_attr( $item->get_id() ) . '">';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    public function no_items(): void {
        echo '<td colspan="9" style="text-align:center;padding:2rem">No hay pedidos para mostrar.</td>';
    }
}
