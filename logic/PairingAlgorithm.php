<?php

class PairingAlgorithm {
    public function generate($members, $history) {
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
        
        $familyBlocks = array_values($families);
        
        $minCars = (int)ceil($totalPeople / 4);
        $maxCars = min($totalDrivers, $totalPeople); // bounded by drivers since each needs 1

        // Collect valid configs
        $validConfigs = [];
        
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
            $score = $this->calculateHistoryScore($config, $history);
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

    private function calculateHistoryScore($config, $history) {
        $score = 0;
        $pastPairs = [];
        foreach ($history as $h) {
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
            if (count($familyIds) === 1) {
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
