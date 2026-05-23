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
    return [
        'consent'                => true,
        'ip_hash'                => bsc_privacy_current_ip_hash(),
        'privacy_policy_version' => '2026-05-23',
        'retention_days'         => bsc_privacy_get_form_retention_days(),
    ];
}

function bsc_privacy_delete_indexed_row( array $rows, int $index ): array {
    if ( isset( $rows[ $index ] ) ) {
        unset( $rows[ $index ] );
    }

    return array_values( $rows );
}
