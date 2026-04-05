<?php
/**
 * BSC-053: Shared order items summary table partial.
 * Variables: $order (WC_Order)
 */
defined('ABSPATH') || exit;
if ( ! isset($order) ) return;
?>
        <!-- Order items -->
        <tr>
          <td style="padding:0 40px 28px">
            <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase">Resumen de tu pedido</p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <?php foreach ( $order->get_items() as $item ) : ?>
              <tr>
                <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:14px">
                  <?php echo esc_html( $item->get_quantity() . '× ' . $item->get_name() ); ?>
                </td>
                <td style="padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:14px;text-align:right">
                  <?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <tr>
                <td style="padding:10px 0 0;font-size:15px;font-weight:700">Total</td>
                <td style="padding:10px 0 0;font-size:15px;font-weight:700;text-align:right">
                  <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                </td>
              </tr>
            </table>
          </td>
        </tr>
