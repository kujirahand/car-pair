<?php

class UpdateScriptTest {
    private function command($args, $cwd, $expected = 0) {
        $process = proc_open($args, [0 => ['file', '/dev/null', 'r'],
            1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $cwd);
        if (!is_resource($process)) {
            throw new Exception('コマンドを起動できません。');
        }
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $code = proc_close($process);
        if (($expected === 0 && $code !== 0) || ($expected !== 0 && $code === 0)) {
            throw new Exception('コマンドの終了コードが不正: ' . $output);
        }
        return trim($output);
    }

    private function removeTree($dir) {
        foreach (scandir($dir) as $name) {
            if ($name === '.' || $name === '..') { continue; }
            $path = $dir . '/' . $name;
            if (is_dir($path) && !is_link($path)) { $this->removeTree($path); }
            else { unlink($path); }
        }
        rmdir($dir);
    }

    private function fixture(callable $test) {
        $root = sys_get_temp_dir() . '/car-pair-update-' . bin2hex(random_bytes(6));
        mkdir($root);
        try {
            $this->command(['git', 'init', '--bare', '--initial-branch=main', $root . '/remote'], $root);
            $this->command(['git', 'clone', $root . '/remote', $root . '/source'], $root);
            $source = $root . '/source';
            $this->command(['git', 'config', 'user.email', 'test@example.invalid'], $source);
            $this->command(['git', 'config', 'user.name', 'Test'], $source);
            copy(__DIR__ . '/../update.sh', $source . '/update.sh');
            file_put_contents($source . '/app.txt', 'old');
            $this->command(['git', 'add', '.'], $source);
            $this->command(['git', 'commit', '-m', 'initial'], $source);
            $this->command(['git', 'push', 'origin', 'main'], $source);
            $this->command(['git', 'clone', $root . '/remote', $root . '/deploy'], $root);
            $deploy = $root . '/deploy';
            $this->command(['git', 'config', 'user.email', 'test@example.invalid'], $deploy);
            $this->command(['git', 'config', 'user.name', 'Test'], $deploy);
            mkdir($deploy . '/data');
            file_put_contents($deploy . '/data/history.json', 'preserve');
            file_put_contents($source . '/app.txt', 'new');
            $this->command(['git', 'commit', '-am', 'new release'], $source);
            $this->command(['git', 'push', 'origin', 'main'], $source);
            $test($deploy, $root);
            if (file_get_contents($deploy . '/data/history.json') !== 'preserve') {
                throw new Exception('未追跡データが変更されました。');
            }
        } finally {
            $this->removeTree($root);
        }
    }

    private function update($deploy, $cwd, $expected = 0) {
        return $this->command(['/bin/sh', $deploy . '/update.sh'], $cwd, $expected);
    }

    private function assertContains($output, $message) {
        if (strpos($output, $message) === false) {
            throw new Exception('想定した停止理由ではありません: ' . $output);
        }
    }

    public function testUpdatesFromDifferentDirectoryAndRepeatedCall() {
        $this->fixture(function ($deploy, $root) {
            $this->update($deploy, $root);
            if (file_get_contents($deploy . '/app.txt') !== 'new') {
                throw new Exception('最新版へ更新されません。');
            }
            $this->update($deploy, $root);
        });
    }

    public function testRejectsTrackedChangesIncludingStagedChanges() {
        $this->fixture(function ($deploy, $root) {
            file_put_contents($deploy . '/app.txt', 'local');
            $this->assertContains($this->update($deploy, $root, 1), 'ローカル変更');
            $this->command(['git', 'add', 'app.txt'], $deploy);
            $this->assertContains($this->update($deploy, $root, 1), 'ローカル変更');
            if (file_get_contents($deploy . '/app.txt') !== 'local') {
                throw new Exception('ローカル変更が失われました。');
            }
        });
    }

    public function testRejectsDivergentHistory() {
        $this->fixture(function ($deploy, $root) {
            file_put_contents($deploy . '/local.txt', 'local');
            $this->command(['git', 'add', 'local.txt'], $deploy);
            $this->command(['git', 'commit', '-m', 'local commit'], $deploy);
            $head = $this->command(['git', 'rev-parse', 'HEAD'], $deploy);
            $this->assertContains($this->update($deploy, $root, 1), '分岐');
            if ($head !== $this->command(['git', 'rev-parse', 'HEAD'], $deploy)) {
                throw new Exception('分岐した履歴が変更されました。');
            }
        });
    }

    public function testRejectsOtherBranchAndExistingLock() {
        $this->fixture(function ($deploy, $root) {
            $this->command(['git', 'checkout', '-b', 'feature'], $deploy);
            $this->assertContains($this->update($deploy, $root, 1), '更新先ブランチ');
            $this->command(['git', 'checkout', 'main'], $deploy);
            mkdir($deploy . '/.git/car-pair-update.lock');
            $this->assertContains($this->update($deploy, $root, 1), '実行中');
            rmdir($deploy . '/.git/car-pair-update.lock');
            $this->update($deploy, $root);
        });
    }

    public function testFetchFailureReleasesLock() {
        $this->fixture(function ($deploy, $root) {
            $this->command(['git', 'remote', 'set-url', 'origin', $root . '/missing'], $deploy);
            $this->update($deploy, $root, 1);
            if (is_dir($deploy . '/.git/car-pair-update.lock') || file_get_contents($deploy . '/app.txt') !== 'old') {
                throw new Exception('取得失敗後にロックや変更が残りました。');
            }
            $this->command(['git', 'remote', 'set-url', 'origin', $root . '/remote'], $deploy);
            $this->update($deploy, $root);
        });
    }
}
