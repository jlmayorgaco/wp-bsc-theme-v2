<?php
/** View and settle a Showcase order from BSC Admin → Pedidos. */
defined( 'ABSPATH' ) || exit;

function bsc_showroom_deposit_order_url( int $order_id ): string {
	return add_query_arg( 'bsc_abono_order', $order_id, admin_url( 'admin.php?page=bsc-orders' ) );
}

function bsc_render_showroom_deposit_order( int $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! BSC_Showroom_Deposits::is_deposit_order( $order ) ) {
		wp_die( 'No se encontró una orden de Showcase con abono.', '', array( 'response' => 404, 'back_link' => true ) );
	}

	$methods = BSC_Showroom_Deposits::methods();
	$currency = array( 'currency' => $order->get_currency() );
	$balance = BSC_Showroom_Deposits::balance( $order );
	$final = (array) $order->get_meta( '_bsc_showroom_final_payment' );
	$initial_at = (int) $order->get_meta( '_bsc_showroom_initial_paid_at' );
	$regular_method = $methods[ $order->get_meta( '_bsc_showroom_regular_method' ) ] ?? '';
	?>
	<div class="wrap bsc-admin-orders">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=bsc-orders' ) ); ?>">Volver a pedidos</a>
		<h1>Pagos del pedido #<?php echo esc_html( $order->get_order_number() ); ?></h1>
		<p><strong><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></strong></p>
		<p>Total del pedido <?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></p>
		<p>Pago inicial <?php echo wp_kses_post( wc_price( $order->get_meta( '_bsc_showroom_initial_paid' ), $currency ) ); ?>
			<?php echo $initial_at ? esc_html( ' · ' . wp_date( 'd/m/Y H:i', $initial_at ) ) : ''; ?>
		</p>
		<?php if ( $regular_method ) : ?>
			<p>Medio de pago de productos de Bodega o Tienda: <?php echo esc_html( $regular_method ); ?></p>
		<?php endif; ?>
		<table class="widefat striped">
			<thead><tr><th scope="col">Producto de Encargo</th><th scope="col">Abono</th><th scope="col">Medio de pago</th></tr></thead>
			<tbody>
			<?php foreach ( $order->get_items() as $item ) : ?>
				<?php if ( 'encargo' !== $item->get_meta( '_bsc_stock_source' ) ) { continue; } ?>
				<tr>
					<td><?php echo esc_html( $item->get_quantity() . ' × ' . $item->get_name() . ' ' . $item->get_meta( 'Variante' ) ); ?></td>
					<td><?php echo wp_kses_post( wc_price( (float) $item->get_meta( '_bsc_showroom_deposit_amount' ), $currency ) ); ?></td>
					<td><?php echo esc_html( $methods[ $item->get_meta( '_bsc_showroom_deposit_method' ) ] ?? '—' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<h2>Saldo pendiente <?php echo wp_kses_post( wc_price( $balance, $currency ) ); ?></h2>
		<?php if ( ! empty( $final['paid_at'] ) ) : ?>
			<h2>Pago final registrado</h2>
			<p>
				<?php echo wp_kses_post( wc_price( $final['amount'], $currency ) ); ?>
				<?php echo esc_html( ' · ' . ( $methods[ $final['method'] ] ?? '' ) . ' · ' . wp_date( 'd/m/Y H:i', (int) $final['paid_at'] ) ); ?>
			</p>
			<?php if ( ! empty( $final['comment'] ) ) : ?>
				<p><?php echo nl2br( esc_html( $final['comment'] ) ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $order->has_status( BSC_Showroom_Deposits::STATUS ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'bsc_showroom_final_payment_' . $order_id ); ?>
				<input type="hidden" name="action" value="bsc_showroom_final_payment">
				<input type="hidden" name="order_id" value="<?php echo esc_attr( $order_id ); ?>">
				<input type="hidden" name="balance" value="<?php echo esc_attr( wc_format_decimal( $balance, wc_get_price_decimals() ) ); ?>">
				<?php if ( $balance > 0 ) : ?>
					<p>
						<label for="bsc-final-payment-method">Medio de pago</label>
						<select id="bsc-final-payment-method" name="payment_method" required>
							<option value="">Selecciona un medio</option>
							<?php foreach ( $methods as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label for="bsc-final-payment-comment">Comentario (opcional)</label><br>
						<textarea id="bsc-final-payment-comment" name="payment_comment" class="large-text" rows="3" maxlength="1000"></textarea>
					</p>
				<?php endif; ?>
				<p><label><input type="checkbox" name="shipment_confirmed" value="1" required> El pedido ya fue despachado.</label></p>
				<button class="button button-primary" type="submit"><?php echo $balance > 0 ? 'Registrar pago final y cerrar' : 'Cerrar pedido'; ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_bsc_showroom_final_payment', 'bsc_handle_showroom_final_payment' );
function bsc_handle_showroom_final_payment(): void {
	if ( ! bsc_current_user_has_bsc_page_access( 'bsc-orders' )
		|| ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'edit_orders' ) ) ) {
		wp_die( 'No tienes permisos para registrar pagos.', '', array( 'response' => 403 ) );
	}
	$order_id = absint( wp_unslash( $_POST['order_id'] ?? 0 ) );
	check_admin_referer( 'bsc_showroom_final_payment_' . $order_id );
	$comment = sanitize_textarea_field( wp_unslash( $_POST['payment_comment'] ?? '' ) );
	if ( '1' !== ( $_POST['shipment_confirmed'] ?? '' ) || mb_strlen( $comment ) > 1000 ) {
		wp_die( 'Confirma el despacho y revisa la longitud del comentario.', '', array( 'response' => 400, 'back_link' => true ) );
	}
	$result = BSC_Showroom_Deposits::record_final_payment(
		$order_id,
		sanitize_text_field( wp_unslash( $_POST['balance'] ?? '' ) ),
		sanitize_key( wp_unslash( $_POST['payment_method'] ?? '' ) ),
		$comment
	);
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 409, 'back_link' => true ) );
	}
	wp_safe_redirect( bsc_showroom_deposit_order_url( $order_id ) );
	exit;
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'bsc_showroom_render_deposit_order_link' );
function bsc_showroom_render_deposit_order_link( WC_Order $order ): void {
	if ( BSC_Showroom_Deposits::is_deposit_order( $order ) && bsc_current_user_has_bsc_page_access( 'bsc-orders' ) ) {
		echo '<p><a href="' . esc_url( bsc_showroom_deposit_order_url( $order->get_id() ) ) . '">Ver pagos de Showcase</a></p>';
	}
}
