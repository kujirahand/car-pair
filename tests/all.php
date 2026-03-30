<?php
// tests/all.php
$files = glob(__DIR__ . '/*Test.php');
$failed = 0;
$passed = 0;

foreach ($files as $file) {
    require_once $file;
    $className = basename($file, '.php');
    if (class_exists($className)) {
        $testClass = new $className();
        $methods = get_class_methods($testClass);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                try {
                    $testClass->$method();
                    echo "✅ $className::$method\n";
                    $passed++;
                } catch (Exception $e) {
                    echo "❌ $className::$method\n";
                    echo "   Error: " . $e->getMessage() . "\n";
                    $failed++;
                }
            }
        }
    }
}

echo "\nTests Completed.\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
exit($failed > 0 ? 1 : 0);
