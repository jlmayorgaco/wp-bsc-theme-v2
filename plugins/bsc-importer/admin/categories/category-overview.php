<?php

defined( 'ABSPATH' ) || exit;

function bsc_theme_importer_category_overview_config(): array {
	return array(
		'Skin Care' => array(
			'sk-tipo-piel',
			'sk-necesidades',
			'sk-rutina',
			'sk-ingredientes',
			'sk-marcas',
		),
		'Hair Care' => array(
			'hc-necesidades',
			'hc-rutina',
			'hc-marca',
		),
		'Make Up'   => array(
			'mk-productos',
			'mk-marcas',
		),
	);
}

function bsc_theme_importer_get_terms_for_parent_slug( string $parent_slug ): array {
	$parent = get_term_by( 'slug', $parent_slug, 'product_cat' );

	if (!$parent || is_wp_error( $parent )) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => (int) $parent->term_id,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if (is_wp_error( $terms )) {
		return array();
	}

	return $terms;
}

function bsc_theme_importer_get_category_label( string $slug ): string {
	$term = get_term_by( 'slug', $slug, 'product_cat' );

	if ($term && !is_wp_error( $term )) {
		return (string) $term->name;
	}

	return ucwords( str_replace( '-', ' ', $slug ) );
}

?>

<details class="bsc-importer-panel bsc-category-overview">
	<summary class="bsc-category-overview__header">
		<div>
			<h2><?php esc_html_e( 'Mapa de categorias', 'bubblesskincare' ); ?></h2>
			<p><?php esc_html_e( 'Vista rapida de las familias usadas por el importador y la navegacion.', 'bubblesskincare' ); ?></p>
		</div>
	</summary>

	<?php foreach (bsc_theme_importer_category_overview_config() as $group_label => $parent_slugs) : ?>
		<section class="bsc-category-group">
			<h3><?php echo esc_html( $group_label ); ?></h3>
			<div class="bsc-category-columns">
				<?php foreach ($parent_slugs as $parent_slug) : ?>
					<?php $terms = bsc_theme_importer_get_terms_for_parent_slug( $parent_slug ); ?>
					<article class="bsc-category-column">
						<h4><?php echo esc_html( bsc_theme_importer_get_category_label( $parent_slug ) ); ?></h4>
						<ul>
							<?php if (empty( $terms )) : ?>
								<li class="bsc-category-column__empty"><?php esc_html_e( 'Sin categorias hijas.', 'bubblesskincare' ); ?></li>
							<?php else : ?>
								<?php foreach ($terms as $term) : ?>
									<li>
										<strong><?php echo esc_html( $term->name ); ?></strong>
										<span><?php echo esc_html( $term->slug ); ?></span>
									</li>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
</details>
