<?php
/**
 * BSC-065: Simplified BSC product editor for Shop Manager.
 * Allows editing: name, short description, main image, gallery, categories,
 * tags, SKU, dual stock, and review toggle — without the full WC editor.
 */
defined('ABSPATH') || exit;

function bsc_render_product_edit_page(): void {
    if ( ! current_user_can('manage_options') && ! current_user_can('edit_products') ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    $product_id = absint( $_GET['id'] ?? 0 );
    if ( ! $product_id || get_post_type($product_id) !== 'product' ) {
        wp_die( esc_html__( 'Producto no encontrado.', 'bsc-2-0' ) );
    }

    $product = wc_get_product( $product_id );
    $post    = get_post( $product_id );

    // ── Handle POST save ─────────────────────────────────────────────
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['bsc_product_edit_nonce']) ) {
        if ( ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['bsc_product_edit_nonce'])), 'bsc_product_edit_action' ) ) {
            wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
        }

        // Basic fields
        $new_title   = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) );
        $new_excerpt = wp_kses_post( wp_unslash( $_POST['post_excerpt'] ?? '' ) );
        $new_status  = in_array( $_POST['post_status'] ?? '', ['publish','draft'], true )
            ? sanitize_key( $_POST['post_status'] )
            : 'draft';

        wp_update_post([
            'ID'           => $product_id,
            'post_title'   => $new_title,
            'post_excerpt' => $new_excerpt,
            'post_status'  => $new_status,
        ]);

        // SKU
        $sku = sanitize_text_field( wp_unslash( $_POST['_sku'] ?? '' ) );
        update_post_meta( $product_id, '_sku', $sku );

        // Main image
        $thumbnail_id = absint( $_POST['_thumbnail_id'] ?? 0 );
        if ( $thumbnail_id ) {
            set_post_thumbnail( $product_id, $thumbnail_id );
        } elseif ( isset($_POST['_remove_thumbnail']) ) {
            delete_post_thumbnail( $product_id );
        }

        // Gallery
        $gallery_ids = sanitize_text_field( wp_unslash( $_POST['_product_image_gallery'] ?? '' ) );
        update_post_meta( $product_id, '_product_image_gallery', $gallery_ids );

        // Categories
        $cat_ids = array_map('absint', (array)($_POST['product_cat'] ?? []));
        wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );

        // Tags
        $tag_input = sanitize_text_field( wp_unslash( $_POST['product_tag'] ?? '' ) );
        $tags = array_filter( array_map('trim', explode(',', $tag_input)) );
        wp_set_object_terms( $product_id, $tags, 'product_tag' );

        // Reviews toggle
        $comment_status = isset($_POST['comment_status']) ? 'open' : 'closed';
        wp_update_post([
            'ID'             => $product_id,
            'comment_status' => $comment_status,
        ]);

        // Stock dual (BSC-066)
        $stock_bodega = max( 0, intval( $_POST['_stock_bodega'] ?? 0 ) );
        $stock_tienda = max( 0, intval( $_POST['_stock_tienda'] ?? 0 ) );
        $envio_tipo   = in_array( $_POST['_envio_tipo'] ?? '', ['bodega','tienda','ambos'], true )
            ? $_POST['_envio_tipo'] : 'bodega';
        update_post_meta( $product_id, '_stock_bodega', $stock_bodega );
        update_post_meta( $product_id, '_stock_tienda', $stock_tienda );
        update_post_meta( $product_id, '_envio_tipo', $envio_tipo );

        // Repurchase follow-up timeout override (BSC-082)
        $repurchase_days_raw = isset( $_POST['_bsc_repurchase_days'] )
            ? trim( (string) wp_unslash( $_POST['_bsc_repurchase_days'] ) )
            : '';

        if ( $repurchase_days_raw === '' ) {
            delete_post_meta( $product_id, '_bsc_repurchase_days' );
        } else {
            update_post_meta( $product_id, '_bsc_repurchase_days', max( 1, intval( $repurchase_days_raw ) ) );
        }

        // Redirect back to products list
        wp_safe_redirect( admin_url('admin.php?page=bsc-products&saved=1') );
        exit;
    }

    // ── Load current values ──────────────────────────────────────────
    $stock      = BSC_Stock::get_stock( $product_id );
    $sku        = $product->get_sku();
    $gallery    = $product->get_gallery_image_ids();
    $thumbnail_id = get_post_thumbnail_id( $product_id );
    $thumbnail_src = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'medium')[0] : '';
    $repurchase_days = (string) get_post_meta( $product_id, '_bsc_repurchase_days', true );
    $default_repurchase_days = function_exists( 'bsc_get_followup_email_setting' )
        ? (int) bsc_get_followup_email_setting( 'bsc_default_repurchase_days' )
        : 30;

    $all_cats     = get_terms(['taxonomy'=>'product_cat','hide_empty'=>false,'orderby'=>'name']);
    $current_cats = wp_get_object_terms($product_id, 'product_cat', ['fields'=>'ids']);
    $all_tags     = wp_get_object_terms($product_id, 'product_tag', ['fields'=>'names']);

    // BSC-023: Build nested category tree
    $build_cat_tree = function(array $terms, int $parent) use (&$build_cat_tree): array {
        $nodes = [];
        foreach ($terms as $t) {
            if ((int) $t->parent === $parent) {
                $nodes[] = [
                    'id'       => $t->term_id,
                    'name'     => $t->name,
                    'slug'     => $t->slug,
                    'children' => $build_cat_tree($terms, $t->term_id),
                ];
            }
        }
        return $nodes;
    };
    $cat_tree_data   = $build_cat_tree( is_array($all_cats) ? $all_cats : [], 0 );
    $current_cat_ids = array_values( array_map('intval', is_array($current_cats) ? $current_cats : []) );

    // Covers
    $cover_fields = [
        'bsc_cover_desktop'  => 'Cover Desktop',
        'bsc_cover_mobile'   => 'Cover Mobile',
        '_bsc_extra_image_1' => 'Imagen extra 1',
        '_bsc_extra_image_2' => 'Imagen extra 2',
        '_bsc_extra_image_3' => 'Imagen extra 3',
    ];
    $cover_values = [];
    foreach ( $cover_fields as $key => $label ) {
        $cover_values[$key] = (int) get_post_meta( $product_id, $key, true );
    }

    // Enqueue media uploader
    wp_enqueue_media();
    ?>
    <div class="wrap bsc-admin-product-edit">
        <h1>Editar Producto <span style="font-weight:400;font-size:0.85em">#<?php echo esc_html($product_id); ?></span></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="page-title-action">← Volver a Productos</a>
        <hr class="wp-header-end">

        <?php if ( isset($_GET['saved']) ): ?>
        <div class="notice notice-success is-dismissible"><p>✓ Producto actualizado correctamente.</p></div>
        <?php endif; ?>

        <form method="post" style="max-width:960px;margin-top:16px">
            <?php wp_nonce_field('bsc_product_edit_action', 'bsc_product_edit_nonce'); ?>
            <?php wp_nonce_field('bsc_product_covers_save', 'bsc_product_covers_nonce'); ?>

            <div class="bsc-product-edit-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

                <!-- LEFT COLUMN -->
                <div>
                    <!-- A: Básico -->
                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Básico</h2>

                        <label style="display:block;margin-bottom:12px">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Nombre del producto</span>
                            <input type="text" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" class="large-text" required>
                        </label>

                        <label style="display:block;margin-bottom:12px">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Descripción corta</span>
                            <textarea name="post_excerpt" rows="4" class="large-text"><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                        </label>

                        <label style="display:block;margin-bottom:12px">
                            <span style="font-weight:600;display:block;margin-bottom:4px">SKU</span>
                            <input type="text" name="_sku" value="<?php echo esc_attr($sku); ?>" class="regular-text">
                        </label>

                        <label style="display:block">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Estado</span>
                            <select name="post_status">
                                <option value="publish" <?php selected($post->post_status,'publish'); ?>>Publicado</option>
                                <option value="draft" <?php selected($post->post_status,'draft'); ?>>Borrador</option>
                            </select>
                        </label>

                        <label style="display:block;margin-top:12px">
                            <input type="checkbox" name="comment_status" value="open" <?php checked($post->comment_status,'open'); ?>>
                            Habilitar reseñas de clientes
                        </label>
                    </div>

                    <!-- C: Tags -->
                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Tags</h2>
                        <label style="display:block">
                            <span style="font-size:12px;color:#666;display:block;margin-bottom:4px">Separados por coma</span>
                            <input type="text" name="product_tag" value="<?php echo esc_attr(implode(', ', $all_tags)); ?>" class="large-text">
                        </label>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <!-- B: Imágenes -->
                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Imagen principal</h2>
                        <div id="bsc-main-image-preview">
                            <?php if ($thumbnail_src): ?>
                                <img src="<?php echo esc_url($thumbnail_src); ?>" style="max-width:180px;border-radius:6px;margin-bottom:8px;display:block">
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="_thumbnail_id" id="bsc-thumbnail-id" value="<?php echo esc_attr($thumbnail_id ?: ''); ?>">
                        <button type="button" class="button" id="bsc-select-main-image">Seleccionar imagen</button>
                        <?php if ($thumbnail_id): ?>
                            <button type="button" class="button" id="bsc-remove-main-image" style="margin-left:6px">Quitar imagen</button>
                            <input type="hidden" name="_remove_thumbnail" id="bsc-remove-thumbnail-flag" value="">
                        <?php endif; ?>
                    </div>

                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Galería</h2>
                        <div id="bsc-gallery-preview" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px">
                            <?php foreach ($gallery as $gid):
                                $gsrc = wp_get_attachment_image_src($gid, [80,80])[0] ?? ''; ?>
                                <img src="<?php echo esc_url($gsrc); ?>" style="width:72px;height:72px;object-fit:cover;border-radius:4px">
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="_product_image_gallery" id="bsc-gallery-ids" value="<?php echo esc_attr(implode(',', $gallery)); ?>">
                        <button type="button" class="button" id="bsc-select-gallery">Editar galería</button>
                    </div>

                    <!-- D: Stock dual -->
                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Stock Dual (BSC)</h2>

                        <label style="display:block;margin-bottom:10px">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Stock Bodega (web)</span>
                            <input type="number" min="0" name="_stock_bodega" value="<?php echo esc_attr($stock['bodega']); ?>" style="width:100px">
                        </label>

                        <label style="display:block;margin-bottom:10px">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Stock Tienda (showroom)</span>
                            <input type="number" min="0" name="_stock_tienda" value="<?php echo esc_attr($stock['tienda']); ?>" style="width:100px">
                        </label>

                        <label style="display:block">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Tipo de envío</span>
                            <select name="_envio_tipo">
                                <option value="bodega" <?php selected($stock['envio_tipo'],'bodega'); ?>>Bodega (web)</option>
                                <option value="tienda" <?php selected($stock['envio_tipo'],'tienda'); ?>>Tienda (showroom)</option>
                                <option value="ambos" <?php selected($stock['envio_tipo'],'ambos'); ?>>Ambos</option>
                            </select>
                        </label>
                    </div>

                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Emails de seguimiento</h2>

                        <label style="display:block">
                            <span style="font-weight:600;display:block;margin-bottom:4px">Días para recompra</span>
                            <input type="number"
                                   min="1"
                                   step="1"
                                   name="_bsc_repurchase_days"
                                   value="<?php echo esc_attr( $repurchase_days ); ?>"
                                   placeholder="<?php echo esc_attr( (string) $default_repurchase_days ); ?>"
                                   style="width:120px">
                            <span style="display:block;margin-top:4px;font-size:12px;color:#666">
                                Déjalo vacío para usar el valor global del módulo Emails: <?php echo esc_html( (string) $default_repurchase_days ); ?> días.
                            </span>
                        </label>
                    </div>

                    <!-- E: Covers de Producto -->
                    <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                        <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Covers de Producto</h2>
                        <?php foreach ( $cover_fields as $key => $label ):
                            $att_id  = $cover_values[$key];
                            $img_src = $att_id ? wp_get_attachment_image_url($att_id, 'thumbnail') : '';
                            ?>
                            <div class="bsc-cover-field" style="margin-bottom:16px">
                                <p style="margin:0 0 4px"><strong><?php echo esc_html($label); ?></strong></p>
                                <div class="bsc-cover-preview" style="margin-bottom:6px;min-height:40px">
                                    <?php if ($img_src): ?>
                                    <img src="<?php echo esc_url($img_src); ?>"
                                         style="max-width:100%;height:auto;display:block;border-radius:3px">
                                    <?php endif; ?>
                                </div>
                                <input type="hidden"
                                       name="<?php echo esc_attr($key); ?>"
                                       id="<?php echo esc_attr($key); ?>"
                                       value="<?php echo esc_attr($att_id ?: ''); ?>">
                                <button type="button" class="button bsc-cover-select"
                                        data-field="<?php echo esc_attr($key); ?>">
                                    <?php echo $att_id ? 'Cambiar imagen' : 'Seleccionar imagen'; ?>
                                </button>
                                <?php if ($att_id): ?>
                                <button type="button" class="button bsc-cover-remove"
                                        data-field="<?php echo esc_attr($key); ?>"
                                        style="margin-left:4px">Eliminar</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- C2: Categorías jerárquicas (BSC-023) – full width -->
            <div class="postbox" style="padding:16px 20px;margin-bottom:16px">
                <h2 style="margin:0 0 12px;font-size:1rem;border-bottom:1px solid #eee;padding-bottom:8px">Categorías</h2>

                <!-- Root group tabs -->
                <div id="bsc-root-tabs" style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap"></div>

                <!-- Branch sections (rendered by JS) -->
                <div id="bsc-cat-branches"></div>

                <!-- Hidden product_cat[] inputs populated by JS (only leaf IDs) -->
                <div id="bsc-cat-hidden-inputs" style="display:none"></div>
            </div>

            <div style="margin-top:8px">
                <button type="submit" class="button button-primary button-large">Guardar cambios</button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bsc-products')); ?>" class="button button-large" style="margin-left:8px">Cancelar</a>
                <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="button" target="_blank" style="margin-left:8px">Ver en tienda ↗</a>
            </div>
        </form>
    </div>

    <script>
    (function($){
        // Main image uploader
        var mainFrame;
        $('#bsc-select-main-image').on('click', function(){
            if (mainFrame) { mainFrame.open(); return; }
            mainFrame = wp.media({ title: 'Imagen principal', button: { text: 'Usar imagen' }, multiple: false });
            mainFrame.on('select', function(){
                var attachment = mainFrame.state().get('selection').first().toJSON();
                $('#bsc-thumbnail-id').val(attachment.id);
                $('#bsc-remove-thumbnail-flag').val('');
                $('#bsc-main-image-preview').html('<img src="'+attachment.url+'" style="max-width:180px;border-radius:6px;margin-bottom:8px;display:block">');
            });
            mainFrame.open();
        });

        $('#bsc-remove-main-image').on('click', function(){
            $('#bsc-thumbnail-id').val('');
            $('#bsc-remove-thumbnail-flag').val('1');
            $('#bsc-main-image-preview').html('');
        });

        // Gallery uploader
        var galleryFrame;
        $('#bsc-select-gallery').on('click', function(){
            if (galleryFrame) { galleryFrame.open(); return; }
            galleryFrame = wp.media({
                title: 'Galería del producto',
                button: { text: 'Usar estas imágenes' },
                multiple: true
            });
            galleryFrame.on('select', function(){
                var ids = galleryFrame.state().get('selection').map(function(a){ return a.id; });
                $('#bsc-gallery-ids').val(ids.join(','));
                var html = galleryFrame.state().get('selection').map(function(a){
                    return '<img src="'+(a.attributes.sizes.thumbnail ? a.attributes.sizes.thumbnail.url : a.attributes.url)+'" style="width:72px;height:72px;object-fit:cover;border-radius:4px">';
                }).join('');
                $('#bsc-gallery-preview').html(html);
            });
            galleryFrame.open();
        });
    })(jQuery);

    // ── BSC-023: Hierarchical Category Selector ──────────────────────
    (function() {
        var catTree     = <?php echo wp_json_encode($cat_tree_data); ?>;
        var currentCats = <?php echo wp_json_encode($current_cat_ids); ?>;
        var ROOT_SLUGS  = ['group-skin-care','group-hair-care','group-make-up'];
        var ROOT_LABELS = {'group-skin-care':'Skin Care','group-hair-care':'Hair Care','group-make-up':'Make Up'};

        // Build flat indexes from tree
        var byId = {}, bySlug = {};
        function indexTree(nodes, parentId) {
            nodes.forEach(function(n) {
                n.parentId = parentId;
                byId[n.id]     = n;
                bySlug[n.slug] = n;
                if (n.children && n.children.length) indexTree(n.children, n.id);
            });
        }
        indexTree(catTree, 0);

        // Walk ancestors to find which root group a term belongs to
        function getRootSlug(termId) {
            var node = byId[termId];
            while (node && node.parentId !== 0) node = byId[node.parentId];
            return (node && ROOT_SLUGS.indexOf(node.slug) !== -1) ? node.slug : null;
        }

        // Detect initial root from currently assigned cats
        function detectInitialRoot() {
            for (var i = 0; i < currentCats.length; i++) {
                var r = getRootSlug(currentCats[i]);
                if (r) return r;
            }
            return ROOT_SLUGS[0];
        }

        var activeRoot = detectInitialRoot();

        function esc(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function renderTabs() {
            var html = '';
            ROOT_SLUGS.forEach(function(slug) {
                html += '<button type="button" class="button' + (slug === activeRoot ? ' button-primary' : '') + '" ' +
                        'data-root="' + esc(slug) + '">' + esc(ROOT_LABELS[slug] || slug) + '</button>';
            });
            document.getElementById('bsc-root-tabs').innerHTML = html;
        }

        function leafHtml(node) {
            var chk = currentCats.indexOf(node.id) !== -1 ? ' checked' : '';
            return '<label style="display:inline-flex;align-items:center;gap:4px;margin:2px 4px;white-space:nowrap;font-size:13px">' +
                   '<input type="checkbox" class="bsc-cat-check" value="' + node.id + '"' + chk + '> ' +
                   esc(node.name) + '</label>';
        }

        function renderBranches() {
            var rootNode   = bySlug[activeRoot];
            var container  = document.getElementById('bsc-cat-branches');

            if (!rootNode || !rootNode.children || !rootNode.children.length) {
                container.innerHTML = '<p style="color:#888;font-style:italic;font-size:13px">No hay categorías para este grupo.</p>';
                syncHiddenInputs();
                return;
            }

            var html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px">';

            rootNode.children.forEach(function(branch) {
                html += '<div class="bsc-branch-section" style="border:1px solid #dde0e3;border-radius:4px;padding:10px 12px">';

                // Header + search
                html += '<strong style="font-size:0.85rem;display:block;margin-bottom:6px;color:#23282d">' + esc(branch.name) + '</strong>';
                html += '<input type="text" class="bsc-branch-search" placeholder="Buscar..." ' +
                        'style="width:100%;box-sizing:border-box;padding:3px 6px;border:1px solid #ccd0d4;border-radius:3px;font-size:12px;margin-bottom:8px">';

                html += '<div class="bsc-branch-items">';

                if (!branch.children || !branch.children.length) {
                    html += '<em style="color:#aaa;font-size:11px">Sin subcategorías</em>';
                } else {
                    branch.children.forEach(function(child) {
                        if (child.children && child.children.length) {
                            // Level-3 sub-group: child is a named group, its children are the checkboxes
                            html += '<div class="bsc-subgroup">';
                            html += '<div class="bsc-subgroup-header" style="font-size:11px;font-weight:600;color:#666;margin:6px 0 2px;padding-top:6px;border-top:1px dashed #e0e0e0">' +
                                    esc(child.name) + '</div>';
                            child.children.forEach(function(gc) { html += leafHtml(gc); });
                            html += '</div>';
                        } else {
                            html += leafHtml(child);
                        }
                    });
                }

                html += '</div></div>';
            });

            html += '</div>';
            container.innerHTML = html;
            syncHiddenInputs();
        }

        // Populate hidden product_cat[] inputs from checked leaf checkboxes
        function syncHiddenInputs() {
            var container = document.getElementById('bsc-cat-hidden-inputs');
            container.innerHTML = '';
            document.querySelectorAll('.bsc-cat-check:checked').forEach(function(el) {
                var inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'product_cat[]';
                inp.value = el.value;
                container.appendChild(inp);
            });
        }

        jQuery(document)
            .on('click', '#bsc-root-tabs .button', function() {
                activeRoot = this.getAttribute('data-root');
                renderTabs();
                renderBranches();
            })
            .on('change', '.bsc-cat-check', syncHiddenInputs)
            .on('input', '.bsc-branch-search', function() {
                var q       = this.value.toLowerCase().trim();
                var section = this.closest('.bsc-branch-section');
                section.querySelectorAll('.bsc-branch-items label').forEach(function(lbl) {
                    lbl.style.display = (!q || lbl.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
                });
                // Hide sub-group header + wrapper if all children hidden
                section.querySelectorAll('.bsc-subgroup').forEach(function(sg) {
                    var anyVisible = Array.from(sg.querySelectorAll('label')).some(function(l) {
                        return l.style.display !== 'none';
                    });
                    sg.style.display = (!q || anyVisible) ? '' : 'none';
                });
            });

        renderTabs();
        renderBranches();
    })();
    </script>
    <?php
}
