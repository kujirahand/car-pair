<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 2rem;">
    <h1 class="page-title" style="margin-bottom: 0;">📝 テキストから一括追加</h1>
    <a href="?action=select_members" class="btn btn-outline" style="border-color: var(--border); background: #fff;">戻る</a>
</div>

<div class="card form-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p style="margin-bottom: 20px; color: var(--text-muted); padding: 10px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary);">
        参加者のニックネームやふりがなを、1行に1人ずつ貼り付けてください。<br>
        既存の名簿（名前 / ふりがな / ニックネーム）と自動で照合し、まとめて選択します。
    </p>

    <form action="?action=select_by_textbox" method="post" class="stack">
        <div class="form-group">
            <label for="member_text">参加者リスト（複数行OK）</label>
            <textarea name="member_text" id="member_text" class="form-control" rows="12" style="font-family: monospace; font-size: 1.05em; line-height: 1.6;" placeholder="(例)&#10たろー&#10はなちゃん&#10やまだたろう"><?= htmlspecialchars($inputText ?? '') ?></textarea>
        </div>

        <div class="form-actions mt-4" style="text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg pulse-hover" style="min-width: 220px;">
                この内容でメンバーを選択する ✅
            </button>
        </div>
    </form>
</div>
