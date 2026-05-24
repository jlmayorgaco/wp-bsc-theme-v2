<?php
/**
 * Template Name: Página Mi Cuenta Personalizada
 */

get_header();
?>

<main class="bsc bsc__page bsc__page--account">
	<div class="bsc__container bsc__account-container">

	<div class="bsc__account-wrapper">
		<?php
		// Renderiza contenido de WooCommerce My Account (incluye dashboard, pedidos, detalles, etc.)
		echo do_shortcode( '[woocommerce_my_account]' );
		?>
	</div>

	</div>
</main>

<?php get_footer(); ?>
