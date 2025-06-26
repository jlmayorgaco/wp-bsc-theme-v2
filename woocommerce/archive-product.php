<?php
defined('ABSPATH') || exit;

get_header('shop'); ?>




<main class="bsc__shop">

    <?php 
        if (!is_product_category()) {
            require_once get_template_directory() . '/components/shop.php';
        } else {
            require_once get_template_directory() . '/components/product-category.php';
        }
    ?>

</main>




<?php get_footer('shop'); ?>
