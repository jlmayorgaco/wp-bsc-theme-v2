<?php
$home_url             = esc_url(home_url('/'));
$shop_url             = esc_url(wc_get_page_permalink('shop'));
$my_account_url       = esc_url(wc_get_page_permalink('myaccount'));
$orders_url           = esc_url(wc_get_account_endpoint_url('orders'));
$bubble_points_url    = esc_url(home_url('/mi-cuenta/bubble-points/'));
$bubble_creators_url  = esc_url(home_url('/bubble-creators/'));
$shipping_returns_url = esc_url(home_url('/shipping-returns/'));
$faq_url              = esc_url(home_url('/faq/'));
$contact_url          = esc_url(home_url('/contact-us/'));
$product_category_url = esc_url(home_url('/product-category/'));
$cart_items_count     = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$checkout_aria_label  = sprintf(
  _n('Ir al checkout con %d producto', 'Ir al checkout con %d productos', $cart_items_count, 'bsc-2-0'),
  $cart_items_count
);
?><footer class="bsc footer">
  <div class="footer__container">
    <div class="footer__links-section">
      <div class="footer__column">
        <h2 class="footer__heading"><a href="<?php echo $home_url; ?>">Bubbles Skin Care</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="<?php echo $shipping_returns_url; ?>" class="footer__link"
              >Envíos y devoluciones</a
            >
          </li>
          <li class="footer__list-item">
            <a href="<?php echo $faq_url; ?>" class="footer__link">Preguntas frecuentes</a>
          </li>
          <li class="footer__list-item">
            <a href="<?php echo $contact_url; ?>" class="footer__link">Contacto</a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading"><a href="<?php echo $shop_url; ?>">K-Beauty</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="<?php echo $product_category_url; ?>" class="footer__link">Entrega inmediata</a>
          </li>
          <li class="footer__list-item">
            <a
              href="<?php echo esc_url( bsc_get_whatsapp_url( 'encargo' ) ); ?>"
              target="_blank"
              rel="noopener noreferrer"
              class="footer__link"
            >
              Encargos
            </a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading"><a href="<?php echo $my_account_url; ?>">Mi cuenta</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="<?php echo $bubble_points_url; ?>" class="footer__link"
              >Mis puntos</a
            >
          </li>
          <li class="footer__list-item">
            <a href="<?php echo $orders_url; ?>" class="footer__link">Pedidos</a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading">Programas</h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="<?php echo $bubble_creators_url; ?>" class="footer__link">Bubble Creators</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer__divider footer__divider--full"></div>

    <div class="footer__bottom-section">
      <div class="footer__logo">
        <a href="<?php echo $home_url; ?>">
          <img
            src="<?php echo get_template_directory_uri();?>/images/bsc_logo_footer.png"
            alt="Logo de Bubbles Skin Care"
            class="footer__logo-image"
          />
        </a>
      </div>
      <div class="footer__social-icons">
        <a
          href="https://www.instagram.com/bubbles.skincare/"
          target="_blank"
          aria-label="Instagram"
          class="footer__social-icon footer__social-icon--instagram"
        >
          <i class="fab fa-instagram"></i>
        </a>
        <a
          href="https://www.tiktok.com/@bubblesskincare"
          target="_blank"
          aria-label="TikTok"
          class="footer__social-icon footer__social-icon--tiktok"
        >
          <i class="fab fa-tiktok"></i>
        </a>
      </div>
      <div class="footer__copyright">
        <p class="footer__copyright-text">
          <strong>© 2020 - <?php echo esc_html(date('Y')); ?> BSC | Bubbles Skin Care</strong><br />Todos los derechos reservados
        </p>
      </div>
      </div>

      <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="footer__shopping-cart" aria-label="<?php echo esc_attr($checkout_aria_label); ?>">
        <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
        <span class="footer__cart-count"><?php echo esc_html($cart_items_count); ?></span>
      </a>

      <?php get_template_part( 'components/whatsapp' ); ?>

      <div class="footer__developer-section">

    <!--
      <h2 class="footer__developer-heading">
        <a href="http://www.wappy.com.co" target="_blank">Desarrollado con amor por BSC</a>
      </h2>
-->
  </div>

  </div>


</footer>
