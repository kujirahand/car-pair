<?php
// 先に本文を描画して、各テンプレートが $pageMenuItems(メニューへの追加項目)を設定できるようにする
$pageMenuItems = [];
ob_start();
if (isset($contentView) && file_exists(__DIR__ . '/' . $contentView)) {
    require __DIR__ . '/' . $contentView;
}
$contentHtml = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Pairing</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <!-- Modern font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/style.css?v=<?= filemtime(__DIR__ . '/../public/style.css') ?>">
</head>
<body>
    <div class="app-container">
        <?php if ($auth->isLoggedIn()): ?>
        <nav class="navbar">
            <div class="nav-brand">
                🚗 Car Pairing
                <span class="nav-workspace-name"><?= htmlspecialchars($currentWorkspaceName) ?></span>
            </div>
            <button type="button" class="menu-toggle" id="menu-toggle" aria-label="メニュー" aria-expanded="false" aria-controls="nav-menu">☰</button>
            <ul class="nav-links" id="nav-menu" hidden>
                <?php foreach ($pageMenuItems as $item): ?>
                <li>
                    <?php if (isset($item['href'])): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                    <?php else: ?>
                    <button type="button" id="<?= htmlspecialchars($item['id']) ?>" class="menu-action <?= !empty($item['danger']) ? 'danger' : '' ?>"><?= htmlspecialchars($item['label']) ?></button>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
                <?php if ($pageMenuItems): ?><li class="menu-divider" role="separator"></li><?php endif; ?>
                <li><a href="?action=select_members" class="<?= $action === 'select_members' ? 'active' : '' ?>">配車</a></li>
                <li><a href="?action=history" class="<?= $action === 'history' ? 'active' : '' ?>">履歴</a></li>
                <li><a href="?action=edit_list" class="<?= $action === 'edit_list' ? 'active' : '' ?>">名簿</a></li>
                <li><a href="?action=switch_workspace" class="<?= $action === 'switch_workspace' ? 'active' : '' ?>">切替</a></li>
                <li><a href="?action=logout" class="logout-btn">ログアウト</a></li>
            </ul>
        </nav>
        <?php endif; ?>

        <main class="content-area">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="flash-message">
                    <?= htmlspecialchars($_SESSION['flash_message'], ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php unset($_SESSION['flash_message']); ?>
            <?php endif; ?>

            <?= $contentHtml ?>
        </main>
    </div>
    <div class="footer">
        <div class="footer-content">
            <p>Car Pairing App
                <a href="https://github.com/kujirahand/car-pair">ソースコード</a>
            </p>
        </div>
    </div>
<?php if ($auth->isLoggedIn()): ?>
    <script>
    (function () {
        const toggle = document.getElementById('menu-toggle');
        const menu = document.getElementById('nav-menu');
        const setOpen = (open) => {
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? '✕' : '☰';
        };
        toggle.addEventListener('click', (e) => { e.stopPropagation(); setOpen(menu.hidden); });
        menu.addEventListener('click', () => setOpen(false));
        document.addEventListener('click', (e) => { if (!menu.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setOpen(false); });
    })();
    </script>
<?php endif; ?>
</body>
</html>
