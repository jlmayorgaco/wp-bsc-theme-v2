<?php
defined('ABSPATH') || exit;

if (!is_user_logged_in()) { echo '<p>Debes iniciar sesión para ver tus cupones.</p>'; return; }

$user = wp_get_current_user();
$codes = get_posts([
  'post_type'      => 'shop_coupon',
  'post_status'    => 'publish',
  'posts_per_page' => -1,
  'meta_query'     => [[ 'key' => 'customer_email', 'value' => $user->user_email, 'compare' => '=' ]]
]);
?>
<div class="bsc-coupon-wallet">
  <h2 class="bsc-coupon-wallet__title">¡Mis cupones <strong>disponibles</strong>!</h2>

  <?php if ($codes): ?>
  <ul class="bsc-coupon-wallet__grid" aria-live="polite">
    <?php foreach ($codes as $p):
      $code    = sanitize_text_field($p->post_title);
      $coupon  = new WC_Coupon($code);
      $amount  = (float) $coupon->get_amount();
      $type    = $coupon->get_discount_type(); // fixed_cart, percent, etc.
      $expires = $coupon->get_date_expires();
      $exp_ts  = $expires ? $expires->getTimestamp() : null;

      $usage_limit = (int) $coupon->get_usage_limit();
      $used        = (int) $coupon->get_usage_count();
      $remaining   = $usage_limit ? max(0, $usage_limit - $used) : null; // null => ilimitado

      $is_expired = $exp_ts && $exp_ts < time();
      $is_used_up = ($remaining !== null && $remaining === 0);
      $status     = $is_expired ? 'vencido' : ($is_used_up ? 'usado' : 'activo');

      $value_str  = $type === 'percent'
        ? sprintf('%s%% OFF', rtrim(rtrim(number_format($amount, 2), '0'), '.'))
        : wc_price($amount);
      $exp_str    = $expires ? 'Vence: ' . wc_format_datetime($expires) : 'Sin vencimiento';
      $left_str   = ($remaining === null) ? '∞' : (string) $remaining;
      $disabled   = ($status !== 'activo') ? 'disabled' : '';
    ?>
      <li class="bsc-ticket bsc-ticket--<?=esc_attr($status);?>">
        <div class="bsc-ticket__body">

              <!-- CODE = HERO -->
          <div class="bsc-ticket__code">
            <div class="ticket_check">
              <div class="ticket_check__back"></div>
              <div class="ticket_check__front">
                <img src="<?php echo trailingslashit(get_stylesheet_directory_uri()) . ltrim('plugins/bubble-points/images/coupon_check.png', '/');  ?>">
              </div>
            </div>
            <span class="bsc-code-badge js-copy-coupon" data-code="<?=esc_attr($code);?>" role="button" tabindex="0" aria-label="Copiar código <?=$code;?>">
              <code><?=$code;?></code>
            </span>
          </div>


          <div class="bsc-ticket__value"><?=$value_str;?></div>
          <div class="bsc-ticket__carita">                
            <div class="bsc-ticket__carita__bg"></div>              
            <div class="bsc-ticket__carita__img">                
              <img src="<?php echo trailingslashit(get_stylesheet_directory_uri()) . ltrim('plugins/bubble-points/images/coupon_carita.png', '/');  ?>">
            </div>
          </div>


                <div class="bsc-ticket__row">
            <span class="bsc-chip bsc-chip--outline"><?=esc_html($status);?></span>
            <span class="bsc-dot">|</span>
            <span class="bsc-ticket__exp"><?=esc_html($exp_str);?></span>
            <span class="bsc-dot">|</span>
            <span class="bsc-ticket__uses">Usos restantes: <?=esc_html($left_str);?></span>
          </div>

        </div>

        <div class="bsc-ticket__actions"  style="display:none">
          <button class="bsc-btn bsc-btn--copy js-copy-coupon" data-code="<?=esc_attr($code);?>" type="button" <?=$disabled;?>>Copiar código</button>
          <?php if ($status !== 'activo'): ?>
            <small class="bsc-ticket__hint">No disponible</small>
          <?php endif; ?>
        </div>

      </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?>
    <div class="bsc-empty"><p>Aún no tienes cupones. Redime tus Bubble Points para generar uno ✨</p></div>
  <?php endif; ?>
</div>
