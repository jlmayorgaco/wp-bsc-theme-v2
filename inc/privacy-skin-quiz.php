<?php
/** Retention and WordPress privacy tools for the skin questionnaire. */
defined( 'ABSPATH' ) || exit;

function bsc_skin_quiz_clear_profile( int $user_id ): void {
	foreach ( array( 'bsc_skin_type', 'bsc_sensitivity', 'bsc_needs1', 'bsc_needs2', 'bsc_needs3', 'bsc_needs4', 'bsc_needs5', 'bsc_needs6', 'bsc_skin_goal', 'bsc_age_range', 'bsc_sunscreen_habit', 'bsc_post_cleanse_feel', 'bsc_breakout_frequency', 'bsc_skin_quiz_last_routine' ) as $key ) {
		delete_user_meta( $user_id, $key );
	}
}

add_action( 'bsc_skin_quiz_run_deleted', static function ( $run_id, $run ) {
	$user_id = (int) ( $run['user_id'] ?? 0 );
	if ( ! $user_id ) { return; }
	$routine = get_user_meta( $user_id, 'bsc_skin_quiz_last_routine', true );
	if ( (int) ( $routine['run_id'] ?? 0 ) === (int) $run_id ) {
		delete_user_meta( $user_id, 'bsc_skin_quiz_last_routine' );
	}
	if ( ! BSC_Growth_Skin_Quiz_Store::get_user_runs( $user_id, 1 ) ) {
		bsc_skin_quiz_clear_profile( $user_id );
	}
}, 10, 2 );

add_action( 'bsc_skin_quiz_cleanup', static function () {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s", 'bsc_skin_quiz_last_routine' ) );
	foreach ( $ids as $user_id ) {
		$routine = (array) get_user_meta( $user_id, 'bsc_skin_quiz_last_routine', true );
		$expiry = (int) ( $routine['expires_at'] ?? ( strtotime( $routine['created_at'] ?? '1970-01-01' ) + BSC_Growth_Skin_Quiz_Store::retention_days() * DAY_IN_SECONDS ) );
		if ( $expiry <= time() ) { bsc_skin_quiz_clear_profile( (int) $user_id ); }
	}
}, 20 );

function bsc_skin_quiz_privacy_erase( string $email, int $page = 1 ): array {
	global $wpdb;
	$table = BSC_Growth_Skin_Quiz_Store::runs_table();
	$user = get_user_by( 'email', $email );
	$user_id = $user ? (int) $user->ID : -1;
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s OR user_id = %d ORDER BY id LIMIT 50", $email, $user_id ) );
	$removed = 0; $retained = false;
	foreach ( $ids as $id ) {
		if ( BSC_Growth_Skin_Quiz_Store::delete_run( (int) $id ) ) { ++$removed; } else { $retained = true; }
	}
	if ( $user ) {
		bsc_skin_quiz_clear_profile( $user_id );
		if ( false === $wpdb->delete( BSC_Growth_Skin_Quiz_Store::events_table(), array( 'user_id' => $user_id ), array( '%d' ) ) ) { $retained = true; }
	}
	delete_option( 'bsc_skin_quiz_submissions' );
	return array( 'items_removed' => $removed > 0 || (bool) $user, 'items_retained' => $retained, 'messages' => $retained ? array( 'No se pudo eliminar toda la información. Revisar permisos de la carpeta privada y migración de imágenes; después repetir el borrado.' ) : array(), 'done' => $retained || count( $ids ) < 50 );
}

function bsc_skin_quiz_privacy_export( string $email, int $page = 1 ): array {
	global $wpdb;
	$table = BSC_Growth_Skin_Quiz_Store::runs_table();
	$user = get_user_by( 'email', $email );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, created_at, expires_at, answers_json, ai_json FROM {$table} WHERE (email = %s OR user_id = %d) AND expires_at > %s ORDER BY id LIMIT 50 OFFSET %d", $email, $user ? $user->ID : -1, gmdate( 'Y-m-d H:i:s' ), max( 0, $page - 1 ) * 50 ), ARRAY_A );
	$data = array();
	foreach ( $rows as $row ) {
		$fields = array();
		foreach ( array( 'created_at' => 'Fecha', 'expires_at' => 'Vencimiento', 'answers_json' => 'Respuestas', 'ai_json' => 'Rutina' ) as $key => $label ) {
			$fields[] = array( 'name' => $label, 'value' => (string) $row[ $key ] );
		}
		$data[] = array( 'group_id' => 'bsc-skin-quiz', 'group_label' => 'Skin Quiz BSC', 'item_id' => 'bsc-skin-quiz-' . $row['id'], 'data' => $fields );
	}
	return array( 'data' => $data, 'done' => count( $rows ) < 50 );
}
add_filter( 'wp_privacy_personal_data_erasers', static function ( $erasers ) {
	$erasers['bsc-skin-quiz'] = array( 'eraser_friendly_name' => 'Skin Quiz BSC', 'callback' => 'bsc_skin_quiz_privacy_erase' );
	return $erasers;
} );
add_filter( 'wp_privacy_personal_data_exporters', static function ( $exporters ) {
	$exporters['bsc-skin-quiz'] = array( 'exporter_friendly_name' => 'Skin Quiz BSC', 'callback' => 'bsc_skin_quiz_privacy_export' );
	return $exporters;
} );
