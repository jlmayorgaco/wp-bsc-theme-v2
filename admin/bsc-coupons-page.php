<?php
/**
 * BSC-066: BSC Coupons admin page - manage WooCommerce coupons from the BSC menu.
 */
defined('ABSPATH') || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_coupons_admin_assets' );
function bsc_enqueue_coupons_admin_assets(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'bsc-coupons' !== $page ) {
        return;
    }

    bsc_enqueue_admin_ui_assets();

    $css_path = get_template_directory() . '/admin/bsc-coupons.css';
    $js_path  = get_template_directory() . '/js/bsc-admin-coupons.js';

    wp_enqueue_style(
        'bsc-admin-coupons',
        get_template_directory_uri() . '/admin/bsc-coupons.css',
        array( 'bsc-admin-ui' ),
        file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
    );

    wp_enqueue_script(
        'bsc-admin-coupons',
        get_template_directory_uri() . '/js/bsc-admin-coupons.js',
        array(),
        file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
        true
    );
}

// Handle create / delete
add_action( 'admin_init', 'bsc_coupons_handle_actions' );
function bsc_coupons_handle_actions(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'bsc-coupons' !== $page ) return;
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! bsc_current_user_has_bsc_page_access( 'bsc-coupons' ) ) return;

    // Create coupon
    if ( isset( $_POST['bsc_create_coupon_nonce'] ) ) {
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_create_coupon_nonce'] ) ), 'bsc_create_coupon' ) ) {
            wp_die( esc_html__( 'Nonce inválido.', 'bsc-2-0' ) );
        }

        $code          = sanitize_text_field( strtolower( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );
        $discount_type = sanitize_text_field( wp_unslash( $_POST['discount_type'] ?? 'percent' ) );
        $amount        = (float) wp_unslash( $_POST['coupon_amount'] ?? 0 );
        $expiry        = sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) );
        $description   = sanitize_textarea_field( wp_unslash( $_POST['coupon_description'] ?? '' ) );
        $usage_limit   = absint( wp_unslash( $_POST['usage_limit'] ?? 0 ) );
        $min_amount    = (float) wp_unslash( $_POST['minimum_amount'] ?? 0 );
        $allowed_types = [ 'percent', 'fixed_cart', 'fixed_product' ];
        $discount_type = in_array( $discount_type, $allowed_types, true ) ? $discount_type : 'percent';
        $amount        = max( 0, $amount );
        $min_amount    = max( 0, $min_amount );

        if ( 'percent' === $discount_type && $amount > 100 ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'bsc-coupons', 'bsc_notice' => 'invalid_percent' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        if ( $expiry && false === strtotime( $expiry ) ) {
            wp_safe_redirect( add_query_arg( [ 'page' => 'bsc-coupons', 'bsc_notice' => 'invalid_expiry' ], admin_url( 'admin.php' ) ) );
            exit;
        }

        if ( $code ) {
            $coupon = new WC_Coupon();
            $coupon->set_code( $code );
            $coupon->set_discount_type( $discount_type );
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
    if ( isset( $_POST['bsc_delete_coupon'], $_POST['bsc_delete_nonce'] ) ) {
        $coupon_id = absint( wp_unslash( $_POST['bsc_delete_coupon'] ) );
        if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_delete_nonce'] ) ), 'bsc_delete_coupon_' . $coupon_id ) && 'shop_coupon' === get_post_type( $coupon_id ) ) {
            wp_delete_post( $coupon_id, true );
        }
        wp_safe_redirect( add_query_arg( [ 'page' => 'bsc-coupons', 'bsc_notice' => 'deleted' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}

// Page render
function bsc_render_coupons_page(): void {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! bsc_current_user_has_bsc_page_access( 'bsc-coupons' ) ) {
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

        <?php $bsc_notice = isset( $_GET['bsc_notice'] ) ? sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) : ''; ?>
        <?php if ( $bsc_notice ) : ?>
        <?php
            $notice_messages = [
                'created'         => [ 'success', 'Cupón creado correctamente.' ],
                'deleted'         => [ 'success', 'Cupón eliminado.' ],
                'invalid_percent' => [ 'error', 'El porcentaje no puede ser mayor a 100%.' ],
                'invalid_expiry'  => [ 'error', 'La fecha de expiración no es válida.' ],
            ];
            $notice_config = $notice_messages[ $bsc_notice ] ?? [ 'success', 'Acción completada.' ];
        ?>
        <div class="notice notice-<?php echo esc_attr( $notice_config[0] ); ?> is-dismissible"><p>
            <?php echo esc_html( $notice_config[1] ); ?>
        </p></div>
        <?php endif; ?>

        <!-- Create form -->
        <div class="bsc-coupon-create-panel">
            <h2>Crear nuevo cupón</h2>
            <form method="post">
                <?php wp_nonce_field( 'bsc_create_coupon', 'bsc_create_coupon_nonce' ); ?>
                <table class="form-table bsc-admin-coupons__form-table">
                    <tr>
                        <th><label for="coupon_code">Código</label></th>
                        <td>
                            <input type="text" id="coupon_code" name="coupon_code" class="regular-text bsc-admin-coupons__code-input" required
                                   placeholder="ej: BIENVENIDA20">
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

        <!-- Coupons list -->
        <h2 class="bsc-admin-coupons__section-title">Cupones activos</h2>

        <?php if ( ! $coupons_query->have_posts() ) : ?>
            <p class="bsc-admin-coupons__empty">No hay cupones creados todavía.</p>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped bsc-coupons-table">
            <thead>
                <tr>
                    <th class="bsc-admin-coupons__code-col">Código</th>
                    <th>Tipo</th>
                    <th class="bsc-admin-coupons__value-col">Valor</th>
                    <th>Descripción</th>
                    <th class="bsc-admin-coupons__expiry-col">Expira</th>
                    <th class="bsc-admin-coupons__limit-col">Límite</th>
                    <th class="bsc-admin-coupons__usage-col">Usos</th>
                    <th class="bsc-admin-coupons__actions-col">Acciones</th>
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
                $is_expired = $coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time();
            ?>
            <tr class="<?php echo $is_expired ? 'bsc-admin-coupons__expired' : ''; ?>">
                <td>
                    <strong class="bsc-admin-coupons__code">
                        <?php echo esc_html( $coupon->get_code() ); ?>
                    </strong>
                    <?php if ( $is_expired ) echo '<br><span class="bsc-admin-coupons__expired-badge">Expirado</span>'; ?>
                </td>
                <td><?php echo esc_html( $type_label ); ?></td>
                <td><strong><?php echo esc_html( $value ); ?></strong></td>
                <td class="bsc-admin-coupons__description"><?php echo esc_html( $coupon->get_description() ?: '—' ); ?></td>
                <td><?php echo esc_html( $expiry ); ?></td>
                <td><?php echo esc_html( $limit ); ?></td>
                <td><?php echo esc_html( $coupon->get_usage_count() ); ?></td>
                <td>
                    <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" target="_blank">Editar</a>
                    <form method="post" class="bsc-admin-coupons__delete-form">
                        <?php wp_nonce_field( 'bsc_delete_coupon_' . get_the_ID(), 'bsc_delete_nonce' ); ?>
                        <input type="hidden" name="bsc_delete_coupon" value="<?php echo esc_attr( get_the_ID() ); ?>">
                        <button type="submit" class="button button-small bsc-admin-coupons__delete"
                                data-bsc-confirm="¿Eliminar este cupón?">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; wp_reset_postdata(); ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}
