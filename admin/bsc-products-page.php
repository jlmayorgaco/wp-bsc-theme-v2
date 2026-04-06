<?php
/**
 * BSC-062: BSC Products table — inline stock editing for Shop Manager.
 * BSC-066: Includes AJAX handlers for stock adjustment and history log.
 */
defined('ABSPATH') || exit;

// ── AJAX: update inline stock (direct set from table input) ───────────
add_action('wp_ajax_bsc_update_product_stock', 'bsc_ajax_update_product_stock');
function bsc_ajax_update_product_stock(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if ( ! current_user_can('manage_options') && ! current_user_can('edit_products') ) {
        wp_send_json_error([ 'message' => 'Sin permisos' ], 403);
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $type       = in_array( $_POST['type'] ?? '', [ 'bodega', 'tienda' ], true ) ? $_POST['type'] : 'bodega';
    $value      = max( 0, intval( $_POST['value'] ?? 0 ) );
    $meta_key   = ( $type === 'tienda' ) ? '_stock_tienda' : '_stock_bodega';

    if ( ! $product_id || get_post_type($product_id) !== 'product' ) {
        wp_send_json_error([ 'message' => 'Producto inválido' ], 400);
    }

    $old = (int) get_post_meta( $product_id, $meta_key, true );
    update_post_meta( $product_id, $meta_key, $value );

    // Log the manual change via BSC_Stock
    if ( class_exists('BSC_Stock') && $old !== $value ) {
        BSC_Stock::adjust( $product_id, $type, $value - $old, 'Edición inline BSC Products' );
        // adjust() will double-write — undo that by resetting to $value
        update_post_meta( $product_id, $meta_key, $value );
    }

    wp_send_json_success([ 'new_value' => $value ]);
}

// ── AJAX: adjust stock with delta (BSC-066) ───────────────────────────
add_action('wp_ajax_bsc_adjust_stock', 'bsc_ajax_adjust_stock');
function bsc_ajax_adjust_stock(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if ( ! current_user_can('manage_options') && ! current_user_can('edit_products') ) {
        wp_send_json_error([ 'message' => 'Sin permisos' ], 403);
    }

    $product_id = absint( $_POST['product_id'] ?? 0 );
    $type       = in_array( $_POST['type'] ?? '', [ 'bodega', 'tienda' ], true ) ? $_POST['type'] : 'bodega';
    $delta      = intval( $_POST['delta'] ?? 0 );
    $reason     = sanitize_text_field( $_POST['reason'] ?? '' );

    if ( ! $product_id || get_post_type($product_id) !== 'product' ) {
        wp_send_json_error([ 'message' => 'Producto inválido' ], 400);
    }

    $new_stock = BSC_Stock::adjust( $product_id, $type, $delta, $reason );
    wp_send_json_success([ 'new_stock' => $new_stock ]);
}

// ── AJAX: get stock log (BSC-066) ─────────────────────────────────────
add_action('wp_ajax_bsc_get_stock_log', 'bsc_ajax_get_stock_log');
function bsc_ajax_get_stock_log(): void {
    check_ajax_referer('bsc_products_nonce', 'nonce');
    if ( ! current_user_can('manage_options') && ! current_user_can('edit_products') ) {
        wp_send_json_error([ 'message' => 'Sin permisos' ], 403);
    }

    $product_id = absint( $_GET['product_id'] ?? 0 );
    if ( ! $product_id ) {
        wp_send_json_error([ 'message' => 'Producto inválido' ], 400);
    }

    $log = BSC_Stock::get_log( $product_id );

    // Enrich with username
    $enriched = array_map( function( $entry ) {
        $entry['username'] = $entry['user_id']
            ? get_userdata( $entry['user_id'] )->display_name ?? '—'
            : '—';
        return $entry;
    }, $log );

    wp_send_json_success([ 'log' => $enriched ]);
}

// ── Enqueue admin script only on bsc-products page ────────────────────
add_action('admin_enqueue_scripts', function( string $hook ) {
    if ( strpos($hook, 'bsc-products') === false ) return;
    $nonce = wp_create_nonce('bsc_products_nonce');
    wp_add_inline_script('jquery', bsc_products_inline_js($nonce));
});

function bsc_products_inline_js( string $nonce ): string {
    return <<<JS
(function($){
  var bscNonce = '{$nonce}';

  // Inline stock edit: on blur, save the new value
  $(document).on('change blur', '.bsc-stock-input', function(){
    var \$input = $(this);
    var productId = \$input.data('product-id');
    var type      = \$input.data('type');
    var value     = parseInt(\$input.val(), 10);
    if (isNaN(value) || value < 0) { \$input.val(\$input.data('original')); return; }

    \$input.prop('disabled', true);
    $.post(ajaxurl, {
      action: 'bsc_update_product_stock',
      nonce: bscNonce,
      product_id: productId,
      type: type,
      value: value
    }).done(function(res){
      if (res.success) {
        \$input.data('original', res.data.new_value);
        \$input.css('background','#e6ffed');
        setTimeout(function(){ \$input.css('background',''); }, 1200);
      } else {
        \$input.val(\$input.data('original'));
      }
    }).always(function(){ \$input.prop('disabled', false); });
  });

  // Stock history modal
  $(document).on('click', '.bsc-stock-history-btn', function(){
    var productId = $(this).data('product-id');
    var productName = $(this).data('product-name');
    \$('#bsc-stock-modal-title').text('Historial: ' + productName);
    \$('#bsc-stock-modal-body').html('<p>Cargando…</p>');
    \$('#bsc-stock-modal').show();

    $.get(ajaxurl, {
      action: 'bsc_get_stock_log',
      nonce: bscNonce,
      product_id: productId
    }).done(function(res){
      if (!res.success || !res.data.log.length) {
        \$('#bsc-stock-modal-body').html('<p>Sin movimientos registrados.</p>');
        return;
      }
      var rows = res.data.log.map(function(e){
        var delta = e.delta > 0 ? '+'+e.delta : e.delta;
        var color = e.delta > 0 ? '#276749' : '#c53030';
        return '<tr><td>'+e.date+'</td><td>'+e.type+'</td>'
          +'<td style="color:'+color+';font-weight:700">'+delta+'</td>'
          +'<td>'+e.before+' → '+e.after+'</td>'
          +'<td>'+e.username+'</td><td>'+e.reason+'</td></tr>';
      }).join('');
      \$('#bsc-stock-modal-body').html(
        '<table class="wp-list-table widefat"><thead><tr>'
        +'<th>Fecha</th><th>Tipo</th><th>Δ</th><th>Antes→Después</th><th>Usuario</th><th>Razón</th>'
        +'</tr></thead><tbody>'+rows+'</tbody></table>'
      );
    });
  });

  // Close modal
  $(document).on('click', '#bsc-stock-modal-close, #bsc-stock-modal-overlay', function(){
    \$('#bsc-stock-modal').hide();
  });
})(jQuery);
JS;
}

// ── Page render function ──────────────────────────────────────────────
function bsc_render_products_page(): void {
    if ( ! current_user_can('manage_options') && ! current_user_can('edit_products') ) {
        wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
    }

    // Filters
    $search     = sanitize_text_field( $_GET['s'] ?? '' );
    $status     = in_array( $_GET['status'] ?? '', [ 'publish', 'draft' ], true ) ? $_GET['status'] : '';
    $paged      = max( 1, intval( $_GET['paged'] ?? 1 ) );
    $per_page   = 20;

    // Query
    $args = [
        'post_type'              => 'product',
        'post_status'            => $status ?: [ 'publish', 'draft' ],
        'posts_per_page'         => $per_page,
        'paged'                  => $paged,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ];
    if ( $search ) {
        $args['s'] = $search;
    }

    $query    = new WP_Query( $args );
    $products = $query->posts;
    $total    = $query->found_posts;
    $pages    = ceil( $total / $per_page );

    $nonce = wp_create_nonce('bsc_products_nonce');
    ?>
    <div class="wrap bsc-admin-products">
        <h1 class="wp-heading-inline">Productos BSC</h1>
        <hr class="wp-header-end">

        <!-- Filters -->
        <form method="get" style="margin:12px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="page" value="bsc-products">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Buscar por nombre…" class="regular-text">
            <select name="status">
                <option value="">Todos</option>
                <option value="publish" <?php selected($status,'publish'); ?>>Publicados</option>
                <option value="draft" <?php selected($status,'draft'); ?>>Borradores</option>
            </select>
            <button type="submit" class="button">Filtrar</button>
            <?php if ($search || $status): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="button">Limpiar</a>
            <?php endif; ?>
        </form>

        <p class="description"><?php echo esc_html($total); ?> productos encontrados.</p>

        <!-- Products table -->
        <table class="wp-list-table widefat fixed striped" style="margin-top:8px">
            <thead>
                <tr>
                    <th style="width:60px">Imagen</th>
                    <th>Nombre / SKU</th>
                    <th style="width:100px">Precio</th>
                    <th style="width:110px">Stock Bodega</th>
                    <th style="width:110px">Stock Tienda</th>
                    <th style="width:80px">Estado</th>
                    <th style="width:140px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $products as $post ):
                $product    = wc_get_product( $post->ID );
                if ( ! $product ) continue;
                $sku        = $product->get_sku();
                $price      = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $stock      = BSC_Stock::get_stock( $post->ID );
                $img_id     = $product->get_image_id();
                $img_src    = $img_id
                    ? wp_get_attachment_image_src( $img_id, [ 60, 60 ] )[0] ?? ''
                    : wc_placeholder_img_src();
                $edit_url   = admin_url( 'admin.php?page=bsc-product-edit&id=' . $post->ID );
                $view_url   = get_permalink( $post->ID );
                $low_threshold = (int) get_option('bsc_low_stock_threshold', 3);
            ?>
            <tr>
                <td>
                    <img src="<?php echo esc_url($img_src); ?>" alt="" loading="lazy" style="width:56px;height:56px;object-fit:cover;border-radius:6px;">
                </td>
                <td>
                    <strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($post->post_title); ?></a></strong>
                    <?php if ($sku): ?><br><code style="font-size:11px;color:#888"><?php echo esc_html($sku); ?></code><?php endif; ?>
                </td>
                <td>
                    <?php if ($sale_price): ?>
                        <del style="color:#aaa;font-size:11px"><?php echo wc_price($price); ?></del><br>
                        <strong><?php echo wp_kses_post(wc_price($sale_price)); ?></strong>
                    <?php else: ?>
                        <?php echo wp_kses_post(wc_price($price)); ?>
                    <?php endif; ?>
                </td>
                <td>
                    <input type="number" min="0"
                        class="bsc-stock-input"
                        data-product-id="<?php echo esc_attr($post->ID); ?>"
                        data-type="bodega"
                        data-original="<?php echo esc_attr($stock['bodega']); ?>"
                        value="<?php echo esc_attr($stock['bodega']); ?>"
                        style="width:60px;text-align:center<?php echo ($stock['bodega'] < $low_threshold ? ';border-color:#e53e3e;background:#fff5f5' : ''); ?>">
                </td>
                <td>
                    <input type="number" min="0"
                        class="bsc-stock-input"
                        data-product-id="<?php echo esc_attr($post->ID); ?>"
                        data-type="tienda"
                        data-original="<?php echo esc_attr($stock['tienda']); ?>"
                        value="<?php echo esc_attr($stock['tienda']); ?>"
                        style="width:60px;text-align:center">
                </td>
                <td>
                    <?php if ($post->post_status === 'publish'): ?>
                        <span style="color:#276749;font-weight:600">✓ Publicado</span>
                    <?php else: ?>
                        <span style="color:#888">Borrador</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Editar</a>
                    <a href="<?php echo esc_url($view_url); ?>" class="button button-small" target="_blank" title="Ver en tienda">↗</a>
                    <button class="button button-small bsc-stock-history-btn"
                        data-product-id="<?php echo esc_attr($post->ID); ?>"
                        data-product-name="<?php echo esc_attr($post->post_title); ?>"
                        title="Historial de stock">🕐</button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if ( empty($products) ): ?>
            <tr><td colspan="7" style="text-align:center;padding:20px;color:#888">No hay productos.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div style="margin:16px 0;display:flex;gap:8px;align-items:center">
            <?php if ($paged > 1): ?>
                <a href="<?php echo esc_url(add_query_arg(['paged' => $paged-1])); ?>" class="button">← Anterior</a>
            <?php endif; ?>
            <span style="color:#666">Página <?php echo esc_html($paged); ?> de <?php echo esc_html($pages); ?></span>
            <?php if ($paged < $pages): ?>
                <a href="<?php echo esc_url(add_query_arg(['paged' => $paged+1])); ?>" class="button">Siguiente →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- BSC-066: Stock history modal -->
    <div id="bsc-stock-modal" style="display:none;position:fixed;inset:0;z-index:100000">
        <div id="bsc-stock-modal-overlay" style="position:absolute;inset:0;background:rgba(0,0,0,0.5)"></div>
        <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:10px;padding:24px;max-width:800px;width:90%;max-height:80vh;overflow-y:auto;box-shadow:0 10px 40px rgba(0,0,0,0.2)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h2 id="bsc-stock-modal-title" style="margin:0;font-size:1.1rem">Historial de stock</h2>
                <button id="bsc-stock-modal-close" class="button" style="font-size:1.2rem;line-height:1">×</button>
            </div>
            <div id="bsc-stock-modal-body"></div>
        </div>
    </div>

    <?php wp_nonce_field('bsc_products_nonce', 'bsc_products_nonce_field'); ?>
    <?php
}
