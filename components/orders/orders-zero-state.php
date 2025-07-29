<?php
function order_zero_state() {
    ?>
    <div class="bsc__orders bsc__orders--empty-orders">
        <h1 class="orders__title">No has realizado pedidos :(</h1>

        <div class="orders__image">
            <img class="orders__empty-image" 
                 src="<?php echo get_template_directory_uri(); ?>/images/bsc_zero_state_pink.png" 
                 alt="Estado vacío: sin pedidos">
        </div>

        <div class="orders__button">
            <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" 
               class="bsc__button"
               aria-label="Haz tu primer pedido en nuestra tienda">
                ¡Hacer tu primer pedido!
            </a>
        </div>
    </div>
    <?php
}
