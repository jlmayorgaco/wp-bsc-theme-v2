<?php
defined('ABSPATH') || exit;

class BSC_Checkout_Review_Summary {

    protected $cart;
    protected $debug_messages = [];

    public function __construct() {
        $this->cart = WC()->cart;

        if (isset(WC()->session)) {
            WC()->cart->calculate_totals(); // Asegura que todo esté listo
        }

        // 🚫 Aplica lógica para forzar eliminación visual del envío gratuito si no aplica
        add_filter('woocommerce_package_rates', [$this, 'maybe_disable_free_shipping'], 10, 2);
    }

    public function render(): void {
        WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        

        $cart_count = $this->cart->get_cart_contents_count();
        $subtotal = $this->cart->get_subtotal();
        $discount = $this->cart->get_discount_total();
        $subtotal_discounted = $subtotal - $discount;

        $shipping_total = $this->cart->get_shipping_total();
        $shipping_method_label = $this->get_shipping_method_label();
        $shipping_display = $shipping_total <= 0 ? 'Gratis' : wc_price($shipping_total);
        $shipping_text = $shipping_method_label ? "$shipping_method_label – $shipping_display" : $shipping_display;

        $taxes = $this->cart->get_total_tax();
        $total = $subtotal_discounted + $shipping_total + $taxes;

        echo '<div class="bsc bsc__review-summary" id="bsc-review-summary">';
        echo '  <div class="review-summary__container">';

        $this->render_row(
            "Subtotal (<span id=\"review-summary__cart-count\">$cart_count</span> ítem" . ($cart_count !== 1 ? 's' : '') . ")",
            wc_price($subtotal),
            'review-summary__subtotal'
        );

        $this->render_row(
            "Subtotal con descuento",
            wc_price($subtotal_discounted),
            'review-summary__subtotal-discounted'
        );

        $this->render_row(
            "Envío",
            $shipping_text,
            'review-summary__shipping'
        );

        $this->render_row(
            "Total",
            "<strong>" . wc_price($total) . "</strong>",
            'review-summary__total',
            true
        );

        echo '  </div>';


        echo '</div>';
    }

    public function maybe_disable_free_shipping($rates, $package) {
        $subtotal = WC()->cart->get_subtotal();
        $discount = WC()->cart->get_discount_total();
        $subtotal_after_discount = $subtotal - $discount;
        $min_amount = 300000;

        $this->debug_messages[] = "Subtotal original: " . wc_price($subtotal);
        $this->debug_messages[] = "Descuento aplicado: " . wc_price($discount);
        $this->debug_messages[] = "Subtotal con descuento: " . wc_price($subtotal_after_discount);
        $this->debug_messages[] = "Mínimo requerido para envío gratuito: " . wc_price($min_amount);

        if (empty($rates)) {
            $this->debug_messages[] = "🚨 No hay métodos de envío disponibles.";
        }

        foreach ($rates as $rate_id => $rate) {
            $this->debug_messages[] = "🔹 Método disponible: {$rate->label} ({$rate->method_id}) – Costo: " . wc_price($rate->cost);

            if ($rate->method_id === 'free_shipping') {
                if ($subtotal_after_discount < $min_amount) {
                    $this->debug_messages[] = "❌ Eliminando 'Free Shipping' porque el subtotal con descuento es menor a " . wc_price($min_amount);
                    unset($rates[$rate_id]);
                } else {
                    $this->debug_messages[] = "✅ 'Free Shipping' permitido: el subtotal con descuento cumple.";
                }
            }
        }

        return $rates;
    }

    protected function get_shipping_method_label(): string {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');

        if (!empty($chosen_methods) && is_array($chosen_methods)) {
            foreach (WC()->shipping()->get_packages() as $i => $package) {
                foreach ($package['rates'] as $rate_id => $rate) {
                    if (isset($chosen_methods[$i]) && $rate_id === $chosen_methods[$i]) {
                        return $rate->get_label();
                    }
                }
            }
        }

        return '';
    }

    protected function render_row(string $label, string $value, string $id, bool $highlight = false): void {
        $row_class = 'review-summary__row' . ($highlight ? ' review-summary__row--total' : '');

        echo "<div class=\"$row_class\">";
        echo "  <div class=\"review-summary__label\">$label</div>";
        echo "  <div class=\"review-summary__value\" id=\"$id\">$value</div>";
        echo "</div>";
    }
}
