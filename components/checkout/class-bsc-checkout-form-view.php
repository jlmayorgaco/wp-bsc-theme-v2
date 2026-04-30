<?php

defined('ABSPATH') || exit;

class BSC_Checkout_Form_View {

    protected $checkout;
    protected $fields;
    protected $country_label;
    protected $placeholder_map = [
        'billing_first_name' => 'Nombres',
        'billing_last_name'  => 'Apellidos',
        'billing_cedula'     => 'N&uacute;mero de c&eacute;dula',
        'billing_email'      => 'Correo electr&oacute;nico',
        'billing_phone'      => 'N&uacute;mero de tel&eacute;fono',
        'billing_country'    => 'Selecciona un pa&iacute;s',
        'billing_state'      => 'Selecciona un departamento',
        'billing_city'       => 'Selecciona una ciudad',
        'billing_postcode'   => 'C&oacute;digo postal',
        'billing_address_1'  => 'Direcci&oacute;n de entrega',
        'billing_address_2'  => 'Complemento de direcci&oacute;n',
        'shipping_country'   => 'Selecciona un pa&iacute;s',
        'shipping_state'     => 'Selecciona un departamento',
        'shipping_city'      => 'Selecciona una ciudad',
        'shipping_postcode'  => 'C&oacute;digo postal',
        'shipping_address_1' => 'Direcci&oacute;n de entrega',
        'order_comments'     => 'Notas del pedido (opcional)',
    ];

    public function __construct() {
        $this->checkout = WC()->checkout();
        $this->fields = $this->checkout->get_checkout_fields();

        $countries = WC()->countries->get_countries();
        $this->country_label = $countries['CO'] ?? 'Colombia';

        $this->prepare_fields();
    }

    protected function prepare_fields(): void {
        foreach (['billing', 'shipping'] as $group) {
            if (!isset($this->fields[$group])) {
                continue;
            }

            foreach ($this->fields[$group] as $key => &$field) {
                if (isset($this->placeholder_map[$key])) {
                    $field['placeholder'] = html_entity_decode($this->placeholder_map[$key], ENT_QUOTES, 'UTF-8');
                } elseif (!isset($field['placeholder']) && isset($field['label'])) {
                    $field['placeholder'] = $field['label'];
                }

                $field['class'][] = 'bsc__field';
            }
        }

        if (isset($this->fields['order']['order_comments'])) {
            $this->fields['order']['order_comments']['placeholder'] = html_entity_decode($this->placeholder_map['order_comments'], ENT_QUOTES, 'UTF-8');
            $this->fields['order']['order_comments']['class'][] = 'bsc__field';
        }
    }

    public function render(): void {
        ?>
        <div class="checkout bsc__checkout-form">
          <div class="bsc__checkout-section">

            <div class="bsc__grid-2">
              <?php
                woocommerce_form_field('billing_first_name', $this->fields['billing']['billing_first_name'], $this->checkout->get_value('billing_first_name'));
                woocommerce_form_field('billing_last_name', $this->fields['billing']['billing_last_name'], $this->checkout->get_value('billing_last_name'));
              ?>
            </div>

            <?php
              woocommerce_form_field('billing_cedula', $this->fields['billing']['billing_cedula'], $this->checkout->get_value('billing_cedula'));
              woocommerce_form_field('billing_email', $this->fields['billing']['billing_email'], $this->checkout->get_value('billing_email'));
              woocommerce_form_field('billing_phone', $this->fields['billing']['billing_phone'], $this->checkout->get_value('billing_phone'));

              $this->render_locked_country_field('billing_country', 'billing_country', 'Pais');

              echo '<div class="bsc__grid-3">';
                woocommerce_form_field('billing_state', $this->fields['billing']['billing_state'], $this->checkout->get_value('billing_state'));
                woocommerce_form_field('billing_city', $this->fields['billing']['billing_city'], $this->checkout->get_value('billing_city'));
                woocommerce_form_field('billing_postcode', $this->fields['billing']['billing_postcode'], $this->checkout->get_value('billing_postcode'));
              echo '</div>';

              woocommerce_form_field('billing_address_1', $this->fields['billing']['billing_address_1'], $this->checkout->get_value('billing_address_1'));
            ?>
          </div>

          <div class="bsc__checkout-section">
            <h2 class="bsc__section-title">Notas del pedido (opcional)</h2>
            <?php woocommerce_form_field('order_comments', $this->fields['order']['order_comments'], $this->checkout->get_value('order_comments')); ?>
          </div>
        </div>
        <?php
    }

    protected function render_locked_country_field(string $field_id, string $input_name, string $label): void {
        $locked_select_id = $field_id . '_locked';
        ?>
        <p class="form-row form-row-wide bsc__field bsc__field--locked-country" id="<?php echo esc_attr($field_id); ?>_field">
          <label for="<?php echo esc_attr($locked_select_id); ?>"><?php echo esc_html($label); ?></label>
          <select id="<?php echo esc_attr($locked_select_id); ?>" class="country_to_state country_select bsc__locked-country-select" disabled="disabled" aria-disabled="true">
            <option value="CO" selected="selected"><?php echo esc_html($this->country_label); ?></option>
          </select>
          <input type="hidden" name="<?php echo esc_attr($input_name); ?>" id="<?php echo esc_attr($field_id); ?>" value="CO">
        </p>
        <?php
    }
}
