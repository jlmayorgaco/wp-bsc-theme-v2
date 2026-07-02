<?php
/**
 * Privacy helpers for public form data stored by the theme.
 */
defined( 'ABSPATH' ) || exit;

function bsc_privacy_get_form_retention_days(): int {
	$days = absint( get_option( 'bsc_form_data_retention_days', 730 ) );

	return max( 30, $days );
}

function bsc_privacy_hash_value( string $value ): string {
	$value = strtolower( trim( $value ) );

	return '' === $value ? '' : wp_hash( $value );
}

function bsc_privacy_current_ip_hash(): string {
	$remote_addr = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '';

	return bsc_privacy_hash_value( $remote_addr );
}

function bsc_privacy_form_metadata(): array {
	return array(
		'consent'                => true,
		'ip_hash'                => bsc_privacy_current_ip_hash(),
		'privacy_policy_version' => '2026-05-23',
		'retention_days'         => bsc_privacy_get_form_retention_days(),
	);
}

function bsc_privacy_delete_indexed_row( array $rows, int $index ): array {
	if ( isset( $rows[ $index ] ) ) {
		unset( $rows[ $index ] );
	}

	return array_values( $rows );
}

function bsc_privacy_row_timestamp( array $row ): int {
	foreach ( array( 'date', 'created_at', 'updated_at' ) as $field ) {
		if ( empty( $row[ $field ] ) ) {
			continue;
		}

		$timestamp = strtotime( (string) $row[ $field ] );
		if ( false !== $timestamp ) {
			return $timestamp;
		}
	}

	return 0;
}

function bsc_privacy_prune_rows( array $rows, int $max_rows = 1000 ): array {
	$now  = current_time( 'timestamp' );
	$keep = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$retention_days = absint( $row['retention_days'] ?? 0 );
		$retention_days = $retention_days > 0 ? $retention_days : bsc_privacy_get_form_retention_days();
		$timestamp      = bsc_privacy_row_timestamp( $row );

		if ( $timestamp > 0 && $timestamp < strtotime( '-' . $retention_days . ' days', $now ) ) {
			continue;
		}

		$keep[] = $row;
	}

	return array_values( array_slice( $keep, -absint( $max_rows ) ) );
}

function bsc_privacy_prune_form_data(): void {
	if ( function_exists( 'bsc_newsletter_get_subscribers' ) && function_exists( 'bsc_newsletter_save_subscribers' ) ) {
		bsc_newsletter_save_subscribers( bsc_newsletter_get_subscribers() );
	}

	if ( function_exists( 'bsc_contact_get_messages' ) && function_exists( 'bsc_contact_save_messages' ) ) {
		bsc_contact_save_messages( bsc_contact_get_messages() );
	}

	$applications = get_option( 'bsc_creator_applications', array() );
	if ( is_array( $applications ) ) {
		update_option( 'bsc_creator_applications', bsc_privacy_prune_rows( $applications, 1000 ), false );
	}
}
add_action( 'bsc_privacy_prune_form_data', 'bsc_privacy_prune_form_data' );

function bsc_privacy_schedule_form_data_prune(): void {
	if ( ! wp_next_scheduled( 'bsc_privacy_prune_form_data' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'bsc_privacy_prune_form_data' );
	}
}
add_action( 'init', 'bsc_privacy_schedule_form_data_prune' );
