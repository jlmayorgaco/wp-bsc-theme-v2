<?php
/**
 * Hidden Skin Quiz page and recommendation endpoint.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Skin_Quiz {
	private const OPTION_SUBMISSIONS = 'bsc_skin_quiz_submissions';
	private const OPTION_EVENTS      = 'bsc_skin_quiz_events';
	private const USER_LAST_ROUTINE  = 'bsc_skin_quiz_last_routine';
	private const USER_PROGRESS      = 'bsc_skin_quiz_progress_snapshots';
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
			$classes   = array_values( array_diff( $classes, array( 'error404' ) ) );
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
		$is_account = function_exists( 'is_account_page' ) && is_account_page();
		if ( ! self::is_request() && ! $is_account ) {
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
				'checkoutUrl'    => BSC_Growth_Plugin::checkout_url(),
				'aiAvailable'    => ( new BSC_Growth_AI_Service() )->has_api_key(),
				'savedRoutine'   => self::get_saved_routine_payload(),
				'sharedRoutine'  => self::get_shared_routine_payload(),
				'currentEmail'    => is_user_logged_in() ? (string) wp_get_current_user()->user_email : '',
				'abTesting'      => array(
					'enabled' => (bool) get_option( 'bsc_skin_quiz_ab_testing_enabled', 0 ),
				),
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
		$mode = isset( $_GET['mode'] ) && 'ai' === sanitize_key( wp_unslash( $_GET['mode'] ) ) ? 'ai' : 'normal';

		get_header();
		?>
		<main id="primary" class="site-main bsc-skin-quiz">
			<section class="bsc-skin-quiz__hero">
				<div class="bsc-skin-quiz__container">
					<p class="bsc-skin-quiz__eyebrow">Asesoría BSC</p>
					<h1 class="bsc-skin-quiz__title">Encuentra tu rutina ideal</h1>
					<p class="bsc-skin-quiz__intro">Responde unas preguntas y, si quieres, usa una foto para recibir una rutina facial personalizada con productos listos para agregar al carrito.</p>
					<div class="bsc-skin-quiz__hero-signals" aria-label="Resumen del Skin Quiz">
						<span><strong>2 modos</strong><em>quiz o AI</em></span>
						<span><strong>Lectura</strong><em>cosmetica</em></span>
						<span><strong>Carrito</strong><em>listo</em></span>
					</div>
				</div>
			</section>

			<section class="bsc-skin-quiz__content" aria-label="Skin Quiz">
				<div class="bsc-skin-quiz__container">
					<div class="bsc-skin-quiz__mode-tabs" role="tablist" aria-label="Versiones del Skin Quiz">
						<button type="button" class="bsc-skin-quiz__mode-tab <?php echo 'normal' === $mode ? 'is-active' : ''; ?>" data-bsc-quiz-mode-tab="normal" role="tab" aria-selected="<?php echo 'normal' === $mode ? 'true' : 'false'; ?>">Quiz rápido</button>
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
								<span>Última rutina</span>
								<strong data-bsc-saved-routine-title>Recupera tu recomendación guardada</strong>
							</div>
							<button type="button" class="bsc-skin-quiz__saved-button" data-bsc-restore-routine>Ver recomendación</button>
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
		$virtual_post = self::virtual_post();

		$query->is_404            = false;
		$query->is_page           = true;
		$query->is_single         = false;
		$query->is_singular       = true;
		$query->queried_object    = $virtual_post;
		$query->post              = $virtual_post;
		$query->posts             = array( $virtual_post );
		$query->post_count        = 1;
		$query->found_posts       = 1;
		$query->queried_object_id = 0;

		global $post;
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Virtual page needs a page-like global post for core template helpers.
		$post = $virtual_post;
	}

	private static function virtual_post(): WP_Post {
		$now = current_time( 'mysql' );

		return new WP_Post(
			(object) array(
				'ID'                    => 0,
				'post_author'           => 0,
				'post_date'             => $now,
				'post_date_gmt'         => get_gmt_from_date( $now ),
				'post_content'          => '',
				'post_title'            => 'Skin Quiz',
				'post_excerpt'          => '',
				'post_status'           => 'publish',
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'post_password'         => '',
				'post_name'             => 'skin-quiz',
				'to_ping'               => '',
				'pinged'                => '',
				'post_modified'         => $now,
				'post_modified_gmt'     => get_gmt_from_date( $now ),
				'post_content_filtered' => '',
				'post_parent'           => 0,
				'guid'                  => home_url( '/skin-quiz/' ),
				'menu_order'            => 0,
				'post_type'             => 'page',
				'post_mime_type'        => '',
				'comment_count'         => 0,
				'filter'                => 'raw',
			)
		);
	}

	public static function ajax_recommend(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );
		if ( function_exists( 'bsc_rate_limit' ) ) {
			bsc_rate_limit( 'skin_quiz_recommend', 20, MINUTE_IN_SECONDS );
		}

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
		$run_id = BSC_Growth_Skin_Quiz_Store::create_run( $answers, array( 'bundles' => $payload ), array(), 'normal' );
		if ( $run_id > 0 ) {
			$payload = self::attach_run_meta_to_bundles( $payload, BSC_Growth_Skin_Quiz_Store::get_run( $run_id ) );
		}
		self::save_customer_last_routine(
			array(
				'mode'    => 'normal',
				'bundles' => $payload,
				'run_id'  => $run_id,
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
		if ( function_exists( 'bsc_rate_limit' ) ) {
			bsc_rate_limit( 'skin_quiz_ai', 3, 10 * MINUTE_IN_SECONDS );
		}

		$answers = self::sanitize_answers( $_POST );
		$answers['progress_context'] = self::build_progress_context( $answers );

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
		self::save_customer_progress_snapshot( $answers );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload is validated by BSC_Growth_AI_Service before private storage.
		$run_id = BSC_Growth_Skin_Quiz_Store::create_run( $answers, $response, $_FILES['skin_photo'] ?? array(), 'ai' );
		if ( $run_id > 0 ) {
			$run = BSC_Growth_Skin_Quiz_Store::get_run( $run_id );
			$response['bundles'] = self::attach_run_meta_to_bundles( (array) $response['bundles'], $run );
			if ( isset( $response['ai'] ) && is_array( $response['ai'] ) ) {
				$response['ai']['run_id']       = $run_id;
				$response['ai']['share_url']    = $run ? BSC_Growth_Skin_Quiz_Store::share_url( $run ) : '';
				$response['ai']['whatsapp_url'] = $run ? BSC_Growth_Skin_Quiz_Store::whatsapp_url( $run ) : '';
			}
		}

		$saved_ai = isset( $response['ai'] ) && is_array( $response['ai'] ) ? $response['ai'] : array();
		unset( $saved_ai['after_image_data_uri'] );

		self::save_customer_last_routine(
			array(
				'mode'    => 'ai',
				'bundles' => $response['bundles'],
				'ai'      => $saved_ai,
				'run_id'  => $run_id,
			)
		);

		wp_send_json_success( $response );
	}

	public static function ajax_track_event(): void {
		check_ajax_referer( 'bsc_growth_action', 'nonce' );
		if ( function_exists( 'bsc_rate_limit' ) ) {
			bsc_rate_limit( 'skin_quiz_track', 120, MINUTE_IN_SECONDS );
		}

		$event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';
		$mode  = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'normal';
		$meta  = self::sanitize_tracking_meta( $_POST );

		if ( '' === $event ) {
			wp_send_json_error( array( 'message' => 'Evento inválido.' ), 400 );
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

			<div class="bsc-skin-quiz__capture" data-bsc-camera-shell data-bsc-photo-dropzone>
				<div class="bsc-skin-quiz__capture-intro">
					<span class="bsc-skin-quiz__capture-kicker">Estudio Skin AI</span>
					<h2 class="bsc-skin-quiz__capture-title">Foto para tu lectura cosmética</h2>
					<p class="bsc-skin-quiz__capture-help">Sube una selfie o abre la cámara. Analizamos luz, textura y señales visibles para crear tu rutina.</p>
					<div class="bsc-skin-quiz__quality-pills" aria-label="Checklist para una buena foto">
						<span><i class="fas fa-sun" aria-hidden="true"></i>Luz frontal</span>
						<span><i class="fas fa-user-check" aria-hidden="true"></i>Rostro centrado</span>
						<span><i class="fas fa-sparkles" aria-hidden="true"></i>Sin filtros</span>
					</div>
					<div class="bsc-skin-quiz__photo-signals" data-bsc-photo-signals aria-label="Estado de foto">
						<span data-bsc-photo-signal="lighting"><i aria-hidden="true"></i>Luz</span>
						<span data-bsc-photo-signal="sharpness"><i aria-hidden="true"></i>Enfoque</span>
						<span data-bsc-photo-signal="framing"><i aria-hidden="true"></i>Encuadre</span>
					</div>
				</div>

				<div class="bsc-skin-quiz__photo-stage">
					<label class="bsc-skin-quiz__upload" data-bsc-upload-dropzone>
						<input type="file" name="skin_photo" accept="image/jpeg,image/png,image/webp" data-bsc-skin-photo>
						<input type="hidden" name="vision_signals" value="" data-bsc-vision-signals>
						<canvas class="bsc-skin-quiz__upload-canvas" data-bsc-upload-canvas aria-hidden="true"></canvas>
						<span class="bsc-skin-quiz__upload-control">
							<i class="fas fa-image" aria-hidden="true"></i>
							<span>
								<strong>Subir selfie</strong>
								<em data-bsc-upload-file>Arrastra aquí o elige una foto</em>
							</span>
						</span>
						<span class="bsc-skin-quiz__upload-hint">JPG, PNG o WebP</span>
					</label>

					<div class="bsc-skin-quiz__photo-options">
						<button type="button" class="bsc-skin-quiz__photo-option" data-bsc-camera-start>
							<i class="fas fa-camera" aria-hidden="true"></i>
							<span>
								<strong>Abrir cámara</strong>
								<em>Usar preview como espejo</em>
							</span>
						</button>
					</div>
				</div>
				<div class="bsc-skin-quiz__mirror" data-bsc-camera-mirror>
					<video class="bsc-skin-quiz__mirror-video is-hidden" data-bsc-camera-video autoplay muted playsinline></video>
					<canvas class="bsc-skin-quiz__mirror-canvas is-hidden" data-bsc-camera-canvas></canvas>
					<div class="bsc-skin-quiz__mirror-empty" data-bsc-camera-empty>
						<span class="bsc-skin-quiz__mirror-ring" aria-hidden="true"></span>
						<strong>Espejo de análisis</strong>
					</div>
				</div>
				<div class="bsc-skin-quiz__capture-actions">
					<button type="button" class="bsc-skin-quiz__secondary-button is-hidden" data-bsc-camera-shot>Usar esta foto</button>
					<button type="button" class="bsc-skin-quiz__ghost-button is-hidden" data-bsc-camera-stop>Cancelar</button>
				</div>
				<p class="bsc-skin-quiz__photo-warning" data-bsc-photo-quality aria-live="polite"></p>
			</div>

			<div class="bsc-skin-quiz__questions">
				<details class="bsc-skin-quiz__advanced">
					<summary>
						<span>
							<strong>Filtros avanzados</strong>
							<em>Ajusta tu rutina si quieres ser más precisa</em>
						</span>
						<i class="fas fa-sliders-h" aria-hidden="true"></i>
					</summary>
					<div class="bsc-skin-quiz__advanced-body">
				<fieldset class="bsc-skin-quiz__fieldset">
					<legend>¿Cómo se siente tu piel?</legend>
					<?php self::render_radio_group( 'skin_type', self::skin_type_options(), 'mixta' ); ?>
				</fieldset>

				<label class="bsc-skin-quiz__field">
					<span>Necesidad que más quieres mejorar</span>
					<select name="needs[]">
						<option value="manchas">Manchas</option>
						<option value="acne">Brotes</option>
						<option value="hidratacion">Hidratación</option>
						<option value="barrera">Barrera</option>
						<option value="glow">Glow</option>
						<option value="protector-solar">Protector solar</option>
					</select>
				</label>

				<fieldset class="bsc-skin-quiz__fieldset">
					<legend>Meta principal</legend>
					<?php self::render_radio_group( 'skin_goal', self::skin_goal_options(), 'tono-uniforme' ); ?>
				</fieldset>

				<?php self::render_common_fields(); ?>
					</div>
				</details>

				<label class="bsc-skin-quiz__consent">
					<input type="checkbox" name="ai_consent" value="1" required>
					<span>Acepto guardar mi selfie por 90 días para historial, soporte y seguimiento de rutina. La imagen no se comparte públicamente.</span>
				</label>

				<button type="submit" class="bsc__button bsc__button--primary bsc-skin-quiz__submit">Crear mi rutina</button>
				<p class="bsc-skin-quiz__status" data-bsc-skin-quiz-status aria-live="polite"></p>
				<div class="bsc-skin-quiz__ai-loader" data-bsc-ai-loader aria-hidden="true">
					<div class="bsc-skin-quiz__ai-loader-header">
						<span class="bsc-skin-quiz__ai-loader-orbit" aria-hidden="true"></span>
						<div>
							<strong data-bsc-ai-loader-phrase>Preparando tu lectura</strong>
							<em data-bsc-ai-loader-detail>Gemini esta procesando foto y respuestas.</em>
						</div>
						<time data-bsc-ai-loader-time datetime="PT0S">0:00</time>
					</div>
					<div class="bsc-skin-quiz__ai-loader-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-bsc-ai-loader-progress-bar>
						<span data-bsc-ai-loader-progress></span>
					</div>
					<div class="bsc-skin-quiz__ai-loader-steps" aria-hidden="true">
						<span data-bsc-ai-loader-step>Foto</span>
						<span data-bsc-ai-loader-step>Senales</span>
						<span data-bsc-ai-loader-step>Rutina</span>
						<span data-bsc-ai-loader-step>Carrito</span>
					</div>
				</div>
			</div>
		</form>
		<?php
	}

	private static function render_common_fields(): void {
		?>
		<div class="bsc-skin-quiz__row">
			<label class="bsc-skin-quiz__field">
				<span>Rango de edad</span>
				<select name="age_range">
					<?php foreach ( self::age_range_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Sensibilidad</span>
				<select name="sensitivity">
					<option value="normal">Normal</option>
					<option value="sensible">Sensible</option>
					<option value="muy-sensible">Muy sensible</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Tipo de rutina</span>
				<select name="routine_level">
					<option value="basica">Básica</option>
					<option value="completa">Completa</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>¿Usas protector solar?</span>
				<select name="sunscreen_habit">
					<option value="diario">Todos los días</option>
					<option value="a-veces">A veces</option>
					<option value="no-uso">Casi nunca</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Después de lavar</span>
				<select name="post_cleanse_feel">
					<option value="normal">Se siente normal</option>
					<option value="tirante">Tirante o reseca</option>
					<option value="brillante">Brilla rápido</option>
					<option value="variable">Depende del día</option>
				</select>
			</label>
			<label class="bsc-skin-quiz__field">
				<span>Frecuencia de brotes</span>
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
						<strong>Agrega una foto para activar la comparación</strong>
					</div>
					<img class="bsc-skin-quiz__compare-image" data-bsc-before-image alt="Foto original">
					<div class="bsc-skin-quiz__compare-after" data-bsc-after-wrap>
						<img class="bsc-skin-quiz__compare-image bsc-skin-quiz__compare-image--after" data-bsc-after-image alt="Simulación AI">
						<canvas class="bsc-skin-quiz__webgl-canvas" data-bsc-webgl-preview aria-hidden="true"></canvas>
					</div>
					<div class="bsc-skin-quiz__analysis" data-bsc-ai-analysis aria-hidden="true">
						<div class="bsc-skin-quiz__analysis-mask" aria-hidden="true"></div>
						<div class="bsc-skin-quiz__analysis-scan" aria-hidden="true"></div>
						<div class="bsc-skin-quiz__analysis-points" aria-hidden="true">
							<span></span>
							<span></span>
							<span></span>
						</div>
						<div class="bsc-skin-quiz__analysis-panel">
							<span data-bsc-ai-analysis-stage>Procesando foto</span>
							<strong data-bsc-ai-analysis-text>Analizando luz, textura y necesidades</strong>
							<div class="bsc-skin-quiz__analysis-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
								<span data-bsc-ai-analysis-progress></span>
							</div>
							<div class="bsc-skin-quiz__analysis-steps" data-bsc-ai-analysis-steps aria-hidden="true">
								<span></span>
								<span></span>
								<span></span>
								<span></span>
								<span></span>
							</div>
						</div>
					</div>
					<div class="bsc-skin-quiz__compare-handle" aria-hidden="true"></div>
					<input type="range" min="0" max="100" value="50" class="bsc-skin-quiz__compare-range" data-bsc-compare-range aria-label="Comparar antes y después">
					<span class="bsc-skin-quiz__compare-label bsc-skin-quiz__compare-label--before">Antes</span>
					<span class="bsc-skin-quiz__compare-label bsc-skin-quiz__compare-label--after">Después</span>
				</div>
			</div>
			<div class="bsc-skin-quiz__diagnosis is-empty" data-bsc-ai-diagnosis aria-live="polite">
				<div class="bsc-skin-quiz__diagnosis-header">
					<span>Lectura cosmética</span>
					<strong data-bsc-ai-skin-type>Lista para analizar</strong>
				</div>
				<div class="bsc-skin-quiz__diagnosis-meter">
					<span data-bsc-ai-confidence></span>
				</div>
				<div class="bsc-skin-quiz__diagnosis-summary" data-bsc-ai-summary>
					<div>
						<span>Ajuste</span>
						<strong data-bsc-ai-fit-score>--</strong>
					</div>
					<div>
						<span>Foco</span>
						<strong data-bsc-ai-primary-focus>Rutina</strong>
					</div>
					<div>
						<span>Base</span>
						<strong data-bsc-ai-base-note>Foto + quiz</strong>
					</div>
				</div>
				<ul class="bsc-skin-quiz__diagnosis-needs" data-bsc-ai-needs></ul>
				<div class="bsc-skin-quiz__diagnosis-bars-title">
					<span>Prioridades cosméticas</span>
					<em>Mayor porcentaje = más peso en tu rutina</em>
				</div>
				<div class="bsc-skin-quiz__diagnosis-bars" data-bsc-ai-diagnostic-bars></div>
				<div class="bsc-skin-quiz__ai-notes" data-bsc-ai-notes>Tu lectura cosmética aparecerá aquí después de analizar la foto.</div>
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
				<?php if ( ! empty( $bundle['total_html'] ) ) : ?>
					<span class="bsc-skin-quiz__bundle-total"><?php echo wp_kses_post( $bundle['total_html'] ); ?></span>
				<?php endif; ?>
				<h3><?php echo esc_html( $bundle['title'] ); ?></h3>
				<p><?php echo esc_html( $bundle['summary'] ); ?></p>
			</div>

			<?php if ( ! empty( $bundle['products'] ) ) : ?>
				<ul class="bsc-skin-quiz__products">
					<?php foreach ( $bundle['products'] as $product ) : ?>
						<li class="bsc-skin-quiz__product <?php echo empty( $product['is_addable'] ) ? 'is-unavailable' : ''; ?>">
							<a href="<?php echo esc_url( $product['permalink'] ); ?>">
								<?php if ( ! empty( $product['image'] ) ) : ?>
									<img src="<?php echo esc_url( $product['image'] ); ?>" alt="<?php echo esc_attr( $product['name'] ); ?>">
								<?php else : ?>
									<span class="bsc-skin-quiz__product-placeholder" aria-hidden="true"><i class="fas fa-spa"></i></span>
								<?php endif; ?>
								<span><?php echo esc_html( $product['name'] ); ?></span>
								<?php if ( ! empty( $product['brand'] ) ) : ?>
									<small><?php echo esc_html( $product['brand'] ); ?></small>
								<?php endif; ?>
								<em class="bsc-skin-quiz__product-meta">
									<?php echo esc_html( trim( (string) ( $product['price'] ?? '' ) . ' · ' . (string) ( $product['stock_label'] ?? '' ), " \t\n\r\0\x0B·" ) ); ?>
								</em>
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
					<?php echo esc_html( ! empty( $bundle['total_raw'] ) ? 'Agregar rutina - ' . wp_strip_all_tags( $bundle['total_html'] ) : 'Agregar rutina' ); ?>
				</button>
			</div>
		</article>
		<?php
	}

	private static function sanitize_answers( array $request ): array {
		$skin_type          = isset( $request['skin_type'] ) ? sanitize_key( wp_unslash( $request['skin_type'] ) ) : 'mixta';
		$age_range          = isset( $request['age_range'] ) ? sanitize_key( wp_unslash( $request['age_range'] ) ) : 'prefiero-no-decir';
		$sensitivity        = isset( $request['sensitivity'] ) ? sanitize_key( wp_unslash( $request['sensitivity'] ) ) : 'normal';
		$routine_level      = isset( $request['routine_level'] ) ? sanitize_key( wp_unslash( $request['routine_level'] ) ) : 'basica';
		$skin_goal          = isset( $request['skin_goal'] ) ? sanitize_key( wp_unslash( $request['skin_goal'] ) ) : 'tono-uniforme';
		$sunscreen_habit    = isset( $request['sunscreen_habit'] ) ? sanitize_key( wp_unslash( $request['sunscreen_habit'] ) ) : 'diario';
		$post_cleanse_feel  = isset( $request['post_cleanse_feel'] ) ? sanitize_key( wp_unslash( $request['post_cleanse_feel'] ) ) : 'normal';
		$breakout_frequency = isset( $request['breakout_frequency'] ) ? sanitize_key( wp_unslash( $request['breakout_frequency'] ) ) : 'ocasional';
		$needs              = isset( $request['needs'] ) && is_array( $request['needs'] )
			? array_values( array_unique( array_map( 'sanitize_key', wp_unslash( $request['needs'] ) ) ) )
			: array();

		$vision_signals = self::sanitize_vision_signals( $request );
		$age_range      = self::allowed_value( $age_range, array_keys( self::age_range_options() ), 'prefiero-no-decir' );

		return array(
			'skin_type'          => self::allowed_value( $skin_type, array_keys( self::skin_type_options() ), 'mixta' ),
			'age_range'          => $age_range,
			'sensitivity'        => self::allowed_value( $sensitivity, array( 'normal', 'sensible', 'muy-sensible' ), 'normal' ),
			'routine_level'      => self::allowed_value( $routine_level, array( 'basica', 'completa' ), 'basica' ),
			'skin_goal'          => self::allowed_value( $skin_goal, array_keys( self::skin_goal_options() ), 'tono-uniforme' ),
			'sunscreen_habit'    => self::allowed_value( $sunscreen_habit, array( 'diario', 'a-veces', 'no-uso' ), 'diario' ),
			'post_cleanse_feel'  => self::allowed_value( $post_cleanse_feel, array( 'normal', 'tirante', 'brillante', 'variable' ), 'normal' ),
			'breakout_frequency' => self::allowed_value( $breakout_frequency, array( 'raro', 'ocasional', 'frecuente', 'hormonal' ), 'ocasional' ),
			'needs'              => array_slice( $needs, 0, 4 ),
			'vision_signals'     => $vision_signals,
			'age_context'        => self::build_age_context( $age_range, $vision_signals ),
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
			'face_box_x',
			'face_box_y',
			'face_box_w',
			'face_box_h',
			'shine_signal',
			'redness_signal',
			'dark_spot_signal',
			'texture_signal',
			'sharpness',
			'skin_tone_r',
			'skin_tone_g',
			'skin_tone_b',
			'skin_tone_luma',
			'corrected_skin_tone_r',
			'corrected_skin_tone_g',
			'corrected_skin_tone_b',
			'corrected_skin_tone_luma',
			'zone_t_shine',
			'zone_cheek_redness',
			'zone_spot_concentration',
			'zone_forehead_shine',
			'zone_nose_shine',
			'zone_chin_texture',
			'zone_left_cheek_redness',
			'zone_right_cheek_redness',
			'zone_under_eye_shadow',
			'zone_mouth_tint',
			'fine_texture_signal',
			'pores_proxy_signal',
			'blur_proxy_signal',
			'skin_support_signal',
			'lighting_warmth_signal',
			'lighting_coolness_signal',
			'lighting_green_tint_signal',
			'lighting_magenta_tint_signal',
			'lighting_evenness_signal',
			'lighting_cast_signal',
			'lighting_side_delta_signal',
			'lighting_vertical_delta_signal',
			'tone_sample_confidence',
			'symmetry_balance_signal',
			'asymmetric_shadow_signal',
			'asymmetric_redness_signal',
			'asymmetric_spot_signal',
			'left_right_luma_delta',
			'left_right_redness_delta',
			'left_right_spot_delta',
			'spot_cluster_signal',
			'spot_area_signal',
			'spot_largest_signal',
			'red_cluster_signal',
			'shadow_cluster_signal',
			'mask_skin_confidence',
			'oil_control_signal',
			'tone_unevenness_signal',
			'pigment_spot_proxy',
			'lighting_shadow_proxy',
			'spot_shadow_confidence',
			'dryness_signal',
			'barrier_stress_signal',
			'breakout_prone_signal',
			'hydration_need_signal',
			'spf_priority_signal',
			'under_eye_shadow_signal',
			'makeup_match_confidence',
			'progress_tracking_signal',
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

		if ( isset( $data['cosmetic_priority_flags'] ) && is_array( $data['cosmetic_priority_flags'] ) ) {
			$signals['cosmetic_priority_flags'] = array_slice(
				array_values(
					array_filter(
						array_map( 'sanitize_key', $data['cosmetic_priority_flags'] ),
						static fn( string $flag ): bool => '' !== $flag
					)
				),
				0,
				8
			);
		}

		$text = array(
			'image_processing_version',
			'mask_quality',
			'undertone_proxy',
			'skin_depth_proxy',
			'skin_type_proxy',
			'lighting_temperature_proxy',
			'lighting_tint_proxy',
			'lighting_shadow_bias',
			'asymmetry_bias',
			'routine_focus_hint',
			'makeup_lip_hint',
			'makeup_blush_hint',
			'makeup_finish_hint',
			'makeup_foundation_note',
			'makeup_profile_version',
			'makeup_base_finish',
			'makeup_coverage_hint',
			'makeup_color_family',
			'makeup_blush_v2',
			'makeup_lip_v2',
			'makeup_eye_brightness_hint',
			'makeup_texture_strategy',
			'makeup_foundation_depth_hint',
			'makeup_foundation_undertone_hint',
			'progress_tracking_ready',
			'progress_signature',
			'progress_baseline_hint',
		);

		foreach ( $text as $key ) {
			if ( isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ) {
				$signals[ $key ] = sanitize_text_field( (string) $data[ $key ] );
			}
		}

		return $signals;
	}

	private static function build_age_context( string $age_range, array $vision_signals ): array {
		$baseline = array(
			'18-24'             => 0.28,
			'25-34'             => 0.36,
			'35-44'             => 0.46,
			'45-54'             => 0.56,
			'55-plus'           => 0.66,
			'prefiero-no-decir' => 0.46,
		);
		$support_signal = isset( $vision_signals['skin_support_signal'] )
			? (float) $vision_signals['skin_support_signal']
			: max(
				(float) ( $vision_signals['tone_unevenness_signal'] ?? 0 ),
				(float) ( $vision_signals['dryness_signal'] ?? 0 ),
				(float) ( $vision_signals['barrier_stress_signal'] ?? 0 ),
				(float) ( $vision_signals['spf_priority_signal'] ?? 0 )
			);
		$expected       = (float) ( $baseline[ $age_range ] ?? $baseline['prefiero-no-decir'] );
		$delta          = $support_signal - $expected;

		if ( 'prefiero-no-decir' === $age_range || empty( $vision_signals ) ) {
			$alignment = 'age_neutral';
		} elseif ( $delta <= 0.1 ) {
			$alignment = 'aligned_or_lower_support';
		} elseif ( $delta <= 0.24 ) {
			$alignment = 'preventive_extra_support';
		} else {
			$alignment = 'extra_support_recommended';
		}

		return array(
			'age_range'                 => $age_range,
			'skin_support_signal'       => max( 0, min( 1, round( $support_signal, 4 ) ) ),
			'expected_support_baseline' => max( 0, min( 1, round( $expected, 4 ) ) ),
			'alignment'                 => $alignment,
			'language_rule'             => 'No estimes edad aparente ni edad numérica de piel; usa el rango declarado solo para ajustar prevención, barrera, SPF, manchas, textura y maquillaje.',
		);
	}

	private static function build_progress_context( array $answers ): array {
		$signals  = isset( $answers['vision_signals'] ) && is_array( $answers['vision_signals'] ) ? $answers['vision_signals'] : array();
		$current  = self::progress_signal_subset( $signals );
		$previous = self::latest_progress_snapshot();

		$context = array(
			'current'               => $current,
			'has_previous_snapshot' => ! empty( $previous ),
			'language_rule'         => 'Usa progreso solo si hay snapshot previo comparable; no prometas resultados médicos ni cambios garantizados.',
		);

		if ( ! empty( $previous['signals'] ) && is_array( $previous['signals'] ) ) {
			$context['previous_created_at'] = sanitize_text_field( (string) ( $previous['created_at'] ?? '' ) );
			$context['previous_signature']  = sanitize_text_field( (string) ( $previous['signals']['progress_signature'] ?? '' ) );
			$context['comparable']          = ( $current['progress_tracking_ready'] ?? '' ) === 'yes'
				&& ( $previous['signals']['progress_tracking_ready'] ?? '' ) === 'yes';
			$context['delta']               = self::progress_delta( $current, (array) $previous['signals'] );
		}

		return $context;
	}

	private static function latest_progress_snapshot(): array {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return array();
		}

		$snapshots = get_user_meta( $user_id, self::USER_PROGRESS, true );
		$snapshots = is_array( $snapshots ) ? $snapshots : array();

		return isset( $snapshots[0] ) && is_array( $snapshots[0] ) ? $snapshots[0] : array();
	}

	private static function save_customer_progress_snapshot( array $answers ): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 || empty( $answers['vision_signals'] ) || ! is_array( $answers['vision_signals'] ) ) {
			return;
		}

		$snapshot = array(
			'created_at' => current_time( 'mysql' ),
			'age_range'  => sanitize_key( (string) ( $answers['age_range'] ?? '' ) ),
			'signals'    => self::progress_signal_subset( $answers['vision_signals'] ),
		);
		$snapshots = get_user_meta( $user_id, self::USER_PROGRESS, true );
		$snapshots = is_array( $snapshots ) ? $snapshots : array();

		array_unshift( $snapshots, $snapshot );
		update_user_meta( $user_id, self::USER_PROGRESS, array_slice( $snapshots, 0, 6 ) );
	}

	private static function progress_signal_subset( array $signals ): array {
		$numeric = array(
			'oil_control_signal',
			'tone_unevenness_signal',
			'pigment_spot_proxy',
			'lighting_shadow_proxy',
			'dryness_signal',
			'barrier_stress_signal',
			'hydration_need_signal',
			'spf_priority_signal',
			'under_eye_shadow_signal',
			'fine_texture_signal',
			'pores_proxy_signal',
			'mask_skin_confidence',
			'lighting_evenness_signal',
			'progress_tracking_signal',
		);
		$text = array(
			'progress_tracking_ready',
			'progress_signature',
			'progress_baseline_hint',
			'routine_focus_hint',
			'skin_type_proxy',
			'mask_quality',
			'undertone_proxy',
			'skin_depth_proxy',
		);
		$subset = array();

		foreach ( $numeric as $key ) {
			if ( isset( $signals[ $key ] ) && is_numeric( $signals[ $key ] ) ) {
				$subset[ $key ] = max( 0, min( 1, round( (float) $signals[ $key ], 4 ) ) );
			}
		}

		foreach ( $text as $key ) {
			if ( isset( $signals[ $key ] ) && is_scalar( $signals[ $key ] ) ) {
				$subset[ $key ] = sanitize_text_field( (string) $signals[ $key ] );
			}
		}

		if ( isset( $signals['cosmetic_priority_flags'] ) && is_array( $signals['cosmetic_priority_flags'] ) ) {
			$subset['cosmetic_priority_flags'] = array_slice( array_values( array_map( 'sanitize_key', $signals['cosmetic_priority_flags'] ) ), 0, 8 );
		}

		return $subset;
	}

	private static function progress_delta( array $current, array $previous ): array {
		$keys  = array( 'oil_control_signal', 'tone_unevenness_signal', 'pigment_spot_proxy', 'dryness_signal', 'barrier_stress_signal', 'hydration_need_signal', 'fine_texture_signal' );
		$delta = array();

		foreach ( $keys as $key ) {
			if ( isset( $current[ $key ], $previous[ $key ] ) && is_numeric( $current[ $key ] ) && is_numeric( $previous[ $key ] ) ) {
				$value         = round( (float) $current[ $key ] - (float) $previous[ $key ], 4 );
				$delta[ $key ] = max( -1, min( 1, $value ) );
			}
		}

		return $delta;
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
		update_user_meta( $user_id, 'bsc_age_range', $answers['age_range'] ?? '' );
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

	private static function get_shared_routine_payload(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public share token is read-only and does not expose selfies.
		$token = isset( $_GET['routine_run'] ) ? sanitize_text_field( wp_unslash( $_GET['routine_run'] ) ) : '';
		if ( '' === $token ) {
			return array();
		}

		$run = BSC_Growth_Skin_Quiz_Store::get_run_by_token( $token );
		if ( ! $run ) {
			return array();
		}

		$repository = new BSC_Growth_Bundle_Repository();
		$routine    = is_array( $run['ai']['routine'] ?? null ) ? (array) $run['ai']['routine'] : array();
		$product_ids = wp_parse_id_list( ! empty( $routine['product_ids'] ) ? (array) $routine['product_ids'] : (string) $run['product_ids'] );
		$products    = array_values( array_filter( (array) ( $routine['products'] ?? array() ), 'is_array' ) );
		$steps       = array_values( array_filter( (array) ( $routine['steps'] ?? array() ), 'is_array' ) );

		if ( ! empty( $products ) ) {
			$total_raw = (float) ( $routine['total_raw'] ?? $run['total_raw'] ?? 0 );
			$bundle    = array(
				'id'             => 'shared-run-' . (int) $run['id'],
				'title'          => $run['bundle_title'] ? $run['bundle_title'] : 'Rutina Skin Quiz',
				'summary'        => 'Rutina guardada de Skin Quiz, lista para agregar al carrito.',
				'badge'          => 'Skin Quiz',
				'discount_label' => 'Carrito listo',
				'url'            => home_url( '/skin-quiz/' ),
				'product_count'  => count( $product_ids ),
				'product_ids'    => $product_ids,
				'products'       => $products,
				'total_raw'      => $total_raw,
				'total_html'     => BSC_Growth_Plugin::price_html( $total_raw ),
				'steps'          => $steps,
			);
		} else {
			$bundle = $repository->format_dynamic_bundle_for_response(
				array(
					'id'             => 'shared-run-' . (int) $run['id'],
					'title'          => $run['bundle_title'] ? $run['bundle_title'] : 'Rutina Skin Quiz',
					'summary'        => 'Rutina guardada de Skin Quiz, lista para agregar al carrito.',
					'badge'          => 'Skin Quiz',
					'discount_label' => 'Carrito listo',
					'product_ids'    => $product_ids,
					'steps'          => $steps,
				)
			);

			if ( empty( $bundle['product_ids'] ) && ! empty( $product_ids ) ) {
				$bundle['product_ids']   = $product_ids;
				$bundle['product_count'] = count( $product_ids );
				$bundle['total_raw']     = (float) ( $run['total_raw'] ?? 0 );
				$bundle['total_html']    = BSC_Growth_Plugin::price_html( (float) ( $run['total_raw'] ?? 0 ) );
			}
		}

		return array(
			'mode'    => $run['mode'],
			'run_id'  => (int) $run['id'],
			'bundles' => self::attach_run_meta_to_bundles( array( $bundle ), $run ),
			'ai'      => array_diff_key( (array) $run['ai'], array( 'after_image_data_uri' => true ) ),
		);
	}

	private static function attach_run_meta_to_bundles( array $bundles, ?array $run ): array {
		if ( ! $run ) {
			return $bundles;
		}

		return array_map(
			static function ( array $bundle ) use ( $run ): array {
				$bundle['run_id']       = (int) $run['id'];
				$bundle['share_token']  = (string) ( $run['share_token'] ?? '' );
				$bundle['share_url']    = BSC_Growth_Skin_Quiz_Store::share_url( $run );
				$bundle['whatsapp_url'] = BSC_Growth_Skin_Quiz_Store::whatsapp_url( $run );

				return $bundle;
			},
			$bundles
		);
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
			'routine_send',
			'routine_share',
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
		BSC_Growth_Skin_Quiz_Store::record_event( $event, $mode, $meta );
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

	private static function age_range_options(): array {
		return array(
			'prefiero-no-decir' => 'Prefiero no decir',
			'18-24'             => '18 a 24',
			'25-34'             => '25 a 34',
			'35-44'             => '35 a 44',
			'45-54'             => '45 a 54',
			'55-plus'           => '55+',
		);
	}

	private static function need_options(): array {
		return array(
			'acne'            => 'Acné',
			'manchas'         => 'Manchas',
			'hidratacion'     => 'Hidratación',
			'barrera'         => 'Barrera',
			'glow'            => 'Glow',
			'protector-solar' => 'Protector solar',
		);
	}

	private static function allowed_value( string $value, array $allowed, string $fallback ): string {
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
