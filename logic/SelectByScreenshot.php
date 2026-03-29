<?php

class SelectByScreenshot {
    
    /**
     * 画像をOpenAI APIに送信し、参加者の名前リスト（文字列配列）として抽出する
     * 
     * @param string $tmpPath アップロードされた画像の一時パス
     * @return array 抽出された名前の配列
     * @throws Exception エラー発生時にメッセージをスローする
     */
    public function extractNamesFromImage(string $tmpPath): array {
        $configFile = __DIR__ . '/../data/config.php';
        if (!file_exists($configFile)) {
            throw new Exception('設定ファイル (data/config.php) が見つかりません。連携機能を利用するには設定を作成してください。');
        }
        
        // 変数スコープ内で読み込む
        require $configFile;
        if (empty($OPENAI_API_KEY)) {
            throw new Exception('OpenAI APIキーが設定されていません。');
        }
        
        $imageData = base64_encode(file_get_contents($tmpPath));
        $mimeType = mime_content_type($tmpPath);
        
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-5.4-nano',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'この画像から参加者リスト（名前、ニックネーム）を読み取り、リストを抽出してください。JSON形式で返してください。キー名は "members" で、配列の要素として抽出した個々の名前（文字列）を含めてください。例: {"members": ["山田", "けんちゃん"]}'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}"
                            ]
                        ]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object']
        ];
        
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\nAuthorization: Bearer {$OPENAI_API_KEY}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            throw new Exception('API呼び出しに失敗しました。');
        }
        
        $json = json_decode($result, true);
        if (isset($json['error'])) {
            throw new Exception('APIエラー: ' . ($json['error']['message'] ?? '不明なエラー'));
        }
        
        $content = $json['choices'][0]['message']['content'];
        $aiResult = json_decode($content, true);
        
        return $aiResult['members'] ?? [];
    }
    
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
            
            foreach ($members as $mem) {
                $nameLower = mb_strtolower($mem['name']);
                $furiLower = mb_strtolower($mem['furigana']);
                $nickLower = mb_strtolower($mem['nickname']);
                
                if ($nameLower === $exLower || 
                    $furiLower === $exLower || 
                    $nickLower === $exLower ||
                    strpos($nameLower, $exLower) !== false || 
                    strpos($exLower, $nameLower) !== false ||
                    (!empty($nickLower) && strpos($exLower, $nickLower) !== false)) {
                    $matchedIds[] = $mem['id'];
                    // break; マッチするメンバーがいれば全員を選択する
                }
            }
        }
        
        return array_unique($matchedIds);
    }
}
