<?php

class WorkspaceManager {
    private $workspaceFile;
    private $dataDir;

    public function __construct($dataDir = null) {
        if ($dataDir === null) {
            $dataDir = __DIR__ . '/../data';
        }
        $this->dataDir = rtrim($dataDir, '/');
        $this->workspaceFile = $this->dataDir . '/workspaces.json';

        // Initialize workspaces.json if not exists
        if (!file_exists($this->workspaceFile)) {
            $this->saveWorkspaces([
                ['id' => 'default', 'name' => 'デフォルト']
            ]);
        }
    }

    public function getWorkspaces() {
        if (!file_exists($this->workspaceFile)) {
            return [];
        }
        $json = file_get_contents($this->workspaceFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public function saveWorkspaces($workspaces) {
        file_put_contents($this->workspaceFile, json_encode($workspaces, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function addWorkspace($name) {
        $id = 'ws_' . bin2hex(random_bytes(4)); // generate simple id
        $workspaces = $this->getWorkspaces();
        $workspaces[] = ['id' => $id, 'name' => $name];
        $this->saveWorkspaces($workspaces);

        // Create directory
        $dir = $this->dataDir . '/' . $id;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $id;
    }

    public function updateWorkspace($id, $name) {
        if ($id === 'default') return false; // Default name shouldn't change maybe? 
        $workspaces = $this->getWorkspaces();
        $found = false;
        foreach ($workspaces as &$ws) {
            if ($ws['id'] === $id) {
                $ws['name'] = $name;
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->saveWorkspaces($workspaces);
        }
        return $found;
    }

    public function deleteWorkspace($id) {
        if ($id === 'default') return false;
        $workspaces = $this->getWorkspaces();
        $newWorkspaces = [];
        $found = false;
        foreach ($workspaces as $ws) {
            if ($ws['id'] !== $id) {
                $newWorkspaces[] = $ws;
            } else {
                $found = true;
            }
        }
        if ($found) {
            $this->saveWorkspaces($newWorkspaces);
            // Delete directory contents and directory
            $dir = $this->dataDir . '/' . $id;
            if (is_dir($dir)) {
                $this->recursiveDelete($dir);
            }
        }
        return $found;
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function getWorkspacePaths($id) {
        if ($id === 'default' || empty($id)) {
            return [
                'list' => $this->dataDir . '/list.csv',
                'history' => $this->dataDir . '/history.json'
            ];
        } else {
            $dir = $this->dataDir . '/' . $id;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            return [
                'list' => $dir . '/list.csv',
                'history' => $dir . '/history.json'
            ];
        }
    }

    public function getWorkspaceName($id) {
        $workspaces = $this->getWorkspaces();
        foreach ($workspaces as $ws) {
            if ($ws['id'] === $id) {
                return $ws['name'];
            }
        }
        return 'デフォルト';
    }
}
