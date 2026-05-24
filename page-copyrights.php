<?php
/**
 * BSC: Copyrights page.
 */

bsc_render_static_page_template(
	array(
		'page_class'        => 'page-copyrights',
		'title'             => 'Derechos de Autor',
		'subtitle'          => 'Uso del contenido, marcas e imagenes de Bubble Skin Care',
		'fallback_callback' => static function (): void {
			?>
			<div class="bsc__info-block">
				<h2>Propiedad intelectual</h2>
				<p>Las marcas, imagenes, fotografias, descripciones, logos y demas contenidos publicados en este sitio pertenecen a Bubble Skin Care o a sus respectivos titulares.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Uso autorizado</h2>
				<p>No esta permitida la reproduccion, distribucion o uso comercial del contenido sin autorizacion previa y por escrito.</p>
			</div>

			<div class="bsc__info-block">
				<h2>Consultas</h2>
				<p>Si necesitas solicitar autorizacion o reportar un uso no autorizado, utiliza nuestra pagina de <a href="<?php echo esc_url( bsc_get_static_page_url( 'contact-us' ) ); ?>">contacto</a>.</p>
			</div>
			<?php
		},
	)
);
