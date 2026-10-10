<?php
require_once __DIR__ . '/logic/UpdateWebhook.php';

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
}

try {
    $configFile = __DIR__ . '/data/webhook.json';
    $config = is_file($configFile) ? json_decode(file_get_contents($configFile), true) : [];
    [$status, $message] = UpdateWebhook::handle(
        $_SERVER['REQUEST_METHOD'] ?? '',
        $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '',
        $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '',
        file_get_contents('php://input'),
        $config['secret'] ?? '',
        static function () { return UpdateWebhook::runScript(__DIR__ . '/update.sh'); }
    );
} catch (Throwable $e) {
    error_log('[car-pair update] ' . $e->getMessage());
    $status = 500;
    $message = '更新に失敗しました。サーバーのログを確認してください。';
}
http_response_code($status);
echo $message . "\n";
