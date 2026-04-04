<?php
/**
 * BSC-008 — Contact form AJAX handler.
 * Destination: BSC_CONTACT_EMAIL constant (defined in functions.php,
 * overridable from wp-config.php).
 */
defined('ABSPATH') || exit;

add_action('wp_ajax_bsc_contact_form_submit',        'bsc_contact_form_submit');
add_action('wp_ajax_nopriv_bsc_contact_form_submit', 'bsc_contact_form_submit');

function bsc_contact_form_submit() {
    check_ajax_referer('bsc_ajax_action', 'nonce');

    // Rate limiting: 1 submission per IP per 60 seconds
    $ip_key = 'bsc_cf_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
    if ( get_transient( $ip_key ) ) {
        wp_send_json_error(['message' => 'Por favor espera un momento antes de enviar otro mensaje.']);
    }

    $name    = isset($_POST['bsc_name'])    ? sanitize_text_field($_POST['bsc_name'])       : '';
    $email   = isset($_POST['bsc_email'])   ? sanitize_email($_POST['bsc_email'])            : '';
    $message = isset($_POST['bsc_message']) ? sanitize_textarea_field($_POST['bsc_message']) : '';

    if ( empty($name) || strlen($name) < 2 ) {
        wp_send_json_error(['message' => 'Por favor ingresa tu nombre.']);
    }
    if ( empty($email) || ! is_email($email) ) {
        wp_send_json_error(['message' => 'Por favor ingresa un correo electrónico válido.']);
    }
    if ( empty($message) || strlen($message) < 10 ) {
        wp_send_json_error(['message' => 'El mensaje debe tener al menos 10 caracteres.']);
    }

    // Set rate-limit transient AFTER validation so failed attempts don't lock the user
    set_transient( $ip_key, 1, 60 );

    $to      = defined('BSC_CONTACT_EMAIL') ? BSC_CONTACT_EMAIL : get_option('admin_email');
    $subject = 'Nuevo mensaje de contacto — ' . esc_html($name);
    $body    = "Nombre: {$name}\nCorreo: {$email}\n\nMensaje:\n{$message}";
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];

    $sent = wp_mail($to, $subject, $body, $headers);

    if ( ! $sent ) {
        wp_send_json_error(['message' => 'No se pudo enviar el mensaje. Por favor intenta nuevamente.']);
    }

    wp_send_json_success(['message' => '¡Gracias por escribirnos! Te respondemos pronto. 🌸']);
}
