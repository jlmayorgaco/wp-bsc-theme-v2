<?php
defined( 'ABSPATH' ) || exit;

function bsc_workshops_order_slots( WC_Order $order ): array {
	$slots = array();
	foreach ( $order->get_items() as $item ) {
		$id = (int) $item->get_meta( '_bsc_workshop_id' );
		if ( ! $id ) {
			continue;
		}
		$slot = (string) $item->get_meta( '_bsc_workshop_slot' );
		$key  = $id . ':' . $slot;
		$item_id = $item->get_id();
		$seats = max( 0, $item->get_quantity() + $order->get_qty_refunded_for_item( $item_id ) );
		$amount = $order->get_line_total( $item, true, false ) - $order->get_total_refunded_for_item( $item_id );
		foreach ( array_keys( $item->get_taxes()['total'] ?? array() ) as $rate ) {
			$amount -= $order->get_tax_refunded_for_item( $item_id, $rate );
		}
		$slots[ $key ] ??= array( 'workshop_id' => $id, 'slot_key' => $slot, 'seats' => 0, 'amount' => 0 );
		$slots[ $key ]['seats'] += $seats;
		$slots[ $key ]['amount'] += max( 0, (int) round( $amount ) );
	}
	return $slots;
}

function bsc_workshops_order_is_paid( WC_Order $order ): bool {
	if ( ! $order->get_date_paid() || $order->get_total() <= 0 || 'cod' === $order->get_payment_method()
		|| $order->has_status( array( 'pending', 'on-hold', 'failed', 'cancelled', 'refunded', 'checkout-draft' ) ) ) {
		return false;
	}
	if ( 'wompi' === $order->get_payment_method() ) {
		$approved = (string) $order->get_meta( '_bsc_wompi_approved_id', true );
		return '' !== $approved && $approved === $order->get_transaction_id();
	}
	return true;
}

/**
 * Serialize payment callbacks per order and take workshop locks in ID order.
 * A mixed order either holds all its workshop seats or holds none of them.
 */
function bsc_workshops_sync_order( int $order_id, string $event = 'status' ) {
	$result = BSC_DB_Lock::run( 'workshop-order:' . $order_id, static function () use ( $order_id, $event ) {
		global $wpdb;
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return true;
		}
		$slots = bsc_workshops_order_slots( $order );
		if ( ! $slots ) {
			return true;
		}

		$status = $order->get_status();
		$target = '';
		$expires = 0;
		if ( in_array( $status, array( 'cancelled', 'refunded' ), true ) || ( 'failed' === $status && 'checkout' !== $event ) ) {
			$target = 'cancelled';
		} elseif ( bsc_workshops_order_is_paid( $order ) ) {
			$target = 'confirmed';
		} elseif ( 'checkout' === $event || 'on-hold' === $status ) {
			$target = 'pending';
			$expires = 'on-hold' === $status ? 0 : time() + max( 15, min( 120, (int) get_option( 'woocommerce_hold_stock_minutes', 60 ) ) ) * MINUTE_IN_SECONDS;
		} elseif ( 'expire' === $event || 'discard' === $event ) {
			$target = 'cancelled';
		}
		if ( '' === $target ) {
			return true;
		}

		$table = bsc_workshops_table();
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE order_id = %d", $order_id ) );
		if ( $wpdb->last_error ) {
			return new WP_Error( 'bsc_workshop_write', 'No pudimos verificar los cupos del taller.' );
		}
		$bookings = array();
		foreach ( $rows as $row ) {
			$bookings[ $row->workshop_id . ':' . $row->slot_key ] = $row;
		}
		$ids = array_unique( array_merge( array_column( $slots, 'workshop_id' ), array_map( 'intval', array_column( $rows, 'workshop_id' ) ) ) );
		sort( $ids, SORT_NUMERIC );
		$locked = array();
		try {
			foreach ( $ids as $id ) {
				if ( ! BSC_DB_Lock::acquire( 'workshop:' . $id ) ) {
					return new WP_Error( 'bsc_lock_timeout', 'Los cupos del taller se están actualizando. Intenta nuevamente.' );
				}
				$locked[] = $id;
				wp_cache_delete( $id, 'posts' );
				wp_cache_delete( $id, 'post_meta' );
			}
			return BSC_DB_Lock::transaction( static function () use ( $wpdb, $table, $order, $slots, $bookings, $target, $expires, $event, $status ) {
				$now = time();
				foreach ( $slots as $key => $slot ) {
					if ( 'cancelled' === $target || ! $slot['seats'] ) {
						continue;
					}
					$result = bsc_workshops_hold_order_slot( $order, $slot, $bookings[ $key ] ?? null, $target, $expires, $now );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
				foreach ( $bookings as $key => $booking ) {
					if ( 'cancelled' === $booking->status || ( 'cancelled' !== $target && ! empty( $slots[ $key ]['seats'] ) ) ) {
						continue;
					}
					if ( 'cancelled' !== $status && 'refunded' !== $status && 'cancelled' === $target && 'pending' !== $booking->status ) {
						continue;
					}
					if ( 'expire' === $event && ( ! $booking->hold_until || (int) $booking->hold_until > $now ) ) {
						continue;
					}
					$send = 'confirmed' === $booking->status && $booking->email;
					$cancelled = $wpdb->update( $table, array( 'status' => 'cancelled', 'hold_until' => 0, 'force_reminder' => 0, 'notice_sent' => 0, 'mail_due' => $send ? $now : 0 ), array( 'id' => $booking->id ) );
					$id = (int) $booking->workshop_id;
					if ( false === $cancelled || ! bsc_workshops_queue_waiting( $id, bsc_workshops_settings( $id ) ) ) {
						return new WP_Error( 'bsc_workshop_write', 'No pudimos liberar los cupos del taller.' );
					}
				}
				if ( $expires ) {
					// Include the expiry so a retry can extend it without colliding with the first event.
					$scheduled = wp_schedule_single_event( $expires, 'bsc_workshops_expire_order', array( $order->get_id(), $expires ), true );
					if ( is_wp_error( $scheduled ) && 'duplicate_event' !== $scheduled->get_error_code() ) {
						return new WP_Error( 'bsc_workshop_write', 'No pudimos apartar los cupos del taller.' );
					}
				}
				return true;
			} );
		} finally {
			foreach ( array_reverse( $locked ) as $id ) {
				BSC_DB_Lock::release( 'workshop:' . $id );
			}
		}
	} );
	if ( true === $result ) {
		bsc_workshops_kick_mail();
	}
	return $result;
}

/** Write a hold or confirm the existing hold. The caller owns the workshop lock. */
function bsc_workshops_hold_order_slot( WC_Order $order, array $entry, ?object $booking, string $status, int $expires, int $now ) {
	global $wpdb;
	$id       = $entry['workshop_id'];
	$key      = $entry['slot_key'];
	$post     = bsc_workshops_get( $id );
	$settings = bsc_workshops_settings( $id );
	$slot     = $settings['slots'][ $key ] ?? null;
	$held     = $booking && ( 'confirmed' === $booking->status
		|| ( 'pending' === $booking->status && ( ! $booking->hold_until || (int) $booking->hold_until > $now ) ) ) ? (int) $booking->seats : 0;

	if ( 'confirmed' === $status && 'confirmed' !== ( $booking->status ?? '' ) && ( $slot['start'] ?? 0 ) && $slot['start'] <= $now ) {
		return new WP_Error( 'bsc_workshop_started', 'El horario del taller ya comenzó.' );
	}

	if ( $entry['seats'] > $held ) {
		$used = bsc_workshops_counts( $id )[ $key ] ?? 0;
		if ( $wpdb->last_error ) {
			return new WP_Error( 'bsc_workshop_write', 'No pudimos verificar los cupos del taller.' );
		}
		if ( ! $post || 'publish' !== $post->post_status || ! $slot || $settings['price'] <= 0
			|| $entry['seats'] > $held + bsc_workshops_available( $slot, $used ) ) {
			return new WP_Error( 'bsc_workshop_full', 'Ya no quedan suficientes cupos en el horario seleccionado. Revisa tu carrito.' );
		}
	}
	if ( 'pending' === $status && ( $entry['amount'] <= 0 || 'cod' === $order->get_payment_method() ) ) {
		return new WP_Error( 'bsc_workshop_payment', 'El taller requiere un pago antes de confirmar la reserva.' );
	}
	if ( 'confirmed' === ( $booking->status ?? '' ) && 'pending' === $status ) {
		return true;
	}

	$table = bsc_workshops_table();
	$name  = mb_substr( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ), 0, 120 );
	$email = strtolower( sanitize_email( $order->get_billing_email() ) );
	$data  = array(
		'seats'      => $entry['seats'],
		'amount'     => $entry['amount'],
		'status'     => $status,
		'hold_until' => $expires,
	);
	if ( 'confirmed' === $status && 'confirmed' !== ( $booking->status ?? '' ) ) {
		$data += array( 'confirmation_sent' => 0, 'reminder_for' => 0, 'notice_sent' => 0, 'mail_due' => $email ? $now : 0 );
	}
	if ( $booking ) {
		// An erased customer must not be copied back from WooCommerce.
		if ( $booking->email ) {
			$data += compact( 'name', 'email' );
		} else {
			$data['mail_due'] = 0;
		}
		$saved = $wpdb->update( $table, $data, array( 'id' => $booking->id ) );
	} else {
		$saved = $wpdb->insert( $table, $data + array(
			'workshop_id' => $id,
			'slot_key'    => $key,
			'order_id'    => $order->get_id(),
			'email_hash'  => hash( 'sha256', 'order:' . $order->get_id() ),
			'name'        => $name,
			'email'       => $email,
			'created_at'  => $now,
		) );
	}
	if ( false === $saved || ( $entry['seats'] < $held && ! bsc_workshops_queue_waiting( $id, $settings ) ) ) {
		return new WP_Error( 'bsc_workshop_write', 'No pudimos guardar los cupos del taller.' );
	}
	return true;
}

function bsc_workshops_reserve_checkout( WC_Order $order ): void {
	if ( ! bsc_workshops_order_slots( $order ) ) {
		return;
	}
	$result = bsc_workshops_sync_order( $order->get_id(), 'checkout' );
	if ( is_wp_error( $result ) ) {
		throw new Exception( $result->get_error_message() );
	}
}
add_action( 'woocommerce_checkout_order_created', 'bsc_workshops_reserve_checkout', 21 );
add_action( 'woocommerce_store_api_checkout_order_processed', 'bsc_workshops_reserve_checkout', 21 );
add_action( 'woocommerce_before_pay_action', 'bsc_workshops_reserve_checkout', 10 );

function bsc_workshops_update_order( int $id, string $event = 'status' ): void {
	$order = wc_get_order( $id );
	if ( ! $order || ! bsc_workshops_order_slots( $order ) ) {
		return;
	}
	$result = bsc_workshops_sync_order( $id, $event );
	if ( ! is_wp_error( $result ) ) {
		return;
	}
	if ( in_array( $result->get_error_code(), array( 'bsc_lock_timeout', 'bsc_workshop_write', 'bsc_transaction_failed' ), true ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bsc_workshops_retry_order', array( $id, $event ) );
	} else {
		$order->add_order_note( 'Reserva de taller pendiente de revisión. ' . $result->get_error_message() );
	}
}
add_action( 'woocommerce_order_status_changed', 'bsc_workshops_update_order', 10, 1 );
add_action( 'woocommerce_payment_complete', 'bsc_workshops_update_order', 10, 1 );
add_action( 'bsc_wompi_payment_verified', 'bsc_workshops_update_order', 10, 1 );
add_action( 'woocommerce_order_refunded', 'bsc_workshops_update_order', 10, 1 );
add_action( 'bsc_workshops_retry_order', 'bsc_workshops_update_order', 10, 2 );
add_action( 'bsc_workshops_expire_order', static function ( int $id ): void {
	bsc_workshops_update_order( $id, 'expire' );
} );
add_action( 'woocommerce_checkout_order_exception', static function ( WC_Order $order ): void {
	bsc_workshops_update_order( $order->get_id(), 'discard' );
} );
