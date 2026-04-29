<?php
defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();
$get_addresses = [
    'billing' => 'Facturación y Entregas',
];

$addresses = [];
foreach ( $get_addresses as $type => $label ) {
    $addresses[$type] = [
        'label'    => $label,
        'address'  => wc_get_account_formatted_address( $type ),
        'edit_url' => wc_get_endpoint_url( 'edit-address', $type ),
        'type'     => $type, // <-- guardamos el tipo para usarlo abajo
    ];
}

// Campos a mostrar (puedes quitar/agregar sin romper estilos)
$pretty_fields = [
    'first_name' => 'Nombre',
    'last_name'  => 'Apellido',
    'company'    => 'Empresa',
    'address_1'  => 'Dirección',
    'address_2'  => 'Complemento',
    'city'       => 'Ciudad',
    'state'      => 'Departamento',
    'postcode'   => 'Código postal',
    'country'    => 'País',
    'phone'      => 'Teléfono',
    'email'      => 'Email',
];
?>

<section class="bsc__address-wrapper">
    <div class="bsc__address-container">

        <!-- Left Illustration -->
        <div class="bsc__address-illustration">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/profile/Caja_carita.png" alt="BSC Skin Care First" />
        </div>

        <!-- Addresses -->
        <?php foreach ( $addresses as $type => $data ) : ?>
        <div class="bsc__address-card">
            <h3><?php echo esc_html( $data['label'] ); ?></h3>
            <p class="bsc__address-subtitle">Dirección y datos</p>

            <div class="bsc__address-content">
                <?php
                $has_any = false;

                foreach ( $pretty_fields as $key => $label ) {
                    $meta_key = $type . '_' . $key; // billing_first_name, shipping_city, etc.
                    $value    = get_user_meta( $customer_id, $meta_key, true );

                    if ( $value === '' || $value === null ) {
                        continue; // no mostramos vacíos (no rompe estilos)
                    }

                    $has_any = true;

                    // Formateos suaves
                    if ( $key === 'country' ) {
                        $countries = WC()->countries->get_countries();
                        if ( isset($countries[$value]) ) $value = $countries[$value];
                    }
                    if ( $key === 'state' ) {
                        $states = WC()->countries->get_states( get_user_meta($customer_id, $type . '_country', true) );
                        if ( is_array($states) && isset($states[$value]) ) $value = $states[$value];
                    }
                    if ( $key === 'city' && function_exists('bsc_get_colombia_shipping_places') ) {
                        foreach ( bsc_get_colombia_shipping_places() as $_dept => $cities ) {
                            if ( is_array($cities) && isset($cities[$value]) ) {
                                $value = $cities[$value];
                                break;
                            }
                        }
                    }

                    echo '<div style="display:flex">' . '<strong>'. esc_html($label) . '</strong>' . ': ' . esc_html($value) . '</div>';
                }

                if ( ! $has_any ) {
                    echo '<em>No hay dirección guardada.</em>';
                }
                ?>
            </div>

            <br>
            <a class="bsc__button bsc__address-button" href="<?php echo esc_url( $data['edit_url'] ); ?>">¡ Editar datos !</a>
        </div>
        <?php endforeach; ?>

    </div>
</section>
