<?php
defined('ABSPATH') || exit;

require_once __DIR__ . '/classes/class-bsc-catalog-filter-config.php';
require_once __DIR__ . '/classes/class-bsc-catalog-request-context.php';
require_once __DIR__ . '/classes/class-bsc-catalog-filter-sidebar-renderer.php';
require_once __DIR__ . '/classes/class-bsc-catalog-product-query.php';
require_once __DIR__ . '/classes/class-bsc-catalog-product-renderer.php';
require_once __DIR__ . '/classes/class-bsc-catalog-ajax-controller.php';
require_once __DIR__ . '/classes/class-bsc-catalog-legacy-seed-repository.php';
require_once __DIR__ . '/helpers/compat.php';

BSC_Catalog_Ajax_Controller::register_hooks();
