<?php

/**
 * 名前・ふりがな・ニックネームの文字列リストを名簿と照合して、メンバーIDを返す
 */
class MemberMatcher {
    /**
     * 文字列の配列を名簿と照合し、該当するメンバーのIDリストを返す
     * 
     * @param array $extractedNames 抽出した名前のリスト
     * @param array $members 全名簿の配列
     * @return array 該当したメンバーのID配列
     */
    public function matchMembers(array $extractedNames, array $members): array {
        $matchedIds = [];
        
        foreach ($extractedNames as $exName) {
            $exLower = mb_strtolower(trim($exName));
            if (empty($exLower)) continue;
            
            // Clean common OCR noise like list numbers and honorifics
            $cleanEx = preg_replace('/^[0-9\.\s・]+|(さん|くん|ちゃん|様)$/u', '', $exLower);
            $cleanEx = trim($cleanEx);
            
            $matchedThisLine = false;
            
            // Pass 1: Exact Match (Highest precision)
            foreach ($members as $mem) {
                $nameLower = mb_strtolower($mem['name']);
                $furiLower = mb_strtolower($mem['furigana']);
                $nickLower = mb_strtolower($mem['nickname']);
                
                if ($nameLower === $exLower || $nameLower === $cleanEx ||
                    (!empty($furiLower) && ($furiLower === $exLower || $furiLower === $cleanEx)) ||
                    (!empty($nickLower) && ($nickLower === $exLower || $nickLower === $cleanEx))) {
                    $matchedIds[] = $mem['id'];
                    $matchedThisLine = true;
                }
            }
            
            if ($matchedThisLine) continue; // If we found an exact match, don't do loose matching
            
            // Pass 2: Loose Substring Match (e.g. OCR returned a sentence, or a typo)
            foreach ($members as $mem) {
                $nameLower = mb_strtolower($mem['name']);
                $furiLower = mb_strtolower($mem['furigana']);
                $nickLower = mb_strtolower($mem['nickname']);
                
                // Ignore meaningless strings for substring matching to prevent false positives
                $isMeaningful = function($str) {
                    return !empty($str) && !in_array($str, ['なし', '無し', '0', '-'], true);
                };

                $nameMatch = $isMeaningful($nameLower) && (mb_strpos($nameLower, $exLower) !== false || mb_strpos($exLower, $nameLower) !== false);
                $furiMatch = $isMeaningful($furiLower) && (mb_strpos($furiLower, $exLower) !== false || mb_strpos($exLower, $furiLower) !== false);
                $nickMatch = $isMeaningful($nickLower) && (mb_strpos($nickLower, $exLower) !== false || mb_strpos($exLower, $nickLower) !== false);

                if ($nameMatch || $furiMatch || $nickMatch) {
                    $matchedIds[] = $mem['id'];
                }
            }
        }
        
        return array_unique($matchedIds);
    }
}
