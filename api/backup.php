<?php
$database = __DIR__ . '/../data/database.sqlite';
if (!is_file($database)) {
  http_response_code(404);
  echo 'Database not found';
  exit;
}

header('Content-Type: application/vnd.sqlite3');
header('Content-Disposition: attachment; filename="school-erp-backup-' . date('Y-m-d-His') . '.sqlite"');
header('Content-Length: ' . filesize($database));
readfile($database);
