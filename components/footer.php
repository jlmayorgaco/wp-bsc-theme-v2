<footer class="bsc footer">
  <div class="footer__container">
    <div class="footer__links-section">
      <div class="footer__column">
        <h2 class="footer__heading"><a href="/">Bubbles Skin Care</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="/shipping-returns/" class="footer__link"
              >Envíos y devoluciones</a
            >
          </li>
          <li class="footer__list-item">
            <a href="/faq/" class="footer__link">Preguntas frecuentes</a>
          </li>
          <li class="footer__list-item">
            <a href="/contact-us/" class="footer__link">Contacto</a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading"><a href="/shop/">Tienda</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="/shop/" class="footer__link">Entrega inmediata</a>
          </li>
          <li class="footer__list-item">
            <a href="#encargos-popup" class="footer__link">Encargos</a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading"><a href="http://yourwebsite.com/my-account/">Mi cuenta</a></h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="/my-account/bubble-points/" class="footer__link"
              >Bubble Points</a
            >
          </li>
          <li class="footer__list-item">
            <a href="/my-account/orders/" class="footer__link">Pedidos</a>
          </li>
          <li class="footer__list-item">
            <a href="/my-account/data/" class="footer__link">Datos</a>
          </li>
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading">Programas</h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="#bubble-creators-popup" class="footer__link">Bubble Creators</a>
          </li>
        </ul>
      </div>
    </div>

    <div class="footer__divider footer__divider--full"></div>

    <div class="footer__bottom-section">
      <div class="footer__logo">
        <a href="/">
          <img
            src="<?php echo get_template_directory_uri();?>/images/bsc_logo_footer.png"
            alt="Bubbles Skin Care Logo"
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
          <strong>© 2024 BSC | Bubbles Skin Care</strong><br />Todos los derechos reservados
        </p>
      </div>
      </div>

<a href="<?php echo wc_get_cart_url(); ?>" class="footer__shopping-cart" aria-label="Shopping Cart with <?php echo WC()->cart->get_cart_contents_count(); ?> items">
  <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
  <span class="footer__cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
</a>

      <div class="footer__developer-section">
      <h2 class="footer__developer-heading">
        <a href="http://www.wappy.com.co" target="_blank">Desarrollado con amor por Wappy</a>
      </h2>
  </div>

  </div>


</footer>