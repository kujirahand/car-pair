<?php
class HistoryManager {
    private $historyFile;

    public function __construct($historyFile = null) {
        if ($historyFile === null) {
            $historyFile = __DIR__ . '/../data/history.json';
        }
        $this->historyFile = $historyFile;
    }

    public function getHistory() {
        if (!file_exists($this->historyFile)) {
            return [];
        }
        $json = file_get_contents($this->historyFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * 保存日時で履歴を1件探す(見つからなければ null)
     */
    public function findByDate($date) {
        foreach ($this->getHistory() as $record) {
            if (($record['date'] ?? null) === $date) {
                return $record;
            }
        }
        return null;
    }

    /**
     * 履歴1件から、当時の参加者IDと、その日のドライバー指定(id => bool)を取り出す。
     * 車と徒歩の全員が対象。重複IDは除く。
     */
    public static function extractSelection($record) {
        $ids = [];
        $drivers = [];
        $groups = array_merge($record['cars'] ?? [], [$record['walk'] ?? []]);
        foreach ($groups as $group) {
            foreach ($group as $p) {
                $id = (string)($p['id'] ?? '');
                if ($id === '' || isset($drivers[$id])) {
                    continue;
                }
                $ids[] = $id;
                $drivers[$id] = ($p['is_driver'] ?? '0') === '1';
            }
        }
        return ['ids' => $ids, 'drivers' => $drivers];
    }

    public function addHistory($pairingData) {
        $history = $this->getHistory();
        $record = [
            'date' => date('Y-m-d H:i:s')
        ];
        if (isset($pairingData['cars'])) {
            $record['cars'] = $pairingData['cars'];
            if (isset($pairingData['walk'])) {
                $record['walk'] = $pairingData['walk'];
            }
        } else {
            $record['cars'] = $pairingData; // fallback
        }
        $history[] = $record;
        file_put_contents($this->historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
