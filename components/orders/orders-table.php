<?php
if (!defined('ABSPATH')) exit;
require_once get_template_directory() . '/components/orders/order-progress-bar.php';

class BSC_Orders_Table {
    protected $customer_orders;
    protected $current_page = 1;
    protected $button_class = 'bsc__button';

    public function set_customer_orders($customer_orders) {
        $this->customer_orders = $customer_orders;
    }

    public function set_current_page($page) {
        $this->current_page = max(1, intval($page));
    }

    public function set_button_class($class) {
        $this->button_class = sanitize_html_class($class);
    }

    public function render() {
        if (empty($this->customer_orders) || empty($this->customer_orders->orders)) {
            echo '<p class="bsc__orders-empty">No has realizado pedidos aún.</p>';
            return;
        }
        ?>
        <div class="bsc bsc__orders">
            <table class="bsc__orders-table woocommerce-orders-table shop_table responsive">
                <thead>
                    <tr>
                        <th class="bsc__orders-header-order-number">Número de Orden</th>
                        <th class="bsc__orders-header-order-date">Fecha</th>
                        <th class="bsc__orders-header-status">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->customer_orders->orders as $customer_order) :
                        $order = wc_get_order($customer_order);
                    ?>
                    <tr class="bsc__orders-row status-<?php echo esc_attr($order->get_status()); ?>">
                        <!-- Número de orden -->
                        <td class="bsc__orders-cell-order-number" data-title="Número de Orden">
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>">
                                #<?php echo esc_html($order->get_order_number()); ?>
                            </a>
                        </td>

                        <!-- Fecha -->
                        <td class="bsc__orders-cell-order-date" data-title="Fecha">
                            <?php
                                $timestamp = $order->get_date_created()->getTimestamp();

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

                                $fecha_en = date('j F Y', $timestamp);
                                $fecha_es = strtr($fecha_en, $meses);
                                ?>

                                <time datetime="<?php echo esc_attr( $order->get_date_created()->date('c') ); ?>">
                                    <?php echo esc_html( $fecha_es ); ?>
                                </time>
                        </td>

                        <!-- Estado (barra de progreso) -->
                        <td class="bsc__orders-cell-status" data-title="Estado">
                            <?php $this->render_progress_bar($order); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($this->customer_orders->max_num_pages > 1): ?>
                <div class="bsc__orders-pagination">
                    <?php if ($this->current_page > 1): ?>
                        <a class="<?php echo esc_attr($this->button_class); ?> bsc__orders-prev" href="<?php echo esc_url(wc_get_endpoint_url('orders', $this->current_page - 1)); ?>">
                            <?php _e('Anterior', 'woocommerce'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($this->current_page < $this->customer_orders->max_num_pages): ?>
                        <a class="<?php echo esc_attr($this->button_class); ?> bsc__orders-next" href="<?php echo esc_url(wc_get_endpoint_url('orders', $this->current_page + 1)); ?>">
                            <?php _e('Siguiente', 'woocommerce'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    protected function render_progress_bar($order) {
        if (!class_exists('BSC_Order_Progress_Bar')) {
            require_once get_template_directory() . '/src/components/BSC_Order_Progress_Bar.php';
        }

        $wc_status = $order->get_status();
        $status_map = [
            'pending'    => BSC_Order_Progress_Bar::PENDING,
            'on-hold'    => BSC_Order_Progress_Bar::PENDING,
            'processing' => BSC_Order_Progress_Bar::RECEIVED,
            'completed'  => BSC_Order_Progress_Bar::DELIVERED,
            'cancelled'  => BSC_Order_Progress_Bar::CANCELLED,
            'refunded'   => BSC_Order_Progress_Bar::CANCELLED,
            'failed'     => BSC_Order_Progress_Bar::CANCELLED,
            'shipped'    => BSC_Order_Progress_Bar::SHIPPED, // custom status
        ];

        $mapped_status = $status_map[$wc_status] ?? BSC_Order_Progress_Bar::PENDING;

        $progress = new BSC_Order_Progress_Bar($mapped_status);
        $progress->render();
    }
}
?>
