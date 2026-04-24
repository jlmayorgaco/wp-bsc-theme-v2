<?php
/**
 * BSC Order Labels — ViewModel + render entry point.
 * Bulk action: print_order_labels → bsc_render_order_labels()
 */
defined( 'ABSPATH' ) || exit;

// ── View model ────────────────────────────────────────────────────────────────

final class BSC_Order_Label_ViewModel {

    public string $order_number;
    public string $name;
    public string $phone;
    public string $address_line1;
    public string $address_line2;
    public string $city;
    public string $state;
    public string $observations;
    public string $contains;

    private function __construct() {}

    public static function from_order( WC_Order $order ): self {
        $vm = new self();
        $vm->order_number  = (string) $order->get_order_number();
        $vm->name          = self::resolve_name( $order );
        $vm->phone         = self::resolve_phone( $order );
        [ $vm->address_line1, $vm->address_line2 ] = self::resolve_address( $order );
        $vm->city          = self::resolve_city( $order );
        $vm->state         = self::resolve_state( $order );
        $vm->observations  = self::resolve_observations( $order );
        $vm->contains      = self::resolve_contains( $order );
        return $vm;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function resolve_name( WC_Order $order ): string {
        $name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
        if ( $name === '' ) {
            $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        }
        if ( $name === '' ) {
            $user = $order->get_user();
            $name = $user ? $user->display_name : 'Cliente';
        }
        return $name;
    }

    private static function resolve_phone( WC_Order $order ): string {
        // Some setups store a separate shipping phone in meta
        $phone = trim( (string) $order->get_meta( '_shipping_phone' ) );
        if ( $phone === '' ) {
            $phone = trim( (string) $order->get_billing_phone() );
        }
        return $phone;
    }

    /** @return array{string, string} [line1, line2] */
    private static function resolve_address( WC_Order $order ): array {
        $line1 = trim( $order->get_shipping_address_1() );
        $line2 = trim( $order->get_shipping_address_2() );

        // Fall back to billing when shipping address is absent
        if ( $line1 === '' ) {
            $line1 = trim( $order->get_billing_address_1() );
            $line2 = trim( $order->get_billing_address_2() );
        }

        // Append barrio if stored as custom meta
        $barrio = trim( (string) ( $order->get_meta( '_shipping_barrio' ) ?: $order->get_meta( '_billing_barrio' ) ) );
        if ( $barrio !== '' ) {
            $line2 = $line2 !== '' ? $line2 . ', ' . $barrio : $barrio;
        }

        return [ $line1, $line2 ];
    }

    private static function resolve_city( WC_Order $order ): string {
        $city = trim( $order->get_shipping_city() ?: $order->get_billing_city() );

        // Resolve DANE city codes → readable name (BSC Colombia helper)
        if ( $city !== '' && function_exists( 'bsc_get_colombia_shipping_places' ) ) {
            foreach ( bsc_get_colombia_shipping_places() as $_dept => $cities ) {
                if ( is_array( $cities ) && isset( $cities[ $city ] ) ) {
                    $city = (string) $cities[ $city ];
                    break;
                }
            }
        }

        return $city;
    }

    private static function resolve_state( WC_Order $order ): string {
        $country = $order->get_shipping_country() ?: $order->get_billing_country() ?: 'CO';
        $state   = $order->get_shipping_state() ?: $order->get_billing_state();

        if ( $state !== '' ) {
            $states = WC()->countries->get_states( $country );
            if ( is_array( $states ) && isset( $states[ $state ] ) ) {
                $state = $states[ $state ];
            }
        }

        return (string) $state;
    }

    private static function resolve_observations( WC_Order $order ): string {
        // 1. Custom dispatch notes (operator-entered)
        $obs = trim( (string) $order->get_meta( '_bsc_dispatch_notes' ) );
        if ( $obs !== '' ) return $obs;

        // 2. Customer note at checkout
        $note = trim( $order->get_customer_note() );
        if ( $note !== '' ) return $note;

        // 3. Sensible default for cosmetics
        return 'COSMÉTICOS, DELICADO! NO PONER PESO ENCIMA';
    }

    private static function resolve_contains( WC_Order $order ): string {
        $items = $order->get_items();
        if ( empty( $items ) ) return '—';

        $names = [];
        foreach ( $items as $item ) {
            $names[] = $item->get_name();
        }
        $count = count( $names );

        if ( $count === 1 ) {
            return self::truncate( $names[0], 45 );
        }

        if ( $count <= 3 ) {
            $short  = array_map( fn( string $n ): string => self::truncate( $n, 28 ), $names );
            $joined = implode( ', ', $short );
            // If still too long for the label column, fall back to count
            if ( mb_strlen( $joined ) <= 85 ) {
                return $joined;
            }
        }

        return $count . ' productos';
    }

    private static function truncate( string $str, int $limit ): string {
        if ( mb_strlen( $str ) <= $limit ) return $str;
        return mb_substr( $str, 0, $limit - 1 ) . '…';
    }
}

// ── Render entry point ────────────────────────────────────────────────────────

function bsc_render_order_labels( array $order_ids ): void {
    // Capability check (matches existing bulk handler)
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_orders' ) ) {
        wp_die( esc_html__( 'Sin permisos.', 'bsc-2-0' ) );
    }

    // Build view models — skip any invalid IDs silently
    $labels = [];
    foreach ( $order_ids as $id ) {
        $order = wc_get_order( absint( $id ) );
        if ( ! $order instanceof WC_Order ) continue;
        $labels[] = BSC_Order_Label_ViewModel::from_order( $order );
    }

    if ( empty( $labels ) ) {
        wp_die( esc_html__( 'No se encontraron órdenes válidas.', 'bsc-2-0' ) );
    }

    $autoprint = isset( $_GET['autoprint'] ) && $_GET['autoprint'] === '1';
    $template  = get_template_directory() . '/admin/order-label-print.php';

    // Clear WP output buffers so we control the full response
    while ( ob_get_level() > 0 ) {
        ob_end_clean();
    }
    header( 'Content-Type: text/html; charset=UTF-8' );

    if ( file_exists( $template ) ) {
        include $template;
    } else {
        echo '<p>Error: template no encontrado (' . esc_html( $template ) . ').</p>';
    }

    exit;
}
