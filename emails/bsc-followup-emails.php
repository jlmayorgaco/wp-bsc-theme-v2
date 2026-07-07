<?php
/**
 * BSC-082: Follow-up email module and account email hooks.
 */
defined( 'ABSPATH' ) || exit;

function bsc_get_followup_email_defaults(): array {
	return array(
		'bsc_followup_emails_enabled'        => 1,
		'bsc_welcome_email_enabled'          => 1,
		'bsc_password_reset_email_enabled'   => 1,
		'bsc_password_changed_email_enabled' => 1,
		'bsc_birthday_email_enabled'         => 1,
		'bsc_inactive_email_enabled'         => 1,
		'bsc_inactive_email_days'            => 60,
		'bsc_repurchase_email_enabled'       => 1,
		'bsc_default_repurchase_days'        => 30,
	);
}

function bsc_get_followup_email_setting( string $key ) {
	$defaults = bsc_get_followup_email_defaults();
	return get_option( $key, $defaults[ $key ] ?? null );
}

function bsc_is_followup_emails_enabled(): bool {
	return (bool) bsc_get_followup_email_setting( 'bsc_followup_emails_enabled' );
}

function bsc_get_email_shop_url(): string {
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_page_id = (int) wc_get_page_id( 'shop' );
		if ( $shop_page_id > 0 ) {
			$url = get_permalink( $shop_page_id );
			if ( $url ) {
				return $url;
			}
		}
	}

	return home_url( '/shop/' );
}

function bsc_get_email_account_url(): string {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$url = wc_get_page_permalink( 'myaccount' );
		if ( $url ) {
			return $url;
		}
	}

	return home_url( '/mi-cuenta/' );
}

function bsc_schedule_followup_email_jobs(): void {
	if ( ! wp_next_scheduled( 'bsc_run_daily_followup_emails' ) ) {
		wp_schedule_event( time(), 'daily', 'bsc_run_daily_followup_emails' );
	}
}
add_action( 'init', 'bsc_schedule_followup_email_jobs' );

function bsc_get_followup_paid_statuses(): array {
	return array( 'processing', 'preparing', 'shipped', 'completed' );
}

function bsc_get_followup_orders_desc(): array {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$orders     = array();
	$page       = 1;
	$max_pages  = 1;
	$per_page   = 100;
	$query_args = array(
		'status'   => bsc_get_followup_paid_statuses(),
		'orderby'  => 'date',
		'order'    => 'DESC',
		'limit'    => $per_page,
		'paginate' => true,
	);

	do {
		$query_result = wc_get_orders(
			array_merge(
				$query_args,
				array(
					'page' => $page,
				)
			)
		);

		$page_orders = is_object( $query_result ) && isset( $query_result->orders ) && is_array( $query_result->orders )
			? $query_result->orders
			: array();

		foreach ( $page_orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$orders[] = $order;
			}
		}

		$max_pages = max( 1, (int) ( $query_result->max_num_pages ?? 1 ) );
		++$page;
	} while ( $page <= $max_pages );

	return $orders;
}

function bsc_get_followup_contact_key( WC_Order $order ): string {
	$user_id = (int) $order->get_user_id();
	if ( $user_id > 0 ) {
		return 'user:' . $user_id;
	}

	$email = strtolower( trim( (string) $order->get_billing_email() ) );
	if ( $email !== '' ) {
		return 'email:' . md5( $email );
	}

	return 'order:' . $order->get_id();
}

function bsc_get_followup_order_recipient( WC_Order $order ): array {
	$user_id = (int) $order->get_user_id();
	$email   = sanitize_email( (string) $order->get_billing_email() );
	$name    = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

	if ( $user_id > 0 ) {
		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			if ( $email === '' ) {
				$email = sanitize_email( (string) $user->user_email );
			}
			if ( $name === '' ) {
				$name = trim( $user->first_name . ' ' . $user->last_name );
				if ( $name === '' ) {
					$name = $user->display_name;
				}
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

function bsc_get_product_repurchase_days( int $product_id ): int {
	$raw  = get_post_meta( $product_id, '_bsc_repurchase_days', true );
	$days = (int) $raw;

	if ( $days > 0 ) {
		return $days;
	}

	return max( 1, (int) bsc_get_followup_email_setting( 'bsc_default_repurchase_days' ) );
}

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

function bsc_customize_password_reset_email( array $defaults, string $key, string $user_login, WP_User $user_data ): array {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' ) ) {
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

function bsc_password_reset_title_fallback( string $title ): string {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' ) ) {
		return $title;
	}

	return 'Recupera tu contraseña — Bubble Skin Care';
}
add_filter( 'retrieve_password_title', 'bsc_password_reset_title_fallback', 10, 1 );

function bsc_password_reset_message_fallback( string $message, string $key, string $user_login, $user_data ): string {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_password_reset_email_enabled' ) ) {
		return $message;
	}

	$reset_url = network_site_url(
		'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ),
		'login'
	);

	$name = $user_data instanceof WP_User ? $user_data->display_name : $user_login;

	return "Hola {$name},\n\nUsa este enlace para cambiar tu contraseña:\n{$reset_url}\n\nSi no solicitaste este cambio, ignora este correo.\n";
}
add_filter( 'retrieve_password_message', 'bsc_password_reset_message_fallback', 10, 4 );

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

function bsc_process_birthday_followup_emails(): int {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_birthday_email_enabled' ) ) {
		return 0;
	}

	$today_month  = wp_date( 'm', current_time( 'timestamp' ), wp_timezone() );
	$today_day    = wp_date( 'd', current_time( 'timestamp' ), wp_timezone() );
	$current_year = wp_date( 'Y', current_time( 'timestamp' ), wp_timezone() );
	$sent_count   = 0;

	$user_ids = get_users(
		array(
			'fields'       => 'ids',
			'meta_key'     => 'bsc_birthday',
			'meta_compare' => 'EXISTS',
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
		if ( ! ( $user instanceof WP_User ) || ! is_email( $user->user_email ) ) {
			continue;
		}

		$sent = bsc_send_email_from_template(
			(string) $user->user_email,
			'Feliz cumpleaños de parte de BSC',
			'bsc-birthday-email.php',
			array(
				'user'     => $user,
				'shop_url' => bsc_get_email_shop_url(),
			)
		);

		if ( $sent ) {
			update_user_meta( (int) $user_id, '_bsc_birthday_email_year', $current_year );
			++$sent_count;
		}
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

	$threshold_days = max( 1, (int) bsc_get_followup_email_setting( 'bsc_inactive_email_days' ) );
	$cutoff         = current_time( 'timestamp' ) - ( $threshold_days * DAY_IN_SECONDS );
	$latest_orders  = array();
	$sent_count     = 0;

	foreach ( bsc_get_followup_orders_desc() as $order ) {
		$contact_key = bsc_get_followup_contact_key( $order );
		if ( isset( $latest_orders[ $contact_key ] ) ) {
			continue;
		}

		$latest_orders[ $contact_key ] = $order;
	}

	foreach ( $latest_orders as $order ) {
		$date_created = $order->get_date_created();
		if ( ! $date_created || $date_created->getTimestamp() > $cutoff ) {
			continue;
		}

		if ( $order->get_meta( '_bsc_inactivity_email_sent_at', true ) ) {
			continue;
		}

		$recipient = bsc_get_followup_order_recipient( $order );
		if ( $recipient['email'] === '' ) {
			continue;
		}

		$sent = bsc_send_email_from_template(
			$recipient['email'],
			'Te extrañamos en Bubble Skin Care',
			'bsc-followup-inactive.php',
			array(
				'customer_name'   => $recipient['name'],
				'order'           => $order,
				'last_order_date' => bsc_get_followup_date_label( $date_created->getTimestamp() ),
				'shop_url'        => bsc_get_email_shop_url(),
				'account_url'     => bsc_get_email_account_url(),
			)
		);

		if ( $sent ) {
			$order->update_meta_data( '_bsc_inactivity_email_sent_at', current_time( 'mysql' ) );
			$order->save_meta_data();
			++$sent_count;
		}
	}

	return $sent_count;
}

function bsc_process_repurchase_followup_emails(): int {
	if ( ! bsc_is_followup_emails_enabled() || ! (bool) bsc_get_followup_email_setting( 'bsc_repurchase_email_enabled' ) ) {
		return 0;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$now              = current_time( 'timestamp' );
	$latest_purchases = array();
	$grouped_payloads = array();
	$sent_count       = 0;

	foreach ( bsc_get_followup_orders_desc() as $order ) {
		$contact_key = bsc_get_followup_contact_key( $order );
		$ordered_at  = $order->get_date_created();

		if ( ! $ordered_at ) {
			continue;
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$product_id = (int) $item->get_product_id();
			if ( $product_id <= 0 ) {
				continue;
			}

			$record_key = $contact_key . ':' . $product_id;
			if ( isset( $latest_purchases[ $record_key ] ) ) {
				continue;
			}

			$latest_purchases[ $record_key ] = array(
				'contact_key' => $contact_key,
				'order'       => $order,
				'product_id'  => $product_id,
				'ordered_at'  => $ordered_at->getTimestamp(),
				'recipient'   => bsc_get_followup_order_recipient( $order ),
			);
		}
	}

	foreach ( $latest_purchases as $purchase ) {
		$product_id = (int) $purchase['product_id'];
		$order      = $purchase['order'];
		$days       = bsc_get_product_repurchase_days( $product_id );

		if ( ( $purchase['ordered_at'] + ( $days * DAY_IN_SECONDS ) ) > $now ) {
			continue;
		}

		$reminded_products = array_map( 'intval', (array) $order->get_meta( '_bsc_repurchase_reminded_products', true ) );
		if ( in_array( $product_id, $reminded_products, true ) ) {
			continue;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			continue;
		}

		$recipient = $purchase['recipient'];
		if ( $recipient['email'] === '' ) {
			continue;
		}

		$contact_key = (string) $purchase['contact_key'];
		if ( ! isset( $grouped_payloads[ $contact_key ] ) ) {
			$grouped_payloads[ $contact_key ] = array(
				'email'    => $recipient['email'],
				'name'     => $recipient['name'],
				'products' => array(),
				'marks'    => array(),
			);
		}

		$grouped_payloads[ $contact_key ]['products'][ $product_id ] = array(
			'id'         => $product_id,
			'name'       => $product->get_name(),
			'url'        => get_permalink( $product_id ),
			'image_url'  => get_the_post_thumbnail_url( $product_id, 'thumbnail' ),
			'ordered_at' => bsc_get_followup_date_label( (int) $purchase['ordered_at'] ),
			'days'       => $days,
		);

		$grouped_payloads[ $contact_key ]['marks'][ $order->get_id() ][] = $product_id;
	}

	foreach ( $grouped_payloads as $payload ) {
		$sent = bsc_send_email_from_template(
			$payload['email'],
			'Es momento de reponer tu rutina BSC',
			'bsc-followup-repurchase.php',
			array(
				'customer_name' => $payload['name'],
				'products'      => array_values( $payload['products'] ),
				'shop_url'      => bsc_get_email_shop_url(),
				'account_url'   => bsc_get_email_account_url(),
			)
		);

		if ( ! $sent ) {
			continue;
		}

		foreach ( $payload['marks'] as $order_id => $product_ids ) {
			$order = wc_get_order( (int) $order_id );
			if ( ! $order ) {
				continue;
			}

			$existing = array_map( 'intval', (array) $order->get_meta( '_bsc_repurchase_reminded_products', true ) );
			$updated  = array_values( array_unique( array_merge( $existing, array_map( 'intval', $product_ids ) ) ) );
			$order->update_meta_data( '_bsc_repurchase_reminded_products', $updated );
			$order->save_meta_data();
		}

		++$sent_count;
	}

	return $sent_count;
}

function bsc_run_followup_email_jobs(): array {
	$summary = array(
		'ran_at'     => current_time( 'mysql' ),
		'birthday'   => 0,
		'inactive'   => 0,
		'repurchase' => 0,
		'module_on'  => bsc_is_followup_emails_enabled() ? 1 : 0,
	);

	if ( ! bsc_is_followup_emails_enabled() ) {
		update_option( 'bsc_followup_email_last_run_summary', $summary, false );
		return $summary;
	}

	$summary['birthday']   = bsc_process_birthday_followup_emails();
	$summary['inactive']   = bsc_process_inactivity_followup_emails();
	$summary['repurchase'] = bsc_process_repurchase_followup_emails();

	update_option( 'bsc_followup_email_last_run_summary', $summary, false );

	return $summary;
}
add_action( 'bsc_run_daily_followup_emails', 'bsc_run_followup_email_jobs' );
