<?php

require_once get_stylesheet_directory() . '/components/header/BSC_HeaderNav.class.php';
require_once get_stylesheet_directory() . '/components/header/BSC_MenuNav.class.php';
require_once get_stylesheet_directory() . '/components/header/header-menu-config.php';

$headerNav = bsc_build_header_nav();

[
    'my_account_url'       => $my_account_url,
    'orders_url'           => $orders_url,
    'bubble_points_url'    => $bubble_points_url,
    'edit_account_url'     => $edit_account_url,
    'edit_address_url'     => $edit_address_url,
    'logout_url'           => $logout_url,
    'login_url'            => $login_url,
    'register_url'         => $register_url,
    'checkout_url'         => $checkout_url,
    'mobile_profile_url'   => $mobile_profile_url,
    'mobile_profile_label' => $mobile_profile_label,
] = bsc_get_header_account_links();

?>
<header class="bsc bsc__header bsc__header--desktop">
    <div class="header__container">
        <a class="header__image" href="/">
            <img src="<?php echo get_template_directory_uri();?>/images/bsc_logo_header.png">
        </a>
        <div class="header__nav">
            <?php
                echo $headerNav->renderNavButtons();
			?>
        </div>
        <div class="header__menu">
            <ul class="menu__icons">
                <li class="menu__icon icon--search">
                    <button class="btn-search-toggle">
                          <div class="image__icon-hoverable">
                                <img class="image__icon icon--normal" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png">
                                <img class="image__icon icon--hover" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png">
                          </div>
                    </button>
                    
                    <div class="bsc header__search hidden">
                        <input type="text" class="header-search-input" placeholder="Buscar productos...">
                        <ul class="search-results"></ul>
                    </div>
                </li>
                <?php if (is_user_logged_in()) : ?>
                    <li class="menu__icon icon--profile">
                        <button id="profile-button" aria-haspopup="true" aria-expanded="false">
                            <div class="image__icon-hoverable">
                                <img class="image__icon icon--normal" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png">
                                <img class="image__icon icon--hover" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png">
                            </div>
                        </button>
                        <div id="profile-dropdown" class="bsc__profile-dropdown">
                        <ul>
                            <li style="display:none"><a href="<?php echo esc_url($my_account_url); ?>">Mi perfil</a></li>
                            <li><hr><a href="<?php echo esc_url($orders_url); ?>">Mis pedidos</a></li>
                            <li><hr><a href="<?php echo esc_url($bubble_points_url); ?>">Mis puntos</a></li>
                            <li><hr><a href="<?php echo esc_url($edit_account_url); ?>">Mis datos</a></li>
                            <li><hr><a href="<?php echo esc_url($edit_address_url); ?>">Mis direcciones</a></li>
                            <li><hr><a href="<?php echo esc_url($logout_url); ?>">Cerrar sesión</a></li>
                        </ul>
                        </div>
                    </li>
                    <?php else : ?>
                    <li class="menu__icon icon--profile">
                        <button id="profile-button" aria-haspopup="true" aria-expanded="false">
                        <i aria-hidden="true" class="dlicon users_single-03"></i>
                        </button>
                        <div id="profile-dropdown" class="bsc__profile-dropdown">
                        <ul>
                            <li><a href="<?php echo esc_url($login_url); ?>">Iniciar sesión</a></li>
                            <li><hr><a href="<?php echo esc_url($register_url); ?>">Crear cuenta</a></li>
                        </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                <li class="menu__icon icon--shop" style="display:none">
                    <a href="<?php echo esc_url($checkout_url); ?>" aria-label="Ir al checkout" title="Ir al checkout">
                        <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
                        <span></span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
<div class="bsc header__nav-menus">
<?php
    echo $headerNav->renderNavContent();
?>
</div>



<header class="bsc bsc__header bsc__header--mobile" id="mobileHeader">
    <div class="header-mobile__nav">

        <div class="header-mobile__nav-left">
            <button
                id="mobileMenuToggle"
                type="button"
                aria-label="Abrir menú"
                aria-controls="mobileSidebar"
                aria-expanded="false"
                class="header-mobile__icon-btn header-mobile__menu-btn"
            >
                <span class="mobile-toggle__icon mobile-toggle__icon--open">
                    <i aria-hidden="true" class="dlicon ui-3_menu-left"></i>
                </span>

                <span class="mobile-toggle__icon mobile-toggle__icon--close" aria-hidden="true">
                    &times;
                </span>
            </button>
        </div>

        <div class="header-mobile__nav-center">
            <a href="/" class="item--logo">
                <img
                    class="header-mobile__logo"
                    src="<?php echo get_template_directory_uri(); ?>/images/bsc_logo_header_mobile.png"
                    alt="Bubbles Skin Care"
                >
            </a>
        </div>

        <div class="header-mobile__nav-right">
            <button
                id="mobile-search-btn"
                aria-label="Buscar productos"
                class="header-mobile__icon-btn header-mobile__search-btn"
                type="button"
            >
                <div class="image__icon-hoverable">
                    <img
                        class="image__icon icon--normal"
                        alt="Buscar"
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png"
                    >
                    <img
                        class="image__icon icon--hover"
                        alt=""
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__search-icon--hover.png"
                    >
                </div>
            </button>
            <a
                id="profile-button-mobile"
                href="<?php echo esc_url($mobile_profile_url); ?>"
                aria-label="<?php echo esc_attr($mobile_profile_label); ?>"
                class="header-mobile__icon-btn header-mobile__profile-btn"
            >
                <div class="image__icon-hoverable">
                    <img
                        class="image__icon icon--normal"
                        alt="<?php echo esc_attr($mobile_profile_label); ?>"
                        src="<?php echo esc_url( get_template_directory_uri() );?>/images/bsc_header__profile-icon--hover.png"
                    >
                    <img
                        class="image__icon icon--hover"
                        alt=""
                        src="<?php echo esc_url( get_template_directory_uri() );?>/images/bsc_header__profile-icon--hover.png"
                    >
                </div>
            </a>
        </div>

    </div>
</header>

<!-- Mobile search panel (standalone — not inside any display:none header) -->
<div class="bsc-mobile-search-panel" role="search" aria-label="Buscar productos">
    <input type="text" class="header-search-input" placeholder="Buscar productos…" autocomplete="off">
    <ul class="search-results"></ul>
</div>

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
