<?php
defined('ABSPATH') || exit;

if (!function_exists('bsc_get_header_menu_configs')) {
    function bsc_get_header_menu_configs(): array {
        return [
            [
                'enabled' => true,
                'name'    => 'SKIN CARE',
                'slug'    => 'BSC_MENU_NAV_SKIN_CARE',
                'cover'   => [
                    'image' => get_theme_file_uri('images/header_menus/Menu-01-F-100.jpg'),
                    'link'  => get_theme_file_uri('images/header_menus/Menu-01-F-100.jpg'),
                ],
                'menus' => [
                    [
                        'slug'  => 'bsc-menu-rutina-coreana',
                        'title' => 'Rutina coreana',
                        'items' => [
                            ['slug' => 'sk-rutina-s1-limpiadores-aceitosos', 'title' => '1. Limpiadores Aceitosos', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
                            ['slug' => 'sk-rutina-s2-limpiadores-acuosos', 'title' => '2. Limpiadores Acuosos', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                            ['slug' => 'sk-rutina-s3-exfoliantes', 'title' => '3. Exfoliantes', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes'],
                            ['slug' => 'sk-rutina-s4-tonicos', 'title' => '4. Tónicos', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                            ['slug' => 'sk-rutina-s5-mascarillas-1', 'title' => '5. Mascarillas', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1'],
                            ['slug' => 'sk-rutina-s6-esencias', 'title' => '6. Esencias', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias'],
                            ['slug' => 'sk-rutina-s7-serums', 'title' => '7. Serums', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
                            ['slug' => 'sk-rutina-s8-contorno-de-ojos', 'title' => '8. Contorno de Ojos', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s8-contorno-de-ojos'],
                            ['slug' => 'sk-rutina-s9-hidratantes', 'title' => '9. Hidratantes', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                            ['slug' => 'sk-rutina-s10-protectores-solares-crema', 'title' => '10. Protectores Solares Liquido', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
                            ['slug' => 'sk-rutina-s10-protectores-solares-barrita', 'title' => '10. Protectores Solares Barrita', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita'],
                        ],
                    ],
                    [
                        'slug'  => 'bsc-menu-complementos',
                        'title' => 'Complementos',
                        'items' => [
                            ['slug' => 'sk-rutina-s11-complemento-c1-aceites-faciales', 'title' => '11. Aceites Faciales', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s11-complemento-c1-aceites-faciales'],
                            ['slug' => 'sk-rutina-s12-complemento-c2-spot', 'title' => '12. Spot', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c2-spot'],
                            ['slug' => 'sk-rutina-s12-complemento-c3-patches', 'title' => '12. Patches', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c3-patches'],
                            ['slug' => 'sk-rutina-s13-complemento-c4-mist-y-brumas', 'title' => '13. Mist y Brumas', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s13-complemento-c4-mist-y-brumas'],
                            ['slug' => 'sk-rutina-s14-complemento-c5-sticks', 'title' => '14. Sticks', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s14-complemento-c5-sticks'],
                            ['slug' => 'sk-rutina-s15-complemento-c6-labios', 'title' => '15. Labios', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s15-complemento-c6-labios'],
                            ['slug' => 'sk-rutina-s16-complemento-c7-inner-beauty', 'title' => '16. Inner Beauty', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s16-complemento-c7-inner-beauty'],
                            ['slug' => 'sk-rutina-s17-complemento-c8-accesorios', 'title' => '17. Accesorios', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s17-complemento-c8-accesorios'],
                            ['slug' => 'sk-rutina-s18-complemento-c9-minis', 'title' => '18. Minis', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s18-complemento-c9-minis'],
                        ],
                    ],
                    [
                        'slug'  => 'bsc-menu-tipos-piel',
                        'title' => 'Tipo de piel',
                        'items' => [
                            ['slug' => 'sk-tipo-piel-seca', 'title' => 'Piel Seca', 'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-seca'],
                            ['slug' => 'sk-tipo-piel-normal', 'title' => 'Piel Normal', 'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-normal'],
                            ['slug' => 'sk-tipo-piel-mixta', 'title' => 'Piel Mixta', 'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-mixta'],
                            ['slug' => 'sk-tipo-piel-grasa', 'title' => 'Piel Grasa', 'link' => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-grasa'],
                        ],
                    ],
                ],
            ],
            [
                'enabled' => true,
                'name'    => 'HAIR CARE',
                'slug'    => 'BSC_MENU_NAV_HAIR_CARE',
                'cover'   => [
                    'image' => get_theme_file_uri('images/header_menus/Menu-02-F-100.jpg'),
                    'link'  => get_theme_file_uri('images/header_menus/Menu-02-F-100.jpg'),
                ],
                'menus' => [
                    [
                        'slug'  => 'hc-menu-basico',
                        'title' => 'Rutina Capilar Coreana',
                        'items' => [
                            ['slug' => 'hc-shampoo', 'title' => '1. Shampoo', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s1-shampoo'],
                            ['slug' => 'hc-acondicionador', 'title' => '2. Acondicionador', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s4-acondicionadores'],
                            ['slug' => 'hc-mascarillas', 'title' => '3. Mascarillas', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s3-mascarillas'],
                            ['slug' => 'hc-tratamientos-leave-in', 'title' => '4. Tratamientos sin enjuague', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s7-esencias-leave-in'],
                        ],
                    ],
                    [
                        'slug'  => 'hc-menu-complementario',
                        'title' => 'Complementos',
                        'items' => [
                            ['slug' => 'hc-exfoliantes', 'title' => '5. Exfoliantes', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s2-exfoliantes'],
                            ['slug' => 'hc-ampollas', 'title' => '6. Ampollas', 'link' => '/product-category/group-hair-care/hc-rutina/hc-ampollas'],
                            ['slug' => 'hc-aceites', 'title' => '7. Aceites', 'link' => '/product-category/group-hair-care/hc-rutina/hc-rutina-s9-aceites'],
                            ['slug' => 'hc-cuero-cabelludo', 'title' => '8. Cuero cabelludo', 'link' => '/product-category/group-hair-care/hc-rutina/hc-necesidad-cuero-cabelludo'],
                        ],
                    ],
                ],
            ],
            [
                'enabled' => true,
                'name'    => 'MAKE UP',
                'slug'    => 'BSC_MENU_NAV_MAKE_UP',
                'cover'   => [
                    'image' => get_theme_file_uri('images/header_menus/Menu-05-F-100.jpg'),
                    'link'  => get_theme_file_uri('images/header_menus/Menu-05-F-100.jpg'),
                ],
                'menus' => [
                    [
                        'slug'  => 'nav-menu-mk-corean-makeup',
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
                        ],
                    ],
                    [
                        'slug'  => 'nav-menu-mk-complements',
                        'title' => 'Complementos',
                        'items' => [
                            ['slug' => 'mk-cejas', 'title' => '12. Cejas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p12-complementos-c1-cejas'],
                            ['slug' => 'mk-primers', 'title' => '13. Primers', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p13-complementos-c2-primers'],
                            ['slug' => 'mk-fijadores', 'title' => '14. Fijadores', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p14-complementos-c3-fijadores'],
                            ['slug' => 'mk-brochas', 'title' => '15. Brochas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p15-complementos-c4-brochas'],
                            ['slug' => 'mk-pestanas', 'title' => '16. Pestañas', 'link' => '/product-category/group-make-up/mk-productos/mk-rutina-p16-complementos-c5-pestanas'],
                        ],
                    ],
                ],
            ],
            [
                'enabled' => false,
                'name'    => 'RUTINA COREANA',
                'slug'    => 'BSC_MENU_NAV_COREAN_RUTINE',
                'cover'   => [
                    'image' => content_url('/uploads/2023/10/Menu-03-F-100.jpg'),
                    'link'  => bsc_get_whatsapp_url('general'),
                ],
                'menus' => [
                    [
                        'slug'  => 'nav-menu-corea-rutine-basic',
                        'title' => 'Rutina básica',
                        'items' => [
                            ['slug' => 'limpiador-acuoso', 'title' => 'Limpiador Acuoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                            ['slug' => 'tonico', 'title' => 'Tónico', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                            ['slug' => 'hidratante', 'title' => 'Hidratante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                            ['slug' => 'protector-solar', 'title' => 'Protector Solar', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema'],
                        ],
                    ],
                    [
                        'slug'  => 'nav-menu-corea-rutine-intermedia',
                        'title' => 'Rutina intermedia',
                        'items' => [
                            ['slug' => 'limpiador-aceitoso', 'title' => 'Limpiador Aceitoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos'],
                            ['slug' => 'limpiador-acuoso', 'title' => 'Limpiador Acuoso', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos'],
                            ['slug' => 'tonico', 'title' => 'Tónico', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos'],
                            ['slug' => 'serum', 'title' => 'Serum', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums'],
                            ['slug' => 'hidratante', 'title' => 'Hidratante', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes'],
                            ['slug' => 'protector-solar', 'title' => 'Protector Solar', 'link' => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita'],
                        ],
                    ],
                    [
                        'slug'  => 'nav-menu-corea-rutine-experta',
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
                        ],
                    ],
                ],
            ],
            [
                'enabled' => false,
                'name'    => 'BLOG',
                'slug'    => 'BSC_MENU_NAV_BLOG',
                'cover'   => [
                    'image' => content_url('/uploads/2023/10/Menu-04-F-100.jpg'),
                    'link'  => home_url('/veja-just-dropped-limited-edition-sneakers-with-mansur-gavriel/'),
                ],
                'menus' => [
                    [
                        'slug'  => 'nav-menu-blog-categorias',
                        'title' => 'Categorías',
                        'items' => [
                            ['slug' => 'entrevistas', 'title' => '1. Entrevistas', 'link' => home_url('/blog/')],
                            ['slug' => 'resenas', 'title' => '2. Reseñas', 'link' => '#'],
                            ['slug' => 'tendencias', 'title' => '3. Tendencias', 'link' => '#'],
                            ['slug' => 'skin-care', 'title' => '4. Skin care', 'link' => '#'],
                            ['slug' => 'hair-care', 'title' => '5. Hair care', 'link' => '#'],
                            ['slug' => 'maquillaje', 'title' => '6. Maquillaje', 'link' => '#'],
                        ],
                    ],
                ],
            ],
            [
                'enabled' => true,
                'name'    => 'CONTACTO',
                'slug'    => 'BSC_MENU_NAV_CONTACT',
                'link'    => '/contact-us/',
                'menus'   => [],
            ],
        ];
    }
}

if (!function_exists('bsc_create_header_menu')) {
    function bsc_create_header_menu(array $config): BSC_MenuNav {
        $menu = new BSC_MenuNav();
        $menu->setName((string) ($config['name'] ?? ''));
        $menu->setSlug((string) ($config['slug'] ?? ''));
        $menu->setMenus([]);

        if (!empty($config['cover']) && is_array($config['cover'])) {
            $menu->setCover($config['cover']);
        }

        if (!empty($config['link'])) {
            $menu->setLink((string) $config['link']);
        }

        foreach (($config['menus'] ?? []) as $section) {
            $menu->appendMenu($section);
        }

        return $menu;
    }
}

if (!function_exists('bsc_build_header_nav')) {
    function bsc_build_header_nav(): BSC_HeaderNav {
        $header_nav = new BSC_HeaderNav();
        $header_nav->setId('BSC_Header_Nav');
        $header_nav->setClass('bsc__header-nav');
        $header_nav->setMenus([]);

        foreach (bsc_get_header_menu_configs() as $config) {
            if (array_key_exists('enabled', $config) && !$config['enabled']) {
                continue;
            }

            $header_nav->addMenu(bsc_create_header_menu($config));
        }

        return $header_nav;
    }
}

if (!function_exists('bsc_get_header_account_links')) {
    function bsc_get_header_account_links(): array {
        return [
            'my_account_url'       => wc_get_page_permalink('myaccount'),
            'orders_url'           => wc_get_account_endpoint_url('orders'),
            'bubble_points_url'    => home_url('/mi-cuenta/bubble-points/'),
            'edit_account_url'     => wc_get_account_endpoint_url('edit-account'),
            'edit_address_url'     => wc_get_account_endpoint_url('edit-address'),
            'logout_url'           => wc_logout_url(home_url('/')),
            'login_url'            => home_url('/login/'),
            'register_url'         => home_url('/register/'),
            'checkout_url'         => wc_get_checkout_url(),
            'mobile_profile_url'   => is_user_logged_in() ? wc_get_account_endpoint_url('orders') : home_url('/login/'),
            'mobile_profile_label' => is_user_logged_in() ? 'Mis pedidos' : 'Ingresar',
        ];
    }
}
