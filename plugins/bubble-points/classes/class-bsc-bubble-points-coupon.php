<?php
if (!class_exists('BSC_Bubble_Point_Coupon')) {
    class BSC_Bubble_Point_Coupon {

        protected $points;
        protected $value;
        protected $text;
        protected $image;     // path or URL
        protected $alt;
        protected $state;     // 'normal', 'disabled', 'error'
        protected $color_mod; // e.g. 'yellow', 'blue' for CSS modifiers

        /**
         * Constructor
         */
        public function __construct(array $config) {
            $this->points    = $config['points'] ?? 0;
            $this->value     = $config['value'] ?? 0;
            $this->text      = $config['text'] ?? 'en skin care, hair care, maquillaje, inner beauty y dispositivos coreanos en BSC';
            $this->image     = $config['image'] ?? '';
            $this->alt       = $config['alt']   ?? 'Coupon';
            $this->state     = $config['state'] ?? 'normal';
            $this->color_mod = $config['color_mod'] ?? '';
        }

        /**
         * Resolve image path
         */
        protected function img_src(string $path): string {
            if (!$path) {
                return '';
            }
            if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
                return $path;
            }
            // Treat as theme-relative
            return trailingslashit(get_stylesheet_directory_uri()) . ltrim($path, '/');
        }

        /**
         * Generate HTML for the coupon card
         */
        public function get_html(): string {
            $classes = [
                'bsc__coupon-card',
                'is-' . esc_attr($this->state),
            ];
            if ($this->color_mod) {
                $classes[] = 'bsc__coupon-card--' . sanitize_html_class($this->color_mod);
            }
            $class_str = implode(' ', $classes);

            $price      = number_format((int)$this->value);
            $points     = number_format((int)$this->points);
            $text       = esc_html($this->text);
            $img        = esc_url($this->img_src($this->image));
            $alt        = esc_attr($this->alt);
            $check_img  = esc_url($this->img_src('plugins/bubble-points/images/coupon__check.png'));

            // Action area
            if ($this->state === 'normal') {
                $action = '<button class="bsc__coupon-button" type="button">Redimir</button>';
            } elseif ($this->state === 'error') {
                $action = '<div class="bsc__coupon-error-msg">Error al redimir</div>';
            } else {
                $action = '<div class="bsc__coupon-locked">No tienes suficientes puntos</div>';
            }

            return <<<HTML
                <div class="{$class_str} js-bsc-coupon" data-points="{$points}" data-value="{$price}" data-state="{$this->state}">
                <div class="bsc__coupon-card__container">
                    <div class="bsc__coupon-card__col">
                    <div class="row">
                        <div class="price border_bottom_dotted">\${$price}</div>
                        <div class="check">
                        <div class="check__back check__back--{$this->color_mod}"></div>
                        <div class="check__front">
                            <img src="{$check_img}" alt="check">
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <p>{$text}</p>
                    </div>
                    </div>

                    <div class="bsc__coupon-card__col">
                    <div class="card__image">
                        <div class="image__back image__back--{$this->color_mod}"></div>
                        <div class="image__front">
                        <img src="{$img}" alt="{$alt}">
                        </div>
                    </div>
                    </div>
                </div>

                <div class="bsc__coupon-card__label">
                    Redimir x {$points} Bubble Points
                </div>
                </div>
            HTML;
        }

        /**
         * Echo the HTML
         */
        public function render(): void {
            echo $this->get_html();
        }
    }
}
