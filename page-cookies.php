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
				<p>Utilizamos cookies para recordar tu sesion, mantener el carrito, mejorar la navegacion y entender como se usa la tienda.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Tipos de cookies</h2>
				<ul>
					<li>Cookies esenciales para login, carrito y checkout.</li>
					<li>Cookies de analitica para entender el comportamiento de navegacion.</li>
					<li>Cookies funcionales para recordar preferencias de la experiencia.</li>
				</ul>
			</div>

			<div class="bsc__info-block">
				<h2>Control de preferencias</h2>
				<p>Puedes administrar o eliminar cookies desde la configuracion de tu navegador. Algunas funciones de la tienda podrian verse afectadas si desactivas cookies esenciales.</p>
			</div>
			<?php
		},
	)
);
