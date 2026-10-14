<?php
/**
 * BSC: Privacy policy page.
 */

bsc_render_static_page_template(
	array(
		'page_class'        => 'page-privacy',
		'title'             => 'Politicas de Privacidad',
		'subtitle'          => 'Conoce como protegemos y tratamos tus datos',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Datos que recopilamos</h2>
				<p>Durante el proceso de compra podemos solicitar nombre, identificación, teléfono, dirección y correo electrónico para procesar pedidos, entregas, soporte y comunicaciones relacionadas con tu compra.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Uso de la informacion</h2>
				<p>La información se usa para operar la tienda, validar pagos, despachar pedidos, atender solicitudes, medir navegación y búsquedas de forma operativa, enviar notificaciones asociadas al servicio y, si dejas tus datos en checkout, recordarte un carrito pendiente por tiempo limitado.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Formularios y solicitudes</h2>
				<p>Los formularios de contacto, Newsletter y Bubble Creators pueden recopilar correo electrónico, redes sociales y mensaje enviado por la persona. Los leads guardados en el panel interno usan retención configurable y no almacenan IP en texto plano.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Skin Quiz AI y selfie</h2>
				<p>Si eliges el modo Estudio con foto, puedes enviar una selfie para generar recomendaciones cosméticas. La imagen se procesa con Google Gemini cuando la función AI está habilitada, junto con las respuestas del quiz y un perfil cosmético derivado; no se usa para identificarte ni para diagnóstico médico.</p>
				<p>La selfie y los datos asociados se conservan por un máximo configurable de 90 días y se eliminan mediante el proceso de limpieza del sitio. Puedes solicitar acceso o eliminación antes de ese plazo desde nuestra <a href="<?php echo esc_url( bsc_get_static_page_url( 'contact-us' ) ); ?>">página de contacto</a>. La imagen se entrega únicamente mediante una ruta autorizada y con headers de no almacenamiento.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Comparticion y seguridad</h2>
				<p>Podemos compartir datos con proveedores de pago, transporte, analítica opcional y soporte cuando sea necesario para prestar el servicio o cuando hayas dado tu consentimiento. Si tienes dudas sobre tratamiento de datos, usa nuestra página de <a href="<?php echo esc_url( bsc_get_static_page_url( 'contact-us' ) ); ?>">contacto</a>.</p>
			</div>
			<?php
		},
	)
);
