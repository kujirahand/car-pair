<?php
require_once __DIR__ . '/../logic/UpdateWebhook.php';

class UpdateWebhookTest {
    private function request($changes = [], $exitCode = 0) {
        $body = json_encode(['repository' => ['full_name' => 'kujirahand/car-pair'],
            'ref' => 'refs/heads/main', 'deleted' => false]);
        $args = array_merge(['method' => 'POST', 'event' => 'push', 'body' => $body,
            'secret' => 'test-secret'], $changes);
        $signature = $changes['signature'] ?? 'sha256=' . hash_hmac('sha256', $args['body'], 'test-secret');
        $calls = 0;
        $response = UpdateWebhook::handle($args['method'], $args['event'], $signature,
            $args['body'], $args['secret'], function () use (&$calls, $exitCode) {
                $calls++;
                return $exitCode;
            });
        return [$response[0], $calls];
    }

    private function assertResponse($actual, $expected) {
        if ($actual !== $expected) {
            throw new Exception('Webhook結果が不正: ' . json_encode($actual));
        }
    }

    public function testValidPushRunsScriptAndReportsFailure() {
        $this->assertResponse($this->request(), [200, 1]);
        $this->assertResponse($this->request([], 1), [500, 1]);
    }

    public function testUnauthenticatedRequestsNeverRunScript() {
        $this->assertResponse($this->request(['method' => 'GET']), [405, 0]);
        $this->assertResponse($this->request(['secret' => '']), [503, 0]);
        $this->assertResponse($this->request(['signature' => 'sha256=invalid']), [403, 0]);
        $this->assertResponse($this->request(['secret' => 'different-secret']), [403, 0]);
        $this->assertResponse($this->request(['signature' => '']), [403, 0]);
    }

    public function testOtherEventsAndPushesNeverRunScript() {
        $this->assertResponse($this->request(['event' => 'ping']), [200, 0]);
        foreach ([['ref' => 'refs/heads/feature'], ['deleted' => true],
            ['repository' => ['full_name' => 'other/repo']], ['deleted' => null]] as $change) {
            $payload = array_merge(['repository' => ['full_name' => 'kujirahand/car-pair'],
                'ref' => 'refs/heads/main', 'deleted' => false], $change);
            $this->assertResponse($this->request(['body' => json_encode($payload)]), [200, 0]);
        }
        $this->assertResponse($this->request(['body' => '{']), [400, 0]);
    }

    public function testModifiedPayloadInvalidatesSignature() {
        $body = json_encode(['repository' => ['full_name' => 'kujirahand/car-pair'],
            'ref' => 'refs/heads/main', 'deleted' => false]);
        $this->assertResponse($this->request(['body' => $body . ' ',
            'signature' => 'sha256=' . hash_hmac('sha256', $body, 'test-secret')]), [403, 0]);
    }

    public function testRunScriptReturnsActualExitCode() {
        $script = tempnam(sys_get_temp_dir(), 'car-pair-script-');
        try {
            if (file_put_contents($script, "#!/bin/sh\necho test-output\necho test-error >&2\nexit 7\n") === false) {
                throw new Exception('テストスクリプトを作成できません。');
            }
            if (UpdateWebhook::runScript($script) !== 7) {
                throw new Exception('スクリプトの終了コードが反映されません。');
            }
        } finally {
            unlink($script);
        }
    }
}
