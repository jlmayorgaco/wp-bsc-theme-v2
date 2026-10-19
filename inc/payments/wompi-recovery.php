<?php
/**
 * Bounded, durable reconciliation for known transaction IDs; never initiates a charge.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_action( 'bsc_wompi_retry_transaction', 'bsc_wompi_retry_transaction', 10, 4 );

/**
 * Persist a unique recovery job with bounded backoff.
 *
 * @param string $transaction_id Known transaction ID.
 * @param int    $order_id Expected order, or zero for a signature-verified event ID.
 * @param int    $attempt Zero-based retry number.
 * @param string $environment Original gateway environment.
 * @return bool
 */
function bsc_wompi_schedule_reconciliation( string $transaction_id, int $order_id = 0, int $attempt = 0, string $environment = '' ): bool {
	$delays      = array( 60, 300, 900, 1800, 3600, 10800, 21600, 43200 );
	$environment = '' !== $environment ? $environment : bsc_wompi_environment();
	$context     = array(
		'transaction_id' => $transaction_id,
		'order_id'       => $order_id,
		'attempt'        => $attempt,
		'environment'    => $environment,
	);
	if ( ! bsc_wompi_valid_transaction_id( $transaction_id ) || $order_id < 0 || $attempt < 0 || ! in_array( $environment, array( 'prod', 'test' ), true ) ) {
		return false;
	}
	if ( ! isset( $delays[ $attempt ] ) ) {
		bsc_wompi_log( 'error', 'retry_exhausted', $context );
		return false;
	}
	$args = array( $transaction_id, $order_id, $attempt, $environment );
	try {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			if ( as_has_scheduled_action( 'bsc_wompi_retry_transaction', $args, 'bsc-wompi' ) || as_schedule_single_action( time() + $delays[ $attempt ], 'bsc_wompi_retry_transaction', $args, 'bsc-wompi', true ) ) {
				return true;
			}
		}
	} catch ( Throwable $error ) {
		// A scheduling failure must not hide the original callback failure.
		bsc_wompi_log( 'error', 'retry_scheduler_exception', $context );
	}
	try {
		if ( function_exists( 'wp_next_scheduled' ) && ( wp_next_scheduled( 'bsc_wompi_retry_transaction', $args ) || true === wp_schedule_single_event( time() + $delays[ $attempt ], 'bsc_wompi_retry_transaction', $args ) ) ) {
			return true;
		}
	} catch ( Throwable $error ) {
		bsc_wompi_log( 'error', 'retry_cron_exception', $context );
	}
	bsc_wompi_log( 'error', 'retry_schedule_failed', $context );
	return false;
}

/**
 * Look up the authoritative transaction again after the browser has gone away.
 *
 * @param string $transaction_id Known transaction ID.
 * @param int    $order_id Expected order, or zero for a signed event ID.
 * @param int    $attempt Zero-based retry number.
 * @param string $environment Environment captured when scheduling.
 */
function bsc_wompi_retry_transaction( string $transaction_id, int $order_id = 0, int $attempt = 0, string $environment = '' ): void {
	if ( ! bsc_wompi_valid_transaction_id( $transaction_id ) || $order_id < 0 || $attempt < 0 || $attempt > 7 ) {
		return;
	}
	$environment = '' !== $environment ? $environment : bsc_wompi_environment();
	$context     = array(
		'transaction_id' => $transaction_id,
		'order_id'       => $order_id,
		'attempt'        => $attempt,
		'environment'    => $environment,
	);
	if ( bsc_wompi_environment() !== $environment ) {
		bsc_wompi_log( 'error', 'retry_environment_mismatch', $context );
		return;
	}
	try {
		$transaction = bsc_get_wompi_transaction_for_return( $transaction_id );
		if ( $transaction ) {
			// Browser jobs are bound to the order whose key was verified, never to another order.
			if ( $order_id && (string) ( $transaction->reference ?? '' ) !== (string) $order_id ) {
				bsc_wompi_log( 'error', 'retry_order_mismatch', $context );
				return;
			}
			$result = bsc_wompi_reconcile_transaction( $transaction );
			if ( ! is_wp_error( $result ) ) {
				$context['order_id'] = (int) $transaction->reference;
				$context['status']   = $transaction->status;
			}
			if ( ! is_wp_error( $result ) && 'PENDING' !== $transaction->status ) {
				bsc_wompi_log( 'info', 'retry_reconciled', $context );
				return;
			}
			if ( is_wp_error( $result ) && 400 === bsc_wompi_error_http_status( $result ) ) {
				bsc_wompi_log( 'error', $result->get_error_code(), $context );
				return;
			}
			bsc_wompi_log( is_wp_error( $result ) ? 'error' : 'info', is_wp_error( $result ) ? $result->get_error_code() : 'retry_pending', $context );
		} else {
			bsc_wompi_log( 'warning', 'retry_api_unavailable', $context );
		}
	} catch ( Throwable $error ) {
		bsc_wompi_log( 'error', 'retry_processing_failed', $context );
	}
	bsc_wompi_schedule_reconciliation( $transaction_id, $order_id, $attempt + 1, $environment );
}
