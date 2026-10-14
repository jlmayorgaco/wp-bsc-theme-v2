<?php
/**
 * Remove the numbered Bubble Points pages created by the old theme bootstrap.
 *
 * Run with WP-CLI after deploying the fixed theme:
 *   wp eval-file wp-content/themes/wp-bsc-theme-v2/tools/cleanup-bubble-points-pages.php
 *   wp eval-file wp-content/themes/wp-bsc-theme-v2/tools/cleanup-bubble-points-pages.php delete
 *
 * The first command is a dry run. The second is resumable and deletes only
 * published, empty "Bubble Points" pages beneath /mi-cuenta/ with a numbered
 * slug and the expected page template (or no template if page creation was
 * interrupted before the template was saved). Back up the database first.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "WP-CLI is required.\n" );
}

global $wpdb;

$mode = $args[0] ?? 'dry-run';
if ( ! in_array( $mode, array( 'dry-run', 'delete' ), true ) ) {
	WP_CLI::error( 'Use dry-run or delete.' );
}

$account   = get_page_by_path( 'mi-cuenta' );
$canonical = get_page_by_path( 'mi-cuenta/bubble-points' );
if ( ! $account instanceof WP_Post || ! $canonical instanceof WP_Post || (int) $canonical->post_parent !== (int) $account->ID ) {
	WP_CLI::error( 'The canonical /mi-cuenta/bubble-points/ page was not found. Nothing was deleted.' );
}

$where = $wpdb->prepare(
	"post_type = 'page' AND post_status = 'publish' AND post_parent = %d AND post_title = %s AND post_content = '' AND post_name REGEXP %s AND ID <> %d",
	$account->ID,
	'Bubble Points',
	'^bubble-points-[1-9][0-9]*$',
	$canonical->ID
);
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Conditions are prepared above.
$sample = $wpdb->get_results( "SELECT ID, post_name FROM {$wpdb->posts} WHERE {$where} ORDER BY ID ASC LIMIT 5" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Conditions are prepared above.

WP_CLI::log( sprintf( 'Canonical page: %d; numbered candidates: %d', $canonical->ID, $total ) );
foreach ( $sample as $page ) {
	WP_CLI::log( sprintf( '  %d %s', $page->ID, $page->post_name ) );
}

if ( 'dry-run' === $mode ) {
	WP_CLI::log( 'Dry run only. Back up the database and rerun with the positional argument delete.' );
	return;
}

$deleted = 0;
while ( true ) {
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE {$where} ORDER BY ID ASC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Conditions are prepared above.
	if ( ! $ids ) {
		break;
	}

	foreach ( $ids as $id ) {
		$page = get_post( (int) $id );
		if ( ! $page instanceof WP_Post || (int) $page->post_parent !== (int) $account->ID || ! preg_match( '/^bubble-points-[1-9][0-9]*$/', $page->post_name ) || 'Bubble Points' !== $page->post_title || '' !== $page->post_content || ! in_array( get_page_template_slug( $page->ID ), array( '', 'page-bubble-points.php' ), true ) ) {
			WP_CLI::error( sprintf( 'Candidate %d differs from the expected duplicate. Stopped after %d deletions.', $id, $deleted ) );
		}
		if ( ! wp_delete_post( $page->ID, true ) ) {
			WP_CLI::error( sprintf( 'Could not delete page %d. Stopped after %d deletions.', $id, $deleted ) );
		}
		++$deleted;
	}
	WP_CLI::log( sprintf( 'Deleted %d / %d candidates', $deleted, $total ) );
}

WP_CLI::success( sprintf( 'Deleted %d numbered Bubble Points pages. Canonical page %d was preserved.', $deleted, $canonical->ID ) );
