<?php
defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'bsc_bp_enqueue_scripts');

function bsc_bp_enqueue_scripts() {

        wp_enqueue_script(
            'bsc-bubble-points-modal',
            get_stylesheet_directory_uri() . '/plugins/bubble-points/scripts/bubble-points-modal.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('bsc-bubble-points-modal', 'bsc_points', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('bsc_redeem_points')
        ]);

}
