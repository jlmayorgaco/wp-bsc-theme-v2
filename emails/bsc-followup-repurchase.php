<?php
/**
 * BSC-082: Repurchase follow-up email template.
 *
 * Variables:
 * - $customer_name (string)
 * - $products (array)
 * - $shop_url (string)
 * - $account_url (string)
 */
defined( 'ABSPATH' ) || exit;

$email_title = 'Tu rutina puede estar por acabarse';
$emoji       = '🛍️';

require_once __DIR__ . '/bsc-email-header.php';
?>

        <tr>
          <td style="padding:0 40px 24px;text-align:center">
            <p style="font-size:15px;color:#555;margin:0;line-height:1.7">
              Hola <?php echo esc_html( $customer_name ?: 'amiga' ); ?>,<br>
              revisamos tu historial y hay productos de tu rutina que probablemente ya están cerca de acabarse.
            </p>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 28px">
            <?php foreach ( $products as $product ) : ?>
              <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;border:1px solid #eee;border-radius:12px;overflow:hidden">
                <tr>
                  <td style="padding:14px 16px;width:88px;background:#fff7fa">
                    <?php if ( ! empty( $product['image_url'] ) ) : ?>
                      <img src="<?php echo esc_url( $product['image_url'] ); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:10px;display:block">
                    <?php endif; ?>
                  </td>
                  <td style="padding:14px 16px">
                    <p style="margin:0 0 6px;font-size:15px;font-weight:700;color:#222"><?php echo esc_html( $product['name'] ); ?></p>
                    <p style="margin:0 0 10px;font-size:13px;color:#777">Última compra: <?php echo esc_html( $product['ordered_at'] ); ?></p>
                    <a href="<?php echo esc_url( $product['url'] ?: $shop_url ); ?>" style="display:inline-block;padding:9px 18px;background:#333;color:#fff;text-decoration:none;border-radius:24px;font-size:13px;font-weight:700">
                      Volver a comprar
                    </a>
                  </td>
                </tr>
              </table>
            <?php endforeach; ?>
          </td>
        </tr>

        <tr>
          <td style="padding:0 40px 32px;text-align:center">
            <a href="<?php echo esc_url( $shop_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver toda la tienda
            </a>
            <a href="<?php echo esc_url( $account_url ); ?>" style="display:inline-block;margin:0 6px 12px;padding:12px 26px;background:#333;color:#fff;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver mi cuenta
            </a>
          </td>
        </tr>

<?php require_once __DIR__ . '/bsc-email-footer.php'; ?>
