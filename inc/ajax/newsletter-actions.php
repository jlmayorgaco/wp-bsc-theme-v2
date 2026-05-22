<?php
defined('ABSPATH') || exit;

add_action('wp_ajax_bsc_newsletter_subscribe', 'bsc_newsletter_subscribe');
add_action('wp_ajax_nopriv_bsc_newsletter_subscribe', 'bsc_newsletter_subscribe');

function bsc_newsletter_subscribe() {
    check_ajax_referer('bsc_ajax_action', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Por favor ingresa un correo electronico valido.']);
    }

    if ( function_exists( 'bsc_rate_limit' ) ) {
        bsc_rate_limit( 'newsletter', 1, MINUTE_IN_SECONDS );
    }

    $subscribers = get_option('bsc_newsletter_subscribers', []);
    $existing = array_column($subscribers, 'email');

    if (in_array($email, $existing, true)) {
        wp_send_json_success(['message' => 'Ya eres parte de la comunidad BSC. Pronto tendras novedades.']);
    }

    $subscribers[] = [
        'email' => $email,
        'date'  => current_time('mysql'),
    ];
    update_option('bsc_newsletter_subscribers', $subscribers, false);

    $admin_email = get_option('admin_email');
    wp_mail(
        $admin_email,
        'Nueva suscripcion BSC Newsletter',
        "Nueva suscripcion recibida desde el sitio web.\n\nCorreo: {$email}\nFecha: " . current_time('mysql'),
        ['Content-Type: text/plain; charset=UTF-8']
    );

    wp_send_json_success(['message' => 'Bienvenida a la comunidad BSC. Pronto tendras novedades exclusivas.']);
}
