<?php
/**
 * Hidden Skin Quiz page and recommendation endpoint.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Skin_Quiz {
	private const OPTION_SUBMISSIONS = 'bsc_skin_quiz_submissions';
	private const OPTION_EVENTS      = 'bsc_skin_quiz_events';
	private const USER_LAST_ROUTINE  = 'bsc_skin_quiz_last_routine';
	private const MAX_SUBMISSIONS    = 300;
	private const MAX_EVENTS         = 1200;

	public static function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render_page' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_recommend', array( __CLASS__, 'ajax_recommend' ) );
		add_action( 'wp_ajax_nopriv_bsc_skin_quiz_recommend', array( __CLASS__, 'ajax_recommend' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_ai_recommend', array( __CLASS__, 'ajax_ai_recommend' ) );
		add_action( 'wp_ajax_nopriv_bsc_skin_quiz_ai_recommend', array( __CLASS__, 'ajax_ai_recommend' ) );
		add_action( 'wp_ajax_bsc_skin_quiz_track', array( __CLASS__, 'ajax_track_event' ) );
		add_action( 'wp_ajax_nopriv_bsc_skin_quiz_track', array( __CLASS__, 'ajax_track_event' ) );
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

		self::prepare_virtual_query( $query );
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
				'aiAvailable'    => ( new BSC_Growth_AI_Service() )->has_api_key(),
				'savedRoutine'   => self::get_saved_routine_payload(),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Optional read-only routine preselection.
				'initialRoutine' => isset( $_GET['routine'] ) ? sanitize_text_field( wp_unslash( $_GET['routine'] ) ) : '',
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Optional read-only mode preselection.
				'initialMode'    => isset( $_GET['mode'] ) ? sanitize_key( wp_unslash( $_GET['mode'] ) ) : 'normal',
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
			self::prepare_virtual_query( $wp_query );
		}

		$repository = new BSC_Growth_Bundle_Repository();
		$bundles    = $repository->get_bundles( 3 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only mode preselection.
		$mode       = isset( $_GET['mode'] ) && 'ai' === sanitize_key( wp_unslash( $_GET['mode'] ) ) ? 'ai' : 'normal';

		get_header();
		?>
		<main id="primary" class="site-main bsc-skin-quiz">
			<section class="bsc-skin-quiz__hero">
				<div class="bsc-skin-quiz__container">
					<p class="bsc-skin-quiz__eyebrow">Skin Quiz</p>
					<h1 class="bsc-skin-quiz__title">Arma tu rutina BSC</h1>
					<p class="bsc-skin-quiz__intro">Elige el quiz rapido o la version AI Enhanced con foto para generar rutina y carrito listo.</p>
				</div>
			</section>

			<section class="bsc-skin-quiz__content" aria-label="Skin Quiz">
				<div class="bsc-skin-quiz__container">
					<div class="bsc-skin-quiz__mode-tabs" role="tablist" aria-label="Versiones del Skin Quiz">
						<button type="button" class="bsc-skin-quiz__mode-tab <?php echo 'normal' === $mode ? 'is-active' : ''; ?>" data-bsc-quiz-mode-tab="normal" role="tab" aria-selected="<?php echo 'normal' === $mode ? 'true' : 'false'; ?>">Skin Quiz 1</button>
						<button type="button" class="bsc-skin-quiz__mode-tab <?php echo 'ai' === $mode ? 'is-active' : ''; ?>" data-bsc-quiz-mode-tab="ai" role="tab" aria-selected="<?php echo 'ai' === $mode ? 'true' : 'false'; ?>">Skincare AI</button>
					</div>
				</div>

				<div class="bsc-skin-quiz__container bsc-skin-quiz__layout <?php echo 'ai' === $mode ? 'is-ai-mode' : ''; ?>">
					<div class="bsc-skin-quiz__forms">
						<?php self::render_normal_form( 'normal' !== $mode ); ?>
						<?php self::render_ai_form( 'ai' !== $mode ); ?>
					</div>

					<aside class="bsc-skin-quiz__results" data-bsc-skin-quiz-results>
						<div class="bsc-skin-quiz__saved is-hidden" data-bsc-saved-routine>
							<div>
								<span>Rutina guardada</span>
								<strong data-bsc-saved-routine-title>Tu ultima recomendacion BSC</strong>
							</div>
							<button type="button" class="bsc-skin-quiz__saved-button" data-bsc-restore-routine>Ver rutina</button>
						</div>
						<?php self::render_ai_visual(); ?>
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

	private static function prepare_virtual_query( WP_Query $query ): void {
		$query->is_404        = false;
		$query->is_page       = false;
		$query->is_single     = false;
		$query->is_singular   = false;
		$query->queried_object = null;
		$query->queried_object_id = 0;
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
		self::save_customer_last_routine(
			array(
				'mode'    => 'normal',
				'bundles' => $payload,
			)
		);

		wp_send_json_success(
			array(
				'bundles' => $payload,
			)
		);
	}

	public static function ajax_ai_recommend(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );

		$answers = self::sanitize_answers( $_POST );

		if ( empty( $_POST['ai_consent'] ) ) {
			wp_send_json_error( array( 'message' => 'Confirma el consentimiento para analizar la foto.' ), 400 );
		}

		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload is validated by BSC_Growth_AI_Service::validate_image().
			$response = ( new BSC_Growth_AI_Service() )->recommend_with_image( $answers, $_FILES['skin_photo'] ?? array() );
		} catch ( Throwable $exception ) {
			wp_send_json_error( array( 'message' => $exception->getMessage() ), 400 );
		}

		self::persist_submission( array_merge( $answers, array( 'mode' => 'ai' ) ), $response['bundles'] );
		self::update_customer_profile( $answers );
		self::save_customer_last_routine(
			array(
				'mode'    => 'ai',
				'bundles' => $response['bundles'],
				'ai'      => $response['ai'] ?? array(),
			)
		);

		wp_send_json_success( $response );
	}

	public static function ajax_track_event(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );

		$event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
		$mode  = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'normal';
		$meta  = self::sanitize_tracking_meta( $_POST );

		if ( '' === $event ) {
			wp_send_json_error( array( 'message' => 'Evento invalido.' ), 400 );
		}

		self::track_event( $event, $mode, $meta );
		wp_send_json_success( array( 'tracked' => true ) );
	}

	private static function render_normal_form( bool $hidden ): void {
		?>
		<form class="bsc-skin-quiz__form <?php echo $hidden ? 'is-hidden' : ''; ?>" data-bsc-skin-quiz-form data-quiz-mode="normal">
			<?php wp_nonce_field( 'bsc_growth_action', 'bsc_growth_nonce' ); ?>

			<fieldset class="bsc-skin-quiz__fieldset">
				<legend>Tipo de piel</legend>
				<?php self::render_radio_group( 'skin_type', self::skin_type_options(), 'mixta' ); ?>
			</fieldset>

			<fieldset class="bsc-skin-quiz__fieldset">
				<legend>Necesidades</legend>
				<?php self::render_checkbox_group( 'needs', self::need_options() ); ?>
			</fieldset>

			<?php self::render_common_fields(); ?>

			<button type="submit" class="bsc__button bsc__button--primary bsc-skin-quiz__submit">Ver rutina</button>
			<p class="bsc-skin-quiz__status" data-bsc-skin-quiz-status aria-live="polite"></p>
		</form>
		<?php
	}

	private static function render_ai_form( bool $hidden ): void {
		?>
		<form class="bsc-skin-quiz__form bsc-skin-quiz__form--ai <?php echo $hidden ? 'is-hidden' : ''; ?>" data-bsc-skin-quiz-form data-quiz-mode="ai" enctype="multipart/form-data">
			<?php wp_nonce_field( 'bsc_growth_action', 'bsc_growth_nonce' ); ?>

			<div class="bsc-skin-quiz__capture" data-bsc-camera-shell>
				<span class="bsc-skin-quiz__capture-label">Foto para asesoria</span>
				<div class="bsc-skin-quiz__mirror" data-bsc-camera-mirror>
					<video class="bsc-skin-quiz__mirror-video is-hidden" data-bsc-camera-video autoplay muted playsinline></video>
					<canvas class="bsc-skin-quiz__mirror-canvas is-hidden" data-bsc-camera-canvas></canvas>
					<div class="bsc-skin-quiz__mirror-empty" data-bsc-camera-empty>
						<strong>Usa la camara como espejo</strong>
						<span>Busca luz natural y toma la foto de frente.</span>
					</div>
				</div>
				<div class="bsc-skin-quiz__capture-actions">
					<button type="button" class="bsc-skin-quiz__secondary-button" data-bsc-camera-start>Prender camara</button>
					<button type="button" class="bsc-skin-quiz__secondary-button is-hidden" data-bsc-camera-shot>Tomar foto</button>
					<button type="button" class="bsc-skin-quiz__ghost-button is-hidden" data-bsc-camera-stop>Apagar</button>
				</div>
				<p class="bsc-skin-quiz__photo-warning" data-bsc-photo-quality aria-live="polite"></p>
			</div>

			<label class="bsc-skin-quiz__upload">
				<span class="bsc-skin-quiz__upload-label">Foto del rostro</span>
				<input type="file" name="skin_photo" accept="image/jpeg,image/png,image/webp" data-bsc-skin-photo>
				<input type="hidden" name="vision_signals" value="" data-bsc-vision-signals>
				<span class="bsc-skin-quiz__upload-control">
					<strong>Seleccionar foto</strong>
					<em data-bsc-upload-file>JPG, PNG o WebP hasta 4MB</em>
				</span>
			</label>

			<fieldset class="bsc-skin-quiz__fieldset">
				<legend>Como se siente tu piel</legend>
				<?php self::render_radio_group( 'skin_type', self::skin_type_options(), 'mixta' ); ?>
			</fieldset>

			<label class="bsc-skin-quiz__field">
				<span>Necesidad principal</span>
				<select name="needs[]">
					<option value="manchas">Manchas</option>
					<option value="acne">Brotes</option>
					<option value="hidratacion">Hidratacion</option>
					<option value="barrera">Barrera</option>
					<option value="glow">Glow</option>
					<option value="protector-solar">Protector solar</option>
				</select>
			</label>

			<fieldset class="bsc-skin-quiz__fieldset">
				<legend>Objetivo principal</legend>
				<?php self::render_radio_group( 'skin_goal', self::skin_goal_options(), 'tono-uniforme' ); ?>
			</fieldset>

			<?php self::render_common_fields(); ?>

			<label class="bsc-skin-quiz__consent">
				<input type="checkbox" name="ai_consent" value="1" required>
				<span>Acepto analizar esta foto solo para recomendar skincare. La imagen no se guarda.</span>
			</label>

			<button type="submit" class="bsc__button bsc__button--primary bsc-skin-quiz__submit">Analizar con AI</button>
			<p class="bsc-skin-quiz__status" data-bsc-skin-quiz-status aria-live="polite"></p>
		</form>
		<?php
	}

	private static function render_common_fields(): void {
		?>
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
			<label class="bsc-skin-quiz__field">
				<span>Protector solar</span>
				<select name="sunscreen_habit">
					<option value="diario">Lo uso todos los dias</option>
					<option value="a-veces">A veces</option>
					<option value="no-uso">Casi no lo uso</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Despues de lavar</span>
				<select name="post_cleanse_feel">
					<option value="normal">Se siente normal</option>
					<option value="tirante">Tirante o reseca</option>
					<option value="brillante">Brillante rapido</option>
					<option value="variable">Depende del dia</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Brotes</span>
				<select name="breakout_frequency">
					<option value="ocasional">Ocasionales</option>
					<option value="raro">Rara vez</option>
					<option value="frecuente">Frecuentes</option>
					<option value="hormonal">Por ciclos</option>
				</select>
			</label>
		</div>
		<?php
	}

	private static function render_ai_visual(): void {
		?>
		<div class="bsc-skin-quiz__ai-visual" data-bsc-ai-visual>
			<div class="bsc-skin-quiz__compare is-empty" data-bsc-ai-compare>
				<div class="bsc-skin-quiz__compare-frame">
					<div class="bsc-skin-quiz__compare-placeholder" data-bsc-compare-placeholder>
						<span>Skincare AI</span>
						<strong>Sube tu foto y genera el antes / despues</strong>
					</div>
					<img class="bsc-skin-quiz__compare-image" data-bsc-before-image alt="Foto original">
					<div class="bsc-skin-quiz__compare-after" data-bsc-after-wrap>
						<img class="bsc-skin-quiz__compare-image bsc-skin-quiz__compare-image--after" data-bsc-after-image alt="Simulacion AI">
					</div>
					<div class="bsc-skin-quiz__compare-handle" aria-hidden="true"></div>
					<input type="range" min="0" max="100" value="50" class="bsc-skin-quiz__compare-range" data-bsc-compare-range aria-label="Comparar antes y despues">
					<span class="bsc-skin-quiz__compare-label bsc-skin-quiz__compare-label--before">Antes</span>
					<span class="bsc-skin-quiz__compare-label bsc-skin-quiz__compare-label--after">Despues AI</span>
				</div>
			</div>
			<div class="bsc-skin-quiz__diagnosis is-empty" data-bsc-ai-diagnosis aria-live="polite">
				<div class="bsc-skin-quiz__diagnosis-header">
					<span>Diagnostico Gemini</span>
					<strong data-bsc-ai-skin-type>Pendiente de analisis</strong>
				</div>
				<div class="bsc-skin-quiz__diagnosis-meter">
					<span data-bsc-ai-confidence></span>
				</div>
				<ul class="bsc-skin-quiz__diagnosis-needs" data-bsc-ai-needs></ul>
				<p class="bsc-skin-quiz__ai-notes" data-bsc-ai-notes>La lectura cosmetica aparecera aqui despues de analizar la foto.</p>
			</div>
		</div>
		<?php
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
		$skin_type          = isset( $request['skin_type'] ) ? sanitize_key( wp_unslash( $request['skin_type'] ) ) : 'mixta';
		$sensitivity        = isset( $request['sensitivity'] ) ? sanitize_key( wp_unslash( $request['sensitivity'] ) ) : 'normal';
		$routine_level      = isset( $request['routine_level'] ) ? sanitize_key( wp_unslash( $request['routine_level'] ) ) : 'basica';
		$skin_goal          = isset( $request['skin_goal'] ) ? sanitize_key( wp_unslash( $request['skin_goal'] ) ) : 'tono-uniforme';
		$sunscreen_habit    = isset( $request['sunscreen_habit'] ) ? sanitize_key( wp_unslash( $request['sunscreen_habit'] ) ) : 'diario';
		$post_cleanse_feel  = isset( $request['post_cleanse_feel'] ) ? sanitize_key( wp_unslash( $request['post_cleanse_feel'] ) ) : 'normal';
		$breakout_frequency = isset( $request['breakout_frequency'] ) ? sanitize_key( wp_unslash( $request['breakout_frequency'] ) ) : 'ocasional';
		$needs              = isset( $request['needs'] ) && is_array( $request['needs'] )
			? array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $request['needs'] ) ) ) )
			: array();

		return array(
			'skin_type'          => self::allowed_value( $skin_type, array_keys( self::skin_type_options() ), 'mixta' ),
			'sensitivity'        => self::allowed_value( $sensitivity, array( 'normal', 'sensible', 'muy-sensible' ), 'normal' ),
			'routine_level'      => self::allowed_value( $routine_level, array( 'basica', 'completa' ), 'basica' ),
			'skin_goal'          => self::allowed_value( $skin_goal, array_keys( self::skin_goal_options() ), 'tono-uniforme' ),
			'sunscreen_habit'    => self::allowed_value( $sunscreen_habit, array( 'diario', 'a-veces', 'no-uso' ), 'diario' ),
			'post_cleanse_feel'  => self::allowed_value( $post_cleanse_feel, array( 'normal', 'tirante', 'brillante', 'variable' ), 'normal' ),
			'breakout_frequency' => self::allowed_value( $breakout_frequency, array( 'raro', 'ocasional', 'frecuente', 'hormonal' ), 'ocasional' ),
			'needs'              => array_slice( $needs, 0, 4 ),
			'vision_signals'     => self::sanitize_vision_signals( $request ),
		);
	}

	/**
	 * Sanitize approximate browser-side image signals before sending them to AI.
	 *
	 * @param array $request Request payload.
	 * @return array
	 */
	private static function sanitize_vision_signals( array $request ): array {
		$raw = isset( $request['vision_signals'] ) ? sanitize_textarea_field( wp_unslash( $request['vision_signals'] ) ) : '';

		if ( '' === $raw ) {
			return array();
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		$signals = array();
		$numeric = array(
			'width',
			'height',
			'brightness',
			'contrast',
			'saturation',
			'skin_pixel_ratio',
			'face_count',
			'face_area_ratio',
			'face_center_score',
			'shine_signal',
			'redness_signal',
			'dark_spot_signal',
			'texture_signal',
			'sharpness',
		);

		foreach ( $numeric as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_numeric( $data[ $key ] ) ) {
				continue;
			}

			$value = (float) $data[ $key ];

			if ( in_array( $key, array( 'width', 'height' ), true ) ) {
				$signals[ $key ] = max( 0, min( 8000, (int) round( $value ) ) );
			} elseif ( 'face_count' === $key ) {
				$signals[ $key ] = max( 0, min( 8, (int) round( $value ) ) );
			} else {
				$signals[ $key ] = max( 0, min( 1, round( $value, 4 ) ) );
			}
		}

		if ( isset( $data['face_detection'] ) ) {
			$signals['face_detection'] = self::allowed_value( sanitize_key( (string) $data['face_detection'] ), array( 'supported', 'unsupported', 'failed' ), 'unsupported' );
		}

		if ( isset( $data['quality_flags'] ) && is_array( $data['quality_flags'] ) ) {
			$signals['quality_flags'] = array_slice(
				array_values(
					array_filter(
						array_map( 'sanitize_key', $data['quality_flags'] ),
						static fn( string $flag ): bool => '' !== $flag
					)
				),
				0,
				6
			);
		}

		return $signals;
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

		update_user_meta( $user_id, 'bsc_skin_goal', $answers['skin_goal'] ?? '' );
		update_user_meta( $user_id, 'bsc_sunscreen_habit', $answers['sunscreen_habit'] ?? '' );
		update_user_meta( $user_id, 'bsc_post_cleanse_feel', $answers['post_cleanse_feel'] ?? '' );
		update_user_meta( $user_id, 'bsc_breakout_frequency', $answers['breakout_frequency'] ?? '' );
	}

	private static function save_customer_last_routine( array $routine ): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$routine['created_at'] = current_time( 'mysql' );
		update_user_meta( $user_id, self::USER_LAST_ROUTINE, $routine );
	}

	private static function get_saved_routine_payload(): array {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return array();
		}

		$routine = get_user_meta( $user_id, self::USER_LAST_ROUTINE, true );

		return is_array( $routine ) ? $routine : array();
	}

	private static function sanitize_tracking_meta( array $request ): array {
		$raw = isset( $request['meta'] ) ? sanitize_textarea_field( wp_unslash( $request['meta'] ) ) : '';

		if ( '' === $raw ) {
			return array();
		}

		$decoded = json_decode( $raw, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$meta = array();
		foreach ( $decoded as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}

			if ( is_scalar( $value ) ) {
				$meta[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return array_slice( $meta, 0, 10, true );
	}

	private static function track_event( string $event, string $mode, array $meta = array() ): void {
		$allowed_events = array(
			'quiz_view',
			'mode_change',
			'photo_selected',
			'camera_started',
			'camera_captured',
			'photo_quality_blocked',
			'quiz_submit',
			'quiz_result',
			'routine_add_to_cart',
			'routine_restore',
		);

		if ( ! in_array( $event, $allowed_events, true ) ) {
			return;
		}

		$events = get_option( self::OPTION_EVENTS, array() );
		$events = is_array( $events ) ? $events : array();

		array_unshift(
			$events,
			array(
				'created_at' => current_time( 'mysql' ),
				'date'       => current_time( 'Y-m-d' ),
				'event'      => $event,
				'mode'       => in_array( $mode, array( 'normal', 'ai' ), true ) ? $mode : 'normal',
				'user_id'    => get_current_user_id(),
				'meta'       => $meta,
			)
		);

		update_option( self::OPTION_EVENTS, array_slice( $events, 0, self::MAX_EVENTS ), false );
	}

	public static function get_tracking_summary( string $start_date, string $end_date ): array {
		$events  = get_option( self::OPTION_EVENTS, array() );
		$events  = is_array( $events ) ? $events : array();
		$summary = array_fill_keys(
			array(
				'quiz_view',
				'mode_change',
				'photo_selected',
				'camera_started',
				'camera_captured',
				'photo_quality_blocked',
				'quiz_submit',
				'quiz_result',
				'routine_add_to_cart',
				'routine_restore',
			),
			0
		);

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$date = (string) ( $event['date'] ?? '' );
			$name = (string) ( $event['event'] ?? '' );

			if ( $date < $start_date || $date > $end_date || ! array_key_exists( $name, $summary ) ) {
				continue;
			}

			++$summary[ $name ];
		}

		return $summary;
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

	private static function skin_goal_options(): array {
		return array(
			'tono-uniforme'  => 'Tono uniforme',
			'control-brillo' => 'Control brillo',
			'calmar-piel'    => 'Calmar piel',
			'glow'           => 'Glow saludable',
			'barrera'        => 'Barrera fuerte',
			'brotes'         => 'Menos brotes',
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

	private static function allowed_value( string $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
