<?php
/**
 * BSC-053: Shared email footer partial.
 * Variables: $order (WC_Order)
 */
defined('ABSPATH') || exit;

$email_whatsapp_url = function_exists( 'bsc_get_email_whatsapp_url' )
	? bsc_get_email_whatsapp_url()
	: 'https://wa.me/573156922859';
?>
        <!-- View order CTA -->
        <?php if ( isset($order) ) : ?>
        <tr>
          <td style="padding:0 40px 32px;text-align:center">
            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"
               style="display:inline-block;padding:12px 32px;background:#f8c0cd;color:#222;text-decoration:none;border-radius:30px;font-size:15px;font-weight:700">
              Ver mi pedido
            </a>
          </td>
        </tr>
        <?php endif; ?>

        <!-- Footer -->
        <tr>
          <td style="background:#f9f9f9;padding:24px 40px;text-align:center;border-top:1px solid #eee">
            <p style="margin:0;font-size:13px;color:#888;line-height:1.6">
              ¿Tienes preguntas? Escríbenos por
              <a href="<?php echo esc_url( $email_whatsapp_url ); ?>" style="color:#555;font-weight:600">WhatsApp</a>
              o visita nuestras redes sociales.<br>
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
