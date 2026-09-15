<?php
/**
 * Customer directory and BSC profile editor.
 *
 * This screen intentionally keeps account security controls out of the
 * customer workflow. Passwords and roles are managed through WordPress' own
 * user editor, while this page focuses on the contact and skin-profile data
 * the store collects from customers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_customers_admin_assets' );
add_action( 'admin_init', 'bsc_customers_handle_admin_update' );

/**
 * Enqueue the small, page-specific customer directory stylesheet.
 */
function bsc_enqueue_customers_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-customers' !== $page ) {
		return;
	}

	bsc_enqueue_admin_ui_assets();

	$css_path = get_template_directory() . '/admin/bsc-customers.css';

	wp_enqueue_style(
		'bsc-admin-customers',
		get_template_directory_uri() . '/admin/bsc-customers.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);

	$js_path = get_template_directory() . '/admin/bsc-customers.js';

	wp_enqueue_script(
		'bsc-admin-customers',
		get_template_directory_uri() . '/admin/bsc-customers.js',
		array(),
		file_exists( $js_path ) ? (string) filemtime( $js_path ) : '1',
		true
	);
}

/**
 * Customer records contain personal data, so only site administrators may
 * browse or modify them from the BSC panel.
 */
function bsc_customers_user_can_manage(): bool {
	return current_user_can( 'manage_options' ) && current_user_can( 'list_users' );
}

/**
 * Return the skin type values collected in the customer profile.
 */
function bsc_customers_skin_type_options(): array {
	return array(
		'Grasa'  => 'Grasa',
		'Mixta'  => 'Mixta',
		'Seca'   => 'Seca',
		'Normal' => 'Normal',
	);
}

/**
 * Return the skin sensitivity values collected in the customer profile.
 */
function bsc_customers_sensitivity_options(): array {
	return array(
		'Sensible normal' => 'Sensible normal',
		'Muy sensible'    => 'Muy sensible',
		'No sensible'     => 'No sensible',
	);
}

/**
 * Return helpful examples for the four routine-need fields.
 */
function bsc_customers_needs_placeholders(): array {
	return array(
		'bsc_needs1' => 'Ej. Pigmentación…',
		'bsc_needs2' => 'Ej. Elasticidad…',
		'bsc_needs3' => 'Ej. Exceso de sebo…',
		'bsc_needs4' => 'Ej. Hidratación…',
	);
}

/**
 * Check both the expected format and whether the submitted calendar date is
 * real (for example, prevents 2026-02-31).
 *
 * @param string $birthday Birthday in YYYY-MM-DD format.
 * @return bool Whether the date is blank or valid.
 */
function bsc_customers_is_valid_birthday( string $birthday ): bool {
	if ( '' === $birthday ) {
		return true;
	}

	$timezone = new DateTimeZone( 'America/Bogota' );
	$date     = DateTimeImmutable::createFromFormat( '!Y-m-d', $birthday, $timezone );
	$errors   = DateTimeImmutable::getLastErrors();

	return $date instanceof DateTimeImmutable
		&& ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) )
		&& $date->format( 'Y-m-d' ) === $birthday
		&& $date <= new DateTimeImmutable( 'now', $timezone );
}

/**
 * Build a URL to the customer directory.
 *
 * @param array $args Additional URL arguments.
 * @return string Directory URL.
 */
function bsc_customers_list_url( array $args = array() ): string {
	$base_args = array( 'page' => 'bsc-customers' );

	return add_query_arg( array_merge( $base_args, $args ), admin_url( 'admin.php' ) );
}

/**
 * Build a URL to a single customer record.
 *
 * @param int   $user_id Customer user ID.
 * @param array $args    Additional URL arguments.
 * @return string Editor URL.
 */
function bsc_customers_edit_url( int $user_id, array $args = array() ): string {
	return bsc_customers_list_url(
		array_merge(
			array( 'user_id' => $user_id ),
			$args
		)
	);
}

/**
 * Query by account details plus the first and last name fields. WP_User_Query
 * cannot combine its built-in search with name meta in a single OR condition,
 * so this deliberately uses a prepared, read-only query.
 *
 * @param string $search Search term.
 * @param string $role Role filter.
 * @param int    $current_page Requested results page.
 * @param int    $per_page Results per page.
 * @return array{ids: int[], total: int, page: int, per_page: int, total_pages: int}
 */
function bsc_customers_find_users( string $search, string $role, int $current_page, int $per_page = 20 ): array {
	global $wpdb;

	$current_page = max( 1, $current_page );
	$per_page     = min( 100, max( 1, $per_page ) );
	$joins        = array();
	$where        = array( '1 = 1' );

	if ( '' !== $search ) {
		$joins[] = "LEFT JOIN {$wpdb->usermeta} AS bsc_customer_first_name ON bsc_customer_first_name.user_id = u.ID AND bsc_customer_first_name.meta_key = 'first_name'";
		$joins[] = "LEFT JOIN {$wpdb->usermeta} AS bsc_customer_last_name ON bsc_customer_last_name.user_id = u.ID AND bsc_customer_last_name.meta_key = 'last_name'";

		$like    = '%' . $wpdb->esc_like( $search ) . '%';
		$where[] = $wpdb->prepare(
			'(u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s OR bsc_customer_first_name.meta_value LIKE %s OR bsc_customer_last_name.meta_value LIKE %s)',
			$like,
			$like,
			$like,
			$like,
			$like
		);
	}

	$role_names = wp_roles()->get_names();
	if ( '' !== $role && array_key_exists( $role, $role_names ) ) {
		$capability_key = $wpdb->prefix . 'capabilities';
		$role_like      = '%"' . $wpdb->esc_like( $role ) . '";b:1%';
		$joins[]        = $wpdb->prepare(
			"INNER JOIN {$wpdb->usermeta} AS bsc_customer_roles ON bsc_customer_roles.user_id = u.ID AND bsc_customer_roles.meta_key = %s AND bsc_customer_roles.meta_value LIKE %s",
			$capability_key,
			$role_like
		);
	}

	$from  = "FROM {$wpdb->users} AS u " . implode( ' ', $joins );
	$where = 'WHERE ' . implode( ' AND ', $where );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Search combines account columns and two profile meta values in one OR query; fragments are fixed SQL or prepared values.
	$total = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT u.ID) {$from} {$where}" );

	$total_pages  = max( 1, (int) ceil( $total / $per_page ) );
	$current_page = min( $current_page, $total_pages );
	$offset       = ( $current_page - 1 ) * $per_page;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- See the documented prepared customer-search query below.
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Dynamic fragments are fixed SQL or prepared values.
			"SELECT DISTINCT u.ID {$from} {$where} ORDER BY u.user_registered DESC, u.ID DESC LIMIT %d OFFSET %d",
			$per_page,
			$offset
		)
	);

	return array(
		'ids'         => array_map( 'absint', (array) $ids ),
		'total'       => $total,
		'page'        => $current_page,
		'per_page'    => $per_page,
		'total_pages' => $total_pages,
	);
}

/**
 * Return a translated, human-readable list of a user's roles.
 *
 * @param WP_User $user User being displayed.
 * @return string Role labels.
 */
function bsc_customers_get_user_roles_label( WP_User $user ): string {
	$role_names = wp_roles()->get_names();
	$labels     = array();

	foreach ( (array) $user->roles as $role ) {
		$labels[] = $role_names[ $role ] ?? $role;
	}

	return ! empty( $labels ) ? implode( ', ', $labels ) : 'Sin rol';
}

/**
 * Read the custom BSC profile fields for a customer.
 *
 * @param WP_User $user User being displayed.
 * @return array Customer profile values.
 */
function bsc_customers_get_profile( WP_User $user ): array {
	$needs = array();
	foreach ( array_keys( bsc_customers_needs_placeholders() ) as $meta_key ) {
		$needs[ $meta_key ] = (string) get_user_meta( $user->ID, $meta_key, true );
	}

	return array(
		'birthday'            => (string) get_user_meta( $user->ID, 'bsc_birthday', true ),
		'birthday_email_year' => (string) get_user_meta( $user->ID, '_bsc_birthday_email_year', true ),
		'skin_type'           => (string) get_user_meta( $user->ID, 'bsc_skin_type', true ),
		'sensitivity'         => (string) get_user_meta( $user->ID, 'bsc_sensitivity', true ),
		'needs'               => $needs,
	);
}

/**
 * Count completed custom profile fields for a concise directory indicator.
 *
 * @param array $profile Customer profile values.
 * @return array{filled: int, total: int} Completion counts.
 */
function bsc_customers_profile_completion( array $profile ): array {
	$values = array(
		$profile['birthday'] ?? '',
		$profile['skin_type'] ?? '',
		$profile['sensitivity'] ?? '',
		...array_values( (array) ( $profile['needs'] ?? array() ) ),
	);
	$filled = count( array_filter( $values, static fn( $value ): bool => '' !== trim( (string) $value ) ) );

	return array(
		'filled' => $filled,
		'total'  => count( $values ),
	);
}

/**
 * Format a valid birthday for compact display in the directory.
 *
 * @param string $birthday Birthday in YYYY-MM-DD format.
 * @return string Formatted birthday or an empty-state label.
 */
function bsc_customers_format_birthday( string $birthday ): string {
	if ( ! bsc_customers_is_valid_birthday( $birthday ) || '' === $birthday ) {
		return 'Sin registrar';
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $birthday, new DateTimeZone( 'America/Bogota' ) );

	return $date instanceof DateTimeImmutable ? $date->format( 'd/m/Y' ) : 'Sin registrar';
}

/**
 * Build editor values from the persisted user and profile records.
 *
 * @param WP_User $user User being edited.
 * @param array   $profile BSC profile data.
 * @return array Editor values.
 */
function bsc_customers_get_editor_values( WP_User $user, array $profile ): array {
	$values = array(
		'first_name'      => $user->first_name,
		'last_name'       => $user->last_name,
		'display_name'    => $user->display_name,
		'user_email'      => $user->user_email,
		'bsc_birthday'    => $profile['birthday'] ?? '',
		'bsc_skin_type'   => $profile['skin_type'] ?? '',
		'bsc_sensitivity' => $profile['sensitivity'] ?? '',
	);

	foreach ( array_keys( bsc_customers_needs_placeholders() ) as $meta_key ) {
		$values[ $meta_key ] = $profile['needs'][ $meta_key ] ?? '';
	}

	return $values;
}

/**
 * Keep invalid submissions in memory for this request so the editor can show
 * the error without discarding the administrator's input or persisting PII.
 *
 * @param int    $user_id User being edited.
 * @param string $notice Error notice key.
 * @param array  $values Sanitized submitted values.
 */
function bsc_customers_set_form_error( int $user_id, string $notice, array $values ): void {
	$GLOBALS['bsc_customers_form_error'] = array(
		'user_id' => $user_id,
		'notice'  => $notice,
		'values'  => $values,
	);
}

/**
 * Return the current request's form state for one user.
 *
 * @param int $user_id User being edited.
 * @return array Form state, or an empty array.
 */
function bsc_customers_get_form_error( int $user_id ): array {
	$state = $GLOBALS['bsc_customers_form_error'] ?? array();

	return is_array( $state ) && (int) ( $state['user_id'] ?? 0 ) === $user_id ? $state : array();
}

/**
 * Update optional metadata without retaining empty rows that daily jobs would
 * otherwise need to scan.
 *
 * @param int    $user_id User being edited.
 * @param string $meta_key Metadata key.
 * @param string $value Sanitized value.
 */
function bsc_customers_update_optional_meta( int $user_id, string $meta_key, string $value ): void {
	if ( '' === $value ) {
		delete_user_meta( $user_id, $meta_key );
		return;
	}

	update_user_meta( $user_id, $meta_key, $value );
}

/**
 * Get the current post-redirect-get notice key.
 *
 * @return string Notice key.
 */
function bsc_customers_notice_key(): string {
	$state = $GLOBALS['bsc_customers_form_error'] ?? array();
	if ( is_array( $state ) && isset( $state['notice'] ) ) {
		return sanitize_key( (string) $state['notice'] );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only status set after a validated redirect.
	return isset( $_GET['bsc_notice'] ) ? sanitize_key( wp_unslash( $_GET['bsc_notice'] ) ) : '';
}

/**
 * Return the human-readable message for the current notice key.
 *
 * @return string Notice message.
 */
function bsc_customers_notice_message(): string {
	$notice = bsc_customers_notice_key();

	$messages = array(
		'updated'          => 'Datos del cliente actualizados correctamente.',
		'updated-reset'    => 'Datos actualizados. El correo de cumpleaños puede enviarse nuevamente este año.',
		'invalid-email'    => 'Revisa el correo electrónico antes de guardar.',
		'email-in-use'     => 'Ese correo ya está asociado a otra cuenta.',
		'invalid-birthday' => 'Ingresa una fecha de cumpleaños real que no esté en el futuro.',
		'invalid-profile'  => 'Selecciona valores válidos para el tipo y la sensibilidad de la piel.',
		'update-failed'    => 'No se pudieron guardar los cambios. Inténtalo de nuevo.',
	);

	return $messages[ $notice ] ?? '';
}

/**
 * Whether the current notice indicates that saving was rejected.
 *
 * @return bool Whether the notice is an error.
 */
function bsc_customers_notice_is_error(): bool {
	return in_array(
		bsc_customers_notice_key(),
		array( 'invalid-email', 'email-in-use', 'invalid-birthday', 'invalid-profile', 'update-failed' ),
		true
	);
}

/**
 * Tell the template whether a control is responsible for the current error.
 *
 * @param string $field Form field group.
 * @return bool Whether the field should be marked invalid.
 */
function bsc_customers_field_is_invalid( string $field ): bool {
	$notice = bsc_customers_notice_key();

	if ( 'email' === $field ) {
		return in_array( $notice, array( 'invalid-email', 'email-in-use' ), true );
	}

	if ( 'birthday' === $field ) {
		return 'invalid-birthday' === $notice;
	}

	return 'profile' === $field && 'invalid-profile' === $notice;
}

/**
 * Handles profile writes independently from presentation and redirects back
 * to the same record to prevent accidental duplicate POST submissions.
 */
function bsc_customers_handle_admin_update(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only routing; nonce validation follows for writes.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-customers' !== $page || ! isset( $_POST['bsc_customer_action'] ) ) {
		return;
	}

	if ( ! bsc_customers_user_can_manage() ) {
		wp_die( esc_html__( 'No tienes permisos para administrar clientes.', 'bsc-2-0' ) );
	}

	$action  = sanitize_key( wp_unslash( $_POST['bsc_customer_action'] ) );
	$user_id = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
	$user    = $user_id > 0 ? get_user_by( 'id', $user_id ) : false;

	if ( 'update_customer' !== $action || ! ( $user instanceof WP_User ) ) {
		wp_die( esc_html__( 'Cliente no válido.', 'bsc-2-0' ) );
	}

	if (
		! isset( $_POST['bsc_customer_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field( wp_unslash( $_POST['bsc_customer_nonce'] ) ),
			'bsc_customer_update_' . $user_id
		)
	) {
		wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
	}

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		wp_die( esc_html__( 'No tienes permisos para editar este usuario.', 'bsc-2-0' ) );
	}

	$first_name   = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last_name    = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
	$email_input  = isset( $_POST['user_email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['user_email'] ) ) ) : '';
	$email        = sanitize_email( $email_input );
	$birthday     = isset( $_POST['bsc_birthday'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_birthday'] ) ) : '';
	$skin_type    = isset( $_POST['bsc_skin_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_skin_type'] ) ) : '';
	$sensitivity  = isset( $_POST['bsc_sensitivity'] ) ? sanitize_text_field( wp_unslash( $_POST['bsc_sensitivity'] ) ) : '';
	$values       = array(
		'first_name'      => $first_name,
		'last_name'       => $last_name,
		'display_name'    => $display_name,
		'user_email'      => $email_input,
		'bsc_birthday'    => $birthday,
		'bsc_skin_type'   => $skin_type,
		'bsc_sensitivity' => $sensitivity,
	);

	foreach ( array_keys( bsc_customers_needs_placeholders() ) as $meta_key ) {
		$values[ $meta_key ] = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) : '';
	}

	if ( ! is_email( $email_input ) || $email !== $email_input ) {
		bsc_customers_set_form_error( $user_id, 'invalid-email', $values );
		return;
	}

	if ( ! bsc_customers_is_valid_birthday( $birthday ) ) {
		bsc_customers_set_form_error( $user_id, 'invalid-birthday', $values );
		return;
	}

	if (
		( '' !== $skin_type && ! array_key_exists( $skin_type, bsc_customers_skin_type_options() ) )
		|| ( '' !== $sensitivity && ! array_key_exists( $sensitivity, bsc_customers_sensitivity_options() ) )
	) {
		bsc_customers_set_form_error( $user_id, 'invalid-profile', $values );
		return;
	}

	$existing_email_user = get_user_by( 'email', $email );
	if ( $existing_email_user instanceof WP_User && $existing_email_user->ID !== $user_id ) {
		bsc_customers_set_form_error( $user_id, 'email-in-use', $values );
		return;
	}

	if ( '' === $display_name ) {
		$display_name = trim( $first_name . ' ' . $last_name );
	}
	if ( '' === $display_name ) {
		$display_name = $user->display_name;
	}

	$updated = wp_update_user(
		array(
			'ID'           => $user_id,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => $display_name,
			'user_email'   => $email,
		)
	);

	if ( is_wp_error( $updated ) ) {
		bsc_customers_set_form_error( $user_id, 'update-failed', $values );
		return;
	}

	bsc_customers_update_optional_meta( $user_id, 'bsc_birthday', $birthday );
	bsc_customers_update_optional_meta( $user_id, 'bsc_skin_type', $skin_type );
	bsc_customers_update_optional_meta( $user_id, 'bsc_sensitivity', $sensitivity );

	foreach ( array_keys( bsc_customers_needs_placeholders() ) as $meta_key ) {
		bsc_customers_update_optional_meta( $user_id, $meta_key, $values[ $meta_key ] );
	}

	$reset_birthday_email = isset( $_POST['bsc_reset_birthday_email'] )
		&& (string) get_user_meta( $user_id, '_bsc_birthday_email_year', true ) === current_datetime()->format( 'Y' );

	if ( $reset_birthday_email ) {
		delete_user_meta( $user_id, '_bsc_birthday_email_year' );
	}

	bsc_customers_redirect_after_update( $user_id, $reset_birthday_email ? 'updated-reset' : 'updated' );
}

/**
 * Redirect after a save attempt to prevent duplicate form submissions.
 *
 * @param int    $user_id Customer user ID.
 * @param string $notice Notice key to display.
 */
function bsc_customers_redirect_after_update( int $user_id, string $notice ): void {
	wp_safe_redirect( bsc_customers_edit_url( $user_id, array( 'bsc_notice' => $notice ) ) );
	exit;
}

/**
 * Route the customer screen to the directory or a selected profile editor.
 */
function bsc_render_customers_page(): void {
	if ( ! bsc_customers_user_can_manage() ) {
		wp_die( esc_html__( 'No tienes permisos para administrar clientes.', 'bsc-2-0' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only editor routing parameter.
	$user_id = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;

	if ( $user_id > 0 ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			bsc_render_customer_editor( $user );
			return;
		}

		$GLOBALS['bsc_customers_directory_error'] = 'El usuario solicitado no existe o fue eliminado.';
	}

	bsc_render_customers_directory();
}

/**
 * Render the searchable customer directory.
 */
function bsc_render_customers_directory(): void {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only directory filters.
	$search       = isset( $_GET['customer_query'] ) ? sanitize_text_field( wp_unslash( $_GET['customer_query'] ) ) : '';
	$role         = isset( $_GET['customer_role'] ) ? sanitize_key( wp_unslash( $_GET['customer_role'] ) ) : '';
	$current_page = isset( $_GET['customer_page'] ) ? max( 1, absint( wp_unslash( $_GET['customer_page'] ) ) ) : 1;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	$role_names    = wp_roles()->get_names();
	$role          = array_key_exists( $role, $role_names ) ? $role : '';
	$query_results = bsc_customers_find_users( $search, $role, $current_page );
	$users         = array();

	foreach ( $query_results['ids'] as $user_id ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			$users[] = $user;
		}
	}

	$current_page    = $query_results['page'];
	$total_pages     = $query_results['total_pages'];
	$per_page        = $query_results['per_page'];
	$clear_url       = bsc_customers_list_url();
	$directory_error = (string) ( $GLOBALS['bsc_customers_directory_error'] ?? '' );
	?>
	<div class="wrap bsc-admin-customers">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Clientes</span>
				<h1>Directorio de clientes</h1>
				<p class="bsc-admin-page-header__description">Encuentra una cuenta y actualiza los datos de contacto y el perfil de piel que BSC recoge en “Mi cuenta”.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<span class="bsc-admin-badge bsc-admin-badge--locked">Acceso administrativo</span>
			</div>
		</div>

		<?php if ( '' !== $directory_error ) : ?>
			<div class="bsc-admin-note bsc-customers__notice--error" role="alert" aria-live="assertive"><strong>Error:</strong> <?php echo esc_html( $directory_error ); ?></div>
		<?php endif; ?>

		<form method="get" class="bsc-admin-filter-panel bsc-customers__filters">
			<input type="hidden" name="page" value="bsc-customers">
			<div class="bsc-admin-field bsc-admin-field--grow">
				<label for="customer_query">Buscar cliente</label>
				<input id="customer_query" name="customer_query" type="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Nombre, correo o usuario…" autocomplete="off" spellcheck="false">
			</div>
			<div class="bsc-admin-field">
				<label for="customer_role">Rol</label>
				<select id="customer_role" name="customer_role">
					<option value="">Todos los roles</option>
					<?php foreach ( $role_names as $role_key => $role_label ) : ?>
						<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $role, $role_key ); ?>><?php echo esc_html( translate_user_role( $role_label ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="bsc-admin-filter-panel__actions">
				<button type="submit" class="button button-primary">Buscar</button>
				<a class="button" href="<?php echo esc_url( $clear_url ); ?>">Limpiar</a>
			</div>
		</form>

		<section class="bsc-admin-stat-grid bsc-customers__summary" aria-labelledby="bsc-customers-summary-title">
			<h2 id="bsc-customers-summary-title" class="screen-reader-text">Resumen de resultados</h2>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Resultados</span>
				<strong class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( $query_results['total'] ) ); ?></strong>
				<p class="bsc-admin-stat-card__help">Coinciden con los filtros actuales.</p>
			</div>
			<div class="bsc-admin-stat-card">
				<span class="bsc-admin-stat-card__label">Mostrando</span>
				<strong class="bsc-admin-stat-card__value"><?php echo esc_html( number_format_i18n( count( $users ) ) ); ?></strong>
				<p class="bsc-admin-stat-card__help"><?php echo esc_html( sprintf( '%d cuentas por página para mantener la búsqueda ágil.', $per_page ) ); ?></p>
			</div>
		</section>

		<section class="bsc-admin-panel" aria-labelledby="bsc-customers-results-title">
			<div class="bsc-admin-panel__header">
				<div>
					<h2 id="bsc-customers-results-title" class="bsc-admin-panel__title">Cuentas encontradas</h2>
					<p class="bsc-admin-panel__description">Selecciona “Ver y editar” para abrir la ficha completa de una persona.</p>
				</div>
			</div>

			<?php if ( empty( $users ) ) : ?>
				<p class="bsc-admin-empty-state">No encontramos usuarios con esos filtros.</p>
			<?php else : ?>
				<div class="bsc-admin-table-wrap bsc-admin-table-wrap--flush bsc-admin-table-wrap--spacious">
					<table class="wp-list-table widefat striped bsc-customers__table">
						<thead>
							<tr>
								<th scope="col">Cliente</th>
								<th scope="col">Rol</th>
								<th scope="col">Cumpleaños</th>
								<th scope="col">Perfil BSC</th>
								<th scope="col" class="bsc-customers__actions-column"><span class="screen-reader-text">Acciones</span></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $users as $user ) : ?>
								<?php
								$profile    = bsc_customers_get_profile( $user );
								$completion = bsc_customers_profile_completion( $profile );
								$edit_url   = bsc_customers_edit_url( $user->ID );
								$name       = trim( $user->first_name . ' ' . $user->last_name );
								?>
								<tr>
									<td>
										<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( '' !== $name ? $name : $user->display_name ); ?></a></strong>
										<span class="bsc-customers__email"><?php echo esc_html( $user->user_email ); ?></span>
										<span class="bsc-customers__login">@<?php echo esc_html( $user->user_login ); ?></span>
									</td>
									<td><?php echo esc_html( bsc_customers_get_user_roles_label( $user ) ); ?></td>
									<td><?php echo esc_html( bsc_customers_format_birthday( $profile['birthday'] ) ); ?></td>
									<td>
										<span class="bsc-admin-badge <?php echo esc_attr( $completion['filled'] > 0 ? 'bsc-admin-badge--success' : 'bsc-admin-badge--muted' ); ?>">
											<?php echo esc_html( sprintf( '%1$d de %2$d campos', $completion['filled'], $completion['total'] ) ); ?>
										</span>
									</td>
									<td class="bsc-customers__actions-column"><a href="<?php echo esc_url( $edit_url ); ?>" class="button button-secondary">Ver y editar</a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<?php if ( $total_pages > 1 ) : ?>
				<nav class="bsc-customers__pagination" aria-label="Paginación de clientes">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg(
									'customer_page',
									'%#%',
									bsc_customers_list_url(
										array(
											'customer_query' => $search,
											'customer_role'  => $role,
										)
									)
								),
								'format'    => '',
								'current'   => $current_page,
								'total'     => $total_pages,
								'prev_text' => '← Anterior',
								'next_text' => 'Siguiente →',
							)
						)
					);
					?>
				</nav>
			<?php endif; ?>
		</section>
	</div>
	<?php
}

/**
 * Render the editable BSC profile for one user.
 *
 * @param WP_User $user User being edited.
 */
function bsc_render_customer_editor( WP_User $user ): void {
	$profile               = bsc_customers_get_profile( $user );
	$form_error            = bsc_customers_get_form_error( $user->ID );
	$values                = ! empty( $form_error['values'] ) && is_array( $form_error['values'] )
		? $form_error['values']
		: bsc_customers_get_editor_values( $user, $profile );
	$notice_message        = bsc_customers_notice_message();
	$back_url              = bsc_customers_list_url();
	$native_edit           = get_edit_user_link( $user->ID );
	$name                  = trim( $user->first_name . ' ' . $user->last_name );
	$birthday_now          = new DateTimeImmutable( 'now', new DateTimeZone( 'America/Bogota' ) );
	$current_year          = $birthday_now->format( 'Y' );
	$email_sent            = $profile['birthday_email_year'] === $current_year;
	$has_birthday          = bsc_customers_is_valid_birthday( (string) $values['bsc_birthday'] ) && '' !== $values['bsc_birthday'];
	$email_invalid         = bsc_customers_field_is_invalid( 'email' );
	$birthday_invalid      = bsc_customers_field_is_invalid( 'birthday' );
	$profile_invalid       = bsc_customers_field_is_invalid( 'profile' );
	$email_described_by    = 'bsc-customer-email-help' . ( $email_invalid ? ' bsc-customer-email-error' : '' );
	$birthday_described_by = 'bsc-customer-birthday-help' . ( $birthday_invalid ? ' bsc-customer-birthday-error' : '' );
	$profile_described_by  = 'bsc-customer-profile-help' . ( $profile_invalid ? ' bsc-customer-profile-error' : '' );
	$emails_enabled        = function_exists( 'bsc_is_followup_emails_enabled' )
		&& function_exists( 'bsc_get_followup_email_setting' )
		&& bsc_is_followup_emails_enabled()
		&& (bool) bsc_get_followup_email_setting( 'bsc_birthday_email_enabled' );
	?>
	<div class="wrap bsc-admin-customers bsc-customers--editor">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Clientes / ficha</span>
				<h1><?php echo esc_html( '' !== $name ? $name : $user->display_name ); ?></h1>
				<p class="bsc-admin-page-header__description">Actualiza la información de contacto y el perfil BSC. Los cambios se reflejan también en “Mi cuenta”.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a href="<?php echo esc_url( $back_url ); ?>" class="button">← Directorio</a>
				<?php if ( $native_edit ) : ?>
					<a href="<?php echo esc_url( $native_edit ); ?>" class="button">Opciones avanzadas</a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( '' !== $notice_message ) : ?>
			<div class="bsc-admin-note <?php echo esc_attr( bsc_customers_notice_is_error() ? 'bsc-customers__notice--error' : 'bsc-admin-note--success' ); ?>" role="<?php echo esc_attr( bsc_customers_notice_is_error() ? 'alert' : 'status' ); ?>" aria-live="<?php echo esc_attr( bsc_customers_notice_is_error() ? 'assertive' : 'polite' ); ?>">
				<?php if ( bsc_customers_notice_is_error() ) : ?>
					<strong>Error:</strong>
				<?php endif; ?>
				<?php echo esc_html( $notice_message ); ?>
			</div>
		<?php endif; ?>

		<section class="bsc-customers__identity" aria-labelledby="bsc-customer-identity-title">
			<h2 id="bsc-customer-identity-title" class="screen-reader-text">Resumen de cuenta</h2>
			<span><strong>Usuario:</strong> <?php echo esc_html( $user->user_login ); ?></span>
			<span><strong>Rol:</strong> <?php echo esc_html( bsc_customers_get_user_roles_label( $user ) ); ?></span>
			<span><strong>Registro:</strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ), $user->user_registered ) ); ?></span>
		</section>

		<form id="bsc-customer-editor-form" method="post" class="bsc-customers__form">
			<?php wp_nonce_field( 'bsc_customer_update_' . $user->ID, 'bsc_customer_nonce' ); ?>
			<input type="hidden" name="bsc_customer_action" value="update_customer">
			<input type="hidden" name="customer_id" value="<?php echo esc_attr( (string) $user->ID ); ?>">

			<section class="bsc-admin-panel" aria-labelledby="bsc-customer-account-title">
				<div class="bsc-admin-panel__header">
					<div>
						<h2 id="bsc-customer-account-title" class="bsc-admin-panel__title">Datos de cuenta</h2>
						<p class="bsc-admin-panel__description">El correo es el destino de los correos transaccionales y de seguimiento.</p>
					</div>
				</div>
				<div class="bsc-customers__field-grid">
					<div class="bsc-admin-field">
						<label for="bsc-customer-first-name">Nombres</label>
						<input id="bsc-customer-first-name" name="first_name" type="text" value="<?php echo esc_attr( $values['first_name'] ); ?>" autocomplete="given-name" maxlength="250">
					</div>
					<div class="bsc-admin-field">
						<label for="bsc-customer-last-name">Apellidos</label>
						<input id="bsc-customer-last-name" name="last_name" type="text" value="<?php echo esc_attr( $values['last_name'] ); ?>" autocomplete="family-name" maxlength="250">
					</div>
					<div class="bsc-admin-field">
						<label for="bsc-customer-display-name">Nombre para mostrar</label>
						<input id="bsc-customer-display-name" name="display_name" type="text" value="<?php echo esc_attr( $values['display_name'] ); ?>" autocomplete="off" maxlength="250">
					</div>
					<div class="bsc-admin-field">
						<label for="bsc-customer-email">Correo electrónico</label>
						<input id="bsc-customer-email" name="user_email" type="email" value="<?php echo esc_attr( $values['user_email'] ); ?>" autocomplete="email" maxlength="100" spellcheck="false" aria-describedby="<?php echo esc_attr( $email_described_by ); ?>" aria-invalid="<?php echo esc_attr( $email_invalid ? 'true' : 'false' ); ?>" required>
						<span id="bsc-customer-email-help" class="bsc-customers__field-help">Si lo cambias, WordPress puede notificar la dirección anterior.</span>
						<?php if ( $email_invalid ) : ?>
							<span id="bsc-customer-email-error" class="bsc-customers__field-error"><?php echo esc_html( $notice_message ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<section class="bsc-admin-panel" aria-labelledby="bsc-customer-profile-title">
				<div class="bsc-admin-panel__header">
					<div>
						<h2 id="bsc-customer-profile-title" class="bsc-admin-panel__title">Perfil de piel BSC</h2>
						<p id="bsc-customer-profile-help" class="bsc-admin-panel__description">Estos son los datos que la persona completa desde su perfil. El cumpleaños activa el correo anual si las automatizaciones están habilitadas.</p>
					</div>
				</div>
				<div class="bsc-customers__field-grid">
					<div class="bsc-admin-field">
						<label for="bsc-customer-birthday">Cumpleaños</label>
						<input id="bsc-customer-birthday" name="bsc_birthday" type="date" value="<?php echo esc_attr( $values['bsc_birthday'] ); ?>" max="<?php echo esc_attr( $birthday_now->format( 'Y-m-d' ) ); ?>" autocomplete="bday" aria-describedby="<?php echo esc_attr( $birthday_described_by ); ?>" aria-invalid="<?php echo esc_attr( $birthday_invalid ? 'true' : 'false' ); ?>">
						<span id="bsc-customer-birthday-help" class="bsc-customers__field-help">Para probar mañana, usa el día y mes de mañana con un año de nacimiento real.</span>
						<?php if ( $birthday_invalid ) : ?>
							<span id="bsc-customer-birthday-error" class="bsc-customers__field-error"><?php echo esc_html( $notice_message ); ?></span>
						<?php endif; ?>
					</div>
					<div class="bsc-admin-field">
						<label for="bsc-customer-skin-type">Tipo de piel</label>
						<select id="bsc-customer-skin-type" name="bsc_skin_type" aria-invalid="<?php echo esc_attr( $profile_invalid ? 'true' : 'false' ); ?>" aria-describedby="<?php echo esc_attr( $profile_described_by ); ?>">
							<option value="">Sin registrar</option>
							<?php foreach ( bsc_customers_skin_type_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['bsc_skin_type'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="bsc-admin-field">
						<label for="bsc-customer-sensitivity">Sensibilidad</label>
						<select id="bsc-customer-sensitivity" name="bsc_sensitivity" aria-invalid="<?php echo esc_attr( $profile_invalid ? 'true' : 'false' ); ?>" aria-describedby="<?php echo esc_attr( $profile_described_by ); ?>">
							<option value="">Sin registrar</option>
							<?php foreach ( bsc_customers_sensitivity_options() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['bsc_sensitivity'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if ( $profile_invalid ) : ?>
							<span id="bsc-customer-profile-error" class="bsc-customers__field-error"><?php echo esc_html( $notice_message ); ?></span>
						<?php endif; ?>
					</div>
					<div class="bsc-customers__birthday-status">
						<span class="bsc-admin-badge <?php echo esc_attr( $email_sent ? 'bsc-admin-badge--success' : 'bsc-admin-badge--muted' ); ?>">
							<?php
							if ( $email_sent ) {
								echo esc_html( sprintf( 'Correo enviado en %s', $current_year ) );
							} elseif ( $has_birthday ) {
								echo 'Pendiente este año';
							} else {
								echo 'Sin fecha programada';
							}
							?>
						</span>
						<p>Programada cada día a las 2:15 a. m. (Bogotá). WP-Cron la ejecuta en la primera visita disponible después de esa hora. <?php echo esc_html( $emails_enabled ? 'La automatización está activa.' : 'La automatización está desactivada.' ); ?></p>
						<?php if ( $email_sent ) : ?>
							<label class="bsc-customers__reset-option">
								<input type="checkbox" name="bsc_reset_birthday_email" value="1">
								<span><strong>Permitir otro envío en <?php echo esc_html( $current_year ); ?></strong> Úsalo solo para una prueba controlada: puede generar un segundo correo y beneficio de cumpleaños.</span>
							</label>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<section class="bsc-admin-panel" aria-labelledby="bsc-customer-needs-title">
				<div class="bsc-admin-panel__header">
					<div>
						<h2 id="bsc-customer-needs-title" class="bsc-admin-panel__title">Necesidades de la rutina</h2>
						<p class="bsc-admin-panel__description">Registra hasta cuatro objetivos expresados por el cliente. Déjalos vacíos si no aplica.</p>
					</div>
				</div>
				<div class="bsc-customers__field-grid bsc-customers__field-grid--needs">
					<?php foreach ( bsc_customers_needs_placeholders() as $meta_key => $placeholder ) : ?>
						<div class="bsc-admin-field">
							<label for="<?php echo esc_attr( 'bsc-customer-' . $meta_key ); ?>"><?php echo esc_html( sprintf( 'Necesidad %d', (int) substr( $meta_key, -1 ) ) ); ?></label>
							<input id="<?php echo esc_attr( 'bsc-customer-' . $meta_key ); ?>" name="<?php echo esc_attr( $meta_key ); ?>" type="text" value="<?php echo esc_attr( $values[ $meta_key ] ?? '' ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" maxlength="160">
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<div class="bsc-admin-sticky-submit">
				<p class="bsc-customers__save-help">Los campos se validan antes de guardar. Las contraseñas y los roles se gestionan en “Opciones avanzadas”.</p>
				<button type="submit" class="button button-primary" data-saving-label="Guardando…">Guardar datos del cliente</button>
			</div>
		</form>
	</div>
	<?php
}
