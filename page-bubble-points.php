<?php
/**
 * Template Name: Bubble Points
 */

get_header(); ?>

<main id="primary" class="site-main bsc bsc__bubble-points">

<nav class="woocommerce-MyAccount-navigation bubble-points-nav" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
 <?php

	require_once get_template_directory() . '/components/my-account/my-account-header.php';
	$headerMyAccount = new BSC_MY_ACCOUNT_HEADER();
	$headerMyAccount->setCurrentRoute( 'bubble-points' );
	$headerMyAccount->render();

 ?>
</nav>

  <div class="container">
      <?php require_once get_template_directory() . '/plugins/bubble-points/views/profile-bubble-points.php'; ?>
  </div>
  
</main>

<?php get_footer(); ?>