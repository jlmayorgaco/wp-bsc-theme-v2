<?php
defined('ABSPATH') || exit;

$checkout = WC()->checkout();
$fields   = $checkout->get_checkout_fields();

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

// Asignar placeholders y clases
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

// Agregar class y placeholder a order_comments
if (isset($fields['order']['order_comments'])) {
  $fields['order']['order_comments']['placeholder'] = $placeholder_map['order_comments'];
  $fields['order']['order_comments']['class'][] = 'bsc__field';
}
?>
<div class="checkout bsc__checkout-form">
  <div class="bsc__checkout-section">
    <h2 class="bsc__section-title">Datos de Entrega</h2>

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

      // Set default value to Colombia
      $fields['billing']['billing_country']['default'] = 'CO';
      $fields['billing']['billing_country']['custom_attributes'] = [
        'readonly' => 'readonly'
      ];

      // Output the country field
      woocommerce_form_field(
        'billing_country',
        $fields['billing']['billing_country'],
        $checkout->get_value('billing_country') ?: 'CO'
      );
      
      echo '<div class="bsc__grid-3">';
        woocommerce_form_field('billing_state', $fields['billing']['billing_state'], $checkout->get_value('billing_state'));
        woocommerce_form_field('billing_city', $fields['billing']['billing_city'], $checkout->get_value('billing_city'));
        woocommerce_form_field('billing_postcode', $fields['billing']['billing_postcode'], $checkout->get_value('billing_postcode'));
      echo '</div>';

      woocommerce_form_field('billing_address_1', $fields['billing']['billing_address_1'], $checkout->get_value('billing_address_1'));
    ?>

    <div class="bsc__shipping-toggle">
      <?php
        woocommerce_form_field('ship_to_different_address', [
          'type'  => 'checkbox',
          'label' => '¿Enviar a otra dirección?',
          'class' => ['form-row-wide'],
        ], $checkout->get_value('ship_to_different_address'));
      ?>
    </div>

    <div class="bsc__shipping-fields" style="display: none;">
      <h3 class="bsc__section-title">Dirección de envío</h3>

      <?php
        $fields['shipping']['shipping_country']['custom_attributes'] = ['readonly' => 'readonly'];
        woocommerce_form_field('shipping_country', $fields['shipping']['shipping_country'], $checkout->get_value('shipping_country'));

        echo '<div class="bsc__grid-3">';
          woocommerce_form_field('shipping_state', $fields['shipping']['shipping_state'], $checkout->get_value('shipping_state'));
          woocommerce_form_field('shipping_city', $fields['shipping']['shipping_city'], $checkout->get_value('shipping_city'));
          woocommerce_form_field('shipping_postcode', $fields['shipping']['shipping_postcode'], $checkout->get_value('shipping_postcode'));
        echo '</div>';

        woocommerce_form_field('shipping_address_1', $fields['shipping']['shipping_address_1'], $checkout->get_value('shipping_address_1'));
      ?>
    </div>
  </div>

  <div class="bsc__checkout-section">
    <h2 class="bsc__section-title">Notas del pedido (opcional)</h2>
    <?php woocommerce_form_field('order_comments', $fields['order']['order_comments'], $checkout->get_value('order_comments')); ?>
  </div>

</div>


<script>
  document.addEventListener("DOMContentLoaded", () => {
    const toggle = document.querySelector('input[name="ship_to_different_address"]');
    const shippingSection = document.querySelector('.bsc__shipping-fields');
    if (toggle && shippingSection) {
      toggle.addEventListener('change', () => {
        shippingSection.style.display = toggle.checked ? 'block' : 'none';
      });
      shippingSection.style.display = toggle.checked ? 'block' : 'none';
    }
  });
</script>
