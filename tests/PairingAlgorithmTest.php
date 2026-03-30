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
}
