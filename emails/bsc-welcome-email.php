<?php
/**
 * BSC-082: Welcome email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $account_url (string)
 * - $shop_url (string)
 */
defined( 'ABSPATH' ) || exit;

$email_title   = 'Bienvenida a Bubble Skin Care';
$emoji         = '🌸';
$display_name  = $user instanceof WP_User ? ( $user->first_name ?: $user->display_name ) : 'amiga';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.7">
              Hola <?php echo esc_html( $display_name ); ?>,<br>
              ya haces parte de la familia BSC. Desde ahora puedes guardar tus datos,
              revisar tus pedidos y seguir armando tu rutina de skincare con nosotras.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 28px">
            <div style="background:#fdf4f8;border-radius:12px;padding:18px 24px;border-left:4px solid #f8c0cd;text-align:center">
              <p style="margin:0;font-size:15px;font-weight:700;color:#222">Tu cuenta ya está activa</p>
              <p style="margin:6px 0 0;font-size:13px;color:#888">Completa tu perfil y empieza a explorar la tienda.</p>
            </div>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 32px;text-align:center">
            <a href="<?php echo esc_url( $account_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Completar mi perfil
            </a>
            <a href="<?php echo esc_url( $shop_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ir a la tienda
            </a>
          </td>
        </tr>

<?php require_once __DIR__ . '/bsc-email-footer.php'; ?>
