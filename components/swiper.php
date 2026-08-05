<?php
/**
 * Home hero slider with responsive desktop and mobile images.
 *
 * @package BSC_2_0
 */

$slides_query = new WP_Query(
	array(
		'post_type'      => 'home_slide',
		'post_status'    => 'publish',
		'posts_per_page' => 10,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
			'ID'         => 'ASC',
		),
		'no_found_rows'  => true,
	)
);

$slides = array();

if ($slides_query->have_posts()) {
	while ($slides_query->have_posts()) {
		$slides_query->the_post();

		$slide_post_id    = get_the_ID();
		$desktop_image_id = (int) get_post_thumbnail_id( $slide_post_id );
		$mobile_image_id  = (int) get_post_meta( $slide_post_id, '_slide_mobile_image_id', true );

		if ( $desktop_image_id > 0 && ! wp_attachment_is_image( $desktop_image_id ) ) {
			$desktop_image_id = 0;
		}
		if ( $mobile_image_id > 0 && ! wp_attachment_is_image( $mobile_image_id ) ) {
			$mobile_image_id = 0;
		}

		// Existing slides keep their featured image as desktop. Either format can
		// safely back up the other until both responsive assets are configured.
		$render_desktop_image_id = $desktop_image_id ? $desktop_image_id : $mobile_image_id;
		$render_mobile_image_id  = $mobile_image_id ? $mobile_image_id : $desktop_image_id;

		$slides[] = array(
			'title'            => get_the_title(),
			'subtitle'         => get_post_meta( $slide_post_id, '_slide_subtitle', true ),
			'desktop_image_id' => $render_desktop_image_id,
			'mobile_image_id'  => $render_mobile_image_id,
			'button_text'      => get_post_meta( $slide_post_id, '_slide_button_text', true ),
			'button_link'      => get_post_meta( $slide_post_id, '_slide_button_link', true ),
		);
	}
	wp_reset_postdata();
}

$repeated_slides = $slides;

if (count( $repeated_slides ) > 0) : ?>

	<div class="bsc bsc__home-swiper">

		<div class="swiper-wrapper">
			<?php foreach ($repeated_slides as $i => $slide) : ?>
			<div class="swiper-slide bsc-swiper__slide">
				<div class="slide__image">
				<?php
				$image_attrs = array(
					'alt'      => $slide['title'],
					'loading'  => 0 === $i ? 'eager' : 'lazy',
					'decoding' => 0 === $i ? 'sync' : 'async',
					'sizes'    => '100vw',
				);

				if (0 === $i) {
					$image_attrs['fetchpriority'] = 'high';
				}

				$desktop_image_id = (int) $slide['desktop_image_id'];
				$mobile_image_id  = (int) $slide['mobile_image_id'];

				if ( $desktop_image_id > 0 ) {
					?>
					<picture>
						<?php if ( $mobile_image_id > 0 && $mobile_image_id !== $desktop_image_id ) : ?>
							<?php
							$mobile_srcset = wp_get_attachment_image_srcset( $mobile_image_id, 'full' );
							$mobile_srcset = $mobile_srcset ? $mobile_srcset : wp_get_attachment_image_url( $mobile_image_id, 'full' );
							?>
							<source media="(max-width: 800px)" srcset="<?php echo esc_attr( (string) $mobile_srcset ); ?>" sizes="100vw">
						<?php endif; ?>
						<?php echo wp_get_attachment_image( $desktop_image_id, 'full', false, $image_attrs ); ?>
					</picture>
					<?php
				} else {
					printf(
						'<img src="%s" alt="%s" width="1440" height="700" loading="%s" decoding="%s"%s>',
						esc_url( get_template_directory_uri() . '/images/bsc__placeholder_product.jpg' ),
						esc_attr( $slide['title'] ),
						esc_attr( $image_attrs['loading'] ),
						esc_attr( $image_attrs['decoding'] ),
						0 === $i ? ' fetchpriority="high"' : ''
					);
				}
				?>
				</div>
				<div class="slide__content">
				<div class="slide__container">
					<div class="slide__hero">
					<h1 class="hero__title"><?php echo esc_html( $slide['title'] ); ?></h1>
					<p class="hero__text"><?php echo esc_html( $slide['subtitle'] ); ?></p>
					<a class="hero__button" href="<?php echo esc_url( $slide['button_link'] ); ?>">
						<?php echo esc_html( $slide['button_text'] ); ?>
					</a>
					</div>
				</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Navigation Arrows -->
		<div class="swiper-button-prev bsc-swiper__nav bsc-swiper__nav--prev"></div>
		<div class="swiper-button-next bsc-swiper__nav bsc-swiper__nav--next"></div>

	</div>

<?php else : ?>

	<!-- Fallback hero: shown when no Home Slide CPT posts exist.
		To replace: WP Admin → Home Slides → Add New → set title, subtitle, featured image, button. -->
	<div class="bsc bsc__home-swiper bsc__home-swiper--fallback">
		<div class="swiper-slide bsc-swiper__slide">
		<div class="slide__image">
			<img
			src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc__placeholder_product.jpg"
			alt="Bubbles Skin Care"
			width="1440"
			height="700"
			loading="eager"
			fetchpriority="high"
			decoding="sync"
			>
		</div>
		<div class="slide__content">
			<div class="slide__container">
			<div class="slide__hero fade-in">
				<h1 class="hero__title">K-Beauty para tu piel</h1>
				<p class="hero__text">Descubre nuestra selección de skincare coreano</p>
				<a class="hero__button" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Ver tienda</a>
			</div>
			</div>
		</div>
		</div>
	</div>

<?php endif; ?>


<!-- Swiper init enqueued via js/swiper-init.js -->
