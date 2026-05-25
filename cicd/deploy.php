<?php
/**
 * GitHub webhook deploy endpoint for the production theme.
 *
 * @package BSC2
 */

$env_path = '/var/www/.env';
if ( file_exists( $env_path ) ) {
	$lines = file( $env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ( $lines as $line ) {
		if ( false === strpos( $line, '=' ) ) {
			continue;
		}

		list( $key, $value ) = explode( '=', $line, 2 );
		$key                = trim( $key );
		$value              = trim( $value );
		$_ENV[ $key ]       = $value;
		putenv( $key . '=' . $value );
	}
}

$secret   = getenv( 'GITHUB_WEBHOOK_SECRET' ) ?: '';
$web_root = getenv( 'BSC_DEPLOY_WEB_ROOT' ) ?: '/var/www/bubblesskincare.com/htdocs';
$repo_dir = getenv( 'BSC_DEPLOY_REPO_DIR' ) ?: $web_root . '/wp-content/themes/wp-bsc-theme-v2';

header( 'Content-Type: text/plain; charset=UTF-8' );

$request_method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?: '';
if ( 'POST' !== $request_method ) {
	http_response_code( 405 );
	exit( 'Method not allowed' );
}

if ( '' === $secret ) {
	http_response_code( 500 );
	exit( 'Webhook secret is not configured' );
}

$payload   = file_get_contents( 'php://input' ) ?: '';
$signature = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );

$webhook_signature = filter_input( INPUT_SERVER, 'HTTP_X_HUB_SIGNATURE_256', FILTER_UNSAFE_RAW ) ?: '';
if ( ! hash_equals( $signature, $webhook_signature ) ) {
	http_response_code( 403 );
	exit( 'Invalid signature' );
}

/**
 * Run a deploy command inside the theme repo.
 *
 * @param string $repo_dir Theme repository directory.
 * @param string $command  Command to run.
 */
function bsc_deploy_run_git_command( string $repo_dir, string $command ): string {
	$full_command = 'cd ' . escapeshellarg( $repo_dir ) . ' && ' . $command . ' 2>&1';

	return (string) shell_exec( $full_command );
}

/**
 * Delete a path inside the production theme directory.
 *
 * @param string $path Absolute path to delete.
 */
function bsc_deploy_delete_path( string $path ): void {
	if ( ! file_exists( $path ) && ! is_link( $path ) ) {
		return;
	}

	if ( is_file( $path ) || is_link( $path ) ) {
		unlink( $path );
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() && ! $item->isLink() ) {
			rmdir( $item->getPathname() );
			continue;
		}

		unlink( $item->getPathname() );
	}

	rmdir( $path );
}

/**
 * Remove development-only files from the public theme directory.
 *
 * @param string $repo_dir Theme repository directory.
 */
function bsc_deploy_cleanup_public_theme( string $repo_dir ): array {
	$repo_real_path = realpath( $repo_dir );
	if ( false === $repo_real_path ) {
		return array();
	}

	$repo_real_prefix = rtrim( $repo_real_path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	$paths = array(
		'.github',
		'.gitattributes',
		'.gitignore',
		'.stylelintrc.json',
		'README.md',
		'ROADMAP_BSC.md',
		'composer.json',
		'composer.lock',
		'node_modules',
		'package.json',
		'package-lock.json',
		'phpcs.xml.dist',
		'phpstan.neon',
		'phpstan-baseline.neon',
		'phpstan-bootstrap.php',
		'playwright-report',
		'sass',
		'style.css.map',
		'test-results',
		'tests',
		'tools',
		'vendor',
		'Videos',
		'admin.zip',
		'admin2.zip',
	);
	$removed = array();

	foreach ( $paths as $relative_path ) {
		$path = realpath( $repo_dir . '/' . $relative_path );
		if ( false === $path || 0 !== strpos( $path, $repo_real_prefix ) ) {
			continue;
		}

		bsc_deploy_delete_path( $path );
		$removed[] = $relative_path;
	}

	return $removed;
}

/**
 * Write the production robots.txt expected after launch.
 *
 * @param string $web_root Public WordPress root.
 */
function bsc_deploy_write_robots_txt( string $web_root ): bool {
	$robots = implode(
		"\n",
		array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Disallow: /cart/',
			'Disallow: /checkout/',
			'Disallow: /my-account/',
			'Disallow: /*?s=',
			'Disallow: /*?orderby=',
			'Disallow: /*filter_',
			'Disallow: /*min_price=',
			'Disallow: /*max_price=',
			'Sitemap: https://bubblesskincare.com/wp-sitemap.xml',
			'',
		)
	);

	return false !== file_put_contents( rtrim( $web_root, '/' ) . '/robots.txt', $robots );
}

$log  = bsc_deploy_run_git_command( $repo_dir, 'git reset --hard HEAD' );
$log .= bsc_deploy_run_git_command( $repo_dir, 'git clean -fd' );
$log .= bsc_deploy_run_git_command( $repo_dir, 'git pull --ff-only' );

$removed       = bsc_deploy_cleanup_public_theme( $repo_dir );
$robots_status = bsc_deploy_write_robots_txt( $web_root ) ? 'updated' : 'failed';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
echo "Deployed theme:\n" . htmlspecialchars( $log, ENT_NOQUOTES, 'UTF-8' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
echo "\nRemoved public artifacts: " . htmlspecialchars( implode( ', ', $removed ), ENT_NOQUOTES, 'UTF-8' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
echo "\nrobots.txt: " . htmlspecialchars( $robots_status, ENT_NOQUOTES, 'UTF-8' ) . "\n";
