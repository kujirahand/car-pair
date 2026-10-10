<?php
// ドライバーを各車の先頭に表示する(同じ役割の中では元の順序を保つ)
if (!isset($result['error'])) {
    foreach ($result['cars'] as &$carMembers) {
        usort($carMembers, fn($a, $b) => ($b['is_driver'] === '1') <=> ($a['is_driver'] === '1'));
    }
    unset($carMembers);
}
?>
<div class="page-header">
    <h1 class="page-title">✨ 乗りあわせ候補</h1>
</div>

<?php if (isset($result['error'])): ?>
    <div class="card error-card">
        <div class="alert alert-danger" style="margin-bottom:0">
            <strong>エラー:</strong> <?= htmlspecialchars($result['error']) ?>
        </div>
        <div class="text-center mt-4">
            <a href="?action=select_members" class="btn btn-primary">戻って選択し直す</a>
        </div>
    </div>
<?php else: ?>
    <?php if (!empty($editError)): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($editError) ?> 候補を作り直しました。</div>
    <?php endif; ?>
    
    <div class="alert alert-info">
        <strong>組み方:</strong> <?= htmlspecialchars($pairingModeName) ?> &nbsp;
        <span id="pairing-score"><strong>履歴スコア:</strong> <?= number_format((float)$result['score'], 1, '.', '') ?> （小さいと良い）</span>
        <span class="float-right text-muted" style="float: right; font-size: 0.85em;">処理時間: <strong><?= $executionTime ?></strong> ms</span>
    </div>

    <p class="pairing-help">名前を別の名前にドラッグすると入れ替え、枠の余白にドラッグすると移動します。名前をタップしてから相手の名前や枠をタップしても操作できます。「家族を一緒に」では家族を分ける変更はできません。</p>
    <p id="pairing-edit-message" role="status" aria-live="polite"></p>
    <div class="cars-grid" id="pairing-editor" data-mode="<?= htmlspecialchars($pairingMode ?? 'family') ?>">
        <?php $carIndex = 1; ?>
        <?php foreach ($result['cars'] as $car): ?>
        <div class="car-card" data-group="<?= $carIndex - 1 ?>" tabindex="0" aria-label="車 <?= $carIndex ?>に移動">
            <div class="car-header">
                <h3>🚗 車 <?= $carIndex++ ?></h3>
                <span class="badge"><?= count($car) ?>人</span>
            </div>
            <ul class="passenger-list">
                <?php foreach ($car as $p): ?>
                <li class="<?= $p['is_driver'] === '1' ? 'is-driver' : 'is-passenger' ?>" data-member-id="<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" draggable="true" tabindex="0" role="button" aria-label="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>を選択・入れ替え" aria-pressed="false">
                    <div class="passenger-info">
                        <strong class="<?= $p['gender'] === 'M' ? 'man' : 'woman' ?>"><?= htmlspecialchars($p['name']) ?></strong>
                    </div>
                    <span class="driver-icon" title="<?= $p['is_driver'] === '1' ? 'ドライバー' : '乗客' ?>"><?= $p['is_driver'] === '1' ? '🚗' : '👤' ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
        
        <div class="car-card" data-group="walk" tabindex="0" aria-label="徒歩に移動" style="border-color: #94a3b8; background: #f8fafc;">
            <div class="car-header" style="background: #e2e8f0; color: #475569;">
                <h3>🚶 徒歩</h3>
                <span class="badge" style="background: #94a3b8;"><?= count($result['walk'] ?? []) ?>人</span>
            </div>
            <ul class="passenger-list">
                <?php foreach ($result['walk'] ?? [] as $p): ?>
                <li class="is-passenger" data-member-id="<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" draggable="true" tabindex="0" role="button" aria-label="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>を選択・入れ替え" aria-pressed="false">
                    <div class="passenger-info">
                        <strong class="<?= $p['gender'] === 'M' ? 'man' : 'woman' ?>"><?= htmlspecialchars($p['name']) ?></strong>
                    </div>
                    <span class="driver-icon" title="<?= $p['is_driver'] === '1' ? 'ドライバー' : '乗客' ?>"><?= $p['is_driver'] === '1' ? '🚗' : '👤' ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="bottom-actions mt-5 text-center">
        <a href="?action=select_members" class="btn btn-outline btn-lg mr-3">↩️ メンバーを選び直す</a>
        <a href="?action=pairing" class="btn btn-outline btn-lg mr-3">🔄 組合せをやり直す</a>
        <form action="?action=pairing" method="post" class="inline-block">
            <input type="hidden" id="pairing-result" name="pairing_result" value="<?= htmlspecialchars(json_encode(['cars' => $result['cars'], 'walk' => $result['walk'] ?? []])) ?>">
            <button type="submit" name="decide" class="btn btn-success btn-lg shadow-hover">✅ この組合せで決定</button>
        </form>
    </div>
    <script src="public/pairing-editor.js?v=<?= filemtime(__DIR__ . '/../public/pairing-editor.js') ?>"></script>

<?php endif; ?>
