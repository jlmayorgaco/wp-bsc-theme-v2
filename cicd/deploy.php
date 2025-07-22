<?php
// Load .env file
$envPath = '/var/www/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$secret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? '';
$repoDir = '/var/www/bubblesskincare.com/htdocs/wp-content/themes/wp-bsc-theme-v2';

header('Content-Type: text/plain');

$signature = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secret);

if (!hash_equals($signature, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '')) {
    http_response_code(403);
    exit('Invalid signature');
}

$output = shell_exec("cd $repoDir && git pull 2>&1");
echo "✅ Deployed theme:\n" . $output;
