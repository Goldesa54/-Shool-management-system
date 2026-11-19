<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM communication ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($method === 'POST') {
  $type = $input['type'] ?? '';
  $target = $input['target'] ?? '';
  $message = $input['message'] ?? '';
  $date = $input['date'] ?? date('c');
  if (!$message) { http_response_code(400); echo json_encode(['error'=>'Missing message']); exit; }
  try {
    $stmt = $pdo->prepare('INSERT INTO communication (date,type,target,message) VALUES (:d,:t,:tg,:m)');
    $stmt->execute([':d'=>$date,':t'=>$type,':tg'=>$target,':m'=>$message]);
    echo json_encode(['success'=>true]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
  }
  exit;
}

if ($method === 'DELETE') {
  // Clear all
  $pdo->exec('DELETE FROM communication');
  echo json_encode(['success'=>true]);
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
