<?php
$file = file(__DIR__ . '/../data/ws_8e5d4f80/list.csv');
foreach ($file as $line) {
    if (strpos($line, 'な') !== false || strpos($line, 'し') !== false) {
        echo $line;
    }
}
