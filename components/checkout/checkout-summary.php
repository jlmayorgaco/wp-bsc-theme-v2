<?php
defined('ABSPATH') || exit;

class BSC_Checkout_Review_Summary {

    protected $cart;

    public function __construct() {
        $this->cart = WC()->cart;
        // I-5: free_shipping filter is already registered globally in inc/woocommerce.php
        // I-4: calculate_totals() moved to render() — only called once per request
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
