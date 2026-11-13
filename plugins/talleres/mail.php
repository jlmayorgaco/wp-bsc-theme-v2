<?php
defined( 'ABSPATH' ) || exit;

function bsc_workshops_email_types(): array {
	return array(
		'receipt'      => array(
			'title'   => 'Recibimos tu solicitud',
			'label'   => 'Talleres · Solicitud de reserva',
			'trigger' => 'Al apartar cupos en un taller de pago',
		),
		'confirmation' => array(
			'title'   => 'Tu cupo está confirmado',
			'label'   => 'Talleres · Reserva confirmada',
			'trigger' => 'Al confirmar el pago o reservar un taller gratuito',
		),
		'reminder'     => array(
			'title'   => 'Recordatorio de tu taller',
			'label'   => 'Talleres · Recordatorio',
			'trigger' => 'Aproximadamente 24 horas antes del taller o desde el panel',
		),
		'availability' => array(
			'title'   => 'Hay cupos disponibles',
			'label'   => 'Talleres · Aviso de cupos',
			'trigger' => 'Cuando vuelve a haber disponibilidad para la lista de aviso',
		),
		'cancellation' => array(
			'title'   => 'Tu reserva fue cancelada',
			'label'   => 'Talleres · Reserva cancelada',
			'trigger' => 'Al cancelar una reserva desde el panel',
		),
	);
}

add_action( 'init', static function () {
	if ( ! wp_next_scheduled( 'bsc_workshops_mail_tick' ) ) {
		BSC_DB_Lock::run( 'workshops-mail-schedule', static function () {
			if ( ! wp_next_scheduled( 'bsc_workshops_mail_tick' ) ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'hourly', 'bsc_workshops_mail_tick' );
			}
		} );
	}
}, 12 );

function bsc_workshops_kick_mail(): void {
	if ( ! wp_next_scheduled( 'bsc_workshops_mail_now' ) ) {
		wp_schedule_single_event( time(), 'bsc_workshops_mail_now' );
	}
}

/** Queue availability notices while the caller holds the workshop lock. */
function bsc_workshops_queue_waiting( int $id, array $settings ): bool {
	global $wpdb;
	if ( ! bsc_workshops_open_slots( $settings, bsc_workshops_counts( $id ) ) ) {
		return true;
	}
	$table = bsc_workshops_table();
	return false !== $wpdb->query( $wpdb->prepare(
		"UPDATE {$table} SET mail_due = %d WHERE workshop_id = %d AND status = 'waiting' AND notice_sent = 0 AND mail_due = 0 AND email <> ''",
		time(),
		$id
	) );
}

add_action( 'bsc_workshops_mail_tick', 'bsc_workshops_process_mail' );
add_action( 'bsc_workshops_mail_now', 'bsc_workshops_process_mail' );
function bsc_workshops_process_mail(): void {
	global $wpdb;
	$table = bsc_workshops_table();
	$jobs  = $wpdb->get_results( $wpdb->prepare( "SELECT id, workshop_id FROM {$table} WHERE mail_due > 0 AND mail_due <= %d ORDER BY mail_due, id LIMIT 30", time() ) );
	foreach ( (array) $jobs as $job ) {
		BSC_DB_Lock::run( 'workshop:' . $job->workshop_id, static function () use ( $job, $table, $wpdb ) {
			wp_cache_delete( (int) $job->workshop_id, 'posts' );
			wp_cache_delete( (int) $job->workshop_id, 'post_meta' );
			$row  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $job->id ) );
			$post = bsc_workshops_get( (int) $job->workshop_id );
			if ( ! $row || ! $row->mail_due || (int) $row->mail_due > time() ) {
				return;
			}
			if ( ! $row->email || ! $post || ( 'publish' !== $post->post_status && 'cancelled' !== $row->status ) ) {
				$wpdb->update( $table, array( 'mail_due' => 0 ), array( 'id' => $row->id ) );
				return;
			}
			bsc_workshops_send_due_mail( $post, $row );
		}, 0 );
	}
	if ( count( (array) $jobs ) === 30 ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'bsc_workshops_mail_now' );
	}
}

function bsc_workshops_send_due_mail( WP_Post $post, object $row ): void {
	global $wpdb;
	$table    = bsc_workshops_table();
	$settings = bsc_workshops_settings( $post->ID );
	$slot     = $settings['slots'][ $row->slot_key ] ?? null;
	$start    = $slot['start'] ?? 0;
	$now      = time();
	$next     = 0;
	$type     = '';
	$stamp    = '';

	if ( 'pending' === $row->status && $slot && ! $row->receipt_sent && ( ! $start || $start > $now ) ) {
		$type = 'receipt';
		$stamp = 'receipt_sent';
	} elseif ( 'confirmed' === $row->status && $slot && ( ! $start || $start > $now ) ) {
		if ( ! $row->confirmation_sent ) {
			$type = 'confirmation';
			$stamp = 'confirmation_sent';
		} elseif ( $row->force_reminder || ( $start && (int) $row->reminder_for !== $start && $now >= $start - DAY_IN_SECONDS ) ) {
			$type = 'reminder';
			$stamp = 'reminder_sent';
		} elseif ( $start && (int) $row->reminder_for !== $start ) {
			$next = $start - DAY_IN_SECONDS;
		}
	} elseif ( 'waiting' === $row->status && ! $row->notice_sent && bsc_workshops_open_slots( $settings, bsc_workshops_counts( $post->ID ) ) ) {
		$type = 'availability';
		$stamp = 'notice_sent';
	} elseif ( 'cancelled' === $row->status && $slot && ! $row->notice_sent ) {
		$type = 'cancellation';
		$stamp = 'notice_sent';
	}

	$changes = array( 'mail_due' => $next );
	if ( '' !== $type ) {
		$title = bsc_workshops_email_types()[ $type ]['title'];
		$vars = array( 'type' => $type, 'title' => $title, 'workshop' => $post, 'booking' => $row, 'slot' => $slot, 'venue' => $settings['venue'] );
		$sent = bsc_workshops_is_demo( $post->ID )
			? '' !== bsc_render_email_template( 'bsc-workshop.php', $vars )
			: bsc_send_email_from_template( $row->email, $title . ' | ' . $post->post_title, 'bsc-workshop.php', $vars );
		if ( $sent ) {
			$changes[ $stamp ] = $now;
			if ( 'confirmation' === $type ) {
				$changes['mail_due'] = $row->force_reminder ? $now : ( $start ? max( $now, $start - DAY_IN_SECONDS ) : 0 );
			} elseif ( 'reminder' === $type ) {
				$changes['reminder_for']   = $start;
				$changes['force_reminder'] = 0;
			}
		} else {
			$changes['mail_due'] = $now + HOUR_IN_SECONDS;
		}
	}
	$wpdb->update( $table, $changes, array( 'id' => $row->id ) );
}
