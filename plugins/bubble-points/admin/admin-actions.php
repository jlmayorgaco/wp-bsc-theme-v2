<?php


// Inline adjust from All Users table
add_action(
	'admin_post_bsc_bp_inline_adjust',
	function () {
		if (!current_user_can( 'manage_woocommerce' ) && !current_user_can( 'manage_options' )) {
			wp_die( 'No permissions' );
		}

		$user_id  = absint( wp_unslash( $_POST['user_id'] ?? 0 ) );
		$nonce    = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		$nonce_ok = wp_verify_nonce( $nonce, 'bsc_bp_inline_adjust_' . $user_id );
		if (!$nonce_ok) {
			wp_die( 'Bad nonce' );
		}

		$amount = absint( wp_unslash( $_POST['amount'] ?? 0 ) ); // unsigned from UI
		$op     = sanitize_key( wp_unslash( $_POST['op'] ?? 'add' ) ); // 'add' | 'reduce'
		$note   = sanitize_text_field( wp_unslash( $_POST['note'] ?? '' ) );

		if ($user_id > 0 && $amount > 0) {
			$delta = ( $op === 'reduce' ) ? -abs( $amount ) : abs( $amount );

			// Manual adjustment, no order
			$res = bsc_bp_add_ledger_entry(
				$user_id,
				$delta,
				'manual_adjustment',
				null,
				array(
					'admin_id' => get_current_user_id(),
					'note'     => $note,
					'source'   => 'admin_inline',
				)
			);

			$msg = $res['ok'] ? 'updated=1' : 'error=' . rawurlencode( sanitize_key( (string) ( $res['error'] ?? 'unknown' ) ) );
			wp_redirect( admin_url( 'admin.php?page=bsc-bubble-points&' . $msg ) );
			exit;
		}

		wp_redirect( admin_url( 'admin.php?page=bsc-bubble-points' ) );
		exit;
	}
);
