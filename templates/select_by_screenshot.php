<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 2rem;">
    <h1 class="page-title" style="margin-bottom: 0;">📸 スクショから選択</h1>
    <a href="?action=select_members" class="btn btn-outline" style="border-color: var(--border); background: #fff;">戻る</a>
</div>

<div class="card form-card">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!isset($step) || $step === 'upload'): ?>

    <p style="margin-bottom: 20px; color: var(--text-muted); padding: 10px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--primary);">
        LINEなどのスクショ画像をアップロードすると、AIが画像を解析して参加者を読み取り、名簿から自動的に選択します。
        ただし、あくまでも補助機能なので、読み取り精度は100%ではありません。
        必要に応じて名簿でニックネームを指定してください。
    </p>

    <form action="?action=select_by_screenshot" method="post" enctype="multipart/form-data" class="stack" id="upload-form">
        <input type="hidden" name="step" value="upload">
        <div class="form-group">
            <label for="screenshot">📸 スクリーンショット画像を選択</label>
            <input type="file" name="screenshot" id="screenshot" class="form-control" accept="image/*" required style="padding: 1rem; border: 2px dashed var(--border); background: #fdfdfd;">
        </div>
        
        <div class="form-actions mt-4" style="text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg pulse-hover" id="submit-btn" style="width: 100%; max-width: 300px;">
                画像を解析して抽出する ✨
            </button>
        </div>
    </form>

    <script>
    document.getElementById('upload-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        // Timeout is needed so form submits before disabling button
        setTimeout(() => {
            btn.disabled = true;
            btn.innerHTML = '解析中... 少しお待ちください ⏳';
            btn.style.opacity = '0.8';
            btn.style.cursor = 'not-allowed';
        }, 10);
    });
    </script>
    
    <?php elseif ($step === 'confirm'): ?>
    
    <p style="margin-bottom: 20px; color: var(--text-muted); padding: 10px; background: #fffbeb; border-radius: 8px; border-left: 4px solid #f59e0b;">
        AIが画像から以下の参加者を抽出しました。必要に応じてテキストを直接編集して修正してください。<br>
        <small class="text-muted">（1行に1人分の名前を入力してください）</small>
    </p>

    <form action="?action=select_by_screenshot" method="post" class="stack">
        <input type="hidden" name="step" value="confirm">
        <div class="form-group">
            <label for="extracted_text">抽出されたメンバーリスト</label>
            <textarea name="extracted_text" id="extracted_text" class="form-control" rows="10" style="font-family: monospace; font-size: 1.1em; line-height: 1.8;"><?= htmlspecialchars($extractedText ?? '') ?></textarea>
        </div>
        
        <div class="form-actions mt-4 d-flex" style="display: flex; gap: 1rem; justify-content: center;">
            <a href="?action=select_by_screenshot" class="btn btn-outline" style="min-width: 120px;">画像を選び直す</a>
            <button type="submit" class="btn btn-success btn-lg pulse-hover" style="min-width: 200px;">
                この内容で選択する ✅
            </button>
        </div>
    </form>
    
    <?php endif; ?>

</div>
