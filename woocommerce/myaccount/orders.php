<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders ); ?>



<?php if ( $has_orders ) : ?>

	<?php 
		require_once get_template_directory() . '/components/orders/orders-table.php'; 
		$ordersTable = new BSC_Orders_Table();
		$ordersTable->set_customer_orders(wc_get_orders([
			'customer_id' => get_current_user_id(),
			'paginate'    => true,
			'paged'       => 1,
		]));
		$ordersTable->set_button_class('bsc__button');
		$ordersTable->render();
	?>


<?php else : ?>
	<?php 
		require_once get_template_directory() . '/components/orders/orders-zero-state'; 
		order_zero_state();
	?>
<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
