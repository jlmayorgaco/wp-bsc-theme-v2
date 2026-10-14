<?php
// plugins/bubble-points/includes/install.php

if (!defined( 'ABSPATH' )) {
	exit;
}

if (!defined( 'BSC_BP_DB_VERSION' )) {
	define( 'BSC_BP_DB_VERSION', '1.1.0' );
}

if (!function_exists( 'bsc_bp_install_schema' )) {
	function bsc_bp_install_schema() {
		global $wpdb;

		$table   = $wpdb->prefix . 'bsc_points_ledger';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            delta INT NOT NULL,
            balance_after INT NOT NULL,
            reason VARCHAR(64) NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            event_key VARCHAR(191) NULL,
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_created (user_id, created_at),
            KEY order_idx (order_id),
            UNIQUE KEY event_key (event_key)
        ) ENGINE=InnoDB $charset;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$index = $wpdb->get_row( "SHOW INDEX FROM {$table} WHERE Key_name = 'event_key'" );
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'event_key' ) ) && $index && 0 === (int) $index->Non_unique ) {
			update_option( 'bsc_bp_db_version', BSC_BP_DB_VERSION );
		}
	}
}

if (!function_exists( 'bsc_bp_maybe_install' )) {
	function bsc_bp_maybe_install() {
		$installed = get_option( 'bsc_bp_db_version' );
		if ($installed !== BSC_BP_DB_VERSION) {
			bsc_bp_install_schema();
		}
	}
}
