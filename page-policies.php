<?php
/**
 * BSC: Policies / terms page.
 */

bsc_render_static_page_template(
	array(
		'page_class'        => 'page-policies',
		'title'             => 'Politicas y Terminos',
		'subtitle'          => 'Condiciones generales de compra y uso del sitio',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Compras y pagos</h2>
				<p>Todos los pedidos estan sujetos a disponibilidad de inventario, validacion del pago y confirmacion de los datos de entrega.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Envios y devoluciones</h2>
				<p>Los tiempos, costos y condiciones de devolucion se detallan en nuestra pagina de <a href="<?php echo esc_url( bsc_get_shipping_returns_url() ); ?>">Envios y Devoluciones</a>.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Cuenta y servicio</h2>
				<p>Al usar este sitio aceptas mantener informacion de contacto actualizada y hacer un uso adecuado de tu cuenta y de los canales de soporte.</p>
			</div>
			<?php
		},
	)
);
