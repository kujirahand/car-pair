<?php
require_once __DIR__ . '/PairingAlgorithm.php';

class PairingEditor {
    // ブラウザからは配置だけを受け取り、氏名・当日のドライバー指定は名簿から復元する。
    public static function validate($data, array $members, $mode) {
        if (!is_array($data) || !isset($data['cars'], $data['walk']) ||
            !is_array($data['cars']) || !array_is_list($data['cars']) ||
            !is_array($data['walk']) || !array_is_list($data['walk'])) {
            throw new InvalidArgumentException('組合せの形式が正しくありません。');
        }
        $byId = [];
        foreach ($members as $member) $byId[(string)$member['id']] = $member;
        $seen = [];
        $familyGroups = [];
        $result = ['cars' => [], 'walk' => []];
        $groups = array_merge($data['cars'], [$data['walk']]);
        foreach ($groups as $index => $group) {
            if (!is_array($group) || !array_is_list($group)) {
                throw new InvalidArgumentException('組合せの形式が正しくありません。');
            }
            $isWalk = $index === count($data['cars']);
            $resolved = [];
            foreach ($group as $person) {
                $id = is_array($person) ? ($person['id'] ?? null) : null;
                if ((!is_string($id) && !is_int($id)) || !isset($byId[(string)$id]) || isset($seen[(string)$id])) {
                    throw new InvalidArgumentException('参加者が重複しているか、選択されていないメンバーが含まれています。');
                }
                $id = (string)$id;
                $seen[$id] = true;
                $member = $byId[$id];
                if (PairingAlgorithm::normalizeMode($mode) === PairingAlgorithm::MODE_FAMILY) {
                    $family = (string)$member['family_id'];
                    if (isset($familyGroups[$family]) && $familyGroups[$family] !== $index) {
                        throw new InvalidArgumentException('「家族を一緒に」では家族を別の組に分けられません。');
                    }
                    $familyGroups[$family] = $index;
                }
                $resolved[] = $member;
            }
            if (!$isWalk) {
                if (count($resolved) === 0 || count($resolved) > 4) {
                    throw new InvalidArgumentException('車の人数は1〜4人にしてください。');
                }
                if (!array_filter($resolved, fn($p) => $p['is_driver'] === '1')) {
                    throw new InvalidArgumentException('各車にドライバーが必要です。');
                }
                if (count($resolved) === 2 && $resolved[0]['gender'] !== $resolved[1]['gender'] &&
                    $resolved[0]['family_id'] !== $resolved[1]['family_id']) {
                    throw new InvalidArgumentException('家族が異なる男女を2人だけの車にはできません。');
                }
                usort($resolved, fn($a, $b) => ($b['is_driver'] === '1') <=> ($a['is_driver'] === '1'));
                $result['cars'][] = $resolved;
            } else {
                $result['walk'] = $resolved;
            }
        }
        if (count($seen) !== count($byId)) {
            throw new InvalidArgumentException('選択された参加者が組合せから抜けています。');
        }
        return $result;
    }
}
