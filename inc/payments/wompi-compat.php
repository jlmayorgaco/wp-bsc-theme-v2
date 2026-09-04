<?php
/**
 * Wompi gateway compatibility fixes.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

add_action( 'woocommerce_receipt_wompi', 'bsc_render_wompi_receipt', 0 );
add_action( 'template_redirect', 'bsc_maybe_complete_wompi_return', 5 );
add_filter( 'woocommerce_gateway_title', 'bsc_filter_wompi_gateway_title', 20, 2 );
add_filter( 'woocommerce_gateway_icon', 'bsc_filter_wompi_gateway_icon', 20, 2 );
add_filter( 'woocommerce_gateway_description', 'bsc_filter_wompi_gateway_description', 20, 2 );

/**
 * Check whether a gateway ID belongs to Wompi.
 *
 * @param mixed $gateway_id Gateway ID.
 * @return bool
 */
function bsc_is_wompi_gateway_id( $gateway_id ): bool {
	return in_array( (string) $gateway_id, array( 'wompi', 'wompi_wwp' ), true );
}

/**
 * Keep Wompi branding out of customer-facing payment method labels.
 *
 * @return bool
 */
function bsc_should_hide_wompi_gateway_branding(): bool {
	return ! is_admin() || wp_doing_ajax();
}

/**
 * Replace the Wompi checkout label with a generic customer-facing label.
 *
 * @param string $title      Gateway title.
 * @param string $gateway_id Gateway ID.
 * @return string
 */
function bsc_filter_wompi_gateway_title( $title, $gateway_id ): string {
	if ( bsc_should_hide_wompi_gateway_branding() && bsc_is_wompi_gateway_id( $gateway_id ) ) {
		return __( 'Pago en linea', 'bsc-2-0' );
	}

	return (string) $title;
}

/**
 * Remove the Wompi logo from customer-facing gateway output.
 *
 * @param string $icon       Gateway icon HTML.
 * @param string $gateway_id Gateway ID.
 * @return string
 */
function bsc_filter_wompi_gateway_icon( $icon, $gateway_id ): string {
	if ( bsc_should_hide_wompi_gateway_branding() && bsc_is_wompi_gateway_id( $gateway_id ) ) {
		return '';
	}

	return (string) $icon;
}

/**
 * Remove the default "Pay via Wompi gateway" checkout description.
 *
 * @param string $description Gateway description.
 * @param string $gateway_id  Gateway ID.
 * @return string
 */
function bsc_filter_wompi_gateway_description( $description, $gateway_id ): string {
	if ( bsc_should_hide_wompi_gateway_branding() && bsc_is_wompi_gateway_id( $gateway_id ) ) {
		return '';
	}

	return (string) $description;
}

/**
 * Render Wompi checkout using one WidgetCheckout instance.
 *
 * The official plugin renders both a data-render script and a WidgetCheckout
 * instance. BSC uses the custom WidgetCheckout path so the order-pay page owns
 * a single widget bootstrap and keeps the payment retry button predictable.
 *
 * @param int $order_id WooCommerce order ID.
 * @return void
 */
function bsc_render_wompi_receipt( int $order_id ): void {
	bsc_remove_official_wompi_receipt_callback();

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		wc_print_notice( __( 'No pudimos encontrar el pedido para iniciar el pago.', 'bsc-2-0' ), 'error' );
		return;
	}

	$gateway = bsc_get_wompi_gateway();
	if ( ! $gateway ) {
		wc_print_notice( __( 'El pago en linea no esta disponible en este momento.', 'bsc-2-0' ), 'error' );
		return;
	}

	bsc_remove_official_wompi_receipt_callback();

	$public_key    = bsc_get_wompi_gateway_public_key( $gateway );
	$integrity_key = bsc_get_wompi_gateway_integrity_key( $gateway );

	if ( '' === $public_key || '' === $integrity_key ) {
		wc_print_notice( __( 'No pudimos iniciar el pago en linea en este momento.', 'bsc-2-0' ), 'error' );
		return;
	}

	$amount_in_cents = max( 0, (int) round( (float) $order->get_total() * 100 ) );
	$currency        = $order->get_currency();
	$signature       = hash( 'sha256', "{$order_id}{$amount_in_cents}{$currency}{$integrity_key}" );
	$redirect_url    = $order->get_checkout_order_received_url();

	$wompi_data = array(
		'currency'        => $currency,
		'amountInCents'   => $amount_in_cents,
		'reference'       => (string) $order_id,
		'publicKey'       => $public_key,
		'signature'       => $signature,
		'redirectUrl'     => $redirect_url,
		'checkoutBaseUrl' => 'https://checkout.wompi.co',
	);

	wp_enqueue_script( 'bsc-wompi-widget', 'https://checkout.wompi.co/widget.js', array(), '1.0.0', true );
	wp_add_inline_script( 'bsc-wompi-widget', 'window.bscWompiData = ' . wp_json_encode( $wompi_data ) . ';', 'before' );
	wp_add_inline_script( 'bsc-wompi-widget', bsc_get_wompi_widget_inline_script() );

	?>
	<div id="wompi-button" class="wompi-button-holder bsc-wompi-receipt">
		<button type="button" class="button alt bsc-wompi-receipt__button" data-bsc-wompi-open>
			<?php esc_html_e( 'Pagar ahora', 'bsc-2-0' ); ?>
		</button>
	</div>
	<?php
}

/**
 * Remove the official Wompi receipt renderer after the gateway registers it.
 *
 * @return void
 */
function bsc_remove_official_wompi_receipt_callback(): void {
	global $wp_filter;

	$hook_name = 'woocommerce_receipt_wompi';
	if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
		return;
	}

	foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$function = $callback['function'] ?? null;
			if ( ! is_array( $function ) || 'generate_wompi_widget' !== ( $function[1] ?? null ) ) {
				continue;
			}

			$object = $function[0] ?? null;
			if ( ! is_object( $object ) || ! is_a( $object, 'Wompi_Portal_Pagos_Gateway_Custom' ) ) {
				continue;
			}

			remove_action( $hook_name, array( $object, 'generate_wompi_widget' ), (int) $priority );
		}
	}
}

/**
 * Get the active Wompi gateway instance.
 *
 * @return WC_Payment_Gateway|null
 */
function bsc_get_wompi_gateway(): ?WC_Payment_Gateway {
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return null;
	}

	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = $gateways['wompi'] ?? null;

	return $gateway instanceof WC_Payment_Gateway ? $gateway : null;
}

/**
 * Get the configured Wompi public key from the gateway.
 *
 * @param WC_Payment_Gateway $gateway Wompi gateway.
 * @return string
 */
function bsc_get_wompi_gateway_public_key( WC_Payment_Gateway $gateway ): string {
	if ( isset( $gateway->public_key ) && is_string( $gateway->public_key ) ) {
		return trim( $gateway->public_key );
	}

	if ( class_exists( 'Wompi_Portal_Pagos_Main' ) ) {
		$testmode = Wompi_Portal_Pagos_Main::get_setting( 'testmode' );
		$key_name = 'yes' === $testmode ? 'test_public_key' : 'public_key';
		return trim( (string) Wompi_Portal_Pagos_Main::get_setting( $key_name, '' ) );
	}

	return '';
}

/**
 * Get the configured Wompi integrity key from the gateway.
 *
 * @param WC_Payment_Gateway $gateway Wompi gateway.
 * @return string
 */
function bsc_get_wompi_gateway_integrity_key( WC_Payment_Gateway $gateway ): string {
	if ( isset( $gateway->integrity_key ) && is_string( $gateway->integrity_key ) ) {
		return trim( $gateway->integrity_key );
	}

	if ( class_exists( 'Wompi_Portal_Pagos_Main' ) ) {
		$testmode = Wompi_Portal_Pagos_Main::get_setting( 'testmode' );
		$key_name = 'yes' === $testmode ? 'test_integrity_key' : 'integrity_key';
		return trim( (string) Wompi_Portal_Pagos_Main::get_setting( $key_name, '' ) );
	}

	return '';
}

/**
 * Return the Wompi checkout bootstrap script.
 *
 * @return string
 */
function bsc_get_wompi_widget_inline_script(): string {
	return <<<'JS'
(function () {
	var data = window.bscWompiData || {};
	var autoOpened = false;

	function createCheckout() {
		if (typeof window.WidgetCheckout !== 'function') {
			return null;
		}

		return new window.WidgetCheckout({
			currency: data.currency,
			amountInCents: data.amountInCents,
			reference: data.reference,
			publicKey: data.publicKey,
			signature: { integrity: data.signature },
			redirectUrl: data.redirectUrl
		});
	}

	function openCheckout() {
		var checkout = createCheckout();
		if (!checkout) {
			return;
		}

		checkout.open(function (result) {
			var transaction = result && result.transaction;
			if (!transaction || !transaction.id) {
				return;
			}
			var redirectUrl = (transaction && transaction.redirectUrl) || data.redirectUrl;

			if (redirectUrl) {
				window.location.href = appendReturnParams(redirectUrl, transaction || {});
			}
		});
	}

	function appendReturnParams(url, transaction) {
		try {
			var target = new URL(url, window.location.href);
			target.searchParams.set('bsc_wompi_return', '1');

			if (transaction.id) {
				target.searchParams.set('bsc_wompi_transaction_id', transaction.id);
			}

			if (transaction.status) {
				target.searchParams.set('bsc_wompi_status', transaction.status);
			}

			return target.toString();
		} catch (error) {
			return url;
		}
	}

	function boot() {
		var buttons = document.querySelectorAll('[data-bsc-wompi-open]');
		buttons.forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault();
				openCheckout();
			});
		});

		if (!autoOpened) {
			autoOpened = true;
			openCheckout();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot, { once: true });
	} else {
		boot();
	}
}());
JS;
}

function bsc_maybe_complete_wompi_return(): void {
	if ( is_admin() || empty( $_GET['bsc_wompi_return'] ) ) {
		return;
	}

	$order_id       = absint( get_query_var( 'order-received' ) );
	$transaction_id = bsc_get_wompi_return_query_value( array( 'bsc_wompi_transaction_id', 'id', 'transaction_id' ) );
	$order_key      = bsc_get_wompi_return_query_value( array( 'key' ) );

	if ( ! $order_id || ! $transaction_id || ! $order_key ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || 'wompi' !== $order->get_payment_method() ) {
		return;
	}

	if ( ! hash_equals( $order->get_order_key(), $order_key ) ) {
		return;
	}

	if ( $order->is_paid() && $order->get_transaction_id() === $transaction_id ) {
		if ( function_exists( 'bsc_send_order_email' ) ) {
			bsc_send_order_email( $order_id, 'processing' );
		}
		return;
	}

	$transaction = bsc_get_wompi_transaction_for_return( $transaction_id );
	if ( ! bsc_wompi_transaction_matches_order( $transaction, $order ) ) {
		return;
	}

	bsc_update_order_from_wompi_transaction( $order, $transaction );
	$order->payment_complete( $transaction_id );
	$order->add_order_note( 'Pago Wompi verificado en retorno del checkout. Transaction ID: ' . $transaction_id );
}

function bsc_get_wompi_return_query_value( array $keys ): string {
	foreach ( $keys as $key ) {
		if ( empty( $_GET[ $key ] ) || is_array( $_GET[ $key ] ) ) {
			continue;
		}

		return sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) );
	}

	return '';
}

function bsc_get_wompi_transaction_for_return( string $transaction_id ): ?object {
	if ( ! class_exists( 'Wompi_Portal_Pagos_API' ) ) {
		return null;
	}

	$response = Wompi_Portal_Pagos_API::instance()->request(
		'GET',
		'/transactions/' . rawurlencode( $transaction_id ),
		null,
		true
	);

	if ( ! is_object( $response ) || empty( $response->data ) || ! is_object( $response->data ) ) {
		return null;
	}

	return $response->data;
}

function bsc_wompi_transaction_matches_order( ?object $transaction, WC_Order $order ): bool {
	if ( ! $transaction ) {
		return false;
	}

	if ( (string) ( $transaction->status ?? '' ) !== 'APPROVED' ) {
		return false;
	}

	if ( (string) ( $transaction->reference ?? '' ) !== (string) $order->get_id() ) {
		return false;
	}

	$expected_amount = max( 0, (int) round( (float) $order->get_total() * 100 ) );
	if ( (int) ( $transaction->amount_in_cents ?? 0 ) !== $expected_amount ) {
		return false;
	}

	$currency = (string) ( $transaction->currency ?? '' );
	if ( $currency && $currency !== $order->get_currency() ) {
		return false;
	}

	return true;
}

function bsc_update_order_from_wompi_transaction( WC_Order $order, object $transaction ): void {
	$order->set_transaction_id( (string) ( $transaction->id ?? '' ) );

	if ( empty( $order->get_billing_email() ) && ! empty( $transaction->customer_email ) ) {
		$order->set_billing_email( sanitize_email( (string) $transaction->customer_email ) );
	}

	if (
		empty( $order->get_billing_first_name() )
		&& ! empty( $transaction->customer_data )
		&& is_object( $transaction->customer_data )
		&& ! empty( $transaction->customer_data->full_name )
	) {
		$order->set_billing_first_name( sanitize_text_field( (string) $transaction->customer_data->full_name ) );
	}

	if (
		empty( $order->get_billing_phone() )
		&& ! empty( $transaction->customer_data )
		&& is_object( $transaction->customer_data )
		&& ! empty( $transaction->customer_data->phone_number )
	) {
		$order->set_billing_phone( sanitize_text_field( (string) $transaction->customer_data->phone_number ) );
	}

	if ( class_exists( 'Wompi_Portal_Pagos_Main' ) && ! empty( $transaction->payment_method_type ) ) {
		$order->update_meta_data(
			Wompi_Portal_Pagos_Main::FIELD_PAYMENT_METHOD_TYPE,
			sanitize_text_field( (string) $transaction->payment_method_type )
		);
	}

	$order->save();
}
