<?php
/**
 * BSC-082: Inactivity follow-up email template.
 *
 * Variables:
 * - $customer_name (string)
 * - $order (WC_Order)
 * - $last_order_date (string)
 * - $shop_url (string)
 * - $account_url (string)
 */
defined( 'ABSPATH' ) || exit;

$email_title = 'Hace rato no te vemos por aquí';
$emoji       = '🫧';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.7">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              tu última compra fue el <strong><?php echo esc_html( $last_order_date ); ?></strong> y quisimos recordarte
              que tu rutina BSC sigue aquí esperándote.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 24px">
            <div style="background:#f9f9f9;border-radius:12px;padding:18px 20px">
              <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase">Último pedido</p>
              <p style="margin:0;font-size:14px;color:#333">Pedido #<?php echo esc_html( $order->get_order_number() ); ?></p>
            </div>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 32px;text-align:center">
            <a href="<?php echo esc_url( $shop_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Volver a comprar
            </a>
            <a href="<?php echo esc_url( $account_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver mi cuenta
            </a>
          </td>
        </tr>

<?php require_once __DIR__ . '/bsc-email-footer.php'; ?>
