<?php

class UpdateWebhook {
    public static function handle($method, $event, $signature, $body, $secret, callable $update) {
        if ($method !== 'POST') {
            return [405, 'POSTで呼び出してください。'];
        }
        if (!is_string($secret) || $secret === '') {
            return [503, 'WebhookのSecretが設定されていません。'];
        }
        $expected = 'sha256=' . hash_hmac('sha256', $body, $secret);
        if (!is_string($signature) || !hash_equals($expected, $signature)) {
            return [403, '署名が正しくありません。'];
        }
        if ($event !== 'push') {
            return [200, '対象外のイベントです。'];
        }
        $payload = json_decode($body, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            return [400, 'JSONが正しくありません。'];
        }
        if (($payload['repository']['full_name'] ?? null) !== 'kujirahand/car-pair'
            || ($payload['ref'] ?? null) !== 'refs/heads/main'
            || ($payload['deleted'] ?? null) !== false) {
            return [200, '対象外のPushです。'];
        }
        return $update() === 0
            ? [200, '更新しました。']
            : [500, '更新に失敗しました。サーバーのログを確認してください。'];
    }

    public static function runScript($script) {
        $log = tmpfile();
        if ($log === false) {
            throw new RuntimeException('更新ログを作成できません。');
        }
        try {
            $process = proc_open(['/bin/sh', $script], [
                0 => ['file', '/dev/null', 'r'],
                1 => $log,
                2 => $log,
            ], $pipes, dirname($script), null, ['bypass_shell' => true]);
            if (!is_resource($process)) {
                throw new RuntimeException('更新スクリプトを起動できません。');
            }
            $code = proc_close($process);
            rewind($log);
            error_log('[car-pair update] ' . stream_get_contents($log));
            return $code;
        } finally {
            fclose($log);
        }
    }
}
