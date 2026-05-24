<?php
/**
 * Template Name: Bubble Creators
 * BSC-018: Landing page para creadores de contenido / influencers.
 */

get_header();

$theme_uri = get_template_directory_uri();
?>

<main class="bsc bsc__page page-bubble-creators">
	<section class="bc__intro" aria-labelledby="bc-title">
		<div class="bc__intro-logo" aria-hidden="true">
			<svg class="bc__intro-logo-text" viewBox="0 0 190 74" focusable="false" aria-hidden="true">
				<path id="bc-logo-arc" d="M 24 68 A 78 78 0 0 1 166 68"></path>
				<text>
					<textPath href="#bc-logo-arc" startOffset="50%" text-anchor="middle">Bubble Creators</textPath>
				</text>
			</svg>
			<img src="<?php echo esc_url( $theme_uri ); ?>/images/BSC_COMING_SOON_FACE.png" alt="">
		</div>

		<h1 id="bc-title" class="screen-reader-text">Bubble Creators</h1>

		<p class="bc__intro-copy">
			Si amas Bubbles Skin Care, el K-Beauty, tienes una plataforma activa en redes sociales y eres creativa/o
			<strong>&iexcl;Queremos conocerte!</strong>
		</p>

		<img class="bc__wave" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_title_underline.png" alt="" aria-hidden="true">
	</section>

	<section class="bc__benefits" aria-label="Beneficios Bubble Creators">
		<article class="bc__benefit-card bc__benefit-card--yellow">
			<img class="bc__benefit-icon bc__benefit-icon--rainbow" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_home_about_icon1.png" alt="" aria-hidden="true">
			<h2>Contenido que conecta</h2>
			<div class="bc__benefit-box">
				<p><strong>&iexcl;Crea contenido real con los mejores productos Coreanos!</strong></p>
				<p>Tu estilo, tu piel, tu proceso.<br>Crece con Bubbles en campa&ntilde;as de 3 a 12 meses.</p>
			</div>
		</article>

		<article class="bc__benefit-card bc__benefit-card--blue">
			<img class="bc__benefit-icon bc__benefit-icon--rainbow" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_home_about_icon1.png" alt="" aria-hidden="true">
			<h2>Beneficios que s&iacute; suman</h2>
			<div class="bc__benefit-box">
				<p><strong>&iexcl;Accede a productos Kbeauty y beneficios exclusivos!</strong></p>
				<p>Genera ingresos con tu contenido y tu c&oacute;digo personal, mientras recomiendas lo que te gusta.</p>
			</div>
		</article>

		<article class="bc__benefit-card bc__benefit-card--pink">
			<img class="bc__benefit-icon bc__benefit-icon--rainbow" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_home_about_icon1.png" alt="" aria-hidden="true">
			<h2>Experiencias</h2>
			<div class="bc__benefit-box">
				<p><strong>&iexcl;S&eacute; parte de campa&ntilde;as V.I.P, drops y experiencias Kbeauty!</strong></p>
				<p>Colabora con Bubbles y marcas coreanas en lanzamientos y contenido exclusivo.</p>
			</div>
		</article>
	</section>

	<section class="bc__form-section" id="bc-form" aria-labelledby="bc-form-title">
		<div class="bc__form-heading">
			<h2 id="bc-form-title"><strong>Reg&iacute;strate</strong> como creador</h2>
			<img class="bc__wave" src="<?php echo esc_url( $theme_uri ); ?>/images/bsc_title_underline.png" alt="" aria-hidden="true">
			<p>
				<strong>Nuestro equipo revisar&aacute; cuidadosamente tu perfil.</strong>
				Ten en cuenta que enviar la solicitud no garantiza la selecci&oacute;n, pero tu informaci&oacute;n si quedar&aacute; en nuestra base de datos de creadores y si tu perfil es seleccionado, nos pondremos en contacto contigo :)
			</p>
		</div>

		<form class="bc__form" id="bc-creator-form" action="#">
			<div class="bc__form-row">
				<div class="bc__form-field">
					<label for="bc-name">Nombre completo</label>
					<input type="text" id="bc-name" name="nombre" placeholder="Tu nombre" required>
				</div>
				<div class="bc__form-field">
					<label for="bc-email">Correo electr&oacute;nico</label>
					<input type="email" id="bc-email" name="email" placeholder="tu@correo.com" required>
				</div>
			</div>

			<div class="bc__form-row">
				<div class="bc__form-field">
					<label for="bc-instagram">Link de Instagram</label>
					<input type="url" id="bc-instagram" name="instagram" placeholder="https://www.instagram.com/tu_usuario/" inputmode="url" autocomplete="url" required>
				</div>
				<div class="bc__form-field">
					<label for="bc-tiktok">Link de TikTok</label>
					<input type="url" id="bc-tiktok" name="tiktok" placeholder="https://www.tiktok.com/@tu_usuario" inputmode="url" autocomplete="url" required>
				</div>
			</div>

			<div class="bc__form-field bc__form-field--full">
				<label for="bc-message">Cu&eacute;ntanos sobre ti y tu contenido</label>
				<textarea id="bc-message" name="mensaje" rows="4" placeholder="&iquest;Qu&eacute; tipo de contenido creas? &iquest;Por qu&eacute; quieres colaborar con BSC?" required></textarea>
			</div>

			<p class="bc__form-feedback bc__form-feedback--error" id="bc-form-error"></p>

			<button type="submit" class="bc__form-submit" id="bc-form-submit">&iexcl; Aplicar a <strong>Bubble Creators</strong> !</button>
		</form>

		<div class="bc__form-success" id="bc-form-success">
			<div class="bc__form-success-inner">
				<img class="bc__form-success-face" src="<?php echo esc_url( $theme_uri ); ?>/images/BSC_COMING_SOON_FACE.png" alt="">
				<p id="bc-form-success-msg"></p>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
