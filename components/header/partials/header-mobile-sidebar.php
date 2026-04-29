<sidebar class="bsc bsc__sidebar bsc__sidebar--mobile" id="mobileSidebar">
  <div class="sidebar-mobile__container">



    <!-- Navigation -->
    <nav class="sidebar-mobile__nav">

        <details class="mobile-nav">
            <summary class="mobile-nav__title">
                <div class="mobile-nav__title-text">MI CUENTA</div>
            </summary>
            <ul class="mobile-nav__items">
                <?php if (is_user_logged_in()) : ?>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="<?php echo esc_url($orders_url); ?>">
                        <span class="mobile-nav__item-text">Mis pedidos</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="<?php echo esc_url($bubble_points_url); ?>">
                        <span class="mobile-nav__item-text">Mis puntos</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="<?php echo esc_url($logout_url); ?>">
                        <span class="mobile-nav__item-text">Cerrar sesión</span>
                    </a>
                </li>
                <?php else : ?>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="<?php echo esc_url($login_url); ?>">
                        <span class="mobile-nav__item-text">Iniciar sesión</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="<?php echo esc_url($register_url); ?>">
                        <span class="mobile-nav__item-text">Crear cuenta</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </details>

        <?php foreach ($headerNav->getMenus() as $menu): ?>
            <?php $menuSections = $menu->getMenus(); ?>

            <?php if (count($menuSections) > 0) { ?>
                <details class="mobile-nav">
                    <summary class="mobile-nav__title">
                        <div class="mobile-nav__title-text"><?php echo esc_html($menu->getName()); ?></div>
                    </summary>

                    <ul class="mobile-nav__items">
                        <?php foreach ($menuSections as $section): ?>
                        <?php foreach ($section['items'] as $item): ?>
                           
                            
                            <li class="mobile-nav__item <?php echo ($item['slug']); ?>">
                            <a class="mobile-nav__item-link" href="<?php echo esc_url($item['link']); ?>">
                                <span class="mobile-nav__item-text"><?php echo esc_html($item['title']); ?></span>
                            </a>
                            </li>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </ul>
                </details>

            <?php }; ?>

            <?php if (count($menuSections) == 0) { ?>
                <?php $menu->getLink(); ?>
                <a class="mobile-nav__title" href="<?php echo esc_url($menu->getLink()); ?>">
                    <span class="mobile-nav__title-text"><?php echo esc_html($menu->getName()); ?></span>
                </a>
            <?php }; ?>

        <?php endforeach; ?>



    </nav>

    <!-- CTA Button -->
    <a class="sidebar-mobile__button " href="/shop">
      <span>¡Ir a la tienda!</span>
    </a>

    <div class="sidebar-mobile__carita">
        <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_profile_logo.png" alt="BSC Profile">
    </div>

    <!-- Social Media -->
    <ul class="sidebar-mobile__socials">
    <li class="sidebar-mobile__social">
        <a href="https://www.instagram.com/bubbles.skincare/" target="_blank" rel="noopener">
        <i class="fab fa-instagram"></i>
        </a>
    </li>
    <li class="sidebar-mobile__social">
        <a href="https://www.tiktok.com/@bubblesskincare" target="_blank" rel="noopener">
        <i class="fab fa-tiktok"></i>
        </a>
    </li>

    
    </ul>


    <!-- Footer -->
    <div class="sidebar-mobile__copy">
      <h5>© 2020 - 2026 BSC | Bubbles Skin Care</h5>
      <h4>Todos los derechos reservados</h4>
    </div>
    

  </div>

</sidebar>


<!-- Mobile menu script enqueued via mobile-menu.js -->
