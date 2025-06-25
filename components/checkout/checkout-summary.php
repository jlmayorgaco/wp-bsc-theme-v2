<?php
defined('ABSPATH') || exit;

class BSC_Checkout_Review_Summary {

    protected $cart;

    public function __construct() {
        $this->cart = WC()->cart;
        WC()->customer->set_calculated_shipping(true);
        $this->cart->calculate_totals();
    }

    public function render(): void {
        $cart_count = $this->cart->get_cart_contents_count();

        $subtotal = $this->cart->get_subtotal();
        $discount = $this->cart->get_discount_total();
        $subtotal_discounted = $subtotal - $discount;
        $shipping = $this->cart->get_shipping_total();
        $taxes = $this->cart->get_total_tax();
        $total = $subtotal_discounted + $shipping + $taxes;

        echo '<div class="bsc bsc__review-summary" id="bsc-review-summary">';
        echo '  <div class="review-summary__container">';

        $this->render_row("Subtotal (<span id=\"review-summary__cart-count\">$cart_count</span> ítem" . ($cart_count !== 1 ? 's' : '') . ")", wc_price($subtotal), 'review-summary__subtotal');
        $this->render_row("Subtotal con descuento", wc_price($subtotal_discounted), 'review-summary__subtotal-discounted');
        $this->render_row("Envío", wc_price($shipping), 'review-summary__shipping');
        $this->render_row("Total", "<strong>" . wc_price($total) . "</strong>", 'review-summary__total', true);

        echo '  </div>';
        echo '</div>';
    }

    protected function render_row(string $label, string $value, string $id, bool $highlight = false): void {
        $row_class = 'review-summary__row' . ($highlight ? ' review-summary__row--total' : '');

        echo "<div class=\"$row_class\">";
        echo "  <div class=\"review-summary__label\">$label</div>";
        echo "  <div class=\"review-summary__value\" id=\"$id\">$value</div>";
        echo "</div>";
    }
}
