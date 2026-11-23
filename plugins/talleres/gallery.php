<?php
defined( 'ABSPATH' ) || exit;

$slides = array();
foreach ( bsc_workshops_gallery_ids() as $id ) {
	$slides[] = array(
		'title'  => get_the_title( $id ),
		'video'  => wp_get_attachment_url( $id ),
		'poster' => get_the_post_thumbnail_url( $id, 'large' ),
	);
}

if ( ! $slides ) {
	// The reference PDF supplies a still and the three captions.
	foreach ( array( 'Taller kbeauty & matcha', 'Taller rutina base coreana', 'Taller korean glass skin' ) as $title ) {
		$slides[] = array( 'title' => $title, 'video' => '', 'poster' => $args['assets'] . 'experiencia.webp' );
	}
}

$count = count( $slides );
$active = $count >= 3 ? 1 : 0;
?>
<section class="bsc-workshops__experience" id="talleres-experiencia" aria-labelledby="talleres-experience-title">
	<h2 class="bsc__title" id="talleres-experience-title">¡Así se ven nuestros <strong>talleres coreanos!</strong></h2>
	<div class="bsc-workshops__carousel" data-workshop-carousel>
		<div class="bsc-workshops__slides">
			<?php foreach ( $slides as $index => $slide ) : ?>
				<figure class="bsc-workshops__slide"<?php if ( $index <= $active + 1 ) : ?> data-position="<?php echo esc_attr( $index - $active ); ?>"<?php endif; ?>>
					<?php if ( $slide['video'] ) : ?>
						<video src="<?php echo esc_url( $slide['video'] ); ?>"<?php if ( $slide['poster'] ) : ?> poster="<?php echo esc_url( $slide['poster'] ); ?>"<?php endif; ?> preload="<?php echo $index <= $active + 1 ? 'metadata' : 'none'; ?>" controls playsinline aria-labelledby="talleres-video-caption-<?php echo esc_attr( $index ); ?>"></video>
					<?php else : ?>
						<img src="<?php echo esc_url( $slide['poster'] ); ?>" alt="" width="720" height="1278" loading="lazy">
					<?php endif; ?>
					<figcaption id="talleres-video-caption-<?php echo esc_attr( $index ); ?>"><?php echo esc_html( $slide['title'] ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php if ( $count > 1 ) : ?>
			<button class="bsc-workshops__carousel-arrow bsc-workshops__carousel-arrow--prev" type="button" data-workshop-direction="-1" aria-label="Ver taller anterior" hidden>
				<svg width="20" height="34" viewBox="0 0 20 34" fill="none" aria-hidden="true"><path d="M16 2 3 17l13 15" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<button class="bsc-workshops__carousel-arrow bsc-workshops__carousel-arrow--next" type="button" data-workshop-direction="1" aria-label="Ver siguiente taller" hidden>
				<svg width="20" height="34" viewBox="0 0 20 34" fill="none" aria-hidden="true"><path d="m4 2 13 15L4 32" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
			<span class="screen-reader-text" aria-live="polite" data-workshop-slide-status></span>
		<?php endif; ?>
	</div>
</section>
