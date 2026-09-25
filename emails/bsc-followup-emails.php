<?php
/**
 * BSC-082: Follow-up email module and account email hooks.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

function bsc_get_followup_email_defaults(): array {
	return array(
		'bsc_followup_emails_enabled'        => 1,
		'bsc_welcome_email_enabled'          => 1,
		'bsc_password_reset_email_enabled'   => 1,
		'bsc_password_changed_email_enabled' => 1,
		'bsc_birthday_email_enabled'         => 1,
		'bsc_inactive_email_enabled'         => 1,
		'bsc_inactive_email_months'          => 5,
		'bsc_repurchase_email_enabled'       => 1,
		'bsc_repurchase_inactivity_days'     => 90,
	);
}

function bsc_get_followup_email_setting( string $key ) {
	$defaults = bsc_get_followup_email_defaults();
	return get_option( $key, $defaults[ $key ] ?? null );
}

function bsc_is_followup_emails_enabled(): bool {
	return (bool) bsc_get_followup_email_setting( 'bsc_followup_emails_enabled' );
}

/**
 * Whether BSC password reset emails should be customized.
 *
 * @return bool
 */
function bsc_is_password_reset_email_enabled(): bool {
	return bsc_is_followup_emails_enabled() && (bool) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' );
}

function bsc_get_email_shop_url(): string {
	$url = '';

	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_page_id = (int) wc_get_page_id( 'shop' );
		if ( $shop_page_id > 0 ) {
			$url = (string) get_permalink( $shop_page_id );
		}
	}

	if ( '' === $url ) {
		$url = home_url( '/shop/' );
	}

	return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $url ) : $url;
}

function bsc_get_email_account_url(): string {
	$url = '';

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = (string) wc_get_page_permalink( 'myaccount' );
	}

	if ( '' === $url ) {
		$url = home_url( '/mi-cuenta/' );
	}

	return function_exists( 'bsc_email_publicize_url' ) ? bsc_email_publicize_url( $url ) : $url;
}

function bsc_schedule_followup_email_jobs(): void {
	$schedule_version = '3';
	if ( get_option( 'bsc_followup_email_schedule_version' ) !== $schedule_version ) {
		wp_clear_scheduled_hook( 'bsc_run_daily_followup_emails' );
		update_option( 'bsc_followup_email_schedule_version', $schedule_version, false );
	}

	if ( ! wp_next_scheduled( 'bsc_run_daily_followup_emails' ) ) {
		$timezone = new DateTimeZone( 'America/Bogota' );
		$now      = new DateTimeImmutable( 'now', $timezone );
		$next_run = $now->setTime( 2, 15 );
		if ( $next_run <= $now ) {
			$next_run = $next_run->modify( '+1 day' );
		}

		wp_schedule_event( $next_run->getTimestamp(), 'daily', 'bsc_run_daily_followup_emails' );
	}
}
add_action( 'init', 'bsc_schedule_followup_email_jobs' );

function bsc_get_followup_paid_statuses(): array {
	return array( 'processing', 'preparing', 'shipped', 'completed' );
}

function bsc_get_followup_contact_key( WC_Order $order ): string {
	$user_id = (int) $order->get_user_id();
	if ( $user_id > 0 ) {
		return 'user:' . $user_id;
	}

	$email = strtolower( trim( (string) $order->get_billing_email() ) );
	if ( $email !== '' ) {
		$user = get_user_by( 'email', $email );
		if ( $user instanceof WP_User ) {
			return 'user:' . $user->ID;
		}

		return 'email:' . md5( $email );
	}

	return 'order:' . $order->get_id();
}

function bsc_get_followup_order_recipient( WC_Order $order ): array {
	$user_id = (int) $order->get_user_id();
	$email   = sanitize_email( (string) $order->get_billing_email() );
	$name    = bsc_email_name_from_order( $order, '' );

	if ( $user_id > 0 ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			if ( $email === '' ) {
				$email = sanitize_email( (string) $user->user_email );
			}
			if ( $name === '' ) {
				$name = bsc_email_name_from_user( $user, '' );
			}
		}
	}

	return array(
		'user_id' => $user_id,
		'email'   => $email,
		'name'    => $name !== '' ? $name : 'amiga',
	);
}

function bsc_get_followup_date_label( int $timestamp ): string {
	return wp_date( 'j \\d\\e F \\d\\e Y', $timestamp, wp_timezone() );
}

/**
 * Return the inactivity threshold used by the recommendation email.
 *
 * @return int
 */
function bsc_get_repurchase_inactivity_days(): int {
	return max( 1, (int) bsc_get_followup_email_setting( 'bsc_repurchase_inactivity_days' ) );
}

/**
 * Return the calendar-month threshold used by the inactivity email.
 *
 * @return int
 */
function bsc_get_inactivity_followup_months(): int {
	return max( 1, (int) bsc_get_followup_email_setting( 'bsc_inactive_email_months' ) );
}

/**
 * Calculate a calendar-month due date while keeping end-of-month purchases valid.
 *
 * @param int $purchase_timestamp Purchase timestamp.
 * @param int $months             Number of calendar months to add.
 * @return int
 */
function bsc_get_inactivity_due_timestamp( int $purchase_timestamp, int $months ): int {
	$purchase_date = ( new DateTimeImmutable( '@' . $purchase_timestamp ) )->setTimezone( wp_timezone() );
	$target_month  = $purchase_date
		->modify( 'first day of this month' )
		->modify( '+' . max( 1, $months ) . ' months' );
	$target_day    = min( (int) $purchase_date->format( 'j' ), (int) $target_month->format( 't' ) );
	$due_date      = $target_month
		->setDate( (int) $target_month->format( 'Y' ), (int) $target_month->format( 'n' ), $target_day )
		->setTime(
			(int) $purchase_date->format( 'G' ),
			(int) $purchase_date->format( 'i' ),
			(int) $purchase_date->format( 's' )
		);

	return $due_date->getTimestamp();
}

require_once __DIR__ . '/bsc-followup-state.php';

function bsc_parse_birthday_month_day( string $raw ): array {
	$raw = trim( $raw );
	if ( $raw === '' ) {
		return array();
	}

	if ( preg_match( '/^(\\d{4})-(\\d{2})-(\\d{2})$/', $raw, $matches ) ) {
		return array(
			'month' => $matches[2],
			'day'   => $matches[3],
		);
	}

	if ( preg_match( '/^(\\d{2})\\/(\\d{2})\\/(\\d{4})$/', $raw, $matches ) ) {
		return array(
			'month' => $matches[2],
			'day'   => $matches[1],
		);
	}

	return array();
}

function bsc_handle_new_customer_welcome_email( int $user_id ): void {
	if ( ! (bool) bsc_get_followup_email_setting( 'bsc_welcome_email_enabled' ) ) {
		return;
	}

	if ( get_user_meta( $user_id, '_bsc_welcome_email_sent_at', true ) ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! ( $user instanceof WP_User ) ) {
		return;
	}

	if ( ! in_array( 'customer', (array) $user->roles, true ) ) {
		return;
	}

	$subject = 'Bienvenida a Bubble Skin Care';
	$sent    = bsc_send_email_from_template(
		(string) $user->user_email,
		$subject,
		'bsc-welcome-email.php',
		array(
			'user'        => $user,
			'account_url' => bsc_get_email_account_url(),
			'shop_url'    => bsc_get_email_shop_url(),
		)
	);

	if ( $sent ) {
		update_user_meta( $user_id, '_bsc_welcome_email_sent_at', current_time( 'mysql' ) );
	}
}
add_action( 'user_register', 'bsc_handle_new_customer_welcome_email', 20 );
add_action( 'woocommerce_created_customer', 'bsc_handle_new_customer_welcome_email', 20 );

/**
 * Customize the native WordPress reset password email with the BSC template.
 *
 * @param array   $defaults   Email defaults.
 * @param string  $key        Password reset key.
 * @param string  $user_login User login.
 * @param WP_User $user_data  User object.
 * @return array
 */
function bsc_customize_password_reset_email( array $defaults, string $key, string $user_login, WP_User $user_data ): array {
	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $defaults;
	}

	$reset_url = network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ),
		'login'
	);

	$defaults['subject'] = 'Recupera tu contraseña — Bubble Skin Care';
	$defaults['message'] = bsc_render_email_template(
		'bsc-password-reset-email.php',
		array(
			'user'      => $user_data,
			'reset_url' => $reset_url,
		)
	);
	$defaults['headers'] = bsc_get_email_headers();

	return $defaults;
}
add_filter( 'retrieve_password_notification_email', 'bsc_customize_password_reset_email', 10, 4 );

/**
 * Customize the native WordPress reset password subject fallback.
 *
 * @param string $title Default title.
 * @return string
 */
function bsc_password_reset_title_fallback( string $title ): string {
	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $title;
	}

	return 'Recupera tu contraseña — Bubble Skin Care';
}
add_filter( 'retrieve_password_title', 'bsc_password_reset_title_fallback', 10, 1 );

/**
 * Customize the native WordPress reset password message fallback.
 *
 * @param string       $message    Default message.
 * @param string       $key        Password reset key.
 * @param string       $user_login User login.
 * @param WP_User|null $user_data  User object.
 * @return string
 */
function bsc_password_reset_message_fallback( string $message, string $key, string $user_login, $user_data ): string {
	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $message;
	}

	$reset_url = network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ),
		'login'
	);

	$name = $user_data instanceof WP_User ? bsc_email_name_from_user( $user_data ) : 'Bubble Lover';

	return "Hola {$name},\n\nUsa este enlace para cambiar tu contraseña:\n{$reset_url}\n\nSi no solicitaste este cambio, ignora este correo.\n";
}
add_filter( 'retrieve_password_message', 'bsc_password_reset_message_fallback', 10, 4 );

/**
 * Shared subject for BSC password reset emails.
 *
 * @return string
 */
function bsc_get_password_reset_email_subject(): string {
	return wp_specialchars_decode( 'Recupera tu contrase&ntilde;a - Bubble Skin Care', ENT_QUOTES );
}

/**
 * Force a clean Spanish subject for the native WordPress reset email.
 *
 * @param array   $defaults   Email defaults.
 * @param string  $key        Password reset key.
 * @param string  $user_login User login.
 * @param WP_User $user_data  User object.
 * @return array
 */
function bsc_force_password_reset_notification_email_subject( array $defaults, string $key, string $user_login, WP_User $user_data ): array {
	unset( $key, $user_login, $user_data );

	if ( bsc_is_password_reset_email_enabled() ) {
		$defaults['subject'] = bsc_get_password_reset_email_subject();
	}

	return $defaults;
}
add_filter( 'retrieve_password_notification_email', 'bsc_force_password_reset_notification_email_subject', 20, 4 );

/**
 * Force a clean Spanish subject for older WordPress reset hooks.
 *
 * @param string $title Default title.
 * @return string
 */
function bsc_force_password_reset_title_fallback( string $title ): string {
	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $title;
	}

	return bsc_get_password_reset_email_subject();
}
add_filter( 'retrieve_password_title', 'bsc_force_password_reset_title_fallback', 20, 1 );

/**
 * Force a clean Spanish plain-text fallback for older WordPress reset hooks.
 *
 * @param string       $message    Default message.
 * @param string       $key        Password reset key.
 * @param string       $user_login User login.
 * @param WP_User|null $user_data  User object.
 * @return string
 */
function bsc_force_password_reset_message_fallback( string $message, string $key, string $user_login, $user_data ): string {
	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $message;
	}

	$reset_url = network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ),
		'login'
	);
	$name      = $user_data instanceof WP_User ? bsc_email_name_from_user( $user_data ) : 'Bubble Lover';
	$password  = wp_specialchars_decode( 'contrase&ntilde;a', ENT_QUOTES );

	return sprintf(
		"Hola %s,\n\nUsa este enlace para cambiar tu %s:\n%s\n\nSi no solicitaste este cambio, ignora este correo.\n",
		wp_strip_all_tags( (string) $name ),
		$password,
		$reset_url
	);
}
add_filter( 'retrieve_password_message', 'bsc_force_password_reset_message_fallback', 20, 4 );

/**
 * Force a Spanish subject for WooCommerce reset password emails.
 *
 * @param string $subject Default subject.
 * @param mixed  $_object Related object.
 * @param mixed  $_email  WooCommerce email object.
 * @return string
 */
function bsc_customize_woocommerce_password_reset_subject( string $subject, $_object = null, $_email = null ): string {
	unset( $_object, $_email );

	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $subject;
	}

	return bsc_get_password_reset_email_subject();
}
add_filter( 'woocommerce_email_subject_customer_reset_password', 'bsc_customize_woocommerce_password_reset_subject', 10, 3 );

/**
 * Force a Spanish heading for WooCommerce reset password emails.
 *
 * @param string $heading Default heading.
 * @param mixed  $_object Related object.
 * @param mixed  $_email  WooCommerce email object.
 * @return string
 */
function bsc_customize_woocommerce_password_reset_heading( string $heading, $_object = null, $_email = null ): string {
	unset( $_object, $_email );

	if ( ! bsc_is_password_reset_email_enabled() ) {
		return $heading;
	}

	return wp_specialchars_decode( 'Recupera tu contrase&ntilde;a', ENT_QUOTES );
}
add_filter( 'woocommerce_email_heading_customer_reset_password', 'bsc_customize_woocommerce_password_reset_heading', 10, 3 );

function bsc_customize_password_changed_email( array $pass_change_email, array $user, array $userdata ): array {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_password_changed_email_enabled' ) ) {
		return $pass_change_email;
	}

	$user_id = (int) ( $user['ID'] ?? $userdata['ID'] ?? 0 );
	if ( $user_id <= 0 ) {
		return $pass_change_email;
	}

	$wp_user = get_user_by( 'id', $user_id );
	if ( ! ( $wp_user instanceof WP_User ) ) {
		return $pass_change_email;
	}

	$message = bsc_render_email_template(
		'bsc-password-changed-email.php',
		array(
			'user'        => $wp_user,
			'account_url' => bsc_get_email_account_url(),
			'reset_url'   => wp_lostpassword_url(),
		)
	);

	if ( '' === $message ) {
		return $pass_change_email;
	}

	$pass_change_email['subject'] = 'Tu contraseña fue actualizada — Bubble Skin Care';
	$pass_change_email['message'] = $message;
	$pass_change_email['headers'] = bsc_get_email_headers();

	return $pass_change_email;
}
add_filter( 'password_change_email', 'bsc_customize_password_changed_email', 10, 3 );

/**
 * Create a unique WooCommerce coupon for a promotional follow-up email.
 *
 * @param array<string,mixed> $args Coupon definition.
 * @return WC_Coupon|WP_Error
 */
function bsc_create_followup_coupon( array $args ) {
	if ( ! class_exists( 'WC_Coupon' ) || ! function_exists( 'wc_format_coupon_code' ) || ! function_exists( 'wc_get_coupon_id_by_code' ) ) {
		return new WP_Error( 'bsc_followup_coupon_api_unavailable', 'WooCommerce coupon APIs are unavailable.' );
	}

	$prefix        = strtoupper( (string) preg_replace( '/[^A-Z0-9]+/', '-', strtoupper( (string) ( $args['prefix'] ?? '' ) ) ) );
	$prefix        = trim( $prefix, '-' );
	$recipient     = sanitize_email( (string) ( $args['email'] ?? '' ) );
	$discount_type = sanitize_key( (string) ( $args['discount_type'] ?? '' ) );
	$amount        = (string) ( $args['amount'] ?? '' );
	$expires_at    = $args['expires_at'] ?? null;
	$usage_limit   = absint( $args['usage_limit'] ?? 0 );
	$per_user      = absint( $args['usage_limit_per_user'] ?? 0 );
	$source        = sanitize_key( (string) ( $args['source'] ?? '' ) );

	if ( '' === $prefix || ! is_email( $recipient ) || ! in_array( $discount_type, array( 'percent', 'fixed_cart', 'fixed_product' ), true ) || ! is_numeric( $amount ) || ! ( $expires_at instanceof DateTimeImmutable ) || $usage_limit < 1 || $per_user < 1 || ! in_array( $source, array( 'birthday', 'inactive' ), true ) ) {
		return new WP_Error( 'bsc_followup_coupon_invalid_definition', 'The follow-up coupon definition is invalid.' );
	}

	$code = '';
	for ( $attempt = 0; $attempt < 5; $attempt++ ) {
		$suffix    = (string) preg_replace( '/[^A-Z0-9]/', '', strtoupper( wp_generate_password( 8, false, false ) ) );
		$candidate = wc_format_coupon_code( sprintf( '%s-%s', $prefix, $suffix ) );

		if ( 8 === strlen( $suffix ) && '' !== $candidate && ! wc_get_coupon_id_by_code( $candidate ) ) {
			$code = $candidate;
			break;
		}
	}

	if ( '' === $code ) {
		return new WP_Error( 'bsc_followup_coupon_code_unavailable', 'A unique follow-up coupon code could not be generated.' );
	}

	$timezone   = new DateTimeZone( 'America/Bogota' );
	$expires_at = $expires_at->setTimezone( $timezone );
	$created_at = new DateTimeImmutable( 'now', $timezone );
	$coupon     = null;

	try {
		$coupon = new WC_Coupon();
		$coupon->set_code( $code );
		$coupon->set_description( sanitize_text_field( (string) ( $args['description'] ?? '' ) ) );
		$coupon->set_discount_type( $discount_type );
		$coupon->set_amount( $amount );
		$coupon->set_individual_use( false );
		$coupon->set_free_shipping( ! empty( $args['free_shipping'] ) );
		$coupon->set_usage_limit( $usage_limit );
		$coupon->set_usage_limit_per_user( $per_user );
		$coupon->set_email_restrictions( array( $recipient ) );
		$coupon->set_date_expires( $expires_at->format( DATE_ATOM ) );
		$coupon->update_meta_data( '_bsc_email_coupon_source', $source );
		$coupon->update_meta_data( '_bsc_email_coupon_created_at', $created_at->format( DATE_ATOM ) );

		$user_id  = absint( $args['user_id'] ?? 0 );
		$order_id = absint( $args['order_id'] ?? 0 );
		if ( $user_id > 0 ) {
			$coupon->update_meta_data( '_bsc_email_coupon_user_id', $user_id );
		}

		if ( $order_id > 0 ) {
			$coupon->update_meta_data( '_bsc_email_coupon_order_id', $order_id );
		}

		$coupon->save();
	} catch ( Throwable $error ) {
		if ( $coupon instanceof WC_Coupon && (int) $coupon->get_id() > 0 ) {
			$coupon->delete( true );
		}

		return new WP_Error( 'bsc_followup_coupon_save_failed', sprintf( 'WooCommerce could not save the follow-up coupon (%s).', get_class( $error ) ) );
	}

	if ( ! $coupon instanceof WC_Coupon || (int) $coupon->get_id() < 1 || '' === (string) $coupon->get_code() ) {
		if ( $coupon instanceof WC_Coupon && (int) $coupon->get_id() > 0 ) {
			$coupon->delete( true );
		}

		return new WP_Error( 'bsc_followup_coupon_save_failed', 'WooCommerce returned an invalid follow-up coupon.' );
	}

	return $coupon;
}

function bsc_process_birthday_followup_emails(): int {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_birthday_email_enabled' ) ) {
		return 0;
	}

	$today        = new DateTimeImmutable( 'now', new DateTimeZone( 'America/Bogota' ) );
	$today_month  = $today->format( 'm' );
	$today_day    = $today->format( 'd' );
	$current_year = $today->format( 'Y' );
	$sent_count   = 0;

	$user_ids = get_users(
		array(
			'fields'      => 'ids',
			'count_total' => false,
			'meta_query'  => array(
				'relation' => 'OR',
				array(
					'key'     => 'bsc_birthday',
					'value'   => '-' . $today_month . '-' . $today_day,
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'bsc_birthday',
					'value'   => $today_day . '/' . $today_month . '/',
					'compare' => 'LIKE',
				),
			),
		)
	);

	foreach ( $user_ids as $user_id ) {
		$birthday = (string) get_user_meta( (int) $user_id, 'bsc_birthday', true );
		$parts    = bsc_parse_birthday_month_day( $birthday );

		if ( empty( $parts ) || $parts['month'] !== $today_month || $parts['day'] !== $today_day ) {
			continue;
		}

		if ( (string) get_user_meta( (int) $user_id, '_bsc_birthday_email_year', true ) === $current_year ) {
			continue;
		}

		$user = get_user_by( 'id', (int) $user_id );
		if ( ! ( $user instanceof WP_User ) ) {
			continue;
		}

		$user_email = sanitize_email( (string) $user->user_email );
		if ( ! is_email( $user_email ) ) {
			continue;
		}

		$coupon = bsc_create_followup_coupon(
			array(
				'prefix'               => 'CUMPLE',
				'email'                => $user_email,
				'discount_type'        => 'percent',
				'amount'               => '10',
				'free_shipping'        => false,
				'expires_at'           => $today->modify( '+7 days' ),
				'usage_limit'          => 1,
				'usage_limit_per_user' => 1,
				'description'          => sprintf( 'Beneficio cumpleaños %s - usuario #%d', $current_year, (int) $user->ID ),
				'source'               => 'birthday',
				'user_id'              => (int) $user->ID,
			)
		);
		if ( is_wp_error( $coupon ) ) {
			error_log( sprintf( 'BSC birthday coupon creation failed for user #%d: %s - %s', (int) $user_id, $coupon->get_error_code(), $coupon->get_error_message() ) );
			continue;
		}

		$coupon_code = (string) $coupon->get_code();
		if ( '' === $coupon_code ) {
			$coupon->delete( true );
			error_log( sprintf( 'BSC birthday coupon creation failed for user #%d: coupon code was empty.', (int) $user_id ) );
			continue;
		}

		$sent = bsc_send_email_from_template(
			$user_email,
			'Feliz cumpleaños de parte de BSC',
			'bsc-birthday-email.php',
			array(
				'user'        => $user,
				'shop_url'    => bsc_get_email_shop_url(),
				'coupon_code' => $coupon_code,
			)
		);

		if ( ! $sent ) {
			$coupon_id = (int) $coupon->get_id();
			if ( $coupon_id > 0 ) {
				$deleted = $coupon->delete( true );
				error_log( sprintf( 'BSC birthday email failed for user #%d; coupon #%d rollback %s.', (int) $user_id, $coupon_id, $deleted ? 'completed' : 'failed' ) );
			}
			continue;
		}

		update_user_meta( (int) $user_id, '_bsc_birthday_email_year', $current_year );
		update_user_meta( (int) $user_id, '_bsc_birthday_coupon_id', (int) $coupon->get_id() );
		update_user_meta( (int) $user_id, '_bsc_birthday_coupon_code', $coupon_code );
		update_user_meta( (int) $user_id, '_bsc_birthday_coupon_year', $current_year );
		++$sent_count;
	}

	return $sent_count;
}

function bsc_process_inactivity_followup_emails(): int {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_inactive_email_enabled' ) ) {
		return 0;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$sent_count = 0;
	$states     = bsc_get_due_followup_states( 'inactive', bsc_get_followup_email_batch_size() );

	foreach ( $states as $state ) {
		$contact_key = (string) ( $state['contact_key'] ?? '' );
		$order       = bsc_get_valid_followup_state_order( $state );
		if ( ! is_object( $order ) ) {
			continue;
		}

		$recipient = bsc_get_followup_order_recipient( $order );
		if ( $recipient['email'] === '' ) {
			bsc_delay_followup_state_retry( $contact_key, 'inactive' );
			continue;
		}

		$now      = new DateTimeImmutable( 'now', new DateTimeZone( 'America/Bogota' ) );
		$order_id = (int) $order->get_id();
		$coupon   = bsc_create_followup_coupon(
			array(
				'prefix'               => 'VUELVE',
				'email'                => $recipient['email'],
				'discount_type'        => 'fixed_cart',
				'amount'               => '0',
				'free_shipping'        => true,
				'expires_at'           => $now->modify( '+1 month' ),
				'usage_limit'          => 1,
				'usage_limit_per_user' => 1,
				'description'          => sprintf( 'Beneficio de reactivación - pedido #%d', $order_id ),
				'source'               => 'inactive',
				'order_id'             => $order_id,
			)
		);
		if ( is_wp_error( $coupon ) ) {
			error_log( sprintf( 'BSC inactivity coupon creation failed for order #%d: %s - %s', $order_id, $coupon->get_error_code(), $coupon->get_error_message() ) );
			bsc_delay_followup_state_retry( $contact_key, 'inactive' );
			continue;
		}

		$coupon_code = (string) $coupon->get_code();
		if ( '' === $coupon_code ) {
			$coupon->delete( true );
			error_log( sprintf( 'BSC inactivity coupon creation failed for order #%d: coupon code was empty.', $order_id ) );
			bsc_delay_followup_state_retry( $contact_key, 'inactive' );
			continue;
		}

		$date_created = $order->get_date_created();

		$sent = bsc_send_email_from_template(
			$recipient['email'],
			'Te extrañamos en Bubbles',
			'bsc-followup-inactive.php',
			array(
				'customer_name'   => $recipient['name'],
				'order'           => $order,
				'last_order_date' => $date_created ? bsc_get_followup_date_label( $date_created->getTimestamp() ) : '',
				'shop_url'        => bsc_get_email_shop_url(),
				'account_url'     => bsc_get_email_account_url(),
				'coupon_code'     => $coupon_code,
			)
		);

		if ( $sent ) {
			try {
				$order->update_meta_data( '_bsc_inactivity_coupon_id', (int) $coupon->get_id() );
				$order->update_meta_data( '_bsc_inactivity_coupon_code', $coupon_code );
				$order->save();
			} catch ( Throwable $error ) {
				error_log( sprintf( 'BSC inactivity coupon metadata could not be saved for order #%d.', $order_id ) );
			}

			bsc_mark_followup_state_sent( $contact_key, 'inactive', $order );
			++$sent_count;
		} else {
			$coupon_id = (int) $coupon->get_id();
			$deleted   = $coupon->delete( true );
			error_log( sprintf( 'BSC inactivity email failed for order #%d; coupon #%d rollback %s.', $order_id, $coupon_id, $deleted ? 'completed' : 'failed' ) );
			bsc_delay_followup_state_retry( $contact_key, 'inactive' );
		}
	}

	return $sent_count;
}

/**
 * Return the distinct product IDs purchased in an order.
 *
 * @param object $order Order to inspect.
 * @return int[]
 */
function bsc_get_order_product_ids( $order ): array {
	$product_ids = array();
	if ( ! is_callable( array( $order, 'get_items' ) ) ) {
		return $product_ids;
	}

	foreach ( call_user_func( array( $order, 'get_items' ), 'line_item' ) as $item ) {
		$product_id = (int) $item->get_product_id();
		if ( $product_id > 0 ) {
			$product_ids[] = $product_id;
		}
	}

	return array_values( array_unique( $product_ids ) );
}

/**
 * Return the product-category slugs shared by a collection of products.
 *
 * @param int[] $product_ids Product IDs to inspect.
 * @return string[]
 */
function bsc_get_product_category_slugs( array $product_ids ): array {
	$category_slugs = array();

	foreach ( array_unique( array_map( 'absint', $product_ids ) ) as $product_id ) {
		$slugs = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $slugs ) ) {
			$category_slugs = array_merge( $category_slugs, $slugs );
		}
	}

	return array_values( array_unique( array_filter( $category_slugs ) ) );
}

/**
 * Check that a recommendation is public, purchasable, and currently in stock.
 *
 * @param WC_Product|null $product     Candidate product.
 * @param int[]           $exclude_ids Product IDs from the source order.
 * @return bool
 */
function bsc_repurchase_recommendation_is_eligible( ?WC_Product $product, array $exclude_ids = array() ): bool {
	if ( ! $product instanceof WC_Product ) {
		return false;
	}

	$product_id = $product->get_id();
	if ( in_array( $product_id, array_map( 'absint', $exclude_ids ), true ) ) {
		return false;
	}

	$is_publicly_listable = function_exists( 'bsc_product_is_publicly_listable' )
		? bsc_product_is_publicly_listable( $product )
		: 'publish' === $product->get_status();

	if ( ! $is_publicly_listable || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return false;
	}

	if ( class_exists( 'BSC_Stock' ) && BSC_Stock::has_dual_stock( $product_id ) ) {
		return BSC_Stock::get_total_stock( $product_id ) > 0;
	}

	return true;
}

/**
 * Build category-based, in-stock recommendations for an order.
 *
 * @param object $order Source order.
 * @param int    $limit Maximum recommendation count.
 * @return array<int, array{id: int, name: string, url: string, image_url: string, button_label: string}>
 */
function bsc_get_repurchase_recommendations_for_order( $order, int $limit = 3 ): array {
	$limit                 = max( 1, $limit );
	$purchased_product_ids = bsc_get_order_product_ids( $order );
	$category_slugs        = bsc_get_product_category_slugs( $purchased_product_ids );

	if ( empty( $purchased_product_ids ) || empty( $category_slugs ) ) {
		return array();
	}

	$candidate_ids = BSC_Growth_Plugin::products(
		array(
			'status'       => 'publish',
			'stock_status' => 'instock',
			'category'     => $category_slugs,
			'exclude'      => $purchased_product_ids,
			'limit'        => max( 24, $limit * 8 ),
			'orderby'      => 'popularity',
			'order'        => 'DESC',
			'return'       => 'ids',
		)
	);

	$ranked_candidates = array();

	foreach ( array_map( 'absint', $candidate_ids ) as $candidate_id ) {
		$product = wc_get_product( $candidate_id );
		if ( ! bsc_repurchase_recommendation_is_eligible( $product, $purchased_product_ids ) ) {
			continue;
		}

		$shared_categories = array_intersect(
			$category_slugs,
			bsc_get_product_category_slugs( array( $candidate_id ) )
		);
		if ( empty( $shared_categories ) ) {
			continue;
		}

		$image_id            = $product->get_image_id();
		$ranked_candidates[] = array(
			'id'           => $candidate_id,
			'name'         => wp_strip_all_tags( $product->get_name() ),
			'url'          => get_permalink( $candidate_id ),
			'image_url'    => $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '',
			'button_label' => 'Ver producto',
			'shared_count' => count( $shared_categories ),
			'total_sales'  => (int) $product->get_total_sales(),
		);
	}

	usort(
		$ranked_candidates,
		static function ( array $left, array $right ): int {
			$shared_comparison = $right['shared_count'] <=> $left['shared_count'];
			if ( 0 !== $shared_comparison ) {
				return $shared_comparison;
			}

			$sales_comparison = $right['total_sales'] <=> $left['total_sales'];
			if ( 0 !== $sales_comparison ) {
				return $sales_comparison;
			}

			return $right['id'] <=> $left['id'];
		}
	);

	$recommendations = array();
	foreach ( array_slice( $ranked_candidates, 0, $limit ) as $ranked_candidate ) {
		unset( $ranked_candidate['shared_count'], $ranked_candidate['total_sales'] );
		$recommendations[] = $ranked_candidate;
	}

	return $recommendations;
}

/**
 * Send one recommendation email when the latest purchase reaches the threshold.
 *
 * @return int Number of emails accepted by wp_mail().
 */
function bsc_process_repurchase_followup_emails(): int {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_repurchase_email_enabled' ) ) {
		return 0;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$days       = bsc_get_repurchase_inactivity_days();
	$sent_count = 0;
	$states     = bsc_get_due_followup_states( 'repurchase', bsc_get_followup_email_batch_size() );

	foreach ( $states as $state ) {
		$contact_key = (string) ( $state['contact_key'] ?? '' );
		$order       = bsc_get_valid_followup_state_order( $state );
		if ( ! is_object( $order ) ) {
			continue;
		}

		$recipient = bsc_get_followup_order_recipient( $order );
		if ( '' === $recipient['email'] ) {
			bsc_delay_followup_state_retry( $contact_key, 'repurchase' );
			continue;
		}

		$recommendations = bsc_get_repurchase_recommendations_for_order( $order, 3 );
		if ( empty( $recommendations ) ) {
			bsc_delay_followup_state_retry( $contact_key, 'repurchase' );
			continue;
		}

		$sent = bsc_send_email_from_template(
			$recipient['email'],
			'Complementa tu rutina coreana',
			'bsc-followup-repurchase.php',
			array(
				'customer_name'   => $recipient['name'],
				'products'        => $recommendations,
				'inactivity_days' => $days,
				'shop_url'        => bsc_get_email_shop_url(),
				'account_url'     => bsc_get_email_account_url(),
				'order'           => $order,
			)
		);

		if ( ! $sent ) {
			bsc_delay_followup_state_retry( $contact_key, 'repurchase' );
			continue;
		}

		bsc_mark_followup_state_sent(
			$contact_key,
			'repurchase',
			$order,
			array_values( array_map( 'absint', wp_list_pluck( $recommendations, 'id' ) ) )
		);
		++$sent_count;
	}

	return $sent_count;
}

function bsc_run_followup_email_jobs(): array {
	$summary = array(
		'ran_at'            => current_time( 'mysql' ),
		'birthday'          => 0,
		'inactive'          => 0,
		'repurchase'        => 0,
		'backfill_orders'   => 0,
		'backfill_complete' => (bool) get_option( 'bsc_followup_state_backfill_complete', false ),
		'locked'            => 0,
		'module_on'         => bsc_is_followup_emails_enabled() ? 1 : 0,
	);

	if ( ! bsc_is_followup_emails_enabled() ) {
		update_option( 'bsc_followup_email_last_run_summary', $summary, false );
		return $summary;
	}

	if ( ! bsc_acquire_followup_state_lock() ) {
		$summary['locked'] = 1;
		update_option( 'bsc_followup_email_last_run_summary', $summary, false );
		return $summary;
	}

	try {
		$summary['birthday'] = bsc_process_birthday_followup_emails();

		$backfill                     = bsc_backfill_followup_state_batch( 100 );
		$summary['backfill_orders']   = $backfill['processed'];
		$summary['backfill_complete'] = $backfill['complete'];
		$summary['repurchase']        = bsc_process_repurchase_followup_emails();
		$summary['inactive']          = bsc_process_inactivity_followup_emails();
	} finally {
		bsc_release_followup_state_lock();
	}

	update_option( 'bsc_followup_email_last_run_summary', $summary, false );

	return $summary;
}
add_action( 'bsc_run_daily_followup_emails', 'bsc_run_followup_email_jobs' );
