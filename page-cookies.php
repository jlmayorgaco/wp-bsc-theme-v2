<?php
/**
 * BSC: Cookies policy page.
 */

bsc_render_static_page_template(
	array(
		'page_class'        => 'page-cookies',
		'title'             => 'Politica de Cookies',
		'subtitle'          => 'Como usamos cookies y tecnologias similares',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Uso de cookies</h2>
				<p>Utilizamos cookies esenciales para recordar tu sesión y mantener el carrito. Las cookies de analítica son opcionales y solo se activan después de que las aceptes.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Tipos de cookies</h2>
				<ul>
					<li>Cookies esenciales para login, carrito y checkout.</li>
					<li>Cookies de analítica, gestionadas con Google Analytics, para entender el comportamiento de navegación cuando das tu consentimiento.</li>
					<li>Cookies funcionales para recordar preferencias de la experiencia.</li>
				</ul>
			</div>

			<div class="bsc__info-block">
				<h2>Control de preferencias</h2>
				<p>Puedes aceptar o rechazar la analítica desde el aviso de consentimiento, o administrar y eliminar cookies desde la configuración de tu navegador. Algunas funciones de la tienda podrían verse afectadas si desactivas cookies esenciales.</p>
			</div>
			<?php
		},
	)
);
