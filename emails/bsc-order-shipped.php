<?php
/**
 * BSC-033: Shipping notification email template.
 * Variables available: $order, $tracking_code, $tracking_link.
 */
defined('ABSPATH') || exit;

$customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
$order_number  = $order->get_order_number();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tu pedido está en camino</title>
</head>
<body style="margin:0;padding:0;background:#fdf4f8;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#333">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#fdf4f8;padding:32px 16px">
  <tr>
    <td align="center">
      <table width="580" cellpadding="0" cellspacing="0" style="max-width:580px;width:100%;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.07)">

        <!-- Header -->
        <tr>
          <td style="background:#f8c0cd;padding:32px 40px;text-align:center">
            <p style="margin:0;font-size:28px;font-weight:900;color:#222;letter-spacing:1px">Bubble Skin Care</p>
            <p style="margin:8px 0 0;font-size:14px;color:#555;letter-spacing:2px">K-BEAUTY COLOMBIA</p>
          </td>
        </tr>

        <!-- Hero message -->
        <tr>
          <td style="padding:40px 40px 24px;text-align:center">
            <p style="font-size:40px;margin:0">🚚</p>
            <h1 style="font-size:22px;font-weight:800;margin:12px 0 8px;color:#222">
              ¡Tu pedido está en camino!
            </h1>
            <p style="font-size:15px;color:#555;margin:0;line-height:1.6">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              tu pedido <strong>#<?php echo esc_html( $order_number ); ?></strong> ya fue despachado. 🌸
            </p>
          </td>
        </tr>

        <!-- Tracking block -->
        <?php if ( $tracking_code || $tracking_link ) : ?>
        <tr>
          <td style="padding:0 40px 32px">
            <div style="background:#fdf4f8;border-radius:12px;padding:20px 24px;border-left:4px solid #f8c0cd">
              <p style="margin:0 0 12px;font-size:13px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase">Información de seguimiento</p>
              <?php if ( $tracking_code ) : ?>
              <p style="margin:0 0 8px;font-size:15px">
                <strong>Código de guía:</strong>
                <span style="font-family:monospace;background:#fff;padding:2px 8px;border-radius:6px;border:1px solid #eee"><?php echo esc_html( $tracking_code ); ?></span>
              </p>
              <?php endif; ?>
              <?php if ( $tracking_link ) : ?>
              <p style="margin:0">
                <a href="<?php echo esc_url( $tracking_link ); ?>"
                   style="display:inline-block;margin-top:8px;padding:10px 24px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:14px;font-weight:700">
                  Rastrear mi pedido ↗
                </a>
              </p>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>

        <!-- Order items summary -->
        <tr>
          <td style="padding:0 40px 32px">
            <p style="margin:0 0 12px;font-size:13px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase">Resumen de tu pedido</p>
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
                <td style="padding:12px 0 0;font-size:15px;font-weight:700">Total</td>
                <td style="padding:12px 0 0;font-size:15px;font-weight:700;text-align:right">
                  <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- CTA -->
        <tr>
          <td style="padding:0 40px 40px;text-align:center">
            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
               style="display:inline-block;padding:12px 32px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver mi pedido
            </a>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9f9f9;padding:24px 40px;text-align:center;border-top:1px solid #eee">
            <p style="margin:0;font-size:13px;color:#888;line-height:1.6">
              ¿Tienes preguntas? Escríbenos por
              <a href="https://wa.me/573156922859" style="color:#555;font-weight:600">WhatsApp</a>
              o por nuestras redes sociales.<br>
              <strong style="color:#222">Bubble Skin Care</strong> — K-Beauty Colombia 🌸
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
