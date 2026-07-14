<?php
defined( 'ABSPATH' ) || exit;

if (!function_exists( 'bsc_get_header_menu_default_configs' )) {
	function bsc_get_header_menu_default_configs(): array {
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

		return $configs;
	}
}

if (!function_exists( 'bsc_get_header_menu_configs' )) {
	function bsc_get_header_menu_configs(): array {
		$native_configs = function_exists( 'bsc_get_header_menu_native_configs' )
			? bsc_get_header_menu_native_configs()
			: array();

		if (!empty( $native_configs )) {
			return $native_configs;
		}

		$configs = bsc_get_header_menu_default_configs();
		$configs = function_exists( 'bsc_apply_header_menu_content_overrides' )
			? bsc_apply_header_menu_content_overrides( $configs )
			: $configs;

		return function_exists( 'bsc_apply_header_menu_cover_overrides' )
			? bsc_apply_header_menu_cover_overrides( $configs )
			: $configs;
	}
}

if (!defined( 'BSC_HEADER_MENU_CONTENT_OPTION' )) {
	define( 'BSC_HEADER_MENU_CONTENT_OPTION', 'bsc_header_menu_content' );
}

if (!defined( 'BSC_HEADER_MENU_NATIVE_LOCATION' )) {
	define( 'BSC_HEADER_MENU_NATIVE_LOCATION', 'bsc-header-mega' );
}

if (!defined( 'BSC_HEADER_MENU_NATIVE_NAME' )) {
	define( 'BSC_HEADER_MENU_NATIVE_NAME', 'BSC Header Mega Menu' );
}

if (!function_exists( 'bsc_get_header_menu_editable_slugs' )) {
	function bsc_get_header_menu_editable_slugs(): array {
		return array(
			'BSC_MENU_NAV_SKIN_CARE',
			'BSC_MENU_NAV_HAIR_CARE',
			'BSC_MENU_NAV_MAKE_UP',
			'BSC_MENU_NAV_CONTACT',
		);
	}
}

if (!function_exists( 'bsc_header_menu_strip_auto_number' )) {
	function bsc_header_menu_strip_auto_number( string $label ): string {
		return trim( (string) preg_replace( '/^\s*\d+\s*[.)\/-]\s*/', '', $label ) );
	}
}

if (!function_exists( 'bsc_header_menu_item_is_numbered' )) {
	function bsc_header_menu_item_is_numbered( array $item ): bool {
		return isset( $item['title'] ) && preg_match( '/^\s*\d+\s*[.)\/-]\s*/', (string) $item['title'] ) === 1;
	}
}

if (!function_exists( 'bsc_header_menu_section_is_numbered' )) {
	function bsc_header_menu_section_is_numbered( array $section ): bool {
		foreach ((array) ( $section['items'] ?? array() ) as $item) {
			if (is_array( $item ) && bsc_header_menu_item_is_numbered( $item )) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists( 'bsc_header_menu_format_item_title' )) {
	function bsc_header_menu_format_item_title( string $label, int $position, bool $numbered ): string {
		$label = bsc_header_menu_strip_auto_number( $label );

		if ($label === '') {
			return '';
		}

		return $numbered ? sprintf( '%d. %s', $position, $label ) : $label;
	}
}

if (!function_exists( 'bsc_header_menu_configs_to_content_options' )) {
	function bsc_header_menu_configs_to_content_options( array $configs ): array {
		$editable_slugs = bsc_get_header_menu_editable_slugs();
		$options        = array();

		foreach ($configs as $config) {
			$slug = (string) ( $config['slug'] ?? '' );

			if ($slug === '' || !in_array( $slug, $editable_slugs, true )) {
				continue;
			}

			$sections = array();
			foreach ((array) ( $config['menus'] ?? array() ) as $section) {
				if (!is_array( $section )) {
					continue;
				}

				$items = array();
				foreach ((array) ( $section['items'] ?? array() ) as $item) {
					if (!is_array( $item )) {
						continue;
					}

					$term_id = absint( $item['term_id'] ?? 0 );

					if ($term_id <= 0 && !empty( $item['link'] )) {
						$term_id = bsc_get_header_product_cat_term_id_from_link( (string) $item['link'] );
					}

					$items[] = array(
						'label'   => bsc_header_menu_strip_auto_number( (string) ( $item['title'] ?? '' ) ),
						'term_id' => $term_id,
					);
				}

				$sections[] = array(
					'title'    => sanitize_text_field( (string) ( $section['title'] ?? '' ) ),
					'numbered' => bsc_header_menu_section_is_numbered( $section ),
					'items'    => $items,
				);
			}

			$options[ $slug ] = array(
				'enabled'  => !array_key_exists( 'enabled', $config ) || (bool) $config['enabled'],
				'name'     => sanitize_text_field( (string) ( $config['name'] ?? '' ) ),
				'link'     => sanitize_text_field( (string) ( $config['link'] ?? $config['cover']['link'] ?? '' ) ),
				'sections' => $sections,
			);
		}

		return $options;
	}
}

if (!function_exists( 'bsc_sanitize_header_menu_content_options' )) {
	function bsc_sanitize_header_menu_content_options( $input ): array {
		$defaults = bsc_header_menu_configs_to_content_options( bsc_get_header_menu_default_configs() );
		$input    = is_array( $input ) ? $input : array();
		$clean    = array();

		foreach ($defaults as $slug => $default) {
			$has_submitted_row = isset( $input[ $slug ] ) && is_array( $input[ $slug ] );
			$row               = $has_submitted_row ? $input[ $slug ] : $default;
			$name         = trim( sanitize_text_field( (string) ( $row['name'] ?? '' ) ) );
			$raw_link     = trim( sanitize_text_field( (string) ( $row['link'] ?? '' ) ) );
			$link         = $raw_link !== '' ? esc_url_raw( $raw_link ) : (string) ( $default['link'] ?? '' );
			$sections     = array();
			$raw_sections = isset( $row['sections'] ) && is_array( $row['sections'] )
				? array_values( $row['sections'] )
				: ( $has_submitted_row ? array() : (array) ( $default['sections'] ?? array() ) );

			foreach ($raw_sections as $section) {
				if (!is_array( $section ) || count( $sections ) >= 8) {
					continue;
				}

				$title     = trim( sanitize_text_field( (string) ( $section['title'] ?? '' ) ) );
				$numbered  = !empty( $section['numbered'] );
				$raw_items = isset( $section['items'] ) && is_array( $section['items'] )
					? array_values( $section['items'] )
					: array();
				$items     = array();

				foreach ($raw_items as $item) {
					if (!is_array( $item ) || count( $items ) >= 32) {
						continue;
					}

					$term_id = absint( $item['term_id'] ?? 0 );
					$term    = $term_id > 0 ? get_term( $term_id, 'product_cat' ) : null;

					if (!$term instanceof WP_Term) {
						continue;
					}

					$label = bsc_header_menu_strip_auto_number(
						sanitize_text_field( (string) ( $item['label'] ?? $item['title'] ?? '' ) )
					);

					if ($label === '') {
						$label = (string) $term->name;
					}

					$items[] = array(
						'label'   => $label,
						'term_id' => $term_id,
					);
				}

				if ($title === '' && empty( $items )) {
					continue;
				}

				$sections[] = array(
					'title'    => $title !== '' ? $title : sprintf( 'Columna %d', count( $sections ) + 1 ),
					'numbered' => $numbered,
					'items'    => $items,
				);
			}

			if (empty( $sections ) && !$has_submitted_row) {
				$sections = (array) ( $default['sections'] ?? array() );
			}

			$clean[ $slug ] = array(
				'enabled'  => !empty( $row['enabled'] ),
				'name'     => $name !== '' ? $name : (string) ( $default['name'] ?? $slug ),
				'link'     => $link,
				'sections' => $sections,
			);
		}

		return $clean;
	}
}

if (!function_exists( 'bsc_get_header_menu_content_options' )) {
	function bsc_get_header_menu_content_options(): array {
		$saved = get_option( BSC_HEADER_MENU_CONTENT_OPTION, null );

		if (!is_array( $saved ) || empty( $saved )) {
			return bsc_header_menu_configs_to_content_options( bsc_get_header_menu_default_configs() );
		}

		return bsc_sanitize_header_menu_content_options( $saved );
	}
}

if (!function_exists( 'bsc_header_menu_content_has_saved_options' )) {
	function bsc_header_menu_content_has_saved_options(): bool {
		$saved = get_option( BSC_HEADER_MENU_CONTENT_OPTION, null );

		return is_array( $saved ) && !empty( $saved );
	}
}

if (!function_exists( 'bsc_get_header_product_cat_term_id_from_link' )) {
	function bsc_get_header_product_cat_term_id_from_link( string $link ): int {
		$path = (string) wp_parse_url( $link, PHP_URL_PATH );

		if ($path === '') {
			$path = $link;
		}

		if (!str_contains( $path, '/product-category/' )) {
			return 0;
		}

		$slug = sanitize_title( wp_basename( untrailingslashit( $path ) ) );

		if ($slug === '') {
			return 0;
		}

		$term = get_term_by( 'slug', $slug, 'product_cat' );

		return $term instanceof WP_Term ? (int) $term->term_id : 0;
	}
}

if (!function_exists( 'bsc_get_header_product_cat_link_by_term_id' )) {
	function bsc_get_header_product_cat_link_by_term_id( int $term_id ): string {
		static $cache = array();

		if ($term_id <= 0) {
			return '';
		}

		if (array_key_exists( $term_id, $cache )) {
			return $cache[ $term_id ];
		}

		$term = get_term( $term_id, 'product_cat' );

		if (!$term instanceof WP_Term) {
			$cache[ $term_id ] = '';
			return '';
		}

		$link = get_term_link( $term, 'product_cat' );

		$cache[ $term_id ] = is_wp_error( $link ) || !is_string( $link ) ? '' : $link;

		return $cache[ $term_id ];
	}
}

if (!function_exists( 'bsc_build_header_menu_sections_from_content_option' )) {
	function bsc_build_header_menu_sections_from_content_option( string $menu_slug, array $sections ): array {
		$built_sections = array();

		foreach (array_values( $sections ) as $section_index => $section) {
			if (!is_array( $section )) {
				continue;
			}

			$section_title = sanitize_text_field( (string) ( $section['title'] ?? '' ) );
			$numbered     = !empty( $section['numbered'] );
			$items        = array();

			foreach (array_values( (array) ( $section['items'] ?? array() ) ) as $item) {
				if (!is_array( $item )) {
					continue;
				}

				$term_id = absint( $item['term_id'] ?? 0 );
				$link    = bsc_get_header_product_cat_link_by_term_id( $term_id );

				if ($link === '') {
					continue;
				}

				$term  = get_term( $term_id, 'product_cat' );
				$label = bsc_header_menu_strip_auto_number( (string) ( $item['label'] ?? '' ) );

				if ($label === '' && $term instanceof WP_Term) {
					$label = (string) $term->name;
				}

				$title = bsc_header_menu_format_item_title( $label, count( $items ) + 1, $numbered );

				if ($title === '') {
					continue;
				}

				$items[] = array(
					'slug'    => $term instanceof WP_Term ? (string) $term->slug : sanitize_title( $label ),
					'title'   => $title,
					'link'    => $link,
					'term_id' => $term_id,
				);
			}

			if ($section_title === '' && empty( $items )) {
				continue;
			}

			$built_sections[] = array(
				'slug'  => sanitize_title( $menu_slug . '-' . ( $section_title !== '' ? $section_title : 'section' ) . '-' . ( $section_index + 1 ) ),
				'title' => $section_title !== '' ? $section_title : sprintf( 'Columna %d', $section_index + 1 ),
				'items' => $items,
			);
		}

		return $built_sections;
	}
}

if (!function_exists( 'bsc_apply_header_menu_content_overrides' )) {
	function bsc_apply_header_menu_content_overrides( array $configs ): array {
		if (!bsc_header_menu_content_has_saved_options()) {
			return $configs;
		}

		$options = bsc_get_header_menu_content_options();

		foreach ($configs as &$config) {
			$slug = (string) ( $config['slug'] ?? '' );

			if ($slug === '' || empty( $options[ $slug ] )) {
				continue;
			}

			$saved             = $options[ $slug ];
			$config['enabled'] = !empty( $saved['enabled'] );
			$config['name']    = (string) ( $saved['name'] ?? $config['name'] ?? '' );
			if (!empty( $saved['link'] )) {
				if (!empty( $config['cover'] ) && is_array( $config['cover'] )) {
					$config['cover']['link'] = (string) $saved['link'];
				} else {
					$config['link'] = (string) $saved['link'];
				}
			}
			$config['menus']   = bsc_build_header_menu_sections_from_content_option(
				$slug,
				(array) ( $saved['sections'] ?? array() )
			);
		}
		unset( $config );

		return $configs;
	}
}

if (!function_exists( 'bsc_get_header_menu_product_category_label' )) {
	function bsc_get_header_menu_product_category_label( WP_Term $term ): string {
		static $label_cache = array();

		$term_id = (int) $term->term_id;

		if (array_key_exists( $term_id, $label_cache )) {
			return $label_cache[ $term_id ];
		}

		$labels    = array();
		$ancestors = array_reverse( get_ancestors( $term_id, 'product_cat' ) );

		foreach ($ancestors as $ancestor_id) {
			$ancestor = get_term( (int) $ancestor_id, 'product_cat' );

			if ($ancestor instanceof WP_Term) {
				$labels[] = (string) $ancestor->name;
			}
		}

		$labels[] = (string) $term->name;

		$label_cache[ $term_id ] = implode( ' / ', $labels );

		return $label_cache[ $term_id ];
	}
}

if (!function_exists( 'bsc_get_header_menu_product_category_choice' )) {
	function bsc_get_header_menu_product_category_choice( int $term_id ): array {
		static $choice_cache = array();

		if ($term_id <= 0) {
			return array();
		}

		if (array_key_exists( $term_id, $choice_cache )) {
			return $choice_cache[ $term_id ];
		}

		$term = get_term( $term_id, 'product_cat' );

		if (!$term instanceof WP_Term) {
			$choice_cache[ $term_id ] = array();
			return $choice_cache[ $term_id ];
		}

		$label = bsc_get_header_menu_product_category_label( $term );
		$slug  = (string) $term->slug;

		$choice_cache[ $term_id ] = array(
			'id'     => (int) $term->term_id,
			'name'   => (string) $term->name,
			'slug'   => $slug,
			'parent' => (int) $term->parent,
			'label'  => $label,
			'search' => strtolower( remove_accents( $label . ' ' . $slug ) ),
		);

		return $choice_cache[ $term_id ];
	}
}

if (!function_exists( 'bsc_search_header_menu_product_category_choices' )) {
	function bsc_search_header_menu_product_category_choices( string $query, int $selected_term_id = 0, int $limit = 30 ): array {
		$query   = trim( sanitize_text_field( $query ) );
		$limit   = max( 1, min( 50, $limit ) );
		$choices = array();

		if ($selected_term_id > 0) {
			$selected_choice = bsc_get_header_menu_product_category_choice( $selected_term_id );

			if (!empty( $selected_choice )) {
				$choices[ (int) $selected_choice['id'] ] = $selected_choice;
			}
		}

		if ($query !== '') {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'number'     => $limit,
					'orderby'    => 'name',
					'order'      => 'ASC',
					'search'     => $query,
				)
			);

			if (is_array( $terms )) {
				foreach ($terms as $term) {
					if (!$term instanceof WP_Term) {
						continue;
					}

					$choice = bsc_get_header_menu_product_category_choice( (int) $term->term_id );

					if (!empty( $choice )) {
						$choices[ (int) $choice['id'] ] = $choice;
					}
				}
			}

			$slug = sanitize_title( $query );
			if ($slug !== '') {
				$slug_term = get_term_by( 'slug', $slug, 'product_cat' );

				if ($slug_term instanceof WP_Term) {
					$choice = bsc_get_header_menu_product_category_choice( (int) $slug_term->term_id );

					if (!empty( $choice )) {
						$choices[ (int) $choice['id'] ] = $choice;
					}
				}

				global $wpdb;

				$slug_term_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT t.term_id
						FROM {$wpdb->terms} t
						INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
						WHERE tt.taxonomy = %s
							AND t.slug LIKE %s
						ORDER BY t.name ASC
						LIMIT %d",
						'product_cat',
						'%' . $wpdb->esc_like( $slug ) . '%',
						$limit
					)
				);

				foreach ((array) $slug_term_ids as $slug_term_id) {
					$choice = bsc_get_header_menu_product_category_choice( (int) $slug_term_id );

					if (!empty( $choice )) {
						$choices[ (int) $choice['id'] ] = $choice;
					}
				}
			}
		}

		uasort(
			$choices,
			static function ( array $a, array $b ) use ( $selected_term_id ): int {
				$a_id = (int) ( $a['id'] ?? 0 );
				$b_id = (int) ( $b['id'] ?? 0 );

				if ($selected_term_id > 0 && $a_id === $selected_term_id && $b_id !== $selected_term_id) {
					return -1;
				}

				if ($selected_term_id > 0 && $b_id === $selected_term_id && $a_id !== $selected_term_id) {
					return 1;
				}

				return strnatcasecmp( (string) ( $a['label'] ?? '' ), (string) ( $b['label'] ?? '' ) );
			}
		);

		return array_values( $choices );
	}
}

if (!function_exists( 'bsc_get_header_menu_product_category_choices' )) {
	function bsc_get_header_menu_product_category_choices(): array {
		static $choices = null;

		if (is_array( $choices )) {
			return $choices;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if (!is_array( $terms )) {
			$choices = array();
			return $choices;
		}

		$choices = array_filter(
			array_map(
				static fn( WP_Term $term ): array => bsc_get_header_menu_product_category_choice( (int) $term->term_id ),
				$terms
			)
		);

		usort(
			$choices,
			static fn( array $a, array $b ): int => strnatcasecmp( (string) $a['label'], (string) $b['label'] )
		);

		return $choices;
	}
}

if (!function_exists( 'bsc_get_header_menu_native_menu_object' )) {
	function bsc_get_header_menu_native_menu_object() {
		$locations = get_nav_menu_locations();
		$menu_id   = absint( $locations[ BSC_HEADER_MENU_NATIVE_LOCATION ] ?? 0 );

		if ($menu_id > 0) {
			$menu = wp_get_nav_menu_object( $menu_id );

			if ($menu instanceof WP_Term) {
				return $menu;
			}
		}

		$menu = wp_get_nav_menu_object( BSC_HEADER_MENU_NATIVE_NAME );

		return $menu instanceof WP_Term ? $menu : null;
	}
}

if (!function_exists( 'bsc_get_header_menu_native_meta' )) {
	function bsc_get_header_menu_native_meta( int $item_id, string $key, string $default = '' ): string {
		$value = get_post_meta( $item_id, $key, true );

		return is_scalar( $value ) && (string) $value !== '' ? (string) $value : $default;
	}
}

if (!function_exists( 'bsc_get_header_menu_default_cover_image' )) {
	function bsc_get_header_menu_default_cover_image( string $slug ): string {
		if (!function_exists( 'bsc_get_header_menu_cover_definitions' )) {
			return '';
		}

		$definitions = bsc_get_header_menu_cover_definitions();

		return (string) ( $definitions[ $slug ]['default_image'] ?? '' );
	}
}

if (!function_exists( 'bsc_get_header_menu_default_cover_link' )) {
	function bsc_get_header_menu_default_cover_link( string $slug ): string {
		if (!function_exists( 'bsc_get_header_menu_cover_definitions' )) {
			return '';
		}

		$definitions = bsc_get_header_menu_cover_definitions();

		return (string) ( $definitions[ $slug ]['default_link'] ?? '' );
	}
}

if (!function_exists( 'bsc_get_header_menu_native_cover_image_url' )) {
	function bsc_get_header_menu_native_cover_image_url( int $item_id, string $slug ): string {
		$image_id = absint( get_post_meta( $item_id, '_bsc_header_menu_cover_image_id', true ) );

		if ($image_id > 0) {
			$image_url = wp_get_attachment_image_url( $image_id, 'full' );

			if (is_string( $image_url ) && $image_url !== '') {
				return $image_url;
			}
		}

		return bsc_get_header_menu_default_cover_image( $slug );
	}
}

if (!function_exists( 'bsc_get_header_menu_native_configs' )) {
	function bsc_get_header_menu_native_configs( bool $include_disabled = false ): array {
		$menu = bsc_get_header_menu_native_menu_object();

		if (!$menu instanceof WP_Term) {
			return array();
		}

		$items = wp_get_nav_menu_items(
			$menu->term_id,
			array(
				'post_status' => 'publish',
			)
		);

		if (!is_array( $items ) || empty( $items )) {
			return array();
		}

		$children = array();
		foreach ($items as $item) {
			$parent_id = absint( $item->menu_item_parent ?? 0 );

			if (!isset( $children[ $parent_id ] )) {
				$children[ $parent_id ] = array();
			}

			$children[ $parent_id ][] = $item;
		}

		$configs = array();
		foreach ((array) ( $children[0] ?? array() ) as $top_item) {
			$top_item_id = absint( $top_item->ID ?? 0 );

			if ($top_item_id <= 0) {
				continue;
			}

			$enabled = bsc_get_header_menu_native_meta( $top_item_id, '_bsc_header_menu_enabled', '1' ) !== '0';

			if (!$enabled && !$include_disabled) {
				continue;
			}

			$slug     = bsc_get_header_menu_native_meta( $top_item_id, '_bsc_header_menu_slug', 'BSC_MENU_NAV_' . $top_item_id );
			$sections = array();

			foreach ((array) ( $children[ $top_item_id ] ?? array() ) as $section_index => $section_item) {
				$section_item_id = absint( $section_item->ID ?? 0 );

				if ($section_item_id <= 0) {
					continue;
				}

				$numbered = bsc_get_header_menu_native_meta( $section_item_id, '_bsc_header_menu_column_numbered', '0' ) === '1';
				$items    = array();

				foreach ((array) ( $children[ $section_item_id ] ?? array() ) as $menu_item) {
					$menu_item_id = absint( $menu_item->ID ?? 0 );

					if ($menu_item_id <= 0) {
						continue;
					}

					$term_id = 'product_cat' === (string) ( $menu_item->object ?? '' )
						? absint( $menu_item->object_id ?? 0 )
						: 0;
					$term    = $term_id > 0 ? get_term( $term_id, 'product_cat' ) : null;
					$title   = bsc_header_menu_format_item_title(
						(string) ( $menu_item->title ?? '' ),
						count( $items ) + 1,
						$numbered
					);
					$link    = (string) ( $menu_item->url ?? '' );

					if ($title === '' || $link === '') {
						continue;
					}

					$items[] = array(
						'slug'    => $term instanceof WP_Term ? (string) $term->slug : sanitize_title( $title ),
						'title'   => $title,
						'link'    => $link,
						'term_id' => $term_id,
					);
				}

				$sections[] = array(
					'slug'  => sanitize_title( $slug . '-' . ( $section_item->title ?? 'section' ) . '-' . ( $section_index + 1 ) ),
					'title' => (string) ( $section_item->title ?? '' ),
					'items' => $items,
				);
			}

			$config = array(
				'enabled' => $enabled,
				'name'    => (string) ( $top_item->title ?? '' ),
				'slug'    => $slug,
				'menus'   => $sections,
			);

			if (!empty( $sections )) {
				$cover_link = bsc_get_header_menu_native_meta( $top_item_id, '_bsc_header_menu_cover_link', '' );

				if ($cover_link === '') {
					$cover_link = (string) ( $top_item->url ?? '' );
				}

				if ($cover_link === '' || $cover_link === '#') {
					$cover_link = bsc_get_header_menu_default_cover_link( $slug );
				}

				$cover_image = bsc_get_header_menu_native_cover_image_url( $top_item_id, $slug );

				if ($cover_image !== '') {
					$config['cover'] = array(
						'image' => $cover_image,
						'link'  => $cover_link,
					);
				}
			} else {
				$config['link'] = (string) ( $top_item->url ?? '#' );
			}

			$configs[] = $config;
		}

		return $configs;
	}
}

if (!function_exists( 'bsc_get_header_menu_fallback_configs_for_native_seed' )) {
	function bsc_get_header_menu_fallback_configs_for_native_seed(): array {
		$configs = bsc_get_header_menu_default_configs();
		$configs = bsc_apply_header_menu_content_overrides( $configs );

		return function_exists( 'bsc_apply_header_menu_cover_overrides' )
			? bsc_apply_header_menu_cover_overrides( $configs )
			: $configs;
	}
}

if (!function_exists( 'bsc_save_header_menu_native_from_options' )) {
	function bsc_save_header_menu_native_from_options( array $content_options, array $cover_options ): bool {
		if (!function_exists( 'wp_create_nav_menu' ) || !function_exists( 'wp_update_nav_menu_item' )) {
			return false;
		}

		$existing_menu = bsc_get_header_menu_native_menu_object();
		if ($existing_menu instanceof WP_Term) {
			wp_delete_nav_menu( $existing_menu->term_id );
		}

		$menu_id = wp_create_nav_menu( BSC_HEADER_MENU_NATIVE_NAME );
		if (is_wp_error( $menu_id ) || absint( $menu_id ) <= 0) {
			return false;
		}

		$menu_id   = absint( $menu_id );
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if (!is_array( $locations )) {
			$locations = array();
		}
		$locations[ BSC_HEADER_MENU_NATIVE_LOCATION ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		$cover_definitions = function_exists( 'bsc_get_header_menu_cover_definitions' )
			? bsc_get_header_menu_cover_definitions()
			: array();
		$position          = 1;

		foreach ($content_options as $slug => $row) {
			if (!is_array( $row )) {
				continue;
			}

			$name        = trim( (string) ( $row['name'] ?? '' ) );
			$is_dropdown = !empty( $row['sections'] ) && is_array( $row['sections'] );
			$cover_row   = isset( $cover_options[ $slug ] ) && is_array( $cover_options[ $slug ] )
				? $cover_options[ $slug ]
				: array();
			$top_url     = (string) ( $row['link'] ?? '' );

			if (isset( $cover_definitions[ $slug ] )) {
				$top_url = (string) ( $cover_row['link'] ?? '' );
				if ($top_url === '') {
					$top_url = (string) ( $cover_definitions[ $slug ]['default_link'] ?? '' );
				}
			}

			if ($top_url === '') {
				$top_url = '#';
			}

			if ($name === '') {
				$name = $slug;
			}

			$top_item_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'    => $name,
					'menu-item-url'      => $top_url,
					'menu-item-status'   => 'publish',
					'menu-item-position' => $position++,
				)
			);

			if (is_wp_error( $top_item_id ) || absint( $top_item_id ) <= 0) {
				continue;
			}

			$top_item_id = absint( $top_item_id );
			update_post_meta( $top_item_id, '_bsc_header_menu_kind', 'menu' );
			update_post_meta( $top_item_id, '_bsc_header_menu_slug', $slug );
			update_post_meta( $top_item_id, '_bsc_header_menu_enabled', !empty( $row['enabled'] ) ? '1' : '0' );

			if (isset( $cover_definitions[ $slug ] )) {
				update_post_meta( $top_item_id, '_bsc_header_menu_cover_image_id', absint( $cover_row['image_id'] ?? 0 ) );
				update_post_meta( $top_item_id, '_bsc_header_menu_cover_link', $top_url );
			}

			if (!$is_dropdown) {
				continue;
			}

			foreach (array_values( (array) $row['sections'] ) as $section) {
				if (!is_array( $section )) {
					continue;
				}

				$section_title = trim( (string) ( $section['title'] ?? '' ) );
				if ($section_title === '') {
					$section_title = 'Columna';
				}

				$section_item_id = wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $section_title,
						'menu-item-url'       => '#',
						'menu-item-parent-id' => $top_item_id,
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $position++,
					)
				);

				if (is_wp_error( $section_item_id ) || absint( $section_item_id ) <= 0) {
					continue;
				}

				$section_item_id = absint( $section_item_id );
				update_post_meta( $section_item_id, '_bsc_header_menu_kind', 'column' );
				update_post_meta( $section_item_id, '_bsc_header_menu_column_numbered', !empty( $section['numbered'] ) ? '1' : '0' );

				foreach (array_values( (array) ( $section['items'] ?? array() ) ) as $item) {
					if (!is_array( $item )) {
						continue;
					}

					$term_id = absint( $item['term_id'] ?? 0 );
					$term    = $term_id > 0 ? get_term( $term_id, 'product_cat' ) : null;

					if (!$term instanceof WP_Term) {
						continue;
					}

					$label = bsc_header_menu_strip_auto_number( (string) ( $item['label'] ?? '' ) );
					if ($label === '') {
						$label = (string) $term->name;
					}

					$item_id = wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => $label,
							'menu-item-object'    => 'product_cat',
							'menu-item-object-id' => $term_id,
							'menu-item-type'      => 'taxonomy',
							'menu-item-parent-id' => $section_item_id,
							'menu-item-status'    => 'publish',
							'menu-item-position'  => $position++,
						)
					);

					if (!is_wp_error( $item_id ) && absint( $item_id ) > 0) {
						update_post_meta( absint( $item_id ), '_bsc_header_menu_kind', 'item' );
					}
				}
			}
		}

		return true;
	}
}

if (!function_exists( 'bsc_ensure_header_menu_native_menu' )) {
	function bsc_ensure_header_menu_native_menu(): int {
		$menu = bsc_get_header_menu_native_menu_object();

		if ($menu instanceof WP_Term) {
			return (int) $menu->term_id;
		}

		$content_options = bsc_header_menu_configs_to_content_options(
			bsc_get_header_menu_fallback_configs_for_native_seed()
		);
		$cover_options   = function_exists( 'bsc_get_header_menu_cover_options' )
			? bsc_get_header_menu_cover_options()
			: array();

		bsc_save_header_menu_native_from_options( $content_options, $cover_options );
		$menu = bsc_get_header_menu_native_menu_object();

		return $menu instanceof WP_Term ? (int) $menu->term_id : 0;
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
		static $cache = array();

		$slug = sanitize_title( wp_basename( untrailingslashit( $path ) ) );

		if ($slug === '') {
			return '';
		}

		$cache_key = $taxonomy . ':' . $slug;

		if (array_key_exists( $cache_key, $cache )) {
			return $cache[ $cache_key ];
		}

		$term = get_term_by( 'slug', $slug, $taxonomy );

		if (!$term || is_wp_error( $term )) {
			$cache[ $cache_key ] = '';
			return '';
		}

		$term_link = get_term_link( $term );

		if (is_wp_error( $term_link ) || !is_string( $term_link ) || $term_link === '') {
			$cache[ $cache_key ] = '';
			return '';
		}

		$cache[ $cache_key ] = $term_link;

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
