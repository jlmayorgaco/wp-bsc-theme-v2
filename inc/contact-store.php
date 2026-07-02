<?php
/**
 * Storage helpers for Contact form messages.
 */
defined( 'ABSPATH' ) || exit;

function bsc_contact_status_options(): array {
	return array(
		'new'         => 'Nuevo',
		'in_progress' => 'En seguimiento',
		'answered'    => 'Respondido',
		'discarded'   => 'Descartado',
	);
}

function bsc_contact_normalize_status( string $status ): string {
	$aliases = array(
		'active'    => 'new',
		'reviewed'  => 'in_progress',
		'contacted' => 'answered',
		'resolved'  => 'answered',
		'rejected'  => 'discarded',
	);

	$status = sanitize_key( $status );
	$status = $aliases[ $status ] ?? $status;

	return array_key_exists( $status, bsc_contact_status_options() ) ? $status : 'new';
}

function bsc_contact_normalize_message( $message ): array {
	if ( ! is_array( $message ) ) {
		$message = array();
	}

	$email = sanitize_email( (string) ( $message['email'] ?? '' ) );

	return array(
		'id'                     => sanitize_text_field( (string) ( $message['id'] ?? '' ) ),
		'name'                   => sanitize_text_field( (string) ( $message['name'] ?? '' ) ),
		'email'                  => $email,
		'message'                => sanitize_textarea_field( (string) ( $message['message'] ?? '' ) ),
		'date'                   => sanitize_text_field( (string) ( $message['date'] ?? '' ) ),
		'status'                 => bsc_contact_normalize_status( (string) ( $message['status'] ?? 'new' ) ),
		'source'                 => sanitize_text_field( (string) ( $message['source'] ?? 'contact-form' ) ),
		'notes'                  => sanitize_textarea_field( (string) ( $message['notes'] ?? '' ) ),
		'mail_sent'              => ! empty( $message['mail_sent'] ),
		'consent'                => ! empty( $message['consent'] ),
		'ip_hash'                => sanitize_text_field( (string) ( $message['ip_hash'] ?? '' ) ),
		'privacy_policy_version' => sanitize_text_field( (string) ( $message['privacy_policy_version'] ?? '' ) ),
		'retention_days'         => absint( $message['retention_days'] ?? 0 ),
		'updated_at'             => sanitize_text_field( (string) ( $message['updated_at'] ?? '' ) ),
		'updated_by'             => absint( $message['updated_by'] ?? 0 ),
	);
}

function bsc_contact_get_messages(): array {
	$messages = get_option( 'bsc_contact_messages', array() );

	if ( ! is_array( $messages ) ) {
		return array();
	}

	$normalized = array_map( 'bsc_contact_normalize_message', $messages );

	return array_values(
		array_filter(
			$normalized,
			static fn( array $message ): bool => $message['email'] !== '' && $message['message'] !== ''
		)
	);
}

function bsc_contact_save_messages( array $messages ): void {
	$normalized = array_values(
		array_filter(
			array_map( 'bsc_contact_normalize_message', $messages ),
			static fn( array $message ): bool => $message['email'] !== '' && $message['message'] !== ''
		)
	);

	$normalized = function_exists( 'bsc_privacy_prune_rows' )
		? bsc_privacy_prune_rows( $normalized, 1000 )
		: array_slice( $normalized, -1000 );

	update_option( 'bsc_contact_messages', array_values( $normalized ), false );
}
