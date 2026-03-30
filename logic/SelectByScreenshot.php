<?php

class SelectByScreenshot {
    
    /**
     * 画像を圧縮・リサイズしてBase64エンコードの文字列を返す
     * ファイルサイズが2MB以上の場合、長辺1500px以下になるようリサイズする
     * 
     * @param string $tmpPath 画像ファイルパス
     * @return string Base64エンコードされた画像データ
     */
    public function getCompressedImageBase64(string $tmpPath): string {
        $mimeType = mime_content_type($tmpPath);
        $fileSize = filesize($tmpPath);
        $imageData = null;

        // ファイルサイズが2MB以上の場合、GDでリサイズ（長辺1500px以下）
        if ($fileSize >= 2 * 1024 * 1024) {
            $image = null;
            switch ($mimeType) {
                case 'image/jpeg':
                    $image = @imagecreatefromjpeg($tmpPath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($tmpPath);
                    break;
                case 'image/gif':
                    $image = @imagecreatefromgif($tmpPath);
                    break;
                case 'image/webp':
                    $image = @imagecreatefromwebp($tmpPath);
                    break;
            }

            if ($image) {
                $width = imagesx($image);
                $height = imagesy($image);
                $maxDim = 1500;
                
                $newWidth = $width;
                $newHeight = $height;

                if ($width > $maxDim || $height > $maxDim) {
                    $ratio = $width / $height;
                    if ($ratio > 1) {
                        $newWidth = $maxDim;
                        $newHeight = (int)round($maxDim / $ratio);
                    } else {
                        $newHeight = $maxDim;
                        $newWidth = (int)round($maxDim * $ratio);
                    }
                }

                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                if ($mimeType === 'image/png' || $mimeType === 'image/webp' || $mimeType === 'image/gif') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                    $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                    imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
                }

                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                ob_start();
                switch ($mimeType) {
                    case 'image/jpeg':
                        imagejpeg($newImage, null, 85);
                        break;
                    case 'image/png':
                        imagepng($newImage, null, 8);
                        break;
                    case 'image/gif':
                        imagegif($newImage, null);
                        break;
                    case 'image/webp':
                        imagewebp($newImage, null, 85);
                        break;
                }
                $imageString = ob_get_clean();
                $imageData = base64_encode($imageString);
                
                imagedestroy($newImage);
                imagedestroy($image);
            }
        }

        if ($imageData === null) {
            $imageData = base64_encode(file_get_contents($tmpPath));
        }
        
        return $imageData;
    }

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
        
        $mimeType = mime_content_type($tmpPath);
        $imageData = $this->getCompressedImageBase64($tmpPath);
        
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

                $nameMatch = $nameLower === $exLower || strpos($nameLower, $exLower) !== false || strpos($exLower, $nameLower) !== false;
                $furiMatch = (!empty($furiLower) && ($furiLower === $exLower || strpos($furiLower, $exLower) !== false || strpos($exLower, $furiLower) !== false));
                $nickMatch = (!empty($nickLower) && ($nickLower === $exLower || strpos($nickLower, $exLower) !== false || strpos($exLower, $nickLower) !== false));

                if ($nameMatch || $furiMatch || $nickMatch) {
                    $matchedIds[] = $mem['id'];
                    // break; マッチするメンバーがいれば全員を選択する
                }
            }
        }
        
        return array_unique($matchedIds);
    }
}
