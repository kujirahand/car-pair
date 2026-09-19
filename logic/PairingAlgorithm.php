<?php

class PairingAlgorithm {
    const MODE_FAMILY = 'family';
    const MODE_DRIVER = 'driver';
    const MODE_RANDOM = 'random';

    /**
     * 選択できるアルゴリズム(モード)の一覧: [モードID => 表示名]
     * - family: 家族を必ず同じ車にして、履歴・人数の均等さを考慮する(標準)
     * - driver: ドライバーを軸に車を作り、家族は分けてもよい。履歴・人数の均等さは考慮する
     * - random: ドライバー必須・定員・人数の均等さだけを守り、家族・履歴は考慮せずランダムに決める
     */
    public static function getModes() {
        return [
            self::MODE_FAMILY => '家族を一緒に',
            self::MODE_DRIVER => 'ドライバー中心',
            self::MODE_RANDOM => '完全ランダム',
        ];
    }

    public static function normalizeMode($mode) {
        return is_string($mode) && isset(self::getModes()[$mode]) ? $mode : self::MODE_FAMILY;
    }

    public function generate($members, $history, $mode = self::MODE_FAMILY) {
        $mode = self::normalizeMode($mode);
        $families = [];
        $totalPeople = count($members);
        $totalDrivers = 0;
        
        foreach ($members as $m) {
            $fid = $m['family_id'];
            if (!isset($families[$fid])) {
                $families[$fid] = [];
            }
            $families[$fid][] = $m;
            if ($m['is_driver'] == '1') {
                $totalDrivers++;
            }
        }
        
        // Collect valid configs
        $validConfigs = [];

        if ($mode !== self::MODE_FAMILY) {
            // 家族の制約がないので、バックトラックせず直接組み立てる(人数が多くても速い)
            $validConfigs = $this->buildIndividualConfigs($members, 200);
        } else {
            $familyBlocks = array_values($families);

            $minCars = (int)ceil($totalPeople / 4);
            $maxCars = min($totalDrivers, $totalPeople); // bounded by drivers since each needs 1

            // Try combinations of NumCars and AllowMultipleDrivers
            for ($iter = 0; $iter < 100; $iter++) {
                shuffle($familyBlocks); // Randomize
            
                // We search over possible K cars. If totalDrivers == 0, we only try K=0 (all walk).
                $kStart = ($totalDrivers > 0) ? 1 : 0;
                $kEnd = max(0, $maxCars);
            
                for ($k = $kStart; $k <= $kEnd; $k++) {
                    $strictModePossible = ($totalDrivers <= $k);
                
                    $found = false;
                    if ($strictModePossible && $k > 0) {
                        $res = $this->attemptPartition($familyBlocks, $k, false);
                        if ($res !== false) {
                            $validConfigs[] = $res;
                            $found = true;
                        }
                    }
                
                    // Fallback to allowing multiple drivers or K=0
                    if (!$found) {
                        $res = $this->attemptPartition($familyBlocks, $k, true);
                        if ($res !== false) {
                            $validConfigs[] = $res;
                            $found = true;
                        }
                    }
                }
            }
        }

        if (empty($validConfigs)) {
            return ['error' => '条件を満たす乗りあわせが見つかりませんでした。家族の人数やドライバーの数を確認してください。'];
        }

        // 1. Minimize walk people count
        $minWalkPeople = PHP_INT_MAX;
        foreach ($validConfigs as $config) {
            $walkPeople = count($config['walk']);
            $minWalkPeople = min($minWalkPeople, $walkPeople);
        }
        
        $filtered1 = [];
        foreach ($validConfigs as $config) {
            if (count($config['walk']) === $minWalkPeople) {
                $filtered1[] = $config;
            }
        }

        // 2. Minimize number of cars
        $actualMinCars = PHP_INT_MAX;
        foreach ($filtered1 as $config) {
            $actualMinCars = min($actualMinCars, count($config['cars']));
        }
        
        $filteredConfigs = [];
        foreach ($filtered1 as $config) {
            if (count($config['cars']) === $actualMinCars) {
                $filteredConfigs[] = $config;
            }
        }

        // Score configs based on history & unformity
        $bestConfigs = [];
        $bestScore = PHP_INT_MAX;

        foreach ($filteredConfigs as $config) {
            $score = $this->calculateHistoryScore($config, $history, $mode);
            if ($score < $bestScore) {
                $bestScore = $score;
                $bestConfigs = [$config];
            } elseif ($score == $bestScore) {
                // To avoid duplicate identical configs in the array, we serialize and check
                $hash = $this->hashConfig($config);
                $isDuplicate = false;
                foreach ($bestConfigs as $bc) {
                    if ($this->hashConfig($bc) === $hash) {
                        $isDuplicate = true;
                        break;
                    }
                }
                if (!$isDuplicate) {
                    $bestConfigs[] = $config;
                }
            }
        }

        // Pick one randomly from the best to allow "Regenerate" to give different good options
        $chosen = $bestConfigs[array_rand($bestConfigs)];

        return [
            'success' => true,
            'cars' => $chosen['cars'],
            'walk' => $chosen['walk'],
            'score' => $bestScore
        ];
    }

    /**
     * 家族を考慮しないモード用: 1人ずつを独立した単位として、有効な配車パターンをランダムに作る。
     * 車の数は「ドライバー数」と「必要台数(人数/4切り上げ)」の小さい方。
     * 各車にドライバーを1人ずつ割り当て、残りを均等に配り、乗り切れない人は徒歩にする。
     */
    private function buildIndividualConfigs($members, $tries) {
        $drivers = [];
        $others = [];
        foreach ($members as $m) {
            if ($m['is_driver'] == '1') $drivers[] = $m; else $others[] = $m;
        }
        $total = count($members);
        $driverCount = count($drivers);

        if ($driverCount === 0) {
            return [['cars' => [], 'walk' => $members]];
        }

        $k = min($driverCount, (int)ceil($total / 4));
        $seated = min($total - $k, 3 * $k);
        $configs = [];

        for ($t = 0; $t < $tries; $t++) {
            shuffle($drivers);
            $cars = [];
            for ($i = 0; $i < $k; $i++) {
                $cars[$i] = [$drivers[$i]];
            }
            $pool = array_merge(array_slice($drivers, $k), $others);
            shuffle($pool);
            for ($i = 0; $i < $seated; $i++) {
                $cars[$i % $k][] = $pool[$i];
            }
            $walk = array_slice($pool, $seated);

            // 家族が異なる男女2人だけの車は作らない
            $valid = true;
            foreach ($cars as $car) {
                if (count($car) === 2 && $car[0]['gender'] !== $car[1]['gender'] && $car[0]['family_id'] !== $car[1]['family_id']) {
                    $valid = false;
                    break;
                }
            }
            if ($valid) {
                $configs[] = ['cars' => $cars, 'walk' => $walk];
            }
        }
        return $configs;
    }

    private function attemptPartition($blocks, $numCars, $allowMultipleDrivers) {
        $cars = array_fill(0, $numCars, []);
        $walk = [];
        return $this->backtrackPartition($cars, $walk, $blocks, 0, $allowMultipleDrivers);
    }

    private function backtrackPartition(&$cars, &$walk, $blocks, $idx, $allowMultipleDrivers) {
        if ($idx == count($blocks)) {
            if (count($cars) > 0) {
                foreach ($cars as $car) {
                    if (empty($car)) return false; 
                    $driverCount = 0;
                    $males = 0;
                    $females = 0;
                    $families_in_car = [];
                    foreach ($car as $p) {
                        if ($p['is_driver'] == '1') $driverCount++;
                        if ($p['gender'] == 'M') $males++;
                        if ($p['gender'] == 'F') $females++;
                        $families_in_car[$p['family_id']] = true;
                    }
                    if ($driverCount < 1) return false;
                    if (!$allowMultipleDrivers && $driverCount > 1) return false;
                    if (count($car) == 2 && $males == 1 && $females == 1 && count($families_in_car) == 2) {
                        return false; 
                    }
                }
            }
            return ['cars' => $cars, 'walk' => $walk];
        }
        
        $carIndices = array_keys($cars);
        shuffle($carIndices); // Randomize to find diverse solutions when counts are same
        usort($carIndices, function($a, $b) use ($cars) {
            return count($cars[$a]) <=> count($cars[$b]);
        });
        
        $triedEmpty = false;
        foreach ($carIndices as $c) {
            $originalCar = $cars[$c];
            
            if (empty($originalCar)) {
                if ($triedEmpty) continue; // symmetry breaking
                $triedEmpty = true;
            }

            if (count($originalCar) + count($blocks[$idx]) <= 4) {
                $cars[$c] = array_merge($cars[$c], $blocks[$idx]);
                
                $res = $this->backtrackPartition($cars, $walk, $blocks, $idx + 1, $allowMultipleDrivers);
                if ($res !== false) return $res;
                
                $cars[$c] = $originalCar;
            }
        }
        
        // Try assigning to walk group
        $originalWalk = $walk;
        $walk = array_merge($walk, $blocks[$idx]);
        $res = $this->backtrackPartition($cars, $walk, $blocks, $idx + 1, $allowMultipleDrivers);
        if ($res !== false) return $res;
        $walk = $originalWalk;

        return false;
    }

    private function calculateHistoryScore($config, $history, $mode = self::MODE_FAMILY) {
        $score = 0;
        $pastPairs = [];
        // 完全ランダムでは履歴を見ない
        foreach (($mode === self::MODE_RANDOM ? [] : $history) as $h) {
            $groups = [];
            if (isset($h['cars'])) {
                foreach ($h['cars'] as $c) $groups[] = $c;
            }
            if (isset($h['walk'])) {
                $groups[] = $h['walk'];
            }
            foreach ($groups as $carIds) {
                $len = count($carIds);
                for ($i = 0; $i < $len; $i++) {
                    for ($j = $i + 1; $j < $len; $j++) {
                        $u = $carIds[$i]['id'];
                        $v = $carIds[$j]['id'];
                        if (strcmp($u, $v) > 0) { $temp = $u; $u = $v; $v = $temp; }
                        $key = "$u-$v";
                        if (!isset($pastPairs[$key])) $pastPairs[$key] = 0;
                        $pastPairs[$key]++;
                    }
                }
            }
        }
        
        $allGroups = $config['cars'];
        if (!empty($config['walk'])) {
            $allGroups[] = $config['walk'];
        }

        $sizes = [];
        foreach ($allGroups as $is_walk => $car) {
            $len = count($car);
            
            // Only car size counts towards size equality penalty (ignore walk group size)
            // Or maybe we ignore walk group for size variance? Yes.
            if ($is_walk !== count($config['cars'])) { // actually `is_walk` here is just key, wait this is wrong
                $sizes[] = $len;
            }
            $familyIds = [];
            
            for ($i = 0; $i < $len; $i++) {
                $familyIds[$car[$i]['family_id']] = true;
                
                for ($j = $i + 1; $j < $len; $j++) {
                    $u = $car[$i]['id'];
                    $v = $car[$j]['id'];
                    if (strcmp($u, $v) > 0) { $temp = $u; $u = $v; $v = $temp; }
                    $key = "$u-$v";
                    if (isset($pastPairs[$key])) {
                        $score += $pastPairs[$key];
                    }
                }
            }
            
            // ペナルティ: 1人だけ、または、その家族だけの組
            if ($mode !== self::MODE_RANDOM && count($familyIds) === 1) {
                $score += 50;
            }
        }

        // 乗車人数が不均等にならないように配慮 (人数の差分に強いペナルティ)
        if (!empty($sizes)) {
            $diff = max($sizes) - min($sizes);
            $score += ($diff * 200); 
            
            // 分散もペナルティに加えて、少しの違いも平準化するよう配慮
            $mean = array_sum($sizes) / count($sizes);
            $variance = 0;
            foreach ($sizes as $s) {
                $variance += pow($s - $mean, 2);
            }
            $score += ($variance * 100);
        }
        
        // 歩く人のペナルティ (歩く人がいると少しスコア悪化)
        $score += count($config['walk']) * 1000;
        
        return $score;
    }

    private function hashConfig($config) {
        $carHashes = [];
        $allGroups = $config['cars'];
        if (!empty($config['walk'])) {
            $allGroups[] = $config['walk'];
        }
        foreach ($allGroups as $car) {
            $ids = array_map(function($p) { return $p['id']; }, $car);
            sort($ids);
            $carHashes[] = implode(',', $ids);
        }
        sort($carHashes);
        return implode('|', $carHashes);
    }
}
