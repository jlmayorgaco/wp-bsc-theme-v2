<?php
/**
 * BSC-053: Order confirmed email (payment received / processing status).
 * Variables: $order (WC_Order)
 */
defined('ABSPATH') || exit;

$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$order_number  = $order->get_order_number();
$email_title   = '¡Tu pago fue recibido!';
$emoji         = '🎉';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <!-- Message -->
        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.6">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              recibimos el pago de tu pedido <strong>#<?php echo esc_html( $order_number ); ?></strong>. 🌸<br>
              Pronto comenzaremos a prepararlo con todo el amor de BSC.
            </p>
          </td>
        </tr>

        <!-- Highlight box -->
        <tr>
          <td style="padding:0 40px 28px">
            <div style="background:#fdf4f8;border-radius:12px;padding:18px 24px;border-left:4px solid #f8c0cd;text-align:center">
              <p style="margin:0;font-size:15px;font-weight:700;color:#222">Estado: Pago confirmado ✅</p>
              <p style="margin:6px 0 0;font-size:13px;color:#888">Recibirás otro correo cuando tu pedido sea despachado.</p>
            </div>
          </td>
        </tr>

<?php
require_once __DIR__ . '/bsc-order-items-table.php';
require_once __DIR__ . '/bsc-email-footer.php';
