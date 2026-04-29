<?php
/**
 * My Account Dashboard
 *
 * Shows the first intro screen on the account dashboard.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/dashboard.php.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_html = array(
	'a' => array(
		'href' => array(),
	),
);
?>

<p>
	<?php
	printf(
		wp_kses( __( 'Hola %1$s (¿no eres tú? <a href="%2$s">Cerrar sesión</a>)', 'bsc-2-0' ), $allowed_html ),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>

<p>
	<?php
	$dashboard_desc = __( 'Desde tu cuenta puedes ver tus <a href="%1$s">pedidos recientes</a>, gestionar tu <a href="%2$s">dirección de facturación</a> y <a href="%3$s">editar tu contraseña y tus datos</a>.', 'bsc-2-0' );
	if ( wc_shipping_enabled() ) {
		$dashboard_desc = __( 'Desde tu cuenta puedes ver tus <a href="%1$s">pedidos recientes</a>, gestionar tus <a href="%2$s">direcciones</a> y <a href="%3$s">editar tu contraseña y tus datos</a>.', 'bsc-2-0' );
	}
	printf(
		wp_kses( $dashboard_desc, $allowed_html ),
		esc_url( wc_get_endpoint_url( 'orders' ) ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>

<?php
	do_action( 'woocommerce_account_dashboard' );
	do_action( 'woocommerce_before_my_account' );
	do_action( 'woocommerce_after_my_account' );