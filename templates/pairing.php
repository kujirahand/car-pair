<?php
require_once __DIR__ . '/../logic/GroupLabel.php';
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

    <p class="pairing-help">名前を移動先のグループにドラッグしてください。別の名前の上にドロップしても、そのグループへ移動します。名前をタップしてから移動先をタップしても操作できます。手動調整では家族を分けられます。車グループへの追加人数に上限はありません。空グループに順にメンバーを追加し、決定までに各車へドライバーを配置してください。空のままのグループは保存されません。</p>
    <button type="button" id="add-pairing-group" class="btn btn-outline mb-4">＋ 空グループを追加</button>
    <p class="pairing-help">各車の［決定］でメンバーを固定すると、組合せをやり直してもその車は変わりません。［固定解除］で戻せます。</p>
    <p id="pairing-edit-message" role="status" aria-live="polite"></p>
    <div class="cars-grid" id="pairing-editor">
        <?php foreach ($result['cars'] as $carIndex => $car): ?>
        <div class="car-card" data-group="<?= $carIndex ?>" tabindex="0" aria-label="<?= GroupLabel::car($carIndex) ?>に移動">
            <div class="car-header">
                <h3>🚗 <?= GroupLabel::car($carIndex) ?></h3>
                <span class="badge"><?= count($car) ?>人</span>
            </div>
            <ul class="passenger-list">
                <?php foreach ($car as $p): ?>
                <li class="<?= $p['is_driver'] === '1' ? 'is-driver' : 'is-passenger' ?>" data-member-id="<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" draggable="true" tabindex="0" role="button" aria-label="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>を選択・移動" aria-pressed="false">
                    <div class="passenger-info">
                        <strong class="<?= $p['gender'] === 'M' ? 'man' : 'woman' ?>"><?= htmlspecialchars($p['name']) ?></strong>
                        <?php include __DIR__ . '/family_badge.php'; ?>
                    </div>
                    <span class="driver-icon" title="<?= $p['is_driver'] === '1' ? 'ドライバー' : '乗客' ?>"><?= $p['is_driver'] === '1' ? '🚗' : '👤' ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn btn-outline group-lock" aria-pressed="false">決定</button>
        </div>
        <?php endforeach; ?>
        
        <div class="car-card" data-group="walk" tabindex="0" aria-label="徒歩に移動" style="border-color: #94a3b8; background: #f8fafc;">
            <div class="car-header" style="background: #e2e8f0; color: #475569;">
                <h3>🚶 徒歩</h3>
                <span class="badge" style="background: #94a3b8;"><?= count($result['walk'] ?? []) ?>人</span>
            </div>
            <ul class="passenger-list">
                <?php foreach ($result['walk'] ?? [] as $p): ?>
                <li class="is-passenger" data-member-id="<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" draggable="true" tabindex="0" role="button" aria-label="<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>を選択・移動" aria-pressed="false">
                    <div class="passenger-info">
                        <strong class="<?= $p['gender'] === 'M' ? 'man' : 'woman' ?>"><?= htmlspecialchars($p['name']) ?></strong>
                        <?php include __DIR__ . '/family_badge.php'; ?>
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
        <form action="?action=pairing" method="post" class="inline-block">
            <input type="hidden" id="pairing-result" name="pairing_result" value="<?= htmlspecialchars(json_encode(['cars' => $result['cars'], 'walk' => $result['walk'] ?? [], 'locked' => $result['locked'] ?? []])) ?>">
            <button type="submit" name="retry" class="btn btn-outline btn-lg mr-3">🔄 組合せをやり直す</button>
            <button type="submit" name="decide" class="btn btn-success btn-lg shadow-hover">✅ この組合せで決定</button>
        </form>
    </div>
    <script src="public/pairing-editor.js?v=<?= filemtime(__DIR__ . '/../public/pairing-editor.js') ?>"></script>

<?php endif; ?>
