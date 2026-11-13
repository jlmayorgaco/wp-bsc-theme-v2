<?php
/** Korean workshops and reservations. */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/privacy.php';
if ( is_admin() ) {
	require_once __DIR__ . '/admin.php';
}

add_action( 'init', 'bsc_workshops_register' );
function bsc_workshops_register(): void {
	register_post_type( 'bsc_workshop', array( 'label' => 'Talleres', 'public' => false, 'show_ui' => false, 'supports' => array( 'title', 'excerpt' ) ) );
	if ( ! get_option( 'bsc_workshops_page_ready' ) ) {
		BSC_DB_Lock::run( 'workshops-page', static function () {
			$page = get_page_by_path( 'talleres', OBJECT, 'page' );
			if ( ! $page ) {
				$id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'talleres', 'post_title' => 'Talleres coreanos' ), true );
				if ( is_wp_error( $id ) ) {
					return $id;
				}
				update_post_meta( $id, '_wp_page_template', 'page-talleres.php' );
			}
			update_option( 'bsc_workshops_page_ready', 1, false );
		} );
	}
}

function bsc_workshops_is_page(): bool {
	return is_page( 'talleres' ) || is_page_template( 'page-talleres.php' );
}

add_action( 'template_redirect', static function () {
	if ( bsc_workshops_is_page() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
} );

add_action( 'wp_enqueue_scripts', static function () {
	if ( ! bsc_workshops_is_page() ) {
		return;
	}
	$base = get_template_directory_uri() . '/plugins/talleres/';
	wp_enqueue_style( 'bsc-workshops', $base . 'talleres.css', array(), bsc_get_asset_version( 'plugins/talleres/talleres.css' ) );
	wp_enqueue_script( 'bsc-workshops', $base . 'talleres.js', array(), bsc_get_asset_version( 'plugins/talleres/talleres.js' ), true );
} );

function bsc_workshops_input( string $key ): string {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
}

function bsc_workshops_public_url(): string {
	return bsc_get_static_page_url( 'talleres' );
}

add_action( 'admin_post_bsc_workshop_register', 'bsc_workshops_submit' );
add_action( 'admin_post_nopriv_bsc_workshop_register', 'bsc_workshops_submit' );
function bsc_workshops_submit(): void {
	$id     = absint( bsc_workshops_input( 'workshop' ) );
	$slot   = bsc_workshops_input( 'slot' );
	$name   = sanitize_text_field( bsc_workshops_input( 'name' ) );
	$email  = strtolower( sanitize_email( bsc_workshops_input( 'email' ) ) );
	$seats  = '' === $slot ? 0 : filter_var( bsc_workshops_input( 'seats' ), FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1, 'max_range' => 999 ) ) );
	$result = 'invalid';

	if ( ! wp_verify_nonce( bsc_workshops_input( '_bsc_workshop_nonce' ), 'bsc_workshop_register_' . $id ) ) {
		$result = 'expired';
	} elseif ( '' !== bsc_workshops_input( 'website' ) ) {
		$result = 'received';
	} elseif ( $id && is_email( $email ) && strlen( $email ) <= 254 && strlen( $name ) >= 2 && mb_strlen( $name ) <= 120
		&& ( '' === $slot || wp_is_uuid( $slot, 4 ) ) && false !== $seats && 'yes' === bsc_workshops_input( 'consent' ) ) {
		if ( function_exists( 'bsc_rate_limit_passed' ) && ! bsc_rate_limit_passed( 'workshops', 6, MINUTE_IN_SECONDS ) ) {
			$result = 'rate';
		} else {
			$result = bsc_workshops_register_guest( $id, $slot, $name, $email, $seats );
			if ( in_array( $result, array( 'pending', 'confirmed' ), true ) ) {
				bsc_workshops_kick_mail();
			}
			if ( in_array( $result, array( 'pending', 'confirmed', 'waiting' ), true ) && bsc_workshops_is_demo( $id ) ) {
				$result = 'demo_' . $result;
			}
		}
	}

	wp_safe_redirect( add_query_arg( 'taller_resultado', $result, bsc_workshops_public_url() ) . '#talleres-agenda', 303 );
	exit;
}

function bsc_workshops_message( string $status ): string {
	$messages = array(
		'confirmed' => '¡Tu cupo está reservado! Te enviaremos los detalles por correo.',
		'pending'   => 'Recibimos tu solicitud y apartamos tus cupos. Te contactaremos por correo para coordinar el pago.',
		'waiting'   => 'Ya estás en la lista. Te avisaremos por correo cuando haya disponibilidad.',
		'demo_pending'   => 'Solicitud de prueba guardada. Los cupos quedaron apartados y el correo se simula.',
		'demo_confirmed' => 'Reserva de prueba confirmada. El correo se simula.',
		'demo_waiting'   => 'Inscripción de prueba guardada en la lista de aviso.',
		'received'  => 'Recibimos tu solicitud.',
		'existing'  => 'Ya registramos una solicitud con ese correo. Revisa tu bandeja de entrada o escríbenos si necesitas ayuda.',
		'full'      => 'Ya no quedan suficientes cupos en ese horario. Elige otro horario o pide un aviso cuando haya disponibilidad.',
		'available' => 'Ya hay cupos disponibles. Elige un horario para reservar.',
		'invalid'   => 'Revisa tu nombre, correo y los datos de la reserva.',
		'expired'   => 'La página se actualizó. Vuelve a seleccionar el horario y envía el formulario.',
		'rate'      => 'Espera un minuto antes de volver a intentarlo.',
		'closed'    => 'Ese taller u horario ya no está disponible.',
		'failed'    => 'No pudimos guardar tu solicitud. Inténtalo de nuevo en un momento.',
	);
	return $messages[ $status ] ?? '';
}
