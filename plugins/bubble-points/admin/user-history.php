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
			echo '<p>' . esc_html__( 'User not found.', 'bsc' ) . '</p>';
			return;
		}

		// Notices
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after redirect.
		if (isset( $_GET['updated'] )) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Points updated.', 'bsc' ) . '</p></div>';
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin notice flag after redirect.
		$error_message = !empty( $_GET['error'] ) ? sanitize_text_field( wp_unslash( $_GET['error'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		if ('' !== $error_message) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
		}

		$back_url = admin_url( 'admin.php?page=bsc-bubble-points' );
		echo '<a href="' . esc_url( $back_url ) . '" class="button bsc-bp-admin__back-link">&larr; ' . esc_html__( 'Back', 'bsc' ) . '</a>';

		printf(
			'<h2>%s &mdash; %s</h2>',
			esc_html( $user->display_name ),
			esc_html( $user->user_email )
		);

		// Current balance
		$balance = (int) bsc_bp_get_balance( $user_id );
		echo '<p><strong>' . esc_html__( 'Current balance', 'bsc' ) . ':</strong> ' . esc_html( number_format_i18n( $balance ) ) . '</p>';

		// Manual adjustment form (Add / Reduce) with note
		if (current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' )) {
			$action_url = admin_url( 'admin-post.php' ); // form posts here
			?>
		<form method="post" action="<?php echo esc_url( $action_url ); ?>" class="bsc-bp-history-form">
			<input type="hidden" name="action" value="bsc_bp_manual_adjust">
			<input type="hidden" name="user_id" value="<?php echo (int) $user_id; ?>">

				<?php
				// IMPORTANT: field name _bsc_bp_nonce; action string includes the user_id
				wp_nonce_field( 'bsc_bp_manual_adjust_' . $user_id, '_bsc_bp_nonce' );
				?>

			<label for="bsc-bp-amount" class="bsc-bp-history-form__label"><strong><?php esc_html_e( 'Amount', 'bsc' ); ?></strong></label>
			<input id="bsc-bp-amount" type="number" name="amount" step="1" min="0" placeholder="e.g. 100" required />

			<label for="bsc-bp-note" class="bsc-bp-history-form__label bsc-bp-history-form__label--spaced"><strong><?php esc_html_e( 'Note', 'bsc' ); ?></strong></label>
			<input id="bsc-bp-note" type="text" name="note" placeholder="<?php esc_attr_e( 'Optional note', 'bsc' ); ?>" class="bsc-bp-history-form__note" />

			<button class="button button-primary" type="submit" name="op" value="add"><?php esc_html_e( 'Add', 'bsc' ); ?></button>
			<button class="button" type="submit" name="op" value="reduce"><?php esc_html_e( 'Reduce', 'bsc' ); ?></button>
		</form>
			<?php
		}

		// Fetch paginated ledger
		$per_page = 25;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination param.
		$paged = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
		$data  = bsc_bp_get_user_ledger( $user_id, $per_page, $paged ); // must return ['rows'=>[], 'total'=>int]
		$rows  = isset( $data['rows'] ) ? (array) $data['rows'] : array();
		$total = isset( $data['total'] ) ? (int) $data['total'] : 0;

		// Table
		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Date', 'bsc' ) . '</th>';
		echo '<th>' . esc_html__( 'Delta', 'bsc' ) . '</th>';
		echo '<th>' . esc_html__( 'Balance after', 'bsc' ) . '</th>';
		echo '<th>' . esc_html__( 'Type', 'bsc' ) . '</th>';
		echo '<th>' . esc_html__( 'Order', 'bsc' ) . '</th>';
		echo '<th>' . esc_html__( 'Comments', 'bsc' ) . '</th>';
		echo '</tr></thead><tbody>';

		if (empty( $rows )) {
			echo '<tr><td colspan="6">' . esc_html__( 'No movements yet.', 'bsc' ) . '</td></tr>';
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
				$type_label = $order_id ? esc_html__( 'Order', 'bsc' ) : esc_html__( 'Manual', 'bsc' );

				// Order link cell
				if ($order_id) {
					$edit_link  = get_edit_post_link( $order_id );
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

		echo '</tbody></table>';

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
	}
endif; // function_exists
