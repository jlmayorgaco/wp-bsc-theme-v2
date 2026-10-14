<?php
/** Isolated checks for account noindex and sitemap exclusions. */
if ( 'cli' !== PHP_SAPI ) {
	exit;
}
define( 'ABSPATH', __DIR__ );
class WP_Post {
	public function __construct( public int $ID ) {}
}
function add_action( ...$args ): void {}
function add_filter( ...$args ): void {}
function remove_action( ...$args ): void {}
function is_404(): bool { return false; }
function is_search(): bool { return false; }
function is_preview(): bool { return false; }
function is_page( $slugs = null ): bool {
	$route = $GLOBALS['route'];
	return null === $slugs ? true : in_array( $route, (array) $slugs, true );
}
function is_page_template( $template ): bool { return str_starts_with( $GLOBALS['route'], 'bubble-points' ); }
function get_page_by_path( $path ) {
	$ids = array( 'login' => 11, 'register' => 12, 'mi-cuenta' => 13, 'mi-cuenta/bubble-points' => 14 );
	return isset( $ids[ $path ] ) ? new WP_Post( $ids[ $path ] ) : null;
}
function get_queried_object_id(): int { return 15; }
function get_post_ancestors( $id ): array { return str_starts_with( $GLOBALS['route'], 'account-child' ) ? array( 13 ) : array(); }
function is_cart(): bool { return false; }
function is_checkout(): bool { return false; }
function is_account_page(): bool { return false; }
function is_shop(): bool { return false; }
function is_product_category(): bool { return false; }

require dirname( __DIR__ ) . '/inc/seo/meta.php';

foreach ( array( 'login', 'register', 'mi-cuenta', 'bubble-points', 'account-child' ) as $route ) {
	$GLOBALS['route'] = $route;
	if ( ! bsc_seo_should_noindex() ) {
		throw new RuntimeException( "{$route} should be noindex" );
	}
}
$GLOBALS['route'] = 'faq';
if ( bsc_seo_should_noindex() ) {
	throw new RuntimeException( 'Public FAQ page should remain indexable' );
}

$sitemap = bsc_seo_filter_sitemap_pages( array(), 'page' );
foreach ( array( 11, 12, 13, 14 ) as $id ) {
	if ( ! in_array( $id, $sitemap['post__not_in'], true ) ) {
		throw new RuntimeException( "Private page {$id} remained in sitemap" );
	}
}
if ( ! in_array( 13, $sitemap['post_parent__not_in'], true ) ) {
	throw new RuntimeException( 'Account child pages remained in sitemap' );
}
echo "PASS account pages and descendants are noindex and absent from the sitemap\n";
