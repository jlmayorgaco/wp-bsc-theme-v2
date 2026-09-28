<?php
/**
 * PHPStan bootstrap for theme analysis.
 *
 * @package BSC2
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'plugins' );
}

if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'mu-plugins' );
}

if ( ! defined( 'BSC_THEME_VERSION' ) ) {
	define( 'BSC_THEME_VERSION', '2.1.3' );
}
