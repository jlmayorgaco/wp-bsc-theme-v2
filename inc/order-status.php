<?php
/**
 * Shared BSC order status presentation helpers.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the customer and admin presentation for a WooCommerce order status.
 *
 * @param string $status      WooCommerce status slug, with or without wc-.
 * @param bool   $is_archived Whether the BSC order archive flag is set.
 * @return array{key:string,label:string,class:string}
 */
function bsc_get_order_status_display( string $status, bool $is_archived = false ): array {
	if ( $is_archived ) {
		return array(
			'key'   => 'bsc-archived',
			'label' => 'Archivado',
			'class' => 'bsc-archived',
		);
	}

	$status = preg_replace( '/^wc-/', '', $status );
	$map    = array(
		'pending'    => array( 'Pendiente de pago', 'pending' ),
		'on-hold'    => array( 'Pago por confirmar', 'on-hold' ),
		'processing' => array( 'Recibido', 'processing' ),
		'preparing'  => array( 'Recibido', 'processing' ),
		'shipped'    => array( 'Enviado', 'shipped' ),
		'completed'  => array( 'Enviado', 'shipped' ),
		'cancelled'  => array( 'Cancelado', 'cancelled' ),
		'failed'     => array( 'Cancelado', 'cancelled' ),
		'refunded'   => array( 'Cancelado', 'cancelled' ),
	);
	$display = $map[ $status ] ?? array( 'Estado desconocido', 'unknown' );

	return array(
		'key'   => 'wc-' . $display[1],
		'label' => $display[0],
		'class' => $display[1],
	);
}

/**
 * Return accurate confirmation-page copy for the current payment state.
 *
 * @param string $status       WooCommerce status slug.
 * @param string $order_number Public order number.
 * @return array{title:string,message:string}
 */
function bsc_get_order_confirmation_copy( string $status, string $order_number ): array {
	$status       = preg_replace( '/^wc-/', '', $status );
	$order_number = esc_html( $order_number );

	if ( 'pending' === $status ) {
		return array(
			'title'   => 'Pago pendiente',
			'message' => 'Tu pedido <strong>#' . $order_number . '</strong> fue creado, pero el pago aún no está confirmado.',
		);
	}

	if ( 'on-hold' === $status ) {
		return array(
			'title'   => 'Pago por confirmar',
			'message' => 'Estamos verificando el pago de tu pedido <strong>#' . $order_number . '</strong>. Te avisaremos cuando sea confirmado.',
		);
	}

	if ( 'failed' === $status ) {
		return array(
			'title'   => 'Pago no completado',
			'message' => 'El pago del pedido <strong>#' . $order_number . '</strong> no se completó. Puedes intentarlo nuevamente.',
		);
	}

	if ( 'cancelled' === $status || 'refunded' === $status ) {
		return array(
			'title'   => 'cancelled' === $status ? 'Pedido cancelado' : 'Pedido reembolsado',
			'message' => 'Tu pedido <strong>#' . $order_number . '</strong> está ' . ( 'cancelled' === $status ? 'cancelado.' : 'reembolsado.' ),
		);
	}

	if ( in_array( $status, array( 'processing', 'preparing', 'shipped', 'completed' ), true ) ) {
		return array(
			'title'   => html_entity_decode( '&#161;Gracias por tu compra! &#127881;', ENT_QUOTES, 'UTF-8' ),
			'message' => 'Tu orden <strong>#' . $order_number . '</strong> ha sido recibida correctamente.',
		);
	}

	return array(
		'title'   => 'Estado del pedido',
		'message' => 'Consulta el estado de tu pedido <strong>#' . $order_number . '</strong> en el detalle de abajo.',
	);
}

/** Map WooCommerce status to the customer order progress bar. */
function bsc_map_order_status_to_bar( string $wc_status ): string {
	require_once get_template_directory() . '/components/orders/order-progress-bar.php';
	$map = array(
		'pending'      => BSC_Order_Progress_Bar::PENDING,
		'on-hold'      => BSC_Order_Progress_Bar::ON_HOLD,
		'processing'   => BSC_Order_Progress_Bar::RECEIVED,
		'preparing'    => BSC_Order_Progress_Bar::RECEIVED,
		'shipped'      => BSC_Order_Progress_Bar::SHIPPED,
		'completed'    => BSC_Order_Progress_Bar::SHIPPED,
		'refunded'     => BSC_Order_Progress_Bar::CANCELLED,
		'cancelled'    => BSC_Order_Progress_Bar::CANCELLED,
		'failed'       => BSC_Order_Progress_Bar::CANCELLED,
		'bsc-archived' => BSC_Order_Progress_Bar::ARCHIVED,
	);
	return $map[ $wc_status ] ?? BSC_Order_Progress_Bar::CANCELLED;
}

function bsc_map_order_to_bar( WC_Order $order ): string {
	require_once get_template_directory() . '/components/orders/order-progress-bar.php';

	if ( $order->get_meta( '_bsc_archived_at', true ) ) {
		return BSC_Order_Progress_Bar::ARCHIVED;
	}

	return bsc_map_order_status_to_bar( $order->get_status() );
}
