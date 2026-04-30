<?php
/**
 * My Account navigation
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/navigation.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
 <?php
	require_once get_template_directory() . '/components/my-account/my-account-header.php';
	$headerMyAccount = new BSC_MY_ACCOUNT_HEADER();
	$current_route = '';

	if ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ) {
		$current_route = 'orders';
	} elseif ( is_wc_endpoint_url( 'edit-address' ) ) {
		$current_route = 'edit-address';
	} elseif ( is_wc_endpoint_url( 'edit-account' ) ) {
		$current_route = 'edit-account';
	} elseif ( function_exists( 'is_page' ) && is_page( 'bubble-points' ) ) {
		$current_route = 'bubble-points';
	}

	$headerMyAccount->setCurrentRoute( $current_route );
	$headerMyAccount->render();
 ?>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
