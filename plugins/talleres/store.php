<?php
defined( 'ABSPATH' ) || exit;

function bsc_workshops_table(): string {
	global $wpdb;
	return $wpdb->prefix . 'bsc_workshop_bookings';
}

add_action( 'init', 'bsc_workshops_install', 11 );
function bsc_workshops_install(): void {
	if ( '1' === get_option( 'bsc_workshops_db_version' ) ) {
		return;
	}
	BSC_DB_Lock::run( 'workshops-schema', static function () {
		global $wpdb;
		$table   = bsc_workshops_table();
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE {$table} (
			id bigint unsigned NOT NULL AUTO_INCREMENT,
			workshop_id bigint unsigned NOT NULL,
			slot_key varchar(36) NOT NULL DEFAULT '',
			email_hash char(64) NOT NULL,
			name varchar(120) NOT NULL,
			email varchar(254) NOT NULL,
			seats smallint unsigned NOT NULL DEFAULT 0,
			amount int unsigned NOT NULL DEFAULT 0,
			status varchar(12) NOT NULL,
			created_at bigint unsigned NOT NULL,
			receipt_sent bigint unsigned NOT NULL DEFAULT 0,
			confirmation_sent bigint unsigned NOT NULL DEFAULT 0,
			reminder_for bigint unsigned NOT NULL DEFAULT 0,
			reminder_sent bigint unsigned NOT NULL DEFAULT 0,
			notice_sent bigint unsigned NOT NULL DEFAULT 0,
			mail_due bigint unsigned NOT NULL DEFAULT 0,
			force_reminder tinyint unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY workshop_guest (workshop_id,slot_key,email_hash),
			KEY workshop_slots (workshop_id,slot_key,status),
			KEY email_hash (email_hash),
			KEY mail_due (mail_due)
		) {$charset};" );
		if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			update_option( 'bsc_workshops_db_version', '1', false );
		}
	} );
}

function bsc_workshops_get( int $id ): ?WP_Post {
	$post = $id > 0 ? get_post( $id ) : null;
	if ( ! $post instanceof WP_Post || 'bsc_workshop' !== $post->post_type ) {
		return null;
	}
	return 'local' === wp_get_environment_type() || ! bsc_workshops_is_demo( $id ) ? $post : null;
}

function bsc_workshops_is_demo( int $id ): bool {
	return (bool) get_post_meta( $id, '_bsc_workshop_demo', true );
}

function bsc_workshops_posts( array $statuses ): array {
	$args = array( 'post_type' => 'bsc_workshop', 'post_status' => $statuses, 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' );
	if ( 'local' !== wp_get_environment_type() ) {
		$args['meta_query'] = array( array( 'key' => '_bsc_workshop_demo', 'compare' => 'NOT EXISTS' ) );
	}
	return get_posts( $args );
}

function bsc_workshops_settings( int $id ): array {
	$data = $id > 0 ? get_post_meta( $id, '_bsc_workshop_settings', true ) : null;
	$defaults = array( 'price' => 0, 'venue' => '', 'image' => 0, 'slots' => array() );
	return is_array( $data ) ? $data + $defaults : $defaults;
}

function bsc_workshops_counts( int $id ): array {
	global $wpdb;
	$table = bsc_workshops_table();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT slot_key, SUM(seats) AS used FROM {$table} WHERE workshop_id = %d AND status IN ('pending','confirmed') GROUP BY slot_key", $id ) );
	$counts = array();
	foreach ( (array) $rows as $row ) {
		$counts[ $row->slot_key ] = (int) $row->used;
	}
	return $counts;
}

function bsc_workshops_available( array $slot, int $used ): int {
	if ( empty( $slot['open'] ) || ( $slot['start'] && $slot['start'] <= time() ) ) {
		return 0;
	}
	return max( 0, $slot['capacity'] - $used );
}

function bsc_workshops_open_slots( array $settings, array $counts ): array {
	return array_filter( $settings['slots'], static function ( $slot, $key ) use ( $counts ) {
		return bsc_workshops_available( $slot, $counts[ $key ] ?? 0 ) > 0;
	}, ARRAY_FILTER_USE_BOTH );
}

function bsc_workshops_price( int $price ): string {
	return bsc_workshops_price_label( $price ) . ' COP';
}

function bsc_workshops_price_label( int $price ): string {
	return '$' . number_format( $price, 0, ',', '.' );
}

function bsc_workshops_seat_label( int $seats ): string {
	if ( 1 === $seats ) {
		return '¡Queda el último cupo!';
	}
	return sprintf( $seats <= 3 ? '¡Quedan los últimos %d cupos!' : 'Quedan %d cupos', $seats );
}

function bsc_workshops_register_guest( int $id, string $key, string $name, string $email, int $seats ) {
	$result = BSC_DB_Lock::run( 'workshop:' . $id, static function () use ( $id, $key, $name, $email, $seats ) {
		global $wpdb;
		wp_cache_delete( $id, 'posts' );
		wp_cache_delete( $id, 'post_meta' );
		$post = bsc_workshops_get( $id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return 'closed';
		}
		$table = bsc_workshops_table();
		$hash  = hash( 'sha256', $email );
		$prior = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$table} WHERE workshop_id = %d AND slot_key = %s AND email_hash = %s", $id, $key, $hash ) );
		if ( $prior && 'cancelled' !== $prior->status ) {
			return 'existing';
		}

		$settings = bsc_workshops_settings( $id );
		$counts   = bsc_workshops_counts( $id );
		if ( '' === $key ) {
			if ( bsc_workshops_open_slots( $settings, $counts ) ) {
				return 'available';
			}
			$status = 'waiting';
		} else {
			$slot = $settings['slots'][ $key ] ?? null;
			if ( ! $slot || ! $slot['open'] || ( $slot['start'] && $slot['start'] <= time() ) ) {
				return 'closed';
			}
			if ( $seats < 1 || $seats > bsc_workshops_available( $slot, $counts[ $key ] ?? 0 ) ) {
				return 'full';
			}
			$status = $settings['price'] > 0 ? 'pending' : 'confirmed';
		}
		$data = array(
			'workshop_id'       => $id,
			'slot_key'          => $key,
			'email_hash'        => $hash,
			'name'              => $name,
			'email'             => $email,
			'seats'             => $seats,
			'amount'            => $settings['price'] * $seats,
			'status'            => $status,
			'created_at'        => time(),
			'receipt_sent'      => 0,
			'confirmation_sent' => 0,
			'reminder_for'      => 0,
			'reminder_sent'     => 0,
			'notice_sent'       => 0,
			'mail_due'          => 'waiting' === $status ? 0 : time(),
			'force_reminder'    => 0,
		);
		$saved = $prior ? $wpdb->update( $table, $data, array( 'id' => $prior->id ) ) : $wpdb->insert( $table, $data );
		return false === $saved ? 'failed' : $status;
	} );
	return is_wp_error( $result ) ? 'failed' : $result;
}
