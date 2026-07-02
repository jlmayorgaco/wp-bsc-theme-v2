<?php
/**
 * Edit address form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-edit-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.3.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? esc_html__( 'Billing address', 'woocommerce' ) : esc_html__( 'Shipping address', 'woocommerce' );

do_action( 'woocommerce_before_edit_account_address_form' ); ?>

<?php if ( ! $load_address ) : ?>
	<?php wc_get_template( 'myaccount/my-address.php' ); ?>
<?php else : ?>

	<form class="bsc__shipping-address bsc__account-address-v2 bsc__account-address-v2--<?php echo esc_attr( $load_address ); ?> bsc__checkout-form" method="post" novalidate>

		<h2>
			<?php echo ( 'billing' === $load_address ) ? esc_html__( 'Datos de Facturación 2', 'bsc-2-0' ) : esc_html__( 'Datos de Envío', 'bsc-2-0' ); ?>
		</h2>
		
		<?php // @codingStandardsIgnoreLine ?>

		<div class="woocommerce-address-fields">
			<?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); ?>

			<div class="woocommerce-address-fields__field-wrapper">

				<?php
				$bsc_address_placeholders = array(
					'first_name'              => 'Nombres',
					'last_name'               => 'Apellidos',
					'country'                 => html_entity_decode( 'Selecciona un pa&iacute;s', ENT_QUOTES, 'UTF-8' ),
					'address_1'               => html_entity_decode( 'Direcci&oacute;n de entrega', ENT_QUOTES, 'UTF-8' ),
					'address_2'               => html_entity_decode( 'Complemento de direcci&oacute;n', ENT_QUOTES, 'UTF-8' ),
					'state'                   => 'Selecciona un departamento',
					'city'                    => 'Selecciona una ciudad',
					'postcode'                => html_entity_decode( 'C&oacute;digo postal', ENT_QUOTES, 'UTF-8' ),
					'phone'                   => html_entity_decode( 'N&uacute;mero de tel&eacute;fono', ENT_QUOTES, 'UTF-8' ),
					'email'                   => html_entity_decode( 'Correo electr&oacute;nico', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_first_name' => 'Nombres',
					$load_address . '_last_name'  => 'Apellidos',
					$load_address . '_country'    => html_entity_decode( 'Selecciona un pa&iacute;s', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_address_1'  => html_entity_decode( 'Direcci&oacute;n de entrega', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_address_2'  => html_entity_decode( 'Complemento de direcci&oacute;n', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_state'      => 'Selecciona un departamento',
					$load_address . '_city'       => 'Selecciona una ciudad',
					$load_address . '_postcode'   => html_entity_decode( 'C&oacute;digo postal', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_phone'      => html_entity_decode( 'N&uacute;mero de tel&eacute;fono', ENT_QUOTES, 'UTF-8' ),
					$load_address . '_email'      => html_entity_decode( 'Correo electr&oacute;nico', ENT_QUOTES, 'UTF-8' ),
				);

				foreach ( $address as $key => $field ) {
					if ( isset( $bsc_address_placeholders[ $key ] ) ) {
						$field['placeholder'] = $bsc_address_placeholders[ $key ];
					}

					woocommerce_form_field( $key, $field, wc_get_post_data_by_key( $key, $field['value'] ) );
				}
				?>
			</div>

			<?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); ?>

			<p class="button--save-profile">
				<button type="submit" class="bsc__button bsc__address-button button<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" name="save_address" value="<?php esc_attr_e( '¡ Guardar Cambios !', 'woocommerce' ); ?>"><?php esc_html_e( '¡ Guardar Cambios !', 'woocommerce' ); ?></button>
				<?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
				<input type="hidden" name="action" value="edit_address" />
			</p>
		</div>

	</form>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
