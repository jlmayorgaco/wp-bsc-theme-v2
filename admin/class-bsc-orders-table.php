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
            'export_csv'    => 'Exportar CSV',
            'print_packing' => 'Vista de empaque',
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
        $current  = 'wc-' . $item->get_status();
        $statuses = wc_get_order_statuses();

        $html  = '<select class="bsc-status-select" data-order-id="' . esc_attr( $item->get_id() ) . '">';
        foreach ( $statuses as $key => $label ) {
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
        echo '<td colspan="8" style="text-align:center;padding:2rem">No hay pedidos para mostrar.</td>';
    }
}
