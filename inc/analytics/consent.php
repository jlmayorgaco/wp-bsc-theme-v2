<?php
/**
 * Storefront consent preferences and Meta for WooCommerce tracking gate.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

const BSC_META_PRIVATE_VIEW = 'bsc_meta_view';

function bsc_meta_has_consent(): bool {
	return 'granted' === ( $_COOKIE['bsc_meta_consent'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput -- Only the literal consent value is accepted.
}

function bsc_meta_is_private_view(): bool {
	return isset( $_GET[ BSC_META_PRIVATE_VIEW ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Cache routing only.
}

function bsc_meta_tracking_allowed(): bool {
	if ( ! bsc_meta_has_consent() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		return false;
	}

	if ( wp_doing_ajax() ) {
		return true;
	}

	if ( is_admin() ) {
		return false;
	}

	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Compared to fixed method names.

	// Keep tracking out of HTML that the public page cache can share.
	return 'POST' === $method || ( 'GET' === $method && bsc_meta_is_private_view() );
}

function bsc_meta_filter_tracking( $enabled ): bool {
	return $enabled && bsc_meta_tracking_allowed();
}
add_filter( 'facebook_for_woocommerce_integration_pixel_enabled', 'bsc_meta_filter_tracking', PHP_INT_MAX );

function bsc_meta_remove_signals_script(): void {
	if ( ! bsc_meta_tracking_allowed() ) {
		wp_dequeue_script( 'wc-facebook-signals' );
	}
}
add_action( 'wp_enqueue_scripts', 'bsc_meta_remove_signals_script', PHP_INT_MAX );

function bsc_meta_gate_signals_ajax(): void {
	if ( ! bsc_meta_tracking_allowed() ) {
		wp_send_json_error( array( 'message' => 'Se requiere permiso para Publicidad de Meta.' ), 403 );
	}
}

// The plugin registers these AJAX endpoints independently of the tracker.
foreach ( array( 'facebook_release_signals', 'wc_facebook_update_signals_state' ) as $bsc_meta_action ) {
	add_action( 'wp_ajax_' . $bsc_meta_action, 'bsc_meta_gate_signals_ajax', 0 );
	add_action( 'wp_ajax_nopriv_' . $bsc_meta_action, 'bsc_meta_gate_signals_ajax', 0 );
}
unset( $bsc_meta_action );

function bsc_meta_gate_conversion_requests( $preempt, array $request, string $url ) {
	if ( 'POST' !== strtoupper( $request['method'] ?? 'GET' ) || bsc_meta_tracking_allowed() ) {
		return $preempt;
	}

	$host = wp_parse_url( $url, PHP_URL_HOST );
	$path = wp_parse_url( $url, PHP_URL_PATH );
	if ( 'graph.facebook.com' === $host && is_string( $path ) && preg_match( '~^/(?:v\d+(?:\.\d+)?/)?\d+/events/?$~D', $path ) ) {
		return new WP_Error( 'bsc_meta_consent_required', 'Se requiere permiso para Publicidad de Meta.' );
	}
	return $preempt;
}
// Cover CAPI sends independently of the tracker and leave catalog requests alone.
add_filter( 'pre_http_request', 'bsc_meta_gate_conversion_requests', PHP_INT_MAX, 3 );

function bsc_cookie_preferences_render(): void {
	$analytics = bsc_ga4_consent_required() && '' !== bsc_ga4_get_measurement_id();
	$meta      = function_exists( 'facebook_for_woocommerce' );
	if ( ! $analytics && ! $meta ) {
		return;
	}
	?>
	<section class="bsc-analytics-consent" id="bsc-cookie-preferences" data-bsc-cookie-panel data-meta-private-view="<?php echo esc_attr( BSC_META_PRIVATE_VIEW ); ?>" role="dialog" aria-labelledby="bsc-cookie-title" aria-describedby="bsc-cookie-description" hidden>
		<div>
			<h2 id="bsc-cookie-title" tabindex="-1">Preferencias de cookies</h2>
			<p id="bsc-cookie-description">Elige qué cookies opcionales quieres permitir. Las esenciales mantienen activa tu sesión y tu carrito. <a href="<?php echo esc_url( bsc_get_static_page_url( 'cookies' ) ); ?>">Política de cookies</a></p>
		</div>
		<form class="bsc-analytics-consent__form">
			<div class="bsc-analytics-consent__options">
				<?php if ( $analytics ) : ?>
					<label><input type="checkbox" name="bsc_analytics_consent" aria-describedby="bsc-cookie-analytics-description"> <strong>Analítica</strong></label>
					<p id="bsc-cookie-analytics-description">Google Analytics nos ayuda a entender cómo se usa la tienda.</p>
				<?php endif; ?>
				<?php if ( $meta ) : ?>
					<label><input type="checkbox" name="bsc_meta_consent" aria-describedby="bsc-cookie-meta-description"> <strong>Publicidad de Meta</strong></label>
					<p id="bsc-cookie-meta-description">Compartimos actividad de la tienda con Meta, desde tu navegador y nuestro servidor, para medir y personalizar anuncios en Facebook e Instagram.</p>
				<?php endif; ?>
			</div>
			<div class="bsc-analytics-consent__actions">
				<button type="button" class="bsc__button bsc__button--outline" data-bsc-cookie-reject>Rechazar opcionales</button>
				<button type="submit" class="bsc__button">Guardar preferencias</button>
			</div>
		</form>
	</section>
	<?php
}
add_action( 'wp_footer', 'bsc_cookie_preferences_render', 4 );
