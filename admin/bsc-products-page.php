<?php
/**
 * BSC-062: BSC Products table with explicit inline stock editing.
 * BSC-066: Includes AJAX handlers for stock adjustment and history log.
 */
defined('ABSPATH') || exit;

add_action('wp_ajax_bsc_update_product_stock', 'bsc_ajax_update_product_stock');
function bsc_ajax_update_product_stock(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_send_json_error(['message' => 'Sin permisos'], 403);
    }

    $product_id = absint($_POST['product_id'] ?? 0);
    $type = in_array($_POST['type'] ?? '', ['bodega', 'tienda'], true)
        ? sanitize_text_field(wp_unslash($_POST['type']))
        : 'bodega';
    $value = max(0, intval($_POST['value'] ?? 0));
    $meta_key = $type === 'tienda' ? '_stock_tienda' : '_stock_bodega';

    if (!$product_id || get_post_type($product_id) !== 'product') {
        wp_send_json_error(['message' => 'Producto invÃ¡lido'], 400);
    }

    $old_value = (int) get_post_meta($product_id, $meta_key, true);
    update_post_meta($product_id, $meta_key, $value);

    if (class_exists('BSC_Stock') && $old_value !== $value) {
        BSC_Stock::adjust($product_id, $type, $value - $old_value, 'EdiciÃ³n inline BSC Products');
        update_post_meta($product_id, $meta_key, $value);
    }

    wp_send_json_success(['new_value' => $value]);
}

add_action('wp_ajax_bsc_update_product_stocks', 'bsc_ajax_update_product_stocks');
function bsc_ajax_update_product_stocks(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_send_json_error(['message' => 'Sin permisos'], 403);
    }

    $product_id = absint($_POST['product_id'] ?? 0);
    $bodega = max(0, intval($_POST['bodega'] ?? 0));
    $tienda = max(0, intval($_POST['tienda'] ?? 0));

    if (!$product_id || get_post_type($product_id) !== 'product') {
        wp_send_json_error(['message' => 'Producto invÃ¡lido'], 400);
    }

    $current_bodega = (int) get_post_meta($product_id, '_stock_bodega', true);
    $current_tienda = (int) get_post_meta($product_id, '_stock_tienda', true);

    if (class_exists('BSC_Stock')) {
        if ($current_bodega !== $bodega) {
            BSC_Stock::adjust($product_id, 'bodega', $bodega - $current_bodega, 'EdiciÃ³n inline BSC Products');
        }

        if ($current_tienda !== $tienda) {
            BSC_Stock::adjust($product_id, 'tienda', $tienda - $current_tienda, 'EdiciÃ³n inline BSC Products');
        }
    } else {
        update_post_meta($product_id, '_stock_bodega', $bodega);
        update_post_meta($product_id, '_stock_tienda', $tienda);
    }

    wp_send_json_success([
        'bodega' => $bodega,
        'tienda' => $tienda,
    ]);
}

add_action('wp_ajax_bsc_adjust_stock', 'bsc_ajax_adjust_stock');
function bsc_ajax_adjust_stock(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_send_json_error(['message' => 'Sin permisos'], 403);
    }

    $product_id = absint($_POST['product_id'] ?? 0);
    $type = in_array($_POST['type'] ?? '', ['bodega', 'tienda'], true)
        ? sanitize_text_field(wp_unslash($_POST['type']))
        : 'bodega';
    $delta = intval($_POST['delta'] ?? 0);
    $reason = sanitize_text_field(wp_unslash($_POST['reason'] ?? ''));

    if (!$product_id || get_post_type($product_id) !== 'product') {
        wp_send_json_error(['message' => 'Producto invÃ¡lido'], 400);
    }

    $new_stock = BSC_Stock::adjust($product_id, $type, $delta, $reason);
    wp_send_json_success(['new_stock' => $new_stock]);
}

add_action('wp_ajax_bsc_get_stock_log', 'bsc_ajax_get_stock_log');
function bsc_ajax_get_stock_log(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_send_json_error(['message' => 'Sin permisos'], 403);
    }

    $product_id = absint($_GET['product_id'] ?? 0);
    if (!$product_id) {
        wp_send_json_error(['message' => 'Producto invÃ¡lido'], 400);
    }

    $log = BSC_Stock::get_log($product_id);
    $enriched = array_map(static function (array $entry): array {
        $entry['username'] = $entry['user_id']
            ? (get_userdata($entry['user_id'])->display_name ?? 'â€”')
            : 'â€”';
        return $entry;
    }, $log);

    wp_send_json_success(['log' => $enriched]);
}

add_action('admin_enqueue_scripts', 'bsc_enqueue_products_page_assets');
function bsc_enqueue_products_page_assets(string $hook): void {
    if (strpos($hook, 'bsc-products') === false) {
        return;
    }

    bsc_enqueue_admin_ui_assets();

    $css_path = get_template_directory() . '/admin/bsc-products.css';
    wp_enqueue_style(
        'bsc-products-admin',
        get_template_directory_uri() . '/admin/bsc-products.css',
        ['bsc-admin-ui'],
        file_exists($css_path) ? (string) filemtime($css_path) : '1'
    );

    $js_path = get_template_directory() . '/js/admin/bsc-products.js';
    wp_enqueue_script(
        'bsc-products-admin',
        get_template_directory_uri() . '/js/admin/bsc-products.js',
        ['jquery'],
        file_exists($js_path) ? (string) filemtime($js_path) : '1',
        true
    );

    wp_localize_script('bsc-products-admin', 'bscProductsAdmin', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('bsc_products_nonce'),
        'lowStockThreshold' => (int) get_option('bsc_low_stock_threshold', 3),
        'strings' => [
            'historyTitlePrefix' => 'Historial: ',
            'loading'            => 'Cargando...',
            'emptyLog'           => 'Sin movimientos registrados.',
            'loadError'          => 'No se pudo cargar el historial.',
            'saved'              => 'Stock guardado.',
            'saveError'          => 'No se pudo guardar el stock.',
            'connectionError'    => 'Error de conexiÃ³n. Intenta de nuevo.',
        ],
    ]);
}

function bsc_render_products_page(): void {
    if (!current_user_can('manage_options') && !current_user_can('edit_products')) {
        wp_die(esc_html__('No tienes permisos para ver esta pÃ¡gina.', 'bsc-2-0'));
    }

    $search = sanitize_text_field($_GET['s'] ?? '');
    $status = in_array($_GET['status'] ?? '', ['publish', 'draft'], true)
        ? sanitize_text_field($_GET['status'])
        : '';
    $paged = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 20;

    $args = [
        'post_type'              => 'product',
        'post_status'            => $status ?: ['publish', 'draft'],
        'posts_per_page'         => $per_page,
        'paged'                  => $paged,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ];

    if ($search) {
        $args['s'] = $search;
    }

    $query = new WP_Query($args);
    $products = $query->posts;
    $total = (int) $query->found_posts;
    $pages = (int) ceil($total / $per_page);
    ?>
    <div class="wrap bsc-admin-products">
        <h1 class="wp-heading-inline">Productos BSC</h1>
        <hr class="wp-header-end">

        <form method="get" class="bsc-admin-products__filters bsc-admin-toolbar">
            <input type="hidden" name="page" value="bsc-products">
            <div class="bsc-admin-toolbar__group bsc-admin-toolbar__group--grow">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por nombre..." class="regular-text">
                <select name="status">
                    <option value="">Todos</option>
                    <option value="publish" <?php selected($status, 'publish'); ?>>Publicados</option>
                    <option value="draft" <?php selected($status, 'draft'); ?>>Borradores</option>
                </select>
                <button type="submit" class="button button-primary">Filtrar</button>
                <?php if ($search || $status) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="button">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="description"><?php echo esc_html($total); ?> productos encontrados.</p>

        <table class="wp-list-table widefat fixed striped bsc-admin-products__table">
            <thead>
                <tr>
                    <th class="bsc-admin-products__col-image">Imagen</th>
                    <th>Nombre / SKU</th>
                    <th class="bsc-admin-products__col-price">Precio</th>
                    <th class="bsc-admin-products__col-stock">Stock Bodega</th>
                    <th class="bsc-admin-products__col-stock">Stock Tienda</th>
                    <th class="bsc-admin-products__col-status">Estado</th>
                    <th class="bsc-admin-products__col-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $post) : ?>
                    <?php
                    $product = wc_get_product($post->ID);
                    if (!$product) {
                        continue;
                    }

                    $sku = $product->get_sku();
                    $price = $product->get_regular_price();
                    $sale_price = $product->get_sale_price();
                    $stock = BSC_Stock::get_stock($post->ID);
                    $image_id = $product->get_image_id();
                    $image_src = $image_id
                        ? (wp_get_attachment_image_url($image_id, [60, 60]) ?: wc_placeholder_img_src())
                        : wc_placeholder_img_src();
                    $edit_url = admin_url('admin.php?page=bsc-product-edit&id=' . $post->ID);
                    $view_url = get_permalink($post->ID);
                    $low_threshold = (int) get_option('bsc_low_stock_threshold', 3);
                    $bodega_input_classes = 'bsc-stock-input bsc-admin-products__stock-input bsc-admin-inline-editor__field';
                    $tienda_input_classes = 'bsc-stock-input bsc-admin-products__stock-input bsc-admin-inline-editor__field';

                    if ((int) $stock['bodega'] < $low_threshold) {
                        $bodega_input_classes .= ' is-low';
                    }

                    if ((int) $stock['tienda'] < $low_threshold) {
                        $tienda_input_classes .= ' is-low';
                    }
                    ?>
                    <tr class="bsc-admin-products__row" data-product-id="<?php echo esc_attr($post->ID); ?>">
                        <td>
                            <img src="<?php echo esc_url($image_src); ?>" alt="" loading="lazy" class="bsc-admin-products__image">
                        </td>
                        <td>
                            <strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($post->post_title); ?></a></strong>
                            <?php if ($sku) : ?>
                                <br><code class="bsc-admin-products__sku"><?php echo esc_html($sku); ?></code>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sale_price) : ?>
                                <del class="bsc-admin-products__sale-price"><?php echo wc_price($price); ?></del><br>
                                <strong><?php echo wp_kses_post(wc_price($sale_price)); ?></strong>
                            <?php else : ?>
                                <?php echo wp_kses_post(wc_price($price)); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <input
                                type="number"
                                min="0"
                                class="<?php echo esc_attr($bodega_input_classes); ?>"
                                data-type="bodega"
                                data-original="<?php echo esc_attr($stock['bodega']); ?>"
                                value="<?php echo esc_attr($stock['bodega']); ?>"
                            >
                        </td>
                        <td>
                            <input
                                type="number"
                                min="0"
                                class="<?php echo esc_attr($tienda_input_classes); ?>"
                                data-type="tienda"
                                data-original="<?php echo esc_attr($stock['tienda']); ?>"
                                value="<?php echo esc_attr($stock['tienda']); ?>"
                            >
                        </td>
                        <td>
                            <?php if ($post->post_status === 'publish') : ?>
                                <span class="bsc-admin-badge bsc-admin-badge--success">Publicado</span>
                            <?php else : ?>
                                <span class="bsc-admin-badge bsc-admin-badge--muted">Borrador</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="bsc-admin-actions bsc-admin-products__row-actions">
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Editar</a>
                                <a href="<?php echo esc_url($view_url); ?>" class="button button-small" target="_blank" rel="noopener noreferrer">Ver tienda</a>
                                <button
                                    type="button"
                                    class="button button-small bsc-stock-history-btn"
                                    data-product-id="<?php echo esc_attr($post->ID); ?>"
                                    data-product-name="<?php echo esc_attr($post->post_title); ?>"
                                >Historial</button>
                            </div>
                            <div class="bsc-admin-inline-editor bsc-admin-products__save-controls">
                                <span class="bsc-admin-inline-editor__pending" data-role="pending">Guardar cambios</span>
                                <button
                                    type="button"
                                    class="button button-primary button-small bsc-admin-inline-editor__save bsc-product-stock-save"
                                    data-role="save"
                                    disabled
                                >Guardar</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($products)) : ?>
                    <tr><td colspan="7" class="bsc-admin-products__empty">No hay productos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($pages > 1) : ?>
            <div class="bsc-admin-products__pagination">
                <?php if ($paged > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['paged' => $paged - 1])); ?>" class="button">Anterior</a>
                <?php endif; ?>
                <span class="bsc-admin-products__pagination-label">Pagina <?php echo esc_html($paged); ?> de <?php echo esc_html($pages); ?></span>
                <?php if ($paged < $pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg(['paged' => $paged + 1])); ?>" class="button">Siguiente</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="bsc-stock-modal" class="bsc-admin-products__modal">
        <div id="bsc-stock-modal-overlay" class="bsc-admin-products__modal-overlay"></div>
        <div class="bsc-admin-products__modal-dialog">
            <div class="bsc-admin-products__modal-header">
                <h2 id="bsc-stock-modal-title" class="bsc-admin-products__modal-title">Historial de stock</h2>
                <button id="bsc-stock-modal-close" type="button" class="button bsc-admin-products__modal-close">&times;</button>
            </div>
            <div id="bsc-stock-modal-body"></div>
        </div>
    </div>

    <div id="bsc-admin-products-toast" class="bsc-admin-toast" aria-live="polite"></div>

    <?php wp_nonce_field('bsc_products_nonce', 'bsc_products_nonce_field'); ?>
    <?php
}
