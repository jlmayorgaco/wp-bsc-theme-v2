<?php

add_action('wp_ajax_update_cart_quantity', 'bsc_update_cart_quantity');
add_action('wp_ajax_nopriv_update_cart_quantity', 'bsc_update_cart_quantity');

function bsc_update_cart_quantity() {
	if (!isset($_POST['product_id'], $_POST['quantity'])) {
		wp_send_json_error(['message' => 'Missing required fields'], 400);
	}

	$product_id = intval($_POST['product_id']);
	$delta      = intval($_POST['quantity']); // This is the change (+1 or -1)

	if ($product_id < 1) {
		wp_send_json_error(['message' => 'Invalid product ID'], 400);
	}

	foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
		if ($cart_item['product_id'] == $product_id) {
			$current_qty = $cart_item['quantity'];
			$new_qty = $current_qty + $delta;

			if ($new_qty < 1) {
				WC()->cart->remove_cart_item($cart_item_key);
				WC()->cart->calculate_totals();
				wc_clear_notices();
				// BSC-004: incluir cart_count para que el JS actualice el badge sin depender del fragmento
				wp_send_json_success([
					'message'    => 'Product removed from cart',
					'cart_count' => WC()->cart->get_cart_contents_count(),
				]);
			}

			WC()->cart->set_quantity($cart_item_key, $new_qty);
			WC()->cart->calculate_totals();
			wc_clear_notices();
			// BSC-004: incluir cart_count en todas las respuestas
			wp_send_json_success([
				'message'    => 'Quantity updated',
				'new_qty'    => $new_qty,
				'cart_count' => WC()->cart->get_cart_contents_count(),
			]);
		}
	}

	wp_send_json_error(['message' => 'Product not found in cart'], 404);
}


add_action('wp_ajax_bsc_get_cart_quantities', 'bsc_get_cart_quantities');
add_action('wp_ajax_nopriv_bsc_get_cart_quantities', 'bsc_get_cart_quantities');

function bsc_get_cart_quantities() {
  if ( ! WC()->cart ) {
    wp_send_json_error();
  }

  $items = [];

  foreach ( WC()->cart->get_cart() as $key => $item ) {
    $items[] = [
      'key' => $key,
      'quantity' => $item['quantity'],
    ];
  }

  wp_send_json_success($items);
}



add_action('wp_ajax_bsc_remove_cart_item', 'bsc_remove_cart_item');
add_action('wp_ajax_nopriv_bsc_remove_cart_item', 'bsc_remove_cart_item');

function bsc_remove_cart_item() {
  if ( ! isset($_POST['cart_item_key']) || ! WC()->cart ) {
    wp_send_json_error(['message' => 'Datos incompletos o carrito no disponible.']);
    wp_die();
  }

  $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
  $removed = WC()->cart->remove_cart_item($cart_item_key);

  if ($removed) {
    // Recalcular totales
    WC()->cart->calculate_totals();

    // Obtener fragmentos actualizados
    WC_AJAX::get_refreshed_fragments();
  } else {
    wp_send_json_error(['message' => 'No se pudo eliminar el producto.']);
  }
}



?>