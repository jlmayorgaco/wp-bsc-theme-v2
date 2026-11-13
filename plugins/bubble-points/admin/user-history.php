<?php
if (!defined( 'ABSPATH' )) {
	exit;
}

/**
 * Renders the Bubble Points history screen for a single user.
 * Depends on:
 *   - bsc_bp_get_user_ledger($user_id, $per_page, $paged) -> ['rows'=>[], 'total'=>int]
 *   - bsc_bp_get_balance($user_id) -> int
 *
 * NOTE: The handler for the form must verify:
 *   wp_verify_nonce( $_POST['_bsc_bp_nonce'], 'bsc_bp_manual_adjust_'.$user_id )
 */
if ( ! function_exists( 'bsc_bp_render_user_history_screen' ) ) :
	function bsc_bp_render_user_history_screen( $user_id ) {
		$user_id = (int) $user_id;
		$user    = get_user_by( 'id', $user_id );

		if (!$user) {
			echo '<p>No se encontró el cliente.</p>';
			return;
		}

		$back_url = admin_url( 'admin.php?page=bsc-bubble-points' );
		$balance  = (int) bsc_bp_get_balance( $user_id );
		?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="button bsc-bp-admin__back-link">&larr; Volver a clientes</a>
		<section class="bsc-admin-panel bsc-bp-admin__section bsc-bp-profile">
			<div>
				<h2 class="bsc-admin-panel__title"><?php echo esc_html( $user->display_name ); ?></h2>
				<p class="bsc-admin-panel__description"><?php echo esc_html( $user->user_email ); ?></p>
			</div>
			<div class="bsc-bp-profile__balance">
				<span>Saldo actual</span>
				<strong class="bsc-bp-balance"><?php echo esc_html( number_format_i18n( $balance ) ); ?></strong>
			</div>
		</section>
		<section class="bsc-admin-panel bsc-bp-admin__section">
			<h2 class="bsc-admin-panel__title">Ajustar saldo</h2>
			<?php bsc_bp_render_adjust_form( $user_id, $user->display_name, $balance ); ?>
		</section>
		<?php

		// Fetch paginated ledger
		$per_page = 25;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination param.
		$paged = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
		$data  = bsc_bp_get_user_ledger( $user_id, $per_page, $paged ); // must return ['rows'=>[], 'total'=>int]
		$rows  = isset( $data['rows'] ) ? (array) $data['rows'] : array();
		$total = isset( $data['total'] ) ? (int) $data['total'] : 0;

		// Table
		echo '<section class="bsc-admin-panel bsc-bp-admin__section"><h2 class="bsc-admin-panel__title">Historial de movimientos</h2><div class="bsc-admin-table-wrap">';
		echo '<table class="wp-list-table widefat striped bsc-bp-history">';
		echo '<thead><tr>';
		echo '<th scope="col">Fecha</th><th scope="col">Movimiento</th><th scope="col">Saldo resultante</th>';
		echo '<th scope="col">Tipo</th><th scope="col">Pedido</th><th scope="col">Comentarios</th>';
		echo '</tr></thead><tbody>';

		if (empty( $rows )) {
			echo '<tr><td colspan="6">Este cliente aún no tiene movimientos.</td></tr>';
		} else {
			foreach ($rows as $row) {
				// Expected $row fields: id, user_id, delta, balance_after, reason, order_id, meta(JSON), created_at
				$created_at = !empty( $row->created_at ) ? $row->created_at : current_time( 'mysql' );
				$date_str   = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $created_at );

				$delta      = (int) ( $row->delta ?? 0 );
				$balance_af = (int) ( $row->balance_after ?? 0 );
				$reason     = (string) ( $row->reason ?? '' );
				$order_id   = !empty( $row->order_id ) ? (int) $row->order_id : 0;
				$meta_json  = (string) ( $row->meta ?? '' );

				// Type
				$type_label = 'manual_adjustment' === $reason ? 'Ajuste manual' : ( $order_id ? 'Pedido' : 'Otro' );

				// Order link cell
				if ($order_id) {
					$order      = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
					$edit_link  = $order ? $order->get_edit_order_url() : '';
					$order_cell = $edit_link
					? '<a href="' . esc_url( $edit_link ) . '">#' . (int) $order_id . '</a>'
					: '#' . (int) $order_id;
				} else {
					$order_cell = '—';
				}

				// Comments from meta['note'] + any other scalars
				$comments = '';
				if ($meta_json !== '') {
					$meta_arr = json_decode( $meta_json, true );
					if (is_array( $meta_arr )) {
						if (isset( $meta_arr['note'] ) && is_scalar( $meta_arr['note'] )) {
							$comments = esc_html( (string) $meta_arr['note'] );
							unset( $meta_arr['note'] );
						}
						if ( 'manual_adjustment' === $reason && ! empty( $meta_arr['admin_id'] ) ) {
							$admin = get_userdata( (int) $meta_arr['admin_id'] );
							$comments .= ( $comments ? ' — ' : '' ) . 'Por ' . esc_html( $admin ? $admin->display_name : '#' . (int) $meta_arr['admin_id'] );
							unset( $meta_arr['admin_id'], $meta_arr['source'], $meta_arr['event_key'] );
						}
						$extras = array();
						foreach ($meta_arr as $k => $v) {
							if (is_scalar( $v )) {
								$extras[] = esc_html( $k ) . ': ' . esc_html( (string) $v );
							}
						}
						if ($extras) {
							$comments .= ( $comments ? ' — ' : '' ) . implode( ', ', $extras );
						}
					}
				}
				if ($comments === '') {
					$comments = '—';
				}

				printf(
					'<tr>
                    <td>%s</td>
                    <td class="%s">%s%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                 </tr>',
					esc_html( $date_str ),
					esc_attr( $delta >= 0 ? 'bsc-bp-history__delta bsc-bp-history__delta--positive' : 'bsc-bp-history__delta bsc-bp-history__delta--negative' ),
					$delta >= 0 ? '+' : '',
					esc_html( number_format_i18n( $delta ) ),
					esc_html( number_format_i18n( $balance_af ) ),
					esc_html( $type_label ),
					wp_kses_post( $order_cell ),
					wp_kses_post( $comments )
				);
			}
		}

		echo '</tbody></table></div>';

		// Pagination
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		if ($total_pages > 1) {
			echo '<div class="tablenav"><div class="tablenav-pages">';
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( array( 'paged'=>'%#%' ) ),
						'format'    => '',
						'current'   => $paged,
						'total'     => $total_pages,
						'prev_text' => esc_html__( '&laquo;', 'bsc' ),
						'next_text' => esc_html__( '&raquo;', 'bsc' ),
					)
				)
			);
			echo '</div></div>';
		}
		echo '</section>';
	}
endif; // function_exists
