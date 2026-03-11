<?php

add_action('wp_ajax_bsc_creator_apply', 'bsc_creator_apply');
add_action('wp_ajax_nopriv_bsc_creator_apply', 'bsc_creator_apply');

function bsc_creator_apply() {
    $nombre    = isset($_POST['nombre'])    ? sanitize_text_field($_POST['nombre'])    : '';
    $email     = isset($_POST['email'])     ? sanitize_email($_POST['email'])          : '';
    $instagram = isset($_POST['instagram']) ? sanitize_text_field($_POST['instagram']) : '';
    $tiktok    = isset($_POST['tiktok'])    ? sanitize_text_field($_POST['tiktok'])    : '';
    $mensaje   = isset($_POST['mensaje'])   ? sanitize_textarea_field($_POST['mensaje']) : '';

    if (empty($nombre) || empty($email) || !is_email($email)) {
        wp_send_json_error(['message' => 'Por favor completa tu nombre y correo electrónico válido.']);
    }

    // Save application
    $applications = get_option('bsc_creator_applications', []);
    $applications[] = [
        'nombre'    => $nombre,
        'email'     => $email,
        'instagram' => $instagram,
        'tiktok'    => $tiktok,
        'mensaje'   => $mensaje,
        'date'      => current_time('mysql'),
    ];
    update_option('bsc_creator_applications', $applications, false);

    // Notify admin
    $admin_email = 'wallamejorge@hotmail.com'; // get_option('admin_email');
    $body  = "Nueva solicitud Bubble Creator\n\n";
    $body .= "Nombre: {$nombre}\n";
    $body .= "Email: {$email}\n";
    $body .= "Instagram: {$instagram}\n";
    $body .= "TikTok: {$tiktok}\n";
    $body .= "Mensaje:\n{$mensaje}\n\n";
    $body .= "Fecha: " . current_time('mysql');

    wp_mail($admin_email, '¡Nueva solicitud Bubble Creator!', $body, ['Content-Type: text/plain; charset=UTF-8']);

    wp_send_json_success(['message' => '¡Gracias por tu solicitud! El equipo de BSC revisará tu perfil y se pondrá en contacto contigo pronto. 💛']);
}
