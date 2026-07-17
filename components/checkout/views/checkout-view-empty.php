<?php
defined( 'ABSPATH' ) || exit;
?>
<main class="bsc bsc__page bsc__page--empty bsc__page--checkout is-visible">
	<div class="container__empty">
		<img class="bsc__empty-logo" alt="" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_image_empty_cart.png">
		<h1 class="bsc__title bsc__title--cart-empty">Ohh ... <strong>tu carrito</strong> está vacío</h1>
		<a class="bsc__button bsc__button--product-card bsc__button-add-to-cart" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><span>Volver a la tienda</span></a>
	</div>
</main>
