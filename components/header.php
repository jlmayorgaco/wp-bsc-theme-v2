
<?php

    require_once get_stylesheet_directory() . '/components/header/BSC_HeaderNav.class.php';
    require_once get_stylesheet_directory() . '/components/header/BSC_MenuNav.class.php';

    $menuSkinCare = new BSC_MenuNav();
    $menuSkinCare->setName('SKIN CARE');
    $menuSkinCare->setSlug('BSC_MENU_NAV_SKIN_CARE');
    $menuSkinCare->setCover([
        'image' => get_theme_file_uri('images/header_menus/Menu-01-F-100.jpg'),
        'link' => get_theme_file_uri('images/header_menus/Menu-01-F-100.jpg'),
    ]);
    $menuSkinCare->setMenus([]);
    $menuSkinCare->appendMenu([
        'slug' => 'bsc-menu-rutina-coreana',
        'title' => 'Rutina coreana',
        'items' => [
            [
                'slug' => 'sk-rutina-s1-limpiadores-aceitosos',
                'title' => '1. Limpiadores Aceitosos',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'
            ],
            [
                'slug' => 'sk-rutina-s2-limpiadores-acuosos',
                'title' => '2. Limpiadores Acuosos',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'
            ],
            [
                'slug' => 'sk-rutina-s3-exfoliantes',
                'title' => '3. Exfoliantes',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes'
            ],
            [
                'slug' => 'sk-rutina-s4-tonicos',
                'title' => '4. Tónicos',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'
            ],
            [
                'slug' => 'sk-rutina-s5-mascarillas-1',
                'title' => '5. Mascarillas',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1'
            ],
            /*
            [
                'slug' => 'sk-rutina-s5-mascarillas-2',
                'title' => '5. Mascarillas 2',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-2'
            ],
            */
            [
                'slug' => 'sk-rutina-s6-esencias',
                'title' => '6. Esencias',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias'
            ],
            [
                'slug' => 'sk-rutina-s7-serums',
                'title' => '7. Serums',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'
            ],
            [
                'slug' => 'sk-rutina-s8-contorno-de-ojos',
                'title' => '8. Contorno de Ojos',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s8-contorno-de-ojos'
            ],
            [
                'slug' => 'sk-rutina-s9-hidratantes',
                'title' => '9. Hidratantes',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'
            ],
            [
                'slug' => 'sk-rutina-s10-protectores-solares-crema',
                'title' => '10. Protectores Solares Liquido',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'
            ],
            [
                'slug' => 'sk-rutina-s10-protectores-solares-barrita',
                'title' => '10. Protectores Solares Barrita',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita'
            ],
        ]
    ]);
    $menuSkinCare->appendMenu([
        'slug' => 'bsc-menu-complementos',
        'title' => 'Complementos',
        'items' => [
            [
                'slug' => 'sk-rutina-s11-complemento-c1-aceites-faciales',
                'title' => '11. Aceites Faciales',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s11-complemento-c1-aceites-faciales'
            ],
            [
                'slug' => 'sk-rutina-s12-complemento-c2-spot',
                'title' => '12. Spot',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c2-spot'
            ],
            [
                'slug' => 'sk-rutina-s12-complemento-c3-patches',
                'title' => '12. Patches',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c3-patches'
            ],
            [
                'slug' => 'sk-rutina-s13-complemento-c4-mist-y-brumas',
                'title' => '13. Mist y Brumas',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s13-complemento-c4-mist-y-brumas'
            ],
            [
                'slug' => 'sk-rutina-s14-complemento-c5-sticks',
                'title' => '14. Sticks',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s14-complemento-c5-sticks'
            ],
            [
                'slug' => 'sk-rutina-s15-complemento-c6-labios',
                'title' => '15. Labios',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s15-complemento-c6-labios'
            ],
            [
                'slug' => 'sk-rutina-s16-complemento-c7-inner-beauty',
                'title' => '16. Inner Beauty',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s16-complemento-c7-inner-beauty'
            ],
            [
                'slug' => 'sk-rutina-s17-complemento-c8-accesorios',
                'title' => '17. Accesorios',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s17-complemento-c8-accesorios'
            ],
            [
                'slug' => 'sk-rutina-s18-complemento-c9-minis',
                'title' => '18. Minis',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s18-complemento-c9-minis'
            ],
        ]
    ]);
    $menuSkinCare->appendMenu([
        'slug' => 'bsc-menu-tipos-piel',
        'title' => 'Tipo de piel',
        'items' => [
            [
                'slug' => 'sk-tipo-piel-seca',
                'title' => 'Piel Seca',
                'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-seca'
            ],
            [
                'slug' => 'sk-tipo-piel-normal',
                'title' => 'Piel Normal',
                'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-normal'
            ],
            [
                'slug' => 'sk-tipo-piel-mixta',
                'title' => 'Piel Mixta',
                'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-mixta'
            ],
            [
                'slug' => 'sk-tipo-piel-grasa',
                'title' => 'Piel Grasa',
                'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-grasa'
            ],
        ]
    ]);

    $menuHairCare = new BSC_MenuNav();
    $menuHairCare->setName('HAIR CARE');
    $menuHairCare->setSlug('BSC_MENU_NAV_HAIR_CARE');
    $menuHairCare->setCover([
        'image' => get_theme_file_uri('images/header_menus/Menu-02-F-100.jpg'),
        'link' => get_theme_file_uri('images/header_menus/Menu-02-F-100.jpg'),
    ]);
    $menuHairCare->setMenus([]);
    $menuHairCare->appendMenu([
        'slug' => 'hc-menu-basico',
        'title' => 'Rutina Capilar Coreana',
        'items' => [
            [
                'slug' => 'hc-shampoo',
                'title' => '1. Shampoo',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s1-shampoo'
            ],
            [
                'slug' => 'hc-acondicionador',
                'title' => '2. Acondicionador',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s4-acondicionadores'
            ],
            [
                'slug' => 'hc-mascarillas',
                'title' => '3. Mascarillas',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s3-mascarillas'
            ],
            [
                'slug' => 'hc-tratamientos-leave-in',
                'title' => '4. Tratamientos sin enjuague',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s7-esencias-leave-in'
            ],
        ]
    ]);
    $menuHairCare->appendMenu([
        'slug' => 'hc-menu-complementario',
        'title' => 'Complementos',
        'items' => [
            [
                'slug' => 'hc-exfoliantes',
                'title' => '5. Exfoliantes',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s2-exfoliantes'
            ],
            [
                'slug' => 'hc-ampollas',
                'title' => '6. Ampollas',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-ampollas'
            ],
            [
                'slug' => 'hc-aceites',
                'title' => '7. Aceites',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s9-aceites'
            ],
            [
                'slug' => 'hc-cuero-cabelludo',
                'title' => '8. Cuero cabelludo',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-necesidad-cuero-cabelludo'
            ],
        ]
    ]);

    $menuMakeUp = new BSC_MenuNav();
    $menuMakeUp->setName('MAKE UP');
    $menuMakeUp->setSlug('BSC_MENU_NAV_MAKE_UP');
    $menuMakeUp->setCover([
        'image' => get_theme_file_uri('images/header_menus/Menu-05-F-100.jpg'),
        'link' => get_theme_file_uri('images/header_menus/Menu-05-F-100.jpg'),
    ]);

    // Rutina Maquillaje
    $menuMakeUp->appendMenu([
        'slug' => 'nav-menu-mk-corean-makeup',
        'title' => 'Maquillaje coreano',
        'items' => [
            ['slug' => 'mk-bb-creams-bases', 'title' => '1. BB Creams y Bases', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p1-bb-creams-y-bases'],
            ['slug' => 'mk-cushions-refills', 'title' => '2. Cushions y Refills', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p2-cushions-y-refills'],
            ['slug' => 'mk-sombras-paletas', 'title' => '3. Sombras y Paletas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p3-sombras-y-paletas'],
            ['slug' => 'mk-delineadores', 'title' => '4. Delineadores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p4-delineadores'],
            ['slug' => 'mk-pestaninas', 'title' => '5. Pestañinas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p5-pestaninas'],
            ['slug' => 'mk-rubores', 'title' => '6. Rubores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p6-rubores'],
            ['slug' => 'mk-iluminadores', 'title' => '7. Iluminadores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p7-iluminadores'],
            ['slug' => 'mk-correctores', 'title' => '8. Correctores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p8-correctores'],
            ['slug' => 'mk-tintas', 'title' => '9. Tintas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p9-tintas'],
            ['slug' => 'mk-labiales', 'title' => '10. Labiales', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p10-labiales'],
            ['slug' => 'mk-polvos', 'title' => '11. Polvos', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p11-polvos'],
        ]
    ]);

    // Complementos Maquillaje
    $menuMakeUp->appendMenu([
        'slug' => 'nav-menu-mk-complements',
        'title' => 'Complementos',
        'items' => [
            ['slug' => 'mk-cejas', 'title' => '12. Cejas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p12-complementos-c1-cejas'],
            ['slug' => 'mk-primers', 'title' => '13. Primers', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p13-complementos-c2-primers'],
            ['slug' => 'mk-fijadores', 'title' => '14. Fijadores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p14-complementos-c3-fijadores'],
            ['slug' => 'mk-brochas', 'title' => '15. Brochas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p15-complementos-c4-brochas'],
            ['slug' => 'mk-pestanas', 'title' => '16. Pestañas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p16-complementos-c5-pestanas'],
        ]
    ]);

    $menuCoreanRutine = new BSC_MenuNav();
    $menuCoreanRutine->setName('RUTINA COREANA');
    $menuCoreanRutine->setSlug('BSC_MENU_NAV_COREAN_RUTINE');
    $menuCoreanRutine->setCover([
        'image' => 'http://bsc.local/wp-content/uploads/2023/10/Menu-03-F-100.jpg',
        'link' => bsc_get_whatsapp_url( 'general' ),
    ]);
    $menuCoreanRutine->appendMenu([
        'slug' => 'nav-menu-corea-rutine-basic',
        'title' => 'Rutina básica',
        'items' => [
            ['slug' => 'limpiador-acuoso', 'title' => 'Limpiador Acuoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
            ['slug' => 'tonico', 'title' => 'Tónico', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
            ['slug' => 'hidratante', 'title' => 'Hidratante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
            ['slug' => 'protector-solar', 'title' => 'Protector Solar', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
        ]
    ]);
    $menuCoreanRutine->appendMenu([
        'slug' => 'nav-menu-corea-rutine-intermedia',
        'title' => 'Rutina intermedia',
        'items' => [
            ['slug' => 'limpiador-aceitoso', 'title' => 'Limpiador Aceitoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
            ['slug' => 'limpiador-acuoso', 'title' => 'Limpiador Acuoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
            ['slug' => 'tonico', 'title' => 'Tónico', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
            ['slug' => 'serum', 'title' => 'Serum', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
            ['slug' => 'hidratante', 'title' => 'Hidratante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
            ['slug' => 'protector-solar', 'title' => 'Protector Solar', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita'],
        ]
    ]);
    $menuCoreanRutine->appendMenu([
        'slug' => 'nav-menu-corea-rutine-experta',
        'title' => 'Rutina Experta',
        'items' => [
            ['slug' => 'limpiador-aceitoso', 'title' => 'Limpiador Aceitoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
            ['slug' => 'limpiador-acuoso', 'title' => 'Limpiador Acuoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
            ['slug' => 'exfoliante', 'title' => 'Exfoliante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes'],
            ['slug' => 'tonico', 'title' => 'Tónico', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
            ['slug' => 'mascarilla', 'title' => 'Mascarilla', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1'],
            ['slug' => 'esencias', 'title' => 'Esencias', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias'],
            ['slug' => 'serum', 'title' => 'Serum', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
            ['slug' => 'contorno-de-ojos', 'title' => 'Contorno de Ojos', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s8-contorno-de-ojos'],
            ['slug' => 'hidratante', 'title' => 'Hidratante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
            ['slug' => 'protector-solar', 'title' => 'Protector Solar', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
        ]
    ]);

    $menuBlog = new BSC_MenuNav();
    $menuBlog->setName('BLOG');
    $menuBlog->setSlug('BSC_MENU_NAV_BLOG');
    $menuBlog->setCover([
        'image' => 'http://bsc.local/wp-content/uploads/2023/10/Menu-04-F-100.jpg',
        'link' => 'http://bsc.local/veja-just-dropped-limited-edition-sneakers-with-mansur-gavriel/',
    ]);
    $menuBlog->appendMenu([
        'slug' => 'nav-menu-blog-categorias',
        'title' => 'Categorías',
        'items' => [
            ['slug' => 'entrevistas', 'title' => '1. Entrevistas', 'link' => 'http://bsc.local/blog/'],
            ['slug' => 'resenas', 'title' => '2. Reseñas', 'link' => '#'],
            ['slug' => 'tendencias', 'title' => '3. Tendencias', 'link' => '#'],
            ['slug' => 'skin-care', 'title' => '4. Skin care', 'link' => '#'],
            ['slug' => 'hair-care', 'title' => '5. Hair care', 'link' => '#'],
            ['slug' => 'maquillaje', 'title' => '6. Maquillaje', 'link' => '#'],
        ]
    ]);

 

    $menuContact = new BSC_MenuNav();
    $menuContact->setName('CONTACTO');
    $menuContact->setSlug('BSC_MENU_NAV_CONTACT');
    $menuContact->setLink("/contact-us/");

    $headerNav = new BSC_HeaderNav();
    $headerNav->setId('BSC_Header_Nav');
    $headerNav->setClass('bsc__header-nav');

    $headerNav->setMenus([]);
    $headerNav->addMenu($menuSkinCare);
    $headerNav->addMenu($menuHairCare);
    $headerNav->addMenu($menuMakeUp);
    //$headerNav->addMenu($menuCoreanRutine);
    //$headerNav->addMenu($menuBlog);
    $headerNav->addMenu($menuContact);


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
                            <li style="display:none"><a href="/mi-cuenta/">Mi Perfil</a></li>
                            <li><hr><a href="/mi-cuenta/orders/">Mis Pedidos</a></li>
                            <li><hr><a href="/mi-cuenta/bubble-points/">Mis Puntos</a></li>
                            <li><hr><a href="/mi-cuenta/edit-account/">Mis Datos</a></li>
                            <li><hr><a href="/mi-cuenta/edit-address/">Mis Direcciones</a></li>
                            <li><hr><a href="/mi-cuenta/customer-logout/">Cerrar sesión</a></li>
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
                            <li><a href="/login/">Iniciar sesión</a></li>
                            <li><hr><a href="/register/">Crear cuenta</a></li>
                        </ul>
                        </div>
                    </li>
                    <?php endif; ?>

                <li class="menu__icon icon--shop" style="display:none">
                    <a href="/shop/" aria-label="View your shopping cart" title="View your shopping cart">
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

            <button
                id="profile-button-mobile"
                aria-haspopup="true"
                aria-expanded="false"
                class="header-mobile__icon-btn header-mobile__profile-btn"
                type="button"
            >
                <div class="image__icon-hoverable">
                    <img
                        class="image__icon icon--normal"
                        alt="Mi cuenta"
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png"
                    >
                    <img
                        class="image__icon icon--hover"
                        alt=""
                        src="<?php echo get_template_directory_uri();?>/images/bsc_header__profile-icon--hover.png"
                    >
                </div>
            </button>
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
                    <a class="mobile-nav__item-link" href="/mi-cuenta/orders/">
                        <span class="mobile-nav__item-text">Mis Pedidos</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="/mi-cuenta/bubble-points/">
                        <span class="mobile-nav__item-text">Bubble Points</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="/mi-cuenta/customer-logout/">
                        <span class="mobile-nav__item-text">Cerrar sesión</span>
                    </a>
                </li>
                <?php else : ?>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="/login/">
                        <span class="mobile-nav__item-text">Iniciar sesión</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="/register/">
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