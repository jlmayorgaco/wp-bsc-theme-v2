<?php
/**
 * BSC-037: Showroom sale page — create WC orders from physical store sales,
 * deduct _stock_tienda, mark with _bsc_is_showroom_sale meta.
 */
defined('ABSPATH') || exit;

// ── Enqueue JS only on showroom page ─────────────────────────────────
add_action('admin_enqueue_scripts', function (string $hook): void {
    if (!isset($_GET['page']) || $_GET['page'] !== 'bsc-showroom') return;
    wp_enqueue_script('jquery-ui-autocomplete'); // bundled in WP admin
});

// ── Page render ───────────────────────────────────────────────────────
function bsc_render_showroom_page(): void {
    if (!current_user_can('manage_options') && !current_user_can('edit_orders')) {
        wp_die(esc_html__('No tienes permisos.', 'bsc-2-0'));
    }

    $recent_orders = wc_get_orders([
        'meta_key'   => '_bsc_is_showroom_sale',
        'meta_value' => '1',
        'limit'      => 10,
        'orderby'    => 'date',
        'order'      => 'DESC',
    ]);
    ?>
    <div class="wrap bsc-showroom">
        <h1>Venta Presencial</h1>
        <p style="color:#666;margin-top:-8px">Registra una venta del showroom. Descuenta <strong>stock en tienda</strong>.</p>

        <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start;margin-top:20px">

            <!-- ── Left: form ── -->
            <div>
                <div class="bsc-card" style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:24px">

                    <!-- Product search -->
                    <h3 style="margin-top:0">Productos</h3>
                    <div style="display:flex;gap:8px;margin-bottom:12px">
                        <input type="text" id="bsc-product-search"
                               placeholder="Buscar por nombre o SKU…"
                               style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:14px">
                        <input type="number" id="bsc-product-qty" value="1" min="1"
                               style="width:70px;padding:8px;border:1px solid #ddd;border-radius:6px;text-align:center">
                        <button type="button" id="bsc-add-product" class="button button-primary">Agregar</button>
                    </div>
                    <div id="bsc-search-results" style="display:none;border:1px solid #ddd;border-radius:6px;background:#fff;position:relative;z-index:10;max-height:220px;overflow-y:auto"></div>

                    <!-- Cart table -->
                    <table id="bsc-cart-table" style="width:100%;border-collapse:collapse;margin-top:16px;display:none">
                        <thead>
                            <tr style="background:#f9f9f9">
                                <th style="padding:8px 10px;text-align:left;font-size:13px;border-bottom:2px solid #eee">Producto</th>
                                <th style="padding:8px 10px;text-align:center;font-size:13px;border-bottom:2px solid #eee;width:60px">Qty</th>
                                <th style="padding:8px 10px;text-align:right;font-size:13px;border-bottom:2px solid #eee;width:90px">Precio</th>
                                <th style="padding:8px 10px;text-align:right;font-size:13px;border-bottom:2px solid #eee;width:90px">Subtotal</th>
                                <th style="width:32px;border-bottom:2px solid #eee"></th>
                            </tr>
                        </thead>
                        <tbody id="bsc-cart-body"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="padding:10px;text-align:right;font-weight:700;font-size:15px">Total:</td>
                                <td style="padding:10px;text-align:right;font-weight:700;font-size:15px" id="bsc-cart-total">$0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <p id="bsc-cart-empty" style="color:#aaa;font-size:13px;text-align:center;padding:16px 0">Agrega productos usando el buscador arriba.</p>

                    <hr style="margin:20px 0">

                    <!-- Customer info -->
                    <h3 style="margin-top:0">Datos del cliente</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Nombre</label>
                            <input type="text" id="bsc-customer-name" placeholder="Cliente anónimo"
                                   style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Teléfono</label>
                            <input type="text" id="bsc-customer-phone" placeholder="Opcional"
                                   style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box">
                        </div>
                        <div style="grid-column:span 2">
                            <label style="display:block;font-size:12px;font-weight:600;margin-bottom:4px">Email</label>
                            <input type="email" id="bsc-customer-email" placeholder="Opcional"
                                   style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;box-sizing:border-box">
                        </div>
                    </div>

                    <!-- Payment method -->
                    <h3>Método de pago</h3>
                    <div style="display:flex;gap:16px;margin-bottom:20px">
                        <?php
                        $methods = ['efectivo' => '💵 Efectivo', 'transferencia' => '🏦 Transferencia', 'tarjeta' => '💳 Tarjeta'];
                        foreach ($methods as $val => $label) : ?>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:14px">
                            <input type="radio" name="bsc-payment" value="<?php echo esc_attr($val); ?>"
                                   <?php checked($val, 'efectivo'); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div id="bsc-showroom-notice" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:14px"></div>

                    <button type="button" id="bsc-register-sale" class="button button-primary" style="height:40px;font-size:15px;padding:0 24px">
                        Registrar venta
                    </button>
                </div>
            </div>

            <!-- ── Right: recent sales ── -->
            <div>
                <div style="background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px">
                    <h3 style="margin-top:0;font-size:14px;letter-spacing:0.5px;color:#555;text-transform:uppercase">Últimas ventas presenciales</h3>
                    <?php if ($recent_orders) : ?>
                    <table style="width:100%;border-collapse:collapse;font-size:13px">
                        <thead>
                            <tr style="background:#f9f9f9">
                                <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #eee">#</th>
                                <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #eee">Cliente</th>
                                <th style="padding:6px 8px;text-align:right;border-bottom:1px solid #eee">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order) :
                                $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) ?: 'Anónimo';
                            ?>
                            <tr>
                                <td style="padding:6px 8px;border-bottom:1px solid #f5f5f5">
                                    <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" target="_blank">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td style="padding:6px 8px;border-bottom:1px solid #f5f5f5"><?php echo esc_html($name); ?></td>
                                <td style="padding:6px 8px;border-bottom:1px solid #f5f5f5;text-align:right">
                                    <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <p style="color:#aaa;font-size:13px;text-align:center;padding:12px 0">Sin ventas presenciales registradas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function ($) {
        var cart   = {};   // { product_id: { name, price, qty, stock_tienda } }
        var nonce  = '<?php echo esc_js(wp_create_nonce('bsc_admin_orders')); ?>';
        var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
        var selectedProduct = null;

        // ── Product search ──────────────────────────────────────────
        var searchTimer;
        $('#bsc-product-search').on('input', function () {
            var q = $(this).val().trim();
            clearTimeout(searchTimer);
            if (q.length < 2) { $('#bsc-search-results').hide().empty(); return; }
            searchTimer = setTimeout(function () {
                $.post(ajaxUrl, { action: 'bsc_showroom_search', nonce: nonce, q: q })
                  .done(function (res) {
                    var $results = $('#bsc-search-results').empty();
                    if (!res.success || !res.data.length) {
                        $results.html('<div style="padding:10px 14px;color:#888;font-size:13px">Sin resultados.</div>').show();
                        return;
                    }
                    res.data.forEach(function (p) {
                        var $row = $('<div>').css({padding:'10px 14px', cursor:'pointer', fontSize:'13px', borderBottom:'1px solid #f0f0f0'})
                            .html('<strong>' + p.name + '</strong> &nbsp;<span style="color:#888">SKU: ' + (p.sku||'—') + '</span> &nbsp;<span style="color:#46b450">Stock tienda: ' + p.stock_tienda + '</span>')
                            .on('click', function () {
                                selectedProduct = p;
                                $('#bsc-product-search').val(p.name);
                                $results.hide();
                            })
                            .on('mouseenter', function () { $(this).css('background','#f5f5f5'); })
                            .on('mouseleave', function () { $(this).css('background',''); });
                        $results.append($row);
                    });
                    $results.show();
                });
            }, 300);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#bsc-product-search, #bsc-search-results').length) {
                $('#bsc-search-results').hide();
            }
        });

        // ── Add to cart ─────────────────────────────────────────────
        $('#bsc-add-product').on('click', function () {
            if (!selectedProduct) { showNotice('Selecciona un producto primero.', 'error'); return; }
            var qty = parseInt($('#bsc-product-qty').val(), 10) || 1;
            var pid = selectedProduct.id;

            if (cart[pid]) {
                cart[pid].qty += qty;
            } else {
                cart[pid] = { name: selectedProduct.name, price: selectedProduct.price, qty: qty, stock_tienda: selectedProduct.stock_tienda };
            }

            renderCart();
            selectedProduct = null;
            $('#bsc-product-search').val('').focus();
            $('#bsc-product-qty').val(1);
        });

        // ── Render cart table ────────────────────────────────────────
        function renderCart() {
            var $tbody = $('#bsc-cart-body').empty();
            var total  = 0;
            var hasItems = Object.keys(cart).length > 0;

            if (!hasItems) {
                $('#bsc-cart-table').hide();
                $('#bsc-cart-empty').show();
                return;
            }

            $('#bsc-cart-table').show();
            $('#bsc-cart-empty').hide();

            $.each(cart, function (pid, item) {
                var subtotal = item.price * item.qty;
                total += subtotal;
                var $row = $('<tr data-pid="' + pid + '">').html(
                    '<td style="padding:8px 10px;border-bottom:1px solid #f5f5f5">' + item.name + '</td>' +
                    '<td style="padding:8px 10px;border-bottom:1px solid #f5f5f5;text-align:center">' +
                        '<input type="number" class="cart-qty" data-pid="' + pid + '" value="' + item.qty + '" min="1" style="width:55px;text-align:center;padding:4px">' +
                    '</td>' +
                    '<td style="padding:8px 10px;border-bottom:1px solid #f5f5f5;text-align:right">$' + item.price.toLocaleString('es-CO') + '</td>' +
                    '<td style="padding:8px 10px;border-bottom:1px solid #f5f5f5;text-align:right">$' + subtotal.toLocaleString('es-CO') + '</td>' +
                    '<td style="padding:4px 6px;border-bottom:1px solid #f5f5f5;text-align:center"><button class="remove-item button" data-pid="' + pid + '" style="padding:2px 8px">✕</button></td>'
                );
                $tbody.append($row);
            });

            $('#bsc-cart-total').text('$' + total.toLocaleString('es-CO'));
        }

        // ── Update qty inline ────────────────────────────────────────
        $(document).on('change', '.cart-qty', function () {
            var pid = $(this).data('pid');
            var qty = parseInt($(this).val(), 10) || 1;
            if (cart[pid]) { cart[pid].qty = qty; renderCart(); }
        });

        // ── Remove item ──────────────────────────────────────────────
        $(document).on('click', '.remove-item', function () {
            delete cart[$(this).data('pid')];
            renderCart();
        });

        // ── Register sale ────────────────────────────────────────────
        $('#bsc-register-sale').on('click', function () {
            var items = [];
            $.each(cart, function (pid, item) {
                items.push({ id: parseInt(pid, 10), qty: item.qty });
            });

            if (!items.length) { showNotice('Agrega al menos un producto.', 'error'); return; }

            var payment = $('input[name="bsc-payment"]:checked').val();
            var $btn    = $(this).prop('disabled', true).text('Registrando…');

            $.post(ajaxUrl, {
                action:         'bsc_register_showroom_sale',
                nonce:          nonce,
                items:          JSON.stringify(items),
                payment_method: payment,
                customer_name:  $('#bsc-customer-name').val().trim(),
                customer_phone: $('#bsc-customer-phone').val().trim(),
                customer_email: $('#bsc-customer-email').val().trim(),
            })
            .done(function (res) {
                if (res.success) {
                    showNotice('✓ Venta registrada. Pedido <a href="' + res.data.edit_url + '" target="_blank">#' + res.data.order_id + '</a>', 'success');
                    cart = {};
                    renderCart();
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    showNotice('Error: ' + (res.data.message || 'desconocido'), 'error');
                    $btn.prop('disabled', false).text('Registrar venta');
                }
            })
            .fail(function () {
                showNotice('Error de conexión.', 'error');
                $btn.prop('disabled', false).text('Registrar venta');
            });
        });

        // ── Notice helper ────────────────────────────────────────────
        function showNotice(msg, type) {
            var bg  = type === 'success' ? '#d7f4d7' : '#fdecea';
            var col = type === 'success' ? '#276629' : '#b3261e';
            $('#bsc-showroom-notice')
                .css({ background: bg, color: col, border: '1px solid ' + (type === 'success' ? '#b5d9bc' : '#f5c2bf') })
                .html(msg).show();
        }

    })(jQuery);
    </script>
    <?php
}

// ── AJAX: product search for showroom ────────────────────────────────
add_action('wp_ajax_bsc_showroom_search', 'bsc_ajax_showroom_search');
function bsc_ajax_showroom_search(): void {
    check_ajax_referer('bsc_admin_orders', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_orders')) wp_send_json_error();

    $q = sanitize_text_field($_POST['q'] ?? '');
    if (strlen($q) < 2) wp_send_json_success([]);

    $products = wc_get_products([
        's'       => $q,
        'limit'   => 12,
        'status'  => 'publish',
        'return'  => 'objects',
    ]);

    $results = [];
    foreach ($products as $product) {
        $results[] = [
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            'sku'         => $product->get_sku(),
            'price'       => (float) $product->get_price(),
            'stock_tienda' => (int) get_post_meta($product->get_id(), '_stock_tienda', true),
        ];
    }

    wp_send_json_success($results);
}

// ── AJAX: register showroom sale ──────────────────────────────────────
add_action('wp_ajax_bsc_register_showroom_sale', 'bsc_ajax_register_showroom_sale');
function bsc_ajax_register_showroom_sale(): void {
    check_ajax_referer('bsc_admin_orders', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('edit_orders')) {
        wp_send_json_error(['message' => 'Sin permisos']);
    }

    $items_raw     = sanitize_text_field($_POST['items'] ?? '[]');
    $items         = json_decode(stripslashes($items_raw), true);
    $payment       = sanitize_text_field($_POST['payment_method'] ?? 'efectivo');
    $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
    $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
    $customer_email = sanitize_email($_POST['customer_email'] ?? '');

    if (empty($items) || !is_array($items)) {
        wp_send_json_error(['message' => 'Sin productos']);
    }

    // Create WC order
    $order = wc_create_order(['customer_id' => 0]);

    foreach ($items as $item) {
        $product_id = absint($item['id'] ?? 0);
        $qty        = max(1, absint($item['qty'] ?? 1));
        $product    = wc_get_product($product_id);
        if (!$product) continue;
        $order->add_product($product, $qty);
    }

    // Set billing info
    if ($customer_name) {
        $parts = explode(' ', $customer_name, 2);
        $order->set_billing_first_name($parts[0]);
        $order->set_billing_last_name($parts[1] ?? '');
    }
    if ($customer_phone) $order->set_billing_phone($customer_phone);
    if ($customer_email) $order->set_billing_email($customer_email);

    $order->set_payment_method($payment);
    $order->set_payment_method_title(ucfirst($payment));

    $order->calculate_totals();
    $order->update_status('completed', 'Venta presencial registrada desde BSC Admin.');
    $order->save();

    $order_id = $order->get_id();

    // Mark as showroom sale
    update_post_meta($order_id, '_bsc_is_showroom_sale', '1');

    // BSC-036: deduct tienda stock
    if (class_exists('BSC_Stock')) {
        BSC_Stock::deduct_tienda($order_id);
    }

    wp_send_json_success([
        'order_id' => $order_id,
        'edit_url' => $order->get_edit_order_url(),
        'message'  => 'Venta registrada correctamente',
    ]);
}
