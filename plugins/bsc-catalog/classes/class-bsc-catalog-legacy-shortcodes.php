<?php

defined('ABSPATH') || exit;

class BSC_Catalog_Legacy_Shortcodes {
    public static function register_hooks(): void {
        add_shortcode('bsc_menu', [__CLASS__, 'render_menu_shortcode']);
        add_shortcode('bsc_simple_carousel', [__CLASS__, 'render_simple_carousel_shortcode']);
        add_shortcode('bsc_tabs_carousel', [__CLASS__, 'render_tabs_carousel_shortcode']);
    }

    public static function render_menu_shortcode($atts): string {
        $atts = shortcode_atts(
            [
                'name' => '',
            ],
            $atts,
            'bsc_menu'
        );

        $menus = self::menu_shortcode_items();
        $name = sanitize_key((string) $atts['name']);

        if (!isset($menus[$name])) {
            return '';
        }

        $items = array_filter(
            array_map(
                static function (array $item): ?array {
                    $url = self::get_product_category_url((string) ($item['slug'] ?? ''));

                    if ($url === '') {
                        return null;
                    }

                    return [
                        'title' => (string) ($item['title'] ?? ''),
                        'url'   => $url,
                    ];
                },
                $menus[$name]
            )
        );

        if (empty($items)) {
            return '';
        }

        ob_start();
        ?>
        <ul class="bsc__custom-menu">
            <?php foreach ($items as $item) : ?>
                <li>
                    <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_simple_carousel_shortcode($atts): string {
        $atts = shortcode_atts(
            [
                'skus'  => '',
                'title' => '',
                'max'   => (string) get_option('bsc_default_max_products_slider', 5),
            ],
            $atts,
            'bsc_simple_carousel'
        );

        $skus = self::parse_skus((string) $atts['skus']);

        if (empty($skus)) {
            return '';
        }

        ob_start();
        ?>
        <div class="bsc bsc__section bsc__section--product-slider-fixed">
            <?php echo self::render_products_slider($skus, (string) $atts['title'], 'legacy-simple', (int) $atts['max']); ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_tabs_carousel_shortcode($atts): string {
        $categories = self::tabs_categories();
        $defaults = [
            'title' => '',
            'max'   => (string) get_option('bsc_default_max_products_slider', 5),
        ];

        foreach (array_keys($categories) as $key) {
            $defaults[$key . '_skus'] = '';
        }

        $atts = shortcode_atts($defaults, $atts, 'bsc_tabs_carousel');
        $panels = [];

        foreach ($categories as $key => $label) {
            $skus = self::parse_skus((string) ($atts[$key . '_skus'] ?? ''));

            if (!empty($skus)) {
                $panels[$key] = [
                    'label' => $label,
                    'skus'  => $skus,
                ];
            }
        }

        if (empty($panels)) {
            return '';
        }

        self::enqueue_tabs_script();

        $first_key = array_key_first($panels);

        ob_start();
        ?>
        <div class="bsc bsc__section bsc__section--product-slider-tabs">
            <?php if ((string) $atts['title'] !== '') : ?>
                <h2 class="bsc__slider-title"><?php echo esc_html((string) $atts['title']); ?></h2>
            <?php endif; ?>

            <div class="bsc__tabs">
                <div class="tabs__header">
                    <?php foreach ($panels as $key => $panel) : ?>
                        <?php $tab_name = str_replace('_', '-', $key); ?>
                        <button type="button" class="tab__header <?php echo $key === $first_key ? 'active' : ''; ?>" data-tab-name="<?php echo esc_attr($tab_name); ?>">
                            <span class="tab__icon tab__icon--accent" aria-hidden="true"><?php echo esc_html(self::tab_icon_label($key)); ?></span>
                            <span class="tab__title"><?php echo esc_html($panel['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="tabs__content">
                    <?php foreach ($panels as $key => $panel) : ?>
                        <?php $tab_name = str_replace('_', '-', $key); ?>
                        <div class="tab__content <?php echo $key === $first_key ? 'active' : ''; ?>" data-tab-name="<?php echo esc_attr($tab_name); ?>">
                            <?php echo self::render_products_slider($panel['skus'], '', $key, (int) $atts['max']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function render_products_slider(array $skus, string $label = '', string $slug = '', int $max = 5): string {
        self::load_slider_dependencies();

        if (!class_exists('BSC_Products_Sliders')) {
            return '';
        }

        $slider = new BSC_Products_Sliders();
        $slider->setSkus($skus);
        $slider->setLabel($label);
        $slider->setSlug($slug);
        $slider->setMax(max(1, $max));

        ob_start();
        $slider->render();
        return (string) ob_get_clean();
    }

    private static function load_slider_dependencies(): void {
        if (!class_exists('BSC_Products_Card')) {
            require_once get_template_directory() . '/components/products/card.php';
        }

        if (!class_exists('BSC_Products_Sliders')) {
            require_once get_template_directory() . '/components/products/slider.php';
        }
    }

    private static function enqueue_tabs_script(): void {
        wp_enqueue_script(
            'bsc-2-0-tabs',
            get_template_directory_uri() . '/js/tabs.js',
            ['jquery'],
            defined('BSC_THEME_VERSION') ? BSC_THEME_VERSION : null,
            true
        );
    }

    private static function parse_skus(string $value): array {
        return array_values(
            array_filter(
                array_map('trim', explode(',', $value)),
                static fn(string $sku): bool => $sku !== ''
            )
        );
    }

    private static function get_product_category_url(string $slug): string {
        if ($slug === '' || $slug === '#') {
            return '';
        }

        $category = get_term_by('slug', sanitize_title($slug), 'product_cat');

        if (!$category || is_wp_error($category)) {
            return '';
        }

        $url = get_term_link($category, 'product_cat');

        return is_wp_error($url) ? '' : (string) $url;
    }

    private static function tab_icon_label(string $key): string {
        $labels = [
            'piel_seca'   => 'PS',
            'piel_normal' => 'PN',
            'piel_mixta'  => 'PM',
            'piel_grasa'  => 'PG',
            'hair_care'   => 'HC',
            'maquillaje'  => 'MK',
        ];

        return $labels[$key] ?? '';
    }

    private static function tabs_categories(): array {
        return [
            'piel_seca'   => 'Piel Seca',
            'piel_normal' => 'Piel Normal',
            'piel_mixta'  => 'Piel Mixta',
            'piel_grasa'  => 'Piel Grasa',
            'hair_care'   => 'Hair Care',
            'maquillaje'  => 'Maquillaje Coreano',
        ];
    }

    private static function menu_shortcode_items(): array {
        return [
            'sk-rutina-coreana' => [
                ['title' => '1. Limpiadores Aceitosos', 'slug' => 'sk-rutina-s1-limpiadores-aceitosos'],
                ['title' => '2. Limpiadores Acuosos', 'slug' => 'sk-rutina-s2-limpiadores-acuosos'],
                ['title' => '3. Exfoliantes', 'slug' => 'sk-rutina-s3-exfoliantes'],
                ['title' => '4. Tónicos', 'slug' => 'sk-rutina-s4-tonicos'],
                ['title' => '5. Mascarillas 1', 'slug' => 'sk-rutina-s5-mascarillas-1'],
                ['title' => '5. Mascarillas 2', 'slug' => 'sk-rutina-s5-mascarillas-2'],
                ['title' => '6. Esencias', 'slug' => 'sk-rutina-s6-esencias'],
                ['title' => '7. Serums', 'slug' => 'sk-rutina-s7-serums'],
                ['title' => '8. Contorno de Ojos', 'slug' => 'sk-rutina-s8-contorno-de-ojos'],
                ['title' => '9. Hidratantes', 'slug' => 'sk-rutina-s9-hidratantes'],
                ['title' => '10. Protectores Solares', 'slug' => 'sk-rutina-s10-protectores-solares-crema'],
                ['title' => '10. Protectores Solares', 'slug' => 'sk-rutina-s10-protectores-solares-barrita'],
            ],
            'sk-complementos' => [
                ['title' => '11. Aceites Faciales', 'slug' => 'sk-rutina-s11-complemento-c1-aceites-faciales'],
                ['title' => '12. Spot', 'slug' => 'sk-rutina-s12-complemento-c2-spot'],
                ['title' => '12. Patches', 'slug' => 'sk-rutina-s12-complemento-c3-patches'],
                ['title' => '13. Mist y Brumas', 'slug' => 'sk-rutina-s13-complemento-c4-mist-y-brumas'],
                ['title' => '14. Sticks', 'slug' => 'sk-rutina-s14-complemento-c5-sticks'],
                ['title' => '15. Labios', 'slug' => 'sk-rutina-s15-complemento-c6-labios'],
                ['title' => '16. Inner Beauty', 'slug' => 'sk-rutina-s16-complemento-c7-inner-beauty'],
                ['title' => '17. Accesorios', 'slug' => 'sk-rutina-s17-complemento-c8-accesorios'],
                ['title' => '18. Minis', 'slug' => 'sk-rutina-s18-complemento-c9-minis'],
            ],
            'sk-tipos-piel' => [
                ['title' => 'Piel Seca', 'slug' => 'sk-tipo-piel-seca'],
                ['title' => 'Piel Normal', 'slug' => 'sk-tipo-piel-normal'],
                ['title' => 'Piel Mixta', 'slug' => 'sk-tipo-piel-mixta'],
                ['title' => 'Piel Grasa', 'slug' => 'sk-tipo-piel-grasa'],
            ],
            'hc-rutina-coreana' => [
                ['title' => '1. Shampoo', 'slug' => 'hc-rutina-s1-shampoo'],
                ['title' => '2. Exfoliantes', 'slug' => 'hc-rutina-s2-exfoliantes'],
                ['title' => '3. Mascarillas', 'slug' => 'hc-rutina-s3-mascarillas'],
                ['title' => '4. Acondicionadores', 'slug' => 'hc-rutina-s4-acondicionadores'],
                ['title' => '5. Tónicos', 'slug' => 'hc-rutina-s5-tonicos'],
                ['title' => '6. Serums', 'slug' => 'hc-rutina-s6-serums'],
                ['title' => '7. Esencias Leave-in', 'slug' => 'hc-rutina-s7-esencias-leave-in'],
                ['title' => '8. Sprays', 'slug' => 'hc-rutina-s8-sprays'],
                ['title' => '9. Aceites', 'slug' => 'hc-rutina-s9-aceites'],
                ['title' => '10. Protectores', 'slug' => 'hc-rutina-s10-protectores'],
            ],
            'hc-rutina-complementos' => [
                ['title' => '11. Pestañas', 'slug' => 'hc-rutina-s11-complementos-c1-pestanas'],
                ['title' => '12. Cepillos', 'slug' => 'hc-rutina-s12-complementos-c2-cepillos'],
                ['title' => '13. Cushions', 'slug' => 'hc-rutina-s13-complementos-c3-cushions'],
                ['title' => '14. Minis', 'slug' => 'hc-rutina-s14-complementos-c4-minis'],
                ['title' => '15. Accesorios', 'slug' => 'hc-rutina-s15-complementos-c5-accesorios'],
            ],
            'mk-maquillaje' => [
                ['title' => '1. BB Creams y Bases', 'slug' => 'mk-rutina-p1-bb-creams-y-bases'],
                ['title' => '2. Cushions y Refills', 'slug' => 'mk-rutina-p2-cushions-y-refills'],
                ['title' => '3. Sombras y Paletas', 'slug' => 'mk-rutina-p3-sombras-y-paletas'],
                ['title' => '4. Delineadores', 'slug' => 'mk-rutina-p4-delineadores'],
                ['title' => '5. Pestañinas', 'slug' => 'mk-rutina-p5-pestaninas'],
                ['title' => '6. Rubores', 'slug' => 'mk-rutina-p6-rubores'],
                ['title' => '7. Iluminadores', 'slug' => 'mk-rutina-p7-iluminadores'],
                ['title' => '8. Correctores', 'slug' => 'mk-rutina-p8-correctores'],
                ['title' => '9. Tintas', 'slug' => 'mk-rutina-p9-tintas'],
                ['title' => '10. Labiales', 'slug' => 'mk-rutina-p10-labiales'],
                ['title' => '11. Polvos', 'slug' => 'mk-rutina-p11-polvos'],
            ],
            'mk-complementos' => [
                ['title' => '12. Cejas', 'slug' => 'mk-rutina-p12-complementos-c1-cejas'],
                ['title' => '13. Primers', 'slug' => 'mk-rutina-p13-complementos-c2-primers'],
                ['title' => '14. Fijadores', 'slug' => 'mk-rutina-p14-complementos-c3-fijadores'],
                ['title' => '15. Brochas', 'slug' => 'mk-rutina-p15-complementos-c4-brochas'],
                ['title' => '16. Pestañas', 'slug' => 'mk-rutina-p16-complementos-c5-pestanas'],
            ],
            'bsc-rutina-basica' => [
                ['title' => 'Limpiador Acuoso', 'slug' => 'sk-rutina-s2-limpiadores-acuosos'],
                ['title' => 'Tónico', 'slug' => 'sk-rutina-s4-tonicos'],
                ['title' => 'Hidratante', 'slug' => 'sk-rutina-s9-hidratantes'],
                ['title' => 'Protector Solar', 'slug' => 'sk-rutina-s10-protectores-solares-crema'],
            ],
            'bsc-rutina-intermedia' => [
                ['title' => 'Limpiador Aceitoso', 'slug' => 'sk-rutina-s1-limpiadores-aceitosos'],
                ['title' => 'Limpiador Acuoso', 'slug' => 'sk-rutina-s2-limpiadores-acuosos'],
                ['title' => 'Tónico', 'slug' => 'sk-rutina-s4-tonicos'],
                ['title' => 'Serum', 'slug' => 'sk-rutina-s7-serums'],
                ['title' => 'Hidratante', 'slug' => 'sk-rutina-s9-hidratantes'],
                ['title' => 'Protector Solar', 'slug' => 'sk-rutina-s10-protectores-solares-barrita'],
            ],
            'bsc-rutina-avanzada' => [
                ['title' => 'Limpiador Aceitoso', 'slug' => 'sk-rutina-s1-limpiadores-aceitosos'],
                ['title' => 'Limpiador Acuoso', 'slug' => 'sk-rutina-s2-limpiadores-acuosos'],
                ['title' => 'Exfoliante', 'slug' => 'sk-rutina-s3-exfoliantes'],
                ['title' => 'Tónico', 'slug' => 'sk-rutina-s4-tonicos'],
                ['title' => 'Mascarilla', 'slug' => 'sk-rutina-s5-mascarillas-1'],
                ['title' => 'Esencias', 'slug' => 'sk-rutina-s6-esencias'],
                ['title' => 'Serum', 'slug' => 'sk-rutina-s7-serums'],
                ['title' => 'Contorno de Ojos', 'slug' => 'sk-rutina-s8-contorno-de-ojos'],
                ['title' => 'Hidratante', 'slug' => 'sk-rutina-s9-hidratantes'],
                ['title' => 'Protector Solar', 'slug' => 'sk-rutina-s10-protectores-solares-crema'],
            ],
        ];
    }
}
