<?php
/**
 * Hidden Skin Quiz page and recommendation endpoint.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Skin_Quiz {
	private const OPTION_SUBMISSIONS = 'bsc_skin_quiz_submissions';
	private const MAX_SUBMISSIONS    = 300;

	public static function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render_page' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_recommend', array( __CLASS__, 'ajax_recommend' ) );
		add_action( 'wp_ajax_nopriv_bsc_skin_quiz_recommend', array( __CLASS__, 'ajax_recommend' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'pre_handle_404', array( __CLASS__, 'prevent_404' ), 10, 2 );
	}

	public static function is_request(): bool {
		global $wp;

		if ( isset( $wp->request ) && 'skin-quiz' === trim( (string) $wp->request, '/' ) ) {
			return true;
		}

		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		$home_path    = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' !== $home_path && str_starts_with( $request_path, $home_path . '/' ) ) {
			$request_path = substr( $request_path, strlen( $home_path ) + 1 );
		}

		return 'skin-quiz' === trim( $request_path, '/' );
	}

	public static function body_class( array $classes ): array {
		if ( self::is_request() ) {
			$classes = array_values( array_diff( $classes, array( 'error404' ) ) );
			$classes[] = 'page-skin-quiz';
		}

		return $classes;
	}

	public static function prevent_404( $preempt, WP_Query $query ) {
		if ( ! self::is_request() ) {
			return $preempt;
		}

		$query->is_404  = false;
		$query->is_page = true;
		status_header( 200 );

		return true;
	}

	public static function enqueue_assets(): void {
		if ( ! self::is_request() ) {
			return;
		}

		wp_enqueue_script(
			'bsc-growth-skin-quiz',
			BSC_Growth_Plugin::url( 'assets/skin-quiz.js' ),
			array(),
			BSC_Growth_Plugin::asset_version( 'assets/skin-quiz.js' ),
			true
		);

		wp_localize_script(
			'bsc-growth-skin-quiz',
			'bscGrowthQuiz',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'bsc_growth_action' ),
				'cartUrl'        => BSC_Growth_Plugin::cart_url(),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Optional read-only routine preselection.
				'initialRoutine' => isset( $_GET['routine'] ) ? sanitize_text_field( wp_unslash( $_GET['routine'] ) ) : '',
			)
		);
	}

	public static function render_page(): void {
		if ( ! self::is_request() ) {
			return;
		}

		status_header( 200 );
		nocache_headers();

		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->is_404  = false;
			$wp_query->is_page = true;
		}

		$repository = new BSC_Growth_Bundle_Repository();
		$bundles    = $repository->get_bundles( 3 );

		get_header();
		?>
		<main id="primary" class="site-main bsc-skin-quiz">
			<section class="bsc-skin-quiz__hero">
				<div class="bsc-skin-quiz__container">
					<p class="bsc-skin-quiz__eyebrow">Skin Quiz</p>
					<h1 class="bsc-skin-quiz__title">Arma tu rutina BSC</h1>
					<p class="bsc-skin-quiz__intro">Una seleccion corta para recomendar rutinas segun tipo de piel, necesidad principal y nivel de rutina.</p>
				</div>
			</section>

			<section class="bsc-skin-quiz__content" aria-label="Skin Quiz">
				<div class="bsc-skin-quiz__container bsc-skin-quiz__layout">
					<form class="bsc-skin-quiz__form" data-bsc-skin-quiz-form>
						<?php wp_nonce_field( 'bsc_growth_action', 'bsc_growth_nonce' ); ?>

						<fieldset class="bsc-skin-quiz__fieldset">
							<legend>Tipo de piel</legend>
							<?php self::render_radio_group( 'skin_type', self::skin_type_options(), 'mixta' ); ?>
						</fieldset>

						<fieldset class="bsc-skin-quiz__fieldset">
							<legend>Necesidades</legend>
							<?php self::render_checkbox_group( 'needs', self::need_options() ); ?>
						</fieldset>

						<div class="bsc-skin-quiz__row">
							<label class="bsc-skin-quiz__field">
								<span>Sensibilidad</span>
								<select name="sensitivity">
									<option value="normal">Normal</option>
									<option value="sensible">Sensible</option>
									<option value="muy-sensible">Muy sensible</option>
								</select>
							</label>
							<label class="bsc-skin-quiz__field">
								<span>Nivel de rutina</span>
								<select name="routine_level">
									<option value="basica">Basica</option>
									<option value="completa">Completa</option>
								</select>
							</label>
						</div>

						<button type="submit" class="bsc__button bsc__button--primary bsc-skin-quiz__submit">Ver rutina</button>
						<p class="bsc-skin-quiz__status" data-bsc-skin-quiz-status aria-live="polite"></p>
					</form>

					<aside class="bsc-skin-quiz__results" data-bsc-skin-quiz-results>
						<h2>Rutinas recomendadas</h2>
						<div class="bsc-skin-quiz__bundle-list" data-bsc-skin-quiz-bundles>
							<?php foreach ( $bundles as $bundle ) : ?>
								<?php self::render_bundle_card( $repository->format_bundle_for_response( $bundle ) ); ?>
							<?php endforeach; ?>
						</div>
					</aside>
				</div>
			</section>
		</main>
		<?php
		get_footer();
		exit;
	}

	public static function ajax_recommend(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );

		$answers    = self::sanitize_answers( $_POST );
		$repository = new BSC_Growth_Bundle_Repository();
		$bundles    = $repository->recommend_bundles( $answers, 3 );
		$routine_id = isset( $_POST['routine'] ) ? sanitize_text_field( wp_unslash( $_POST['routine'] ) ) : '';

		if ( '' !== $routine_id ) {
			$selected = $repository->find_bundle( $routine_id );

			if ( $selected ) {
				array_unshift( $bundles, $selected );
			}
		}

		$bundles = array_slice( self::unique_bundles( $bundles ), 0, 3 );
		$payload = array_map(
			static fn( array $bundle ): array => $repository->format_bundle_for_response( $bundle ),
			$bundles
		);

		self::persist_submission( $answers, $payload );
		self::update_customer_profile( $answers );

		wp_send_json_success(
			array(
				'bundles' => $payload,
			)
		);
	}

	private static function render_radio_group( string $name, array $options, string $default ): void {
		foreach ( $options as $value => $label ) :
			?>
			<label class="bsc-skin-quiz__choice">
				<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $default ); ?>>
				<span><?php echo esc_html( $label ); ?></span>
			</label>
			<?php
		endforeach;
	}

	private static function render_checkbox_group( string $name, array $options ): void {
		foreach ( $options as $value => $label ) :
			?>
			<label class="bsc-skin-quiz__choice">
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $value ); ?>">
				<span><?php echo esc_html( $label ); ?></span>
			</label>
			<?php
		endforeach;
	}

	private static function render_bundle_card( array $bundle ): void {
		?>
		<article class="bsc-skin-quiz__bundle" data-bundle-id="<?php echo esc_attr( $bundle['id'] ); ?>">
			<div class="bsc-skin-quiz__bundle-header">
				<?php if ( ! empty( $bundle['badge'] ) ) : ?>
					<span class="bsc-skin-quiz__badge"><?php echo esc_html( $bundle['badge'] ); ?></span>
				<?php endif; ?>
				<h3><?php echo esc_html( $bundle['title'] ); ?></h3>
				<p><?php echo esc_html( $bundle['summary'] ); ?></p>
			</div>

			<?php if ( ! empty( $bundle['products'] ) ) : ?>
				<ul class="bsc-skin-quiz__products">
					<?php foreach ( $bundle['products'] as $product ) : ?>
						<li>
							<a href="<?php echo esc_url( $product['permalink'] ); ?>">
								<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['name'] ); ?>">
								<span><?php echo esc_html( $product['name'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="bsc-skin-quiz__bundle-actions">
				<?php if ( ! empty( $bundle['discount_label'] ) ) : ?>
					<span><?php echo esc_html( $bundle['discount_label'] ); ?></span>
				<?php endif; ?>
				<button type="button" class="bsc__button bsc-skin-quiz__bundle-button" data-bsc-add-bundle="<?php echo esc_attr( $bundle['id'] ); ?>" <?php disabled( empty( $bundle['product_count'] ) ); ?>>
					Agregar rutina
				</button>
			</div>
		</article>
		<?php
	}

	private static function sanitize_answers( array $request ): array {
		$skin_type     = isset( $request['skin_type'] ) ? sanitize_key( wp_unslash( $request['skin_type'] ) ) : 'mixta';
		$sensitivity   = isset( $request['sensitivity'] ) ? sanitize_key( wp_unslash( $request['sensitivity'] ) ) : 'normal';
		$routine_level = isset( $request['routine_level'] ) ? sanitize_key( wp_unslash( $request['routine_level'] ) ) : 'basica';
		$needs         = isset( $request['needs'] ) && is_array( $request['needs'] )
			? array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $request['needs'] ) ) ) )
			: array();

		return array(
			'skin_type'     => $skin_type,
			'sensitivity'   => $sensitivity,
			'routine_level' => $routine_level,
			'needs'         => array_slice( $needs, 0, 4 ),
		);
	}

	private static function persist_submission( array $answers, array $bundles ): void {
		$submissions = get_option( self::OPTION_SUBMISSIONS, array() );
		$submissions = is_array( $submissions ) ? $submissions : array();

		array_unshift(
			$submissions,
			array(
				'created_at' => current_time( 'mysql' ),
				'user_id'    => get_current_user_id(),
				'email'      => is_user_logged_in() ? (string) wp_get_current_user()->user_email : '',
				'answers'    => $answers,
				'bundles'    => wp_list_pluck( $bundles, 'id' ),
			)
		);

		update_option( self::OPTION_SUBMISSIONS, array_slice( $submissions, 0, self::MAX_SUBMISSIONS ), false );
	}

	private static function update_customer_profile( array $answers ): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, 'bsc_skin_type', $answers['skin_type'] );
		update_user_meta( $user_id, 'bsc_sensitivity', $answers['sensitivity'] );

		foreach ( array_values( $answers['needs'] ) as $index => $need ) {
			update_user_meta( $user_id, 'bsc_needs' . ( $index + 1 ), $need );
		}
	}

	private static function unique_bundles( array $bundles ): array {
		$seen = array();

		return array_values(
			array_filter(
				$bundles,
				static function ( array $bundle ) use ( &$seen ): bool {
					if ( isset( $seen[ $bundle['id'] ] ) ) {
						return false;
					}

					$seen[ $bundle['id'] ] = true;
					return true;
				}
			)
		);
	}

	private static function skin_type_options(): array {
		return array(
			'grasa'    => 'Grasa',
			'mixta'    => 'Mixta',
			'seca'     => 'Seca',
			'normal'   => 'Normal',
			'sensible' => 'Sensible',
		);
	}

	private static function need_options(): array {
		return array(
			'acne'            => 'Acne',
			'manchas'         => 'Manchas',
			'hidratacion'     => 'Hidratacion',
			'barrera'         => 'Barrera',
			'glow'            => 'Glow',
			'protector-solar' => 'Protector solar',
		);
	}
}
