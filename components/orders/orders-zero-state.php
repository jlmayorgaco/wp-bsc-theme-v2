<?php
function order_zero_state() {
	?>
	<div class="bsc__orders bsc__orders--empty-orders" aria-labelledby="bsc-orders-empty-title">
		<h1 id="bsc-orders-empty-title" class="orders__title">Upss... a&uacute;n no tienes pedidos :(</h1>
		<p class="orders__subtitle">&iexcl;Tenemos todo para armar tu rutina coreana perfecta!</p>

		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"
			class="orders__button bsc__button"
			aria-label="Ir a la tienda">
			&iexcl; Ir a la&nbsp;<strong>tienda</strong>&nbsp;!
		</a>
	</div>
	<?php
}
