<?php
/**
 * Storage helpers for Newsletter leads.
 */
defined( 'ABSPATH' ) || exit;

function bsc_newsletter_status_options(): array {
    return [
        'new'       => 'Nuevo',
        'contacted' => 'Contactado',
        'approved'  => 'Aprobado',
        'discarded' => 'Descartado',
    ];
}

function bsc_newsletter_normalize_status( string $status ): string {
    $aliases = [
        'active'       => 'new',
        'subscribed'   => 'new',
        'reviewed'     => 'contacted',
        'rejected'     => 'discarded',
        'unsubscribed' => 'discarded',
    ];

    $status = sanitize_key( $status );
    $status = $aliases[ $status ] ?? $status;

    return array_key_exists( $status, bsc_newsletter_status_options() ) ? $status : 'new';
}

function bsc_newsletter_normalize_subscriber( $subscriber ): array {
    if ( is_string( $subscriber ) ) {
        $subscriber = [ 'email' => $subscriber ];
    }

    if ( ! is_array( $subscriber ) ) {
        $subscriber = [];
    }

    $email = sanitize_email( (string) ( $subscriber['email'] ?? '' ) );

    return [
        'email'      => $email,
        'date'       => sanitize_text_field( (string) ( $subscriber['date'] ?? '' ) ),
        'status'     => bsc_newsletter_normalize_status( (string) ( $subscriber['status'] ?? 'new' ) ),
        'source'     => sanitize_text_field( (string) ( $subscriber['source'] ?? 'newsletter-form' ) ),
        'notes'      => sanitize_textarea_field( (string) ( $subscriber['notes'] ?? '' ) ),
        'consent'    => ! empty( $subscriber['consent'] ),
        'ip_hash'    => sanitize_text_field( (string) ( $subscriber['ip_hash'] ?? '' ) ),
        'privacy_policy_version' => sanitize_text_field( (string) ( $subscriber['privacy_policy_version'] ?? '' ) ),
        'retention_days' => absint( $subscriber['retention_days'] ?? 0 ),
        'updated_at' => sanitize_text_field( (string) ( $subscriber['updated_at'] ?? '' ) ),
        'updated_by' => absint( $subscriber['updated_by'] ?? 0 ),
    ];
}

function bsc_newsletter_get_subscribers(): array {
    $subscribers = get_option( 'bsc_newsletter_subscribers', [] );

    if ( ! is_array( $subscribers ) ) {
        return [];
    }

    $normalized = array_map( 'bsc_newsletter_normalize_subscriber', $subscribers );

    return array_values(
        array_filter(
            $normalized,
            static fn( array $subscriber ): bool => $subscriber['email'] !== ''
        )
    );
}

function bsc_newsletter_save_subscribers( array $subscribers ): void {
    update_option( 'bsc_newsletter_subscribers', array_values( $subscribers ), false );
}
