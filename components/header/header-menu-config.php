<?php
defined( 'ABSPATH' ) || exit;

if (!function_exists( 'bsc_get_header_menu_configs' )) {
	function bsc_get_header_menu_configs(): array {
		$configs = array(
			array(
				'enabled' => true,
				'name'    => 'SKIN CARE',
				'slug'    => 'BSC_MENU_NAV_SKIN_CARE',
				'cover'   => array(
					'image' => get_theme_file_uri( 'images/header_menus/Menu-01-F-100.jpg' ),
					'link'  => '/product-category/group-skin-care/',
				),
				'menus'   => array(
					array(
						'slug'  => 'bsc-menu-rutina-coreana',
						'title' => 'Rutina coreana',
						'items' => array(
							array(
								'slug'  => 'sk-rutina-s1-limpiadores-aceitosos',
								'title' => '1. Limpiadores Aceitosos',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos',
							),
							array(
								'slug'  => 'sk-rutina-s2-limpiadores-acuosos',
								'title' => '2. Limpiadores Acuosos',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos',
							),
							array(
								'slug'  => 'sk-rutina-s3-exfoliantes',
								'title' => '3. Exfoliantes',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes',
							),
							array(
								'slug'  => 'sk-rutina-s4-tonicos',
								'title' => '4. Tónicos',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos',
							),
							array(
								'slug'  => 'sk-rutina-s5-mascarillas-1',
								'title' => '5. Mascarillas',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1',
							),
							array(
								'slug'  => 'sk-rutina-s6-esencias',
								'title' => '6. Esencias',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias',
							),
							array(
								'slug'  => 'sk-rutina-s7-serums',
								'title' => '7. Serums',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums',
							),
							array(
								'slug'  => 'sk-rutina-s8-contorno-de-ojos',
								'title' => '8. Contorno de Ojos',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s8-contorno-de-ojos',
							),
							array(
								'slug'  => 'sk-rutina-s9-hidratantes',
								'title' => '9. Hidratantes',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes',
							),
							array(
								'slug'  => 'sk-rutina-s10-protectores-solares-crema',
								'title' => '10. Protectores Solares Liquido',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema',
							),
							array(
								'slug'  => 'sk-rutina-s10-protectores-solares-barrita',
								'title' => '10. Protectores Solares Barrita',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita',
							),
						),
					),
					array(
						'slug'  => 'bsc-menu-complementos',
						'title' => 'Complementos',
						'items' => array(
							array(
								'slug'  => 'sk-rutina-s11-complemento-c1-aceites-faciales',
								'title' => '11. Aceites Faciales',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s11-complemento-c1-aceites-faciales',
							),
							array(
								'slug'  => 'sk-rutina-s12-complemento-c2-spot',
								'title' => '12. Spot',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c2-spot',
							),
							array(
								'slug'  => 'sk-rutina-s12-complemento-c3-patches',
								'title' => '12. Patches',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s12-complemento-c3-patches',
							),
							array(
								'slug'  => 'sk-rutina-s13-complemento-c4-mist-y-brumas',
								'title' => '13. Mist y Brumas',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s13-complemento-c4-mist-y-brumas',
							),
							array(
								'slug'  => 'sk-rutina-s14-complemento-c5-sticks',
								'title' => '14. Sticks',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s14-complemento-c5-sticks',
							),
							array(
								'slug'  => 'sk-rutina-s15-complemento-c6-labios',
								'title' => '15. Labios',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s15-complemento-c6-labios',
							),
							array(
								'slug'  => 'sk-rutina-s16-complemento-c7-inner-beauty',
								'title' => '16. Inner Beauty',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s16-complemento-c7-inner-beauty',
							),
							array(
								'slug'  => 'sk-rutina-s17-complemento-c8-accesorios',
								'title' => '17. Accesorios',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s17-complemento-c8-accesorios',
							),
							array(
								'slug'  => 'sk-rutina-s18-complemento-c9-minis',
								'title' => '18. Minis',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s18-complemento-c9-minis',
							),
						),
					),
					array(
						'slug'  => 'bsc-menu-tipos-piel',
						'title' => 'Tipo de piel',
						'items' => array(
							array(
								'slug'  => 'sk-tipo-piel-seca',
								'title' => 'Piel Seca',
								'link'  => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-seca',
							),
							array(
								'slug'  => 'sk-tipo-piel-normal',
								'title' => 'Piel Normal',
								'link'  => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-normal',
							),
							array(
								'slug'  => 'sk-tipo-piel-mixta',
								'title' => 'Piel Mixta',
								'link'  => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-mixta',
							),
							array(
								'slug'  => 'sk-tipo-piel-grasa',
								'title' => 'Piel Grasa',
								'link'  => '/product-category/group-skin-care/sk-tipo-piel/sk-tipo-piel-grasa',
							),
						),
					),
				),
			),
			array(
				'enabled' => true,
				'name'    => 'HAIR CARE',
				'slug'    => 'BSC_MENU_NAV_HAIR_CARE',
				'cover'   => array(
					'image' => get_theme_file_uri( 'images/header_menus/Menu-02-F-100.jpg' ),
					'link'  => '/product-category/group-hair-care/',
				),
				'menus'   => array(
					array(
						'slug'  => 'hc-menu-basico',
						'title' => 'Rutina Capilar Coreana',
						'items' => array(
							array(
								'slug'  => 'hc-shampoo',
								'title' => '1. Shampoo',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s1-shampoo',
							),
							array(
								'slug'  => 'hc-acondicionador',
								'title' => '2. Acondicionador',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s4-acondicionadores',
							),
							array(
								'slug'  => 'hc-mascarillas',
								'title' => '3. Mascarillas',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s3-mascarillas',
							),
							array(
								'slug'  => 'hc-tratamientos-leave-in',
								'title' => '4. Tratamientos sin enjuague',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s7-esencias-leave-in',
							),
						),
					),
					array(
						'slug'  => 'hc-menu-complementario',
						'title' => 'Complementos',
						'items' => array(
							array(
								'slug'  => 'hc-exfoliantes',
								'title' => '5. Exfoliantes',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s2-exfoliantes',
							),
							array(
								'slug'  => 'hc-ampollas',
								'title' => '6. Ampollas',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-ampollas',
							),
							array(
								'slug'  => 'hc-aceites',
								'title' => '7. Aceites',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-rutina-s9-aceites',
							),
							array(
								'slug'  => 'hc-cuero-cabelludo',
								'title' => '8. Cuero cabelludo',
								'link'  => '/product-category/group-hair-care/hc-rutina/hc-necesidad-cuero-cabelludo',
							),
						),
					),
				),
			),
			array(
				'enabled' => true,
				'name'    => 'MAKE UP',
				'slug'    => 'BSC_MENU_NAV_MAKE_UP',
				'cover'   => array(
					'image' => get_theme_file_uri( 'images/header_menus/Menu-05-F-100.jpg' ),
					'link'  => '/product-category/group-make-up/',
				),
				'menus'   => array(
					array(
						'slug'  => 'nav-menu-mk-corean-makeup',
						'title' => 'Maquillaje coreano',
						'items' => array(
							array(
								'slug'  => 'mk-bb-creams-bases',
								'title' => '1. BB Creams y Bases',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p1-bb-creams-y-bases',
							),
							array(
								'slug'  => 'mk-cushions-refills',
								'title' => '2. Cushions y Refills',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p2-cushions-y-refills',
							),
							array(
								'slug'  => 'mk-sombras-paletas',
								'title' => '3. Sombras y Paletas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p3-sombras-y-paletas',
							),
							array(
								'slug'  => 'mk-delineadores',
								'title' => '4. Delineadores',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p4-delineadores',
							),
							array(
								'slug'  => 'mk-pestaninas',
								'title' => '5. Pestañinas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p5-pestaninas',
							),
							array(
								'slug'  => 'mk-rubores',
								'title' => '6. Rubores',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p6-rubores',
							),
							array(
								'slug'  => 'mk-iluminadores',
								'title' => '7. Iluminadores',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p7-iluminadores',
							),
							array(
								'slug'  => 'mk-correctores',
								'title' => '8. Correctores',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p8-correctores',
							),
							array(
								'slug'  => 'mk-tintas',
								'title' => '9. Tintas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p9-tintas',
							),
							array(
								'slug'  => 'mk-labiales',
								'title' => '10. Labiales',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p10-labiales',
							),
							array(
								'slug'  => 'mk-polvos',
								'title' => '11. Polvos',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p11-polvos',
							),
						),
					),
					array(
						'slug'  => 'nav-menu-mk-complements',
						'title' => 'Complementos',
						'items' => array(
							array(
								'slug'  => 'mk-cejas',
								'title' => '12. Cejas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p12-complementos-c1-cejas',
							),
							array(
								'slug'  => 'mk-primers',
								'title' => '13. Primers',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p13-complementos-c2-primers',
							),
							array(
								'slug'  => 'mk-fijadores',
								'title' => '14. Fijadores',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p14-complementos-c3-fijadores',
							),
							array(
								'slug'  => 'mk-brochas',
								'title' => '15. Brochas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p15-complementos-c4-brochas',
							),
							array(
								'slug'  => 'mk-pestanas',
								'title' => '16. Pestañas',
								'link'  => '/product-category/group-make-up/mk-productos/mk-rutina-p16-complementos-c5-pestanas',
							),
						),
					),
				),
			),
			array(
				'enabled' => false,
				'name'    => 'RUTINA COREANA',
				'slug'    => 'BSC_MENU_NAV_COREAN_RUTINE',
				'cover'   => array(
					'image' => content_url( '/uploads/2023/10/Menu-03-F-100.jpg' ),
					'link'  => bsc_get_whatsapp_url( 'general' ),
				),
				'menus'   => array(
					array(
						'slug'  => 'nav-menu-corea-rutine-basic',
						'title' => 'Rutina básica',
						'items' => array(
							array(
								'slug'  => 'limpiador-acuoso',
								'title' => 'Limpiador Acuoso',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos',
							),
							array(
								'slug'  => 'tonico',
								'title' => 'Tónico',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos',
							),
							array(
								'slug'  => 'hidratante',
								'title' => 'Hidratante',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes',
							),
							array(
								'slug'  => 'protector-solar',
								'title' => 'Protector Solar',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema',
							),
						),
					),
					array(
						'slug'  => 'nav-menu-corea-rutine-intermedia',
						'title' => 'Rutina intermedia',
						'items' => array(
							array(
								'slug'  => 'limpiador-aceitoso',
								'title' => 'Limpiador Aceitoso',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos',
							),
							array(
								'slug'  => 'limpiador-acuoso',
								'title' => 'Limpiador Acuoso',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos',
							),
							array(
								'slug'  => 'tonico',
								'title' => 'Tónico',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos',
							),
							array(
								'slug'  => 'serum',
								'title' => 'Serum',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums',
							),
							array(
								'slug'  => 'hidratante',
								'title' => 'Hidratante',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes',
							),
							array(
								'slug'  => 'protector-solar',
								'title' => 'Protector Solar',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-barrita',
							),
						),
					),
					array(
						'slug'  => 'nav-menu-corea-rutine-experta',
						'title' => 'Rutina Experta',
						'items' => array(
							array(
								'slug'  => 'limpiador-aceitoso',
								'title' => 'Limpiador Aceitoso',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s1-limpiadores-aceitosos',
							),
							array(
								'slug'  => 'limpiador-acuoso',
								'title' => 'Limpiador Acuoso',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s2-limpiadores-acuosos',
							),
							array(
								'slug'  => 'exfoliante',
								'title' => 'Exfoliante',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s3-exfoliantes',
							),
							array(
								'slug'  => 'tonico',
								'title' => 'Tónico',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s4-tonicos',
							),
							array(
								'slug'  => 'mascarilla',
								'title' => 'Mascarilla',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s5-mascarillas-1',
							),
							array(
								'slug'  => 'esencias',
								'title' => 'Esencias',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s6-esencias',
							),
							array(
								'slug'  => 'serum',
								'title' => 'Serum',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s7-serums',
							),
							array(
								'slug'  => 'contorno-de-ojos',
								'title' => 'Contorno de Ojos',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s8-contorno-de-ojos',
							),
							array(
								'slug'  => 'hidratante',
								'title' => 'Hidratante',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s9-hidratantes',
							),
							array(
								'slug'  => 'protector-solar',
								'title' => 'Protector Solar',
								'link'  => '/product-category/group-skin-care/sk-rutina/sk-rutina-s10-protectores-solares-crema',
							),
						),
					),
				),
			),
			array(
				'enabled' => false,
				'name'    => 'BLOG',
				'slug'    => 'BSC_MENU_NAV_BLOG',
				'cover'   => array(
					'image' => content_url( '/uploads/2023/10/Menu-04-F-100.jpg' ),
					'link'  => home_url( '/veja-just-dropped-limited-edition-sneakers-with-mansur-gavriel/' ),
				),
				'menus'   => array(
					array(
						'slug'  => 'nav-menu-blog-categorias',
						'title' => 'Categorías',
						'items' => array(
							array(
								'slug'  => 'entrevistas',
								'title' => '1. Entrevistas',
								'link'  => home_url( '/blog/' ),
							),
							array(
								'slug'  => 'resenas',
								'title' => '2. Reseñas',
								'link'  => '#',
							),
							array(
								'slug'  => 'tendencias',
								'title' => '3. Tendencias',
								'link'  => '#',
							),
							array(
								'slug'  => 'skin-care',
								'title' => '4. Skin care',
								'link'  => '#',
							),
							array(
								'slug'  => 'hair-care',
								'title' => '5. Hair care',
								'link'  => '#',
							),
							array(
								'slug'  => 'maquillaje',
								'title' => '6. Maquillaje',
								'link'  => '#',
							),
						),
					),
				),
			),
			array(
				'enabled' => true,
				'name'    => 'CONTACTO',
				'slug'    => 'BSC_MENU_NAV_CONTACT',
				'link'    => '/contact-us/',
				'menus'   => array(),
			),
		);

		return function_exists( 'bsc_apply_header_menu_cover_overrides' )
			? bsc_apply_header_menu_cover_overrides( $configs )
			: $configs;
	}
}

if (!function_exists( 'bsc_create_header_menu' )) {
	function bsc_create_header_menu( array $config ): BSC_MenuNav {
		$menu = new BSC_MenuNav();
		$menu->setName( (string) ( $config['name'] ?? '' ) );
		$menu->setSlug( (string) ( $config['slug'] ?? '' ) );
		$menu->setMenus( array() );

		if (!empty( $config['cover'] ) && is_array( $config['cover'] )) {
			$cover = $config['cover'];

			if (!empty( $cover['link'] )) {
				$cover['link'] = bsc_normalize_header_link( (string) $cover['link'] );
			}

			$menu->setCover( $cover );
		}

		if (!empty( $config['link'] )) {
			$menu->setLink( bsc_normalize_header_link( (string) $config['link'] ) );
		}

		foreach (( $config['menus'] ?? array() ) as $section) {
			if (!empty( $section['items'] ) && is_array( $section['items'] )) {
				$section['items'] = array_map(
					static function ( array $item ): array {
						if (!empty( $item['link'] )) {
							$item['link'] = bsc_normalize_header_link( (string) $item['link'] );
						}

						return $item;
					},
					$section['items']
				);
			}

			$menu->appendMenu( $section );
		}

		return $menu;
	}
}

if (!function_exists( 'bsc_normalize_header_link' )) {
	function bsc_normalize_header_link( string $link ): string {
		$link = trim( $link );

		if ($link === '' || $link === '#') {
			return $link;
		}

		if (preg_match( '#^(https?:)?//#i', $link )) {
			return $link;
		}

		if (preg_match( '#^(mailto:|tel:|javascript:)#i', $link )) {
			return $link;
		}

		if (str_starts_with( $link, '/product-category/' )) {
			$resolved_term_link = bsc_get_header_taxonomy_link_from_path( $link, 'product_cat' );

			if ($resolved_term_link !== '') {
				return $resolved_term_link;
			}
		}

		if (str_starts_with( $link, '/' )) {
			$resolved_page_link = bsc_get_header_page_link_from_path( $link );

			if ($resolved_page_link !== '') {
				return $resolved_page_link;
			}

			if (untrailingslashit( $link ) === '/blog') {
				$posts_page_id = (int) get_option( 'page_for_posts' );

				if ($posts_page_id > 0) {
					$posts_page_link = get_permalink( $posts_page_id );

					if (is_string( $posts_page_link ) && $posts_page_link !== '') {
						return $posts_page_link;
					}
				}
			}

			return home_url( user_trailingslashit( ltrim( $link, '/' ) ) );
		}

		return $link;
	}
}

if (!function_exists( 'bsc_get_header_taxonomy_link_from_path' )) {
	function bsc_get_header_taxonomy_link_from_path( string $path, string $taxonomy ): string {
		$slug = sanitize_title( wp_basename( untrailingslashit( $path ) ) );

		if ($slug === '') {
			return '';
		}

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if (!$term || is_wp_error( $term )) {
			return '';
		}

		$term_link = get_term_link( $term );

		if (is_wp_error( $term_link ) || !is_string( $term_link ) || $term_link === '') {
			return '';
		}

		return $term_link;
	}
}

if (!function_exists( 'bsc_get_header_page_link_from_path' )) {
	function bsc_get_header_page_link_from_path( string $path ): string {
		$normalized_path = trim( (string) wp_parse_url( $path, PHP_URL_PATH ), '/' );

		if ($normalized_path === '') {
			return '';
		}

		$page = get_page_by_path( $normalized_path );

		if (!$page instanceof WP_Post) {
			return '';
		}

		$page_link = get_permalink( $page );

		if (!is_string( $page_link ) || $page_link === '') {
			return '';
		}

		return $page_link;
	}
}

if (!function_exists( 'bsc_get_header_shared_links' )) {
	function bsc_get_header_shared_links(): array {
		$home_url = home_url( '/' );
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';

		if (!is_string( $shop_url ) || $shop_url === '') {
			$shop_url = home_url( '/shop/' );
		}

		return array(
			'home_url' => $home_url,
			'shop_url' => $shop_url,
		);
	}
}

if (!function_exists( 'bsc_build_header_nav' )) {
	function bsc_build_header_nav(): BSC_HeaderNav {
		$header_nav = new BSC_HeaderNav();
		$header_nav->setId( 'BSC_Header_Nav' );
		$header_nav->setClass( 'bsc__header-nav' );
		$header_nav->setMenus( array() );

		foreach (bsc_get_header_menu_configs() as $config) {
			if (array_key_exists( 'enabled', $config ) && !$config['enabled']) {
				continue;
			}

			$header_nav->addMenu( bsc_create_header_menu( $config ) );
		}

		return $header_nav;
	}
}

if (!function_exists( 'bsc_get_header_account_links' )) {
	function bsc_get_header_account_links(): array {
		$shared_links = bsc_get_header_shared_links();

		return array(
			'my_account_url'       => wc_get_page_permalink( 'myaccount' ),
			'orders_url'           => wc_get_account_endpoint_url( 'orders' ),
			'bubble_points_url'    => home_url( '/mi-cuenta/bubble-points/' ),
			'edit_account_url'     => wc_get_account_endpoint_url( 'edit-account' ),
			'edit_address_url'     => wc_get_account_endpoint_url( 'edit-address' ),
			'logout_url'           => wc_logout_url( $shared_links['home_url'] ),
			'login_url'            => home_url( '/login/' ),
			'register_url'         => home_url( '/register/' ),
			'checkout_url'         => wc_get_checkout_url(),
			'mobile_profile_url'   => is_user_logged_in() ? wc_get_account_endpoint_url( 'orders' ) : home_url( '/login/' ),
			'mobile_profile_label' => is_user_logged_in() ? 'Mis pedidos' : 'Ingresar',
		);
	}
}
