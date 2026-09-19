<?php
require_once __DIR__ . '/../logic/PairingAlgorithm.php';
// レイアウト(ハンバーガーメニュー)の構成を確認する

class LayoutTest {
    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function renderLayout($action, $loggedIn = true) {
        $auth = new class($loggedIn) {
            private $in;
            public function __construct($in) { $this->in = $in; }
            public function isLoggedIn() { return $this->in; }
        };
        $currentWorkspaceName = 'デフォルト';
        $contentView = 'select_members.php';
        $members = [];
        $selectedIds = [];
        $error = '';
        $pairingMode = 'family';
        $pairingModes = PairingAlgorithm::getModes();
        ob_start();
        require __DIR__ . '/../templates/layout.php';
        return ob_get_clean();
    }

    public function testNavLinksAreInsideHamburgerMenu() {
        $html = $this->renderLayout('select_members');
        $this->assert(strpos($html, 'id="menu-toggle"') !== false, 'ハンバーガーボタンがありません');
        $toggle = strpos($html, 'id="menu-toggle"');
        $menu = strpos($html, 'id="nav-menu"');
        $this->assert($toggle < $menu, 'ボタンの後にメニューがあるべきです');
        $this->assert(preg_match('/id="nav-menu" hidden/', $html) === 1, 'メニューは初期状態で閉じているべきです');
        foreach (['action=select_members', 'action=history', 'action=edit_list', 'action=switch_workspace', 'action=logout'] as $link) {
            $pos = strpos($html, $link, $menu);
            $this->assert($pos !== false && $pos > $menu, "$link がメニュー内にありません");
        }
    }

    public function testPageSpecificItemsAppearInMenu() {
        $html = $this->renderLayout('select_members');
        $menu = strpos($html, 'id="nav-menu"');
        $menuEnd = strpos($html, '</nav>');
        foreach (['id="clear-all-btn"', 'action=select_by_textbox'] as $needle) {
            $pos = strpos($html, $needle);
            $this->assert($pos !== false && $pos > $menu && $pos < $menuEnd, "$needle がメニュー内にありません");
        }
        $this->assert(substr_count($html, 'id="clear-all-btn"') === 1, '全部クリアは1つだけであるべきです');
    }

    public function testNoMenuWhenLoggedOut() {
        $html = $this->renderLayout('login', false);
        $this->assert(strpos($html, 'id="menu-toggle"') === false, 'ログアウト時はメニューを出さないこと');
    }

    public function testBrandLinksToSelectMembers() {
        $html = $this->renderLayout('history');
        $this->assert(preg_match('/<a href="\?action=select_members" class="nav-brand">\s*🚗 Car Pairing/', $html) === 1, 'Car Pairing が参加者選択画面へのリンクになっていません');
    }
}
