<?php
/**
 * BSC-082: Admin page for customer email flows and follow-up settings.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_enqueue_scripts', 'bsc_enqueue_followup_admin_assets' );
function bsc_enqueue_followup_admin_assets(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin routing parameter.
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'bsc-followup-emails' !== $page ) {
		return;
	}

	$css_path = get_template_directory() . '/admin/bsc-admin-communications.css';

	bsc_enqueue_admin_ui_assets();

	wp_enqueue_style(
		'bsc-admin-communications',
		get_template_directory_uri() . '/admin/bsc-admin-communications.css',
		array( 'bsc-admin-ui' ),
		file_exists( $css_path ) ? (string) filemtime( $css_path ) : '1'
	);
}

function bsc_get_preview_email_manifest_row( string $slug ): array {
	foreach ( bsc_get_email_template_manifest() as $row ) {
		if ( (string) ( $row['slug'] ?? '' ) === $slug ) {
			return $row;
		}
	}

	return array();
}

function bsc_email_debug_is_secret_key( string $key ): bool {
	return (bool) preg_match( '/(password|passwd|pass|pwd|secret|token|api[_-]?key|auth[_-]?key|private[_-]?key)/i', $key );
}

function bsc_email_debug_mask_secret( string $secret ): string {
	$length = strlen( $secret );

	if ( $length <= 0 ) {
		return '(empty)';
	}

	if ( $length <= 4 ) {
		return str_repeat( '*', $length );
	}

	return substr( $secret, 0, 2 ) . str_repeat( '*', min( 12, max( 4, $length - 4 ) ) ) . substr( $secret, -2 );
}

function bsc_email_debug_secret_summary( string $secret, string $source = '' ): array {
	$present = $secret !== '';

	return array(
		'present'      => $present,
		'source'       => $source !== '' ? $source : ( $present ? 'unknown' : 'none' ),
		'length'       => strlen( $secret ),
		'masked'       => $present ? bsc_email_debug_mask_secret( $secret ) : '(not configured)',
		'sha256_12'    => $present ? substr( hash( 'sha256', $secret ), 0, 12 ) : '',
		'raw_excluded' => 'Password is intentionally not logged in clear text.',
	);
}

function bsc_get_bsc_smtp_password_source(): string {
	if ( (string) get_option( 'bsc_smtp_password', '' ) !== '' ) {
		return 'option:bsc_smtp_password';
	}

	if ( defined( 'BSC_SMTP_PASSWORD' ) && (string) BSC_SMTP_PASSWORD !== '' ) {
		return 'constant:BSC_SMTP_PASSWORD';
	}

	return 'none';
}

function bsc_get_bsc_smtp_value_source( string $option_name, string $constant_name = '' ): string {
	$value = get_option( $option_name, null );

	if ( ( is_string( $value ) && $value !== '' ) || is_numeric( $value ) ) {
		return 'option:' . $option_name;
	}

	if ( $constant_name !== '' && defined( $constant_name ) && (string) constant( $constant_name ) !== '' ) {
		return 'constant:' . $constant_name;
	}

	return 'default/empty';
}

function bsc_get_known_email_debug_secrets(): array {
	$secrets = array();

	if ( function_exists( 'bsc_get_smtp_password' ) ) {
		$secrets[] = bsc_get_smtp_password();
	}

	foreach ( array( 'BSC_SMTP_PASSWORD', 'WPMS_SMTP_PASS', 'WPMS_SMTP_PASSWORD', 'POST_SMTP_AUTH_PASSWORD' ) as $constant_name ) {
		if ( defined( $constant_name ) ) {
			$secrets[] = (string) constant( $constant_name );
		}
	}

	$secrets = array_filter(
		array_map( 'strval', $secrets ),
		static fn( string $secret ): bool => strlen( $secret ) > 3
	);

	return array_values( array_unique( $secrets ) );
}

function bsc_email_debug_sanitize_string( string $value, string $key_hint = '' ): string {
	foreach ( bsc_get_known_email_debug_secrets() as $secret ) {
		$value = str_replace( $secret, '[redacted-secret]', $value );
		$value = str_replace( base64_encode( $secret ), '[redacted-secret-base64]', $value );
	}

	$value = wp_strip_all_tags( $value );
	$limit = false !== stripos( $key_hint, 'debug' ) ? 40000 : 2500;

	if ( strlen( $value ) > $limit ) {
		$value = substr( $value, 0, $limit ) . '... [truncated]';
	}

	return $value;
}

function bsc_email_debug_sanitize_value( $value, string $key_hint = '' ) {
	if ( bsc_email_debug_is_secret_key( $key_hint ) ) {
		if ( is_array( $value ) && ( array_key_exists( 'raw_excluded', $value ) || array_key_exists( 'sha256_12', $value ) ) ) {
			return array(
				'present'      => ! empty( $value['present'] ),
				'source'       => bsc_email_debug_sanitize_string( (string) ( $value['source'] ?? '' ), $key_hint . '.source' ),
				'length'       => (int) ( $value['length'] ?? 0 ),
				'masked'       => bsc_email_debug_sanitize_string( (string) ( $value['masked'] ?? '' ), $key_hint . '.masked' ),
				'sha256_12'    => bsc_email_debug_sanitize_string( (string) ( $value['sha256_12'] ?? '' ), $key_hint . '.sha256_12' ),
				'raw_excluded' => 'Password is intentionally not logged in clear text.',
			);
		}

		return bsc_email_debug_secret_summary( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
	}

	if ( $value instanceof WP_Error ) {
		return array(
			'codes'    => $value->get_error_codes(),
			'messages' => $value->get_error_messages(),
			'data'     => bsc_email_debug_sanitize_value(
				method_exists( $value, 'get_all_error_data' ) ? $value->get_all_error_data() : $value->get_error_data(),
				$key_hint . '.wp_error_data'
			),
		);
	}

	if ( is_array( $value ) ) {
		$sanitized = array();
		$count     = 0;

		foreach ( $value as $key => $item ) {
			if ( $count >= 80 ) {
				$sanitized['__truncated__'] = 'Only first 80 entries logged.';
				break;
			}

			$key_string               = is_scalar( $key ) ? (string) $key : 'key';
			$sanitized[ $key_string ] = bsc_email_debug_sanitize_value( $item, $key_hint . '.' . $key_string );
			++$count;
		}

		return $sanitized;
	}

	if ( is_object( $value ) ) {
		return array(
			'class' => get_class( $value ),
		);
	}

	if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
		return $value;
	}

	return bsc_email_debug_sanitize_string( (string) $value, $key_hint );
}

function bsc_email_debug_sanitize_smtp_line( string $line, int $level = 0 ): string {
	$line = bsc_email_debug_sanitize_string( $line, 'smtp_debug' );
	$line = preg_replace( '/(AUTH\s+(?:PLAIN|LOGIN)\s+).*/i', '$1[redacted]', $line );

	return sprintf( '[%d] %s', $level, trim( (string) $line ) );
}

function bsc_get_smtp_settings_debug(): array {
	$settings = function_exists( 'bsc_get_smtp_settings' ) ? bsc_get_smtp_settings() : array();
	$password = (string) ( $settings['password'] ?? '' );
	unset( $settings['password'] );

	$settings['sources'] = array(
		'provider' => bsc_get_bsc_smtp_value_source( 'bsc_smtp_provider', 'BSC_SMTP_PROVIDER' ),
		'enabled'  => bsc_get_bsc_smtp_value_source( 'bsc_smtp_enabled' ),
		'host'     => bsc_get_bsc_smtp_value_source( 'bsc_smtp_host', 'BSC_SMTP_HOST' ),
		'port'     => bsc_get_bsc_smtp_value_source( 'bsc_smtp_port', 'BSC_SMTP_PORT' ),
		'secure'   => bsc_get_bsc_smtp_value_source( 'bsc_smtp_secure', 'BSC_SMTP_SECURE' ),
		'auth'     => bsc_get_bsc_smtp_value_source( 'bsc_smtp_auth', 'BSC_SMTP_AUTH' ),
		'username' => bsc_get_bsc_smtp_value_source( 'bsc_smtp_username', 'BSC_SMTP_USERNAME' ),
		'password' => bsc_get_bsc_smtp_password_source(),
	);

	$settings['password'] = bsc_email_debug_secret_summary( $password, $settings['sources']['password'] );

	return $settings;
}

function bsc_identify_phpmailer_password_source( string $password ): string {
	if ( $password === '' ) {
		return 'none';
	}

	$option_password = (string) get_option( 'bsc_smtp_password', '' );
	if ( $option_password !== '' && hash_equals( $option_password, $password ) ) {
		return 'option:bsc_smtp_password';
	}

	if ( defined( 'BSC_SMTP_PASSWORD' ) && hash_equals( (string) BSC_SMTP_PASSWORD, $password ) ) {
		return 'constant:BSC_SMTP_PASSWORD';
	}

	return 'external hook/plugin or runtime override';
}

function bsc_get_smtp_credential_report( string $runtime_password = '' ): array {
	$option_password     = (string) get_option( 'bsc_smtp_password', '' );
	$constant_password   = defined( 'BSC_SMTP_PASSWORD' ) ? (string) BSC_SMTP_PASSWORD : '';
	$configured_password = function_exists( 'bsc_get_smtp_password' ) ? bsc_get_smtp_password() : '';

	return array(
		'configured'                 => bsc_email_debug_secret_summary( $configured_password, bsc_get_bsc_smtp_password_source() ),
		'option'                     => bsc_email_debug_secret_summary( $option_password, 'option:bsc_smtp_password' ),
		'constant'                   => bsc_email_debug_secret_summary( $constant_password, defined( 'BSC_SMTP_PASSWORD' ) ? 'constant:BSC_SMTP_PASSWORD' : 'constant:BSC_SMTP_PASSWORD not defined' ),
		'runtime'                    => bsc_email_debug_secret_summary( $runtime_password, bsc_identify_phpmailer_password_source( $runtime_password ) ),
		'runtime_available'          => $runtime_password !== '',
		'runtime_matches_configured' => $runtime_password !== '' && $configured_password !== '' && hash_equals( $configured_password, $runtime_password ),
		'runtime_matches_option'     => $runtime_password !== '' && $option_password !== '' && hash_equals( $option_password, $runtime_password ),
		'runtime_matches_constant'   => $runtime_password !== '' && $constant_password !== '' && hash_equals( $constant_password, $runtime_password ),
		'raw_excluded'               => 'Raw SMTP passwords are not printed. Use source, length, mask and sha256_12 to confirm the saved credential and runtime credential match.',
	);
}

function bsc_get_phpmailer_debug_snapshot( $phpmailer ): array {
	if ( ! is_object( $phpmailer ) ) {
		return array( 'error' => 'PHPMailer object not available.' );
	}

	$password = isset( $phpmailer->Password ) ? (string) $phpmailer->Password : '';
	$snapshot = array(
		'class'          => get_class( $phpmailer ),
		'mailer'         => isset( $phpmailer->Mailer ) ? (string) $phpmailer->Mailer : '',
		'host'           => isset( $phpmailer->Host ) ? (string) $phpmailer->Host : '',
		'port'           => isset( $phpmailer->Port ) ? (int) $phpmailer->Port : 0,
		'smtp_auth'      => isset( $phpmailer->SMTPAuth ) ? (bool) $phpmailer->SMTPAuth : null,
		'smtp_secure'    => isset( $phpmailer->SMTPSecure ) ? (string) $phpmailer->SMTPSecure : '',
		'username'       => isset( $phpmailer->Username ) ? (string) $phpmailer->Username : '',
		'password'       => bsc_email_debug_secret_summary( $password, bsc_identify_phpmailer_password_source( $password ) ),
		'smtp_credential_report' => bsc_get_smtp_credential_report( $password ),
		'from'           => isset( $phpmailer->From ) ? (string) $phpmailer->From : '',
		'from_name'      => isset( $phpmailer->FromName ) ? (string) $phpmailer->FromName : '',
		'sender'         => isset( $phpmailer->Sender ) ? (string) $phpmailer->Sender : '',
		'charset'        => isset( $phpmailer->CharSet ) ? (string) $phpmailer->CharSet : '',
		'timeout'        => isset( $phpmailer->Timeout ) ? (int) $phpmailer->Timeout : null,
		'smtp_auto_tls'  => isset( $phpmailer->SMTPAutoTLS ) ? (bool) $phpmailer->SMTPAutoTLS : null,
		'smtp_keepalive' => isset( $phpmailer->SMTPKeepAlive ) ? (bool) $phpmailer->SMTPKeepAlive : null,
	);

	foreach ( array( 'getToAddresses' => 'to', 'getCcAddresses' => 'cc', 'getBccAddresses' => 'bcc', 'getReplyToAddresses' => 'reply_to' ) as $method => $key ) {
		if ( method_exists( $phpmailer, $method ) ) {
			$snapshot[ $key ] = bsc_email_debug_sanitize_value( $phpmailer->{$method}(), $key );
		}
	}

	if ( isset( $phpmailer->SMTPOptions ) ) {
		$snapshot['smtp_options'] = bsc_email_debug_sanitize_value( $phpmailer->SMTPOptions, 'smtp_options' );
	}

	return $snapshot;
}

function bsc_describe_wp_hook_callback( $callback ): string {
	if ( is_string( $callback ) ) {
		return $callback;
	}

	if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
		$target = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
		return $target . '::' . (string) $callback[1];
	}

	if ( $callback instanceof Closure ) {
		return 'Closure';
	}

	if ( is_object( $callback ) ) {
		return get_class( $callback ) . '::__invoke';
	}

	return 'unknown callback';
}

function bsc_get_phpmailer_hook_debug(): array {
	global $wp_filter;

	$callbacks = array();
	if ( empty( $wp_filter['phpmailer_init'] ) || ! isset( $wp_filter['phpmailer_init']->callbacks ) ) {
		return $callbacks;
	}

	foreach ( (array) $wp_filter['phpmailer_init']->callbacks as $priority => $items ) {
		foreach ( (array) $items as $item ) {
			$callbacks[] = array(
				'priority' => (int) $priority,
				'callback' => bsc_describe_wp_hook_callback( $item['function'] ?? null ),
			);
		}
	}

	return $callbacks;
}

function bsc_get_active_mail_plugins_debug(): array {
	$active_plugins = array_merge(
		(array) get_option( 'active_plugins', array() ),
		array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) )
	);
	$active_plugins = array_values( array_unique( $active_plugins ) );
	$mail_plugins   = array();

	if ( ! function_exists( 'get_plugins' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_data = function_exists( 'get_plugins' ) ? get_plugins() : array();

	foreach ( $active_plugins as $plugin ) {
		$plugin_lc = strtolower( (string) $plugin );
		$name      = (string) ( $plugin_data[ $plugin ]['Name'] ?? $plugin );
		$name_lc   = strtolower( $name );

		if ( ! preg_match( '/(smtp|mail|postman|sendgrid|mailgun|ses|brevo|sendinblue|zoho)/', $plugin_lc . ' ' . $name_lc ) ) {
			continue;
		}

		$mail_plugins[] = array(
			'plugin'  => $plugin,
			'name'    => $name,
			'version' => (string) ( $plugin_data[ $plugin ]['Version'] ?? '' ),
		);
	}

	return $mail_plugins;
}

function bsc_get_mail_plugin_options_debug(): array {
	$option_names = array(
		'wp_mail_smtp',
		'wp_mail_smtp_debug',
		'postman_options',
		'post_smtp_settings',
		'smtp_mailer_options',
		'swpsmtp_options',
		'easy_wp_smtp',
		'fluentmail-settings',
		'mail_bank',
	);
	$options      = array();

	foreach ( $option_names as $option_name ) {
		$value = get_option( $option_name, null );
		if ( null === $value || false === $value || '' === $value ) {
			continue;
		}

		$options[ $option_name ] = bsc_email_debug_sanitize_value( $value, $option_name );
	}

	return $options;
}

function bsc_get_email_server_debug(): array {
	global $wp_version;

	$server_keys = array( 'HTTP_HOST', 'SERVER_NAME', 'SERVER_SOFTWARE', 'REMOTE_ADDR' );
	$server      = array();

	foreach ( $server_keys as $key ) {
		if ( isset( $_SERVER[ $key ] ) ) {
			$server[ $key ] = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
		}
	}

	return array(
		'time'                => current_time( 'mysql' ),
		'timezone'            => wp_timezone_string(),
		'home_url'            => home_url( '/' ),
		'site_url'            => site_url( '/' ),
		'is_ssl'              => is_ssl(),
		'wp_version'          => (string) $wp_version,
		'wp_environment_type' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '',
		'php_version'         => PHP_VERSION,
		'openssl_loaded'      => extension_loaded( 'openssl' ),
		'server'              => $server,
	);
}

function bsc_get_email_diagnostic_warnings( array $smtp_settings ): array {
	$warnings = array();
	$provider = (string) ( $smtp_settings['provider'] ?? 'custom' );
	$host     = strtolower( (string) ( $smtp_settings['host'] ?? '' ) );
	$port     = (int) ( $smtp_settings['port'] ?? 0 );
	$secure   = strtolower( (string) ( $smtp_settings['secure'] ?? '' ) );
	$username = (string) ( $smtp_settings['username'] ?? '' );
	$from     = function_exists( 'bsc_get_email_from_address' ) ? bsc_get_email_from_address() : '';
	$is_zeptomail = 'zeptomail' === $provider || false !== strpos( $host, 'zeptomail' );

	if ( empty( $smtp_settings['enabled'] ) ) {
		$warnings[] = 'SMTP BSC esta desactivado. WordPress usara mail() o un plugin externo si existe.';
	}

	if ( ! empty( $smtp_settings['enabled'] ) && $host === '' ) {
		$warnings[] = 'SMTP BSC esta activo pero el servidor SMTP esta vacio.';
	}

	if ( ! empty( $smtp_settings['auth'] ) && $username === '' ) {
		$warnings[] = 'SMTP Auth esta activo pero el usuario SMTP esta vacio.';
	}

	if ( ! empty( $smtp_settings['auth'] ) && empty( $smtp_settings['password']['present'] ) ) {
		$warnings[] = 'SMTP Auth esta activo pero no hay password SMTP guardado/definido.';
	}

	if ( false !== strpos( $host, 'zoho' ) && ! in_array( $port . '/' . $secure, array( '465/ssl', '587/tls' ), true ) ) {
		$warnings[] = 'Zoho normalmente usa 465/SSL o 587/TLS. Revisa puerto y cifrado.';
	}

	if ( $is_zeptomail && '587/tls' !== $port . '/' . $secure ) {
		$warnings[] = 'ZeptoMail SMTP usa smtp.zeptomail.com con puerto 587 y TLS.';
	}

	if ( $is_zeptomail && strtolower( $username ) !== 'emailapikey' ) {
		$warnings[] = 'ZeptoMail SMTP normalmente usa emailapikey como usuario SMTP.';
	}

	if ( $host !== '' && ( 'ssl' === $secure || 'tls' === $secure ) && ! extension_loaded( 'openssl' ) ) {
		$warnings[] = 'PHP no tiene OpenSSL cargado; SMTP seguro puede fallar.';
	}

	if ( ! $is_zeptomail && false !== strpos( $username, '@' ) && $from !== '' && strtolower( $username ) !== strtolower( $from ) ) {
		$warnings[] = 'El remitente From no coincide con el usuario SMTP. Zoho puede rechazar remitentes no autorizados.';
		$warnings[] = sprintf( 'FROM_SMTP_MISMATCH: from=%s smtp_user=%s', $from, $username );
	}

	if ( false !== strpos( $from, '@' ) && false !== strpos( $username, '@' ) ) {
		$from_domain     = strtolower( substr( strrchr( $from, '@' ), 1 ) );
		$username_domain = strtolower( substr( strrchr( $username, '@' ), 1 ) );

		if ( $from_domain !== '' && $username_domain !== '' && $from_domain !== $username_domain ) {
			$warnings[] = sprintf( 'FROM_SMTP_DOMAIN_MISMATCH: from_domain=%s smtp_domain=%s', $from_domain, $username_domain );
		}
	}

	return $warnings;
}

function bsc_get_email_transport_diagnostics(): array {
	$smtp_settings = bsc_get_smtp_settings_debug();

	return array(
		'server'                   => bsc_get_email_server_debug(),
		'bsc_smtp_settings'        => $smtp_settings,
		'bsc_email_from'           => array(
			'name'    => function_exists( 'bsc_get_email_from_name' ) ? bsc_get_email_from_name() : '',
			'address' => function_exists( 'bsc_get_email_from_address' ) ? bsc_get_email_from_address() : '',
		),
		'smtp_credential_report'   => bsc_get_smtp_credential_report(),
		'diagnostic_warnings'      => bsc_get_email_diagnostic_warnings( $smtp_settings ),
		'active_mail_plugins'      => bsc_get_active_mail_plugins_debug(),
		'mail_plugin_options'      => bsc_get_mail_plugin_options_debug(),
		'phpmailer_init_callbacks' => bsc_get_phpmailer_hook_debug(),
		'next_followup_cron_mysql' => wp_next_scheduled( 'bsc_run_daily_followup_emails' )
			? wp_date( 'Y-m-d H:i:s', (int) wp_next_scheduled( 'bsc_run_daily_followup_emails' ) )
			: '',
		'credential_logging_policy' => 'Raw passwords are not stored or displayed. Use length, mask and sha256_12 to confirm which password is in use.',
	);
}

function bsc_summarize_smtp_debug_lines( array $lines ): array {
	$summary = array(
		'line_count'          => count( $lines ),
		'connected_220'      => false,
		'auth_accepted_235'  => false,
		'message_accepted'   => false,
		'mail_from_seen'     => false,
		'rcpt_to_seen'       => false,
		'data_seen'          => false,
		'last_server_reply'  => '',
		'server_errors'      => array(),
	);
	$awaiting_data_result = false;

	foreach ( $lines as $line ) {
		$line = (string) $line;

		if ( false !== stripos( $line, 'CLIENT -> SERVER: MAIL FROM' ) ) {
			$summary['mail_from_seen'] = true;
		}

		if ( false !== stripos( $line, 'CLIENT -> SERVER: RCPT TO' ) ) {
			$summary['rcpt_to_seen'] = true;
		}

		if ( false !== stripos( $line, 'CLIENT -> SERVER: DATA' ) ) {
			$summary['data_seen'] = true;
		}

		if ( preg_match( '/CLIENT -> SERVER:\s*\.\s*$/i', $line ) ) {
			$awaiting_data_result = true;
		}

		if ( false === stripos( $line, 'SERVER -> CLIENT:' ) ) {
			continue;
		}

		$summary['last_server_reply'] = $line;

		if ( preg_match( '/SERVER -> CLIENT:\s*220\b/i', $line ) ) {
			$summary['connected_220'] = true;
		}

		if ( preg_match( '/SERVER -> CLIENT:\s*235\b/i', $line ) ) {
			$summary['auth_accepted_235'] = true;
		}

		if ( $awaiting_data_result && preg_match( '/SERVER -> CLIENT:\s*250\b/i', $line ) ) {
			$summary['message_accepted'] = true;
			$awaiting_data_result        = false;
		}

		if ( preg_match( '/SERVER -> CLIENT:\s*[45][0-9][0-9]\b/i', $line ) ) {
			$summary['server_errors'][] = $line;
			if ( $awaiting_data_result ) {
				$summary['message_accepted'] = false;
				$awaiting_data_result        = false;
			}
		}
	}

	return $summary;
}

function bsc_capture_email_send_diagnostics( callable $send_callback, array $mail_request = array() ): array {
	$trace = array(
		'started_at'              => current_time( 'mysql' ),
		'mail_request'            => bsc_email_debug_sanitize_value( $mail_request, 'mail_request' ),
		'transport_before_send'   => bsc_get_email_transport_diagnostics(),
		'phpmailer_init_observed' => false,
		'phpmailer_before_send'   => array(),
		'wp_mail_failed'          => null,
		'wp_mail_result'          => 'not-run',
		'smtp_debug_lines'        => array(),
		'smtp_conversation'       => array(),
		'exception'               => null,
	);
	$auth_lines_to_redact = 0;

	$mail_failed_callback = static function ( $error ) use ( &$trace ): void {
		$trace['wp_mail_failed'] = bsc_email_debug_sanitize_value( $error, 'wp_mail_failed' );
	};

	$phpmailer_callback = static function ( $phpmailer ) use ( &$trace, &$auth_lines_to_redact ): void {
		$trace['phpmailer_init_observed'] = true;
		$trace['phpmailer_before_send']   = bsc_get_phpmailer_debug_snapshot( $phpmailer );

		if ( ! is_object( $phpmailer ) ) {
			return;
		}

		$runtime_password = isset( $phpmailer->Password ) ? (string) $phpmailer->Password : '';

		$phpmailer->SMTPDebug  = 2;
		$phpmailer->Debugoutput = static function ( $message, $level ) use ( &$trace, &$auth_lines_to_redact, $runtime_password ): void {
			$line = (string) $message;

			if ( $runtime_password !== '' ) {
				$line = str_replace( $runtime_password, '[redacted-runtime-smtp-password]', $line );
				$line = str_replace( base64_encode( $runtime_password ), '[redacted-runtime-smtp-password-base64]', $line );
			}

			if ( false !== stripos( $line, 'AUTH LOGIN' ) ) {
				$auth_lines_to_redact = 2;
			}

			if ( $auth_lines_to_redact > 0 && false !== stripos( $line, 'CLIENT -> SERVER:' ) && false === stripos( $line, 'AUTH LOGIN' ) ) {
				$line = 'CLIENT -> SERVER: [redacted smtp credential]';
				--$auth_lines_to_redact;
			}

			$trace['smtp_debug_lines'][] = bsc_email_debug_sanitize_smtp_line( $line, (int) $level );
		};
	};

	add_action( 'wp_mail_failed', $mail_failed_callback, 10, 1 );
	add_action( 'phpmailer_init', $phpmailer_callback, PHP_INT_MAX, 1 );

	try {
		$result                  = (bool) $send_callback();
		$trace['wp_mail_result'] = $result ? 'true' : 'false';
	} catch ( Throwable $throwable ) {
		$trace['wp_mail_result'] = 'exception';
		$trace['exception']      = array(
			'class'   => get_class( $throwable ),
			'message' => bsc_email_debug_sanitize_string( $throwable->getMessage(), 'exception.message' ),
			'file'    => bsc_email_debug_sanitize_string( $throwable->getFile(), 'exception.file' ),
			'line'    => $throwable->getLine(),
		);
	} finally {
		remove_action( 'wp_mail_failed', $mail_failed_callback, 10 );
		remove_action( 'phpmailer_init', $phpmailer_callback, PHP_INT_MAX );
	}

	$trace['phpmailer_debug'] = ! empty( $trace['smtp_debug_lines'] )
		? implode( "\n", $trace['smtp_debug_lines'] )
		: 'No SMTP debug output captured. If mailer is not smtp, PHPMailer may not emit SMTP conversation.';
	$trace['smtp_conversation'] = bsc_summarize_smtp_debug_lines( (array) $trace['smtp_debug_lines'] );

	return bsc_email_debug_sanitize_value( $trace, 'send_trace' );
}

function bsc_send_preview_email_to_target( string $slug, string $target_email ) {
	$slug         = sanitize_key( $slug );
	$target_email = sanitize_email( $target_email );
	$debug        = array(
		'time'                  => current_time( 'mysql' ),
		'slug'                  => $slug,
		'to'                    => $target_email,
		'transport_diagnostics' => bsc_get_email_transport_diagnostics(),
		'steps'                 => array(),
		'errors'                => array(),
	);

	if ( ! is_email( $target_email ) ) {
		$debug['steps'][] = 'FAIL: email destino invalido';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_target_invalid', 'Ingresa un email destino valido para enviar la prueba.' );
	}
	$debug['steps'][] = 'OK: email destino valido';

	if ( ! function_exists( 'bsc_get_email_preview_definitions' ) || ! function_exists( 'bsc_get_email_preview_context' ) ) {
		$debug['steps'][] = 'FAIL: sistema de previews no disponible';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_unavailable', 'El sistema de previews no esta disponible.' );
	}
	$debug['steps'][] = 'OK: sistema de previews cargado';

	$definitions = bsc_get_email_preview_definitions();
	if ( ! isset( $definitions[ $slug ] ) ) {
		$debug['steps'][] = 'FAIL: template no encontrado en definiciones';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_template_missing', 'Template de preview no encontrado.' );
	}
	$debug['steps'][] = 'OK: template encontrado: ' . ( $definitions[ $slug ]['template'] ?? '?' );

	$debug['template_file'] = (string) ( $definitions[ $slug ]['template'] ?? '' );

	$html = bsc_render_email_template(
		(string) $definitions[ $slug ]['template'],
		bsc_get_email_preview_context( $slug )
	);

	if ( $html === '' ) {
		$debug['steps'][] = 'FAIL: render del template devolvio string vacio';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_render_failed', 'No se pudo renderizar el email de prueba.' );
	}
	$debug['steps'][] = 'OK: template renderizado (' . strlen( $html ) . ' bytes)';

	$row     = bsc_get_preview_email_manifest_row( $slug );
	$label   = (string) ( $row['label'] ?? $definitions[ $slug ]['label'] ?? $slug );
	$subject = sprintf( 'Preview BSC: %s', $label );
	$debug['subject'] = $subject;
	$headers = bsc_get_email_headers(
		array(
			'X-BSC-Email-Preview: 1',
		)
	);
	$debug['mail_request'] = array(
		'to'         => $target_email,
		'subject'    => $subject,
		'template'   => (string) $definitions[ $slug ]['template'],
		'body_bytes' => strlen( $html ),
		'headers'    => $headers,
	);

	$send_trace = bsc_capture_email_send_diagnostics(
		static function () use ( $target_email, $subject, $html, $headers ): bool {
			return wp_mail(
				$target_email,
				$subject,
				$html,
				$headers
			);
		},
		$debug['mail_request']
	);
	$sent       = ( $send_trace['wp_mail_result'] ?? 'false' ) === 'true';

	$debug['wp_mail_result']    = $sent ? 'true' : 'false';
	$debug['send_trace']        = $send_trace;
	$debug['phpmailer_debug']   = (string) ( $send_trace['phpmailer_debug'] ?? '' );
	$debug['phpmailer_runtime'] = (array) ( $send_trace['phpmailer_before_send'] ?? array() );
	$debug['smtp_conversation'] = (array) ( $send_trace['smtp_conversation'] ?? array() );
	$debug['smtp_credential_report'] = (array) (
		$send_trace['phpmailer_before_send']['smtp_credential_report']
		?? $send_trace['transport_before_send']['smtp_credential_report']
		?? array()
	);

	if ( ! empty( $send_trace['wp_mail_failed'] ) ) {
		$debug['errors'][] = array(
			'type' => 'wp_mail_failed',
			'data' => $send_trace['wp_mail_failed'],
		);
	}

	if ( ! empty( $send_trace['exception'] ) ) {
		$debug['errors'][] = array(
			'type' => 'exception',
			'data' => $send_trace['exception'],
		);
	}

	if ( ! $sent ) {
		$debug['steps'][] = 'FAIL: wp_mail() devolvio false';
		$debug['errors'][] = 'wp_mail() returned false - posible fallo SMTP, credenciales, puerto, TLS/SSL, DNS o firewall';
		bsc_store_preview_debug( $debug );
		return new WP_Error( 'bsc_preview_send_failed', 'WordPress no pudo enviar el email de prueba. Revisa los logs de debug abajo.' );
	}

	$debug['steps'][] = 'OK: wp_mail() devolvio true';
	bsc_store_preview_debug( $debug );

	return array(
		'label'   => $label,
		'subject' => $subject,
		'to'      => $target_email,
	);
}

function bsc_store_preview_debug( array $debug ): void {
	$key    = 'bsc_preview_email_debug';
	$stored = get_option( $key, array() );
	if ( ! is_array( $stored ) ) {
		$stored = get_transient( $key );
	}
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	array_unshift( $stored, bsc_email_debug_sanitize_value( $debug, 'preview_debug' ) );
	$stored = array_slice( $stored, 0, 25 );
	update_option( $key, $stored, false );
}

function bsc_get_preview_debug_logs(): array {
	$stored = get_option( 'bsc_preview_email_debug', array() );
	if ( ! is_array( $stored ) ) {
		$stored = get_transient( 'bsc_preview_email_debug' );
	}

	return is_array( $stored ) ? bsc_email_debug_sanitize_value( $stored, 'preview_debug_logs' ) : array();
}

function bsc_debug_json_pretty( $value ): string {
	$json = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	return is_string( $json ) ? $json : '';
}

function bsc_render_email_debug_json( $value ): void {
	?>
	<pre class="bsc-email-debug__json"><?php echo esc_html( bsc_debug_json_pretty( $value ) ); ?></pre>
	<?php
}

function bsc_render_email_debug_list( array $items, string $class_name = '' ): void {
	if ( empty( $items ) ) {
		return;
	}
	?>
	<ul class="bsc-email-debug__list <?php echo esc_attr( $class_name ); ?>">
		<?php foreach ( $items as $item ) : ?>
			<li><?php echo esc_html( is_scalar( $item ) ? (string) $item : bsc_debug_json_pretty( $item ) ); ?></li>
		<?php endforeach; ?>
	</ul>
	<?php
}
function bsc_sanitize_smtp_secure_value( string $secure ): string {
	$secure = strtolower( sanitize_key( $secure ) );
	return in_array( $secure, array( 'ssl', 'tls', '' ), true ) ? $secure : 'ssl';
}

function bsc_sanitize_smtp_provider_value( string $provider ): string {
	$provider = strtolower( sanitize_key( $provider ) );
	return in_array( $provider, array( 'custom', 'zeptomail' ), true ) ? $provider : 'custom';
}

function bsc_save_email_transport_settings_from_post(): void {
	update_option( 'bsc_email_from_name', sanitize_text_field( (string) wp_unslash( $_POST['bsc_email_from_name'] ?? 'Bubble Skin Care' ) ) );
	update_option( 'bsc_email_from_address', sanitize_email( (string) wp_unslash( $_POST['bsc_email_from_address'] ?? '' ) ) );

	$provider = bsc_sanitize_smtp_provider_value( (string) wp_unslash( $_POST['bsc_smtp_provider'] ?? 'custom' ) );
	$host     = sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_host'] ?? '' ) );
	$port     = absint( wp_unslash( $_POST['bsc_smtp_port'] ?? 0 ) );
	$secure   = bsc_sanitize_smtp_secure_value( (string) wp_unslash( $_POST['bsc_smtp_secure'] ?? '' ) );
	$username = sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_username'] ?? '' ) );

	if ( 'zeptomail' === $provider ) {
		$host     = false !== strpos( strtolower( $host ), 'zeptomail' ) ? $host : 'smtp.zeptomail.com';
		$port     = $port > 0 && 465 !== $port ? $port : 587;
		$secure   = $secure !== '' && 'ssl' !== $secure ? $secure : 'tls';
		$username = false === strpos( $username, '@' ) && $username !== '' ? $username : 'emailapikey';
	}

	if ( $port <= 0 ) {
		$port = 'tls' === $secure ? 587 : 465;
	}

	update_option( 'bsc_smtp_provider', $provider, false );
	update_option( 'bsc_smtp_enabled', isset( $_POST['bsc_smtp_enabled'] ) ? 1 : 0, false );
	update_option( 'bsc_smtp_host', $host, false );
	update_option( 'bsc_smtp_port', max( 1, $port ), false );
	update_option( 'bsc_smtp_secure', $secure, false );
	update_option( 'bsc_smtp_auth', isset( $_POST['bsc_smtp_auth'] ) ? 1 : 0, false );
	update_option( 'bsc_smtp_username', $username, false );

	if ( isset( $_POST['bsc_smtp_clear_password'] ) ) {
		delete_option( 'bsc_smtp_password' );
		return;
	}

	$password = sanitize_text_field( (string) wp_unslash( $_POST['bsc_smtp_password'] ?? '' ) );
	if ( $password !== '' ) {
		update_option( 'bsc_smtp_password', $password, false );
	}
}

function bsc_render_followup_emails_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para ver esta página.', 'bsc-2-0' ) );
	}

	$defaults        = bsc_get_followup_email_defaults();
	$manifest        = bsc_get_email_template_manifest();
	$notice          = '';
	$notice_class    = 'notice-success';
	$run_now_summary = array();
	$current_user    = wp_get_current_user();
	$target_email    = sanitize_email( (string) get_user_meta( get_current_user_id(), 'bsc_preview_target_email', true ) );

	if ( $target_email === '' && $current_user instanceof WP_User ) {
		$target_email = sanitize_email( (string) $current_user->user_email );
	}

	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

	if ( 'POST' === $request_method && isset( $_POST['bsc_followup_emails_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bsc_followup_emails_nonce'] ) ), 'bsc_followup_emails_action' ) ) {
			wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
		}

		if ( isset( $_POST['bsc_save_followup_emails'] ) ) {
			bsc_save_email_transport_settings_from_post();

			update_option( 'bsc_followup_emails_enabled', isset( $_POST['bsc_followup_emails_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_welcome_email_enabled', isset( $_POST['bsc_welcome_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_password_reset_email_enabled', isset( $_POST['bsc_password_reset_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_password_changed_email_enabled', isset( $_POST['bsc_password_changed_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_birthday_email_enabled', isset( $_POST['bsc_birthday_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_enabled', isset( $_POST['bsc_inactive_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_inactive_email_days', max( 1, absint( wp_unslash( $_POST['bsc_inactive_email_days'] ?? $defaults['bsc_inactive_email_days'] ) ) ) );
			update_option( 'bsc_repurchase_email_enabled', isset( $_POST['bsc_repurchase_email_enabled'] ) ? 1 : 0 );
			update_option( 'bsc_default_repurchase_days', max( 1, absint( wp_unslash( $_POST['bsc_default_repurchase_days'] ?? $defaults['bsc_default_repurchase_days'] ) ) ) );

			$notice = 'Configuración guardada.';
		}

		if ( isset( $_POST['bsc_run_followup_now'] ) ) {
			$run_now_summary = bsc_run_followup_email_jobs();
			$notice          = 'Seguimiento ejecutado manualmente.';
		}

		if ( isset( $_POST['bsc_send_preview_email'] ) ) {
			$target_email = sanitize_email( (string) wp_unslash( $_POST['bsc_preview_target_email'] ?? '' ) );
			$slug         = sanitize_key( (string) wp_unslash( $_POST['bsc_preview_template'] ?? '' ) );
			$result       = bsc_send_preview_email_to_target( $slug, $target_email );

			if ( is_wp_error( $result ) ) {
				$notice       = $result->get_error_message();
				$notice_class = 'notice-error';
			} else {
				update_user_meta( get_current_user_id(), 'bsc_preview_target_email', $target_email );
				$notice = sprintf(
					'Preview "%s" enviado a %s.',
					(string) $result['label'],
					(string) $result['to']
				);
			}
		}

		if ( isset( $_POST['bsc_clear_preview_debug'] ) ) {
			delete_option( 'bsc_preview_email_debug' );
			delete_transient( 'bsc_preview_email_debug' );
			$notice = 'Logs de debug eliminados.';
		}
	}

	$last_run       = get_option( 'bsc_followup_email_last_run_summary', array() );
	$email_log_rows = function_exists( 'bsc_get_recent_order_email_log_rows' )
		? bsc_get_recent_order_email_log_rows( 20 )
		: array();
	$smtp_settings       = bsc_get_smtp_settings();
	$smtp_provider       = (string) ( $smtp_settings['provider'] ?? 'custom' );
	$smtp_password_saved = bsc_smtp_password_is_configured();
	$email_diagnostics   = bsc_get_email_transport_diagnostics();
	$smtp_debug_settings = (array) ( $email_diagnostics['bsc_smtp_settings'] ?? array() );
	$smtp_debug_password = (array) ( $smtp_debug_settings['password'] ?? array() );
	$smtp_credential_report = (array) ( $email_diagnostics['smtp_credential_report'] ?? array() );
	$smtp_configured_credential = (array) ( $smtp_credential_report['configured'] ?? array() );
	?>
	<div class="wrap bsc-admin-followup">
		<div class="bsc-admin-page-header">
			<div>
				<span class="bsc-admin-page-header__eyebrow">Comunicaciones</span>
				<h1>Emails BSC</h1>
				<p class="bsc-admin-page-header__description">Controla remitente, SMTP, automatizaciones y pruebas de templates desde un solo panel.</p>
			</div>
			<div class="bsc-admin-page-header__actions">
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-dashboard' ) ); ?>">Dashboard</a>
			</div>
		</div>

		<?php if ( $notice !== '' ) : ?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>

		<section class="bsc-email-diagnostics" aria-labelledby="bsc-email-diagnostics-title">
			<div class="bsc-email-diagnostics__header">
				<h2 id="bsc-email-diagnostics-title">Diagnostico SMTP actual</h2>
				<p>La contrasena no se muestra en claro; usa longitud, mascara y sha256_12 para confirmar si es la esperada.</p>
			</div>
			<div class="bsc-email-diagnostics__grid">
				<div>
					<strong>Transporte</strong>
					<span><?php echo ! empty( $smtp_debug_settings['enabled'] ) ? 'SMTP BSC activo' : 'SMTP BSC desactivado'; ?></span>
				</div>
				<div>
					<strong>Proveedor</strong>
					<span><?php echo esc_html( 'zeptomail' === (string) ( $smtp_debug_settings['provider'] ?? '' ) ? 'ZeptoMail' : 'SMTP personalizado' ); ?></span>
				</div>
				<div>
					<strong>Servidor</strong>
					<span><?php echo esc_html( (string) ( $smtp_debug_settings['host'] ?? '' ) ); ?>:<?php echo esc_html( (string) ( $smtp_debug_settings['port'] ?? '' ) ); ?> <?php echo esc_html( strtoupper( (string) ( $smtp_debug_settings['secure'] ?? '' ) ) ); ?></span>
				</div>
				<div>
					<strong>Usuario SMTP</strong>
					<span><?php echo esc_html( (string) ( $smtp_debug_settings['username'] ?? '' ) ); ?></span>
				</div>
				<div>
					<strong>Password SMTP</strong>
					<span>
						<?php echo empty( $smtp_debug_password['present'] ) ? 'No configurada' : 'Configurada'; ?>
						<?php if ( ! empty( $smtp_debug_password['present'] ) ) : ?>
							- len <?php echo esc_html( (string) ( $smtp_debug_password['length'] ?? '' ) ); ?>,
							<?php echo esc_html( (string) ( $smtp_debug_password['masked'] ?? '' ) ); ?>,
							sha256_12 <?php echo esc_html( (string) ( $smtp_debug_password['sha256_12'] ?? '' ) ); ?>
						<?php endif; ?>
					</span>
				</div>
				<div>
					<strong>Credencial configurada</strong>
					<span>
						source <?php echo esc_html( (string) ( $smtp_configured_credential['source'] ?? '?' ) ); ?>,
						len <?php echo esc_html( (string) ( $smtp_configured_credential['length'] ?? '0' ) ); ?>,
						<?php echo esc_html( (string) ( $smtp_configured_credential['masked'] ?? '(not configured)' ) ); ?>,
						sha256_12 <?php echo esc_html( (string) ( $smtp_configured_credential['sha256_12'] ?? '' ) ); ?>
					</span>
				</div>
				<div>
					<strong>From</strong>
					<span><?php echo esc_html( (string) ( $email_diagnostics['bsc_email_from']['name'] ?? '' ) ); ?> &lt;<?php echo esc_html( (string) ( $email_diagnostics['bsc_email_from']['address'] ?? '' ) ); ?>&gt;</span>
				</div>
				<div>
					<strong>Plugins SMTP/mail activos</strong>
					<span><?php echo empty( $email_diagnostics['active_mail_plugins'] ) ? 'No detectados' : esc_html( (string) count( (array) $email_diagnostics['active_mail_plugins'] ) ); ?></span>
				</div>
			</div>
			<?php if ( ! empty( $email_diagnostics['diagnostic_warnings'] ) ) : ?>
				<div class="bsc-email-diagnostics__warnings">
					<strong>Alertas</strong>
					<?php bsc_render_email_debug_list( (array) $email_diagnostics['diagnostic_warnings'] ); ?>
				</div>
			<?php endif; ?>
			<form method="post" class="bsc-email-diagnostics__send-test">
				<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>
				<input type="hidden" name="bsc_preview_template" value="welcome">
				<label for="bsc-diagnostic-preview-target">Email destino</label>
				<input
					type="email"
					id="bsc-diagnostic-preview-target"
					name="bsc_preview_target_email"
					value="<?php echo esc_attr( $target_email ); ?>"
					placeholder="email destino"
					required
				>
				<?php submit_button( 'Enviar prueba con debug', 'secondary', 'bsc_send_preview_email', false ); ?>
			</form>
			<details class="bsc-email-debug__details">
				<summary>Ver JSON completo del diagnostico actual</summary>
				<?php bsc_render_email_debug_json( $email_diagnostics ); ?>
			</details>
		</section>

		<?php $debug_logs = bsc_get_preview_debug_logs(); ?>
		<?php if ( ! empty( $debug_logs ) ) : ?>
			<details class="bsc-email-debug">
				<summary class="bsc-email-debug__summary">
					<?php echo count( $debug_logs ); ?> envío(s) registrado(s) en debug — clic para expandir
				</summary>
				<div class="bsc-email-debug__body">
					<?php foreach ( $debug_logs as $index => $log ) : ?>
						<div class="bsc-email-debug__event">
							<?php $log_smtp = (array) ( $log['transport_diagnostics']['bsc_smtp_settings'] ?? $log['smtp'] ?? array() ); ?>
							<?php $log_smtp_password = is_array( $log_smtp['password'] ?? null ) ? (array) $log_smtp['password'] : bsc_email_debug_secret_summary( (string) ( $log_smtp['password'] ?? '' ), 'legacy-log' ); ?>
							<?php $log_credential_report = (array) ( $log['smtp_credential_report'] ?? $log['send_trace']['phpmailer_before_send']['smtp_credential_report'] ?? array() ); ?>
							<?php $log_configured_credential = (array) ( $log_credential_report['configured'] ?? array() ); ?>
							<?php $log_runtime_credential = (array) ( $log_credential_report['runtime'] ?? array() ); ?>
							<?php $log_smtp_conversation = (array) ( $log['smtp_conversation'] ?? $log['send_trace']['smtp_conversation'] ?? array() ); ?>
							<strong>#<?php echo (int) ( $index + 1 ); ?> — <?php echo esc_html( $log['time'] ?? '?' ); ?> — <?php echo esc_html( $log['slug'] ?? '?' ); ?> → <?php echo esc_html( $log['to'] ?? '?' ); ?></strong>
							<div class="bsc-email-debug__line">
								<strong>wp_mail result:</strong> <?php echo esc_html( $log['wp_mail_result'] ?? '?' ); ?>
							</div>
							<?php if ( ! empty( $log['mail_request'] ) && is_array( $log['mail_request'] ) ) : ?>
								<div class="bsc-email-debug__line">
									<strong>Request:</strong>
									to <?php echo esc_html( (string) ( $log['mail_request']['to'] ?? '?' ) ); ?>,
									template <?php echo esc_html( (string) ( $log['mail_request']['template'] ?? '?' ) ); ?>,
									subject <?php echo esc_html( (string) ( $log['mail_request']['subject'] ?? '?' ) ); ?>,
									bytes <?php echo esc_html( (string) ( $log['mail_request']['body_bytes'] ?? '?' ) ); ?>
								</div>
							<?php endif; ?>
							<div class="bsc-email-debug__line">
								<strong>SMTP diagnostico:</strong>
								<?php echo esc_html( ( $log_smtp['enabled'] ?? false ) ? 'ACTIVO' : 'DESACTIVADO' ); ?>
								<?php if ( ! empty( $log_smtp['host'] ) ) : ?>
									- <?php echo esc_html( (string) $log_smtp['host'] ); ?>:<?php echo esc_html( (string) ( $log_smtp['port'] ?? '' ) ); ?>
									(<?php echo esc_html( (string) ( $log_smtp['secure'] ?? '?' ) ); ?>,
									auth: <?php echo empty( $log_smtp['auth'] ) ? 'no' : 'si'; ?>,
									user: <?php echo esc_html( (string) ( $log_smtp['username'] ?? '?' ) ); ?>,
									pass: <?php echo empty( $log_smtp_password['present'] ) ? 'no configurada' : esc_html( (string) ( $log_smtp_password['masked'] ?? 'configurada' ) ); ?>)
								<?php endif; ?>
							</div>
							<?php if ( ! empty( $log_credential_report ) ) : ?>
								<div class="bsc-email-debug__line">
									<strong>Credencial SMTP:</strong>
									config <?php echo esc_html( (string) ( $log_configured_credential['source'] ?? '?' ) ); ?>
									<?php if ( ! empty( $log_configured_credential['present'] ) ) : ?>
										(<?php echo esc_html( (string) ( $log_configured_credential['masked'] ?? '' ) ); ?>,
										len <?php echo esc_html( (string) ( $log_configured_credential['length'] ?? '' ) ); ?>,
										sha256_12 <?php echo esc_html( (string) ( $log_configured_credential['sha256_12'] ?? '' ) ); ?>)
									<?php endif; ?>
									- runtime <?php echo ! empty( $log_runtime_credential['present'] ) ? 'presente' : 'no capturada'; ?>
									<?php if ( ! empty( $log_runtime_credential['present'] ) ) : ?>
										(<?php echo esc_html( (string) ( $log_runtime_credential['masked'] ?? '' ) ); ?>,
										len <?php echo esc_html( (string) ( $log_runtime_credential['length'] ?? '' ) ); ?>,
										sha256_12 <?php echo esc_html( (string) ( $log_runtime_credential['sha256_12'] ?? '' ) ); ?>)
									<?php endif; ?>
									- match:
									<?php
									if ( empty( $log_credential_report['runtime_available'] ) ) {
										echo esc_html( 'no-runtime' );
									} else {
										echo ! empty( $log_credential_report['runtime_matches_configured'] ) ? esc_html( 'si' ) : esc_html( 'no' );
									}
									?>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $log_smtp_conversation ) ) : ?>
								<div class="bsc-email-debug__line">
									<strong>Resumen SMTP:</strong>
									lineas <?php echo esc_html( (string) ( $log_smtp_conversation['line_count'] ?? 0 ) ); ?>,
									220 <?php echo ! empty( $log_smtp_conversation['connected_220'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>,
									auth235 <?php echo ! empty( $log_smtp_conversation['auth_accepted_235'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>,
									mail_from <?php echo ! empty( $log_smtp_conversation['mail_from_seen'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>,
									rcpt_to <?php echo ! empty( $log_smtp_conversation['rcpt_to_seen'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>,
									data <?php echo ! empty( $log_smtp_conversation['data_seen'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>,
									accepted250 <?php echo ! empty( $log_smtp_conversation['message_accepted'] ) ? esc_html( 'si' ) : esc_html( 'no' ); ?>
								</div>
								<?php if ( ! empty( $log_smtp_conversation['server_errors'] ) ) : ?>
									<div class="bsc-email-debug__errors">
										<strong>Errores SMTP servidor:</strong>
										<?php bsc_render_email_debug_list( (array) $log_smtp_conversation['server_errors'] ); ?>
									</div>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( ! empty( $log['steps'] ) ) : ?>
								<div class="bsc-email-debug__line">
									<strong>Pasos:</strong>
									<ul class="bsc-email-debug__steps">
										<?php foreach ( $log['steps'] as $step ) : ?>
											<li class="<?php echo esc_attr( 0 === strpos( (string) $step, 'FAIL' ) ? 'is-fail' : 'is-ok' ); ?>">
												<?php echo esc_html( $step ); ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $log['errors'] ) ) : ?>
								<div class="bsc-email-debug__errors">
									<strong>Errores:</strong>
									<ul>
										<?php foreach ( $log['errors'] as $error ) : ?>
											<li><?php echo esc_html( is_array( $error ) ? wp_json_encode( $error ) : (string) $error ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $log['phpmailer_debug'] ) && is_string( $log['phpmailer_debug'] ) ) : ?>
								<details class="bsc-email-debug__details">
									<summary>PHPMailer/SMTP debug output</summary>
									<pre class="bsc-email-debug__json"><?php echo esc_html( $log['phpmailer_debug'] ); ?></pre>
								</details>
							<?php endif; ?>
							<?php if ( ! empty( $log['phpmailer_runtime'] ) ) : ?>
								<details class="bsc-email-debug__details">
									<summary>PHPMailer runtime final</summary>
									<?php bsc_render_email_debug_json( $log['phpmailer_runtime'] ); ?>
								</details>
							<?php endif; ?>
							<details class="bsc-email-debug__details">
								<summary>JSON completo sanitizado del evento</summary>
								<?php bsc_render_email_debug_json( $log ); ?>
							</details>
						</div>
					<?php endforeach; ?>
					<form method="post" class="bsc-email-debug__clear">
						<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>
						<button type="submit" name="bsc_clear_preview_debug" class="button button-small bsc-email-debug__clear-button">Limpiar logs de debug</button>
					</form>
				</div>
			</details>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>

			<table class="form-table">
				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title">Remitente y SMTP</h2></th>
				</tr>
				<tr>
					<th><label for="bsc_email_from_name">Nombre del remitente</label></th>
					<td>
						<input
							type="text"
							id="bsc_email_from_name"
							name="bsc_email_from_name"
							value="<?php echo esc_attr( bsc_get_email_from_name() ); ?>"
							class="regular-text"
						>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_email_from_address">Email del remitente</label></th>
					<td>
						<input
							type="email"
							id="bsc_email_from_address"
							name="bsc_email_from_address"
							value="<?php echo esc_attr( bsc_get_email_from_address() ); ?>"
							class="regular-text"
							placeholder="noreply@bubblesskincare.com"
						>
						<p class="description">Debe ser un sender verificado/autorizado en ZeptoMail.</p>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_provider">Proveedor transaccional</label></th>
					<td>
						<select id="bsc_smtp_provider" name="bsc_smtp_provider">
							<option value="zeptomail" <?php selected( $smtp_provider, 'zeptomail' ); ?>>ZeptoMail</option>
							<option value="custom" <?php selected( $smtp_provider, 'custom' ); ?>>SMTP personalizado</option>
						</select>
						<p class="description">Se guarda en wp_options y el theme lo usa en producción. Si eliges ZeptoMail, los defaults son smtp.zeptomail.com, 587, TLS y usuario emailapikey.</p>
					</td>
				</tr>
				<tr>
					<th>SMTP</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_smtp_enabled" value="1" <?php checked( (bool) $smtp_settings['enabled'], true ); ?>>
							Enviar correos con SMTP configurado por BSC
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_host">Servidor SMTP</label></th>
					<td>
						<input
							type="text"
							id="bsc_smtp_host"
							name="bsc_smtp_host"
							value="<?php echo esc_attr( (string) $smtp_settings['host'] ); ?>"
							class="regular-text"
							placeholder="smtp.zeptomail.com"
						>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_port">Puerto y seguridad</label></th>
					<td>
						<input
							type="number"
							id="bsc_smtp_port"
							name="bsc_smtp_port"
							min="1"
							step="1"
							value="<?php echo esc_attr( (string) $smtp_settings['port'] ); ?>"
							class="small-text"
						>
						<select name="bsc_smtp_secure" aria-label="Seguridad SMTP">
							<option value="ssl" <?php selected( (string) $smtp_settings['secure'], 'ssl' ); ?>>SSL</option>
							<option value="tls" <?php selected( (string) $smtp_settings['secure'], 'tls' ); ?>>TLS</option>
							<option value="" <?php selected( (string) $smtp_settings['secure'], '' ); ?>>Sin cifrado</option>
						</select>
						<p class="description">ZeptoMail: smtp.zeptomail.com con puerto 587 y TLS.</p>
					</td>
				</tr>
				<tr>
					<th>Autenticacion</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_smtp_auth" value="1" <?php checked( (bool) $smtp_settings['auth'], true ); ?>>
							Requerir autenticacion SMTP
						</label>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_username">Usuario SMTP</label></th>
					<td>
						<input
							type="text"
							id="bsc_smtp_username"
							name="bsc_smtp_username"
							value="<?php echo esc_attr( (string) $smtp_settings['username'] ); ?>"
							class="regular-text"
							autocomplete="username"
							placeholder="emailapikey"
						>
						<p class="description">Para ZeptoMail el usuario SMTP es emailapikey.</p>
					</td>
				</tr>
				<tr>
					<th><label for="bsc_smtp_password">Password SMTP</label></th>
					<td>
						<input
							type="password"
							id="bsc_smtp_password"
							name="bsc_smtp_password"
							value=""
							class="regular-text"
							autocomplete="new-password"
							placeholder="<?php echo esc_attr( $smtp_password_saved ? 'API key guardada' : 'API key SMTP de ZeptoMail' ); ?>"
						>
						<p class="description">Dejalo vacio para conservar la API key guardada.</p>
						<?php if ( $smtp_password_saved ) : ?>
							<label class="bsc-admin-followup__inline-setting">
								<input type="checkbox" name="bsc_smtp_clear_password" value="1">
								Borrar clave guardada al guardar
							</label>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title">Módulo</h2></th>
				</tr>
				<tr>
					<th>Activación general</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_followup_emails_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_followup_emails_enabled' ), 1 ); ?>>
							Activar correos de seguimiento y branded emails BSC
						</label>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title bsc-admin-followup__section-title--spaced">Correos inmediatos</h2></th>
				</tr>
				<tr>
					<th>Usuario nuevo</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_welcome_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_welcome_email_enabled' ), 1 ); ?>>
							Enviar bienvenida al crear cuenta customer
						</label>
					</td>
				</tr>
				<tr>
					<th>Recuperar contraseña</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_password_reset_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' ), 1 ); ?>>
							Usar template branded BSC para reset
						</label>
					</td>
				</tr>
				<tr>
					<th>Contraseña actualizada</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_password_changed_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_password_changed_email_enabled' ), 1 ); ?>>
							Usar template branded BSC cuando se cambia desde Mis datos
						</label>
					</td>
				</tr>

				<tr>
					<th colspan="2"><h2 class="bsc-admin-followup__section-title bsc-admin-followup__section-title--spaced">Correos de seguimiento</h2></th>
				</tr>
				<tr>
					<th>Cumpleaños</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_birthday_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_birthday_email_enabled' ), 1 ); ?>>
							Enviar una vez por año según `bsc_birthday`
						</label>
					</td>
				</tr>
				<tr>
					<th>Hace mucho no compras</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_inactive_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_inactive_email_enabled' ), 1 ); ?>>
							Activar recordatorio por inactividad
						</label>
						<p class="bsc-admin-followup__inline-setting">
							<input type="number" min="1" step="1" name="bsc_inactive_email_days" value="<?php echo esc_attr( (string) bsc_get_followup_email_setting( 'bsc_inactive_email_days' ) ); ?>" class="small-text">
							días desde la última compra
						</p>
					</td>
				</tr>
				<tr>
					<th>Se te acabó el producto</th>
					<td>
						<label>
							<input type="checkbox" name="bsc_repurchase_email_enabled" value="1" <?php checked( (int) bsc_get_followup_email_setting( 'bsc_repurchase_email_enabled' ), 1 ); ?>>
							Activar recordatorio de recompra
						</label>
						<p class="bsc-admin-followup__inline-setting">
							<input type="number" min="1" step="1" name="bsc_default_repurchase_days" value="<?php echo esc_attr( (string) bsc_get_followup_email_setting( 'bsc_default_repurchase_days' ) ); ?>" class="small-text">
							días por defecto para productos sin override
						</p>
						<p class="description">Cada producto puede sobreescribir este timeout desde el editor BSC.</p>
					</td>
				</tr>
			</table>

			<?php submit_button( 'Guardar configuración', 'primary', 'bsc_save_followup_emails' ); ?>
			<?php submit_button( 'Ejecutar seguimiento ahora', 'secondary', 'bsc_run_followup_now', false ); ?>
		</form>

		<?php if ( ! empty( $run_now_summary ) ) : ?>
			<div class="notice notice-info bsc-admin-followup__notice">
				<p>
					Ejecutado: <?php echo esc_html( $run_now_summary['ran_at'] ?? '' ); ?> |
					Cumpleaños: <?php echo esc_html( (string) ( $run_now_summary['birthday'] ?? 0 ) ); ?> |
					Inactividad: <?php echo esc_html( (string) ( $run_now_summary['inactive'] ?? 0 ) ); ?> |
					Recompra: <?php echo esc_html( (string) ( $run_now_summary['repurchase'] ?? 0 ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="bsc-admin-followup__section">
			<h2>Última ejecución registrada</h2>
			<?php if ( ! empty( $last_run ) ) : ?>
				<p>
					Fecha: <strong><?php echo esc_html( (string) ( $last_run['ran_at'] ?? '' ) ); ?></strong><br>
					Cumpleaños: <?php echo esc_html( (string) ( $last_run['birthday'] ?? 0 ) ); ?><br>
					Inactividad: <?php echo esc_html( (string) ( $last_run['inactive'] ?? 0 ) ); ?><br>
					Recompra: <?php echo esc_html( (string) ( $last_run['repurchase'] ?? 0 ) ); ?>
				</p>
			<?php else : ?>
				<p>No hay ejecuciones registradas todavía.</p>
			<?php endif; ?>
		</div>

		<div class="bsc-admin-followup__section">
			<h2>Log operativo de correos de pedidos</h2>
			<?php if ( ! empty( $email_log_rows ) ) : ?>
				<table class="widefat striped bsc-admin-followup__templates-table">
					<thead>
						<tr>
							<th>Fecha</th>
							<th>Pedido</th>
							<th>Tipo</th>
							<th>Estado</th>
							<th>Destino</th>
							<th>Asunto</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $email_log_rows as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['sent_at'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders&s=' . rawurlencode( (string) $row['order_number'] ) ) ); ?>">
										#<?php echo esc_html( $row['order_number'] ); ?>
									</a>
								</td>
								<td><?php echo esc_html( $row['type'] ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['to'] ); ?></td>
								<td><?php echo esc_html( $row['subject'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No hay correos de pedidos registrados todavia.</p>
			<?php endif; ?>
		</div>

		<div class="bsc-admin-followup__section">
			<h2>Templates editables</h2>
			<table class="widefat striped bsc-admin-followup__templates-table">
				<thead>
					<tr>
						<th>Tipo</th>
						<th>Trigger</th>
						<th>Archivo</th>
						<th>Preview</th>
						<th>Enviar prueba</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $manifest as $row ) : ?>
						<tr>
							<td><?php echo esc_html( $row['label'] ); ?></td>
							<td><?php echo esc_html( $row['trigger'] ); ?></td>
							<td><code><?php echo esc_html( get_template_directory() . '/emails/' . $row['file'] ); ?></code></td>
							<td>
								<?php if ( ! empty( $row['slug'] ) && function_exists( 'bsc_get_email_preview_url' ) ) : ?>
									<a class="button button-secondary" href="<?php echo esc_url( bsc_get_email_preview_url( (string) $row['slug'] ) ); ?>" target="_blank" rel="noopener noreferrer">Preview</a>
								<?php else : ?>
									<span class="description">N/D</span>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['slug'] ) ) : ?>
									<form method="post" class="bsc-admin-followup__preview-send-form">
										<?php wp_nonce_field( 'bsc_followup_emails_action', 'bsc_followup_emails_nonce' ); ?>
										<input type="hidden" name="bsc_preview_template" value="<?php echo esc_attr( (string) $row['slug'] ); ?>">
										<label class="screen-reader-text" for="bsc-preview-target-<?php echo esc_attr( (string) $row['slug'] ); ?>">
											Email destino para <?php echo esc_html( $row['label'] ); ?>
										</label>
										<input
											type="email"
											id="bsc-preview-target-<?php echo esc_attr( (string) $row['slug'] ); ?>"
											name="bsc_preview_target_email"
											value="<?php echo esc_attr( $target_email ); ?>"
											placeholder="email destino"
											required
										>
										<?php submit_button( 'Enviar prueba', 'secondary small', 'bsc_send_preview_email', false ); ?>
									</form>
								<?php else : ?>
									<span class="description">N/D</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
