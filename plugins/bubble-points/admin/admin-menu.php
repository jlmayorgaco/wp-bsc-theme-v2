<?php
if (!defined( 'ABSPATH' )) {
	exit;
}

/**
 * BSC-021: Bubble Points is registered as a submenu of BSC (bsc-dashboard)
 * in admin/bsc-admin-menu.php. This file only defines the callback functions.
 * The top-level add_menu_page() has been removed to avoid a duplicate menu entry.
 */

/**
 * Router / main screen: shows either the users list
 * or the per-user history if ?user_id= is present.
 */
add_action( 'admin_enqueue_scripts', 'bsc_bp_enqueue_admin_assets' );
function bsc_bp_enqueue_admin_assets(): void {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin page routing.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-bubble-points' !== $page ) {
		return;
	}

	$css_path = get_template_directory() . '/plugins/bubble-points/admin/bsc-bp-admin.css';
	$js_path  = get_template_directory() . '/plugins/bubble-points/admin/bsc-bp-admin.js';
	bsc_enqueue_admin_ui_assets();

	wp_enqueue_style(
		'bsc-bubble-points-admin',
		get_template_directory_uri() . '/plugins/bubble-points/admin/bsc-bp-admin.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
	wp_enqueue_script( 'bsc-bubble-points-admin', get_template_directory_uri() . '/plugins/bubble-points/admin/bsc-bp-admin.js', array(), (string) filemtime( $js_path ), true );
}

function bsc_bp_render_adjust_form( int $user_id, string $name, int $balance, bool $inline = false ): void {
	$prefix  = 'bsc-bp-' . $user_id;
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bsc-bp-adjust-form<?php echo $inline ? ' bsc-bp-adjust-form--inline' : ''; ?>" data-bp-adjust data-client="<?php echo esc_attr( $name ); ?>" data-balance="<?php echo esc_attr( $balance ); ?>">
		<?php if ( $inline ) : ?>
			<input type="hidden" name="adjust_source" value="inline">
		<?php endif; ?>
		<input type="hidden" name="action" value="bsc_bp_manual_adjust">
		<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
		<input type="hidden" name="request_id" value="<?php echo esc_attr( wp_generate_uuid4() ); ?>">
		<input type="hidden" name="op" value="">
		<?php wp_nonce_field( 'bsc_bp_manual_adjust_' . $user_id, '_bsc_bp_nonce' ); ?>
		<div class="bsc-bp-adjust-form__field">
			<label for="<?php echo esc_attr( $prefix . '-amount' ); ?>">Puntos</label>
			<input id="<?php echo esc_attr( $prefix . '-amount' ); ?>" type="number" name="amount" min="1" max="2147483647" step="1" required>
		</div>
		<div class="bsc-bp-adjust-form__field">
			<label for="<?php echo esc_attr( $prefix . '-note' ); ?>">Nota (opcional)</label>
			<input id="<?php echo esc_attr( $prefix . '-note' ); ?>" type="text" name="note">
		</div>
		<div class="bsc-bp-adjust-form__actions">
			<button class="button button-primary" type="button" data-bp-op="add">Sumar puntos</button>
			<button class="button bsc-bp-reduce" type="button" data-bp-op="reduce">Restar puntos</button>
		</div>
		<p class="bsc-bp-adjust-form__error" role="alert" hidden></p>
	</form>
	<?php
}

function bsc_bp_render_admin_notice(): void {
	$status = sanitize_key( wp_unslash( $_GET['bp_msg'] ?? '' ) );
	if ( 'ok' === $status || isset( $_GET['updated'] ) ) {
		echo '<div class="notice notice-success is-dismissible"><p>Ajuste de puntos registrado.</p></div>';
	} elseif ( 'invalid' === $status || 'error' === $status || isset( $_GET['error'] ) ) {
		$error = sanitize_key( wp_unslash( $_GET['bp_err'] ?? $_GET['error'] ?? '' ) );
		$message = 'insufficient_points' === $error ? 'El cliente no tiene saldo suficiente para restar esa cantidad.' : 'No se pudo registrar el ajuste. Revisa los datos e inténtalo nuevamente.';
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}

function bsc_bp_render_admin_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions.', 'bsc' ) );
	}

	echo '<div class="wrap bsc-bp-admin"><header class="bsc-admin-page-header bsc-admin-page-header--compact"><div><h1>Bubble Points</h1><p class="bsc-admin-page-header__description">Consulta saldos y movimientos o registra un ajuste manual.</p></div></header><hr class="wp-header-end">';
	bsc_bp_render_admin_notice();

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin detail routing.
	$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;

	if ($user_id > 0) {
		require_once __DIR__ . '/user-history.php';
		bsc_bp_render_user_history_screen( $user_id );
	} else {
		require_once __DIR__ . '/class-bsc-bp-list-table.php';
		$table = new BSC_BP_List_Table();
		$table->prepare_items();

		echo '<section class="bsc-admin-panel bsc-bp-admin__section"><h2 class="bsc-admin-panel__title">Clientes y saldos</h2>';
		echo '<form method="get" class="bsc-bp-admin__search">';
		// preserve routing params
		foreach (array( 'page', 'post_type' ) as $keep) {
            // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Preserving read-only list-table routing params.
			if (isset( $_GET[ $keep ] )) {
				printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $keep ), esc_attr( sanitize_text_field( wp_unslash( $_GET[ $keep ] ) ) ) );
			}
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
		$table->search_box( 'Buscar clientes', 'bsc-bp' );
		echo '</form>';
		echo '<div class="bsc-admin-table-wrap">';
		$table->display();
		echo '</div></section>';
	}

	?>
	<dialog id="bsc-bp-confirm" class="bsc-bp-dialog" aria-labelledby="bsc-bp-confirm-title" aria-describedby="bsc-bp-confirm-summary">
		<h2 id="bsc-bp-confirm-title">Confirmar ajuste de puntos</h2>
		<p id="bsc-bp-confirm-summary"></p>
		<div class="bsc-bp-adjust-form__actions">
			<button type="button" class="button" data-bp-cancel autofocus>Cancelar</button>
			<button type="button" class="button button-primary" data-bp-confirm>Confirmar ajuste</button>
		</div>
	</dialog>
	</div>
	<?php
}

/** Optional settings stub */
function bsc_bp_render_settings_screen() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions.', 'bsc' ) );
	}
	echo '<div class="wrap"><h1>' . esc_html__( 'Bubble Points – Settings', 'bsc' ) . '</h1>';
	echo '<p>' . esc_html__( 'Settings coming soon (earn rules, expiry, UI toggles).', 'bsc' ) . '</p></div>';
}
