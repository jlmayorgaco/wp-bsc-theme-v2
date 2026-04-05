<?php
/**
 * BSC-053: Order delivered email (wc-completed status).
 * Variables: $order (WC_Order)
 */
defined('ABSPATH') || exit;

$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$order_number  = $order->get_order_number();
$email_title   = '¡Tu pedido fue entregado!';
$emoji         = '💛';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <!-- Message -->
        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.6">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              tu pedido <strong>#<?php echo esc_html( $order_number ); ?></strong> fue marcado como entregado. ✨<br>
              ¡Esperamos que ames tus nuevos productos K-Beauty tanto como nosotras!
            </p>
          </td>
        </tr>

        <!-- Status box -->
        <tr>
          <td style="padding:0 40px 24px">
            <div style="background:#f0fff4;border-radius:12px;padding:18px 24px;border-left:4px solid #48bb78;text-align:center">
              <p style="margin:0;font-size:15px;font-weight:700;color:#222">Estado: Entregado ✅</p>
              <p style="margin:6px 0 0;font-size:13px;color:#888">¿Todo bien con tu pedido? Si tienes alguna pregunta, escríbenos.</p>
            </div>
          </td>
        </tr>

        <!-- Review CTA -->
        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:14px;color:#555;margin:0 0 12px">¿Te gustaron los productos? Deja tu reseña y ayuda a otras Bubble lovers 💬</p>
          </td>
        </tr>

<?php
require_once __DIR__ . '/bsc-order-items-table.php';
require_once __DIR__ . '/bsc-email-footer.php';
