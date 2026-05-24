<?php
/**
 * Shared order summary partial.
 *
 * Variables:
 * - $order (WC_Order)
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bsc-email-design-system.php';

if ( isset( $order ) && $order instanceof WC_Order ) {
	bsc_email_render_order_summary( $order );
}
