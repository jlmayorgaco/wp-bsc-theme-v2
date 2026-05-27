<?php
/**
 * Growth tools for CRM, routines, quiz, conversion and search.
 */
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/classes/class-bsc-growth-plugin.php';
require_once __DIR__ . '/classes/class-bsc-growth-bundle-repository.php';
require_once __DIR__ . '/classes/class-bsc-growth-bundles.php';
require_once __DIR__ . '/classes/class-bsc-growth-ai-service.php';
require_once __DIR__ . '/classes/class-bsc-growth-search-service.php';
require_once __DIR__ . '/classes/class-bsc-growth-skin-quiz-store.php';
require_once __DIR__ . '/classes/class-bsc-growth-skin-quiz.php';
require_once __DIR__ . '/classes/class-bsc-growth-skin-quiz-admin.php';
require_once __DIR__ . '/classes/class-bsc-growth-crm.php';
require_once __DIR__ . '/classes/class-bsc-growth-conversion.php';
require_once __DIR__ . '/classes/class-bsc-growth-repurchase.php';
require_once __DIR__ . '/helpers/compat.php';

BSC_Growth_Plugin::register_hooks();
