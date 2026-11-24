<?php
/** Partial payments for Showcase orders that include products by request. */
defined( 'ABSPATH' ) || exit;

final class BSC_Showroom_Deposits {
	public const STATUS = 'abono-pendiente';

	public static function methods(): array {
		return array( 'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta' );
	}

	public static function is_deposit_order( WC_Order $order ): bool {
		return '1' === (string) $order->get_meta( '_bsc_is_showroom_sale' )
			&& (int) $order->get_meta( '_bsc_showroom_initial_paid_at' ) > 0;
	}

	/** Validate against the actual line totals after coupon and tax calculation. */
	public static function save_initial_payment( WC_Order $order, array $deposits, string $regular_method ) {
		$pending = 0.0;
		$abonos = 0;
		$has_regular_items = false;
		foreach ( $deposits as $item_id => $deposit ) {
			$item = $order->get_item( $item_id, false );
			$encargo = 'encargo' === $item->get_meta( '_bsc_stock_source' );
			$amount = $deposit['amount'];
			$method = $deposit['method'];
			$line_total = round( (float) $item->get_total() + (float) $item->get_total_tax(), wc_get_price_decimals() );
			if ( ! $encargo ) {
				$has_regular_items = true;
			}
			if ( ! is_int( $amount ) || $amount < 0 || $amount > $line_total
				|| ( $amount > 0 && ( ! $encargo || ! is_string( $method ) || ! isset( self::methods()[ $method ] ) ) ) ) {
				return new WP_Error( 'invalid_showroom_deposit', sprintf( 'Revisa el abono de %s. Ingresa pesos enteros y un medio de pago válido. El abono no puede superar el total de la línea.', $item->get_name() ) );
			}
			if ( $encargo ) {
				$pending += $line_total - $amount;
				if ( $amount > 0 ) {
					$abonos += $amount;
					$item->update_meta_data( '_bsc_showroom_deposit_amount', $amount );
					$item->update_meta_data( '_bsc_showroom_deposit_method', $method );
					$item->save_meta_data();
				}
			}
		}
		if ( $abonos <= 0 ) {
			return new WP_Error( 'missing_showroom_deposit', 'Ingresa un abono mayor a cero en al menos un producto de Encargo.' );
		}
		if ( $has_regular_items && ! isset( self::methods()[ $regular_method ] ) ) {
			return new WP_Error( 'missing_showroom_regular_method', 'Selecciona el medio de pago de los productos de Bodega o Tienda.' );
		}
		$order->update_meta_data( '_bsc_showroom_initial_paid', wc_format_decimal( (float) $order->get_total() - $pending, wc_get_price_decimals() ) );
		$order->update_meta_data( '_bsc_showroom_initial_paid_at', time() );
		if ( $has_regular_items ) {
			$order->update_meta_data( '_bsc_showroom_regular_method', $regular_method );
		}
		return true;
	}

	public static function balance( WC_Order $order ): float {
		if ( ! self::is_deposit_order( $order ) ) {
			return 0.0;
		}
		$final = (array) $order->get_meta( '_bsc_showroom_final_payment' );
		return max( 0, round(
			(float) $order->get_total() - (float) $order->get_meta( '_bsc_showroom_initial_paid' ) - (float) ( $final['amount'] ?? 0 ),
			wc_get_price_decimals()
		) );
	}

	public static function record_final_payment( int $order_id, string $expected_balance, string $method, string $comment ) {
		return BSC_DB_Lock::run( 'stock-order:' . $order_id, static function () use ( $order_id, $expected_balance, $method, $comment ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || ! self::is_deposit_order( $order ) ) {
				return new WP_Error( 'invalid_showroom_order', 'No se encontró una orden de Showcase con abono.' );
			}
			$final = $order->get_meta( '_bsc_showroom_final_payment' );
			if ( $final && $order->has_status( 'completed' ) ) {
				return $order;
			}
			if ( ! $order->has_status( self::STATUS ) ) {
				return new WP_Error( 'invalid_showroom_status', 'La orden no está pendiente de saldo.' );
			}
			$balance = self::balance( $order );
			if ( $balance > 0 ) {
				if ( $final ) {
					return new WP_Error( 'showroom_balance_changed', 'El total del pedido cambió después de registrar el pago final. Revisa el pedido antes de cerrarlo.' );
				}
				if ( ! is_numeric( $expected_balance ) || (float) $expected_balance !== $balance || ! isset( self::methods()[ $method ] ) ) {
					return new WP_Error( 'invalid_showroom_payment', 'Revisa el saldo actualizado de la orden y selecciona el medio de pago.' );
				}
				$final = array(
					'amount' => wc_format_decimal( $balance, wc_get_price_decimals() ),
					'method' => $method,
					'comment' => $comment,
					'paid_at' => time(),
					'user_id' => get_current_user_id(),
				);
				$order->update_meta_data( '_bsc_showroom_final_payment', $final );
				$order->save_meta_data();
			}
			$order->set_date_paid( $final['paid_at'] ?? time() );
			if ( ! $order->update_status( 'completed', 'Saldo de Showcase saldado desde BSC Admin.' ) || ! $order->has_status( 'completed' ) ) {
				return new WP_Error( 'showroom_close_failed', 'El pago quedó guardado. Vuelve a abrir la orden para finalizar el cierre.' );
			}
			return $order;
		} );
	}

	/** Keep manual status changes and integrations from marking a balance as paid. */
	public static function protect_unpaid_status( WC_Order $order ): void {
		// Creation saves a pending order before its stock and payment data are committed.
		$staged = $order->has_status( 'pending' ) && ! isset( $order->get_changes()['status'] );
		if ( ! $staged && self::balance( $order ) > 0 && ! $order->has_status( array( self::STATUS, 'cancelled', 'failed', 'refunded' ) ) ) {
			$order->set_status( self::STATUS );
			$order->set_date_paid( null );
			$order->set_date_completed( null );
		}
	}
}

add_action( 'woocommerce_before_order_object_save', array( BSC_Showroom_Deposits::class, 'protect_unpaid_status' ) );
