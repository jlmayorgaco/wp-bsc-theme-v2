<?php
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/bsc-email-design-system.php';

$email_title     = $title;
$email_preheader = $workshop->post_title;
$email_hero      = 'bsc-email-hero-rainbow.png';
$messages = array(
	'receipt'      => 'Apartamos tus cupos. El equipo BSC se pondrá en contacto contigo para coordinar el pago.',
	'confirmation' => '¡Tu reserva está confirmada! Te esperamos en nuestro taller.',
	'reminder'     => 'Te recordamos el horario de tu taller. ¡Nos vemos pronto!',
	'availability' => 'Ya hay cupos en este taller. Puedes elegir un horario desde la agenda.',
	'cancellation' => 'Cancelamos tu reserva y liberamos los cupos.',
);
$details = '<strong>' . esc_html( $workshop->post_title ) . '</strong>';
if ( $slot && 'availability' !== $type ) {
	$details .= '<br>' . esc_html( $slot['label'] );
	$details .= '<br>Cupos: ' . (int) $booking->seats;
	if ( in_array( $type, array( 'confirmation', 'reminder' ), true ) && '' !== $venue ) {
		$details .= '<br>' . nl2br( esc_html( $venue ) );
	}
	if ( 'receipt' === $type ) {
		$details .= '<br>Total: ' . esc_html( bsc_workshops_price( (int) $booking->amount ) );
	}
}
require __DIR__ . '/bsc-email-header.php';
bsc_email_render_message( 'Hola <strong>' . esc_html( $booking->name ) . '</strong>,<br>' . esc_html( $messages[ $type ] ) . '<br><br>' . $details );
bsc_email_render_button_row( array( array( 'url' => bsc_email_publicize_url( bsc_workshops_public_url() ), 'label' => 'Ver agenda' ) ) );
require __DIR__ . '/bsc-email-footer.php';
