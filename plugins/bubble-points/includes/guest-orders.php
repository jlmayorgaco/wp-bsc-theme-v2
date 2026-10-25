<?php
/**
 * Recover guest purchase benefits after WooCommerce verifies the account email.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Internal\CustomerEmailVerification\EmailVerificationService;
use Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController;

/**
 * WooCommerce links matching guest orders after email verification and emits
 * woocommerce_update_new_customer_past_order for each one. Never link by a typed
 * email at registration: that would expose someone else's purchase history.
 *
 * @param int         $order_id Order linked by WooCommerce.
 * @param WP_User|int $customer Verified customer, or their ID on cron retries.
 */
function bsc_bp_recover_linked_order_points( $order_id, $customer ): void {
	$user_id = is_object( $customer ) ? (int) $customer->ID : absint( $customer );
	$user    = get_user_by( 'id', $user_id );
	if ( ! $user || ! class_exists( EmailVerificationService::class ) || ! wc_get_container()->get( EmailVerificationService::class )->is_verified( $user_id ) ) {
		return;
	}

	$result = BSC_DB_Lock::run(
		'points-order:' . absint( $order_id ),
		static function () use ( $order_id, $user_id, $user ) {
			$order = wc_get_order( absint( $order_id ) );
			if ( ! $order || $user_id !== (int) $order->get_customer_id() || strtolower( $order->get_billing_email() ) !== strtolower( $user->user_email ) ) {
				return null;
			}
			if ( ! bsc_bp_order_is_awardable( $order ) || bsc_bp_calculate_order_points( $order ) <= 0 ) {
				return null;
			}

			// Commit the initial award and any partial-refund adjustment together, so
			// refunded points are never available for redemption between these steps.
			$result = bsc_bp_with_user_lock(
				$user_id,
				static function () use ( $order, $order_id ) {
					return BSC_DB_Lock::transaction(
						static function () use ( $order, $order_id ) {
							$award = bsc_bp_award_order_points( $order, 'guest_order_link' );
							if ( empty( $award['ok'] ) ) {
								return new WP_Error( 'guest_points_award_failed', 'No se pudieron recuperar los puntos.' );
							}
							$reconciled = bsc_bp_reconcile_order_points( absint( $order_id ), false );
							if ( is_wp_error( $reconciled ) || ( is_array( $reconciled ) && empty( $reconciled['ok'] ) ) ) {
								return new WP_Error( 'guest_points_refund_failed', 'No se pudo ajustar la devolución.' );
							}
							return $award;
						}
					);
				}
			);
			if ( is_array( $result ) && empty( $result['ok'] ) ) {
				// Discard metadata populated inside a transaction that was rolled back.
				$data_store = $order->get_data_store();
				if ( $data_store->has_callable( 'clear_cached_data' ) ) {
					$data_store->clear_cached_data( array( $order->get_id() ) );
				}
				wp_cache_delete( $order->get_meta_cache_key(), 'orders' );
				wp_cache_delete( $order->get_id(), 'post_meta' );
				$order->read_meta_data( true );
				wc_get_container()->get( \Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order->get_id() );
				return new WP_Error( 'guest_points_recovery_failed', 'No se pudieron recuperar los puntos.' );
			}
			return $result;
		}
	);

	if ( is_wp_error( $result ) && ! wp_next_scheduled( 'bsc_bp_recover_linked_order_points', array( absint( $order_id ), $user_id ) ) ) {
		wp_schedule_single_event( time() + 300, 'bsc_bp_recover_linked_order_points', array( absint( $order_id ), $user_id ) );
	}
}
add_action( 'woocommerce_update_new_customer_past_order', 'bsc_bp_recover_linked_order_points', 20, 2 );
add_action( 'bsc_bp_recover_linked_order_points', 'bsc_bp_recover_linked_order_points', 10, 2 );

/** Also expose the native, rate-limited confirmation action on the account home. */
function bsc_bp_guest_recovery_prompt(): void {
	if ( class_exists( VerificationController::class ) ) {
		ob_start();
		wc_get_container()->get( VerificationController::class )->render_prompt();
		$notice = ob_get_clean();
		if ( trim( $notice ) ) {
			echo '<section class="bsc__email-confirmation" aria-label="Confirmación de correo">';
			echo '<h2>Confirma tu correo</h2>';
			echo wp_kses_post( $notice );
			echo '</section>';
		}
	}
}
add_action( 'woocommerce_account_dashboard', 'bsc_bp_guest_recovery_prompt' );

/** Use the same styled native confirmation prompt on the orders panel. */
function bsc_bp_account_recovery_prompt(): void {
	if ( class_exists( VerificationController::class ) ) {
		$controller = wc_get_container()->get( VerificationController::class );
		remove_action( 'woocommerce_before_account_orders', array( $controller, 'render_prompt' ) );
		add_action( 'woocommerce_before_account_orders', 'bsc_bp_guest_recovery_prompt' );
	}
}
add_action( 'wp', 'bsc_bp_account_recovery_prompt' );

/**
 * Our checkout allows guests through filters, so historical recovery must also
 * work when the old WooCommerce guest-checkout option is disabled.
 *
 * @param bool $show Native prompt visibility.
 * @param int  $user_id Current customer.
 * @return bool
 */
function bsc_bp_show_guest_recovery_prompt( bool $show, int $user_id ): bool {
	if ( $show || ! $user_id || ! class_exists( EmailVerificationService::class ) ) {
		return $show;
	}
	$settings = get_option( 'woocommerce_customer_verify_email_settings', array() );
	return 'yes' === ( $settings['enabled'] ?? 'yes' )
		&& ! get_user_option( 'default_password_nag', $user_id )
		&& ! wc_get_container()->get( EmailVerificationService::class )->is_verified( $user_id );
}
add_filter( 'woocommerce_customer_email_verification_should_show_prompt', 'bsc_bp_show_guest_recovery_prompt', 20, 2 );

/**
 * Send the custom registration flow's email confirmation without exposing
 * whether the address has previous purchases.
 *
 * @param int $user_id Newly registered customer.
 */
function bsc_bp_send_guest_recovery_email( int $user_id ): void {
	if ( class_exists( VerificationController::class ) ) {
		wc_get_container()->get( VerificationController::class )->send_verification_email( $user_id );
	}
}

/**
 * Keep WooCommerce's secure action and explain that it also recovers points.
 *
 * @param string $translation Translated text.
 * @param string $text Original WooCommerce text.
 * @param string $domain Translation domain.
 * @return string
 */
function bsc_bp_guest_recovery_copy( string $translation, string $text, string $domain ): string {
	if ( 'woocommerce' !== $domain ) {
		return $translation;
	}
	$copy = array(
		'Confirm email address' => 'Confirmar mi correo',
		'Confirm your email address' => 'Confirma tu correo',
		'Confirm your email address for {site_title}' => 'Confirma tu correo en {site_title}',
		'Confirm your email address to check for past orders and link them to your account.' => '¿Compraste antes sin crear una cuenta? Confirma tu correo para recuperar tus compras y los Bubble Points que correspondan.',
		'Confirm your email address to check for past orders. A confirmation link was sent recently — please check your inbox.' => 'Revisa tu correo y abre el enlace de confirmación para recuperar tus compras y puntos. Si no lo encuentras, revisa la carpeta de spam.',
		'A confirmation link has been sent to your email address. Please check your inbox.' => 'Te enviamos un enlace de confirmación. Revisa tu correo y la carpeta de spam.',
		'A confirmation link was sent recently. Please check your inbox, or wait a moment before requesting a new one.' => 'Ya te enviamos un enlace de confirmación. Revisa tu correo o espera un momento antes de solicitar otro.',
		'Your email address has been confirmed.' => 'Tu correo ha sido confirmado.',
		'This confirmation link is invalid or has expired. Please request a new one.' => 'Este enlace de confirmación no es válido o ha caducado. Solicita uno nuevo.',
		'Unable to confirm this email while you are logged in to a different account. Please log out and open the link again.' => 'Tienes abierta una cuenta diferente. Cierra sesión y abre el enlace de nuevo para confirmar tu correo.',
	);
	return $copy[ $text ] ?? $translation;
}
add_filter( 'gettext', 'bsc_bp_guest_recovery_copy', 20, 3 );
