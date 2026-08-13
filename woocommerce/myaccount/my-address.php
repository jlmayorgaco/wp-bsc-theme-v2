<?php
/**
 * BSC custom account addresses view.
 *
 * Reviewed against WooCommerce my-address.php 9.3.0.
 *
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id   = get_current_user_id();
$get_addresses = array(
	'billing' => html_entity_decode( 'Facturaci&oacute;n y Entregas', ENT_QUOTES, 'UTF-8' ),
);

$addresses = array();
foreach ( $get_addresses as $type => $label ) {
	$addresses[ $type ] = array(
		'label'    => $label,
		'address'  => wc_get_account_formatted_address( $type ),
		'edit_url' => wc_get_endpoint_url( 'edit-address', $type ),
		'type'     => $type,
	);
}

$pretty_fields = array(
	'first_name' => 'Nombre',
	'last_name'  => 'Apellido',
	'company'    => 'Empresa',
	'address_1'  => html_entity_decode( 'Direcci&oacute;n', ENT_QUOTES, 'UTF-8' ),
	'address_2'  => 'Complemento',
	'city'       => 'Ciudad',
	'state'      => 'Departamento',
	'postcode'   => html_entity_decode( 'C&oacute;digo postal', ENT_QUOTES, 'UTF-8' ),
	'country'    => html_entity_decode( 'Pa&iacute;s', ENT_QUOTES, 'UTF-8' ),
	'phone'      => html_entity_decode( 'Tel&eacute;fono', ENT_QUOTES, 'UTF-8' ),
	'email'      => 'Email',
);
?>

<section class="bsc__address-wrapper">
	<div class="bsc__address-container">

		<div class="bsc__address-illustration">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/images/profile/Caja_carita.png" alt="BSC Skin Care First" />
		</div>

		<?php foreach ( $addresses as $type => $data ) : ?>
		<div class="bsc__address-card">
			<h3 id="account-address-summary"><?php echo esc_html( $data['label'] ); ?></h3>
			<p class="bsc__address-subtitle"><?php echo esc_html( html_entity_decode( 'Direcci&oacute;n y datos', ENT_QUOTES, 'UTF-8' ) ); ?></p>

			<div class="bsc__address-content">
				<?php
				$has_any = false;

				foreach ( $pretty_fields as $key => $label ) {
					$meta_key = $type . '_' . $key;
					$value    = get_user_meta( $customer_id, $meta_key, true );

					if ( $value === '' || $value === null ) {
						continue;
					}

					$has_any = true;

					if ( $key === 'country' ) {
						$countries = WC()->countries->get_countries();
						if ( isset( $countries[ $value ] ) ) {
							$value = $countries[ $value ];
						}
					}
					if ( $key === 'state' ) {
						$states = WC()->countries->get_states( get_user_meta( $customer_id, $type . '_country', true ) );
						if ( is_array( $states ) && isset( $states[ $value ] ) ) {
							$value = $states[ $value ];
						}
					}
					if ( $key === 'city' && function_exists( 'bsc_get_colombia_shipping_places' ) ) {
						foreach ( bsc_get_colombia_shipping_places() as $_dept => $cities ) {
							if ( is_array( $cities ) && isset( $cities[ $value ] ) ) {
								$value = $cities[ $value ];
								break;
							}
						}
					}

					echo '<div class="bsc__address-row"><strong>' . esc_html( $label ) . '</strong>: ' . esc_html( $value ) . '</div>';
				}

				if ( ! $has_any ) {
					echo '<em>No hay direcci&oacute;n guardada.</em>';
				}
				?>
			</div>

			<br>
			<a class="bsc__button bsc__button--compact-pill bsc__address-button" href="<?php echo esc_url( $data['edit_url'] ); ?>"><?php echo esc_html( html_entity_decode( '&#161; Editar datos !', ENT_QUOTES, 'UTF-8' ) ); ?></a>
		</div>
		<?php endforeach; ?>

	</div>
</section>
