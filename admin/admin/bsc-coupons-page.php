<?php
/**
 * BSC-066: BSC Coupons admin page — manage WooCommerce coupons from the BSC menu.
 */
defined('ABSPATH') || exit;

// ── Handle create / delete ─────────────────────────────────────────────
add_action( 'admin_init', 'bsc_coupons_handle_actions' );
function bsc_coupons_handle_actions(): void {
    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'bsc-coupons' ) return;
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) return;

    // Create coupon
    if ( isset( $_POST['bsc_create_coupon_nonce'] ) ) {
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_create_coupon_nonce'] ) ), 'bsc_create_coupon' ) ) {
            wp_die( esc_html__( 'Nonce inválido.', 'bsc-2-0' ) );
        }

        $code          = sanitize_text_field( strtolower( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );
        $discount_type = sanitize_text_field( $_POST['discount_type'] ?? 'percent' );
        $amount        = (float) ( $_POST['coupon_amount'] ?? 0 );
        $expiry        = sanitize_text_field( $_POST['expiry_date'] ?? '' );
        $description   = sanitize_textarea_field( $_POST['coupon_description'] ?? '' );
        $usage_limit   = absint( $_POST['usage_limit'] ?? 0 );
        $min_amount    = (float) ( $_POST['minimum_amount'] ?? 0 );

        if ( $code ) {
            $coupon = new WC_Coupon();
            $coupon->set_code( $code );
            $coupon->set_discount_type( in_array( $discount_type, [ 'percent', 'fixed_cart', 'fixed_product' ], true ) ? $discount_type : 'percent' );
            $coupon->set_amount( $amount );
            $coupon->set_description( $description );
            if ( $expiry ) $coupon->set_date_expires( strtotime( $expiry ) );
            if ( $usage_limit ) $coupon->set_usage_limit( $usage_limit );
            if ( $min_amount ) $coupon->set_minimum_amount( $min_amount );
            $coupon->save();

            wp_safe_redirect( add_query_arg( [ 'page' => 'bsc-coupons', 'bsc_notice' => 'created' ], admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    // Delete coupon
    if ( isset( $_GET['bsc_delete_coupon'], $_GET['bsc_delete_nonce'] ) ) {
        $coupon_id = absint( $_GET['bsc_delete_coupon'] );
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bsc_delete_nonce'] ) ), 'bsc_delete_coupon_' . $coupon_id ) ) {
            wp_delete_post( $coupon_id, true );
        }
        wp_safe_redirect( add_query_arg( [ 'page' => 'bsc-coupons', 'bsc_notice' => 'deleted' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}

// ── Page render ────────────────────────────────────────────────────────
function bsc_render_coupons_page(): void {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    // Fetch all WC coupons
    $args = [
        'post_type'      => 'shop_coupon',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];
    $coupons_query = new WP_Query( $args );
    ?>
    <div class="wrap bsc-admin-coupons">
        <h1>Cupones BSC</h1>

        <?php if ( isset( $_GET['bsc_notice'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php echo $_GET['bsc_notice'] === 'created' ? 'Cupón creado correctamente.' : 'Cupón eliminado.'; ?>
        </p></div>
        <?php endif; ?>

        <!-- ── Create form ── -->
        <div class="bsc-coupon-create-panel">
            <h2>Crear nuevo cupón</h2>
            <form method="post">
                <?php wp_nonce_field( 'bsc_create_coupon', 'bsc_create_coupon_nonce' ); ?>
                <table class="form-table" style="max-width:700px">
                    <tr>
                        <th><label for="coupon_code">Código</label></th>
                        <td>
                            <input type="text" id="coupon_code" name="coupon_code" class="regular-text" required
                                   placeholder="ej: BIENVENIDA20" style="text-transform:uppercase">
                            <p class="description">El código se guardará en minúsculas.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="discount_type">Tipo de descuento</label></th>
                        <td>
                            <select id="discount_type" name="discount_type">
                                <option value="percent">Porcentaje (%)</option>
                                <option value="fixed_cart">Descuento fijo en carrito ($)</option>
                                <option value="fixed_product">Descuento fijo por producto ($)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="coupon_amount">Valor</label></th>
                        <td><input type="number" id="coupon_amount" name="coupon_amount" step="0.01" min="0" class="small-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="expiry_date">Fecha de expiración</label></th>
                        <td><input type="date" id="expiry_date" name="expiry_date"></td>
                    </tr>
                    <tr>
                        <th><label for="usage_limit">Límite de usos</label></th>
                        <td>
                            <input type="number" id="usage_limit" name="usage_limit" min="0" class="small-text" value="0">
                            <p class="description">0 = sin límite</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="minimum_amount">Compra mínima (COP)</label></th>
                        <td><input type="number" id="minimum_amount" name="minimum_amount" min="0" step="1000" class="regular-text" value="0"></td>
                    </tr>
                    <tr>
                        <th><label for="coupon_description">Descripción interna</label></th>
                        <td><textarea id="coupon_description" name="coupon_description" class="large-text" rows="2" placeholder="Uso interno, no visible al cliente"></textarea></td>
                    </tr>
                </table>
                <?php submit_button( 'Crear cupón', 'primary', 'submit', false ); ?>
            </form>
        </div>

        <!-- ── Coupons list ── -->
        <h2 style="margin-top:2rem">Cupones activos</h2>

        <?php if ( ! $coupons_query->have_posts() ) : ?>
            <p style="color:#888">No hay cupones creados todavía.</p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped bsc-coupons-table">
            <thead>
                <tr>
                    <th style="width:140px">Código</th>
                    <th>Tipo</th>
                    <th style="width:100px">Valor</th>
                    <th>Descripción</th>
                    <th style="width:110px">Expira</th>
                    <th style="width:80px">Límite</th>
                    <th style="width:80px">Usos</th>
                    <th style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php while ( $coupons_query->have_posts() ) :
                $coupons_query->the_post();
                $coupon = new WC_Coupon( get_the_ID() );
                $type_labels = [
                    'percent'       => '% desc.',
                    'fixed_cart'    => 'Fijo carrito',
                    'fixed_product' => 'Fijo producto',
                ];
                $type_label = $type_labels[ $coupon->get_discount_type() ] ?? $coupon->get_discount_type();
                $value = $coupon->get_discount_type() === 'percent'
                    ? number_format( (float) $coupon->get_amount(), 0 ) . '%'
                    : '$' . number_format( (float) $coupon->get_amount(), 0, ',', '.' );
                $expiry = $coupon->get_date_expires() ? $coupon->get_date_expires()->date('d/m/Y') : '—';
                $limit  = $coupon->get_usage_limit() ?: '∞';
                $edit_url   = get_edit_post_link( get_the_ID() );
                $delete_url = add_query_arg([
                    'page'             => 'bsc-coupons',
                    'bsc_delete_coupon'=> get_the_ID(),
                    'bsc_delete_nonce' => wp_create_nonce( 'bsc_delete_coupon_' . get_the_ID() ),
                ], admin_url( 'admin.php' ));
                $is_expired = $coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time();
            ?>
            <tr class="<?php echo $is_expired ? 'bsc-coupon-expired' : ''; ?>">
                <td>
                    <strong style="font-family:monospace;font-size:13px;text-transform:uppercase">
                        <?php echo esc_html( $coupon->get_code() ); ?>
                    </strong>
                    <?php if ( $is_expired ) echo '<br><span style="color:#c53030;font-size:11px">Expirado</span>'; ?>
                </td>
                <td><?php echo esc_html( $type_label ); ?></td>
                <td><strong><?php echo esc_html( $value ); ?></strong></td>
                <td style="color:#666;font-size:12px"><?php echo esc_html( $coupon->get_description() ?: '—' ); ?></td>
                <td><?php echo esc_html( $expiry ); ?></td>
                <td><?php echo esc_html( $limit ); ?></td>
                <td><?php echo esc_html( $coupon->get_usage_count() ); ?></td>
                <td>
                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" target="_blank">Editar</a>
                    <a href="<?php echo esc_url( $delete_url ); ?>" class="button button-small"
                       onclick="return confirm('¿Eliminar este cupón?')"
                       style="color:#c53030;border-color:#c53030">Eliminar</a>
                </td>
            </tr>
            <?php endwhile; wp_reset_postdata(); ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <style>
        .bsc-coupon-create-panel { background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px 24px; margin:16px 0; max-width:800px; }
        .bsc-coupon-create-panel h2 { margin-top:0; font-size:1rem; }
        .bsc-coupons-table .bsc-coupon-expired { opacity:.6; }
    </style>
    <?php
}
