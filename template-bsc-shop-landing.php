<?php
/**
 * Template Name: BSC Shop Landing (K-Beauty Paraiso)
 * Description: Landing hero + main K-Beauty groups.
 */

get_header();
?>

<div class="bsc bsc__shop">
	<div class="bsc__container">

	<nav class="bsc__shop-nav" aria-label="<?php esc_attr_e( 'Categoria actual', 'bsc-2-0' ); ?>">
		<a class="active">K-Beauty</a>
	</nav>

	<section class="bsc-hero">
		<div class="bsc-hero__icon">
		<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bsc_rainbow.png"
			alt="" loading="lazy" aria-hidden="true">
		</div>
		<h1 class="bsc-hero__title">Paraiso de <strong>K-Beauty</strong></h1>

		<p class="bsc-hero__quote">
		&ldquo;Hace mas de 15 anos probe mi primer producto coreano y desde entonces quede completamente enamorada del K-Beauty.
		Con los anos segui explorando este universo: probando nuevas formulas, aprendiendo de las tendencias y viajando a Corea
		para conocer de cerca su increible tecnologia. Asi nacio BSC: escuchando a nuestra comunidad,
		sonando con un espacio donde el K-Beauty se sintiera cercano, real y confiable. <strong>Bubbles es literalmente un paraiso K-Beauty:
		aqui no solo encuentras marcas cuidadosamente seleccionadas con los mas altos estandares coreanos,
		tambien te ayudamos a crear una rutina efectiva, personalizada y pensada para tu piel :)</strong>&rdquo;
		</p>
		<p class="bsc-hero__author">
		Male
		<img
			src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/bsc_icon_white_heart.png"
			alt=""
			width="50"
			decoding="async"
			aria-hidden="true"
		/>
		</p>

		<div class="bsc-hero__divider"></div>
		<h2 class="bsc-hero__subtitle bsc__title">
		<strong>Bienvenido</strong> al paraiso del K-Beauty <strong>Bubble lover</strong>!
		</h2>
	</section>

	<?php
	$groups = array(
		array(
			'slug'  => 'group-skin-care',
			'title' => 'SKIN CARE',
			'image' => 'images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
			'text'  => 'Tu rutina <strong>coreana empieza aqui</strong>: limpiadores, esencias, serums, contornos, mascarillas y mas para una piel saludable todos los dias.',
		),
		array(
			'slug'  => 'group-hair-care',
			'title' => 'HAIR CARE',
			'image' => 'images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
			'text'  => 'Cuidado capilar coreano para limpiar, hidratar y tratar tu pelo con rutinas faciles de seguir.',
		),
		array(
			'slug'  => 'group-make-up',
			'title' => 'MAKE UP',
			'image' => 'images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
			'text'  => 'Maquillaje coreano para un acabado fresco, luminoso y comodo durante todo el dia.',
		),
	);
	?>

	<section class="bsc-kb-grid">
		<?php foreach ( $groups as $group ) : ?>
			<?php
			$base      = trailingslashit( home_url( '/product-category/' ) );
			$term_link = $base . $group['slug'] . '/';
			?>
		<article class="bsc-kb-card">
			<a href="<?php echo esc_url( $term_link ); ?>" class="bsc-kb-card__link">
			<div class="bsc-kb-card__imgwrap">
				<div class="bsc-kb-card__back">
				<?php
				bsc_responsive_theme_image(
					$group['image'],
					$group['title'],
					array(
						'loading' => 'lazy',
					),
					'(max-width: 768px) 100vw, 33vw'
				);
				?>
				</div>

				<div class="bsc-kb-card__front">
				<img class="bsc-kb-card__icon" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/shop/hear_icon.png" alt="" width="25" aria-hidden="true">
				<p class="bsc-kb-text">
					<?php echo wp_kses( $group['text'], array( 'strong' => array() ) ); ?>
				</p>
				</div>
			</div>
			<div class="bsc-kb-card__label">
				<?php echo esc_html( $group['title'] ); ?>
			</div>
			</a>
		</article>
		<?php endforeach; ?>
	</section>

	</div>
</div>

<?php get_footer(); ?>
