
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
                'title' => '5. Mascarillas 1',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1'
            ],
            [
                'slug' => 'sk-rutina-s5-mascarillas-2',
                'title' => '5. Mascarillas 2',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-2'
            ],
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
                'title' => '10. Protectores Solares',
                'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'
            ],
            [
                'slug' => 'sk-rutina-s10-protectores-solares-barrita',
                'title' => '10. Protectores Solares',
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
                'link' => '/product-category/group-hair-care/hc-rutina/hc-shampoo'
            ],
            [
                'slug' => 'hc-acondicionador',
                'title' => '2. Acondicionador',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-acondicionador'
            ],
            [
                'slug' => 'hc-mascarillas',
                'title' => '3. Mascarillas',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-mascarillas'
            ],
            [
                'slug' => 'hc-tratamientos-leave-in',
                'title' => '4. Tratamientos sin enjuague',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-tratamientos-leave-in'
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
                'link' => '/product-category/group-hair-care/hc-rutina/hc-exfoliantes'
            ],
            [
                'slug' => 'hc-ampollas',
                'title' => '6. Ampollas',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-ampollas'
            ],
            [
                'slug' => 'hc-aceites',
                'title' => '7. Aceites',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-aceites'
            ],
            [
                'slug' => 'hc-cuero-cabelludo',
                'title' => '8. Cuero cabelludo',
                'link' => '/product-category/group-hair-care/hc-rutina/hc-cuero-cabelludo'
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
        'link' => 'https://api.whatsapp.com/send?phone=573202176359&text=Hola%20BSC%2C%20me%20gustar%C3%ADa%20tener%20m%C3%A1s%20informaci%C3%B3n%20sobre%20los%20productos%20que%20tienen%20en%20la%20tienda.%20%E2%98%BA%EF%B8%8F',
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
                            <li><a href="/mi-cuenta/">Mi Perfil</a></li>
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
    <ul class="header-mobile__nav">
        <li class="header-mobile__nav-item item--sidebar-btn"> 
            <button id="mobileHeaderButton">
                <i aria-hidden="true" class="dlicon ui-3_menu-left"></i>
            </button>
        </li>
        <li class="header-mobile__nav-item item--logo">
            <a href="/">
                <img class="header-mobile__logo" src="http://bsc2.local/wp-content/themes/wp-bsc-theme-v2/images/bsc_logo_header.png" alt="Logo">
            </a>
        </li>
        <li class="header-mobile__nav-item item--shop">
            <a href="/shop">
                <i aria-hidden="true" class="dlicon shopping_bag-20"></i>
            </a>
        </li>
    </ul>
</header>

<sidebar class="bsc bsc__sidebar bsc__sidebar--mobile" id="mobileSidebar">
  <div class="sidebar-mobile__container">

    <!-- Logo -->
    <img class="sidebar-mobile__logo" src="http://bsc2.local/wp-content/themes/wp-bsc-theme-v2/images/bsc_logo_header.png" alt="Logo">

    <!-- Navigation -->
    <nav class="sidebar-mobile__nav">

        <details class="mobile-nav">
            <summary class="mobile-nav__title">
                <div class="mobile-nav__title-text">MI CUENTA</div>
            </summary>
            <ul class="mobile-nav__items">
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Iniciar Sesión</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Registrame</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Mi Cuenta</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Mis Puntos</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Politicas de Privacidad</span>
                    </a>
                </li>
                <li class="mobile-nav__item">
                    <a class="mobile-nav__item-link" href="">
                        <span class="mobile-nav__item-text">Olvide mi contraseña</span>
                    </a>
                </li>
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
                            <li class="mobile-nav__item">
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
    <a class="sidebar-mobile__button" href="/shop">
      <span>VISITAR TIENDA</span>
      <i aria-hidden="true" class="dlicon shopping_bag-09"></i>
    </a>

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
    <li class="sidebar-mobile__social">
        <a href="https://www.threads.net/@bubbles.skincare" target="_blank" rel="noopener">
        <!-- Threads icon -->
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="16" height="16" fill="currentColor">
            <path d="m282,149.8c0,24.9,0,49.7,0,74.6,0,32.8-24.2,57-57.1,57-50,0-99.9,0-149.9,0-32.6,0-56.9-24.2-56.9-56.8,0-50.1,0-100.2,0-150.3,0-32.6,24.3-56.8,56.9-56.8,50,0,99.9,0,149.9,0,32.9,0,57,24.1,57.1,57,0,25.1,0,50.2,0,75.3Zm-132.1,107.6c25.1,0,50.2,0,75.3,0,19.5,0,32.8-13.2,32.8-32.6,0-50.2,0-100.4,0-150.6,0-19.4-13.3-32.6-32.8-32.6-50.1,0-100.2,0-150.3,0-19.7,0-32.8,13.2-32.8,33,0,50,0,99.9,0,149.9,0,19.9,13.2,33,33.2,33,24.9,0,49.7,0,74.6,0Z"/>
            <path d="m222.7,112.3c-4.9,1.4-9.8,2.9-14.7,4.3-2-4.8-3.5-9.4-5.6-13.6-7.4-15-19.3-24.5-35.5-28.5-14.6-3.6-29.2-3.4-43.6,1.3-17.7,5.9-28.5,18.9-33.7,36.3-6.8,23.1-6.9,46.3.9,69.2,7.2,21.2,22.3,33.3,44.3,36.6,14.7,2.1,29.3,1.7,42.8-5.5,11.3-6,19.6-14.7,21.8-28,2-11.8-3.7-23.8-13.7-28.9-.8,3.1-1.4,6.2-2.2,9.2-6.8,24-33.2,35.1-55.1,23.1-10.4-5.7-15.8-14.6-15.3-26.5.5-12.7,7.7-20.8,19-25.6,10.6-4.5,21.8-4.1,33-3.2,1.6.1,3.1.3,4.7.5-.5-8.4-5.3-15.3-12.4-17.8-11-4-21.4-1.3-30,8-4-2.8-8.1-5.6-12.5-8.6,6-9.1,14.2-14.4,24.6-16,13.3-2,25.8-.3,35.7,10,6.9,7.1,9.6,16.1,10.6,25.7.2,2.2.6,3.7,3,4.8,31.8,15.1,32.1,51.8,14.7,72.1-11.5,13.4-26.3,20.6-43.7,22.6-1.2.1-2.4.5-3.5.7h-17.7c-1.2-.2-2.4-.6-3.5-.7-18.5-2.4-34.4-9.5-46.4-24.2-9.6-11.7-14.6-25.4-17.2-40.1-.8-4.8-1.4-9.7-2.1-14.5,0-6.4,0-12.8,0-19.2.2-1.1.4-2.1.6-3.2,1.7-8.6,2.6-17.5,5.2-25.9,7.4-23.7,22.6-40.1,46.9-47.1,5.4-1.5,11-2.3,16.5-3.4,5.9,0,11.8,0,17.7,0,1,.2,2.1.6,3.1.7,25.3,3.2,44.7,15.3,56.4,38.4,2.6,5.2,4.5,10.8,6.7,16.2v.7Z"/>
            <path d="m170.5,149.6c-6.7-1.9-16.4-2.6-24.5-1.2-3.9.7-8,1.8-11.4,3.8-7.6,4.5-8.4,14.1-2.1,19.7,9,8.1,27.2,5.6,33.4-4.8,3.1-5.3,4.4-11.1,4.5-17.4Z"/>
        </svg>
        </a>
    </li>
    </ul>


    <!-- Footer -->
    <div class="sidebar-mobile__copy">
      <h5>© 2023 BSC | Bubbles Skin Care</h5>
      <h4>Todos los derechos reservados</h4>
      <br>
    </div>

    <a class="sidebar-mobile__brand" href="http://www.jlma.com.co" target="_blank">Desarrollado con amor por Wappy 🤍</a>
    

  </div>
  <!-- Close Button -->
  <div class="sidebar-mobile__close" id="closeMobileMenu">
    <span>x</span>
  </div>
</sidebar>


<script>
  const openBtn = document.getElementById('mobileHeaderButton');
  const closeBtn = document.getElementById('closeMobileMenu');
  const sidebar = document.getElementById('mobileSidebar');

  // Open mobile menu
  function openMobileMenu() {
    sidebar.classList.add('is-open');
  }

  openBtn.addEventListener('click', openMobileMenu);

  closeBtn.addEventListener('click', () => {
    sidebar.classList.remove('is-open');
  });
</script>
