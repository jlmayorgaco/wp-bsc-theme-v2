<?php
/**
 * Template Name: Preguntas Frecuentes
 * BSC: FAQ - preguntas frecuentes de la tienda.
 */

bsc_render_static_page_template(
	[
		'page_class'        => 'page-faq',
		'title'             => 'Preguntas Frecuentes',
		'subtitle'          => 'Resolvemos tus dudas mas comunes',
		'body_class'        => 'bsc__faq',
		'fallback_callback' => static function (): void {
			?>
			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Realizan envios a todo Colombia?</summary>
				<div class="bsc__faq-answer">
					<p>Si. Enviamos a todo el territorio colombiano y los tiempos varian segun la ciudad de destino.</p>
				</div>
			</details>

			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Los productos son originales?</summary>
				<div class="bsc__faq-answer">
					<p>Todos los productos publicados por Bubble Skin Care son originales y provienen de distribuidores autorizados.</p>
				</div>
			</details>

			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Como puedo saber que producto va mejor con mi piel?</summary>
				<div class="bsc__faq-answer">
					<p>En cada ficha de producto indicamos tipo de piel recomendado. Si necesitas ayuda adicional puedes escribirnos por WhatsApp y te orientamos.</p>
				</div>
			</details>

			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Puedo devolver un producto?</summary>
				<div class="bsc__faq-answer">
					<p>Si. Revisa nuestra <a href="<?php echo esc_url( bsc_get_shipping_returns_url() ); ?>">politica de envios y devoluciones</a> para conocer tiempos y condiciones.</p>
				</div>
			</details>

			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Como hago seguimiento de mi pedido?</summary>
				<div class="bsc__faq-answer">
					<p>Cuando el pedido sea despachado te enviaremos la guia por correo. Tambien puedes revisar el estado desde <a href="<?php echo esc_url( bsc_get_account_orders_url() ); ?>">Mis Pedidos</a>.</p>
				</div>
			</details>

			<details class="bsc__faq-item">
				<summary class="bsc__faq-question">&iquest;Tienen envio gratis?</summary>
				<div class="bsc__faq-answer">
					<p>Si. Los pedidos que superen <strong>$150.000 COP</strong> cuentan con envio gratis a cualquier ciudad de Colombia.</p>
				</div>
			</details>
			<?php
		},
	]
);
