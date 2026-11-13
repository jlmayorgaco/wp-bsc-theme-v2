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
				<p>Utilizamos cookies esenciales para recordar tu sesión y mantener el carrito. Puedes elegir por separado si permites la analítica y la publicidad de Meta.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Tipos de cookies</h2>
				<ul>
					<li>Cookies esenciales para login, carrito y checkout.</li>
					<li>Cookies de analítica, gestionadas con Google Analytics, para entender el comportamiento de navegación cuando das tu consentimiento.</li>
					<li>Publicidad de Meta. Si la permites, usamos el píxel y la API de Conversiones para compartir actividad como las visitas, los productos agregados al carrito y las compras. Meta usa estos datos para medir y personalizar anuncios en Facebook e Instagram.</li>
					<li>Cookies funcionales para recordar preferencias de la experiencia.</li>
				</ul>
			</div>

			<div class="bsc__info-block">
				<h2>Control de preferencias</h2>
				<p>Abre «Preferencias de cookies» en el pie de página para cambiar o retirar tu permiso en cualquier momento.</p>
			</div>
			<?php
		},
	)
);
