<?php

add_action('wp_ajax_bsc_newsletter_subscribe', 'bsc_newsletter_subscribe');
add_action('wp_ajax_nopriv_bsc_newsletter_subscribe', 'bsc_newsletter_subscribe');

function bsc_newsletter_subscribe() {
    check_ajax_referer('bsc_ajax_action', 'nonce');

    // Rate limiting: 1 suscripción por IP por minuto
    $ip_key = 'bsc_nl_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
    if ( get_transient( $ip_key ) ) {
        wp_send_json_error(['message' => 'Por favor espera un momento antes de intentar de nuevo.']);
    }
    set_transient( $ip_key, 1, 60 );

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Por favor ingresa un correo electrónico válido.']);
    }

    $subscribers = get_option('bsc_newsletter_subscribers', []);

    // Duplicate check
    $existing = array_column($subscribers, 'email');
    if (in_array($email, $existing)) {
        wp_send_json_success(['message' => '¡Ya eres parte de la comunidad BSC! Pronto tendrás novedades.']);
    }

    $subscribers[] = [
        'email' => $email,
        'date'  => current_time('mysql'),
    ];
    update_option('bsc_newsletter_subscribers', $subscribers, false);

    // Notify admin
    $admin_email = get_option('admin_email');
    wp_mail(
        $admin_email,
        '¡Nueva suscripción BSC Newsletter!',
        "Nueva suscripción recibida desde el sitio web.\n\nCorreo: {$email}\nFecha: " . current_time('mysql'),
        ['Content-Type: text/plain; charset=UTF-8']
    );

    wp_send_json_success(['message' => '¡Bienvenida a la comunidad BSC! 💛 Pronto tendrás novedades exclusivas.']);
}
