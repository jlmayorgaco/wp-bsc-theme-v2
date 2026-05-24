<?php
/**
 * BSC: Claims / PQR page.
 */

bsc_render_static_page_template(
	array(
		'page_class'        => 'page-claims',
		'title'             => 'Peticiones, Quejas y Reclamos',
		'subtitle'          => 'Canales y tiempos para atender tus solicitudes',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Como radicar una solicitud</h2>
				<p>Si necesitas registrar una peticion, queja o reclamo, escribe a nuestro canal de soporte con el numero de pedido, tus datos de contacto y una descripcion clara de lo ocurrido.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Informacion recomendada</h2>
				<ul>
					<li>Nombre completo y correo asociado a la compra.</li>
					<li>Numero de pedido o referencia del producto.</li>
					<li>Descripcion del caso y soporte fotografico si aplica.</li>
				</ul>
			</div>

			<div class="bsc__info-block">
				<h2>Respuesta</h2>
				<p>Puedes iniciar el caso desde nuestra pagina de <a href="<?php echo esc_url( bsc_get_static_page_url( 'contact-us' ) ); ?>">contacto</a> o revisar el historial del pedido en <a href="<?php echo esc_url( bsc_get_account_orders_url() ); ?>">Mis Pedidos</a>.</p>
			</div>
			<?php
		},
	)
);
