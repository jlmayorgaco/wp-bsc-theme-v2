<?php
/**
 * Admin workflow for Bubble Creators applications stored by the public form.
 */
defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', 'bsc_enqueue_creators_admin_assets');
add_action('admin_init', 'bsc_creators_handle_actions');

function bsc_enqueue_creators_admin_assets(): void {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    if ('bsc-creators' !== $page) {
        return;
    }

    if (function_exists('bsc_enqueue_admin_ui_assets')) {
        bsc_enqueue_admin_ui_assets();
    }

    $css_path = get_template_directory() . '/admin/bsc-creators.css';

    wp_enqueue_style(
        'bsc-admin-creators',
        get_template_directory_uri() . '/admin/bsc-creators.css',
        ['bsc-admin-ui'],
        file_exists($css_path) ? (string) filemtime($css_path) : '1'
    );
}

function bsc_creators_user_can_manage(): bool {
    return current_user_can('manage_options') || current_user_can('manage_woocommerce');
}

function bsc_creators_get_applications(): array {
    $applications = get_option('bsc_creator_applications', []);
    return is_array($applications) ? array_values($applications) : [];
}

function bsc_creators_save_applications(array $applications): void {
    update_option('bsc_creator_applications', array_values($applications), false);
}

function bsc_creators_status_options(): array {
    return [
        'new'       => 'Nueva',
        'contacted' => 'Contactada',
        'approved'  => 'Aprobada',
        'discarded' => 'Descartada',
    ];
}

function bsc_creators_normalize_status(string $status): string {
    $aliases = [
        'reviewed' => 'contacted',
        'rejected' => 'discarded',
    ];

    $status = $aliases[$status] ?? $status;

    return array_key_exists($status, bsc_creators_status_options()) ? $status : 'new';
}

function bsc_creators_handle_actions(): void {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    if ('bsc-creators' !== $page || ! bsc_creators_user_can_manage()) {
        return;
    }

    if (isset($_POST['bsc_creator_action'])) {
        if (! isset($_POST['bsc_creators_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bsc_creators_nonce'])), 'bsc_creators_action')) {
            wp_die(esc_html__('Solicitud no válida.', 'bsc-2-0'));
        }

        $action = sanitize_key(wp_unslash($_POST['bsc_creator_action']));

        if ('update_status' === $action) {
            $index = isset($_POST['application_id']) ? absint(wp_unslash($_POST['application_id'])) : -1;
            $status = isset($_POST['application_status']) ? sanitize_key(wp_unslash($_POST['application_status'])) : 'new';
            $notes = isset($_POST['application_notes']) ? sanitize_textarea_field(wp_unslash($_POST['application_notes'])) : '';
            $applications = bsc_creators_get_applications();

            if (isset($applications[$index])) {
                $applications[$index]['status'] = bsc_creators_normalize_status($status);
                $applications[$index]['notes'] = $notes;
                $applications[$index]['updated_at'] = current_time('mysql');
                $applications[$index]['updated_by'] = get_current_user_id();
                bsc_creators_save_applications($applications);
            }

            wp_safe_redirect(add_query_arg(['page' => 'bsc-creators', 'bsc_notice' => 'updated'], admin_url('admin.php')));
            exit;
        }
    }

    if (isset($_GET['bsc_export'], $_GET['_wpnonce']) && 'creators' === sanitize_key(wp_unslash($_GET['bsc_export']))) {
        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'bsc_creators_export')) {
            wp_die(esc_html__('Solicitud no válida.', 'bsc-2-0'));
        }

        bsc_creators_export_csv();
    }
}

function bsc_creators_export_csv(): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="bubble-creators-' . gmdate('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Fecha', 'Estado', 'Nombre', 'Email', 'Instagram', 'TikTok', 'Origen', 'Mensaje', 'Notas']);

    foreach (bsc_creators_get_applications() as $application) {
        fputcsv(
            $out,
            [
                $application['date'] ?? '',
                bsc_creators_status_options()[bsc_creators_normalize_status((string) ($application['status'] ?? 'new'))],
                $application['nombre'] ?? '',
                $application['email'] ?? '',
                $application['instagram'] ?? '',
                $application['tiktok'] ?? '',
                $application['source'] ?? '',
                $application['mensaje'] ?? '',
                $application['notes'] ?? '',
            ]
        );
    }

    fclose($out);
    exit;
}

function bsc_render_creators_page(): void {
    if (! bsc_creators_user_can_manage()) {
        wp_die(esc_html__('No tienes permisos.', 'bsc-2-0'));
    }

    $applications = bsc_creators_get_applications();
    $status_filter = isset($_GET['creator_status']) ? sanitize_key(wp_unslash($_GET['creator_status'])) : '';
    $statuses = bsc_creators_status_options();
    $status_filter = array_key_exists($status_filter, $statuses) ? $status_filter : '';
    $export_url = wp_nonce_url(
        add_query_arg(['page' => 'bsc-creators', 'bsc_export' => 'creators'], admin_url('admin.php')),
        'bsc_creators_export'
    );
    ?>
    <div class="wrap bsc-admin-creators">
        <div class="bsc-admin-creators__header">
            <h1 class="wp-heading-inline">Bubble Creators</h1>
            <a href="<?php echo esc_url($export_url); ?>" class="page-title-action">Exportar CSV</a>
        </div>
        <hr class="wp-header-end">

        <?php if (isset($_GET['bsc_notice'])) : ?>
            <div class="bsc-admin-note bsc-admin-note--success">Solicitud actualizada.</div>
        <?php endif; ?>

        <form method="get" class="bsc-admin-creators__filters">
            <input type="hidden" name="page" value="bsc-creators">
            <label for="creator_status">Estado</label>
            <select id="creator_status" name="creator_status">
                <option value="">Todos</option>
                <?php foreach ($statuses as $status_key => $status_label) : ?>
                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status_filter, $status_key); ?>>
                        <?php echo esc_html($status_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button">Filtrar</button>
        </form>

        <table class="wp-list-table widefat fixed striped bsc-admin-creators__table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Instagram</th>
                    <th>TikTok</th>
                    <th>Origen</th>
                    <th>Mensaje</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $visible_rows = 0;
            foreach ($applications as $index => $application) :
                $status = bsc_creators_normalize_status((string) ($application['status'] ?? 'new'));

                if ($status_filter && $status !== $status_filter) {
                    continue;
                }

                $visible_rows++;
                $instagram_url = esc_url($application['instagram'] ?? '');
                $tiktok_url = esc_url($application['tiktok'] ?? '');
                ?>
                <tr>
                    <td><?php echo esc_html($application['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($statuses[$status]); ?></td>
                    <td><?php echo esc_html($application['nombre'] ?? ''); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($application['email'] ?? ''); ?>"><?php echo esc_html($application['email'] ?? ''); ?></a></td>
                    <td>
                        <?php if ($instagram_url) : ?>
                            <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
                        <?php else : ?>
                            <span class="bsc-admin-creators__link-empty">No registrado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tiktok_url) : ?>
                            <a href="<?php echo esc_url($tiktok_url); ?>" target="_blank" rel="noopener noreferrer">TikTok</a>
                        <?php else : ?>
                            <span class="bsc-admin-creators__link-empty">No registrado</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($application['source'] ?? 'bubble-creators-form'); ?></td>
                    <td><?php echo esc_html(wp_trim_words((string) ($application['mensaje'] ?? ''), 18)); ?></td>
                    <td><?php echo esc_html(wp_trim_words((string) ($application['notes'] ?? ''), 14)); ?></td>
                    <td>
                        <form method="post" class="bsc-admin-creators__status-form">
                            <?php wp_nonce_field('bsc_creators_action', 'bsc_creators_nonce'); ?>
                            <input type="hidden" name="bsc_creator_action" value="update_status">
                            <input type="hidden" name="application_id" value="<?php echo esc_attr($index); ?>">
                            <select name="application_status">
                                <?php foreach ($statuses as $status_key => $status_label) : ?>
                                    <option value="<?php echo esc_attr($status_key); ?>" <?php selected($status, $status_key); ?>>
                                        <?php echo esc_html($status_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="application_notes" rows="2" placeholder="Notas internas"><?php echo esc_textarea($application['notes'] ?? ''); ?></textarea>
                            <button type="submit" class="button button-small">Guardar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (0 === $visible_rows) : ?>
                <tr><td colspan="10"><?php echo esc_html($status_filter ? 'No hay solicitudes con este estado.' : 'No hay solicitudes de creators todavia.'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
