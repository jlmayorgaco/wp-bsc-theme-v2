<?php
defined( 'ABSPATH' ) || exit;

function bsc_workshops_admin_url( int $id = 0 ): string {
	return add_query_arg( 'page', 'bsc-talleres', $id ? add_query_arg( 'taller', $id, admin_url( 'admin.php' ) ) : admin_url( 'admin.php' ) );
}

add_action( 'admin_enqueue_scripts', static function ( string $hook ) {
	if ( ! str_ends_with( $hook, '_page_bsc-talleres' ) ) {
		return;
	}
	bsc_enqueue_admin_ui_assets();
	$base = get_template_directory_uri() . '/plugins/talleres/';
	wp_enqueue_style( 'bsc-workshops-admin', $base . 'admin.css', array( 'bsc-admin-ui' ), bsc_get_asset_version( 'plugins/talleres/admin.css' ) );
	wp_enqueue_script( 'bsc-workshops-admin', $base . 'admin.js', array(), bsc_get_asset_version( 'plugins/talleres/admin.js' ), true );
	if ( current_user_can( 'upload_files' ) ) {
		wp_enqueue_media();
	}
} );

function bsc_workshops_admin_guard(): void {
	if ( ! bsc_current_user_has_bsc_page_access( 'bsc-talleres' ) ) {
		wp_die( 'No tienes acceso a Talleres.', '', array( 'response' => 403 ) );
	}
}

function bsc_workshops_admin_slot_fields( string $key, array $slot ): void {
	$when = $slot['start'] ? wp_date( 'Y-m-d\TH:i', $slot['start'], wp_timezone() ) : '';
	$prefix = 'slots[' . $key . ']';
	?>
	<div class="bsc-workshops-admin__slot" data-workshop-slot>
		<label>Horario visible <input name="<?php echo esc_attr( $prefix . '[label]' ); ?>" type="text" maxlength="120" value="<?php echo esc_attr( $slot['label'] ); ?>" placeholder="Sáb 24 - 10:00 a. m." required></label>
		<label>Cupos <input name="<?php echo esc_attr( $prefix . '[capacity]' ); ?>" type="number" min="0" max="999" value="<?php echo esc_attr( $slot['capacity'] ); ?>" required></label>
		<label>Fecha real para recordatorio <input name="<?php echo esc_attr( $prefix . '[start]' ); ?>" type="datetime-local" value="<?php echo esc_attr( $when ); ?>"></label>
		<label class="bsc-workshops-admin__check"><input type="checkbox" name="<?php echo esc_attr( $prefix . '[open]' ); ?>" value="1" <?php checked( $slot['open'] ); ?>> Abierto</label>
		<?php if ( isset( $slot['used'] ) ) : ?>
			<span><?php echo esc_html( sprintf( '%d ocupados · %d disponibles', $slot['used'], bsc_workshops_available( $slot, $slot['used'] ) ) ); ?></span>
		<?php endif; ?>
		<button type="button" class="button-link-delete" data-remove-workshop-slot>Quitar horario</button>
	</div>
	<?php
}

function bsc_workshops_render_admin(): void {
	bsc_workshops_admin_guard();
	$id = absint( $_GET['taller'] ?? 0 );
	$post = bsc_workshops_get( $id );
	$settings = bsc_workshops_settings( $id );
	$counts = $post ? bsc_workshops_counts( $id ) : array();
	$list = bsc_workshops_posts( array( 'draft', 'publish' ) );
	$notices = array(
		'saved' => 'Taller guardado.',
		'updated' => 'Inscripción actualizada.',
		'queued' => 'Correo añadido a la cola.',
		'invalid' => 'Revisa el título, precio, horarios y cupos. Las fechas reales deben ser válidas.',
		'capacity' => 'La capacidad no puede quedar por debajo de los cupos ocupados.',
		'slot_history' => 'Este horario tiene inscripciones. Puedes cerrarlo para conservar su historial.',
		'changed' => 'El taller cambió mientras lo editabas. Revisa la versión actual y vuelve a guardar.',
		'closed' => 'La acción requiere un taller publicado o un horario vigente.',
		'no_availability' => 'Abre un horario con cupos antes de avisar a la lista.',
		'failed' => 'No se pudo completar la operación. Inténtalo nuevamente.',
	);
	$notice = sanitize_key( $_GET['notice'] ?? '' );
	?>
	<div class="wrap bsc-workshops-admin">
		<header class="bsc-admin-page-header">
			<div><h1>Talleres</h1><p class="bsc-admin-page-header__description">Publica la agenda y gestiona cupos, inscripciones y recordatorios.</p></div>
			<div class="bsc-admin-page-header__actions">
				<a class="button" href="<?php echo esc_url( bsc_workshops_public_url() ); ?>">Ver página pública</a>
				<a class="button button-primary" href="<?php echo esc_url( bsc_workshops_admin_url() . '#taller-editor' ); ?>">Nuevo taller</a>
			</div>
		</header>
		<?php if ( isset( $notices[ $notice ] ) ) : ?>
			<div class="notice <?php echo in_array( $notice, array( 'saved', 'updated', 'queued' ), true ) ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( $notices[ $notice ] ); ?></p></div>
		<?php endif; ?>
		<div class="bsc-workshops-admin__layout">
			<section class="bsc-admin-panel">
				<h2>Agenda de talleres</h2>
				<?php if ( ! $list ) : ?><p>Aún no hay talleres. Crea el primero desde el formulario.</p><?php endif; ?>
				<ul class="bsc-workshops-admin__list">
					<?php foreach ( $list as $item ) : ?>
						<li><a href="<?php echo esc_url( bsc_workshops_admin_url( $item->ID ) ); ?>" <?php if ( $item->ID === $id ) { echo 'aria-current="page"'; } ?>><?php echo esc_html( $item->post_title ); ?></a>
							<small><?php echo 'publish' === $item->post_status ? 'Publicado' : 'Borrador'; ?><?php if ( bsc_workshops_is_demo( $item->ID ) ) { echo ' · Demo local'; } ?></small></li>
					<?php endforeach; ?>
				</ul>
			</section>
			<section class="bsc-admin-panel" id="taller-editor">
				<h2><?php echo $post ? 'Editar taller' : 'Crear taller'; ?></h2>
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="bsc-workshops-admin__editor">
					<input type="hidden" name="action" value="bsc_workshop_save">
					<input type="hidden" name="workshop" value="<?php echo esc_attr( $post ? $id : 0 ); ?>">
					<input type="hidden" name="revision" value="<?php echo esc_attr( $post ? bsc_workshops_revision( $post, $settings ) : '' ); ?>">
					<?php wp_nonce_field( 'bsc_workshop_save' ); ?>
					<label>Nombre del taller <input name="title" value="<?php echo esc_attr( $post->post_title ?? '' ); ?>" maxlength="160" required placeholder="K-Beauty &amp; Matcha"></label>
					<label>Descripción <textarea name="description" rows="3" maxlength="1600"><?php echo esc_textarea( $post->post_excerpt ?? '' ); ?></textarea></label>
					<div class="bsc-workshops-admin__fields">
						<label>Precio por persona (COP) <input name="price" type="number" min="0" max="4000000" step="1" value="<?php echo esc_attr( $settings['price'] ); ?>" required></label>
						<label>Estado <select name="visibility"><option value="draft">Borrador</option><option value="publish" <?php selected( $post->post_status ?? '', 'publish' ); ?>>Publicado</option></select></label>
					</div>
					<label>Lugar e indicaciones para el correo <textarea name="venue" rows="2" maxlength="500"><?php echo esc_textarea( $settings['venue'] ); ?></textarea></label>
					<div class="bsc-workshops-admin__media">
						<input type="hidden" name="image" value="<?php echo esc_attr( $settings['image'] ); ?>">
						<?php if ( current_user_can( 'upload_files' ) ) : ?>
							<div data-workshop-image><?php echo wp_get_attachment_image( $settings['image'], 'thumbnail' ); ?></div>
							<button type="button" class="button" data-choose-workshop-image>Elegir foto</button>
							<button type="button" class="button-link-delete" data-clear-workshop-image>Quitar foto</button>
						<?php endif; ?>
					</div>
					<fieldset>
						<legend>Horarios y cupos</legend>
						<p class="description">Escribe el horario tal como debe verse en la agenda. Con fecha real, el cupo cierra al empezar el taller y el correo sale unas 24 horas antes. Zona horaria: <?php echo esc_html( wp_timezone_string() ); ?>.</p>
						<div data-workshop-slots>
							<?php foreach ( $settings['slots'] as $key => $slot ) : ?>
								<?php bsc_workshops_admin_slot_fields( $key, $slot + array( 'used' => $counts[ $key ] ?? 0 ) ); ?>
							<?php endforeach; ?>
						</div>
						<template data-workshop-slot-template><?php bsc_workshops_admin_slot_fields( '__new__', array( 'label' => '', 'capacity' => 1, 'start' => 0, 'open' => true ) ); ?></template>
						<button type="button" class="button" data-add-workshop-slot>Añadir horario</button>
					</fieldset>
					<div><button class="button button-primary" type="submit">Guardar taller</button></div>
				</form>
			</section>
		</div>
		<?php if ( $post ) { bsc_workshops_render_bookings( $post, $settings ); } ?>
	</div>
	<?php
}

function bsc_workshops_revision( WP_Post $post, array $settings ): string {
	return hash( 'sha256', wp_json_encode( array( $post->post_title, $post->post_excerpt, $post->post_status, $settings ) ) );
}

function bsc_workshops_admin_redirect( int $id, string $notice, string $section = 'taller-editor' ): void {
	wp_safe_redirect( add_query_arg( 'notice', $notice, bsc_workshops_admin_url( $id ) ) . '#' . $section, 303 );
	exit;
}

add_action( 'admin_post_bsc_workshop_save', 'bsc_workshops_admin_save' );
function bsc_workshops_admin_save(): void {
	bsc_workshops_admin_guard();
	check_admin_referer( 'bsc_workshop_save' );
	$id          = absint( bsc_workshops_input( 'workshop' ) );
	$title       = sanitize_text_field( bsc_workshops_input( 'title' ) );
	$description = sanitize_textarea_field( bsc_workshops_input( 'description' ) );
	$venue       = sanitize_textarea_field( bsc_workshops_input( 'venue' ) );
	$price       = filter_var( bsc_workshops_input( 'price' ), FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0, 'max_range' => 4000000 ) ) );
	$visibility  = 'publish' === bsc_workshops_input( 'visibility' ) ? 'publish' : 'draft';
	$image       = absint( bsc_workshops_input( 'image' ) );
	$raw_slots   = isset( $_POST['slots'] ) && is_array( $_POST['slots'] ) ? wp_unslash( $_POST['slots'] ) : array();
	if ( '' === $title || mb_strlen( $title ) > 160 || false === $price || mb_strlen( $description ) > 1600 || mb_strlen( $venue ) > 500 || count( $raw_slots ) > 80 ) {
		bsc_workshops_admin_redirect( $id, 'invalid' );
	}
	$slots = array();
	foreach ( $raw_slots as $key => $row ) {
		$label = sanitize_text_field( $row['label'] ?? '' );
		$capacity = filter_var( $row['capacity'] ?? '', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0, 'max_range' => 999 ) ) );
		$when = is_string( $row['start'] ?? null ) ? $row['start'] : '';
		$date = '' !== $when ? DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $when, wp_timezone() ) : false;
		if ( ! wp_is_uuid( $key, 4 ) || '' === $label || mb_strlen( $label ) > 120 || false === $capacity
			|| ( '' !== $when && ( ! $date || $date->format( 'Y-m-d\TH:i' ) !== $when || $date->getTimestamp() <= 0 ) ) ) {
			bsc_workshops_admin_redirect( $id, 'invalid' );
		}
		$slots[ $key ] = array( 'label' => $label, 'capacity' => $capacity, 'start' => $date ? $date->getTimestamp() : 0, 'open' => '1' === ( $row['open'] ?? '' ) );
	}
	$image = wp_attachment_is_image( $image ) ? $image : 0;
	$result = BSC_DB_Lock::run( 'workshop:' . $id, static function () use ( $id, $title, $description, $visibility, $price, $venue, $image, $slots ) {
		global $wpdb;
		$table = bsc_workshops_table();
		wp_cache_delete( $id, 'posts' );
		wp_cache_delete( $id, 'post_meta' );
		$old = bsc_workshops_get( $id );
		$before = $old ? bsc_workshops_settings( $id ) : array( 'slots' => array() );
		if ( ! current_user_can( 'upload_files' ) ) {
			$image = $before['image'] ?? 0;
		}
		if ( $id && ( ! $old || ! hash_equals( bsc_workshops_revision( $old, $before ), bsc_workshops_input( 'revision' ) ) ) ) {
			return 'changed';
		}
		foreach ( bsc_workshops_counts( $id ) as $key => $used ) {
			if ( ! isset( $slots[ $key ] ) || $used > $slots[ $key ]['capacity'] ) {
				return 'capacity';
			}
		}
		$removed = array_diff_key( $before['slots'], $slots );
		foreach ( $removed as $key => $slot ) {
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE workshop_id = %d AND slot_key = %s LIMIT 1", $id, $key ) ) ) {
				return 'slot_history';
			}
		}
		return BSC_DB_Lock::transaction( static function () use ( $wpdb, $table, $id, $title, $description, $visibility, $price, $venue, $image, $slots, $before, $old ) {
			$saved = wp_insert_post( wp_slash( array( 'ID' => $id, 'post_type' => 'bsc_workshop', 'post_status' => $visibility, 'post_title' => $title, 'post_excerpt' => $description ) ), true );
			if ( is_wp_error( $saved ) || ! $saved ) {
				return new WP_Error( 'workshop_write_failed' );
			}
			$settings = compact( 'price', 'venue', 'image', 'slots' );
			if ( $settings !== $before && false === update_post_meta( $saved, '_bsc_workshop_settings', wp_slash( $settings ) ) ) {
				clean_post_cache( $saved );
				return new WP_Error( 'workshop_write_failed' );
			}
			if ( 'publish' === $visibility ) {
				foreach ( $slots as $key => $slot ) {
					$previous = $before['slots'][ $key ] ?? null;
					if ( $previous && ( $previous['start'] !== $slot['start'] || $previous['label'] !== $slot['label'] || $before['venue'] !== $venue || $old->post_status !== $visibility ) ) {
						$confirmed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET mail_due = %d, confirmation_sent = 0, reminder_for = 0 WHERE workshop_id = %d AND slot_key = %s AND status = 'confirmed'", time(), $saved, $key ) );
						$pending = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET mail_due = %d, receipt_sent = 0 WHERE workshop_id = %d AND slot_key = %s AND status = 'pending'", time(), $saved, $key ) );
						if ( false === $confirmed || false === $pending ) {
							clean_post_cache( $saved );
							return new WP_Error( 'workshop_write_failed' );
						}
					}
				}
				if ( ! bsc_workshops_queue_waiting( $saved, $settings ) ) {
					clean_post_cache( $saved );
					return new WP_Error( 'workshop_write_failed' );
				}
			}
			return (int) $saved;
		} );
	} );
	if ( is_int( $result ) ) {
		bsc_workshops_kick_mail();
		bsc_workshops_admin_redirect( $result, 'saved' );
	}
	bsc_workshops_admin_redirect( $id, is_string( $result ) ? $result : 'failed' );
}

function bsc_workshops_render_bookings( WP_Post $post, array $settings ): void {
	global $wpdb;
	$table = bsc_workshops_table();
	$id = $post->ID;
	$page = max( 1, absint( $_GET['inscripciones'] ?? 1 ) );
	$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE workshop_id = %d", $id ) );
	$pages = max( 1, (int) ceil( $total / 30 ) );
	$page = min( $page, $pages );
	$bookings = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE workshop_id = %d ORDER BY created_at DESC, id DESC LIMIT 30 OFFSET %d", $id, ( $page - 1 ) * 30 ) );
	$states = array( 'pending' => 'Pendiente de pago', 'confirmed' => 'Confirmada', 'waiting' => 'Lista de aviso', 'cancelled' => 'Cancelada' );
	$demo = bsc_workshops_is_demo( $id );
	?>
	<section class="bsc-admin-panel bsc-workshops-admin__bookings" id="taller-inscripciones">
		<h2>Inscripciones · <?php echo esc_html( $post->post_title ); ?></h2>
		<?php if ( ! $total ) : ?>
			<p>Aún no hay inscripciones para este taller.</p>
		<?php else : ?>
			<div class="bsc-admin-table-wrap">
				<table class="widefat striped">
					<thead><tr><th scope="col">Persona</th><th scope="col">Horario</th><th scope="col">Cupos y total</th><th scope="col">Estado y correo</th><th scope="col">Acciones</th></tr></thead>
					<tbody>
					<?php foreach ( $bookings as $row ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $row->name ); ?></strong>
								<?php if ( $row->email ) : ?><br><a href="mailto:<?php echo esc_attr( $row->email ); ?>"><?php echo esc_html( $row->email ); ?></a><?php endif; ?>
							</td>
							<td><?php echo esc_html( $settings['slots'][ $row->slot_key ]['label'] ?? '—' ); ?></td>
							<td><?php echo $row->seats ? esc_html( $row->seats . ' · ' . bsc_workshops_price( (int) $row->amount ) ) : '—'; ?></td>
							<td>
								<strong><?php echo esc_html( $states[ $row->status ] ?? $row->status ); ?></strong>
								<?php if ( $row->mail_due ) : ?><br><small><?php echo esc_html( 'Correo en cola · ' . wp_date( 'd/m/Y H:i', (int) $row->mail_due ) ); ?></small><?php endif; ?>
								<?php if ( $row->receipt_sent || $row->confirmation_sent ) : ?><br><small><?php echo $row->confirmation_sent ? ( $demo ? 'Confirmación simulada' : 'Confirmación enviada' ) : ( $demo ? 'Solicitud simulada' : 'Solicitud enviada' ); ?></small><?php endif; ?>
								<?php if ( $row->reminder_sent ) : ?><br><small><?php echo esc_html( ( $demo ? 'Recordatorio simulado · ' : 'Recordatorio enviado · ' ) . wp_date( 'd/m/Y H:i', (int) $row->reminder_sent ) ); ?></small><?php endif; ?>
								<?php if ( $row->notice_sent ) : ?><br><small><?php echo $demo ? 'Aviso simulado' : 'Aviso enviado'; ?></small><?php endif; ?>
							</td>
							<td>
								<?php if ( 'cancelled' !== $row->status ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bsc-workshops-admin__actions">
										<input type="hidden" name="action" value="bsc_workshop_booking">
										<input type="hidden" name="workshop" value="<?php echo esc_attr( $id ); ?>">
										<input type="hidden" name="booking" value="<?php echo esc_attr( $row->id ); ?>">
										<?php wp_nonce_field( 'bsc_workshop_booking_' . $row->id ); ?>
										<?php if ( 'pending' === $row->status ) : ?><button class="button button-primary" name="change" value="confirm">Confirmar pago</button><?php endif; ?>
										<?php if ( 'confirmed' === $row->status && $row->email ) : ?><button class="button" name="change" value="remind">Enviar recordatorio</button><?php endif; ?>
										<?php if ( 'waiting' === $row->status && $row->email && ! $row->notice_sent ) : ?><button class="button" name="change" value="notify">Avisar disponibilidad</button><?php endif; ?>
										<button class="button-link-delete" name="change" value="cancel" data-cancel-workshop-booking><?php echo 'waiting' === $row->status ? 'Quitar de la lista' : 'Cancelar reserva'; ?></button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ( $pages > 1 ) : ?>
				<nav aria-label="Páginas de inscripciones"><?php echo wp_kses_post( paginate_links( array(
					'base'         => bsc_workshops_admin_url( $id ) . '&inscripciones=%#%',
					'format'       => '',
					'current'      => $page,
					'total'        => $pages,
					'add_fragment' => '#taller-inscripciones',
				) ) ); ?></nav>
			<?php endif; ?>
		<?php endif; ?>
	</section>
	<?php
}

add_action( 'admin_post_bsc_workshop_booking', 'bsc_workshops_admin_booking' );
function bsc_workshops_admin_booking(): void {
	$id = absint( bsc_workshops_input( 'workshop' ) );
	$booking = absint( bsc_workshops_input( 'booking' ) );
	$action = bsc_workshops_input( 'change' );
	bsc_workshops_admin_guard();
	check_admin_referer( 'bsc_workshop_booking_' . $booking );
	$result = BSC_DB_Lock::run( 'workshop:' . $id, static function () use ( $id, $booking, $action ) {
		global $wpdb;
		$table = bsc_workshops_table();
		wp_cache_delete( $id, 'posts' );
		wp_cache_delete( $id, 'post_meta' );
		$post = bsc_workshops_get( $id );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND workshop_id = %d", $booking, $id ) );
		if ( ! $row || ! $post || 'cancelled' === $row->status ) {
			return 'closed';
		}
		$settings = bsc_workshops_settings( $id );
		$slot = $settings['slots'][ $row->slot_key ] ?? null;
		$now = time();
		$changes = array();
		$notice = 'updated';
		if ( 'cancel' === $action ) {
			$changes = array( 'status' => 'cancelled', 'notice_sent' => 0, 'mail_due' => $slot && $row->email ? $now : 0, 'force_reminder' => 0 );
		} elseif ( 'publish' !== $post->post_status ) {
			return 'closed';
		} elseif ( 'notify' === $action && 'waiting' === $row->status && $row->email && ! $row->notice_sent ) {
			if ( ! bsc_workshops_open_slots( $settings, bsc_workshops_counts( $id ) ) ) {
				return 'no_availability';
			}
			$changes = array( 'mail_due' => $now );
			$notice = 'queued';
		} elseif ( ! $slot || ( $slot['start'] && $slot['start'] <= $now ) ) {
			return 'closed';
		} elseif ( 'confirm' === $action && 'pending' === $row->status ) {
			$changes = array( 'status' => 'confirmed', 'mail_due' => $row->email ? $now : 0 );
		} elseif ( 'remind' === $action && 'confirmed' === $row->status && $row->email ) {
			$changes = array( 'force_reminder' => 1, 'mail_due' => $now );
			$notice = 'queued';
		}
		if ( ! $changes ) {
			return 'closed';
		}
		return BSC_DB_Lock::transaction( static function () use ( $wpdb, $table, $booking, $changes, $post, $id, $settings, $slot, $action, $notice ) {
			if ( false === $wpdb->update( $table, $changes, array( 'id' => $booking ) ) ) {
				return new WP_Error( 'workshop_write_failed' );
			}
			if ( 'cancel' === $action && $slot && 'publish' === $post->post_status && ! bsc_workshops_queue_waiting( $id, $settings ) ) {
				return new WP_Error( 'workshop_write_failed' );
			}
			return $notice;
		} );
	} );
	if ( in_array( $result, array( 'updated', 'queued' ), true ) ) {
		bsc_workshops_kick_mail();
	}
	bsc_workshops_admin_redirect( $id, is_string( $result ) ? $result : 'failed', 'taller-inscripciones' );
}
