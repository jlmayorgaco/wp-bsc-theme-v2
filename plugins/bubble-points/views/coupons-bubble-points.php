<?php
defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    echo '<p>Debes iniciar sesion para ver tus cupones.</p>';
    return;
}

$user = wp_get_current_user();
$codes = get_posts([
    'post_type'      => 'shop_coupon',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_query'     => [[ 'key' => 'customer_email', 'value' => $user->user_email, 'compare' => '=' ]],
]);
?>
<div class="bsc-coupon-wallet">
  <h2 class="bsc-coupon-wallet__title">&iexcl;Mis cupones <strong>disponibles</strong>!</h2>

  <?php if ($codes): ?>
  <ul class="bsc-coupon-wallet__grid" aria-live="polite">
    <?php foreach ($codes as $p):
        $code    = sanitize_text_field($p->post_title);
        $coupon  = new WC_Coupon($code);
        $amount  = (float) $coupon->get_amount();
        $type    = $coupon->get_discount_type();
        $expires = $coupon->get_date_expires();
        $exp_ts  = $expires ? $expires->getTimestamp() : null;

        $usage_limit = (int) $coupon->get_usage_limit();
        $used        = (int) $coupon->get_usage_count();
        $remaining   = $usage_limit ? max(0, $usage_limit - $used) : null;

        $is_expired = $exp_ts && $exp_ts < time();
        $is_used_up = ($remaining !== null && $remaining === 0);
        $status     = $is_expired ? 'vencido' : ($is_used_up ? 'usado' : 'activo');

        $value_str = $type === 'percent'
            ? sprintf('%s%% OFF', rtrim(rtrim(number_format($amount, 2), '0'), '.'))
            : wc_price($amount);
        $exp_str   = $expires ? 'Vence: ' . wc_format_datetime($expires) : 'Sin vencimiento';
        $left_str  = ($remaining === null) ? 'ilimitado' : (string) $remaining;
        $check_src = trailingslashit(get_stylesheet_directory_uri()) . ltrim('plugins/bubble-points/images/coupon_check.png', '/');
        $face_src  = trailingslashit(get_stylesheet_directory_uri()) . ltrim('plugins/bubble-points/images/coupon_carita.png', '/');
    ?>
      <li class="bsc-ticket bsc-ticket--<?php echo esc_attr($status); ?>">
        <div class="bsc-ticket__body">
          <div class="bsc-ticket__code">
            <div class="ticket_check">
              <div class="ticket_check__back"></div>
              <div class="ticket_check__front">
                <img src="<?php echo esc_url($check_src); ?>" alt="">
              </div>
            </div>
            <span class="bsc-code-badge js-copy-coupon" data-code="<?php echo esc_attr($code); ?>" role="button" tabindex="0" aria-label="<?php echo esc_attr('Copiar codigo ' . $code); ?>">
              <code><?php echo esc_html($code); ?></code>
            </span>
          </div>

          <div class="bsc-ticket__value"><?php echo wp_kses_post($value_str); ?></div>
          <div class="bsc-ticket__carita">
            <div class="bsc-ticket__carita__bg"></div>
            <div class="bsc-ticket__carita__img">
              <img src="<?php echo esc_url($face_src); ?>" alt="">
            </div>
          </div>

          <div class="bsc-ticket__row">
            <span class="bsc-chip bsc-chip--outline"><?php echo esc_html($status); ?></span>
            <span class="bsc-dot">|</span>
            <span class="bsc-ticket__exp"><?php echo esc_html($exp_str); ?></span>
            <span class="bsc-dot">|</span>
            <span class="bsc-ticket__uses">Usos restantes: <?php echo esc_html($left_str); ?></span>
          </div>
        </div>

        <div class="bsc-ticket__actions bsc-ticket__actions--hidden">
          <button class="bsc-btn bsc-btn--copy js-copy-coupon" data-code="<?php echo esc_attr($code); ?>" type="button" <?php disabled($status !== 'activo'); ?>>Copiar codigo</button>
          <?php if ($status !== 'activo'): ?>
            <small class="bsc-ticket__hint">No disponible</small>
          <?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
    <div class="bsc-empty"><p>Aun no tienes cupones. Redime tus Bubble Points para generar uno.</p></div>
  <?php endif; ?>
</div>
