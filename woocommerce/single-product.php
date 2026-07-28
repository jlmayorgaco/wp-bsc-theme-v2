<?php
/**
 * BSC custom single product layout.
 *
 * Reviewed against WooCommerce single-product.php 1.6.4.
 *
 * @package WooCommerce\Templates
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $product;
if (!$product instanceof WC_Product) {
	$product = wc_get_product( get_the_ID() );
}

require_once get_template_directory() . '/helpers/recommended_products.php';

if (!class_exists( 'BSC_Products_Card' )) {
	require_once get_template_directory() . '/components/products/card.php';
}

if (!class_exists( 'BSC_Products_Sliders' )) {
	require_once get_template_directory() . '/components/products/slider.php';
}

if (!class_exists( 'BSC_Product_Category_Meta' )) {
	require_once get_template_directory() . '/components/products/categories-meta.php';
}

$card = new BSC_Products_Card();
$card->setProduct( $product );

$meta_renderer = new BSC_Product_Category_Meta( $product );

$product_id = get_the_ID();
$variant_matrix = function_exists( 'bsc_get_product_variant_matrix_public_data' )
	? bsc_get_product_variant_matrix_public_data( (int) $product_id )
	: array();
$color_variants = function_exists( 'bsc_get_product_color_variants' )
	? bsc_get_product_color_variants( (int) $product_id )
	: array();
$size_variants = function_exists( 'bsc_get_product_size_variants' )
	? bsc_get_product_size_variants( (int) $product_id )
	: array();
$base_display_price = wc_format_decimal( wc_get_price_to_display( $product ), wc_get_price_decimals() );
?>

<main class="bsc bsc__product--page">
	<div class="bsc__container">
	<nav class="bsc__breadcrumbs">
		<?php
		$group_slugs = array( 'group-skin-care', 'group-hair-care', 'group-make-up' );
		$group_term  = null;
		$terms       = get_the_terms( $product_id, 'product_cat' );

		if ($terms && !is_wp_error( $terms )) {
			foreach ($terms as $term) {
				if (in_array( $term->slug, $group_slugs, true )) {
					$group_term = $term;
					break;
				}

				foreach (get_ancestors( $term->term_id, 'product_cat' ) as $ancestor_id) {
					$ancestor = get_term( $ancestor_id, 'product_cat' );
					if ($ancestor && !is_wp_error( $ancestor ) && in_array( $ancestor->slug, $group_slugs, true )) {
						$group_term = $ancestor;
						break 2;
					}
				}
			}
		}
		?>
		<nav class="woocommerce-breadcrumb" aria-label="Breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
		<?php
		if ($group_term) :
			$group_link = get_term_link( $group_term );
			?>
			&nbsp;/&nbsp;<a href="<?php echo esc_url( is_wp_error( $group_link ) ? '#' : $group_link ); ?>"><?php echo esc_html( $group_term->name ); ?></a>
		<?php endif; ?>
		&nbsp;/&nbsp;<?php the_title(); ?>
		</nav>
	</nav>

	<div class="bsc__product-layout">
		<div class="bsc__product-gallery">
		<?php woocommerce_show_product_images(); ?>
		</div>

		<div class="bsc__product-info">
		<h1 class="bsc__title bsc__title--product">
			<strong><?php the_title(); ?></strong>
		</h1>

		<div class="bsc__product-price bsc__price">
			<?php woocommerce_template_single_price(); ?>
		</div>

		<div class="bsc__product-shortdesc">
			<?php the_excerpt(); ?>
		</div>

		<?php if ( ! empty( $color_variants ) || ! empty( $size_variants ) ) : ?>
		<div class="bsc-product-options" data-bsc-product-options data-base-price="<?php echo esc_attr( $base_display_price ); ?>">
			<input type="hidden" data-bsc-selected-color-name value="" />
			<input type="hidden" data-bsc-selected-color-hex value="" />
			<input type="hidden" data-bsc-selected-size-name value="" />
			<input type="hidden" data-bsc-selected-variant-key value="" />
			<script type="application/json" data-bsc-product-variant-matrix><?php echo wp_json_encode( $variant_matrix ); ?></script>

			<?php if ( ! empty( $color_variants ) ) : ?>
			<section class="bsc-product-options__group bsc-product-options__group--color" aria-label="Color">
				<div class="bsc-product-options__header">
					<span class="bsc-product-options__label">Tono: </span>
					<span class="bsc-product-options__selected" data-bsc-color-current-label>Escoge un tono</span>
				</div>
				<div class="bsc-product-options__color-list" data-bsc-color-list role="listbox" aria-label="Color">
					<?php foreach ( $color_variants as $index => $variant ) : ?>
					<?php
					$is_selected_color = false;
					$color_label       = sprintf( 'Color %s', (string) $variant['name'] );
					?>
					<button
						type="button"
						class="bsc-product-options__color-option<?php echo $is_selected_color ? ' is-selected' : ''; ?>"
						data-bsc-color-option
						data-name="<?php echo esc_attr( $variant['name'] ); ?>"
						data-hex="<?php echo esc_attr( $variant['hex'] ); ?>"
						data-price="<?php echo esc_attr( $variant['price'] ); ?>"
						role="option"
						aria-selected="<?php echo $is_selected_color ? 'true' : 'false'; ?>"
						aria-label="<?php echo esc_attr( $color_label ); ?>"
						title="<?php echo esc_attr( (string) $variant['name'] ); ?>"
					>
						<span class="bsc-product-options__option-swatch" data-bsc-option-swatch data-color-hex="<?php echo esc_attr( $variant['hex'] ); ?>" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php echo esc_html( (string) $variant['name'] ); ?></span>
					</button>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>

			<?php if ( ! empty( $size_variants ) ) : ?>
			<section class="bsc-product-options__group bsc-product-options__group--size" aria-label="Tamaño">
				<div class="bsc-product-options__header">
					<span class="bsc-product-options__label">Tama&ntilde;o</span>
					<span class="bsc-product-options__selected" data-bsc-size-current-label>Escoge un tama&ntilde;o</span>
				</div>
				<div class="bsc-product-options__size-list" role="listbox">
					<?php foreach ( $size_variants as $index => $variant ) : ?>
					<?php
					$is_selected_size = false;
					?>
					<button
						type="button"
						class="bsc-product-options__size-option<?php echo $is_selected_size ? ' is-selected' : ''; ?>"
						data-bsc-size-option
						data-name="<?php echo esc_attr( $variant['name'] ); ?>"
						data-price="<?php echo esc_attr( $variant['price'] ); ?>"
						role="option"
						aria-selected="<?php echo $is_selected_size ? 'true' : 'false'; ?>"
					>
						<span class="bsc-product-options__option-name"><?php echo esc_html( $variant['name'] ); ?></span>
						<?php if ( $variant['price'] !== '' ) : ?>
						<span class="bsc-product-options__option-price"><?php echo wp_kses_post( wc_price( (float) $variant['price'] ) ); ?></span>
						<?php endif; ?>
					</button>
					<?php endforeach; ?>
				</div>
			</section>
			<?php endif; ?>
			<p class="bsc-product-options__stock" data-bsc-variant-stock-status aria-live="polite"></p>
		</div>
		<?php endif; ?>

		<div class="bsc__product-cart bsc__product-cart--add-to-cart-button">
			<?php $card->render_button( '', true ); ?>
		</div>

		<div class="bsc__product-meta">
			<?php $meta_renderer->render(); ?>
		</div>
		</div>
	</div>

	<?php
	$cover_desktop_id = (int) get_post_meta( $product_id, 'bsc_cover_desktop', true );
	$cover_mobile_id  = (int) get_post_meta( $product_id, 'bsc_cover_mobile', true );

	// Fallback: mobile → desktop, desktop → mobile
	$render_desktop = $cover_desktop_id ?: $cover_mobile_id;
	$render_mobile  = $cover_mobile_id ?: $cover_desktop_id;

	$extra_images = array();
	for ($i = 1; $i <= 3; $i++) {
		$extra_id = (int) get_post_meta( $product_id, "_bsc_extra_image_{$i}", true );
		if ($extra_id > 0) {
			$extra_images[ $i ] = $extra_id;
		}
	}
	?>

	<?php if ($render_desktop || $render_mobile) : ?>
		<div class="bsc__product-cover">
		<?php if ($render_desktop) : ?>
			<div class="bsc__product-cover--desktop">
			<?php
			echo wp_get_attachment_image(
				$render_desktop,
				'full',
				false,
				array(
					'class'    => 'bsc__product-cover-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			?>
			</div>
		<?php endif; ?>

		<?php if ($render_mobile) : ?>
			<div class="bsc__product-cover--mobile">
			<?php
			echo wp_get_attachment_image(
				$render_mobile,
				'full',
				false,
				array(
					'class'    => 'bsc__product-cover-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			?>
			</div>
		<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if (!empty( $extra_images )) : ?>
		<div class="bsc__product-extra-images">
		<?php foreach ($extra_images as $index => $extra_image_id) : ?>
			<div class="bsc__product-extra-image bsc__product-extra-image--<?php echo esc_attr( $index ); ?>">
			<?php
			echo wp_get_attachment_image(
				$extra_image_id,
				'full',
				false,
				array(
					'class'    => 'bsc__product-extra-image-img',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			);
			?>
			</div>
		<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="bsc__product-recommendations">
		<div class="section__container">
		<h1 class="bsc__title">
			<strong>Recomendados</strong> para ti
		</h1>

		<?php
		$key            = 'bsc-recomended-products';
		$products_limit = 5;
		$skus           = get_related_product_skus( $product_id, $products_limit );

		if (!empty( $skus )) {
			$slider = new BSC_Products_Sliders();
			$slider->setSkus( $skus );
			$slider->setSlug( $key );
			$slider->render();
		} else {
			echo '<p class="bsc__empty-recommendations">No hay productos disponibles.</p>';
		}
		?>
		</div>
	</div>

	</div>
</main>

<?php get_footer(); ?>
