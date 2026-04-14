<?php
defined('ABSPATH') || exit;


add_action('wp_ajax_bsc_reload_city_fields', 'bsc_reload_city_fields');
add_action('wp_ajax_nopriv_bsc_reload_city_fields', 'bsc_reload_city_fields');

function bsc_reload_city_fields() {
  check_ajax_referer('bsc_ajax_action', 'nonce');

  $checkout = WC()->checkout();
  $fields = $checkout->get_checkout_fields();
  $state = sanitize_text_field($_POST['billing_state'] ?? '');
  $country = sanitize_text_field($_POST['billing_country'] ?? 'CO') ?: 'CO';

  // The Colombia city plugin reads these through WC_Checkout::get_value().
  $_POST['billing_country'] = $country;
  $_POST['billing_state'] = $state;

  $html = bsc_render_checkout_city_field(
    'billing_city',
    $fields['billing']['billing_city'],
    $country,
    $state
  );

  wp_send_json_success(['html' => $html]);
}

function bsc_render_checkout_city_field( string $key, array $field, string $country, string $state ): string {
  $cities = bsc_get_checkout_cities_for_state( $country, $state );

  if ( empty( $cities ) ) {
    ob_start();
    woocommerce_form_field( $key, $field, '' );
    return ob_get_clean();
  }

  $field_id = $field['id'] ?? $key;
  $classes = array_filter( array_merge(
    [ 'form-row' ],
    (array) ( $field['class'] ?? [] )
  ) );
  $input_classes = array_filter( array_merge(
    [ 'city_select' ],
    (array) ( $field['input_class'] ?? [] )
  ) );
  $label_classes = array_filter( (array) ( $field['label_class'] ?? [] ) );
  $placeholder = $field['placeholder'] ?? 'Selecciona una ciudad';
  $label = $field['label'] ?? '';
  $required = ! empty( $field['required'] );

  ob_start();
  ?>
  <p class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" id="<?php echo esc_attr( $field_id ); ?>_field" data-priority="<?php echo esc_attr( $field['priority'] ?? '' ); ?>">
    <?php if ( $label ) : ?>
      <label for="<?php echo esc_attr( $field_id ); ?>" class="<?php echo esc_attr( implode( ' ', $label_classes ) ); ?>">
        <?php echo esc_html( $label ); ?>
        <?php if ( $required ) : ?>
          <abbr class="required" title="<?php esc_attr_e( 'required', 'woocommerce' ); ?>">*</abbr>
        <?php endif; ?>
      </label>
    <?php endif; ?>
    <select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $field_id ); ?>" class="<?php echo esc_attr( implode( ' ', $input_classes ) ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>">
      <option value=""><?php echo esc_html( $placeholder ); ?></option>
      <?php foreach ( $cities as $city_code => $city_name ) : ?>
        <option value="<?php echo esc_attr( $city_code ); ?>"><?php echo esc_html( $city_name ); ?></option>
      <?php endforeach; ?>
    </select>
  </p>
  <?php

  return ob_get_clean();
}

function bsc_get_checkout_cities_for_state( string $country, string $state ): array {
  if ( $country !== 'CO' || $state === '' || ! function_exists( 'bsc_get_colombia_shipping_places' ) ) {
    return [];
  }

  $places = bsc_get_colombia_shipping_places();
  if ( isset( $places[ $state ] ) && is_array( $places[ $state ] ) ) {
    return $places[ $state ];
  }

  $normalized_state = function_exists( 'bsc_normalize_shipping_text' )
    ? bsc_normalize_shipping_text( $state )
    : strtolower( trim( $state ) );

  foreach ( $places as $place_state => $cities ) {
    $normalized_place_state = function_exists( 'bsc_normalize_shipping_text' )
      ? bsc_normalize_shipping_text( (string) $place_state )
      : strtolower( trim( (string) $place_state ) );

    if ( $normalized_state === $normalized_place_state && is_array( $cities ) ) {
      return $cities;
    }
  }

  return [];
}





?>
