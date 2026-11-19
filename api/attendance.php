<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM attendance ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($method === 'POST') {
  // Expect array of records
  $records = $input['records'] ?? null;
  if (!is_array($records)) { http_response_code(400); echo json_encode(['error'=>'Expected records array']); exit; }
  $ins = $pdo->prepare('INSERT INTO attendance (date,adm,name,status) VALUES (:d,:a,:n,:s)');
  try {
    $pdo->beginTransaction();
    foreach ($records as $r) {
      $ins->execute([':d'=>$r['date'],':a'=>$r['adm'],':n'=>$r['name'],':s'=>$r['status']]);
    }
    $pdo->commit();
    echo json_encode(['success'=>true]);
  } catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
