<?php
/**
 * BSC-066: BSC Access Control — checkbox matrix of roles × pages.
 * Saves to `bsc_access_control` WP option. Read by BSC_Permissions.
 *
 * Only configures operational roles (bsc_employee, bsc_operator).
 * Administrators and shop_managers always have full access.
 */
defined('ABSPATH') || exit;

function bsc_render_access_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'No tienes permisos.', 'bsc-2-0' ) );
    }

    // ── Configurable roles ──────────────────────────────────────────
    $roles = [
        'bsc_employee' => 'BSC Empleado',
        'bsc_operator' => 'BSC Operador',
    ];

    // ── All BSC admin pages ─────────────────────────────────────────
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

    // ── Handle save ─────────────────────────────────────────────────
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['bsc_access_nonce'] ) ) {
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_access_nonce'] ) ), 'bsc_access_save' ) ) {
            wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
        }

        $access = [];
        foreach ( array_keys( $roles ) as $role ) {
            $submitted = (array) ( $_POST['access'][ $role ] ?? [] );
            // Only allow valid page slugs
            $access[ $role ] = array_values(
                array_filter( $submitted, fn( $p ) => isset( $pages[ $p ] ) )
            );
        }

        update_option( 'bsc_access_control', $access );
        echo '<div class="notice notice-success is-dismissible"><p>✓ Control de acceso guardado.</p></div>';
    }

    // ── Load saved config ───────────────────────────────────────────
    $saved = get_option( 'bsc_access_control', [] );

    // Merge with defaults so first load has sensible values
    $defaults = [
        'bsc_employee' => ['bsc-dashboard', 'bsc-orders', 'bsc-products', 'bsc-product-edit', 'bsc-showroom', 'bsc-coupons'],
        'bsc_operator' => ['bsc-dashboard', 'bsc-orders', 'bsc-showroom'],
    ];
    foreach ( $defaults as $role => $default_pages ) {
        if ( ! isset( $saved[ $role ] ) ) {
            $saved[ $role ] = $default_pages;
        }
    }
    ?>
    <div class="wrap bsc-admin-access">
        <h1>Control de Acceso BSC</h1>
        <p style="color:#555;max-width:600px">
            Define qué páginas del panel BSC puede ver cada rol operativo.
            <strong>Administradores</strong> y <strong>Shop Managers</strong> siempre tienen acceso completo.
        </p>

        <form method="post">
            <?php wp_nonce_field( 'bsc_access_save', 'bsc_access_nonce' ); ?>
            <table class="bsc-access-table wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th style="width:200px">Página</th>
                        <?php foreach ( $roles as $role => $label ) : ?>
                        <th style="text-align:center;width:150px"><?php echo esc_html( $label ); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $pages as $page_slug => $page_label ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $page_label ); ?></strong><br><small style="color:#888;font-family:monospace"><?php echo esc_html( $page_slug ); ?></small></td>
                    <?php foreach ( array_keys( $roles ) as $role ) :
                        $is_checked = in_array( $page_slug, $saved[ $role ] ?? [], true );
                        // bsc-orders is always mandatory for all operational roles
                        $is_locked  = ( $page_slug === 'bsc-orders' );
                    ?>
                    <td style="text-align:center">
                        <input type="checkbox"
                               name="access[<?php echo esc_attr( $role ); ?>][]"
                               value="<?php echo esc_attr( $page_slug ); ?>"
                               <?php checked( $is_checked || $is_locked ); ?>
                               <?php echo $is_locked ? 'disabled' : ''; ?>>
                        <?php if ( $is_locked ) : ?>
                        <input type="hidden" name="access[<?php echo esc_attr( $role ); ?>][]" value="<?php echo esc_attr( $page_slug ); ?>">
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:16px;padding:12px 16px;background:#fffbeb;border:1px solid #f6ad55;border-radius:6px;max-width:700px">
                <strong>Nota:</strong> "Pedidos" está marcado como obligatorio para todos los roles operativos y no se puede desmarcar.
                Los cambios se aplican inmediatamente al guardar.
            </div>

            <?php submit_button( 'Guardar control de acceso', 'primary', 'submit', false, ['style' => 'margin-top:16px'] ); ?>
        </form>
    </div>

    <style>
        .bsc-access-table td, .bsc-access-table th { vertical-align:middle; padding:10px 12px; }
        .bsc-access-table input[type=checkbox] { width:18px; height:18px; cursor:pointer; }
        .bsc-access-table input[disabled] { opacity:.5; cursor:not-allowed; }
        .bsc-access-table tbody tr:hover { background:#f9f9f9; }
    </style>
    <?php
}
