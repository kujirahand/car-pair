<?php
require_once __DIR__ . '/../logic/PairingAlgorithm.php';

class PairingAlgorithmTest {
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public function testEnoughDriversNoWalk() {
        $members = [
            ['id' => '1', 'name' => 'A1', 'family_id' => '1', 'gender' => 'M', 'is_driver' => '1', 'participation_count' => '0'],
            ['id' => '2', 'name' => 'A2', 'family_id' => '1', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '3', 'name' => 'B1', 'family_id' => '2', 'gender' => 'M', 'is_driver' => '1', 'participation_count' => '0'],
            ['id' => '4', 'name' => 'B2', 'family_id' => '2', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '5', 'name' => 'C1', 'family_id' => '3', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '6', 'name' => 'C2', 'family_id' => '3', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '7', 'name' => 'D1', 'family_id' => '4', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '8', 'name' => 'D2', 'family_id' => '4', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
        ];
        
        $pa = new PairingAlgorithm();
        $res = $pa->generate($members, []);
        
        $this->assert($res['success'] === true, "Should be successful");
        $this->assert(isset($res['cars']) && count($res['cars']) === 2, "Should use 2 cars");
        $this->assert(empty($res['walk']), "Walk array should be empty");
    }

    public function testNotEnoughDriversWithWalk() {
        $members = [
            // Only 1 driver in total 8 people (needs 2 cars normally)
            ['id' => '1', 'name' => 'A1', 'family_id' => '1', 'gender' => 'M', 'is_driver' => '1', 'participation_count' => '0'],
            ['id' => '2', 'name' => 'A2', 'family_id' => '1', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '3', 'name' => 'B1', 'family_id' => '2', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '4', 'name' => 'B2', 'family_id' => '2', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '5', 'name' => 'C1', 'family_id' => '3', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '6', 'name' => 'C2', 'family_id' => '3', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '7', 'name' => 'D1', 'family_id' => '4', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '8', 'name' => 'D2', 'family_id' => '4', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
        ];
        
        $pa = new PairingAlgorithm();
        $res = $pa->generate($members, []);
        
        $this->assert($res['success'] === true, "Should be successful");
        $this->assert(isset($res['cars']) && count($res['cars']) === 1, "Should use 1 car");
        $this->assert(isset($res['walk']) && count($res['walk']) > 0, "Some people should walk due to lacking drivers");
    }

    public function testNoDriversAtAll() {
        $members = [
            ['id' => '1', 'name' => 'A1', 'family_id' => '1', 'gender' => 'M', 'is_driver' => '0', 'participation_count' => '0'],
            ['id' => '2', 'name' => 'A2', 'family_id' => '1', 'gender' => 'F', 'is_driver' => '0', 'participation_count' => '0'],
        ];
        
        $pa = new PairingAlgorithm();
        $res = $pa->generate($members, []);
        
        $this->assert($res['success'] === true, "Should be successful even with NO drivers");
        $this->assert(empty($res['cars']), "Should use 0 cars");
        $this->assert(isset($res['walk']) && count($res['walk']) === 2, "Everyone walks");
    }

    // モード別テスト用: 家族4組(2人x2, 3人, 4人, 1人...)を含む名簿
    private function modeMembers() {
        $rows = [
            // [id, family, gender, driver]
            [1, 'A', 'M', 1], [2, 'A', 'F', 0], [3, 'A', 'F', 0],
            [4, 'B', 'M', 1], [5, 'B', 'F', 0],
            [6, 'C', 'M', 1], [7, 'C', 'F', 0], [8, 'C', 'M', 0], [9, 'C', 'F', 0],
            [10, 'D', 'M', 0], [11, 'E', 'F', 1], [12, 'F', 'M', 0],
            [13, 'G', 'F', 0], [14, 'G', 'M', 0], [15, 'H', 'F', 0],
            [16, 'I', 'M', 1], [17, 'J', 'F', 0], [18, 'K', 'M', 0],
            [19, 'L', 'F', 0], [20, 'M', 'M', 0],
        ];
        $members = [];
        foreach ($rows as $r) {
            $members[] = ['id' => (string)$r[0], 'name' => 'P' . $r[0], 'family_id' => $r[1], 'gender' => $r[2], 'is_driver' => (string)$r[3], 'participation_count' => '0'];
        }
        return $members;
    }

    // 全モード共通の絶対条件: 全員が1か所にいる / 定員4人 / 各車ちょうど1人以上ドライバー / 車数が最小 / 人数差は1以内
    private function assertCommonRules($res, $members, $mode) {
        $this->assert(isset($res['success']) && $res['success'] === true, "$mode: 成功すること");
        $seen = [];
        foreach ($res['cars'] as $car) {
            $this->assert(count($car) <= 4, "$mode: 定員は4人まで");
            $drivers = 0;
            foreach ($car as $p) {
                $seen[$p['id']] = true;
                if ($p['is_driver'] === '1') $drivers++;
            }
            $this->assert($drivers >= 1, "$mode: 各車にドライバーが必要");
        }
        foreach ($res['walk'] as $p) $seen[$p['id']] = true;
        $this->assert(count($seen) === count($members), "$mode: 全員が配置されること");
        $this->assert(count($res['cars']) === (int)ceil(count($members) / 4), "$mode: 車の数が最小であること");
        $sizes = array_map('count', $res['cars']);
        $this->assert(max($sizes) - min($sizes) <= 1, "$mode: 乗車人数が不均等でないこと");
    }

    public function testModesListAndNormalize() {
        $modes = PairingAlgorithm::getModes();
        $this->assert(array_keys($modes) === ['family', 'driver', 'random'], 'モードは family/driver/random の順');
        $this->assert(PairingAlgorithm::normalizeMode('random') === 'random', 'random はそのまま');
        $this->assert(PairingAlgorithm::normalizeMode('bogus') === 'family', '不正値は family にする');
        $this->assert(PairingAlgorithm::normalizeMode(null) === 'family', 'null は family にする');
    }

    public function testAllModesSatisfyCommonRules() {
        $members = $this->modeMembers();
        foreach (array_keys(PairingAlgorithm::getModes()) as $mode) {
            for ($i = 0; $i < ($mode === 'family' ? 1 : 5); $i++) {
                $res = (new PairingAlgorithm())->generate($members, [], $mode);
                $this->assertCommonRules($res, $members, $mode);
            }
        }
    }

    public function testFamilyModeKeepsFamiliesTogether() {
        $members = $this->modeMembers();
        for ($i = 0; $i < 1; $i++) {
            $res = (new PairingAlgorithm())->generate($members, [], 'family');
            $carOf = [];
            foreach ($res['cars'] as $idx => $car) {
                foreach ($car as $p) $carOf[$p['family_id']][$idx] = true;
            }
            foreach ($carOf as $fid => $cars) {
                $this->assert(count($cars) === 1, "家族 $fid が複数の車に分かれた");
            }
        }
    }

    public function testDefaultModeIsFamily() {
        $members = $this->modeMembers();
        $res = (new PairingAlgorithm())->generate($members, []);
        $carOf = [];
        foreach ($res['cars'] as $idx => $car) {
            foreach ($car as $p) $carOf[$p['family_id']][$idx] = true;
        }
        foreach ($carOf as $fid => $cars) {
            $this->assert(count($cars) === 1, "mode省略時は家族を一緒にする ($fid)");
        }
    }

    public function testNonFamilyModesCanSplitFamilies() {
        // 5人家族(1台に乗り切らない)は家族を一緒にできない → 分けられるモードでのみ成功する
        $members = [];
        for ($i = 1; $i <= 5; $i++) {
            $members[] = ['id' => "f$i", 'name' => "F$i", 'family_id' => 'BIG', 'gender' => $i % 2 ? 'M' : 'F', 'is_driver' => $i <= 2 ? '1' : '0', 'participation_count' => '0'];
        }
        $family = (new PairingAlgorithm())->generate($members, [], 'family');
        $this->assert(empty($family['cars']) || !empty($family['walk']) || isset($family['error']), '家族モードでは5人家族は1台に収まらない');
        foreach (['driver', 'random'] as $mode) {
            $res = (new PairingAlgorithm())->generate($members, [], $mode);
            $this->assertCommonRules($res, $members, $mode);
        }
    }

    public function testDriverModeAvoidsHistory() {
        // a,b がドライバー(2台・3人ずつ)。前回 [a,c,d] [b,e,f] だった。
        // 同じ組み合わせに戻ると重複は6組、最良でも重複は2組(スコア2)。ドライバー中心なら毎回最良を選ぶ。
        $members = [];
        foreach ([['a', 1], ['b', 1], ['c', 0], ['d', 0], ['e', 0], ['f', 0]] as $r) {
            $members[] = ['id' => $r[0], 'name' => strtoupper($r[0]), 'family_id' => $r[0], 'gender' => 'M', 'is_driver' => (string)$r[1], 'participation_count' => '0'];
        }
        $byId = [];
        foreach ($members as $m) $byId[$m['id']] = $m;
        $history = [['cars' => [[$byId['a'], $byId['c'], $byId['d']], [$byId['b'], $byId['e'], $byId['f']]], 'walk' => []]];
        for ($i = 0; $i < 10; $i++) {
            $res = (new PairingAlgorithm())->generate($members, $history, 'driver');
            $this->assert(count($res['cars']) === 2, '2台になること');
            $this->assert($res['score'] === 2, '履歴の重複が最小(スコア2)になること: score=' . $res['score']);
        }
    }

    public function testRandomModeIgnoresHistory() {
        $members = $this->modeMembers();
        $history = [['cars' => [$members], 'walk' => []]]; // 全員が同乗した履歴
        $res = (new PairingAlgorithm())->generate($members, $history, 'random');
        $this->assert($res['score'] === (new PairingAlgorithm())->generate($members, [], 'random')['score'], '完全ランダムのスコアは履歴に影響されない');
    }

    public function testNonFamilyModesWithWalkAndSpeed() {
        $members = $this->modeMembers();
        foreach ($members as &$m) $m['is_driver'] = ($m['id'] === '1' || $m['id'] === '11') ? '1' : '0';
        unset($m);
        foreach (['driver', 'random'] as $mode) {
            $start = microtime(true);
            $res = (new PairingAlgorithm())->generate($members, [], $mode);
            $elapsed = microtime(true) - $start;
            $this->assert($res['success'] === true, "$mode: ドライバー不足でも成功する");
            $this->assert(count($res['cars']) === 2, "$mode: ドライバー数の車に絞られる");
            $this->assert(count($res['walk']) === 20 - 8, "$mode: 乗れない人は徒歩になる");
            $this->assert($elapsed < 5, "$mode: 5秒以内に終わること (" . round($elapsed, 2) . "秒)");
        }
    }
}
