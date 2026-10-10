<?php
require_once __DIR__ . '/../logic/PairingAlgorithm.php';

class PairingLockTest {
    private function members() {
        $members = [];
        for ($i = 1; $i <= 10; $i++) {
            $members[] = ['id' => (string)$i, 'name' => "参加者$i", 'gender' => 'M',
                'family_id' => "F$i", 'is_driver' => in_array($i, [1, 5, 9]) ? '1' : '0'];
        }
        return $members;
    }
    private function check($condition, $message) {
        if (!$condition) throw new Exception($message);
    }
    public function testFixedCarsPreservedInEveryModeAndRepeatedRetry() {
        $m = $this->members();
        $data = ['cars' => [array_slice($m, 0, 4), array_slice($m, 4, 4), array_slice($m, 8)], 'walk' => [], 'locked' => ['1']];
        foreach (array_keys(PairingAlgorithm::getModes()) as $mode) {
            $next = $data;
            for ($i = 0; $i < 3; $i++) {
                $next = (new PairingAlgorithm())->regenerate($m, [], $mode, $next);
                $this->check($next['cars'][1] === $data['cars'][1], '固定した車Bを保持すること');
                $this->check($next['locked'] === ['1'], '繰り返し再生成しても固定を維持すること');
                $ids = array_column(array_merge(...array_merge($next['cars'], [$next['walk']])), 'id');
                sort($ids);
                $expected = array_column($m, 'id'); sort($expected);
                $this->check($ids === $expected, '全参加者が一度ずつ含まれること');
                foreach ($next['cars'] as $index => $group) {
                    if ($index !== 1) $this->check(count($group) <= 4, '自動再生成した車は4人以内であること');
                }
            }
        }
    }
    public function testOversizedFixedCarAndAllFixed() {
        $m = $this->members();
        $data = ['cars' => [[], [], $m], 'walk' => [], 'locked' => ['2']];
        $result = (new PairingAlgorithm())->regenerate($m, [], 'family', $data);
        $this->check($result['cars'] === $data['cars'], '人数上限を超える手動グループも車名・メンバーを固定できること');
        $this->check($result['walk'] === [] && $result['locked'] === ['2'], '全員固定でも生成できること');
    }
    public function testRemainingPassengersWalkAndCanonicalData() {
        $m = array_slice($this->members(), 0, 4);
        $data = ['cars' => [[$m[0]]], 'walk' => array_slice($m, 1), 'locked' => ['0']];
        $data['cars'][0][0]['name'] = '改ざん';
        $result = (new PairingAlgorithm())->regenerate($m, [], 'family', $data);
        $this->check($result['cars'][0] === [$m[0]], '名簿からメンバー情報を復元すること');
        $this->check(count($result['walk']) === 3, '固定した車に残りの乗客を追加せず、ドライバー不足なら徒歩にすること');
    }
    public function testInvalidLocksRejected() {
        $m = $this->members();
        foreach (['bad', ['walk'], ['-1'], ['99'], [[]]] as $locked) {
            try {
                (new PairingAlgorithm())->regenerate($m, [], 'family', ['cars' => [$m], 'walk' => [], 'locked' => $locked]);
            } catch (InvalidArgumentException $e) {
                continue;
            }
            throw new Exception('不正な固定指定が受理されました');
        }
    }
}
