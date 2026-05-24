<?php
/**
 * BSC2 Theme Customizer
 *
 * @package BSC2
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function bsc_2_0_customize_register( $wp_customize ) {
	$wp_customize->get_setting( 'blogname' )->transport         = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport  = 'postMessage';
	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	if ( isset( $wp_customize->selective_refresh ) ) {
		$wp_customize->selective_refresh->add_partial(
			'blogname',
			array(
				'selector'        => '.site-title a',
				'render_callback' => 'bsc_2_0_customize_partial_blogname',
			)
		);
		$wp_customize->selective_refresh->add_partial(
			'blogdescription',
			array(
				'selector'        => '.site-description',
				'render_callback' => 'bsc_2_0_customize_partial_blogdescription',
			)
		);
	}
}
add_action( 'customize_register', 'bsc_2_0_customize_register' );

/**
 * Render the site title for the selective refresh partial.
 *
 * @return void
 */
function bsc_2_0_customize_partial_blogname() {
	bloginfo( 'name' );
}

/**
 * Render the site tagline for the selective refresh partial.
 *
 * @return void
 */
function bsc_2_0_customize_partial_blogdescription() {
	bloginfo( 'description' );
}

/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function bsc_2_0_customize_preview_js() {
	wp_enqueue_script( 'bsc-2-0-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), _S_VERSION, true );
}
add_action( 'customize_preview_init', 'bsc_2_0_customize_preview_js' );


add_action(
	'template_redirect',
	function () {

		// No hacer nada en admin
		if ( is_admin() ) {
			return;
		}

		// Ruta actual sin parámetros (?foo=bar)
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$current_path = strtok( $request_uri, '?' );

		// Normalizamos quitando slash final: /mi-cuenta/ → /mi-cuenta
		$current_path = rtrim( $current_path, '/' );

		// Solo si la ruta es EXACTAMENTE /mi-cuenta
		if ( $current_path === '/mi-cuenta' ) {

			// URL del endpoint "orders" dentro de Mi Cuenta
			$orders_url = wc_get_account_endpoint_url( 'orders' );

			// Redirigimos de forma segura
			wp_safe_redirect( $orders_url );
			exit;
		}
	}
);
