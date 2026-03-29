<div class="page-header">
    <h1 class="page-title">📝 名簿マスター編集</h1>
</div>

<div class="grid layout-grid">
    <div class="card edit-form-card">
        <h3 class="card-title" id="form-title">新規追加 / 編集</h3>
        <form action="?action=edit_list" method="post" class="stack form-inline-custom" id="edit-form">
            <input type="hidden" name="id" id="form-id" value="">
            <div class="form-group">
                <label>名前</label>
                <input type="text" name="name" class="form-control" required placeholder="例: 山田太郎">
            </div>
            <div class="form-group">
                <label>ふりがな</label>
                <input type="text" name="furigana" class="form-control" required placeholder="例: やまだたろう">
            </div>
            <div class="form-group">
                <label>家族ID</label>
                <input type="text" name="family_id" class="form-control" required placeholder="例: 田中家">
            </div>
            <div class="form-group">
                <label>性別</label>
                <select name="gender" class="form-control">
                    <option value="M">男性</option>
                    <option value="F">女性</option>
                </select>
            </div>
            <div class="form-group checkbox-group">
                <label>
                    <input type="checkbox" name="is_driver" id="form-driver" value="1">
                    ドライバー
                </label>
            </div>
            <div class="form-group">
                <label>ニックネーム</label>
                <input type="text" name="nickname" id="form-nickname" class="form-control" placeholder="例: あだ名やZOOM名など">
            </div>
            <div class="form-group">
                <label>備考</label>
                <input type="text" name="notes" id="form-notes" class="form-control" placeholder="例: 午後から参加など">
            </div>
            <div class="form-actions" style="margin-top: 1.5rem;">
                <button type="submit" name="add" id="form-submit" class="btn btn-primary">登録する</button>
                <button type="button" id="form-cancel" class="btn btn-outline" style="display: none;" onclick="resetForm()">キャンセル</button>
            </div>
        </form>
    </div>

    <div class="card table-card list-card">
        <h3 class="card-title">現在の名簿 <span class="badge"><?= count($members) ?> 名</span></h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>ふりがな</th>
                        <th>家族ID</th>
                        <th>性別</th>
                        <th>ドライバー</th>
                        <th>ニックネーム</th>
                        <th>備考</th>
                        <th>参加回数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                    <tr><td colspan="6" class="text-center text-muted">データがありません。</td></tr>
                    <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['name']) ?></td>
                        <td><?= htmlspecialchars($m['furigana']) ?></td>
                        <td><span class="family-tag"><?= htmlspecialchars($m['family_id']) ?></span></td>
                        <td><?= $m['gender'] === 'M' ? '<span class="gender-m">男性</span>' : '<span class="gender-f">女性</span>' ?></td>
                        <td><?= $m['is_driver'] === '1' ? '🚗' : '-' ?></td>
                        <td><?= htmlspecialchars($m['nickname'] ?? '') ?></td>
                        <td><?= htmlspecialchars($m['notes'] ?? '') ?></td>
                        <td><?= htmlspecialchars($m['participation_count']) ?> 回</td>
                        <td class="action-cell">
                            <button type="button" class="btn btn-sm btn-outline" onclick="editMember('<?= htmlspecialchars($m['id']) ?>', '<?= htmlspecialchars(addslashes($m['name'])) ?>', '<?= htmlspecialchars(addslashes($m['furigana'])) ?>', '<?= htmlspecialchars(addslashes($m['family_id'])) ?>', '<?= $m['gender'] ?>', '<?= $m['is_driver'] ?>', '<?= htmlspecialchars(addslashes($m['nickname'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($m['notes'] ?? '')) ?>')">編集</button>
                            <form action="?action=edit_list" method="post" style="display:inline;" onsubmit="return confirm('本当に削除しますか？');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($m['id']) ?>">
                                <button type="submit" name="delete" class="btn btn-sm btn-danger">削除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="?action=export_csv" class="btn btn-outline">📥 CSVエクスポート</a>
        </div>
    </div>

    <!-- CSV Import Card -->
    <div class="card import-card" style="grid-column: 1 / -1; margin-top: 1rem;">
        <h3 class="card-title">📥 CSVインポート</h3>
        <p class="text-muted" style="margin-bottom: 1rem; font-size: 0.9rem;">
            エクスポートしたCSVと同じ形式のファイルを選択してください。
        </p>
        <form action="?action=edit_list" method="post" enctype="multipart/form-data" class="stack form-inline-custom">
            <div class="form-group">
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <div class="form-actions" style="margin-top: 1rem; display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" name="import_replace" class="btn btn-danger" onclick="return confirm('現在の名簿をすべて削除して、CSVの内容に差し替えます。よろしいですか？');">CSVインポート(全部差し替え)</button>
                <button type="submit" name="import_delta" class="btn btn-primary">CSVインポート(差分追加)</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMember(id, name, furigana, family_id, gender, is_driver, nickname, notes) {
    document.getElementById('form-id').value = id;
    document.querySelector('input[name="name"]').value = name;
    document.querySelector('input[name="furigana"]').value = furigana;
    document.querySelector('input[name="family_id"]').value = family_id;
    document.querySelector('select[name="gender"]').value = gender;
    document.getElementById('form-driver').checked = (is_driver === '1');
    document.getElementById('form-nickname').value = nickname;
    document.getElementById('form-notes').value = notes;
    
    // UIを編集モードに変更
    document.getElementById('form-title').innerText = 'メンバー編集';
    document.getElementById('form-submit').innerText = '更新する';
    document.getElementById('form-submit').name = 'edit';
    document.getElementById('form-cancel').style.display = 'inline-block';
    
    // スムーズスクロールでフォームへ移動
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('edit-form').reset();
    document.getElementById('form-id').value = '';
    
    // UIを新規追加モードに戻す
    document.getElementById('form-title').innerText = '新規追加 / 編集';
    document.getElementById('form-submit').innerText = '登録する';
    document.getElementById('form-submit').name = 'add';
    document.getElementById('form-cancel').style.display = 'none';
}
</script>
