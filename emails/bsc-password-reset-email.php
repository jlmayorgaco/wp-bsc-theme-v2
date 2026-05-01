<?php
/**
 * BSC-082: Password reset email template.
 *
 * Variables:
 * - $user (WP_User)
 * - $reset_url (string)
 */
defined( 'ABSPATH' ) || exit;

$email_title = 'Recupera tu contraseña';
$emoji       = '🔐';
$display_name = $user instanceof WP_User ? ( $user->first_name ?: $user->display_name ) : 'amiga';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.7">
              Hola <?php echo esc_html( $display_name ); ?>,<br>
              recibimos una solicitud para cambiar la contraseña de tu cuenta BSC.
              Si fuiste tú, usa el botón de abajo para crear una nueva.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 28px;text-align:center">
            <a href="<?php echo esc_url( $reset_url ); ?>" style="display:inline-block;padding:12px 30px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Cambiar mi contraseña
            </a>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 32px">
            <div style="background:#f9f9f9;border-radius:12px;padding:18px 20px;text-align:left">
              <p style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:1px;color:#888;text-transform:uppercase">Si el botón no abre</p>
              <p style="margin:0;font-size:13px;line-height:1.7;word-break:break-word;color:#555">
                Copia este enlace en tu navegador:<br>
                <a href="<?php echo esc_url( $reset_url ); ?>" style="color:#333"><?php echo esc_html( $reset_url ); ?></a>
              </p>
            </div>
          </td>
        </tr>

<?php require_once __DIR__ . '/bsc-email-footer.php'; ?>
