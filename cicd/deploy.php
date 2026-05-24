<?php
// Load .env file.
$envPath = '/var/www/.env';
if (file_exists( $envPath )) {
	$lines = file( $envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	foreach ($lines as $line) {
		if (strpos( $line, '=' ) !== false) {
			list($key, $value) = explode( '=', $line, 2 );
			$key               = trim( $key );
			$value             = trim( $value );
			$_ENV[ $key ]      = $value;
			putenv( $key . '=' . $value );
		}
	}
}

$secret  = getenv( 'GITHUB_WEBHOOK_SECRET' ) ?: '';
$repoDir = '/var/www/bubblesskincare.com/htdocs/wp-content/themes/wp-bsc-theme-v2';

header( 'Content-Type: text/plain' );

$requestMethod = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?: '';
if ($requestMethod !== 'POST') {
	http_response_code( 405 );
	exit( 'Method not allowed' );
}

if ($secret === '') {
	http_response_code( 500 );
	exit( 'Webhook secret is not configured' );
}

$payload   = file_get_contents( 'php://input' ) ?: '';
$signature = 'sha256=' . hash_hmac( 'sha256', $payload, $secret );

$webhookSignature = filter_input( INPUT_SERVER, 'HTTP_X_HUB_SIGNATURE_256', FILTER_UNSAFE_RAW ) ?: '';
if (!hash_equals( $signature, $webhookSignature )) {
	http_response_code( 403 );
	exit( 'Invalid signature' );
}

$output = shell_exec( 'cd ' . escapeshellarg( $repoDir ) . ' && git pull 2>&1' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Standalone webhook returns text/plain deploy log.
echo "Deployed theme:\n" . htmlspecialchars( (string) $output, ENT_NOQUOTES, 'UTF-8' );
