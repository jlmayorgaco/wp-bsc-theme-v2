<?php
defined('ABSPATH') || exit;

$checkout = WC()->checkout();
$fields   = $checkout->get_checkout_fields();
$countries = WC()->countries->get_countries();
$country_label = $countries['CO'] ?? 'Colombia';

$placeholder_map = [
  'billing_first_name'    => 'Nombres',
  'billing_last_name'     => 'Apellidos',
  'billing_cedula'        => 'Número de cédula',
  'billing_email'         => 'Correo electrónico',
  'billing_phone'         => 'Número de teléfono',
  'billing_country'       => 'Selecciona un país',
  'billing_state'         => 'Selecciona un departamento',
  'billing_city'          => 'Selecciona una ciudad',
  'billing_postcode'      => 'Código postal',
  'billing_address_1'     => 'Dirección de entrega',
  'billing_address_2'     => 'Complemento de dirección',
  'shipping_country'      => 'Selecciona un país',
  'shipping_state'        => 'Selecciona un departamento',
  'shipping_city'         => 'Selecciona una ciudad',
  'shipping_postcode'     => 'Código postal',
  'shipping_address_1'    => 'Dirección de entrega',
  'order_comments'        => 'Notas del pedido (opcional)',
];

foreach (['billing', 'shipping'] as $group) {
  if (isset($fields[$group])) {
    foreach ($fields[$group] as $key => &$field) {
      if (isset($placeholder_map[$key])) {
        $field['placeholder'] = $placeholder_map[$key];
      } elseif (!isset($field['placeholder']) && isset($field['label'])) {
        $field['placeholder'] = $field['label'];
      }
      $field['class'][] = 'bsc__field';
    }
  }
}

if (isset($fields['order']['order_comments'])) {
  $fields['order']['order_comments']['placeholder'] = $placeholder_map['order_comments'];
  $fields['order']['order_comments']['class'][] = 'bsc__field';
}

$render_locked_country_field = static function (string $field_id, string $input_name, string $label, string $country_label): void {
  $locked_select_id = $field_id . '_locked';
  ?>
  <p class="form-row form-row-wide bsc__field bsc__field--locked-country" id="<?php echo esc_attr($field_id); ?>_field">
    <label for="<?php echo esc_attr($locked_select_id); ?>"><?php echo esc_html($label); ?></label>
    <select id="<?php echo esc_attr($locked_select_id); ?>" class="country_to_state country_select bsc__locked-country-select" disabled="disabled" aria-disabled="true">
      <option value="CO" selected="selected"><?php echo esc_html($country_label); ?></option>
    </select>
    <input type="hidden" name="<?php echo esc_attr($input_name); ?>" id="<?php echo esc_attr($field_id); ?>" value="CO">
  </p>
  <?php
};
?>
<div class="checkout bsc__checkout-form">
  <div class="bsc__checkout-section">

    <div class="bsc__grid-2">
      <?php
        woocommerce_form_field('billing_first_name', $fields['billing']['billing_first_name'], $checkout->get_value('billing_first_name'));
        woocommerce_form_field('billing_last_name', $fields['billing']['billing_last_name'], $checkout->get_value('billing_last_name'));
      ?>
    </div>

    <?php
      woocommerce_form_field('billing_cedula', $fields['billing']['billing_cedula'], $checkout->get_value('billing_cedula'));
      woocommerce_form_field('billing_email', $fields['billing']['billing_email'], $checkout->get_value('billing_email'));
      woocommerce_form_field('billing_phone', $fields['billing']['billing_phone'], $checkout->get_value('billing_phone'));

      $render_locked_country_field('billing_country', 'billing_country', 'Pais', $country_label);

      echo '<div class="bsc__grid-3">';
        woocommerce_form_field('billing_state', $fields['billing']['billing_state'], $checkout->get_value('billing_state'));
        woocommerce_form_field('billing_city', $fields['billing']['billing_city'], $checkout->get_value('billing_city'));
        woocommerce_form_field('billing_postcode', $fields['billing']['billing_postcode'], $checkout->get_value('billing_postcode'));
      echo '</div>';

      woocommerce_form_field('billing_address_1', $fields['billing']['billing_address_1'], $checkout->get_value('billing_address_1'));
    ?>
  </div>

  <div class="bsc__checkout-section">
    <h2 class="bsc__section-title">Notas del pedido (opcional)</h2>
    <?php woocommerce_form_field('order_comments', $fields['order']['order_comments'], $checkout->get_value('order_comments')); ?>
  </div>
</div>