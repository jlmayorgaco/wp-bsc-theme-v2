<?php
/**
 * Admin reporting and settings for Skin Quiz Pro.
 */
defined( 'ABSPATH' ) || exit;

class BSC_Growth_Skin_Quiz_Admin {
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 31 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_settings_save' ) );
	}

	public static function register_admin_menu(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		add_submenu_page(
			'bsc-dashboard',
			'Skin Quiz BSC',
			'Skin Quiz',
			'manage_woocommerce',
			'bsc-skin-quiz',
			array( __CLASS__, 'render_page' )
		);
	}

	public static function enqueue_assets(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-skin-quiz' !== $page ) {
			return;
		}

		if ( function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
			bsc_enqueue_admin_ui_assets();
		}

		wp_enqueue_style(
			'bsc-growth-admin',
			get_template_directory_uri() . '/admin/bsc-growth.css',
			array( 'bsc-admin-ui' ),
			BSC_Growth_Plugin::admin_css_version()
		);
	}

	public static function handle_settings_save(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Routing parameter; POST is verified below.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( 'bsc-skin-quiz' !== $page || ! isset( $_POST['bsc_skin_quiz_settings_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
		}

		check_admin_referer( 'bsc_skin_quiz_settings_save', 'bsc_skin_quiz_settings_nonce' );

		update_option( 'bsc_gemini_generate_after_image', empty( $_POST['generate_after_image'] ) ? 0 : 1, false );
		update_option( 'bsc_skin_quiz_ab_testing_enabled', empty( $_POST['ab_testing_enabled'] ) ? 0 : 1, false );
		update_option( 'bsc_skin_quiz_photo_retention_days', max( 1, absint( $_POST['photo_retention_days'] ?? 90 ) ), false );
		update_option( 'bsc_skin_quiz_usd_to_cop', max( 1, (float) ( $_POST['usd_to_cop'] ?? 4000 ) ), false );
		update_option( 'bsc_skin_quiz_analysis_input_usd_m', max( 0, (float) ( $_POST['analysis_input_usd_m'] ?? 1.5 ) ), false );
		update_option( 'bsc_skin_quiz_analysis_output_usd_m', max( 0, (float) ( $_POST['analysis_output_usd_m'] ?? 9 ) ), false );
		update_option( 'bsc_skin_quiz_image_base_usd', max( 0, (float) ( $_POST['image_base_usd'] ?? 0.039 ) ), false );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'bsc-skin-quiz',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin filters.
		$range_key = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '7d';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$range   = self::range( $range_key );
		$summary = BSC_Growth_Skin_Quiz_Store::summary( $range['start_date'], $range['end_date'] );
		$runs    = BSC_Growth_Skin_Quiz_Store::recent_runs( 40 );
		?>
		<div class="wrap bsc-growth-admin">
			<h1>Skin Quiz BSC</h1>
			<p class="bsc-growth-admin__intro">Diagnósticos, costos de Gemini, productos recomendados y embudo del Skin Quiz.</p>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only success notice. ?>
			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>Configuración guardada.</p></div>
			<?php endif; ?>

			<form class="bsc-growth-admin__toolbar" method="get">
				<input type="hidden" name="page" value="bsc-skin-quiz">
				<label>
					<span>Rango</span>
					<select name="range">
						<option value="today" <?php selected( $range['key'], 'today' ); ?>>Hoy</option>
						<option value="7d" <?php selected( $range['key'], '7d' ); ?>>Últimos 7 días</option>
						<option value="30d" <?php selected( $range['key'], '30d' ); ?>>Últimos 30 días</option>
					</select>
				</label>
				<button class="button button-primary" type="submit">Aplicar</button>
			</form>

			<div class="bsc-growth-admin__metrics-grid">
				<?php self::metric( 'Runs totales', number_format_i18n( (int) $summary['total_runs'] ) ); ?>
				<?php self::metric( 'AI / Normal', number_format_i18n( (int) $summary['ai_runs'] ) . ' / ' . number_format_i18n( (int) $summary['normal_runs'] ) ); ?>
				<?php self::metric( 'Fallbacks', number_format_i18n( (int) $summary['fallbacks'] ) ); ?>
				<?php self::metric( 'Costo Gemini', '$' . number_format_i18n( (float) $summary['cost_cop'], 0 ) . ' COP' ); ?>
			</div>

			<section class="bsc-growth-admin__card">
				<h2>Embudo Skin Quiz</h2>
				<div class="bsc-growth-admin__funnel">
					<?php foreach ( self::funnel( $summary['events'] ) as $step ) : ?>
						<div class="bsc-growth-admin__funnel-step">
							<span><?php echo esc_html( $step['label'] ); ?></span>
							<strong><?php echo esc_html( number_format_i18n( $step['value'] ) ); ?></strong>
							<small><?php echo esc_html( $step['rate'] ); ?></small>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="bsc-growth-admin__layout">
				<section class="bsc-growth-admin__card">
					<h2>Runs recientes</h2>
					<table class="widefat striped bsc-growth-admin__table">
						<thead>
							<tr>
								<th>Fecha</th>
								<th>Cliente</th>
								<th>Rutina</th>
								<th>Piel</th>
								<th>Costo</th>
								<th>Foto</th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $runs ) ) : ?>
								<tr><td colspan="6">Sin runs todavía.</td></tr>
							<?php endif; ?>
							<?php foreach ( $runs as $run ) : ?>
								<tr>
									<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $run['created_at'] ) ); ?></td>
									<td>
										<?php echo esc_html( $run['email'] ? $run['email'] : 'Visitante' ); ?>
										<?php if ( ! empty( $run['fallback'] ) ) : ?><br><span class="bsc-growth-admin__muted">Fallback</span><?php endif; ?>
									</td>
									<td>
										<a href="<?php echo esc_url( BSC_Growth_Skin_Quiz_Store::share_url( $run ) ); ?>" target="_blank" rel="noreferrer">
											<?php echo esc_html( $run['bundle_title'] ? $run['bundle_title'] : 'Rutina BSC' ); ?>
										</a>
									</td>
									<td><?php echo esc_html( $run['skin_type'] ? $run['skin_type'] : 'Sin dato' ); ?></td>
									<td>
										<?php echo esc_html( number_format_i18n( (int) ( $run['usage']['total_tokens'] ?? 0 ) ) ); ?> tokens<br>
										<span class="bsc-growth-admin__muted">$<?php echo esc_html( number_format_i18n( (float) ( $run['usage']['cost_cop'] ?? 0 ), 0 ) ); ?> COP</span>
									</td>
									<td>
										<?php if ( ! empty( $run['image_path'] ) ) : ?>
											<a href="<?php echo esc_url( BSC_Growth_Skin_Quiz_Store::image_url( (int) $run['id'] ) ); ?>" target="_blank" rel="noreferrer">Ver</a>
										<?php else : ?>
											<span class="bsc-growth-admin__muted">No guardada</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>

				<aside class="bsc-growth-admin__card">
					<h2>Configuración</h2>
					<form method="post" class="bsc-growth-admin__settings">
						<?php wp_nonce_field( 'bsc_skin_quiz_settings_save', 'bsc_skin_quiz_settings_nonce' ); ?>
						<label>
							<input type="checkbox" name="generate_after_image" value="1" <?php checked( (bool) get_option( 'bsc_gemini_generate_after_image', 1 ) ); ?>>
							<span>Generar imagen “Después”</span>
						</label>
						<label>
							<input type="checkbox" name="ab_testing_enabled" value="1" <?php checked( (bool) get_option( 'bsc_skin_quiz_ab_testing_enabled', 0 ) ); ?>>
							<span>A/B testing activo</span>
						</label>
						<label>
							<span>Retención de fotos</span>
							<input type="number" name="photo_retention_days" min="1" value="<?php echo esc_attr( (string) BSC_Growth_Skin_Quiz_Store::retention_days() ); ?>">
						</label>
						<label>
							<span>USD a COP</span>
							<input type="number" name="usd_to_cop" min="1" step="1" value="<?php echo esc_attr( (string) get_option( 'bsc_skin_quiz_usd_to_cop', 4000 ) ); ?>">
						</label>
						<label>
							<span>Input USD / 1M tokens</span>
							<input type="number" name="analysis_input_usd_m" min="0" step="0.01" value="<?php echo esc_attr( (string) get_option( 'bsc_skin_quiz_analysis_input_usd_m', 1.5 ) ); ?>">
						</label>
						<label>
							<span>Output USD / 1M tokens</span>
							<input type="number" name="analysis_output_usd_m" min="0" step="0.01" value="<?php echo esc_attr( (string) get_option( 'bsc_skin_quiz_analysis_output_usd_m', 9 ) ); ?>">
						</label>
						<label>
							<span>Imagen USD base</span>
							<input type="number" name="image_base_usd" min="0" step="0.001" value="<?php echo esc_attr( (string) get_option( 'bsc_skin_quiz_image_base_usd', 0.039 ) ); ?>">
						</label>
						<button type="submit" class="button button-primary">Guardar</button>
					</form>

					<h3>Más recomendados</h3>
					<ul class="bsc-growth-admin__plain-list">
						<?php foreach ( array_slice( $summary['product_counts'], 0, 8, true ) as $product_id => $count ) : ?>
							<?php $product = wc_get_product( (int) $product_id ); ?>
							<li>
								<?php echo esc_html( $product ? wp_strip_all_tags( $product->get_name() ) : 'Producto #' . (int) $product_id ); ?>
								<span class="bsc-growth-admin__muted"><?php echo esc_html( number_format_i18n( (int) $count ) ); ?> veces</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</aside>
			</div>
		</div>
		<?php
	}

	private static function metric( string $label, string $value ): void {
		?>
		<div class="bsc-growth-admin__metric">
			<span><?php echo esc_html( $label ); ?></span>
			<strong><?php echo esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	private static function range( string $key ): array {
		if ( ! in_array( $key, array( 'today', '7d', '30d' ), true ) ) {
			$key = '7d';
		}

		$now   = current_time( 'timestamp' );
		$today = wp_date( 'Y-m-d', $now );
		$start = $today;

		if ( '7d' === $key ) {
			$start = wp_date( 'Y-m-d', strtotime( '-6 days', $now ) );
		} elseif ( '30d' === $key ) {
			$start = wp_date( 'Y-m-d', strtotime( '-29 days', $now ) );
		}

		return array(
			'key'        => $key,
			'start_date' => $start,
			'end_date'   => $today,
		);
	}

	private static function funnel( array $events ): array {
		$view   = (int) ( $events['quiz_view'] ?? 0 );
		$photo  = (int) ( $events['photo_selected'] ?? 0 ) + (int) ( $events['camera_captured'] ?? 0 );
		$submit = (int) ( $events['quiz_submit'] ?? 0 );
		$result = (int) ( $events['quiz_result'] ?? 0 );
		$cart   = (int) ( $events['routine_add_to_cart'] ?? 0 );

		return array(
			array( 'label' => 'Visitas', 'value' => $view, 'rate' => 'Base' ),
			array( 'label' => 'Foto', 'value' => $photo, 'rate' => self::rate( $photo, $view ) ),
			array( 'label' => 'Submit', 'value' => $submit, 'rate' => self::rate( $submit, $photo ) ),
			array( 'label' => 'Resultado', 'value' => $result, 'rate' => self::rate( $result, $submit ) ),
			array( 'label' => 'Carrito', 'value' => $cart, 'rate' => self::rate( $cart, $result ) ),
		);
	}

	private static function rate( int $numerator, int $denominator ): string {
		return $denominator > 0 ? number_format_i18n( ( $numerator / $denominator ) * 100, 1 ) . '%' : '0.0%';
	}
}
