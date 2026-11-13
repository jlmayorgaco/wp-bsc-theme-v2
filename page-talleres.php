<?php
/**
 * Template Name: Talleres coreanos
 */
defined( 'ABSPATH' ) || exit;

$assets = get_template_directory_uri() . '/images/talleres/';
$workshops = bsc_workshops_posts( array( 'publish' ) );
$agenda = array();
$next = null;
$now = time();
$months = array( 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre' );
foreach ( $workshops as $workshop ) {
	$settings = bsc_workshops_settings( $workshop->ID );
	$counts = bsc_workshops_counts( $workshop->ID );
	$open = bsc_workshops_open_slots( $settings, $counts );
	$first_date = 0;
	foreach ( $settings['slots'] as $slot ) {
		if ( $slot['start'] > $now && ( ! $first_date || $slot['start'] < $first_date ) ) {
			$first_date = $slot['start'];
		}
		if ( $slot['open'] && $slot['start'] > $now && ( ! $next || $slot['start'] < $next['start'] ) ) {
			$next = array( 'title' => $workshop->post_title, 'start' => $slot['start'] );
		}
	}
	$month = $first_date ? $months[ (int) wp_date( 'n', $first_date, wp_timezone() ) - 1 ] : '';
	$agenda[] = compact( 'workshop', 'settings', 'counts', 'open', 'month' );
}
$status = isset( $_GET['taller_resultado'] ) && is_string( $_GET['taller_resultado'] ) ? sanitize_key( wp_unslash( $_GET['taller_resultado'] ) ) : '';
$message = bsc_workshops_message( $status );
$privacy_url = get_privacy_policy_url();
$benefits = array(
	'smile'   => '<strong>Taller coreano</strong> presencial en Bogotá',
	'heart'   => '<strong>Estudio privado</strong> cerca al parque de la 93',
	'spark'   => '<strong>Experiencia coreana</strong> de 2 horas y media',
	'rainbow' => '<strong>Incluye:</strong> taller presencial de skincare coreano, guía K-Beauty, bebida coreana, bandana de skincare, bolsita con stickers de Bubbles, sheet mask, pasaporte de sellos (¡el 6.º taller es gratis!) y 20 % de descuento en productos coreanos ese día.',
	'person'  => '¡Si vienes con una amiga o amigo,<br><strong>10 % de descuento para cada uno!</strong>',
);
$icons = array(
	'smile'   => 'M16 3a13 13 0 1 0 0 26 13 13 0 0 0 0-26ZM11 13h.1m9.9 0h.1M12 18a4 4 0 0 0 8 0',
	'heart'   => 'M16 27C13 24 3 17 3 11a6.5 6.5 0 0 1 13-1 6.5 6.5 0 0 1 13 1c0 6-10 13-13 16Z',
	'spark'   => 'M16 2c1 9 5 13 14 14-9 1-13 5-14 14C15 21 11 17 2 16 11 15 15 11 16 2Z',
	'rainbow' => 'M4 19a12 12 0 0 1 24 0m-21 0a9 9 0 0 1 18 0m-15 0a6 6 0 0 1 12 0M2 26a3 3 0 0 1 1-5 4 4 0 0 1 7 0 3 3 0 0 1 1 5H2Zm19 0a3 3 0 0 1 1-5 4 4 0 0 1 7 0 3 3 0 0 1 1 5h-9Z',
	'person'  => 'M16 4a7 7 0 1 1 0 14 7 7 0 0 1 0-14ZM5 29c0-6 5-11 11-11s11 5 11 11',
);
$questions = array(
	'¿Necesito llevar algo al taller?' => 'Te damos los productos y materiales para practicar. Ven sin maquillaje para que puedas probar la rutina en tu piel.',
	'¿Puedo ir sin saber nada de skincare?' => 'Sí. Te acompañamos paso a paso para que puedas repetir la rutina en casa.',
	'¿Dónde queda exactamente?' => 'Los talleres son presenciales en Bogotá, cerca al Parque de la 93. Compartimos los detalles del lugar con la reserva confirmada.',
	'¿Puedo cambiar mi fecha?' => 'Escríbenos por WhatsApp con el correo de tu reserva para revisar qué horarios tienen cupos.',
);

get_header();
?>
<main class="bsc bsc-workshops" id="talleres">
	<section class="bsc-workshops__hero" aria-labelledby="talleres-title">
		<img src="<?php echo esc_url( $assets . 'mesa.webp' ); ?>" alt="Mesa del taller Bubbles con espejos, toallas y accesorios de skincare" width="1104" height="974" fetchpriority="high">
		<a class="bsc__button bsc-workshops__hero-cta" href="#talleres-agenda">¡Quierooo asistir!</a>
	</section>

	<section class="bsc-workshops__wrap bsc-workshops__agenda" id="talleres-agenda" aria-labelledby="talleres-title">
		<div class="bsc-workshops__section-heading">
			<svg width="55" height="11" viewBox="0 0 55 11" fill="none" aria-hidden="true"><path d="M0 2c5.5 0 5.5 6 11 6s5.5-6 11-6 5.5 6 11 6 5.5-6 11-6 5.5 6 11 6" stroke="currentColor" stroke-width="3"/></svg>
			<h1 id="talleres-title" class="bsc__title"><strong>¡Talleres coreanos!</strong></h1>
		</div>
		<?php if ( $message ) : ?>
			<p class="bsc-workshops__notice" role="status"><?php echo esc_html( $message ); ?></p>
		<?php endif; ?>
		<?php if ( $next ) : ?>
			<?php $minutes = (int) ceil( ( $next['start'] - $now ) / MINUTE_IN_SECONDS ); ?>
			<div class="bsc-workshops__next" data-start="<?php echo esc_attr( $next['start'] ); ?>">
				<div class="bsc-workshops__next-title"><span>Nuestro próximo taller coreano:</span><strong>¡<?php echo esc_html( $next['title'] ); ?> - en Bogotá!</strong></div>
				<div class="bsc-workshops__countdown" aria-hidden="true">
					<?php foreach ( array( 'días' => intdiv( $minutes, 1440 ), 'hrs' => intdiv( $minutes % 1440, 60 ), 'min' => $minutes % 60 ) as $unit => $value ) : ?>
						<span><b data-workshop-countdown><?php echo esc_html( sprintf( '%02d', $value ) ); ?></b><small><?php echo esc_html( $unit ); ?></small></span>
					<?php endforeach; ?>
				</div>
				<span class="screen-reader-text"><?php echo esc_html( sprintf( '%s de %s a las %s', wp_date( 'j', $next['start'] ), $months[ (int) wp_date( 'n', $next['start'] ) - 1 ], wp_date( 'H:i', $next['start'] ) ) ); ?></span>
			</div>
		<?php endif; ?>
		<div class="bsc-workshops__agenda-layout">
			<aside class="bsc-workshops__benefits" aria-label="Qué incluye el taller">
				<ul>
					<?php foreach ( $benefits as $icon => $copy ) : ?>
						<li><svg width="30" height="30" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo esc_attr( $icons[ $icon ] ); ?>"/></svg><span><?php echo wp_kses( $copy, array( 'strong' => array(), 'br' => array() ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<a class="bsc-workshops__benefit-photo" href="#talleres-experiencia" aria-label="Ver cómo se vive un taller"><img src="<?php echo esc_url( $assets . 'experiencia.webp' ); ?>" width="720" height="1278" alt="" loading="lazy"></a>
			</aside>
			<div class="bsc-workshops__cards">
				<?php if ( ! $agenda ) : ?>
					<div class="bsc-workshops__empty">
						<h2>Estamos preparando nuevas fechas</h2>
						<p>Escríbenos para preguntar por el próximo taller o planear una experiencia privada con tu grupo.</p>
						<a class="bsc__button bsc__button--outline" href="<?php echo esc_url( bsc_get_whatsapp_url( 'talleres' ) ); ?>">Consultar por WhatsApp</a>
					</div>
				<?php endif; ?>
				<?php foreach ( $agenda as $entry ) : ?>
					<?php get_template_part( 'plugins/talleres/card', null, $entry + compact( 'assets', 'privacy_url' ) ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="bsc-workshops__experience" id="talleres-experiencia" aria-labelledby="talleres-experience-title">
		<div class="bsc-workshops__wrap bsc-workshops__experience-layout">
			<img src="<?php echo esc_url( $assets . 'experiencia.webp' ); ?>" width="720" height="1278" alt="Participantes durante un taller coreano de Bubbles en Bogotá" loading="lazy">
			<div>
				<h2 id="talleres-experience-title" class="bsc__title"><strong>Así se vive un taller coreano</strong></h2>
				<p>Prueba texturas, aprende el orden de una rutina y descubre qué pasos tienen sentido para tu piel. Te guiamos mientras practicas con productos coreanos.</p>
				<a class="bsc__button bsc__button--outline" href="#talleres-agenda">Explorar los talleres</a>
			</div>
		</div>
	</section>

	<div class="bsc-workshops__photo-band">
		<img src="<?php echo esc_url( $assets . 'rutina.webp' ); ?>" width="1320" height="1100" alt="" loading="lazy">
	</div>
	<section class="bsc-workshops__wrap bsc-workshops__help" aria-labelledby="talleres-faq">
		<div>
			<h2 id="talleres-faq" class="bsc__title"><strong>Preguntas frecuentes</strong></h2>
			<?php foreach ( $questions as $question => $answer ) : ?>
				<details><summary><?php echo esc_html( $question ); ?></summary><p><?php echo esc_html( $answer ); ?></p></details>
			<?php endforeach; ?>
		</div>
		<aside>
			<h2 class="bsc__title"><strong>¿Quieres un taller privado?</strong></h2>
			<p>Para cumpleaños, despedidas o un plan con tu grupo. Cuéntanos qué tienes en mente.</p>
			<a class="bsc__button" href="<?php echo esc_url( bsc_get_whatsapp_url( 'talleres' ) ); ?>">Hablar por WhatsApp</a>
		</aside>
	</section>
</main>
<?php get_footer(); ?>
