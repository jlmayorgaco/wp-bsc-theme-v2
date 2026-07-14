<?php
/**
 * Template Name: Contacto
 * BSC: Pagina de contacto.
 */

$contact_whatsapp_url     = bsc_get_whatsapp_url( 'support' );
$contact_whatsapp_display = bsc_get_whatsapp_display();
$contact_shop_url         = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$theme_uri                = get_template_directory_uri();

get_header();
?>

<main class="bsc bsc__page page-contact-us">
	<section class="bsc__contact-hero" aria-labelledby="bsc-contact-title">
		<img class="bsc__contact-hero__mark" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_profile_logo.png" alt="" aria-hidden="true">
		<h1 id="bsc-contact-title"><strong>Contacto</strong></h1>
		<img class="bsc__contact-wave" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_title_underline.png" alt="" aria-hidden="true">
		<p class="bsc__description">
			Estamos aqu&iacute; para ayudarte con productos, pedidos, rutinas, colaboraciones y cualquier duda sobre tu experiencia K-Beauty.
		</p>
	</section>

	<section class="bsc__contact-channels" aria-label="Canales de contacto">
		<a
			class="bsc__contact-card bsc__contact-card--yellow bsc__contact-item"
			href="<?php echo esc_url( $contact_whatsapp_url ); ?>"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="Escribir por WhatsApp a Bubble Skin Care"
		>
			<span class="bsc__contact-card__icon">
				<i class="fab fa-whatsapp" aria-hidden="true"></i>
			</span>
			<span class="bsc__contact-card__label">WhatsApp</span>
			<span class="bsc__contact-card__value"><?php echo esc_html( $contact_whatsapp_display ); ?></span>
			<span class="bsc__contact-card__arrow" aria-hidden="true">&#8599;</span>
		</a>

		<a
			class="bsc__contact-card bsc__contact-card--blue bsc__contact-item"
			href="https://www.instagram.com/bubbles.skincare/"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="Ir al Instagram de Bubble Skin Care"
		>
			<span class="bsc__contact-card__icon">
				<i class="fab fa-instagram" aria-hidden="true"></i>
			</span>
			<span class="bsc__contact-card__label">Instagram</span>
			<span class="bsc__contact-card__value">@bubbles.skincare</span>
			<span class="bsc__contact-card__arrow" aria-hidden="true">&#8599;</span>
		</a>

		<a
			class="bsc__contact-card bsc__contact-card--pink bsc__contact-item"
			href="https://www.tiktok.com/@bubblesskincare"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="Ir al TikTok de Bubble Skin Care"
		>
			<span class="bsc__contact-card__icon">
				<i class="fab fa-tiktok" aria-hidden="true"></i>
			</span>
			<span class="bsc__contact-card__label">TikTok</span>
			<span class="bsc__contact-card__value">@bubblesskincare</span>
			<span class="bsc__contact-card__arrow" aria-hidden="true">&#8599;</span>
		</a>
	</section>

	<section class="bsc__contact-main" aria-labelledby="bsc-contact-form-title">
		<div class="bsc__contact-note">
			<p>Para compras, disponibilidad y recomendaciones, tambi&eacute;n puedes visitar la tienda.</p>
			<a class="bsc__contact-shop bsc__contact-btn" href="<?php echo esc_url( $contact_shop_url ); ?>">Visitar tienda</a>
		</div>

		<div class="bsc__contact-form-panel">
			<div class="bsc__contact-form-heading">
				<h2 id="bsc-contact-form-title"><strong>Env&iacute;anos</strong> un mensaje</h2>
				<img class="bsc__contact-wave" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_title_underline.png" alt="" aria-hidden="true">
				<p>Cu&eacute;ntanos qu&eacute; necesitas y el equipo de BSC te responder&aacute; lo antes posible.</p>
			</div>

			<form id="bsc-contact-form" class="bsc__contact-form" novalidate>
				<div class="bsc__contact-form-row">
					<div class="bsc__contact-field">
						<label for="bsc-contact-name">Nombre</label>
						<input type="text" id="bsc-contact-name" name="bsc_name" required placeholder="Tu nombre" autocomplete="name">
					</div>

					<div class="bsc__contact-field">
						<label for="bsc-contact-email">Correo electr&oacute;nico</label>
						<input type="email" id="bsc-contact-email" name="bsc_email" required placeholder="tucorreo@ejemplo.com" autocomplete="email">
					</div>
				</div>

				<div class="bsc__contact-field bsc__contact-field--full">
					<label for="bsc-contact-message">Mensaje</label>
					<textarea id="bsc-contact-message" name="bsc_message" required placeholder="&iquest;En qu&eacute; podemos ayudarte?" rows="4"></textarea>
				</div>

				<div id="bsc-contact-notice" class="bsc__contact-notice" aria-live="polite"></div>

				<button type="submit" id="bsc-contact-submit" class="bsc__contact-submit">Enviar mensaje</button>
			</form>
		</div>
	</section>
</main>

<?php get_footer(); ?>
