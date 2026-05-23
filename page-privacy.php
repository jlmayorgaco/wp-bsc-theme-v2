<?php
/**
 * BSC: Privacy policy page.
 */

bsc_render_static_page_template(
	[
		'page_class'        => 'page-privacy',
		'title'             => 'Politicas de Privacidad',
		'subtitle'          => 'Conoce como protegemos y tratamos tus datos',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Datos que recopilamos</h2>
				<p>Durante el proceso de compra podemos solicitar nombre, identificacion, telefono, direccion y correo electronico para procesar pedidos, entregas, soporte y comunicaciones relacionadas con tu compra.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Uso de la informacion</h2>
				<p>La informacion se usa para operar la tienda, validar pagos, despachar pedidos, atender solicitudes y enviar notificaciones asociadas al servicio.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Formularios y solicitudes</h2>
				<p>Los formularios de contacto, Newsletter y Bubble Creators pueden recopilar correo electronico, redes sociales y mensaje enviado por la persona. Los leads guardados en el panel interno usan retencion configurable y no almacenan IP en texto plano.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Comparticion y seguridad</h2>
				<p>Podemos compartir datos con proveedores de pago, transporte y soporte cuando sea necesario para prestar el servicio. Si tienes dudas sobre tratamiento de datos, usa nuestra pagina de <a href="<?php echo esc_url( bsc_get_static_page_url( 'contact-us' ) ); ?>">contacto</a>.</p>
			</div>
			<?php
		},
	]
);
