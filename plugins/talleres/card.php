<?php
defined( 'ABSPATH' ) || exit;

$workshop = $args['workshop'];
$settings = $args['settings'];
$open = $args['open'];
$id = $workshop->ID;
$is_demo = bsc_workshops_is_demo( $id );
$preferred = $is_demo ? ( $settings['preferred_slot'] ?? '' ) : '';
$selected = isset( $open[ $preferred ] ) ? $preferred : array_key_first( $open );
$seats = $selected ? bsc_workshops_available( $open[ $selected ], $args['counts'][ $selected ] ?? 0 ) : 0;
$photo = $settings['image'] ? wp_get_attachment_image( $settings['image'], 'large', false, array( 'loading' => 'lazy', 'alt' => '' ) ) : '';
?>
<article class="bsc-workshops__card" aria-labelledby="workshop-<?php echo esc_attr( $id ); ?>">
	<?php if ( $args['month'] ) : ?><span class="bsc-workshops__month"><?php echo esc_html( $args['month'] ); ?></span><?php endif; ?>
	<?php if ( $photo ) : ?>
		<?php echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped attachment markup. ?>
	<?php else : ?>
		<img src="<?php echo esc_url( $args['assets'] . 'mesa.webp' ); ?>" width="1104" height="974" loading="lazy" alt="">
	<?php endif; ?>
	<div class="bsc-workshops__card-content">
		<header class="bsc-workshops__card-heading">
			<h2 id="workshop-<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $workshop->post_title ); ?></h2>
			<?php if ( $workshop->post_excerpt ) : ?><p><?php echo nl2br( esc_html( $workshop->post_excerpt ) ); ?></p><?php endif; ?>
		</header>
		<?php if ( ! $open ) : ?>
			<div class="bsc-workshops__sold-out">
				<div class="bsc-workshops__hearts" aria-hidden="true">
					<?php for ( $i = 0; $i < 3; ++$i ) : ?>
						<svg width="26" height="26" viewBox="0 0 32 32" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"><path d="M16 29C13 26 1 18 1 11a7.6 7.6 0 0 1 15-2 7.6 7.6 0 0 1 15 2c0 7-12 15-15 18Z"/></svg>
					<?php endfor; ?>
				</div>
				<h3><?php echo $settings['slots'] ? '¡Cupos agotados!' : 'Nuevas fechas próximamente'; ?></h3>
				<p>Déjanos tus datos para avisarte cuando abramos una nueva fecha de este taller coreano y sé de las primeras personas en reservar tu lugar.</p>
			</div>
		<?php endif; ?>
		<form class="bsc-workshops__booking" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="bsc_workshop_register">
			<input type="hidden" name="workshop" value="<?php echo esc_attr( $id ); ?>">
			<?php wp_nonce_field( 'bsc_workshop_register_' . $id, '_bsc_workshop_nonce', false ); ?>
			<div hidden aria-hidden="true"><label>Sitio web <input name="website" tabindex="-1" autocomplete="off"></label></div>
			<?php if ( $open ) : ?>
				<fieldset class="bsc-workshops__slots">
					<legend class="screen-reader-text">Elige un horario para <?php echo esc_html( $workshop->post_title ); ?></legend>
					<?php foreach ( $settings['slots'] as $key => $slot ) :
						$available = isset( $open[ $key ] ) ? bsc_workshops_available( $slot, $args['counts'][ $key ] ?? 0 ) : 0;
						?>
						<label class="bsc-workshops__slot">
							<input type="radio" name="slot" value="<?php echo esc_attr( $key ); ?>" data-seats="<?php echo esc_attr( $available ); ?>" <?php checked( $selected, $key ); ?> <?php disabled( 0, $available ); ?>>
							<span><?php echo esc_html( $slot['label'] ); ?><?php if ( ! $available ) : ?><span class="screen-reader-text"> · Sin cupos</span><?php endif; ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<p class="bsc-workshops__remaining" data-remaining aria-live="polite"><?php echo esc_html( bsc_workshops_seat_label( $seats ) ); ?></p>
				<div class="bsc-workshops__amount">
					<div class="bsc-workshops__stepper">
						<button type="button" data-step="-1" aria-label="Quitar un cupo" disabled>−</button>
						<input type="number" name="seats" value="1" min="1" max="<?php echo esc_attr( $seats ); ?>" aria-label="Cupos" required>
						<button type="button" data-step="1" aria-label="Añadir un cupo" <?php disabled( 1, $seats ); ?>>+</button>
					</div>
					<output class="bsc-workshops__price" data-price="<?php echo esc_attr( $settings['price'] ); ?>" aria-live="polite"><?php echo esc_html( $settings['price'] ? bsc_workshops_price_label( $settings['price'] ) : 'Gratis' ); ?></output>
				</div>
				<details class="bsc-workshops__details">
					<summary class="bsc-workshops__button">¡Reservar!</summary>
					<div class="bsc-workshops__details-inner">
			<?php endif; ?>
			<div class="bsc-workshops__fields">
				<label>Nombre completo: <input name="name" autocomplete="name" maxlength="120" required></label>
				<label>Correo electrónico: <input name="email" type="email" autocomplete="email" maxlength="254" required></label>
			</div>
			<?php if ( $open ) : ?>
				<?php if ( $is_demo ) : ?>
					<p class="bsc-workshops__form-note">Taller de demostración local. Puedes usar un correo de prueba.</p>
				<?php elseif ( $settings['price'] ) : ?>
					<p class="bsc-workshops__form-note">Te contactaremos para coordinar el pago.</p>
				<?php endif; ?>
			<?php endif; ?>
			<input type="hidden" name="consent" value="yes">
			<p class="bsc-workshops__form-note">Al enviar, aceptas recibir correos sobre <?php echo $open ? 'tu reserva' : 'los cupos de este taller'; ?>.<?php if ( $args['privacy_url'] ) : ?> <a href="<?php echo esc_url( $args['privacy_url'] ); ?>">Privacidad</a><?php endif; ?></p>
			<button type="submit" class="bsc-workshops__button<?php echo $open ? '' : ' bsc-workshops__button--outline'; ?>"><?php echo $open ? 'Enviar solicitud' : '¡Avísame!'; ?></button>
			<?php if ( $open ) : ?>
					</div>
				</details>
			<?php endif; ?>
		</form>
	</div>
</article>
