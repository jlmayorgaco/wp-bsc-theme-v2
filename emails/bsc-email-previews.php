<?php
/**
 * BSC-088: admin-only preview routes and fixtures for branded emails.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_bsc_preview_email', 'bsc_render_email_preview_page' );

function bsc_get_email_preview_definitions(): array {
	return [
		'welcome' => [
			'label'    => 'Usuario nuevo',
			'template' => 'bsc-welcome-email.php',
		],
		'password-reset' => [
			'label'    => 'Recuperar contrasena',
			'template' => 'bsc-password-reset-email.php',
		],
		'birthday' => [
			'label'    => 'Cumpleanos',
			'template' => 'bsc-birthday-email.php',
		],
		'order-confirmed' => [
			'label'    => 'Compra confirmada',
			'template' => 'bsc-order-confirmed.php',
		],
		'order-preparing' => [
			'label'    => 'Pedido en preparacion',
			'template' => 'bsc-order-preparing.php',
		],
		'order-shipped' => [
			'label'    => 'Pedido enviado',
			'template' => 'bsc-order-shipped.php',
		],
		'order-delivered' => [
			'label'    => 'Pedido entregado',
			'template' => 'bsc-order-delivered.php',
		],
		'order-cancelled' => [
			'label'    => 'Pedido cancelado',
			'template' => 'bsc-order-cancelled.php',
		],
		'followup-inactive' => [
			'label'    => 'Hace mucho no compras',
			'template' => 'bsc-followup-inactive.php',
		],
		'followup-repurchase' => [
			'label'    => 'Se te acabo el producto',
			'template' => 'bsc-followup-repurchase.php',
		],
	];
}

function bsc_get_email_preview_url( string $slug ): string {
	$args = [
		'action'   => 'bsc_preview_email',
		'template' => sanitize_key( $slug ),
	];

	if ( is_user_logged_in() ) {
		$args['_wpnonce'] = wp_create_nonce( 'bsc_preview_email_' . sanitize_key( $slug ) );
	}

	return add_query_arg(
		$args,
		admin_url( 'admin-post.php' )
	);
}

function bsc_get_email_preview_customer(): WP_User {
	$email = 'preview.customer@bsc.local';
	$user  = get_user_by( 'email', $email );

	if ( ! $user ) {
		$user_id = wp_insert_user(
			[
				'user_login'   => 'preview_customer_bsc',
				'user_email'   => $email,
				'user_pass'    => wp_generate_password( 24, true, true ),
				'role'         => 'customer',
				'display_name' => 'Preview Customer',
				'first_name'   => 'Preview',
				'last_name'    => 'Customer',
			]
		);

		if ( is_wp_error( $user_id ) ) {
			wp_die( esc_html( $user_id->get_error_message() ) );
		}

		$user = get_user_by( 'id', $user_id );
	}

	update_user_meta( $user->ID, 'billing_first_name', 'Preview' );
	update_user_meta( $user->ID, 'billing_last_name', 'Customer' );
	update_user_meta( $user->ID, 'billing_phone', '3000000000' );
	update_user_meta( $user->ID, 'billing_city', 'Bogota' );
	update_user_meta( $user->ID, 'billing_address_1', 'Calle 123 #45-67' );
	update_user_meta( $user->ID, '_billing_cedula', '1234567890' );
	update_user_meta( $user->ID, 'shipping_first_name', 'Preview' );
	update_user_meta( $user->ID, 'shipping_last_name', 'Customer' );
	update_user_meta( $user->ID, 'shipping_city', 'Bogota' );
	update_user_meta( $user->ID, 'shipping_address_1', 'Calle 123 #45-67' );

	return $user;
}

function bsc_get_email_preview_product(): WC_Product {
	$products = wc_get_products(
		[
			'status'  => 'publish',
			'limit'   => 1,
			'orderby' => 'date',
			'order'   => 'ASC',
			'return'  => 'objects',
		]
	);

	if ( empty( $products ) || ! $products[0] instanceof WC_Product ) {
		wp_die( esc_html__( 'No hay productos publicados para renderizar previews de email.', 'bsc-2-0' ) );
	}

	return $products[0];
}

function bsc_get_email_preview_order(): WC_Order {
	$user = bsc_get_email_preview_customer();
	$orders = wc_get_orders(
		[
			'customer_id' => $user->ID,
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_key'    => '_bsc_email_preview_fixture',
			'meta_value'  => '1',
			'return'      => 'objects',
			'status'      => array_keys( wc_get_order_statuses() ),
		]
	);

	if ( ! empty( $orders ) && $orders[0] instanceof WC_Order ) {
		return $orders[0];
	}

	$product = bsc_get_email_preview_product();
	$order   = wc_create_order(
		[
			'customer_id' => $user->ID,
			'created_via' => 'bsc-email-preview',
		]
	);

	$order->add_product( $product, 1 );
	$order->set_address(
		[
			'first_name' => 'Preview',
			'last_name'  => 'Customer',
			'email'      => $user->user_email,
			'phone'      => '3000000000',
			'address_1'  => 'Calle 123 #45-67',
			'address_2'  => 'Apto 301',
			'city'       => 'Bogota',
			'state'      => 'Bogota',
			'postcode'   => '110111',
			'country'    => 'CO',
		],
		'billing'
	);
	$order->set_address(
		[
			'first_name' => 'Preview',
			'last_name'  => 'Customer',
			'address_1'  => 'Calle 123 #45-67',
			'address_2'  => 'Apto 301',
			'city'       => 'Bogota',
			'state'      => 'Bogota',
			'postcode'   => '110111',
			'country'    => 'CO',
		],
		'shipping'
	);
	$order->set_payment_method( 'bacs' );
	$order->set_payment_method_title( 'Transferencia bancaria' );
	$order->update_meta_data( '_bsc_email_preview_fixture', '1' );
	$order->update_meta_data( '_billing_cedula', '1234567890' );
	$order->calculate_totals();
	$order->save();

	return $order;
}

function bsc_get_email_preview_context( string $slug ): array {
	$user        = bsc_get_email_preview_customer();
	$order       = bsc_get_email_preview_order();
	$shop_url    = home_url( '/shop/' );
	$account_url = wc_get_page_permalink( 'myaccount' );
	$product     = bsc_get_email_preview_product();
	$image_id    = $product->get_image_id();
	$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' ) : '';

	switch ( $slug ) {
		case 'welcome':
			return [
				'user'        => $user,
				'account_url' => $account_url,
				'shop_url'    => $shop_url,
			];

		case 'password-reset':
			return [
				'user'      => $user,
				'reset_url' => wp_lostpassword_url(),
			];

		case 'birthday':
			return [
				'user'     => $user,
				'shop_url' => $shop_url,
			];

		case 'order-shipped':
			return [
				'order'         => $order,
				'tracking_code' => 'BSC-TRK-2026-001',
				'tracking_link' => 'https://example.com/tracking/BSC-TRK-2026-001',
			];

		case 'followup-inactive':
			return [
				'customer_name'   => 'Preview Customer',
				'order'           => $order,
				'last_order_date' => '15 de enero de 2026',
				'shop_url'        => $shop_url,
				'account_url'     => $account_url,
			];

		case 'followup-repurchase':
			return [
				'customer_name' => 'Preview Customer',
				'products'      => [
					[
						'name'       => $product->get_name(),
						'ordered_at' => '15 de enero de 2026',
						'url'        => get_permalink( $product->get_id() ),
						'image_url'  => $image_url,
					],
				],
				'shop_url'      => $shop_url,
				'account_url'   => $account_url,
			];

		case 'order-confirmed':
		case 'order-preparing':
		case 'order-delivered':
		case 'order-cancelled':
		default:
			return [
				'order' => $order,
			];
	}
}

function bsc_render_email_preview_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para previsualizar correos.', 'bsc-2-0' ) );
	}

	$slug        = sanitize_key( (string) ( $_GET['template'] ?? '' ) );
	$definitions = bsc_get_email_preview_definitions();

	if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bsc_preview_email_' . $slug ) ) {
		wp_die( esc_html__( 'Solicitud no válida.', 'bsc-2-0' ) );
	}

	if ( ! isset( $definitions[ $slug ] ) ) {
		wp_die( esc_html__( 'Template de preview no encontrado.', 'bsc-2-0' ) );
	}

	$html = bsc_render_email_template(
		$definitions[ $slug ]['template'],
		bsc_get_email_preview_context( $slug )
	);

	if ( $html === '' ) {
		wp_die( esc_html__( 'No se pudo renderizar el preview del email.', 'bsc-2-0' ) );
	}

	nocache_headers();
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, noarchive, nosnippet', true );
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit;
}
