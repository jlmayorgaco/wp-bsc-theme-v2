<?php

defined( 'ABSPATH' ) || exit;

class BSC_Catalog_Legacy_Shortcodes {
	public static function register_hooks(): void {
		add_shortcode( 'bsc_menu', array( __CLASS__, 'render_menu_shortcode' ) );
		add_shortcode( 'bsc_simple_carousel', array( __CLASS__, 'render_simple_carousel_shortcode' ) );
		add_shortcode( 'bsc_tabs_carousel', array( __CLASS__, 'render_tabs_carousel_shortcode' ) );
	}

	public static function render_menu_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'name' => '',
			),
			$atts,
			'bsc_menu'
		);

		$menus = self::menu_shortcode_items();
		$name  = sanitize_key( (string) $atts['name'] );

		if (!isset( $menus[ $name ] )) {
			return '';
		}

		$items = array_filter(
			array_map(
				static function ( array $item ): ?array {
					$url = self::get_product_category_url( (string) ( $item['slug'] ?? '' ) );

					if ($url === '') {
						return null;
					}

					return array(
						'title' => (string) ( $item['title'] ?? '' ),
						'url'   => $url,
					);
				},
				$menus[ $name ]
			)
		);

		if (empty( $items )) {
			return '';
		}

		ob_start();
		?>
		<ul class="bsc__custom-menu">
			<?php foreach ($items as $item) : ?>
				<li>
					<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_simple_carousel_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'skus'  => '',
				'title' => '',
				'max'   => (string) get_option( 'bsc_default_max_products_slider', 5 ),
			),
			$atts,
			'bsc_simple_carousel'
		);

		$skus = self::parse_skus( (string) $atts['skus'] );

		if (empty( $skus )) {
			return '';
		}

		ob_start();
		?>
		<div class="bsc bsc__section bsc__section--product-slider-fixed">
            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal slider renderer returns component markup escaped at source. ?>
			<?php echo self::render_products_slider( $skus, (string) $atts['title'], 'legacy-simple', (int) $atts['max'] ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_tabs_carousel_shortcode( $atts ): string {
		$categories = self::tabs_categories();
		$defaults   = array(
			'title' => '',
			'max'   => (string) get_option( 'bsc_default_max_products_slider', 5 ),
		);

		foreach (array_keys( $categories ) as $key) {
			$defaults[ $key . '_skus' ] = '';
		}

		$atts   = shortcode_atts( $defaults, $atts, 'bsc_tabs_carousel' );
		$panels = array();

		foreach ($categories as $key => $label) {
			$skus = self::parse_skus( (string) ( $atts[ $key . '_skus' ] ?? '' ) );

			if (!empty( $skus )) {
				$panels[ $key ] = array(
					'label' => $label,
					'skus'  => $skus,
				);
			}
		}

		if (empty( $panels )) {
			return '';
		}

		self::enqueue_tabs_script();

		$first_key = array_key_first( $panels );

		ob_start();
		?>
		<div class="bsc bsc__section bsc__section--product-slider-tabs">
			<?php if ( (string) $atts['title'] !== '') : ?>
				<h2 class="bsc__slider-title"><?php echo esc_html( (string) $atts['title'] ); ?></h2>
			<?php endif; ?>

			<div class="bsc__tabs">
				<div class="tabs__header">
					<?php foreach ($panels as $key => $panel) : ?>
						<?php $tab_name = str_replace( '_', '-', $key ); ?>
						<button type="button" class="tab__header <?php echo esc_attr( $key === $first_key ? 'active' : '' ); ?>" data-tab-name="<?php echo esc_attr( $tab_name ); ?>">
							<span class="tab__icon tab__icon--accent" aria-hidden="true"><?php echo esc_html( self::tab_icon_label( $key ) ); ?></span>
							<span class="tab__title"><?php echo esc_html( $panel['label'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="tabs__content">
					<?php foreach ($panels as $key => $panel) : ?>
						<?php $tab_name = str_replace( '_', '-', $key ); ?>
						<div class="tab__content <?php echo esc_attr( $key === $first_key ? 'active' : '' ); ?>" data-tab-name="<?php echo esc_attr( $tab_name ); ?>">
                            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Internal slider renderer returns component markup escaped at source. ?>
							<?php echo self::render_products_slider( $panel['skus'], '', $key, (int) $atts['max'] ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_products_slider( array $skus, string $label = '', string $slug = '', int $max = 5 ): string {
		self::load_slider_dependencies();

		if (!class_exists( 'BSC_Products_Sliders' )) {
			return '';
		}

		$slider = new BSC_Products_Sliders();
		$slider->setSkus( $skus );
		$slider->setLabel( $label );
		$slider->setSlug( $slug );
		$slider->setMax( max( 1, $max ) );

		ob_start();
		$slider->render();
		return (string) ob_get_clean();
	}

	private static function load_slider_dependencies(): void {
		if (!class_exists( 'BSC_Products_Card' )) {
			require_once get_template_directory() . '/components/products/card.php';
		}

		if (!class_exists( 'BSC_Products_Sliders' )) {
			require_once get_template_directory() . '/components/products/slider.php';
		}
	}

	private static function enqueue_tabs_script(): void {
		wp_enqueue_script(
			'bsc-2-0-tabs',
			get_template_directory_uri() . '/js/tabs.js',
			array( 'jquery' ),
			defined( 'BSC_THEME_VERSION' ) ? BSC_THEME_VERSION : null,
			true
		);
	}

	private static function parse_skus( string $value ): array {
		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $value ) ),
				static fn( string $sku ): bool => $sku !== ''
			)
		);
	}

	private static function get_product_category_url( string $slug ): string {
		if ($slug === '' || $slug === '#') {
			return '';
		}

		$category = get_term_by( 'slug', sanitize_title( $slug ), 'product_cat' );

		if (!$category || is_wp_error( $category )) {
			return '';
		}

		$url = get_term_link( $category, 'product_cat' );

		return is_wp_error( $url ) ? '' : (string) $url;
	}

	private static function tab_icon_label( string $key ): string {
		$labels = array(
			'piel_seca'   => 'PS',
			'piel_normal' => 'PN',
			'piel_mixta'  => 'PM',
			'piel_grasa'  => 'PG',
			'hair_care'   => 'HC',
			'maquillaje'  => 'MK',
		);

		return $labels[ $key ] ?? '';
	}

	private static function tabs_categories(): array {
		return array(
			'piel_seca'   => 'Piel Seca',
			'piel_normal' => 'Piel Normal',
			'piel_mixta'  => 'Piel Mixta',
			'piel_grasa'  => 'Piel Grasa',
			'hair_care'   => 'Hair Care',
			'maquillaje'  => 'Maquillaje Coreano',
		);
	}

	private static function menu_shortcode_items(): array {
		return array(
			'sk-rutina-coreana'      => array(
				array(
					'title' => '1. Limpiadores Aceitosos',
					'slug'  => 'sk-rutina-s1-limpiadores-aceitosos',
				),
				array(
					'title' => '2. Limpiadores Acuosos',
					'slug'  => 'sk-rutina-s2-limpiadores-acuosos',
				),
				array(
					'title' => '3. Exfoliantes',
					'slug'  => 'sk-rutina-s3-exfoliantes',
				),
				array(
					'title' => '4. Tónicos',
					'slug'  => 'sk-rutina-s4-tonicos',
				),
				array(
					'title' => '5. Mascarillas 1',
					'slug'  => 'sk-rutina-s5-mascarillas-1',
				),
				array(
					'title' => '5. Mascarillas 2',
					'slug'  => 'sk-rutina-s5-mascarillas-2',
				),
				array(
					'title' => '6. Esencias',
					'slug'  => 'sk-rutina-s6-esencias',
				),
				array(
					'title' => '7. Serums',
					'slug'  => 'sk-rutina-s7-serums',
				),
				array(
					'title' => '8. Contorno de Ojos',
					'slug'  => 'sk-rutina-s8-contorno-de-ojos',
				),
				array(
					'title' => '9. Hidratantes',
					'slug'  => 'sk-rutina-s9-hidratantes',
				),
				array(
					'title' => '10. Protectores Solares',
					'slug'  => 'sk-rutina-s10-protectores-solares-crema',
				),
				array(
					'title' => '10. Protectores Solares',
					'slug'  => 'sk-rutina-s10-protectores-solares-barrita',
				),
			),
			'sk-complementos'        => array(
				array(
					'title' => '11. Aceites Faciales',
					'slug'  => 'sk-rutina-s11-complemento-c1-aceites-faciales',
				),
				array(
					'title' => '12. Spot',
					'slug'  => 'sk-rutina-s12-complemento-c2-spot',
				),
				array(
					'title' => '12. Patches',
					'slug'  => 'sk-rutina-s12-complemento-c3-patches',
				),
				array(
					'title' => '13. Mist y Brumas',
					'slug'  => 'sk-rutina-s13-complemento-c4-mist-y-brumas',
				),
				array(
					'title' => '14. Sticks',
					'slug'  => 'sk-rutina-s14-complemento-c5-sticks',
				),
				array(
					'title' => '15. Labios',
					'slug'  => 'sk-rutina-s15-complemento-c6-labios',
				),
				array(
					'title' => '16. Inner Beauty',
					'slug'  => 'sk-rutina-s16-complemento-c7-inner-beauty',
				),
				array(
					'title' => '17. Accesorios',
					'slug'  => 'sk-rutina-s17-complemento-c8-accesorios',
				),
				array(
					'title' => '18. Minis',
					'slug'  => 'sk-rutina-s18-complemento-c9-minis',
				),
			),
			'sk-tipos-piel'          => array(
				array(
					'title' => 'Piel Seca',
					'slug'  => 'sk-tipo-piel-seca',
				),
				array(
					'title' => 'Piel Normal',
					'slug'  => 'sk-tipo-piel-normal',
				),
				array(
					'title' => 'Piel Mixta',
					'slug'  => 'sk-tipo-piel-mixta',
				),
				array(
					'title' => 'Piel Grasa',
					'slug'  => 'sk-tipo-piel-grasa',
				),
			),
			'hc-rutina-coreana'      => array(
				array(
					'title' => '1. Shampoo',
					'slug'  => 'hc-rutina-s1-shampoo',
				),
				array(
					'title' => '2. Exfoliantes',
					'slug'  => 'hc-rutina-s2-exfoliantes',
				),
				array(
					'title' => '3. Mascarillas',
					'slug'  => 'hc-rutina-s3-mascarillas',
				),
				array(
					'title' => '4. Acondicionadores',
					'slug'  => 'hc-rutina-s4-acondicionadores',
				),
				array(
					'title' => '5. Tónicos',
					'slug'  => 'hc-rutina-s5-tonicos',
				),
				array(
					'title' => '6. Serums',
					'slug'  => 'hc-rutina-s6-serums',
				),
				array(
					'title' => '7. Esencias Leave-in',
					'slug'  => 'hc-rutina-s7-esencias-leave-in',
				),
				array(
					'title' => '8. Sprays',
					'slug'  => 'hc-rutina-s8-sprays',
				),
				array(
					'title' => '9. Aceites',
					'slug'  => 'hc-rutina-s9-aceites',
				),
				array(
					'title' => '10. Protectores',
					'slug'  => 'hc-rutina-s10-protectores',
				),
			),
			'hc-rutina-complementos' => array(
				array(
					'title' => '11. Pestañas',
					'slug'  => 'hc-rutina-s11-complementos-c1-pestanas',
				),
				array(
					'title' => '12. Cepillos',
					'slug'  => 'hc-rutina-s12-complementos-c2-cepillos',
				),
				array(
					'title' => '13. Cushions',
					'slug'  => 'hc-rutina-s13-complementos-c3-cushions',
				),
				array(
					'title' => '14. Minis',
					'slug'  => 'hc-rutina-s14-complementos-c4-minis',
				),
				array(
					'title' => '15. Accesorios',
					'slug'  => 'hc-rutina-s15-complementos-c5-accesorios',
				),
			),
			'mk-maquillaje'          => array(
				array(
					'title' => '1. BB Creams y Bases',
					'slug'  => 'mk-rutina-p1-bb-creams-y-bases',
				),
				array(
					'title' => '2. Cushions y Refills',
					'slug'  => 'mk-rutina-p2-cushions-y-refills',
				),
				array(
					'title' => '3. Sombras y Paletas',
					'slug'  => 'mk-rutina-p3-sombras-y-paletas',
				),
				array(
					'title' => '4. Delineadores',
					'slug'  => 'mk-rutina-p4-delineadores',
				),
				array(
					'title' => '5. Pestañinas',
					'slug'  => 'mk-rutina-p5-pestaninas',
				),
				array(
					'title' => '6. Rubores',
					'slug'  => 'mk-rutina-p6-rubores',
				),
				array(
					'title' => '7. Iluminadores',
					'slug'  => 'mk-rutina-p7-iluminadores',
				),
				array(
					'title' => '8. Correctores',
					'slug'  => 'mk-rutina-p8-correctores',
				),
				array(
					'title' => '9. Tintas',
					'slug'  => 'mk-rutina-p9-tintas',
				),
				array(
					'title' => '10. Labiales',
					'slug'  => 'mk-rutina-p10-labiales',
				),
				array(
					'title' => '11. Polvos',
					'slug'  => 'mk-rutina-p11-polvos',
				),
			),
			'mk-complementos'        => array(
				array(
					'title' => '12. Cejas',
					'slug'  => 'mk-rutina-p12-complementos-c1-cejas',
				),
				array(
					'title' => '13. Primers',
					'slug'  => 'mk-rutina-p13-complementos-c2-primers',
				),
				array(
					'title' => '14. Fijadores',
					'slug'  => 'mk-rutina-p14-complementos-c3-fijadores',
				),
				array(
					'title' => '15. Brochas',
					'slug'  => 'mk-rutina-p15-complementos-c4-brochas',
				),
				array(
					'title' => '16. Pestañas',
					'slug'  => 'mk-rutina-p16-complementos-c5-pestanas',
				),
			),
			'bsc-rutina-basica'      => array(
				array(
					'title' => 'Limpiador Acuoso',
					'slug'  => 'sk-rutina-s2-limpiadores-acuosos',
				),
				array(
					'title' => 'Tónico',
					'slug'  => 'sk-rutina-s4-tonicos',
				),
				array(
					'title' => 'Hidratante',
					'slug'  => 'sk-rutina-s9-hidratantes',
				),
				array(
					'title' => 'Protector Solar',
					'slug'  => 'sk-rutina-s10-protectores-solares-crema',
				),
			),
			'bsc-rutina-intermedia'  => array(
				array(
					'title' => 'Limpiador Aceitoso',
					'slug'  => 'sk-rutina-s1-limpiadores-aceitosos',
				),
				array(
					'title' => 'Limpiador Acuoso',
					'slug'  => 'sk-rutina-s2-limpiadores-acuosos',
				),
				array(
					'title' => 'Tónico',
					'slug'  => 'sk-rutina-s4-tonicos',
				),
				array(
					'title' => 'Serum',
					'slug'  => 'sk-rutina-s7-serums',
				),
				array(
					'title' => 'Hidratante',
					'slug'  => 'sk-rutina-s9-hidratantes',
				),
				array(
					'title' => 'Protector Solar',
					'slug'  => 'sk-rutina-s10-protectores-solares-barrita',
				),
			),
			'bsc-rutina-avanzada'    => array(
				array(
					'title' => 'Limpiador Aceitoso',
					'slug'  => 'sk-rutina-s1-limpiadores-aceitosos',
				),
				array(
					'title' => 'Limpiador Acuoso',
					'slug'  => 'sk-rutina-s2-limpiadores-acuosos',
				),
				array(
					'title' => 'Exfoliante',
					'slug'  => 'sk-rutina-s3-exfoliantes',
				),
				array(
					'title' => 'Tónico',
					'slug'  => 'sk-rutina-s4-tonicos',
				),
				array(
					'title' => 'Mascarilla',
					'slug'  => 'sk-rutina-s5-mascarillas-1',
				),
				array(
					'title' => 'Esencias',
					'slug'  => 'sk-rutina-s6-esencias',
				),
				array(
					'title' => 'Serum',
					'slug'  => 'sk-rutina-s7-serums',
				),
				array(
					'title' => 'Contorno de Ojos',
					'slug'  => 'sk-rutina-s8-contorno-de-ojos',
				),
				array(
					'title' => 'Hidratante',
					'slug'  => 'sk-rutina-s9-hidratantes',
				),
				array(
					'title' => 'Protector Solar',
					'slug'  => 'sk-rutina-s10-protectores-solares-crema',
				),
			),
		);
	}
}
