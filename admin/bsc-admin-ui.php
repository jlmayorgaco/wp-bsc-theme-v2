<?php
/**
 * Shared admin UI helpers.
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bsc_enqueue_admin_ui_assets' ) ) {
	function bsc_enqueue_admin_ui_assets(): void {
		$css_path = get_template_directory() . '/admin/bsc-admin-ui.css';

		wp_enqueue_style(
			'bsc-admin-ui',
			get_template_directory_uri() . '/admin/bsc-admin-ui.css',
			array(),
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
		);
	}
}
