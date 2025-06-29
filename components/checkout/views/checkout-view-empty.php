  <main class="bsc bsc__page bsc__page--empty bsc__page--checkout <?php if (WC()->cart->is_empty()) { echo 'is-visible'; }?>">
        <h1 class="bsc__title"><strong>Finaliza</strong> tu compra</h1>
        <div class="container__empty ">
            <img class="bsc__empty-logo" alt="" src="<?php echo get_template_directory_uri();?>/images/bsc_image_empty_cart.png">
            <p class="bsc__title">Ohh ... <strong>tu carrito</strong> esta vacío</p>
            <a class="bsc__button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Volver a la tienda</a>
        </div>
    </main>