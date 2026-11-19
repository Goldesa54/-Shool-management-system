<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $rows = $pdo->query('SELECT * FROM students ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode($rows);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if ($method === 'POST') {
  $name = $input['name'] ?? '';
  $adm = $input['adm'] ?? '';
  $form = $input['form'] ?? '';
  $stream = $input['stream'] ?? '';
  $parent = $input['parent'] ?? '';
  $phone = $input['phone'] ?? '';
  $status = $input['status'] ?? 'Active';
  if (!$name || !$adm || !$form) { http_response_code(400); echo json_encode(['error'=>'Missing fields']); exit; }
  try {
    $stmt = $pdo->prepare('INSERT INTO students (name,adm,form,stream,parent,phone,status) VALUES (:n,:a,:f,:s,:p,:ph,:st)');
    $stmt->execute([':n'=>$name,':a'=>$adm,':f'=>$form,':s'=>$stream,':p'=>$parent,':ph'=>$phone,':st'=>$status]);
    // Also create a fee record default
    $ins = $pdo->prepare('INSERT INTO fees (name,adm,amount,balance) VALUES (:n,:a,0,45000)');
    $ins->execute([':n'=>$name,':a'=>$adm]);
    echo json_encode(['success'=>true]);
  } catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error'=>'Method not allowed']);
