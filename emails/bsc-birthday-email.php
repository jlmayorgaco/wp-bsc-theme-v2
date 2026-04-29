<?php
/**
 * BSC-082: Birthday email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $shop_url (string)
 */
defined( 'ABSPATH' ) || exit;

$email_title  = 'Feliz cumpleaños';
$emoji        = '🎂';
$display_name = $user instanceof WP_User ? ( $user->first_name ?: $user->display_name ) : 'amiga';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.7">
              Hola <?php echo esc_html( $display_name ); ?>,<br>
              hoy celebramos contigo. Gracias por ser parte de BSC y por dejarnos acompañar tu rutina de cuidado.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 28px">
            <div style="background:#fdf4f8;border-radius:12px;padding:18px 24px;border-left:4px solid #f8c0cd;text-align:center">
              <p style="margin:0;font-size:15px;font-weight:700;color:#222">Que tengas un día lindo y muy glow</p>
              <p style="margin:6px 0 0;font-size:13px;color:#888">Te dejamos la tienda abierta para que sigas armando tu rutina favorita.</p>
            </div>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 32px;text-align:center">
            <a href="<?php echo esc_url( $shop_url ); ?>" style="display:inline-block;padding:12px 30px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver productos
            </a>
          </td>
        </tr>

<?php require_once __DIR__ . '/bsc-email-footer.php'; ?>
