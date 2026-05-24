<?php

add_action( 'woocommerce_save_account_details', 'bsc_save_custom_account_fields', 10, 1 );

function bsc_save_custom_account_fields( $user_id ) {
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce validates the save-account-details nonce before this hook runs.
	$fields = array(
		'account_birthday'    => 'bsc_birthday',
		'account_skin_type'   => 'bsc_skin_type',
		'account_sensitivity' => 'bsc_sensitivity',
		'bsc_needs1'          => 'bsc_needs1',
		'bsc_needs2'          => 'bsc_needs2',
		'bsc_needs3'          => 'bsc_needs3',
		'bsc_needs4'          => 'bsc_needs4',
	);

	foreach ($fields as $input_name => $meta_key) {
		if (isset( $_POST[ $input_name ] )) {
			update_user_meta( $user_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) ) );
		}
	}
    // phpcs:enable WordPress.Security.NonceVerification.Missing
}
