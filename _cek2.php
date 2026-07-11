<?php
$db = new PDO('sqlite:D:/aplikasi sekolah/New folder/sira-rkas/database/database.sqlite');
$failed = $db->query('SELECT * FROM failed_jobs ORDER BY id DESC LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
foreach ($failed as $f) {
    $pl = json_decode($f['payload'], true);
    echo 'Job: ' . ($pl['displayName'] ?? '?') . ' | failed_at: ' . $f['failed_at'] . PHP_EOL;
    $lines = explode(PHP_EOL, $f['exception']);
    echo '  ' . $lines[0] . PHP_EOL . PHP_EOL;
}
