<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM teachers ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($method === 'POST') {
  $name = $input['name'] ?? '';
  $subject = $input['subject'] ?? '';
  $phone = $input['phone'] ?? '';
  $email = $input['email'] ?? '';
  if (!$name || !$subject) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }
  try {
    $stmt = $pdo->prepare('INSERT INTO teachers (name,subject,phone,email) VALUES (:n,:s,:p,:e)');
    $stmt->execute([':n'=>$name,':s'=>$subject,':p'=>$phone,':e'=>$email]);
    echo json_encode(['success'=>true]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
