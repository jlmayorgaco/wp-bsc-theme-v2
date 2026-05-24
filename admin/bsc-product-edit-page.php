<?php
/**
 * BSC-065: Simplified BSC product editor for Shop Manager.
 * Allows editing: name, short description, main image, gallery, categories,
 * tags, SKU, dual stock, and review toggle without the full WC editor.
 */
defined('ABSPATH') || exit;

function bsc_product_edit_page_is_active(): bool {
    return sanitize_key($_GET['page'] ?? '') === 'bsc-product-edit';
}

function bsc_get_product_edit_category_tree_data(): array {
    $terms = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);

    if (!is_array($terms)) {
        return [];
    }

    $build_tree = function (array $items, int $parent_id) use (&$build_tree): array {
        $nodes = [];

        foreach ($items as $term) {
            if ((int) $term->parent !== $parent_id) {
                continue;
            }

            $nodes[] = [
                'id'       => (int) $term->term_id,
                'name'     => (string) $term->name,
                'slug'     => (string) $term->slug,
                'children' => $build_tree($items, (int) $term->term_id),
            ];
        }

        return $nodes;
    };

    return $build_tree($terms, 0);
}

function bsc_get_product_edit_current_category_ids(int $product_id): array {
    $term_ids = wp_get_object_terms($product_id, 'product_cat', ['fields' => 'ids']);

    if (!is_array($term_ids)) {
        return [];
    }

    return array_values(array_map('intval', $term_ids));
}

function bsc_get_product_edit_script_data(int $product_id): array {
    return [
        'catTree'     => bsc_get_product_edit_category_tree_data(),
        'currentCats' => bsc_get_product_edit_current_category_ids($product_id),
        'rootSlugs'   => ['group-skin-care', 'group-hair-care', 'group-make-up'],
        'rootLabels'  => [
            'group-skin-care' => 'Skin Care',
            'group-hair-care' => 'Hair Care',
            'group-make-up'   => 'Make Up',
        ],
        'strings'     => [
            'mainImageTitle'  => 'Imagen principal',
            'mainImageButton' => 'Usar imagen',
            'galleryTitle'    => 'Galeria del producto',
            'galleryButton'   => 'Usar estas imagenes',
            'searchPlaceholder' => 'Buscar...',
            'noCategories'      => 'No hay categorias para este grupo.',
            'noSubcategories'   => 'Sin subcategorias',
        ],
    ];
}

add_action('admin_enqueue_scripts', 'bsc_enqueue_product_edit_page_assets');
function bsc_enqueue_product_edit_page_assets(string $hook): void {
    if (!bsc_product_edit_page_is_active()) {
        return;
    }

    $css_path = get_template_directory() . '/admin/bsc-product-edit.css';
    wp_enqueue_style(
        'bsc-product-edit-admin',
        get_template_directory_uri() . '/admin/bsc-product-edit.css',
        [],
        file_exists($css_path) ? (string) filemtime($css_path) : '1'
    );

    $js_path = get_template_directory() . '/js/admin/bsc-product-edit.js';
    wp_enqueue_script(
        'bsc-product-edit-admin',
        get_template_directory_uri() . '/js/admin/bsc-product-edit.js',
        ['jquery'],
        file_exists($js_path) ? (string) filemtime($js_path) : '1',
        true
    );

    wp_enqueue_media();

    $product_id = absint($_GET['id'] ?? 0);
    wp_localize_script(
        'bsc-product-edit-admin',
        'bscProductEditData',
        bsc_get_product_edit_script_data($product_id)
    );
}

function bsc_render_product_edit_page(): void {
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_die(esc_html__('No tienes permisos.', 'bsc-2-0'));
    }

    $product_id = absint($_GET['id'] ?? 0);
    if (!$product_id || get_post_type($product_id) !== 'product') {
        wp_die(esc_html__('Producto no encontrado.', 'bsc-2-0'));
    }

    $product = wc_get_product($product_id);
    $post = get_post($product_id);

    if (!$product || !$post instanceof WP_Post) {
        wp_die(esc_html__('Producto no encontrado.', 'bsc-2-0'));
    }

    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['bsc_product_edit_nonce'])) {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsc_product_edit_nonce'])), 'bsc_product_edit_action')) {
            wp_die(esc_html__('Solicitud no valida.', 'bsc-2-0'));
        }

        $new_title = sanitize_text_field(wp_unslash($_POST['post_title'] ?? ''));
        $new_excerpt = wp_kses_post(wp_unslash($_POST['post_excerpt'] ?? ''));
        $post_status_raw = isset($_POST['post_status']) ? sanitize_key(wp_unslash($_POST['post_status'])) : '';
        $new_status = in_array($post_status_raw, ['publish', 'draft'], true)
            ? $post_status_raw
            : 'draft';

        wp_update_post([
            'ID'           => $product_id,
            'post_title'   => $new_title,
            'post_excerpt' => $new_excerpt,
            'post_status'  => $new_status,
        ]);

        $sku = sanitize_text_field(wp_unslash($_POST['_sku'] ?? ''));
        update_post_meta($product_id, '_sku', $sku);

        $thumbnail_id = absint($_POST['_thumbnail_id'] ?? 0);
        if ($thumbnail_id) {
            set_post_thumbnail($product_id, $thumbnail_id);
        } elseif (isset($_POST['_remove_thumbnail'])) {
            delete_post_thumbnail($product_id);
        }

        $gallery_ids = sanitize_text_field(wp_unslash($_POST['_product_image_gallery'] ?? ''));
        update_post_meta($product_id, '_product_image_gallery', $gallery_ids);

        $cat_ids = array_map('absint', (array) ($_POST['product_cat'] ?? []));
        wp_set_object_terms($product_id, $cat_ids, 'product_cat');

        $tag_input = sanitize_text_field(wp_unslash($_POST['product_tag'] ?? ''));
        $tags = array_filter(array_map('trim', explode(',', $tag_input)));
        wp_set_object_terms($product_id, $tags, 'product_tag');

        $comment_status = isset($_POST['comment_status']) ? 'open' : 'closed';
        wp_update_post([
            'ID'             => $product_id,
            'comment_status' => $comment_status,
        ]);

        $stock_bodega = max(0, intval($_POST['_stock_bodega'] ?? 0));
        $stock_tienda = max(0, intval($_POST['_stock_tienda'] ?? 0));
        $envio_tipo_raw = isset($_POST['_envio_tipo']) ? sanitize_key(wp_unslash($_POST['_envio_tipo'])) : '';
        $envio_tipo = in_array($envio_tipo_raw, ['bodega', 'tienda', 'ambos'], true)
            ? $envio_tipo_raw
            : 'bodega';

        $current_stock = BSC_Stock::get_stock($product_id);
        if ((int) $current_stock['bodega'] !== $stock_bodega) {
            BSC_Stock::adjust($product_id, 'bodega', $stock_bodega - (int) $current_stock['bodega'], 'Edicion BSC Product Edit');
        }

        if ((int) $current_stock['tienda'] !== $stock_tienda) {
            BSC_Stock::adjust($product_id, 'tienda', $stock_tienda - (int) $current_stock['tienda'], 'Edicion BSC Product Edit');
        }

        update_post_meta($product_id, '_envio_tipo', $envio_tipo);

        $repurchase_days_raw = isset($_POST['_bsc_repurchase_days'])
            ? trim((string) wp_unslash($_POST['_bsc_repurchase_days']))
            : '';

        if ($repurchase_days_raw === '') {
            delete_post_meta($product_id, '_bsc_repurchase_days');
        } else {
            update_post_meta($product_id, '_bsc_repurchase_days', max(1, intval($repurchase_days_raw)));
        }

        wp_safe_redirect(admin_url('admin.php?page=bsc-products&saved=1'));
        exit;
    }

    $stock = BSC_Stock::get_stock($product_id);
    $sku = $product->get_sku();
    $gallery = $product->get_gallery_image_ids();
    $thumbnail_id = get_post_thumbnail_id($product_id);
    $thumbnail_src = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
    $repurchase_days = (string) get_post_meta($product_id, '_bsc_repurchase_days', true);
    $default_repurchase_days = function_exists('bsc_get_followup_email_setting')
        ? (int) bsc_get_followup_email_setting('bsc_default_repurchase_days')
        : 30;
    $all_tags = wp_get_object_terms($product_id, 'product_tag', ['fields' => 'names']);

    $cover_fields = [
        'bsc_cover_desktop'  => 'Cover Desktop',
        'bsc_cover_mobile'   => 'Cover Mobile',
        '_bsc_extra_image_1' => 'Imagen extra 1',
        '_bsc_extra_image_2' => 'Imagen extra 2',
        '_bsc_extra_image_3' => 'Imagen extra 3',
    ];
    $cover_values = [];
    foreach ($cover_fields as $key => $label) {
        $cover_values[$key] = (int) get_post_meta($product_id, $key, true);
    }

    ?>
    <div class="wrap bsc-admin-product-edit">
        <h1>
            Editar Producto
            <span class="bsc-admin-product-edit__title-meta">#<?php echo esc_html($product_id); ?></span>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="page-title-action">&larr; Volver a Productos</a>
        <hr class="wp-header-end">

        <?php if (isset($_GET['saved'])) : ?>
            <div class="notice notice-success is-dismissible"><p>&#10003; Producto actualizado correctamente.</p></div>
        <?php endif; ?>

        <form method="post" class="bsc-admin-product-edit__form">
            <?php wp_nonce_field('bsc_product_edit_action', 'bsc_product_edit_nonce'); ?>
            <?php wp_nonce_field('bsc_product_covers_save', 'bsc_product_covers_nonce'); ?>

            <div class="bsc-product-edit-grid bsc-admin-product-edit__grid">
                <div>
                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Basico</h2>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Nombre del producto</span>
                            <input type="text" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" class="large-text" required>
                        </label>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Descripcion corta</span>
                            <textarea name="post_excerpt" rows="4" class="large-text"><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                        </label>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">SKU</span>
                            <input type="text" name="_sku" value="<?php echo esc_attr($sku); ?>" class="regular-text">
                        </label>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Estado</span>
                            <select name="post_status">
                                <option value="publish" <?php selected($post->post_status, 'publish'); ?>>Publicado</option>
                                <option value="draft" <?php selected($post->post_status, 'draft'); ?>>Borrador</option>
                            </select>
                        </label>

                        <label class="bsc-admin-product-edit__field bsc-admin-product-edit__field--checkbox">
                            <input type="checkbox" name="comment_status" value="open" <?php checked($post->comment_status, 'open'); ?>>
                            Habilitar reseñas de clientes
                        </label>
                    </div>

                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Tags</h2>
                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-help">Separados por coma</span>
                            <input type="text" name="product_tag" value="<?php echo esc_attr(implode(', ', is_array($all_tags) ? $all_tags : [])); ?>" class="large-text">
                        </label>
                    </div>
                </div>

                <div>
                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Imagen principal</h2>
                        <div id="bsc-main-image-preview">
                            <?php if ($thumbnail_src) : ?>
                                <img src="<?php echo esc_url($thumbnail_src); ?>" class="bsc-admin-product-edit__preview-image" alt="">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="_thumbnail_id" id="bsc-thumbnail-id" value="<?php echo esc_attr($thumbnail_id ?: ''); ?>">
                        <input type="hidden" name="_remove_thumbnail" id="bsc-remove-thumbnail-flag" value="">
                        <button type="button" class="button" id="bsc-select-main-image">Seleccionar imagen</button>
                        <button type="button" class="button bsc-admin-product-edit__button-spaced<?php echo esc_attr($thumbnail_id ? '' : ' is-hidden'); ?>" id="bsc-remove-main-image">Quitar imagen</button>
                    </div>

                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Galeria</h2>
                        <div id="bsc-gallery-preview" class="bsc-admin-product-edit__gallery">
                            <?php foreach ($gallery as $gallery_id) : ?>
                                <?php $gallery_src = wp_get_attachment_image_url($gallery_id, [80, 80]); ?>
                                <?php if ($gallery_src) : ?>
                                    <img src="<?php echo esc_url($gallery_src); ?>" class="bsc-admin-product-edit__gallery-thumb" alt="">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="_product_image_gallery" id="bsc-gallery-ids" value="<?php echo esc_attr(implode(',', $gallery)); ?>">
                        <button type="button" class="button" id="bsc-select-gallery">Editar galeria</button>
                    </div>

                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Stock Dual (BSC)</h2>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Stock Bodega (web)</span>
                            <input type="number" min="0" name="_stock_bodega" value="<?php echo esc_attr($stock['bodega']); ?>" class="bsc-admin-product-edit__number-input">
                        </label>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Stock Tienda (showroom)</span>
                            <input type="number" min="0" name="_stock_tienda" value="<?php echo esc_attr($stock['tienda']); ?>" class="bsc-admin-product-edit__number-input">
                        </label>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Tipo de envio</span>
                            <select name="_envio_tipo">
                                <option value="bodega" <?php selected($stock['envio_tipo'], 'bodega'); ?>>Bodega (web)</option>
                                <option value="tienda" <?php selected($stock['envio_tipo'], 'tienda'); ?>>Tienda (showroom)</option>
                                <option value="ambos" <?php selected($stock['envio_tipo'], 'ambos'); ?>>Ambos</option>
                            </select>
                        </label>
                    </div>

                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Emails de seguimiento</h2>

                        <label class="bsc-admin-product-edit__field">
                            <span class="bsc-admin-product-edit__field-label">Dias para recompra</span>
                            <input
                                type="number"
                                min="1"
                                step="1"
                                name="_bsc_repurchase_days"
                                value="<?php echo esc_attr($repurchase_days); ?>"
                                placeholder="<?php echo esc_attr((string) $default_repurchase_days); ?>"
                                class="bsc-admin-product-edit__number-input bsc-admin-product-edit__number-input--wide"
                            >
                            <span class="bsc-admin-product-edit__field-note">
                                Dejalo vacio para usar el valor global del modulo Emails: <?php echo esc_html((string) $default_repurchase_days); ?> dias.
                            </span>
                        </label>
                    </div>

                    <div class="postbox bsc-admin-product-edit__card">
                        <h2 class="bsc-admin-product-edit__section-title">Covers de Producto</h2>
                        <?php foreach ($cover_fields as $key => $label) : ?>
                            <?php
                            $attachment_id = $cover_values[$key];
                            $image_src = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'thumbnail') : '';
                            ?>
                            <div class="bsc-cover-field bsc-admin-cover-field">
                                <p class="bsc-admin-cover-label"><strong><?php echo esc_html($label); ?></strong></p>
                                <div class="bsc-cover-preview bsc-admin-cover-preview">
                                    <?php if ($image_src) : ?>
                                        <img src="<?php echo esc_url($image_src); ?>" class="bsc-admin-cover-image" alt="">
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($attachment_id ?: ''); ?>">
                                <button type="button" class="button bsc-cover-select" data-field="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($attachment_id ? 'Cambiar imagen' : 'Seleccionar imagen'); ?>
                                </button>
                                <?php if ($attachment_id) : ?>
                                    <button type="button" class="button bsc-cover-remove bsc-admin-cover-remove" data-field="<?php echo esc_attr($key); ?>">Eliminar</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="postbox bsc-admin-product-edit__card">
                <h2 class="bsc-admin-product-edit__section-title">Categorias</h2>
                <div id="bsc-root-tabs" class="bsc-admin-product-edit__tabs"></div>
                <div id="bsc-cat-branches"></div>
                <div id="bsc-cat-hidden-inputs" class="bsc-admin-product-edit__hidden"></div>
            </div>

            <div class="bsc-admin-product-edit__actions">
                <button type="submit" class="button button-primary button-large">Guardar cambios</button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="button button-large bsc-admin-product-edit__action-link">Cancelar</a>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="button bsc-admin-product-edit__action-link" target="_blank" rel="noopener noreferrer">Ver en tienda &#8599;</a>
            </div>
        </form>
    </div>
    <?php
}
