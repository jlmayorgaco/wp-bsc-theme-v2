<?php

defined('ABSPATH') || exit;

include_once plugin_dir_path(__FILE__) . 'reset-categories.php';
include_once plugin_dir_path(__FILE__) . 'seed-categories.php';
include_once plugin_dir_path(__FILE__) . 'reset-products.php';
include_once plugin_dir_path(__FILE__) . 'seed-products.php';

include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_Config.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_FileManager.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_MediaManager.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_MediaCleaner.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_ProductManager.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_Processor.php';
include_once plugin_dir_path(__FILE__) . 'classes/BSC_Theme_Importer_PPU_Init.php';

add_action('admin_post_bsc_theme_importer_download_categories', 'bsc_theme_importer_handle_download_categories');
add_action('BSC_Theme_Importer_PPU_process_photos_event', ['BSC_Theme_Importer_PPU_Init', 'init']);

function bsc_theme_importer_plugin_capability(): string
{
    return 'manage_options';
}

function bsc_theme_importer_require_capability(): void
{
    if (!current_user_can(bsc_theme_importer_plugin_capability())) {
        wp_die(esc_html__('No tienes permisos para usar esta pantalla.', 'bubblesskincare'));
    }
}

function bsc_theme_importer_plugin_asset_url(string $relative_path): string
{
    $base_url = defined('BSC_THEME_IMPORTER_URL') ? BSC_THEME_IMPORTER_URL : plugins_url('../', __FILE__);
    return trailingslashit($base_url) . ltrim($relative_path, '/');
}

function bsc_theme_importer_plugin_asset_path(string $relative_path): string
{
    $base_path = defined('BSC_THEME_IMPORTER_PATH') ? BSC_THEME_IMPORTER_PATH : realpath(dirname(__FILE__) . '/..');
    return trailingslashit((string) $base_path) . ltrim($relative_path, '/');
}

function bsc_theme_importer_enqueue_assets(): void
{
    if (function_exists('bsc_enqueue_admin_ui_assets')) {
        bsc_enqueue_admin_ui_assets();
    }

    $css_path = bsc_theme_importer_plugin_asset_path('assets/css/bsc-importer-admin.css');
    $js_path  = bsc_theme_importer_plugin_asset_path('assets/js/bsc-importer-admin.js');

    wp_enqueue_style(
        'bsc-importer-admin',
        bsc_theme_importer_plugin_asset_url('assets/css/bsc-importer-admin.css'),
        [],
        file_exists($css_path) ? (string) filemtime($css_path) : BSC_THEME_IMPORTER_VERSION
    );

    wp_enqueue_script(
        'bsc-importer-admin',
        bsc_theme_importer_plugin_asset_url('assets/js/bsc-importer-admin.js'),
        [],
        file_exists($js_path) ? (string) filemtime($js_path) : BSC_THEME_IMPORTER_VERSION,
        true
    );
}

function bsc_theme_importer_nonce_field(string $action): void
{
    wp_nonce_field('bsc_theme_importer_action_' . $action, 'bsc_theme_importer_nonce');
}

function bsc_theme_importer_make_notice(string $type, string $message, string $details = ''): array
{
    return [
        'type'    => $type,
        'message' => $message,
        'details' => $details,
    ];
}

function bsc_theme_importer_upload_error_message(int $error_code): string
{
    $messages = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el tamano maximo del servidor.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el tamano permitido por el formulario.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subio incompleto.',
        UPLOAD_ERR_NO_FILE    => 'Selecciona un archivo antes de continuar.',
        UPLOAD_ERR_NO_TMP_DIR => 'El servidor no tiene directorio temporal configurado.',
        UPLOAD_ERR_CANT_WRITE => 'El servidor no pudo escribir el archivo.',
        UPLOAD_ERR_EXTENSION  => 'Una extension de PHP bloqueo la subida.',
    ];

    return $messages[$error_code] ?? 'No se pudo subir el archivo.';
}

function bsc_theme_importer_woocommerce_ready(): bool
{
    return function_exists('wc_get_product') && class_exists('WC_Product_Simple');
}

function bsc_theme_importer_require_woocommerce(): void
{
    if (!bsc_theme_importer_woocommerce_ready()) {
        throw new RuntimeException('WooCommerce debe estar activo antes de ejecutar este proceso.');
    }
}

function bsc_theme_importer_requires_confirmation(string $action): bool
{
    return in_array($action, ['delete_categories', 'delete_products', 'process_product_photos'], true);
}

function bsc_theme_importer_validate_action_confirmation(string $action): void
{
    if (!bsc_theme_importer_requires_confirmation($action)) {
        return;
    }

    $confirmation = isset($_POST['bsc_theme_importer_confirm'])
        ? sanitize_key(wp_unslash((string) $_POST['bsc_theme_importer_confirm']))
        : '';

    if ($confirmation !== $action) {
        throw new RuntimeException('Marca la confirmacion de seguridad antes de continuar.');
    }
}

function bsc_theme_importer_read_uploaded_json(string $field_name): array
{
    if (empty($_FILES[$field_name]) || !is_array($_FILES[$field_name])) {
        throw new RuntimeException('Selecciona un archivo JSON.');
    }

    $file = $_FILES[$field_name];
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(bsc_theme_importer_upload_error_message($error));
    }

    $name = sanitize_file_name((string) ($file['name'] ?? ''));
    $tmp_name = (string) ($file['tmp_name'] ?? '');
    $max_size = wp_max_upload_size();
    $size = isset($file['size']) ? (int) $file['size'] : 0;

    if (!$tmp_name || !is_uploaded_file($tmp_name)) {
        throw new RuntimeException('WordPress no reconoce el archivo subido.');
    }

    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
        throw new RuntimeException('El archivo debe tener extension .json.');
    }

    if ($size <= 0 || $size > $max_size) {
        throw new RuntimeException('El JSON esta vacio o supera ' . size_format($max_size) . '.');
    }

    $contents = file_get_contents($tmp_name);

    if ($contents === false || trim($contents) === '') {
        throw new RuntimeException('No se pudo leer el archivo JSON.');
    }

    $decoded = json_decode($contents, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        throw new RuntimeException('JSON invalido: ' . json_last_error_msg());
    }

    return $decoded;
}

function bsc_theme_importer_validate_categories_payload(array $payload): array
{
    foreach ($payload as $index => $node) {
        if (!is_array($node) || empty($node['SLUG']) || empty($node['LABEL'])) {
            throw new RuntimeException('El JSON de categorias debe ser una lista con SLUG y LABEL. Error en fila ' . ((int) $index + 1) . '.');
        }
    }

    return $payload;
}

function bsc_theme_importer_validate_products_payload(array $payload): array
{
    $groups = ['sk', 'hc', 'mk'];
    $has_rows = false;

    foreach ($groups as $group) {
        if (!isset($payload[$group])) {
            $payload[$group] = [];
        }

        if (!is_array($payload[$group])) {
            throw new RuntimeException('El grupo "' . $group . '" debe ser una lista de productos.');
        }

        foreach ($payload[$group] as $index => $node) {
            if (!is_array($node) || empty($node['ID']) || empty($node['NAME']) || !array_key_exists('PRICE', $node)) {
                throw new RuntimeException('El JSON de productos necesita ID, NAME y PRICE. Error en ' . strtoupper($group) . ' fila ' . ((int) $index + 1) . '.');
            }

            $has_rows = true;
        }
    }

    if (!$has_rows) {
        throw new RuntimeException('El JSON de productos no contiene productos en sk, hc o mk.');
    }

    return $payload;
}

function bsc_theme_importer_count_product_categories(): int
{
    if (!taxonomy_exists('product_cat')) {
        return 0;
    }

    $count = wp_count_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ]);

    return is_wp_error($count) ? 0 : (int) $count;
}

function bsc_theme_importer_count_products(): int
{
    if (!post_type_exists('product')) {
        return 0;
    }

    $counts = wp_count_posts('product');

    if (!$counts) {
        return 0;
    }

    return array_sum(array_map('intval', (array) $counts));
}

function bsc_theme_importer_upload_subpath(string $absolute_path): string
{
    $upload_dir = wp_upload_dir();
    $base_dir = isset($upload_dir['basedir']) ? untrailingslashit((string) $upload_dir['basedir']) : '';

    if ($base_dir !== '' && strpos($absolute_path, $base_dir) === 0) {
        return ltrim(str_replace('\\', '/', substr($absolute_path, strlen($base_dir))), '/');
    }

    return basename($absolute_path);
}

function bsc_theme_importer_capture_output(callable $callback): array
{
    ob_start();

    try {
        $return = $callback();
        $output = (string) ob_get_clean();
    } catch (Throwable $throwable) {
        ob_end_clean();
        throw $throwable;
    }

    return [$return, $output];
}

function bsc_theme_importer_excerpt_output(string $output): string
{
    $output = trim(wp_strip_all_tags($output));

    if ($output === '') {
        return '';
    }

    if (strlen($output) > 4000) {
        return substr($output, 0, 4000) . "\n...";
    }

    return $output;
}

function bsc_theme_importer_product_photos_zip_path(): string
{
    return trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . BSC_Theme_Importer_PPU_Config::getPhotoZipFilename();
}

function bsc_theme_importer_product_photos_zip_status(): array
{
    $path = bsc_theme_importer_product_photos_zip_path();
    $exists = file_exists($path);

    return [
        'path'     => $path,
        'exists'   => $exists,
        'size'     => $exists ? size_format((int) filesize($path)) : '',
        'modified' => $exists ? date_i18n('Y-m-d H:i', (int) filemtime($path)) : '',
    ];
}

function bsc_theme_importer_is_unsafe_zip_entry(string $entry): bool
{
    $normalized = str_replace('\\', '/', $entry);
    $parts = array_filter(explode('/', $normalized), static fn($part): bool => $part !== '');

    return in_array('..', $parts, true)
        || strpos($normalized, ':') !== false
        || substr($normalized, 0, 1) === '/';
}

function bsc_theme_importer_validate_product_photos_zip(ZipArchive $zip, int $compressed_size): void
{
    $max_entries = 3000;
    $max_uncompressed_size = 750 * MB_IN_BYTES;
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    $ignored_filenames = ['.ds_store', 'thumbs.db'];
    $total_uncompressed = 0;
    $image_count = 0;

    if ($zip->numFiles <= 0) {
        throw new RuntimeException('El ZIP no contiene archivos.');
    }

    if ($zip->numFiles > $max_entries) {
        throw new RuntimeException('El ZIP contiene demasiados archivos. Maximo permitido: ' . $max_entries . '.');
    }

    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entry = (string) $zip->getNameIndex($index);
        $entry = str_replace('\\', '/', $entry);

        if ($entry === '' || bsc_theme_importer_is_unsafe_zip_entry($entry)) {
            throw new RuntimeException('El ZIP contiene una ruta insegura.');
        }

        $basename = strtolower(basename($entry));
        $is_directory = substr($entry, -1) === '/';
        $is_ignored = in_array($basename, $ignored_filenames, true) || strpos($entry, '__MACOSX/') === 0;

        if ($is_directory || $is_ignored) {
            continue;
        }

        $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed_extensions, true)) {
            throw new RuntimeException('El ZIP solo puede contener imagenes JPG, PNG o WebP. Archivo no permitido: ' . basename($entry));
        }

        $stat = $zip->statIndex($index);
        $total_uncompressed += isset($stat['size']) ? (int) $stat['size'] : 0;
        $image_count++;

        if ($total_uncompressed > $max_uncompressed_size) {
            throw new RuntimeException('El contenido descomprimido del ZIP supera ' . size_format($max_uncompressed_size) . '.');
        }
    }

    if ($image_count <= 0) {
        throw new RuntimeException('El ZIP no contiene imagenes validas.');
    }

    if ($compressed_size > 0 && $total_uncompressed > ($compressed_size * 12)) {
        throw new RuntimeException('El ZIP parece estar comprimido de forma anomala. Reexporta las imagenes y vuelve a intentarlo.');
    }
}

function bsc_theme_importer_upload_product_photos_zip(array $file): string
{
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(bsc_theme_importer_upload_error_message($error));
    }

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('El servidor no tiene ZipArchive activo.');
    }

    $name = sanitize_file_name((string) ($file['name'] ?? ''));
    $tmp_name = (string) ($file['tmp_name'] ?? '');
    $size = isset($file['size']) ? (int) $file['size'] : 0;
    $max_size = wp_max_upload_size();

    if (!$tmp_name || !is_uploaded_file($tmp_name)) {
        throw new RuntimeException('WordPress no reconoce el ZIP subido.');
    }

    if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'zip') {
        throw new RuntimeException('El archivo de imagenes debe ser un .zip.');
    }

    if ($size <= 0 || $size > $max_size) {
        throw new RuntimeException('El ZIP esta vacio o supera ' . size_format($max_size) . '.');
    }

    $zip = new ZipArchive();
    $open_result = $zip->open($tmp_name, ZipArchive::CHECKCONS);

    if ($open_result !== true) {
        throw new RuntimeException('El ZIP no se pudo abrir o esta corrupto.');
    }

    bsc_theme_importer_validate_product_photos_zip($zip, $size);

    $zip->close();

    $target_dir = BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory();

    if (!wp_mkdir_p($target_dir)) {
        throw new RuntimeException('No se pudo crear el directorio de imagenes: ' . $target_dir);
    }

    $target = trailingslashit($target_dir) . BSC_Theme_Importer_PPU_Config::getPhotoZipFilename();

    if (file_exists($target)) {
        wp_delete_file($target);
    }

    if (!move_uploaded_file($tmp_name, $target)) {
        throw new RuntimeException('No se pudo mover el ZIP al directorio final.');
    }

    return size_format($size);
}

function bsc_theme_importer_process_product_photos(): string
{
    $zip_status = bsc_theme_importer_product_photos_zip_status();

    if (!$zip_status['exists']) {
        throw new RuntimeException('Sube primero el ZIP de fotos.');
    }

    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    [, $output] = bsc_theme_importer_capture_output(static function (): void {
        BSC_Theme_Importer_PPU_Init::init();
    });

    return bsc_theme_importer_excerpt_output($output);
}

function bsc_theme_importer_process_post_action(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['bsc_theme_importer_action'])) {
        return [];
    }

    bsc_theme_importer_require_capability();

    $action = sanitize_key(wp_unslash((string) $_POST['bsc_theme_importer_action']));

    if (
        empty($_POST['bsc_theme_importer_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['bsc_theme_importer_nonce'])), 'bsc_theme_importer_action_' . $action)
    ) {
        return [bsc_theme_importer_make_notice('error', 'Solicitud no valida. Recarga la pagina e intenta de nuevo.')];
    }

    try {
        bsc_theme_importer_validate_action_confirmation($action);

        switch ($action) {
            case 'delete_categories':
                bsc_theme_importer_require_woocommerce();

                [$deleted] = bsc_theme_importer_capture_output(static function (): int {
                    return bsc_theme_importer_delete_categories();
                });

                return [bsc_theme_importer_make_notice('success', $deleted . ' categorias borradas.')];

            case 'upload_categories':
                bsc_theme_importer_require_woocommerce();

                $payload = bsc_theme_importer_validate_categories_payload(bsc_theme_importer_read_uploaded_json('upload_categories_json'));
                [$created, $output] = bsc_theme_importer_capture_output(static function () use ($payload) {
                    $count = bsc_theme_importer_upload_categories($payload);
                    bsc_theme_importer_after_upload_categories();
                    return $count;
                });

                return [
                    bsc_theme_importer_make_notice(
                        'success',
                        'Categorias importadas. Nuevas categorias: ' . (int) $created . '.',
                        bsc_theme_importer_excerpt_output($output)
                    ),
                ];

            case 'delete_products':
                bsc_theme_importer_require_woocommerce();

                [$deleted] = bsc_theme_importer_capture_output(static function (): int {
                    return bsc_theme_importer_delete_products();
                });

                return [bsc_theme_importer_make_notice('success', $deleted . ' productos borrados.')];

            case 'upload_products':
                bsc_theme_importer_require_woocommerce();

                $payload = bsc_theme_importer_validate_products_payload(bsc_theme_importer_read_uploaded_json('upload_products_json'));
                [$processed, $output] = bsc_theme_importer_capture_output(static function () use ($payload) {
                    return bsc_theme_importer_upload_products($payload);
                });

                return [
                    bsc_theme_importer_make_notice(
                        'success',
                        'Productos importados o actualizados: ' . (int) $processed . '.',
                        bsc_theme_importer_excerpt_output($output)
                    ),
                ];

            case 'upload_product_photos_zip':
                if (empty($_FILES['product_photos_zip']) || !is_array($_FILES['product_photos_zip'])) {
                    throw new RuntimeException('Selecciona un ZIP de imagenes.');
                }

                $size = bsc_theme_importer_upload_product_photos_zip($_FILES['product_photos_zip']);
                return [bsc_theme_importer_make_notice('success', 'ZIP de imagenes cargado correctamente (' . $size . ').')];

            case 'process_product_photos':
                bsc_theme_importer_require_woocommerce();

                $details = bsc_theme_importer_process_product_photos();
                return [bsc_theme_importer_make_notice('success', 'Procesamiento de imagenes finalizado.', $details)];

            default:
                throw new RuntimeException('Accion no reconocida.');
        }
    } catch (Throwable $throwable) {
        return [bsc_theme_importer_make_notice('error', $throwable->getMessage())];
    }
}

function bsc_theme_importer_build_categories_export_data(): array
{
    $product_categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ]);

    if (is_wp_error($product_categories)) {
        throw new RuntimeException($product_categories->get_error_message());
    }

    $product_categories = array_values(array_filter($product_categories, static function ($category): bool {
        return !in_array($category->slug, ['uncategorized', 'sin-categoria'], true);
    }));

    usort($product_categories, static function ($left, $right): int {
        return (int) $left->term_id <=> (int) $right->term_id;
    });

    $category_data = [];

    foreach ($product_categories as $category) {
        $parent_slug = '';

        if ((int) $category->parent > 0) {
            $parent_term = get_term((int) $category->parent, 'product_cat');
            $parent_slug = (!is_wp_error($parent_term) && $parent_term) ? (string) $parent_term->slug : '';
        }

        $row = [
            'SLUG'        => (string) $category->slug,
            'PARENT_SLUG' => $parent_slug,
            'LABEL'       => htmlspecialchars_decode((string) $category->name, ENT_QUOTES),
            'DESCRIPTION' => htmlspecialchars_decode((string) $category->description, ENT_QUOTES),
            'PICTURE'     => (string) get_term_meta((int) $category->term_id, 'picture', true),
        ];

        $how_to_use = get_term_meta((int) $category->term_id, 'bsc__how_to_use', true);
        $rutine_steps = get_term_meta((int) $category->term_id, 'bsc__rutine_steps', true);
        $skin_root = get_term_meta((int) $category->term_id, 'bsc__skin_type_root', true);
        $skin_desc = get_term_meta((int) $category->term_id, 'bsc__skin_type_desc', true);

        if ($how_to_use !== '') {
            $row['BSC__HOW_TO_USE'] = htmlspecialchars_decode((string) $how_to_use, ENT_QUOTES);
        }

        if ($rutine_steps !== '') {
            $row['BSC__RUTINE_STEPS'] = htmlspecialchars_decode((string) $rutine_steps, ENT_QUOTES);
        }

        if ($skin_root !== '' || $skin_desc !== '') {
            $row['BSC__SKIN_TYPE'] = [
                'root' => htmlspecialchars_decode((string) $skin_root, ENT_QUOTES),
                'desc' => htmlspecialchars_decode((string) $skin_desc, ENT_QUOTES),
            ];
        }

        $category_data[] = $row;
    }

    return $category_data;
}

function bsc_theme_importer_handle_download_categories(): void
{
    bsc_theme_importer_require_capability();

    if (
        empty($_POST['bsc_theme_importer_download_categories_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['bsc_theme_importer_download_categories_nonce'])), 'bsc_theme_importer_download_categories')
    ) {
        wp_die(esc_html__('Solicitud no valida.', 'bubblesskincare'));
    }

    try {
        $json = wp_json_encode(bsc_theme_importer_build_categories_export_data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (!is_string($json)) {
            throw new RuntimeException('No se pudo generar el JSON.');
        }

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="product_categories.json"');
        echo $json;
        exit;
    } catch (Throwable $throwable) {
        wp_die(esc_html($throwable->getMessage()));
    }
}

function bsc_theme_importer_render_notice(array $notice): void
{
    $class = $notice['type'] === 'error' ? 'bsc-admin-note--error' : 'bsc-admin-note--success';
    ?>
    <div class="bsc-admin-note <?php echo esc_attr($class); ?> bsc-importer-admin__notice">
        <p><?php echo esc_html($notice['message']); ?></p>
        <?php if (!empty($notice['details'])) : ?>
            <details class="bsc-importer-admin__notice-details">
                <summary><?php esc_html_e('Ver detalle tecnico', 'bubblesskincare'); ?></summary>
                <pre><?php echo esc_html($notice['details']); ?></pre>
            </details>
        <?php endif; ?>
    </div>
    <?php
}

function bsc_theme_importer_render_action_form_start(string $action, string $class = ''): void
{
    ?>
    <form class="bsc-importer-admin__form <?php echo esc_attr($class); ?>" action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="bsc_theme_importer_action" value="<?php echo esc_attr($action); ?>">
        <?php bsc_theme_importer_nonce_field($action); ?>
    <?php
}

function bsc_theme_importer_render_confirmation_checkbox(string $action, string $label): void
{
    ?>
    <label class="bsc-importer-admin__confirm">
        <input type="checkbox" name="bsc_theme_importer_confirm" value="<?php echo esc_attr($action); ?>" required>
        <span><?php echo esc_html($label); ?></span>
    </label>
    <?php
}

function bsc_theme_importer_get_log_excerpt(): string
{
    $log_file = trailingslashit(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) . 'process.log';

    if (!file_exists($log_file) || !is_readable($log_file)) {
        return '';
    }

    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!is_array($lines)) {
        return '';
    }

    return implode("\n", array_slice($lines, -14));
}

function bsc_theme_importer_render_admin_page()
{
    bsc_theme_importer_require_capability();

    $notices = bsc_theme_importer_process_post_action();
    $zip_status = bsc_theme_importer_product_photos_zip_status();
    $log_excerpt = bsc_theme_importer_get_log_excerpt();
    $max_upload = size_format(wp_max_upload_size());
    $woocommerce_ready = bsc_theme_importer_woocommerce_ready();
    $category_count = bsc_theme_importer_count_product_categories();
    $product_count = bsc_theme_importer_count_products();
    $zip_subpath = bsc_theme_importer_upload_subpath($zip_status['path']);
    ?>

    <div class="wrap bsc-importer-admin">
        <header class="bsc-importer-admin__header">
            <div class="bsc-importer-admin__title">
                <h1><?php esc_html_e('Importador BSC', 'bubblesskincare'); ?></h1>
                <span class="bsc-admin-badge bsc-admin-badge--locked"><?php esc_html_e('Theme module', 'bubblesskincare'); ?></span>
            </div>
            <p class="description"><?php esc_html_e('Operaciones masivas para catalogo BSC: categorias, productos e imagenes por SKU.', 'bubblesskincare'); ?></p>
        </header>

        <?php if (function_exists('bsc_theme_importer_is_legacy_plugin_loaded') && bsc_theme_importer_is_legacy_plugin_loaded()) : ?>
            <div class="bsc-admin-note bsc-admin-note--warning bsc-importer-admin__notice">
                <p><?php esc_html_e('El importador BSC ya esta incluido en el theme. Puedes desactivar wp-bsc-plugin-v1 cuando confirmes que no necesitas sus archivos legacy.', 'bubblesskincare'); ?></p>
            </div>
        <?php endif; ?>

        <?php foreach ($notices as $notice) : ?>
            <?php bsc_theme_importer_render_notice($notice); ?>
        <?php endforeach; ?>

        <?php if (!$woocommerce_ready) : ?>
            <div class="bsc-admin-note bsc-admin-note--error bsc-importer-admin__notice">
                <p><?php esc_html_e('WooCommerce no esta disponible. Las importaciones y borrados quedan bloqueados hasta activarlo.', 'bubblesskincare'); ?></p>
            </div>
        <?php endif; ?>

        <section class="bsc-importer-admin__summary" aria-label="<?php echo esc_attr__('Estado del importador', 'bubblesskincare'); ?>">
            <div class="bsc-importer-admin__summary-item">
                <span class="dashicons dashicons-database" aria-hidden="true"></span>
                <div>
                    <strong><?php echo esc_html(number_format_i18n($category_count)); ?></strong>
                    <span><?php esc_html_e('categorias producto', 'bubblesskincare'); ?></span>
                </div>
            </div>
            <div class="bsc-importer-admin__summary-item">
                <span class="dashicons dashicons-products" aria-hidden="true"></span>
                <div>
                    <strong><?php echo esc_html(number_format_i18n($product_count)); ?></strong>
                    <span><?php esc_html_e('productos', 'bubblesskincare'); ?></span>
                </div>
            </div>
            <div class="bsc-importer-admin__summary-item <?php echo $zip_status['exists'] ? 'is-ready' : 'is-pending'; ?>">
                <span class="dashicons <?php echo $zip_status['exists'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
                <div>
                    <strong><?php echo $zip_status['exists'] ? esc_html($zip_status['size']) : esc_html__('Pendiente', 'bubblesskincare'); ?></strong>
                    <span><?php esc_html_e('ZIP fotos', 'bubblesskincare'); ?></span>
                </div>
            </div>
            <div class="bsc-importer-admin__summary-item">
                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                <div>
                    <strong><?php echo esc_html($max_upload); ?></strong>
                    <span><?php esc_html_e('max subida', 'bubblesskincare'); ?></span>
                </div>
            </div>
        </section>

        <div class="bsc-importer-admin__layout">
            <main class="bsc-importer-admin__main">
                <section class="bsc-importer-panel bsc-importer-panel--tasks">
                    <div class="bsc-importer-panel__header">
                        <div>
                            <h2><?php esc_html_e('Flujo de importacion', 'bubblesskincare'); ?></h2>
                            <p><?php esc_html_e('Ejecuta en orden: categorias, productos y fotos. Cada accion usa nonce, permisos admin y validacion de archivo.', 'bubblesskincare'); ?></p>
                        </div>
                    </div>

                    <div class="bsc-importer-task">
                        <div class="bsc-importer-task__index">1</div>
                        <div class="bsc-importer-task__body">
                            <h3><?php esc_html_e('Categorias', 'bubblesskincare'); ?></h3>
                            <p><?php esc_html_e('JSON plano con SLUG, LABEL y metadatos BSC para product_cat.', 'bubblesskincare'); ?></p>
                            <?php bsc_theme_importer_render_action_form_start('upload_categories', 'bsc-importer-admin__form--row'); ?>
                                <label for="upload_categories_json" class="screen-reader-text"><?php esc_html_e('JSON de categorias', 'bubblesskincare'); ?></label>
                                <input type="file" id="upload_categories_json" name="upload_categories_json" accept="application/json,.json" required <?php disabled(!$woocommerce_ready); ?>>
                                <button type="submit" class="button button-primary" <?php disabled(!$woocommerce_ready); ?>><?php esc_html_e('Importar categorias', 'bubblesskincare'); ?></button>
                            </form>
                            <form class="bsc-importer-admin__form bsc-importer-admin__form--row" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                                <input type="hidden" name="action" value="bsc_theme_importer_download_categories">
                                <?php wp_nonce_field('bsc_theme_importer_download_categories', 'bsc_theme_importer_download_categories_nonce'); ?>
                                <button type="submit" class="button"><?php esc_html_e('Exportar categorias JSON', 'bubblesskincare'); ?></button>
                            </form>
                        </div>
                    </div>

                    <div class="bsc-importer-task">
                        <div class="bsc-importer-task__index">2</div>
                        <div class="bsc-importer-task__body">
                            <h3><?php esc_html_e('Productos', 'bubblesskincare'); ?></h3>
                            <p><?php esc_html_e('JSON maestro con grupos sk, hc y mk. Actualiza por SKU BSC y conserva categorias existentes.', 'bubblesskincare'); ?></p>
                            <?php bsc_theme_importer_render_action_form_start('upload_products', 'bsc-importer-admin__form--row'); ?>
                                <label for="upload_products_json" class="screen-reader-text"><?php esc_html_e('JSON de productos', 'bubblesskincare'); ?></label>
                                <input type="file" id="upload_products_json" name="upload_products_json" accept="application/json,.json" required <?php disabled(!$woocommerce_ready); ?>>
                                <button type="submit" class="button button-primary" <?php disabled(!$woocommerce_ready); ?>><?php esc_html_e('Importar productos', 'bubblesskincare'); ?></button>
                            </form>
                        </div>
                    </div>

                    <div class="bsc-importer-task">
                        <div class="bsc-importer-task__index">3</div>
                        <div class="bsc-importer-task__body">
                            <h3><?php esc_html_e('Fotos por SKU', 'bubblesskincare'); ?></h3>
                            <p><?php esc_html_e('ZIP con carpetas SK_*, HC_* o MK_*. Solo se aceptan JPG, PNG y WebP.', 'bubblesskincare'); ?></p>
                            <div class="bsc-importer-admin__zip-status <?php echo $zip_status['exists'] ? 'is-ready' : 'is-missing'; ?>">
                                <span class="dashicons <?php echo $zip_status['exists'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
                                <div>
                                    <strong><?php echo $zip_status['exists'] ? esc_html__('ZIP listo', 'bubblesskincare') : esc_html__('Sin ZIP cargado', 'bubblesskincare'); ?></strong>
                                    <span>
                                        <?php
                                        echo $zip_status['exists']
                                            ? esc_html($zip_status['size'] . ' - ' . $zip_status['modified'])
                                            : esc_html('Destino: ' . $zip_subpath);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="bsc-importer-admin__split-actions">
                                <form class="bsc-importer-admin__form bsc-importer-admin__form--row" action="" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="bsc_theme_importer_action" value="upload_product_photos_zip">
                                    <?php bsc_theme_importer_nonce_field('upload_product_photos_zip'); ?>
                                    <label for="product_photos_zip" class="screen-reader-text"><?php esc_html_e('ZIP de imagenes', 'bubblesskincare'); ?></label>
                                    <input type="file" id="product_photos_zip" name="product_photos_zip" accept="application/zip,.zip" required>
                                    <button type="submit" class="button button-primary"><?php esc_html_e('Subir ZIP', 'bubblesskincare'); ?></button>
                                </form>

                                <?php bsc_theme_importer_render_action_form_start('process_product_photos', 'bsc-importer-admin__form--stacked bsc-importer-admin__form--danger'); ?>
                                    <?php bsc_theme_importer_render_confirmation_checkbox('process_product_photos', __('Entiendo que limpiara fotos BSC anteriores y reasignara imagenes.', 'bubblesskincare')); ?>
                                    <button type="submit" class="button button-secondary" <?php disabled(!$zip_status['exists'] || !$woocommerce_ready); ?> data-confirm="<?php echo esc_attr__('El proceso limpiara imagenes BSC anteriores y reasignara fotos por SKU. Continuar?', 'bubblesskincare'); ?>">
                                        <?php esc_html_e('Procesar fotos', 'bubblesskincare'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <details class="bsc-importer-admin__danger-zone">
                        <summary><?php esc_html_e('Zona de riesgo', 'bubblesskincare'); ?></summary>
                        <div class="bsc-importer-admin__danger-grid">
                            <?php bsc_theme_importer_render_action_form_start('delete_categories', 'bsc-importer-admin__form--stacked bsc-importer-admin__form--danger'); ?>
                                <strong><?php esc_html_e('Borrar categorias', 'bubblesskincare'); ?></strong>
                                <span><?php esc_html_e('Elimina product_cat excepto Uncategorized/Sin categoria.', 'bubblesskincare'); ?></span>
                                <?php bsc_theme_importer_render_confirmation_checkbox('delete_categories', __('Confirmo borrar categorias de producto.', 'bubblesskincare')); ?>
                                <button type="submit" class="button bsc-importer-button--danger" <?php disabled(!$woocommerce_ready); ?> data-confirm="<?php echo esc_attr__('Esto borrara todas las categorias de producto excepto Uncategorized/Sin categoria. Continuar?', 'bubblesskincare'); ?>">
                                    <?php esc_html_e('Borrar categorias', 'bubblesskincare'); ?>
                                </button>
                            </form>

                            <?php bsc_theme_importer_render_action_form_start('delete_products', 'bsc-importer-admin__form--stacked bsc-importer-admin__form--danger'); ?>
                                <strong><?php esc_html_e('Borrar productos', 'bubblesskincare'); ?></strong>
                                <span><?php esc_html_e('Elimina permanentemente todos los productos WooCommerce.', 'bubblesskincare'); ?></span>
                                <?php bsc_theme_importer_render_confirmation_checkbox('delete_products', __('Confirmo borrar todos los productos.', 'bubblesskincare')); ?>
                                <button type="submit" class="button bsc-importer-button--danger" <?php disabled(!$woocommerce_ready); ?> data-confirm="<?php echo esc_attr__('Esto borrara todos los productos de WooCommerce. Continuar?', 'bubblesskincare'); ?>">
                                    <?php esc_html_e('Borrar productos', 'bubblesskincare'); ?>
                                </button>
                            </form>
                        </div>
                    </details>
                </section>

                <?php include_once plugin_dir_path(__FILE__) . 'categories/category-overview.php'; ?>
            </main>

            <aside class="bsc-importer-admin__side">
                <section class="bsc-importer-panel">
                    <div class="bsc-importer-panel__header">
                        <div>
                            <h2><?php esc_html_e('Checklist', 'bubblesskincare'); ?></h2>
                            <p><?php esc_html_e('Validaciones antes de procesar archivos masivos.', 'bubblesskincare'); ?></p>
                        </div>
                    </div>
                    <ul class="bsc-importer-admin__checklist">
                        <li class="<?php echo $woocommerce_ready ? 'is-ok' : 'is-error'; ?>">
                            <span class="dashicons <?php echo $woocommerce_ready ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
                            <?php echo $woocommerce_ready ? esc_html__('WooCommerce activo', 'bubblesskincare') : esc_html__('WooCommerce requerido', 'bubblesskincare'); ?>
                        </li>
                        <li class="<?php echo class_exists('ZipArchive') ? 'is-ok' : 'is-error'; ?>">
                            <span class="dashicons <?php echo class_exists('ZipArchive') ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
                            <?php echo class_exists('ZipArchive') ? esc_html__('ZipArchive activo', 'bubblesskincare') : esc_html__('ZipArchive requerido', 'bubblesskincare'); ?>
                        </li>
                        <li class="<?php echo is_writable(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) || wp_mkdir_p(BSC_Theme_Importer_PPU_Config::getPhotoZipDirectory()) ? 'is-ok' : 'is-error'; ?>">
                            <span class="dashicons dashicons-admin-media" aria-hidden="true"></span>
                            <?php echo esc_html($zip_subpath); ?>
                        </li>
                    </ul>
                </section>

                <?php if ($log_excerpt !== '') : ?>
                    <section class="bsc-importer-panel">
                        <details class="bsc-importer-admin__log">
                            <summary><?php esc_html_e('Ultimos eventos', 'bubblesskincare'); ?></summary>
                            <pre><?php echo esc_html($log_excerpt); ?></pre>
                        </details>
                    </section>
                <?php endif; ?>
            </aside>
        </div>

    </div>
    <?php
}
