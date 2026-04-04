<?php
require_once __DIR__ . '/../logic/WorkspaceManager.php';
require_once __DIR__ . '/../logic/CsvManager.php';
require_once __DIR__ . '/../logic/HistoryManager.php';

class WorkspaceTest {
    private $testDataDir;

    public function __construct() {
        $this->testDataDir = __DIR__ . '/workspace_test_data';
    }

    private function assert($condition, $message) {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    private function setup() {
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir);
        }
    }

    private function cleanup($dir = null) {
        if ($dir === null) $dir = $this->testDataDir;
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->cleanup("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testWorkspaceCRUD() {
        $this->setup();
        try {
            $wm = new WorkspaceManager($this->testDataDir);
            
            // 1. Initial State
            $workspaces = $wm->getWorkspaces();
            $this->assert(count($workspaces) === 1, "Should have 1 default workspace");
            $this->assert($workspaces[0]['id'] === 'default', "First WS should be default");

            // 2. Add Workspace
            $id = $wm->addWorkspace("Test Group");
            $workspaces = $wm->getWorkspaces();
            $this->assert(count($workspaces) === 2, "Should have 2 workspaces");
            $this->assert(is_dir($this->testDataDir . '/' . $id), "Directory for new WS should exist");

            // 3. Get paths
            $paths = $wm->getWorkspacePaths($id);
            $this->assert($paths['list'] === $this->testDataDir . '/' . $id . '/list.csv', "CSV path mismatch");
            
            // 4. Data Separation
            $csvDefault = new CsvManager($this->testDataDir . '/list.csv');
            $csvNew = new CsvManager($paths['list']);
            
            $csvDefault->add(['name' => 'Default Name', 'furigana' => '', 'family_id' => 'D', 'gender' => 'M', 'is_driver' => '0']);
            $csvNew->add(['name' => 'New Group Name', 'furigana' => '', 'family_id' => 'N', 'gender' => 'F', 'is_driver' => '1']);
            
            $this->assert(count($csvDefault->getAll()) === 1, "Default CSV should have 1");
            $this->assert(count($csvNew->getAll()) === 1, "New CSV should have 1");

            // 5. Update Workspace
            $wm->updateWorkspace($id, "Updated Group Name");
            $this->assert($wm->getWorkspaceName($id) === "Updated Group Name", "Name update failed");

            // 6. Delete Workspace
            $wm->deleteWorkspace($id);
            $workspaces = $wm->getWorkspaces();
            $this->assert(count($workspaces) === 1, "Should be back to 1 workspace");
            $this->assert(!is_dir($this->testDataDir . '/' . $id), "Directory should be deleted");

        } finally {
            $this->cleanup();
        }
    }
}
