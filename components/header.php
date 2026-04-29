<?php

require_once get_stylesheet_directory() . '/components/header/BSC_HeaderNav.class.php';
require_once get_stylesheet_directory() . '/components/header/BSC_MenuNav.class.php';
require_once get_stylesheet_directory() . '/components/header/header-menu-config.php';

$headerNav = bsc_build_header_nav();

[
    'home_url' => $header_home_url,
    'shop_url' => $header_shop_url,
] = bsc_get_header_shared_links();

[
    'my_account_url'       => $my_account_url,
    'orders_url'           => $orders_url,
    'bubble_points_url'    => $bubble_points_url,
    'edit_account_url'     => $edit_account_url,
    'edit_address_url'     => $edit_address_url,
    'logout_url'           => $logout_url,
    'login_url'            => $login_url,
    'register_url'         => $register_url,
    'checkout_url'         => $checkout_url,
    'mobile_profile_url'   => $mobile_profile_url,
    'mobile_profile_label' => $mobile_profile_label,
] = bsc_get_header_account_links();

require get_stylesheet_directory() . '/components/header/partials/header-desktop.php';
require get_stylesheet_directory() . '/components/header/partials/header-mobile.php';
require get_stylesheet_directory() . '/components/header/partials/header-mobile-sidebar.php';
