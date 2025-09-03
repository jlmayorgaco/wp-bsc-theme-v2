<?php
defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();
$get_addresses = [
    'billing'  => 'Facturación',
    'shipping' => 'Entrega de pedidos',
];

$addresses = [];
foreach ( $get_addresses as $type => $label ) {
    $addresses[$type] = [
        'label'   => $label,
        'address' => wc_get_account_formatted_address( $type ),
        'edit_url' => wc_get_endpoint_url( 'edit-address', $type ),
    ];
}
?>

<section class="bsc__address-wrapper">
    <div class="bsc__address-container">
        
        <!-- Left Illustration -->
        <div class="bsc__address-illustration">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/profile/bsc_profile_yellow.png" alt="BSC Skin Care First" />
        </div>

        <!-- Addresses -->
        <?php foreach ( $addresses as $type => $data ) : ?>
        <div class="bsc__address-card">
            <h3><?php echo esc_html( $data['label'] ); ?></h3>
            <p class="bsc__address-subtitle">Dirección y datos</p>
            <div class="bsc__address-content">
                <?php echo $data['address'] ? wp_kses_post( $data['address'] ) : '<em>No hay dirección guardada.</em>'; ?>
            </div>
			<br>
            <a class="bsc__button" href="<?php echo esc_url( $data['edit_url'] ); ?>">¡ Editar datos !</a>
        </div>
        <?php endforeach; ?>
        
    </div>
</section>
