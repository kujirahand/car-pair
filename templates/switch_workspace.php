<div class="page-header">
    <h1 class="page-title">📂 ワークスペース管理</h1>
</div>

    <div class="card table-card list-card">
        <h3 class="card-title">ワークスペース一覧</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>ID</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaces as $ws): ?>
                    <tr class="<?= ($ws['id'] === $currentWorkspaceId) ? 'current-ws-row' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($ws['name']) ?></strong>
                            <?php if ($ws['id'] === $currentWorkspaceId): ?>
                            <span class="badge" style="background: var(--primary-color); color: white; margin-left: 0.5rem;">現在選択中</span>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size: 0.8rem;"><?= htmlspecialchars($ws['id']) ?></code></td>
                        <td class="action-cell">
                            <?php if ($ws['id'] !== $currentWorkspaceId): ?>
                            <form action="?action=switch_workspace" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($ws['id']) ?>">
                                <button type="submit" name="select" class="btn btn-sm btn-primary">切替</button>
                            </form>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-sm btn-outline" onclick="editWs('<?= htmlspecialchars($ws['id']) ?>', '<?= htmlspecialchars(addslashes($ws['name'])) ?>')">編集</button>
                            
                            <?php if ($ws['id'] !== 'default'): ?>
                            <form action="?action=switch_workspace" method="post" style="display:inline;" onsubmit="return confirm('本当に削除しますか？ワークスペース内のデータはすべて失われます。');">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($ws['id']) ?>">
                                <button type="submit" name="delete" class="btn btn-sm btn-danger">削除</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <br/>
    <div class="grid layout-grid">
    <div class="card edit-form-card">
        <h3 class="card-title" id="form-title">ワークスペースの追加</h3>
        <form action="?action=switch_workspace" method="post" class="stack form-inline-custom" id="ws-form">
            <input type="hidden" name="id" id="form-ws-id" value="">
            <div class="form-group">
                <label>ワークスペース名</label>
                <input type="text" name="name" class="form-control" required placeholder="例: 合宿用">
            </div>
            <div class="form-actions" style="margin-top: 1.5rem;">
                <button type="submit" name="add" id="form-submit" class="btn btn-primary">追加する</button>
                <button type="button" id="form-cancel" class="btn btn-outline" style="display: none;" onclick="resetWsForm()">キャンセル</button>
            </div>
        </form>
    </div>

</div>

<style>
.current-ws-row {
    background-color: rgba(var(--primary-rgb, 63, 81, 181), 0.05);
}
</style>

<script>
function editWs(id, name) {
    document.getElementById('form-ws-id').value = id;
    document.querySelector('input[name="name"]').value = name;
    
    // UIを編集モードに変更
    document.getElementById('form-title').innerText = 'ワークスペース編集';
    document.getElementById('form-submit').innerText = '更新する';
    document.getElementById('form-submit').name = 'edit';
    
    if (id === 'default') {
        document.querySelector('input[name="name"]').disabled = true;
        document.getElementById('form-submit').disabled = true;
        document.getElementById('form-title').innerText = 'デフォルト（編集不可）';
    } else {
        document.querySelector('input[name="name"]').disabled = false;
        document.getElementById('form-submit').disabled = false;
    }
    
    document.getElementById('form-cancel').style.display = 'inline-block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetWsForm() {
    document.getElementById('ws-form').reset();
    document.getElementById('form-ws-id').value = '';
    document.querySelector('input[name="name"]').disabled = false;
    document.getElementById('form-submit').disabled = false;
    
    // UIを新規追加モードに戻す
    document.getElementById('form-title').innerText = '新規追加 / 編集';
    document.getElementById('form-submit').innerText = '追加する';
    document.getElementById('form-submit').name = 'add';
    document.getElementById('form-cancel').style.display = 'none';
}
</script>
