<?php
/**
 * Shared renderer for BSC static legal / FAQ pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bsc_render_static_page_template' ) ) {
	/**
	 * Render the shared BSC static page wrapper.
	 *
	 * @param array{
	 *   page_class?: string,
	 *   title?: string,
	 *   title_html?: string,
	 *   subtitle?: string,
	 *   body_class?: string,
	 *   fallback_callback?: callable|null
	 * } $config Template configuration.
	 */
	function bsc_render_static_page_template( array $config ): void {
		$page_id           = get_queried_object_id();
		$page_class        = isset( $config['page_class'] ) ? (string) $config['page_class'] : 'page-static';
		$title             = isset( $config['title'] ) ? (string) $config['title'] : ( $page_id ? (string) get_the_title( $page_id ) : '' );
		$title_html        = isset( $config['title_html'] ) ? (string) $config['title_html'] : '';
		$subtitle          = isset( $config['subtitle'] ) ? (string) $config['subtitle'] : '';
		$body_class        = isset( $config['body_class'] ) ? trim( (string) $config['body_class'] ) : '';
		$fallback_callback = $config['fallback_callback'] ?? null;
		$wrapper_class     = trim( 'bsc__static-body ' . $body_class );

		get_header();
		?>
		<main class="bsc bsc__page <?php echo esc_attr( $page_class ); ?>">
			<section class="bsc__static-hero">
				<div class="bsc__static-hero__container">
					<h1 class="bsc__static-hero__title">
						<?php
						if ( '' !== $title_html ) {
							echo wp_kses( $title_html, array( 'strong' => array() ) );
						} else {
							echo esc_html( $title );
						}
						?>
					</h1>
					<div class="bsc__static-hero__wave" aria-hidden="true"></div>
					<?php if ( '' !== $subtitle ) : ?>
						<p class="bsc__static-hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="bsc__static-content">
				<div class="bsc__static-content__container">
					<?php if ( have_posts() ) : ?>
						<?php
						while ( have_posts() ) :
							the_post();
							?>
							<div class="<?php echo esc_attr( $wrapper_class ); ?>">
								<?php
								$raw_content = trim( (string) get_post_field( 'post_content', get_the_ID() ) );

								if ( '' !== $raw_content ) {
									the_content();
								} elseif ( is_callable( $fallback_callback ) ) {
									call_user_func( $fallback_callback );
								}
								?>
							</div>
						<?php endwhile; ?>
					<?php elseif ( is_callable( $fallback_callback ) ) : ?>
						<div class="<?php echo esc_attr( $wrapper_class ); ?>">
							<?php call_user_func( $fallback_callback ); ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		</main>
		<?php
		get_footer();
	}
}
