<?php
if (!class_exists( 'BSC_Bubble_Point_Coupon' )) {
	class BSC_Bubble_Point_Coupon {

		protected $points;
		protected $value;
		protected $text;
		protected $image;     // path or URL (normal state)
		protected $alt;
		protected $state;     // 'normal', 'disabled', 'error'
		protected $color_mod; // e.g. 'yellow', 'blue' for CSS modifiers

		public function __construct( array $config ) {
			$this->points    = $config['points'] ?? 0;
			$this->value     = $config['value'] ?? 0;
			$this->text      = $config['text'] ?? 'en skin care, hair care, maquillaje, inner beauty y dispositivos coreanos en BSC';
			$this->image     = $config['image'] ?? '';
			$this->alt       = $config['alt'] ?? 'Coupon';
			$this->state     = $config['state'] ?? 'normal';
			$this->color_mod = $config['color_mod'] ?? '';
		}

		protected function img_src( string $path ): string {
			if (!$path) {
				return '';
			}
			if (strpos( $path, 'http' ) === 0 || strpos( $path, '//' ) === 0) {
				return $path;
			}
			// theme-relative
			return trailingslashit( get_stylesheet_directory_uri() ) . ltrim( $path, '/' );
		}

		/**
		 * Build the hover variant by inserting '_colored' before the file extension.
		 * Works with .png, .jpg, .jpeg, .webp and keeps query strings intact.
		 * e.g. coupon_c1__figure.png -> coupon_c1__figure_colored.png
		 */
		protected function hover_variant( string $url ): string {
			if (!$url) {
				return '';
			}
			return preg_replace( '/(\.[a-zA-Z0-9]{2,4})(\?.*)?$/', '_colored$1$2', $url );
		}

		public function get_html(): string {
			$state_attr = esc_attr( $this->state );
			$color_mod  = sanitize_html_class( $this->color_mod );
			$classes    = array( 'bsc__coupon-card', 'is-' . $state_attr );
			if ($color_mod) {
				$classes[] = 'bsc__coupon-card--' . $color_mod;
			}
			$class_str = implode( ' ', $classes );

			// RAW numbers for data attributes
			$price_raw  = (int) $this->value;
			$points_raw = (int) $this->points;

			// Formatted for display
			$price_fmt  = number_format( $price_raw );
			$points_fmt = number_format( $points_raw );

			$text       = esc_html( $this->text );
			$img_normal = esc_url( $this->img_src( $this->image ) );
			$img_hover  = esc_url( $this->hover_variant( $img_normal ) );
			$alt        = esc_attr( $this->alt );
			$check_img  = esc_url( $this->img_src( 'plugins/bubble-points/images/coupon__check.png' ) );

			// Entire card is the trigger (no internal button)
			return <<<HTML
                <div class="{$class_str} js-bsc-coupon"
                     data-points="{$points_raw}"
                     data-value="{$price_raw}"
                     data-state="{$state_attr}">
                  <div class="bsc__coupon-card__container">
                    <div class="bsc__coupon-card__col">
                      <div class="row">
                        <div class="price border_bottom_dotted">\${$price_fmt}</div>
                        <div class="check">
                          <div class="check__back check__back--{$color_mod}"></div>
                          <div class="check__front">
                            <img src="{$check_img}" alt="check">
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <p class="coupon--text">{$text}</p>
                      </div>
                    </div>

                    <div class="bsc__coupon-card__col">
                      <div class="card__image">
                        <div class="image__back image__back--{$color_mod}"></div>
                        <div class="image__front">
                          <img class="normal" src="{$img_normal}" alt="{$alt}">
                          <img class="hover"  src="{$img_hover}"  alt="{$alt}">
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bsc__coupon-card__label">
                    Redimir x {$points_fmt} Bubble Points
                  </div>
                </div>
            HTML;
		}

		public function render(): void {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_html escapes dynamic values and keeps data attributes needed by coupon JS.
			echo $this->get_html();
		}
	}
}
