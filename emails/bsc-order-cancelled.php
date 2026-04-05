<?php
/**
 * BSC-053: Order cancelled email (wc-cancelled status).
 * Variables: $order (WC_Order)
 */
defined('ABSPATH') || exit;

$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$order_number  = $order->get_order_number();
$email_title   = 'Tu pedido fue cancelado';
$emoji         = '😔';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <!-- Message -->
        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.6">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              tu pedido <strong>#<?php echo esc_html( $order_number ); ?></strong> fue cancelado.<br>
              Si tienes dudas o crees que esto fue un error, por favor contáctanos.
            </p>
          </td>
        </tr>

        <!-- Status box -->
        <tr>
          <td style="padding:0 40px 28px">
            <div style="background:#fff5f5;border-radius:12px;padding:18px 24px;border-left:4px solid #fc8181;text-align:center">
              <p style="margin:0;font-size:15px;font-weight:700;color:#222">Estado: Cancelado ❌</p>
              <p style="margin:6px 0 0;font-size:13px;color:#888">¿Fue un error? Escríbenos y con gusto te ayudamos.</p>
            </div>
          </td>
        </tr>

        <!-- Contact CTA -->
        <tr>
          <td style="padding:0 40px 28px;text-align:center">
            <a href="https://wa.me/573156922859"
               style="display:inline-block;padding:12px 28px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:14px;font-weight:700">
              Contáctanos por WhatsApp ↗
            </a>
          </td>
        </tr>

<?php
require_once __DIR__ . '/bsc-order-items-table.php';
require_once __DIR__ . '/bsc-email-footer.php';
