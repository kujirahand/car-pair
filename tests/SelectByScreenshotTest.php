<?php
require_once __DIR__ . '/../logic/SelectByScreenshot.php';

class SelectByScreenshotTest {
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public function testMatchMembersWithNicknamesAndFurigana() {
        $logic = new SelectByScreenshot();
        $members = [
            ['id' => '1', 'name' => '山田太郎', 'furigana' => 'やまだたろう', 'family_id' => '1', 'gender' => 'M', 'is_driver' => '1', 'nickname' => 'たろー', 'notes' => '', 'participation_count' => '0'],
            ['id' => '2', 'name' => '佐藤花子', 'furigana' => 'さとうはなこ', 'family_id' => '2', 'gender' => 'F', 'is_driver' => '0', 'nickname' => 'はなちゃん', 'notes' => '', 'participation_count' => '0'],
            ['id' => '3', 'name' => '鈴木一郎', 'furigana' => 'すずきいちろう', 'family_id' => '3', 'gender' => 'M', 'is_driver' => '0', 'nickname' => 'いっくん', 'notes' => '', 'participation_count' => '0'],
        ];

        $extracted = ['はなちゃん', 'いちろう', 'やまだ'];
        $matched = $logic->matchMembers($extracted, $members);
        sort($matched);

        $this->assert(count($matched) === 3, 'All three members should be matched');
        $this->assert($matched === ['1', '2', '3'], 'Matched IDs should correspond to all members');
    }
}
