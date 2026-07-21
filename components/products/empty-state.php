<?php
/**
 * Shared empty state for product grids.
 *
 * @package BSC2
 */

defined( 'ABSPATH' ) || exit;

$show_button = ! isset( $args['show_button'] ) || (bool) $args['show_button'];
$shop_url = function_exists( 'wc_get_page_permalink' )
	? wc_get_page_permalink( 'shop' )
	: home_url( '/shop/' );
?>

<div class="container__empty bsc__products-empty">
	<img
		class="bsc__empty-logo"
		src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_image_empty_cart.png"
		alt=""
		width="260"
		height="166"
	>
	<h2 class="bsc__title bsc__title--cart-empty bsc__products-empty-title" role="status">
		No se encontraron <strong>productos</strong>.
	</h2>
	<?php if ( $show_button ) : ?>
		<a class="bsc__button bsc__button--product-card bsc__button-add-to-cart" href="<?php echo esc_url( $shop_url ); ?>">
			<span>Volver a la tienda</span>
		</a>
	<?php endif; ?>
</div>
