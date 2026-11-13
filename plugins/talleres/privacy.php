<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'wp_privacy_personal_data_exporters', static function ( array $exporters ): array {
	$exporters['bsc-workshops'] = array(
		'exporter_friendly_name' => 'Talleres BSC',
		'callback' => 'bsc_workshops_privacy_export',
	);
	return $exporters;
} );

function bsc_workshops_privacy_export( string $email, int $page = 1 ): array {
	global $wpdb;
	$table = bsc_workshops_table();
	$hash = hash( 'sha256', strtolower( $email ) );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE email_hash = %s ORDER BY id LIMIT 50 OFFSET %d", $hash, max( 0, $page - 1 ) * 50 ) );
	$data = array();
	foreach ( (array) $rows as $row ) {
		$post = bsc_workshops_get( (int) $row->workshop_id );
		$settings = bsc_workshops_settings( (int) $row->workshop_id );
		$data[] = array(
			'group_id'    => 'bsc-workshops',
			'group_label' => 'Talleres BSC',
			'item_id'     => 'bsc-workshop-' . $row->id,
			'data'        => array(
				array( 'name' => 'Taller', 'value' => $post->post_title ?? '' ),
				array( 'name' => 'Horario', 'value' => $settings['slots'][ $row->slot_key ]['label'] ?? '' ),
				array( 'name' => 'Nombre', 'value' => $row->name ),
				array( 'name' => 'Correo', 'value' => $row->email ),
				array( 'name' => 'Cupos', 'value' => (string) $row->seats ),
				array( 'name' => 'Total', 'value' => bsc_workshops_price( (int) $row->amount ) ),
				array( 'name' => 'Fecha de solicitud', 'value' => wp_date( 'd/m/Y H:i', (int) $row->created_at ) ),
			),
		);
	}
	return array( 'data' => $data, 'done' => count( $data ) < 50 );
}

add_filter( 'wp_privacy_personal_data_erasers', static function ( array $erasers ): array {
	$erasers['bsc-workshops'] = array(
		'eraser_friendly_name' => 'Talleres BSC',
		'callback' => 'bsc_workshops_privacy_erase',
	);
	return $erasers;
} );

function bsc_workshops_privacy_erase( string $email ): array {
	global $wpdb;
	$table = bsc_workshops_table();
	$hash = hash( 'sha256', strtolower( $email ) );
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, workshop_id FROM {$table} WHERE email_hash = %s ORDER BY id LIMIT 50", $hash ) );
	$removed = false;
	$retained = false;
	foreach ( (array) $rows as $row ) {
		$result = BSC_DB_Lock::run( 'workshop:' . $row->workshop_id, static function () use ( $wpdb, $table, $hash, $row ) {
			return $wpdb->update( $table, array(
				'name' => 'Datos eliminados',
				'email' => '',
				'email_hash' => hash( 'sha256', wp_generate_uuid4() ),
				'mail_due' => 0,
				'force_reminder' => 0,
			), array( 'id' => $row->id, 'email_hash' => $hash ) );
		} );
		$removed = $removed || 1 === $result;
		$retained = $retained || false === $result || is_wp_error( $result );
	}
	return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => array(), 'done' => $retained || count( (array) $rows ) < 50 );
}
