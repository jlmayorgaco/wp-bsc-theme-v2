<?php
defined('ABSPATH') || exit;

/**
 * BSC Product Covers
 * Adds bsc_cover_desktop and bsc_cover_mobile fields to the product edit screen.
 * Values stored as attachment IDs in post_meta.
 */

add_action('add_meta_boxes', function () {
    add_meta_box(
        'bsc_product_covers',
        'Covers de Producto',
        'bsc_product_covers_render',
        'product',
        'side',
        'default'
    );
});

function bsc_product_covers_render(WP_Post $post): void {
    wp_nonce_field('bsc_product_covers_save', 'bsc_product_covers_nonce');

    $fields = [
        'bsc_cover_desktop' => 'Cover Desktop',
        'bsc_cover_mobile'  => 'Cover Mobile',
    ];

    foreach ($fields as $key => $label) :
        $attachment_id = (int) get_post_meta($post->ID, $key, true);
        $img_src       = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : '';
        ?>
        <div class="bsc-cover-field" style="margin-bottom:18px;">
            <p style="margin-bottom:4px;"><strong><?php echo esc_html($label); ?></strong></p>
            <div class="bsc-cover-preview" style="margin-bottom:6px;min-height:40px;">
                <?php if ($img_src) : ?>
                <img src="<?php echo esc_url($img_src); ?>"
                     style="max-width:100%;height:auto;display:block;border-radius:3px;" />
                <?php endif; ?>
            </div>
            <input type="hidden"
                   name="<?php echo esc_attr($key); ?>"
                   id="<?php echo esc_attr($key); ?>"
                   value="<?php echo esc_attr($attachment_id ?: ''); ?>" />
            <button type="button"
                    class="button bsc-cover-select"
                    data-field="<?php echo esc_attr($key); ?>">
                <?php echo $attachment_id ? esc_html__('Cambiar imagen') : esc_html__('Seleccionar imagen'); ?>
            </button>
            <?php if ($attachment_id) : ?>
            <button type="button"
                    class="button bsc-cover-remove"
                    data-field="<?php echo esc_attr($key); ?>"
                    style="margin-left:4px;">
                Eliminar
            </button>
            <?php endif; ?>
        </div>
    <?php endforeach;
}

add_action('save_post_product', function (int $post_id): void {
    if (
        !isset($_POST['bsc_product_covers_nonce']) ||
        !wp_verify_nonce(
            sanitize_text_field(wp_unslash($_POST['bsc_product_covers_nonce'])),
            'bsc_product_covers_save'
        )
    ) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    foreach (['bsc_cover_desktop', 'bsc_cover_mobile'] as $key) {
        if (!isset($_POST[$key])) continue;

        $value = absint($_POST[$key]);
        if ($value > 0) {
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
});

add_action('admin_enqueue_scripts', function (string $hook): void {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') return;

    wp_enqueue_media();

    $js_path = get_template_directory() . '/js/admin/product-covers.js';
    wp_enqueue_script(
        'bsc-product-covers-admin',
        get_template_directory_uri() . '/js/admin/product-covers.js',
        ['jquery'],
        file_exists($js_path) ? (string) filemtime($js_path) : '1',
        true
    );
});
