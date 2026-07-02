<?php
/**
 * BSC-008: Contact form AJAX handler.
 * Stores messages in BSC Admin and sends a notification email.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bsc_contact_form_submit', 'bsc_contact_form_submit' );
add_action( 'wp_ajax_nopriv_bsc_contact_form_submit', 'bsc_contact_form_submit' );

function bsc_get_contact_email_recipient(): string {
	$option_email = sanitize_email( (string) get_option( 'bsc_contact_email', '' ) );
	if ( $option_email && is_email( $option_email ) ) {
		return $option_email;
	}

	if ( defined( 'BSC_CONTACT_EMAIL' ) && is_email( BSC_CONTACT_EMAIL ) ) {
		return BSC_CONTACT_EMAIL;
	}

	return (string) get_option( 'admin_email' );
}

function bsc_contact_form_submit() {
	check_ajax_referer( 'bsc_ajax_action', 'nonce' );

	$name    = isset( $_POST['bsc_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_name'] ) ) : '';
	$email   = isset( $_POST['bsc_email'] ) ? sanitize_email( wp_unslash( $_POST['bsc_email'] ) ) : '';
	$message = isset( $_POST['bsc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bsc_message'] ) ) : '';

	if (empty( $name ) || strlen( $name ) < 2) {
		wp_send_json_error( array( 'message' => 'Por favor ingresa tu nombre.' ) );
	}

	if (empty( $email ) || ! is_email( $email )) {
		wp_send_json_error( array( 'message' => 'Por favor ingresa un correo electronico valido.' ) );
	}

	if (empty( $message ) || strlen( $message ) < 10) {
		wp_send_json_error( array( 'message' => 'El mensaje debe tener al menos 10 caracteres.' ) );
	}

	if (function_exists( 'bsc_rate_limit' )) {
		bsc_rate_limit( 'contact', 1, MINUTE_IN_SECONDS );
	} elseif (function_exists( 'bsc_rate_limit_passed' ) && ! bsc_rate_limit_passed( 'contact', 1, MINUTE_IN_SECONDS )) {
		wp_send_json_error( array( 'message' => 'Por favor espera un momento antes de enviar otro mensaje.' ) );
	}

	$to      = bsc_get_contact_email_recipient();
	$subject = 'Nuevo mensaje de contacto - ' . esc_html( $name );
	$body    = "Nombre: {$name}\nCorreo: {$email}\n\nMensaje:\n{$message}";
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$message_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'contact_', true );

	if ( function_exists( 'bsc_contact_get_messages' ) && function_exists( 'bsc_contact_save_messages' ) ) {
		$messages   = bsc_contact_get_messages();
		$messages[] = array_merge(
			array(
				'id'        => $message_id,
				'name'      => $name,
				'email'     => $email,
				'message'   => $message,
				'status'    => 'new',
				'source'    => 'contact-form',
				'date'      => current_time( 'mysql' ),
				'mail_sent' => false,
			),
			function_exists( 'bsc_privacy_form_metadata' ) ? bsc_privacy_form_metadata() : array()
		);
		bsc_contact_save_messages( $messages );
	}

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( $sent && function_exists( 'bsc_contact_get_messages' ) && function_exists( 'bsc_contact_save_messages' ) ) {
		$messages = bsc_contact_get_messages();

		foreach ( $messages as $index => $stored_message ) {
			if ( $message_id !== (string) ( $stored_message['id'] ?? '' ) ) {
				continue;
			}

			$messages[ $index ]['mail_sent'] = true;
			bsc_contact_save_messages( $messages );
			break;
		}
	}

	wp_send_json_success( array( 'message' => 'Gracias por escribirnos. Te respondemos pronto.' ) );
}
