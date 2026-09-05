<?php
/**
 * GitHub webhook deploy endpoint for the production theme.
 *
 * Flow: verify the delivery, sync the pushed branch, strip development files,
 * invalidate every cache in front of PHP, then smoke test the site. A failing
 * smoke test rolls the theme back to the commit that was live before.
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
		$key                 = trim( $key );
		$value               = trim( $value );
		$_ENV[ $key ]        = $value;
		putenv( $key . '=' . $value );
	}
}

/**
 * Read a deployment environment variable.
 *
 * @param string $key     Environment variable name.
 * @param string $default Default value.
 */
function bsc_deploy_env( string $key, string $default = '' ): string {
	$value = getenv( $key );

	return false === $value ? $default : (string) $value;
}

/**
 * Append a line to the deploy log. Logging must never break a deploy.
 *
 * @param string $log_file Absolute log path.
 * @param string $message  Message to append.
 */
function bsc_deploy_log( string $log_file, string $message ): void {
	if ( '' === $log_file ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- An unwritable log must not abort the deploy.
	@file_put_contents( $log_file, '[' . gmdate( 'Y-m-d H:i:s' ) . '] ' . $message . "\n", FILE_APPEND );
}

$secret     = bsc_deploy_env( 'GITHUB_WEBHOOK_SECRET' );
$web_root   = bsc_deploy_env( 'BSC_DEPLOY_WEB_ROOT', '/var/www/bubblesskincare.com/htdocs' );
$repo_dir   = bsc_deploy_env( 'BSC_DEPLOY_REPO_DIR', $web_root . '/wp-content/themes/wp-bsc-theme-v2' );
$log_file   = bsc_deploy_env( 'BSC_DEPLOY_LOG', '/var/www/bsc-deploy.log' );
$smoke_url  = bsc_deploy_env( 'BSC_DEPLOY_SMOKE_URL', 'https://bubblesskincare.com/' );
$cache_dir  = bsc_deploy_env( 'BSC_DEPLOY_CACHE_DIR', '/var/run/nginx-cache' );
$enabled    = strtolower( bsc_deploy_env( 'BSC_DEPLOY_WEBHOOK_ENABLED' ) );

header( 'Content-Type: text/plain; charset=UTF-8' );

if ( ! in_array( $enabled, array( '1', 'true', 'yes' ), true ) ) {
	bsc_deploy_log( $log_file, 'REJECTED: BSC_DEPLOY_WEBHOOK_ENABLED is not 1/true/yes.' );
	http_response_code( 404 );
	exit( 'Not found' );
}

$request_method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$request_method = is_string( $request_method ) ? $request_method : '';
if ( 'POST' !== $request_method ) {
	http_response_code( 405 );
	exit( 'Method not allowed' );
}

if ( '' === $secret ) {
	bsc_deploy_log( $log_file, 'REJECTED: GITHUB_WEBHOOK_SECRET is empty.' );
	http_response_code( 500 );
	exit( 'Webhook secret is not configured' );
}

$payload   = file_get_contents( 'php://input' );
$payload   = false === $payload ? '' : $payload;
$signature = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );

$webhook_signature = filter_input( INPUT_SERVER, 'HTTP_X_HUB_SIGNATURE_256', FILTER_UNSAFE_RAW );
$webhook_signature = is_string( $webhook_signature ) ? $webhook_signature : '';
if ( ! hash_equals( $signature, $webhook_signature ) ) {
	bsc_deploy_log( $log_file, 'REJECTED: invalid X-Hub-Signature-256.' );
	http_response_code( 403 );
	exit( 'Invalid signature' );
}

$github_event = filter_input( INPUT_SERVER, 'HTTP_X_GITHUB_EVENT', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
$github_event = is_string( $github_event ) ? $github_event : '';
if ( 'ping' === $github_event ) {
	bsc_deploy_log( $log_file, 'PING: webhook reachable and signature valid.' );
	exit( "pong\n" );
}

if ( 'push' !== $github_event ) {
	bsc_deploy_log( $log_file, 'IGNORED: event "' . $github_event . '" is not a push.' );
	http_response_code( 202 );
	exit( 'Ignored event' );
}

$decoded_payload = json_decode( $payload, true );
$pushed_ref      = is_array( $decoded_payload ) ? (string) ( $decoded_payload['ref'] ?? '' ) : '';

/*
 * Branches this endpoint deploys. A comma separated list so the same endpoint
 * can serve the integration branch and the release branch, which is what the
 * repo actually does: work lands on MVP2 and is merged into main by PR.
 */
$allowed_refs = array_values(
	array_filter(
		array_map( 'trim', explode( ',', bsc_deploy_env( 'BSC_DEPLOY_REF', 'refs/heads/main,refs/heads/MVP2' ) ) )
	)
);

if ( ! in_array( $pushed_ref, $allowed_refs, true ) ) {
	bsc_deploy_log( $log_file, 'IGNORED: ref "' . $pushed_ref . '" not in [' . implode( ', ', $allowed_refs ) . '].' );
	http_response_code( 202 );
	exit( 'Ignored ref' );
}

$branch = substr( $pushed_ref, strlen( 'refs/heads/' ) );
if ( '' === $branch || ! preg_match( '{^[A-Za-z0-9._/-]+$}', $branch ) ) {
	bsc_deploy_log( $log_file, 'REJECTED: unusable branch name from ref "' . $pushed_ref . '".' );
	http_response_code( 400 );
	exit( 'Invalid ref' );
}

/**
 * Run a deploy command inside the theme repo.
 *
 * @param string $repo_dir  Theme repository directory.
 * @param string $command   Command to run.
 * @param int    $exit_code Command exit code, by reference.
 */
function bsc_deploy_run_git_command( string $repo_dir, string $command, int &$exit_code = 0 ): string {
	$full_command = 'cd ' . escapeshellarg( $repo_dir ) . ' && ' . $command . ' 2>&1';
	$output       = array();

	exec( $full_command, $output, $exit_code );

	return implode( "\n", $output ) . "\n";
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
 * Never list `cicd` here: it holds this endpoint, so deleting it makes every
 * later webhook delivery 404. Never list `vendor` either: Font Awesome, Swiper
 * and the webfonts are enqueued from it at runtime (see scripts/script_init.php),
 * and .gitignore already limits what ships. Same for `.gitignore` itself, which
 * the next `git clean` needs in order to know what to keep.
 *
 * @param string $repo_dir Theme repository directory.
 */
function bsc_deploy_cleanup_public_theme( string $repo_dir ): array {
	$repo_real_path = realpath( $repo_dir );
	if ( false === $repo_real_path ) {
		return array();
	}

	$repo_real_prefix = rtrim( $repo_real_path, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR;
	$paths            = array(
		'.github',
		'.gitattributes',
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
		'Videos',
		'admin.zip',
		'admin2.zip',
	);
	$removed          = array();

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

/**
 * Publish the deployed commit so CI can assert what production is actually running.
 *
 * The GitHub workflow polls this file and fails when it does not match the
 * pushed commit, which is what turns "did it deploy?" into a red or green check.
 *
 * @param string $web_root Public WordPress root.
 * @param string $sha      Deployed commit.
 */
function bsc_deploy_write_version_file( string $web_root, string $sha ): bool {
	return false !== file_put_contents( rtrim( $web_root, '/' ) . '/deploy-version.txt', $sha . "\n" );
}

/**
 * Empty the nginx page cache so visitors are not served pre-deploy HTML.
 *
 * Best effort: the directory belongs to nginx and may not be writable here.
 * Restricted to the usual cache roots so a bad env value cannot wipe the site.
 *
 * @param string $cache_dir Cache directory to empty.
 */
function bsc_deploy_purge_page_cache( string $cache_dir ): string {
	if ( '' === $cache_dir ) {
		return 'disabled';
	}

	$real = realpath( $cache_dir );
	if ( false === $real || ! is_dir( $real ) ) {
		return 'absent';
	}

	$allowed = false;
	foreach ( array( '/var/run/', '/run/', '/var/cache/', '/var/lib/nginx/' ) as $prefix ) {
		if ( 0 === strpos( $real . '/', $prefix ) ) {
			$allowed = true;
			break;
		}
	}

	if ( ! $allowed ) {
		return 'refused (outside cache roots)';
	}

	$purged = 0;
	$failed = 0;
	foreach ( new FilesystemIterator( $real, FilesystemIterator::SKIP_DOTS ) as $item ) {
		try {
			bsc_deploy_delete_path( $item->getPathname() );
			++$purged;
		} catch ( Throwable $e ) {
			++$failed;
		}
	}

	return sprintf( 'purged %d entries, %d failed', $purged, $failed );
}

/**
 * Request the site and return the HTTP status, or 0 when unreachable.
 *
 * @param string $url Absolute URL to request.
 */
function bsc_deploy_http_status( string $url ): int {
	// Cache buster: a page cache hit would report 200 for a theme that no longer parses.
	$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'bsc-deploy-check=' . time();

	if ( function_exists( 'curl_init' ) ) {
		$handle = curl_init( $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $handle, CURLOPT_TIMEOUT, 15 );
		curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_exec( $handle );
		$status = (int) curl_getinfo( $handle, CURLINFO_RESPONSE_CODE );
		curl_close( $handle );

		return $status;
	}

	$output    = array();
	$exit_code = 0;
	exec( 'curl -s -o /dev/null -w "%{http_code}" --max-time 15 ' . escapeshellarg( $url ), $output, $exit_code );

	return (int) trim( implode( '', $output ) );
}

/**
 * Smoke test the site, tolerating one transient failure.
 *
 * PHP-FPM is serving this very request, so a saturated pool can refuse the
 * first check for reasons that have nothing to do with the deployed code.
 *
 * @param string $url      Absolute URL to request.
 * @param int    $attempts How many times to try before giving up.
 * @param int    $status   Last observed status, by reference.
 */
function bsc_deploy_smoke_passes( string $url, int $attempts, int &$status = 0 ): bool {
	for ( $attempt = 1; $attempt <= $attempts; $attempt++ ) {
		$status = bsc_deploy_http_status( $url );
		if ( $status >= 200 && $status < 400 ) {
			return true;
		}

		if ( $attempt < $attempts ) {
			sleep( 3 );
		}
	}

	return false;
}

/*
 * Sync to the branch that was actually pushed. A bare `git pull --ff-only`
 * depends on whichever branch happens to be checked out on the server, and it
 * cannot fast-forward once main has been force-pushed, which is exactly how
 * this deploy silently froze for three weeks.
 */
$previous_sha = trim( bsc_deploy_run_git_command( $repo_dir, 'git rev-parse HEAD' ) );
$log          = 'previous HEAD: ' . $previous_sha . "\n";
$failed       = false;
$commands     = array(
	'git fetch --prune origin',
	'git checkout -B ' . escapeshellarg( $branch ) . ' ' . escapeshellarg( 'origin/' . $branch ),
	'git reset --hard ' . escapeshellarg( 'origin/' . $branch ),
	'git clean -fd',
);

foreach ( $commands as $command ) {
	$exit_code = 0;
	$log      .= '$ ' . $command . "\n" . bsc_deploy_run_git_command( $repo_dir, $command, $exit_code );

	if ( 0 !== $exit_code ) {
		$failed = true;
		$log   .= 'FAILED with exit code ' . $exit_code . "\n";
		break;
	}
}

if ( $failed ) {
	$summary = sprintf( 'branch=%s status=SYNC_FAILED head=%s', $branch, $previous_sha );
	bsc_deploy_log( $log_file, $summary . "\n" . $log );
	http_response_code( 500 );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
	echo htmlspecialchars( $log . $summary, ENT_NOQUOTES, 'UTF-8' ) . "\n";
	exit;
}

$deployed_sha  = trim( bsc_deploy_run_git_command( $repo_dir, 'git rev-parse HEAD' ) );
$removed       = bsc_deploy_cleanup_public_theme( $repo_dir );
$robots_status = bsc_deploy_write_robots_txt( $web_root ) ? 'updated' : 'failed';
$cache_status  = bsc_deploy_purge_page_cache( $cache_dir );

// Without this, FPM keeps serving the previous bytecode when opcache runs with validate_timestamps=0.
$opcache_status = 'unavailable';
if ( function_exists( 'opcache_reset' ) ) {
	$opcache_status = opcache_reset() ? 'reset' : 'reset failed';
}

$smoke_status = 0;
$smoke_ok     = bsc_deploy_smoke_passes( $smoke_url, 3, $smoke_status );
$rollback     = 'not needed';

if ( ! $smoke_ok && '' !== $previous_sha && preg_match( '{^[0-9a-f]{40}$}', $previous_sha ) ) {
	$log .= '$ ROLLBACK to ' . $previous_sha . "\n";
	$log .= bsc_deploy_run_git_command( $repo_dir, 'git reset --hard ' . escapeshellarg( $previous_sha ) );

	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
	bsc_deploy_purge_page_cache( $cache_dir );

	$recovered_status = 0;
	$recovered        = bsc_deploy_smoke_passes( $smoke_url, 3, $recovered_status );
	$rollback         = $recovered
		? 'rolled back to ' . $previous_sha . ' (site recovered)'
		: 'rolled back to ' . $previous_sha . ' (SITE STILL DOWN, status ' . $recovered_status . ')';
	$deployed_sha     = $previous_sha;
}

// Published last, so it always reports what is really live, rollback included.
$version_status = bsc_deploy_write_version_file( $web_root, $deployed_sha ) ? 'written' : 'failed';

$summary = sprintf(
	'branch=%s head=%s status=%s smoke=%d rollback=%s removed=%s robots=%s version=%s cache=%s opcache=%s',
	$branch,
	$deployed_sha,
	$smoke_ok ? 'ok' : 'SMOKE_FAILED',
	$smoke_status,
	$rollback,
	implode( '|', $removed ),
	$robots_status,
	$version_status,
	$cache_status,
	$opcache_status
);

bsc_deploy_log( $log_file, $summary . "\n" . $log );

// A failed deploy must show up red in the GitHub webhook delivery list.
if ( ! $smoke_ok ) {
	http_response_code( 500 );
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
echo htmlspecialchars( $log, ENT_NOQUOTES, 'UTF-8' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns sanitized text/plain deploy log.
echo "\n" . htmlspecialchars( $summary, ENT_NOQUOTES, 'UTF-8' ) . "\n";
