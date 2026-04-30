<?php

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BSC_ACCESS_CONTROL_OPTION' ) ) {
    define( 'BSC_ACCESS_CONTROL_OPTION', 'bsc_access_control' );
}

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_access_admin_assets' );
add_action( 'admin_init', 'bsc_handle_access_control_save' );
add_action( 'admin_init', 'bsc_guard_restricted_admin_pages', 20 );

function bsc_enqueue_access_admin_assets(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'bsc-access' !== $page ) {
        return;
    }

    bsc_enqueue_admin_ui_assets();

    $css_path = get_template_directory() . '/admin/bsc-admin-communications.css';

    wp_enqueue_style(
        'bsc-admin-communications',
        get_template_directory_uri() . '/admin/bsc-admin-communications.css',
        array( 'bsc-admin-ui' ),
        file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
    );
}

function bsc_access_role_labels(): array {
    return array(
        'bsc_shop_manager' => 'BSC Shop Manager',
        'bsc_employee'     => 'BSC Empleado',
        'bsc_operator'     => 'BSC Operador',
    );
}

function bsc_access_managed_pages(): array {
    return array(
        'bsc-dashboard'     => 'Dashboard',
        'bsc-orders'        => 'Pedidos',
        'bsc-products'      => 'Productos',
        'bsc-product-edit'  => 'Editar producto',
        'bsc-reports'       => 'Informes',
        'bsc-showroom'      => 'Venta presencial',
        'bsc-coupons'       => 'Cupones',
        'bsc-settings'      => 'ConfiguraciÃ³n',
        'bsc-home-favorites'=> 'Home Favorites',
        'bsc-bubble-points' => 'Bubble Points',
        'bsc-followup-emails' => 'Emails',
        'bsc-access'        => 'Acceso',
    );
}

function bsc_access_visible_pages(): array {
    $pages = bsc_access_managed_pages();
    unset( $pages['bsc-product-edit'] );

    return $pages;
}

function bsc_access_default_config(): array {
    return array(
        'bsc_employee' => array( 'bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-product-edit', 'bsc-showroom', 'bsc-coupons' ),
        'bsc_operator' => array( 'bsc-dashboard', 'bsc-orders', 'bsc-showroom' ),
    );
}

function bsc_access_required_pages(): array {
    return array(
        'bsc_employee' => array( 'bsc-orders' ),
        'bsc_operator' => array( 'bsc-orders' ),
    );
}

function bsc_access_normalize_config( array $config ): array {
    $managed_pages = array_keys( bsc_access_managed_pages() );
    $defaults      = bsc_access_default_config();
    $required      = bsc_access_required_pages();
    $normalized    = array();

    foreach ( array_keys( $defaults ) as $role ) {
        $submitted = $config[ $role ] ?? $defaults[ $role ];
        $submitted = array_map( 'sanitize_key', (array) $submitted );
        $submitted = array_values( array_unique( array_intersect( $managed_pages, $submitted ) ) );
        $submitted = array_values( array_unique( array_merge( $submitted, $required[ $role ] ?? array() ) ) );

        if ( in_array( 'bsc-products', $submitted, true ) && ! in_array( 'bsc-product-edit', $submitted, true ) ) {
            $submitted[] = 'bsc-product-edit';
        }

        if ( ! in_array( 'bsc-products', $submitted, true ) ) {
            $submitted = array_values( array_diff( $submitted, array( 'bsc-product-edit' ) ) );
        }

        $normalized[ $role ] = $submitted;
    }

    return $normalized;
}

function bsc_access_get_config(): array {
    $saved = get_option( BSC_ACCESS_CONTROL_OPTION, array() );

    return bsc_access_normalize_config( is_array( $saved ) ? $saved : array() );
}

function bsc_access_role_has_page( string $role, string $page_slug ): bool {
    if ( 'bsc_shop_manager' === $role ) {
        return true;
    }

    $config = bsc_access_get_config();

    return in_array( $page_slug, $config[ $role ] ?? array(), true );
}

function bsc_current_user_has_bsc_page_access( string $page_slug ): bool {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }

    $roles = (array) wp_get_current_user()->roles;

    if ( in_array( 'bsc_shop_manager', $roles, true ) ) {
        return true;
    }

    foreach ( array( 'bsc_employee', 'bsc_operator' ) as $role ) {
        if ( in_array( $role, $roles, true ) ) {
            return bsc_access_role_has_page( $role, $page_slug );
        }
    }

    return true;
}

function bsc_handle_access_control_save(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'bsc-access' !== $page || ! isset( $_POST['bsc_access_nonce'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    check_admin_referer( 'bsc_access_save', 'bsc_access_nonce' );

    $submitted = isset( $_POST['access'] ) && is_array( $_POST['access'] )
        ? wp_unslash( $_POST['access'] )
        : array();

    update_option( BSC_ACCESS_CONTROL_OPTION, bsc_access_normalize_config( $submitted ) );

    wp_safe_redirect(
        add_query_arg(
            array(
                'page'       => 'bsc-access',
                'bsc_notice' => 'saved',
            ),
            admin_url( 'admin.php' )
        )
    );
    exit;
}

function bsc_guard_restricted_admin_pages(): void {
    if ( ! is_admin() ) {
        return;
    }

    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( ! $page || 0 !== strpos( $page, 'bsc-' ) ) {
        return;
    }

    if ( bsc_current_user_has_bsc_page_access( $page ) ) {
        return;
    }

    wp_die( esc_html__( 'No tienes acceso a esta secciÃ³n.', 'bsc-2-0' ) );
}

function bsc_render_access_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    $roles    = bsc_access_role_labels();
    $pages    = bsc_access_visible_pages();
    $config   = bsc_access_get_config();
    $required = bsc_access_required_pages();
    ?>
    <div class="wrap bsc-admin-access">
        <h1>Control de acceso BSC</h1>
        <p class="bsc-admin-access__intro">Define quÃ© pÃ¡ginas del panel BSC puede ver cada rol operativo. Administradores y Shop Managers mantienen acceso completo.</p>

        <?php if ( isset( $_GET['bsc_notice'] ) && 'saved' === sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) ) : ?>
            <div class="bsc-admin-note bsc-admin-note--success">
                ConfiguraciÃ³n de acceso guardada.
            </div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'bsc_access_save', 'bsc_access_nonce' ); ?>
            <table class="bsc-admin-access__table wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th>PÃ¡gina</th>
                        <?php foreach ( $roles as $role => $label ) : ?>
                            <th class="bsc-admin-access__role-header"><?php echo esc_html( $label ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $pages as $page_slug => $page_label ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $page_label ); ?></strong><br>
                                <small class="bsc-admin-access__slug"><?php echo esc_html( $page_slug ); ?></small>
                            </td>
                            <?php foreach ( $roles as $role => $label ) : ?>
                                <td>
                                    <?php if ( 'bsc_shop_manager' === $role ) : ?>
                                        <span class="bsc-admin-badge bsc-admin-badge--locked">Acceso completo</span>
                                    <?php else : ?>
                                        <?php
                                        $is_required = in_array( $page_slug, $required[ $role ] ?? array(), true );
                                        $is_allowed  = in_array( $page_slug, $config[ $role ] ?? array(), true );
                                        ?>
                                        <label class="bsc-admin-access__checkbox-wrap">
                                            <input
                                                type="checkbox"
                                                name="access[<?php echo esc_attr( $role ); ?>][]"
                                                value="<?php echo esc_attr( $page_slug ); ?>"
                                                <?php checked( $is_allowed ); ?>
                                                <?php disabled( $is_required ); ?>
                                            >
                                            <span class="screen-reader-text"><?php echo esc_html( $page_label . ' / ' . $label ); ?></span>
                                        </label>
                                        <?php if ( $is_required ) : ?>
                                            <input type="hidden" name="access[<?php echo esc_attr( $role ); ?>][]" value="<?php echo esc_attr( $page_slug ); ?>">
                                            <span class="bsc-admin-badge bsc-admin-badge--locked">Obligatorio</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="bsc-admin-note bsc-admin-note--warning">
                <strong>Nota:</strong> <code>Pedidos</code> estÃ¡ bloqueado para todos los roles operativos. Si un rol pierde acceso a <code>Productos</code>, tambiÃ©n pierde acceso al editor oculto de producto.
            </div>

            <?php submit_button( 'Guardar control de acceso', 'primary bsc-admin-access__submit', 'submit', false ); ?>
        </form>
    </div>
    <?php
}
