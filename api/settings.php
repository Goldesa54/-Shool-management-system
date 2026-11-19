<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_ASSOC);
  $settings = [];
  foreach ($rows as $row) $settings[$row['key']] = $row['value'];
  echo json_encode($settings);
  exit;
}

if ($method === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $allowed = ['school_name', 'academic_year', 'current_term', 'currency', 'theme'];
  $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value) ON CONFLICT(key) DO UPDATE SET value = excluded.value');

  try {
    $pdo->beginTransaction();
    foreach ($allowed as $key) {
      if (array_key_exists($key, $input)) {
        $value = trim((string)$input[$key]);
        $stmt->execute([':key' => $key, ':value' => $value]);
      }
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
