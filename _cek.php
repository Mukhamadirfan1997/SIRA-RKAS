<?php
$db = new PDO('sqlite:D:/aplikasi sekolah/New folder/sira-rkas/database/database.sqlite');
echo 'jobs: ' . $db->query('SELECT count(*) FROM jobs')->fetchColumn() . PHP_EOL;
echo 'failed: ' . $db->query('SELECT count(*) FROM failed_jobs')->fetchColumn() . PHP_EOL;
echo 'rkas_item: ' . $db->query('SELECT count(*) FROM rkas_item')->fetchColumn() . PHP_EOL;
$log = $db->query('SELECT * FROM import_log ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($log) echo 'log: ' . json_encode($log, JSON_PRETTY_PRINT) . PHP_EOL;
