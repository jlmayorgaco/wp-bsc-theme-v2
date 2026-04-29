<?php

define('BSC_ACCESS_CONTROL_OPTION', 'bsc_access_control');

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_access_admin_assets' );
function bsc_enqueue_access_admin_assets(): void {
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

    if ( 'bsc-access' !== $page ) {
        return;
    }

    $css_path = get_template_directory() . '/admin/bsc-admin-communications.css';

    wp_enqueue_style(
        'bsc-admin-communications',
        get_template_directory_uri() . '/admin/bsc-admin-communications.css',
        array(),
        file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
    );
}

function bsc_render_access_page(): void {
    // Security Check -  More robust than just checking for manage_options
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos.', 'bsc-2-0'));
    }

    $roles = [
        'bsc_shop_manager' => 'BSC Shop Manager',
        'bsc_employee' => 'BSC Empleado',
        'bsc_operator' => 'BSC Operador',
    ];

    $pages = [
        'bsc-dashboard'     => 'Dashboard',
        'bsc-orders'        => 'Pedidos',
        'bsc-products'      => 'Productos',
        'bsc-reports'       => 'Informes',
        'bsc-showroom'      => 'Venta Presencial',
        'bsc-coupons'       => 'Cupones',
        'bsc-settings'      => 'Configuración',
        'bsc-home-favorites'=> 'Home Favorites',
        'bsc-bubble-points' => 'Bubble Points',
    ];

    // Load Saved Configuration
    $saved_config = get_option(BSC_ACCESS_CONTROL_OPTION, []);

    // Default configuration -  Centralized and easily updated.
    $default_config = [
        'bsc_employee' => ['bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-product-edit', 'bsc-showroom', 'bsc-coupons'],
        'bsc_operator' => ['bsc-dashboard', 'bsc-orders', 'bsc-showroom'],
    ];

    // Merge configurations -  Simple and clear.
    $config = array_merge($default_config, $saved_config);

    ?>
    <div class="bsc-admin-access">
        <h1>Control de Acceso BSC</h1>
        <p class="bsc-admin-access__intro">Define qué páginas del panel BSC puede ver cada rol operativo. Administradores y Shop Managers siempre tienen acceso completo.</p>

        <form method="post">
            <?php wp_nonce_field('bsc_access_save', 'bsc_access_nonce'); ?>
            <table class="bsc-admin-access__table wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th>Página</th>
                        <?php foreach ($roles as $role => $label) : ?>
                            <th class="bsc-admin-access__role-header"><?php echo esc_html($label); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page_slug => $page_label) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($page_label); ?><br><small class="bsc-admin-access__slug"><?php echo esc_html($page_slug); ?></small></strong></td>
                            <?php foreach ($roles as $role => $label) : ?>
                                <td>
                                    <input type="checkbox"
                                        name="access[<?php echo esc_attr($role); ?>][]"
                                        value="<?php echo esc_attr($page_slug); ?>"
                                        <?php checked(in_array($page_slug, $config[$role] ?? [], true)); ?>>
                                    <?php if (in_array($page_slug, $config[$role] ?? [])) { ?>
                                        <input type="hidden" name="access[<?php echo esc_attr($role); ?>][]" value="<?php echo esc_attr($page_slug); ?>">
                                    <?php } ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="bsc-admin-access__container">
                <strong>Nota:</strong> "Pedidos" está marcado como obligatorio para todos los roles operativos y no se puede desmarcar. Los cambios se aplican inmediatamente al guardar.
            </div>

            <?php submit_button('Guardar control de acceso', 'primary bsc-admin-access__submit', 'submit', false); ?>
        </form>
    </div>
    <?php
}
