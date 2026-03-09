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
        </ul>
      </div>
      <div class="footer__column">
        <h2 class="footer__heading">Programas</h2>
        <div class="footer__divider footer__divider--short"></div>
        <ul class="footer__list">
          <li class="footer__list-item">
            <a href="/bubble-creators/" class="footer__link">Bubble Creators</a>
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
          <strong>© 2020 - 2025 BSC | Bubbles Skin Care</strong><br />Todos los derechos reservados
        </p>
      </div>
      </div>

      <a href="<?php echo wc_get_checkout_url(); ?>" class="footer__shopping-cart" aria-label="Shopping Cart with <?php echo WC()->cart->get_cart_contents_count(); ?> items">
        <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
        <span class="footer__cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
      </a>

      <a target="_blank" rel="noopener noreferrer" href="https://api.whatsapp.com/send?phone=573156922859&text=Hola%20BSC%2C%20me%20gustar%C3%ADa%20tener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20los%20productos%20que%20tienen%20en%20la%20tienda.%20%E2%98%BA%EF%B8%8F" class="footer__whatsapp" aria-label="Whatsapp Link">
        <i class="premium-svg-nodraw premium-drawable-icon fab fa-whatsapp" aria-hidden="true"></i>
        <span class="footer__wa">Whatsapp</span>
      </a>

      <div class="footer__developer-section">

    <!--
      <h2 class="footer__developer-heading">
        <a href="http://www.wappy.com.co" target="_blank">Desarrollado con amor por BSC</a>
      </h2>
-->
  </div>

  </div>


</footer>